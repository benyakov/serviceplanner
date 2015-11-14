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
elseif ("churchyear-collects" == $_POST['import'])
    $importer = new CollectSeriesImporter(array(
        "import-file"=>true,
        "import-assignments-file"=>true));
elseif ("hymnnames" == $_POST['import'])
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
     * $_POST['import-file'] := upload file to import
     */
    private $loadindex = 0;
    protected $loadfiles = array();
    protected $fhandle = array();
    protected $keys = array();
    protected $usekeys = array();

    private function addLoadFile($name) {
        $db = new DBConnection();
        $loadfile = "./load-".$db->getName().$this->loadindex++.".csv";
        $this->loadfiles[$name] = $loadfile;
        return $loadfile;
    }

    public function __construct($extrafiles=array()) {
        if ((! $extrafiles) && array_key_exists('import-file', $_FILES)) {
            $extrafiles['import-file'] = true;
        }
        foreach ($extrafiles as $ef=>$usekeys) {
            $this->addLoadFile($ef);
            $this->usekeys[$ef] = $usekeys;
        }
        require_once("./utility/csv.php");
        foreach ($this->loadfiles as $loadname=>$loadfile)
            if (! move_uploaded_file($_FILES[$loadname]['tmp_name'], $loadfile))
            {
                setMessage("Problem with file upload:$loadname->".$_FILES[$loadname]['tmp_name']);
                header("Location: admin.php");
                exit(0);
            }
    }

    public function __destruct() {
        foreach ($this->loadfiles as $loadname=>$loadfile)
            unlink($loadfile);
        foreach ($this->fhandle as $fh)
            fclose($fh);
    }

    public function getKeys($name='import-file') {
        if (!$this->usekeys[$name]) return array();
        if (! isset($this->fhandle[$name])) {
            $this->getFHandle($name); // populates $this->keys
            return $this->keys[$name];
        } else return $this->keys[$name];
    }

    public function getRecord($name='import-file') {
        if (! $this->fhandle[$name]) $this->getFHandle($name);
        if ($rec = fgetcsv($this->fhandle[$name])) {
            if ($this->usekeys[$name]) {
                $vals = array_fill(0, count($this->getKeys($name)), NULL);
                $rv = array_combine($this->getKeys($name), $vals);
                for ($i=0, $len=count($rec); $i<$len; $i++)
                    $rv[$this->keys[$name][$i]] = $rec[$i];
            } else $rv=$rec;
            return $rv;
        } else {
            return $rec;
        }
    }

    protected function getFHandle($name='import-file') {
        if (isset($this->fhandle[$name])) return $this->fhandle[$name];
        if (($this->fhandle[$name] = fopen($this->loadfiles[$name], "r"))
            !== false)
        {
            if (! $this->keys[$name] = fgetcsv($this->fhandle[$name])) {
                setMessage("Empty file upload.");
            } else {
                if (! $this->usekeys[$name]) {
                    $this->keys[$name] = false;
                    rewind($this->fhandle[$name]);
                }
                return $this->fhandle[$name];
            }
        } else {
            setMessage("Problem opening uploaded file.");
            header("Location: admin.php");
            exit(0);
        }
    }

    protected function rewind($name='import-file') {
        if ($this->fhandle[$name] === NULL) {
            $fh = $this->getFHandle($name);
            rewind($fh);
        } else {
            rewind($this->fhandle[$name]);
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
                setMessage("CSV file contains unknown field '{$fieldname}'");
                header("Location: admin.php");
                exit(0);
            }
        }
        $qcheck = $db->prepare("SELECT COUNT(*)
            FROM `{$db->getPrefix()}churchyear_synonyms`
            WHERE `synonym` = :check");
        $check_day = NULL;
        $qcheck->bindParam(":check", $check_day);
        while ($record = $this->getRecord()) {
            foreach ($thisrec as $key=>&$value) {
                $value = $record[$key];
            }
            $check_day = $thisrec["Dayname"];
            $qcheck->execute() or die(array_pop($qcheck->errorInfo()));
            if ($qcheck->fetchColumn(0) < 1) {
                $unknowns[] = $check_day;
                continue;
            }
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $db->commit();
        setMessage("Lectionary imported. Unrecognized Days: ["
            . implode(", ", $unknowns) . "]");
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
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        if ("on" == $_POST['replaceall']) {
            $q = $db->prepare("UPDATE `{$db->getPrefix()}churchyear` AS cy,
                `{$db->getPrefix()}newchurchyear` AS ncy
                SET cy.season=ncy.season, cy.base=ncy.base,
                cy.offset=ncy.offset, cy.month=ncy.month, cy.day=ncy.day,
                cy.observed_month=ncy.observed_month,
                cy.observed_sunday=ncy.observed_sunday
                WHERE cy.dayname=ncy.dayname");
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        if ("on" == $_POST['deletemissing']) {
            // Remove current churchyear days not in new list
            $db->exec("DELETE FROM `{$db->getPrefix()}churchyear`
                WHERE `dayname` NOT IN
                (SELECT dayname FROM `{$db->getPrefix()}newchurchyear`)");
        }
        // Add previously unknown days
        $db->exec("INSERT IGNORE INTO `{$db->getPrefix()}churchyear`
            SELECT * FROM `{$db->getPrefix()}newchurchyear`");
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
        $q = $db->prepare("INSERT IGNORE INTO
                `{$db->getPrefix()}churchyear_propers`
                (dayname, color, theme, introit, gradual, note)
                VALUES (:dayname, :color, :theme, :introit, :gradual, :note)");
        $oneset = array("Dayname"=>NULL, "Color"=>NULL, "Theme"=>NULL,
            "Introit"=>NULL, "Gradual"=>NULL, "Note"=>NULL);
        $q->bindParam(":dayname", $oneset["Dayname"]);
        $q->bindParam(":color", $oneset["Color"]);
        $q->bindParam(":theme", $oneset["Theme"]);
        $q->bindParam(":introit", $oneset["Introit"]);
        $q->bindParam(":gradual", $oneset["Gradual"]);
        $q->bindParam(":note", $oneset["Note"]);
        while ($record = $this->getRecord()) {
            foreach (array_keys($oneset) as $key) {
                $oneset[$key] = $record[$key];
            }
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $db->commit();
        setMessage("Church year general propers data imported.");
        header("Location: admin.php");
        exit(0);
    }
}

/**
 * Imports a series of collects provided in a 2 CSV export files.
 */
class CollectSeriesImporter extends FormImporter {
    public function import() {
        $db = new DBConnection();
        $db->beginTransaction();
        // Deletes cascade into churchyear_collect_index
        $q = $db->prepare("DELETE FROM
            `{$db->getPrefix()}churchyear_collects`
            WHERE class = :class");
        $q->bindParam(":class", $_POST['collect-series']);
        $q->execute() or die(array_pop($q->errorInfo()));
        $newids = array();
        while ($record = $this->getRecord('import-file')) {
            $q = $db->prepare("
              INSERT INTO `{$db->getPrefix()}churchyear_collects`
              (class, collect) VALUES (:class, :collect)");
            $q->bindParam(":class", $_POST['collect-series']);
            $q->bindParam(":collect", $record['Collect']);
            $q->execute() or die(array_pop($q->errorInfo()));
            $qid = $db->query("SELECT LAST_INSERT_ID()");
            $id = $qid->fetchColumn(0);
            $newids[$record['ID']] = $id;
        }
        while ($record = $this->getRecord('import-assignments-file')) {
            $q = $db->prepare("
              INSERT INTO `{$db->getPrefix()}churchyear_collect_index`
              (dayname, lectionary, id)
              VALUES (:dayname, :lectionary, :id)");
            $q->bindParam(":dayname", $record['Dayname']);
            $q->bindParam(":lectionary", $record['Lectionary']);
            $q->bindParam(":id", $newids[$record['ID']]);
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $db->commit();
        setMessage("Collect series imported.");
        header("Location: admin.php");
        exit(0);
    }
}


/**
 * Imports synonyms provided in a CSV export file
 */
class SynonymImporter extends FormImporter {

    function __construct() {
        parent::__construct(array("import-file"=>false));
    }

    /**
     * Return the next CSV record as an array of 2-item synonym arrays
     */
    function nextSynonymList() {
        if ($rec = $this->getRecord()) {
            $rv = array();
            foreach (array_slice($rec, 1) as $synonym) {
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
                        $q->execute()
                            or die("2 ".array_pop($q->errorInfo()));
                    }
                }
            } catch (EndOfSynonymFile $e) {
                $this->rewind();
            }
            /**
             * Remove current db canonicals not in new list
             * This will cascade into other tables.
             */
            // First, remove canonicals not present in new list
            $q = $db->prepare("DELETE
                FROM `{$db->getPrefix()}churchyear_synonyms`
                WHERE canonical NOT IN
                    (SELECT DISTINCT canonical
                    FROM `{$db->getPrefix()}newsynonyms`)");
            $q->execute() or die("3 ".array_pop($q->errorInfo()));
            // Now, remove old canonicals with conflicting synonyms
            $q = $db->prepare("DELETE cy
                FROM `{$db->getPrefix()}newsynonyms` AS n,
                    `{$db->getPrefix()}churchyear_synonyms` AS cy
                WHERE (cy.canonical != n.canonical
                    AND cy.synonym = n.synonym)");
            $q->execute() or die("4 ".array_pop($q->errorInfo()));
            /* Remove existing matches from new list (could use INSERT IGNORE)
            $q = $db->prepare("DELETE n
                FROM `{$db->getPrefix()}newsynonyms` as n,
                    `{$db->getPrefix()}churchyear_synonyms` AS cy
                WHERE (cy.canonical = n.canonical
                    AND cy.synonym = n.synonym)");
            $q->execute() or die("5 ".array_pop($q->errorInfo()));
             */
            // Add previously unknown synonyms
            //   ... first, adding unknown daynames to the churchyear...
            $q = $db->prepare("SELECT DISTINCT canonical
                FROM `{$db->getPrefix()}newsynonyms`
                WHERE canonical NOT IN
                    (SELECT dayname FROM `{$db->getPrefix()}churchyear`)");
            $q->execute() or die("6 ".array_pop($q->errorInfo()));
            $newdays = $q->fetchall();
            if ($newdays) {
                $q = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear`
                    (dayname) VALUES (:dayname)");
                $dayname = "";
                $q->bindParam(":dayname", $dayname);
                foreach ($newdays as $rec) {
                    $dayname = $rec[0];
                    $q->execute() or die("7 ".array_pop($q->errorInfo()));
                }
            }
            //   ... then, inserting as synonyms
            $q = $db->prepare("INSERT IGNORE INTO
                `{$db->getPrefix()}churchyear_synonyms`
                SELECT `canonical`, `synonym`
                FROM `{$db->getPrefix()}newsynonyms`");
            $q->execute() or die("8 ".array_pop($q->errorInfo()));
        } else {
            $qcheckchurchyear = $db->prepare("SELECT 1
                FROM `{$db->getPrefix()}churchyear`
                WHERE dayname = ?");
            $qaddtochurchyear = $db->prepare("INSERT INTO
                `{$db->getPrefix()}churchyear` (dayname) VALUES (?)");
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
                            $qcheckchurchyear->execute(array($canonical))
                                or die("point 2".array_pop($qinsert->errorInfo()));
                            if (! $qcheckchurchyear->fetch()) {
                                $qaddtochurchyear->execute(array($canonical))
                                or die("point 3".array_pop($qinsert->errorInfo()));
                            }
                            $qinsert->execute()
                                or die("point 4".array_pop($qinsert->errorInfo()));
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
