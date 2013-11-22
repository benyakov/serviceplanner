<? /* Interface for importing data from exported
    Copyright (C) 2012 Jesse Jacobsen

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    Send feedback or donations to: Jesse Jacobsen <jmatjac@gmail.com>

    Mailed donation may be sent to:
    Bethany Lutheran Church
    2323 E. 12th St.
    The Dalles, OR 97058
    USA
 */

require("./init.php");
requireAuth("admin.php");

if ("lectionary" == $_POST['import'])
  $importer = new LectionaryImporter();
elseif ("synonyms" == $_POST['import'])
  $importer = new SynonymImporter();
elseif ("churchyear" == $_POST['import'])
  $importer = new ChurchyearImporter();
elseif ($_POST['prefix'])
    try {
        $importer = new HymnNameImporter();
    } catch (HymnTableNameError $e) {
        setMessage($e->getMessage());
        header('Location: admin.php');
        exit(0);
    }

$importer->import();

class FormImporter {
    /* Expects in $_POST:
     * import := <name of thing being imported>
     * $_POST['import'] := upload file to import
     */
    private $loadfile;

    public function _construct() {
        require_once("./utility/csv.php");
        $db = new DBConnection();
        $this->loadfile = "./load-{$db->connection['dbname']}.txt";
        if (! move_uploaded_file($_FILES['import']['tmp_name'], $loadfile)) {
            setMessage("Problem with file upload.");
            header("Location: admin.php");
            exit(0);
        }
    }

    protected function getfhandle() {
        if (($fhandle = fopen($this->loadfile, "r")) !== false) {
            if (! $keys = fgetcsv($fhandle)) {
                setMessage("Empty file upload.");
            } else return $fhandle;
        } else setMessage("Problem opening uploaded file.");
        header("Location: admin.php");
        exit(0);
    }
}

class LectionaryImporter extends FormImporter {
    /* For lectionary, also handle:
     * lectionary_name
     * replace := replacing all existing records for this lectionary
     */

