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
elseif ("churchyear-propers" == $_POST['import'])
  $importer = new ChurchyearPropersImporter();
elseif ($_POST['prefix'])
    try {
        $importer = new HymnNameImporter();
    } catch (HymnTableNameError $e) {
        setMessage($e->getMessage());
        header('Location: admin.php');
        exit(0);
    }

$importer->import();

/**
 * Base class for importing things from CSV.
 */
class FormImporter {
    /* Expects in $_POST:
     * import := <name of thing being imported>
     * $_POST['import'] := upload file to import
     */
    protected $loadfile;
    protected $fhandle = NULL;
    protected $keys;
    protected $usekeys;

    public function __construct($usekeys=true) {
        require_once("./utility/csv.php");
        $this->usekeys = $usekeys;
        $db = new DBConnection();
        $this->loadfile = "./load-{$db->getName()}.txt";
        if (! move_uploaded_file($_FILES['import']['tmp_name'], $this->loadfile)) {
            setMessage("Problem with file upload.");
            header("Location: admin.php");
            exit(0);
        }
    }

    public function getKeys() {
        if ($this->usekeys && (! $this->fhandle)) $this->getFHandle();
        return $this->keys;
    }

    public function getRecord() {
        if (! $this->fhandle) $this->getFHandle();
        if ($rec = fgetcsv($this->fhandle)) {
            if ($this->usekeys) {
                $vals = array_fill(0, count($this->keys), NULL);
                $rv = array_combine($this->keys, $vals);
                for ($i=0, $len=count($rec); $i<$len; $i++)
                    $rv[$this->keys[$i]] = $rec[$i];
            } else $rv=$rec;
            return $rv;
        } else {
            return $rec;
        }
    }

    protected function getFHandle() {
        if ($this->fhandle !== NULL) return $this->fhandle;
        if (($this->fhandle = fopen($this->loadfile, "r")) !== false) {
            if (! $this->keys = fgetcsv($this->fhandle)) {
                setMessage("Empty file upload.");
            } else {
                if (! $this->usekeys) {
                    $this->keys = false;
                    rewind($this->fhandle);
                }
                return $fhandle;
            }
        } else setMessage("Problem opening uploaded file.");
        header("Location: admin.php");
        exit(0);
    }

    protected function rewind() {
        if ($this->fhandle === NULL) {
            $fh = $this->getFHandle();
            rewind($fh);
        } else {
            rewind($this->fhandle);
        }
    }
}

/**
 * Imports a lectionary provided in a CSV export file
 */
class LectionaryImporter extends FormImporter {
    /* For lectionary, also handle:
     * lectionary_name
     * replace := replacing all existing records for this lectionary
     */

    public function import() {
        $db = new DBConnection();
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
        $thisrec = array("Dayname"=>NULL, "Lesson 1"=>NULL, "Lesson 2"=>NULL,
            "Gospel"=>NULL, "Psalm"=>NULL, "Series 2 Lesson"=>NULL,
            "Series 2 Gospel"=>NULL, "Series 3 Lesson"=>NULL,
            "Series 3 Gospel"=>NULL, "Week Hymn"=>NULL, "Year Hymn"=>NULL);
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
        // Verify that the CSV file contains the appropriate fields
        foreach ($this->getKeys() as $fieldname) {
            if (! array_key_exists($fieldname, $thisrec)) {
                setMessage("CSV file contain unknown field '{$fieldname}'");
                header("Location: admin.php");
                exit(0);
            }
        }
        while ($record = $this->getRecord()) {
            foreach ($thisrec as $key=>&$value) {
                $value = $record[$key];
            }
            if (! $q->execute()) die(array_pop($q->errorInfo()));
        }
        $db->commit();
        setMessage("Lectionary imported.");
        header("Location: admin.php");
        exit(0);
    }
}

/**
 * Imports church year data provided in a CSV export file
 */
