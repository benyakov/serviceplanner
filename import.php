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
require("./utility/csv.php");
requireAuth("admin.php");

if ("lectionary" == $_POST['import'])
  $importer = new LectionaryImporter();
elseif ("synonyms" == $_POST['import'])
  $importer = new SynonymImporter();
elseif ("churchyear" == $_POST['import'])
  $importer = new ChurchyearImporter();

class FormImporter {
    /* Expects in $_POST:
     * import := <name of thing being imported>
     * $_POST['import'] := upload file to import
     */
    private $loadfile;

    public function _construct() {
        $dbconnection = new DBConnection();
        $this->loadfile = "./load-{$dbconnection['dbname']}.txt";
        if (! move_uploaded_file($_FILES['import']['tmp_name'], $loadfile)) {
            setMessage("Problem with file upload.");
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
        $dbh = new DBConnection();
        $dbp = $dbh->prefix;
        if (($fhandle = fopen($loadfile, "r")) !== false) {
            if (! $keys = fgetcsv($fhandle)) {
                setMessage("Empty file upload.");
                header("Location: admin.php");
                exit(0);
            }
            $dbh->beginTransaction();
            // Check for existing lessons and delete, if confirmed.
            $q = $dbh->prepare("SELECT 1 FROM `{$dbp}churchyear_lessons`
                WHERE lectionary = :lectionary");
            $q->bindValue(":lectionary", $_POST['lectionary_name']);
            $q->execute() or die(array_pop($q->errorInfo()));
            if ($q->fetchColumn(0)) {
                if (isset($_POST['replace']) && "on" == $_POST['replace']) {
                    $q = $dbh->prepare("DELETE FROM `{$dbp}churchyear_lessons`
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
            $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_lessons`
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
            $dbh->commit();
            setMessage("Loaded lectionary data.");
            header("Location: admin.php");
        } else {
            setMessage("Problem opening uploaded file.");
        }
        setMessage("Lectionary imported.");
        header("Location: admin.php");
        exit(0);
    }
}

/* For synonyms, also handle:
 * replace := replace all synonyms for the given left-hand words
 *  (Caution: synonym deletions will cascade into other tables.)
 *
 * For churchyear, also handle:
 * replaceall := remove all current days in churchyear before loading
 *  Otherwise, only replace days already defined.
 */



function importChurchyear($loadfile) {
    if (($fhandle = fopen($loadfile, "r")) !== false) {
        if (! $keys = fgetcsv($fhandle)) {
            setMessage("Empty file upload.");
            header("Location: admin.php");
            exit(0);
        }
    } else {
        setMessage("Problem opening uploaded file.");
    }
    setMessage("Church year data imported.");
    header("Location: admin.php");
    exit(0);
}

function importSynonyms($loadfile) {
    if (($fhandle = fopen($loadfile, "r")) !== false) {
        $dbh->beginTransaction();
        $canonical = ""; $synonym = "";
        // Replace using temporary tables;
        if (isset($_POST['replace']) && "on" == $_POST['replace']) {
            // Upload the new synonyms
            $dbh->exec("CREATE TEMPORARY TABLE `{$dbh}newsynonyms`
                    `canonical` varchar(255),
                    `synonym`   varchar(255))
                    ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $q = $dbh->prepare("INSERT INTO `{$dbh}newsynonyms`
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
            $dbh->exec("CREATE TEMPORARY TABLE `{$dbh}addsynonyms`
                    `canonical` varchar(255),
                    `synonym`   varchar(255))
                    ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $dbh->exec("INSERT INTO `{$dbh}addsynonyms`
                SELECT n.`canonical`, n.`synonym`
                FROM `{$dbh}newsynonyms` AS n
                LEFT JOIN `{$dbh}churchyear_synonyms` AS cy
                ON (cy.`canonical` = n.`canonical`
                    AND cy.`synonym` = n.`synonym`)
                WHERE cy.`synonym` == NULL");
            $dbh->exec("INSERT INTO `{$dbh}churchyear_synonyms
                SELECT `canonical`, `synonym`
                FROM `{$dbh}addsynonyms`");
            // Remove current db canonicals not in new list
            // (This will cascade into other tables.)
            $dbh->exec("DELETE FROM `{$dbh}churchyear_synonyms`
                WHERE ! `canonical` IN
                (SELECT DISTINCT `canonical` FROM `{$dbh}newsynonyms`)");
        } else {
            $qexact = $dbh->prepare("SELECT 1 FROM `{$dbh}churchyear_synonyms`
                WHERE `canonical` = :canonical
                AND `synonym` = :synonym");
            $qinsert = $dbh->prepare("INSERT INTO `{$dbh}churchyear_synonyms`
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
        $dbh->commit();
    } else {
        setMessage("Problem opening uploaded file.");
    }
    setMessage("Synonyms imported.");
    header("Location: admin?flag=create-views");
    exit(0);
}



// vim: set foldmethod=indent :
?>