    public function import() {
        $db = new DBConnection();
        $fhandle = $this->getfhandle();
        $db->beginTransaction();
        // Check for existing lessons and delete, if confirmed.
        $q = $db->prepare("SELECT 1 FROM `{$db->getPrefix()}churchyear_lessons`
            WHERE lectionary = :lectionary");
        $q->bindValue(":lectionary", $_POST['lectionary_name']);
        $q->execute() or die(array_pop($q->errorInfo()));
        if ($q->fetchColumn(0)) {
            if (isset($_POST['replace']) && "on" == $_POST['replace']) {
                $q = $db->prepare("DELETE FROM `{$db->getPrefix()}churchyear_lessons`
                    WHERE lectionary = :lectionary");
                $q->bindValue(":lectionary", $_POST['lectionary_name']);
                $q->execute() or die(array_pop($q->errorInfo()));
            } else {
                setMessage("Please confirm replacement of existing lectionary.");
                header("Location: admin.php");
                exit(0);
            }
        }
        // Create records for new lessons
        $q = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear_lessons`
            (lectionary, dayname, lesson1, lesson2, gospel, psalm,
            s2lesson, s2gospel, s3lesson, s3gospel, hymnabc, hymn)
            VALUES
            (:lectionary, :dayname, :lesson1, :lesson2, :gospel, :psalm,
            :s2lesson, :s2gospel, :s3lesson, :s3gospel, :hymnabc, :hymn)");
        $thisrec = array("Dayname"=>"", "Lesson 1"=>"", "Lesson 2"=>"",
            "Gospel"=>"", "Psalm"=>"", "Series 2 Lesson"=>"",
            "Series 2 Gospel"=>"", "Series 3 Lesson"=>"",
            "Series 3 Gospel"=>"", "Week Hymn"=>"", "Year Hymn"=>"");
        $q->bindParam(":lectionary", $_POST['lectionary_name']);
        $q->bindParam(":dayname", $thisrec["Dayname"]);
        $q->bindParam(":lesson1", $thisrec["Lesson 1"]);
        $q->bindParam(":lesson2", $thisrec["Lesson 2"]);
        $q->bindParam(":gospel", $thisrec["Gospel"]);
        $q->bindParam(":psalm", $thisrec["Psalm"]);
        $q->bindParam(":s2lesson", $thisrec["Series 2 Lesson"]);
        $q->bindParam(":s2gospel", $thisrec["Series 2 Gospel"]);
        $q->bindParam(":s3lesson", $thisrec["Series 3 Lesson"]);
        $q->bindParam(":s3gospel", $thisrec["Series 3 Gospel"]);
        $q->bindParam(":hymnabc", $thisrec["Week Hymn"]);
        $q->bindParam(":hymn", $thisrec["Year Hymn"]);
        while ($record = fgetcsv($fhandle)) {
            for ($i=0; $i<count($keys); $i++)
                $thisrec[$keys[$i]] = $record[$i];
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $db->commit();
        setMessage("Lectionary imported.");
        header("Location: admin.php");
        exit(0);
    }
}

class ChurchyearImporter extends FormImporter {
/* For churchyear, also handle:
 * replaceall := remove all current days in churchyear before loading
 *  Otherwise, only replace days already defined.
 */
    public function import() {
        $fhandle = $this->getfhandle();
        setMessage("Church year data imported.");
        header("Location: admin.php");
        exit(0);
    }
}

class SynonymImporter extends FileImporter {
/* For synonyms, also handle:
 * replace := replace all synonyms for the given left-hand words
 *  (Caution: synonym deletions will cascade into other tables.)
 */

    function import() {
        $db = new DBConnection();
        $fhandle = $this->getfhandle();
        $db->beginTransaction();
        $canonical = ""; $synonym = "";
        // Replace using temporary tables;
        if (isset($_POST['replace']) && "on" == $_POST['replace']) {
            // Upload the new synonyms
            $db->exec("CREATE TEMPORARY TABLE `{$db->getPrefix()}newsynonyms`
                    `canonical` varchar(255),
                    `synonym`   varchar(255))
                    ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $q = $db->prepare("INSERT INTO `{$db->getPrefix()}newsynonyms`
                (`canonical`, `synonym`)
                VALUES (:canonical, :synonym)");
            $q->bindParam(":canonical", $canonical);
            $q->bindParam(":synonym", $synonym);
            while ($oneset = fgetcsv($fhandle)) {
               $canonical = $oneset[0];
               for ($i=1; $i<count($oneset); $i++) {
                   $synonym = $oneset[$i];
                   $q->exec or die(array_pop($q->errorInfo()));
               }
            }
            rewind($fhandle);
            // Add new synonyms not in current db
            $db->exec("CREATE TEMPORARY TABLE `{$db->getPrefix()}addsynonyms`
                    `canonical` varchar(255),
                    `synonym`   varchar(255))
                    ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $db->exec("INSERT INTO `{$db->getPrefix()}addsynonyms`
                SELECT n.`canonical`, n.`synonym`
                FROM `{$db->getPrefix()}newsynonyms` AS n
                LEFT JOIN `{$db->getPrefix()}churchyear_synonyms` AS cy
                ON (cy.`canonical` = n.`canonical`
                    AND cy.`synonym` = n.`synonym`)
                WHERE cy.`synonym` == NULL");
            $db->exec("INSERT INTO `{$db->getPrefix()}churchyear_synonyms
                SELECT `canonical`, `synonym`
                FROM `{$db->getPrefix()}addsynonyms`");
            // Remove current db canonicals not in new list
            // (This will cascade into other tables.)
            $db->exec("DELETE FROM `{$db->getPrefix()}churchyear_synonyms`
                WHERE ! `canonical` IN
                (SELECT DISTINCT `canonical`
                    FROM `{$db->getPrefix()}newsynonyms`)");
        } else {
            $qexact = $db->prepare("SELECT 1
                FROM `{$db->getPrefix()}churchyear_synonyms`
                WHERE `canonical` = :canonical
                AND `synonym` = :synonym");
            $qinsert = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear_synonyms`
                (`canonical`, `synonym`) VALUES (:canonical, :synonym)");
            $qexact->bindParam(":canonical", $canonical);
            $qexact->bindParam(":synonym", $synonym);
            $qinsert->bindParam(":canonical", $canonical);
            $qinsert->bindParam(":synonym", $synonym);
            while (list($canonical, $synonym) = fgetcsv($fhandle)) {
                // If the record already exists, leave it
                $qexact->execute() or die(array_pop($qexact->errorInfo()));
                if ($qexact->fetchValue(1)) {
                    continue;
                }
                $qinsert->execute() or die(array_pop($qinsert->errorInfo()));
            }
        }
        $db->commit();
        setMessage("Synonyms imported.");
        header("Location: admin?flag=create-views");
        exit(0);
    }
}

class HymnNameImporter {
    public function __construct() {
        if (! strpos($_POST['prefix'], ' ') === false)
            throw new HymnTableNameError("Bad prefix: `".htmlentities($_POST['prefix']."'"));
        $db = new DBConnection();
        $this->namestable = $db->quote("{$_POST['prefix']}names");
        $q = $db->query("SHOW TABLES LIKE '{$this->namestable}'");
        if (! count($q->fetchAll()))
            throw new HymnTableNameError("No names table exists with prefix `".htmlentities($_POST['prefix'])."'");

    }

    function import() {
        $db = new DBConnection();
        $rowcount = $db->exec("INSERT IGNORE INTO `{$db->getPrefix()}names`
            (book, number, title)
            SELECT n2.book, n2.number, n2.title
                FROM `{$this->namestable}` AS n2");
        setMessage($rowcount . " hymn names imported.");
        header('Location: admin.php');
        exit(0);
    }
}

class HymnTableNameError extends Exception { }


// vim: set foldmethod=indent :
?>