class ChurchyearImporter extends FormImporter {
    public function import() {
        $db = new DBConnection();
        $db->beginTransaction();
        // Upload the new days
        $db->exec("CREATE TEMPORARY TABLE `{$db->getPrefix()}newchurchyear`
                LIKE `{$db->getPrefix()}churchyear`");
        $q = $db->prepare("INSERT INTO `{$db->getPrefix()}newchurchyear`
            (dayname, season, base, offset, month, day, observed_month,
             observed_sunday)
             VALUES (:dayname, :season, :base, :offset, :month, :day,
                 :observed_month, :observed_sunday)");
        $q->bindParam(":dayname", $dayname);
        $q->bindParam(":season", $season);
        $q->bindParam(":base", $base);
        $q->bindParam(":offset", $offset);
        $q->bindParam(":month", $month);
        $q->bindParam(":day", $day);
        $q->bindParam(":observed_month", $observed_month);
        $q->bindParam(":observed_sunday", $observed_sunday);
        while ($oneset = $this->getRecord()) {
            // See export.php:87
            $dayname = $oneset["Dayname"];
            $season = $oneset["Season"];
            $base = $oneset["Base"];
            $offset = $oneset["Offset"];
            $month = $oneset["Month"];
            $day = $oneset["Day"];
            $observed_month = $oneset["Observed Month"];
            $observed_sunday = $oneset["Observed Sunday"];
            $q->exec or die(array_pop($q->errorInfo()));
        }
        $this->rewind();
        // Record daynames not in current db
        $q = $db->exec("CREATE TEMPORARY TABLE `{$db->getPrefix()}addchurchyear`
            LIKE `{$db->getPrefix()}churchyear`");
        $db->exec("INSERT INTO `{$db->getPrefix()}addchurchyear`
            SELECT * FROM `{$db->getPrefix()}newchurchyear` AS n
            LEFT OUTER JOIN `{$db->getPrefix()}churchyear` AS cy
            ON (cy.dayname = n.dayname)
            WHERE cy.dayname == NULL");
        // Remove current churchyear days not in new list
        $db->exec("DELETE FROM `{$db->getPrefix()}churchyear`
            WHERE ! `dayname` IN
            (SELECT DISTINCT cy.dayname
            FROM `{$db->getPrefix()}newchurchyear` AS n
            RIGHT OUTER JOIN `{$db->getPrefix()}churchyear` AS cy
            ON (cy.dayname = n.dayname)
            WHERE n.dayname == NULL)");
        if (isset($_POST['replaceall']) && "on" == $_POST['replaceall']) {
            // Update all daynames
            $db->exec("UPDATE `{$db->getPrefix()}churchyear` AS cy,
                `{$db->getPrefix()}newchurchyear` AS n
                SET cy.season=n.season, cy.base=n.base, cy.offset=n.offset,
                cy.month=n.month, cy.day=n.day,
                cy.observed_month=n.observed_month,
                cy.observed_sunday=n.observed_sunday
                WHERE cy.dayname=n.dayname");
        }
        // Add previously unknown days
        $db->exec("INSERT INTO `{$db->getPrefix()}churchyear`
            SELECT * FROM `{$db->getPrefix()}addchurchyear`");
        $db->commit();
        setMessage("Church year data imported.");
        header("Location: admin.php");
        exit(0);
    }
}

/**
 * Imports general church year propers provided in a CSV export file
 */
class ChurchyearPropersImporter extends FormImporter {
    public function import() {
        $db = new DBConnection();
        $db->beginTransaction();
        if (isset($_POST['replace']) && "on" == $_POST['replace']) {
            $db->query("DELETE FROM `{$db->getPrefix()}churchyear_propers`");
        }
        $q = $db->prepare("REPLACE INTO
                `{$db->getPrefix()}churchyear_propers` AS cp
                (dayname, color, theme, introit, gradual, note)
                VALUES (:dayname, :color, :theme, :introit, :gradual, :note)");
        $oneset = array("dayname"=>NULL, "color"=>NULL, "theme"=>NULL,
            "introit"=>NULL, "gradual"=>NULL, "note"=>NULL);
        $q->bindParam(":dayname", $oneset["dayname"]);
        $q->bindParam(":color", $oneset["color"]);
        $q->bindParam(":theme", $oneset["theme"]);
        $q->bindParam(":introit", $oneset["introit"]);
        $q->bindParam(":gradual", $oneset["gradual"]);
        $q->bindParam(":note", $oneset["note"]);
        while ($record = $this->getRecord()) {
            foreach (array_keys($oneset) as $key) {
                $oneset[$key] = $record[$key];
            }
            $q->execute or die(array_pop($q->errorInfo()));
        }
        setMessage("Church year general propers data imported.");
        header("Location: admin.php");
        exit(0);
    }
}

/**
 * Imports synonyms provided in a CSV export file
 */
class SynonymImporter extends FormImporter {

    function __construct() {
        parent::__construct(false);
    }

    /**
     * Return the next CSV record as an array of 2-item synonym arrays
     */
    function nextSynonymList() {
        if ($rec = $this->getRecord()) {
            $rv = array();
            foreach (array_slice($rec, 2) as $synonym) {
                $rv[] = array($rec[0], $synonym);
            }
            return $rv;
        } else throw new EndOfSynonymFile();
    }

    function import() {
        $db = new DBConnection();
        $fhandle = $this->getFHandle();
        $db->beginTransaction();
        $canonical = ""; $synonym = "";
        // Replace using temporary tables;
        if ("on" == $_POST['replace']) {
            // Upload the new synonyms
            $db->exec("CREATE TEMPORARY TABLE `{$db->getPrefix()}newsynonyms`
                    LIKE `{$db->getPrefix()}churchyear_synonyms`");
            $q = $db->prepare("INSERT INTO `{$db->getPrefix()}newsynonyms`
                (`canonical`, `synonym`)
                VALUES (:canonical, :synonym)");
            $q->bindParam(":canonical", $canonical);
            $q->bindParam(":synonym", $synonym);
            try {
                while (true) {
                    foreach($this->nextSynonymList() as $s) {
                        list($canonical, $synonym) = $s;
                        $q->execute() or die("Dying ".array_pop($q->errorInfo()));
                    }
                }
            } catch (EndOfSynonymFile $e) {
                $this->rewind();
            }
            // Record synonyms not in current db (add)
            $db->exec("CREATE TEMPORARY TABLE `{$db->getPrefix()}addsynonyms`
                LIKE `{$db->getPrefix()}churchyear_synonyms`");
            $q = $db->prepare("INSERT INTO `{$db->getPrefix()}addsynonyms`
                SELECT n.`canonical`, n.`synonym`
                FROM `{$db->getPrefix()}newsynonyms` AS n
                LEFT JOIN `{$db->getPrefix()}churchyear_synonyms` AS cy
                ON (cy.`canonical` = n.`canonical`
                    AND cy.`synonym` = n.`synonym`)
                WHERE cy.`synonym` = NULL");
            $q->execute() or die("1 ".array_pop($q->errorInfo()));
            // Add previously unknown synonyms
            $q = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear_synonyms`
                SELECT `canonical`, `synonym`
                FROM `{$db->getPrefix()}addsynonyms`");
            $q->execute() or die("2 ".array_pop($q->errorInfo()));
            // Remove current db canonicals not in new list
            // (This will cascade into other tables.)
            $q = $db->prepare("DELETE cy
                FROM `{$db->getPrefix()}churchyear_synonyms` AS cy
                WHERE cy.canonical NOT IN
                    (SELECT DISTINCT canonical
                    FROM `{$db->getPrefix()}newsynonyms`)");
            $q->execute() or die("4 ".array_pop($q->errorInfo()));
            $q = $db->prepare("DELETE cy
                FROM `{$db->getPrefix()}newsynonyms` AS n,
                    `{$db->getPrefix()}churchyear_synonyms` AS cy
                WHERE (cy.canonical != n.canonical
                    AND cy.synonym = n.synonym)");
            $q->execute() or die("3 ".array_pop($q->errorInfo()));
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
            try {
                while (true) {
                    foreach ($this->nextSynonymList() as $s) {
                        // If the record already exists, leave it
                        list($canonical, $synonym) = $s;
                        $qexact->execute() or die("point 1".array_pop($qexact->errorInfo()));
                        if ($qexact->fetch()) {
                            continue;
                        } else {
                            $qinsert->execute()
                                or die("point 2".array_pop($qinsert->errorInfo()));
                        }
                    }
                }
            } catch (EndOfSynonymFile $e) {
                $this->rewind();
            }
        }
        $db->commit();
        setMessage("Synonyms imported.");
        header("Location: admin.php?flag=create-views");
        exit(0);
    }
}

/**
 * Imports Hymn names set in a sister installation in the same database.
 */
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

class EndOfSynonymFile extends Exception { }

// vim: set foldmethod=indent :
?>
