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

if (! $auth) {
    setMessage("Access denied.");
    header("Location: index.php");
    exit(0);
}

$loadfile = "./load-{$dbconnection['dbname']}.txt";

if ("lectionary" == $_POST['import']) {
    if (! move_uploaded_file($_FILES['lectionary']['tmp_name'], $loadfile)) {
        setMessage("Problem with file upload.");
        header("Location: index.php");
        exit(0);
    }
    if (($fhandle = fopen($loadfile, "r")) !== false) {
        if (! $keys = fgetcsv($fhandle)) {
            setMessage("Empty file upload.");
            header("Location: index.php");
            exit(0);
        }
        $dbh->beginTransaction();
        $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_lessons` SET
            lectionary = :lectionary, dayname = :dayname, lesson1 = :lesson1,
            lesson2 = :lesson2, gospel = :gospel, psalm = :psalm,
            s2lesson = :s2lesson, s2gospel = :s2gospel, s3lesson = :s3lesson,
            s3gospel = :s3gospel, hymnabc = :hymnabc, hymn = :hymn");
        while ($record = fgetcsv($fhandle)) {
            $thisrec = array();
            for ($i=0; $i<count($keys); $i++)
                $thisrec[$keys[$i]] = $record[$i]];
            $q->bindValue(":lectionary", $_POST['lectionary_name']);
            $q->bindValue(":dayname", $thisrec["Dayname"]);
            $q->bindValue(":lesson1", $thisrec["Lesson 1"]);
            $q->bindValue(":lesson2", $thisrec["Lesson 2"]);
            $q->bindValue(":gospel", $thisrec["Gospel"]);
            $q->bindValue(":psalm", $thisrec["Psalm"]);
            $q->bindValue(":s2lesson", $thisrec["Series 2 Lesson"]);
            $q->bindValue(":s2gospel", $thisrec["Series 2 Gospel"]);
            $q->bindValue(":s3lesson", $thisrec["Series 3 Lesson"]);
            $q->bindValue(":s3gospel", $thisrec["Series 3 Gospel"]);
            $q->bindValue(":hymnabc", $thisrec["Week Hymn"]);
            $q->bindValue(":hymn", $thisrec["Year Hymn"]);
            $q->execute();
        }
        $dbh->commit();
        setMessage("Loaded lectionary data.");
        header("Location: admin.php");
    } else {
        setMessage("Problem opening uploaded file.");
    }
    exit(0);
}



?>
