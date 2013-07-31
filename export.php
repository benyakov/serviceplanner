<? /* Interface for exporting data to a non-db format
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

// Exports here don't require $auth
if (is_numeric($_GET["service"])) {
    $q = queryService($dbh, $_GET['service']);
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $filebase = urlencode($row['dayname']);
    $fieldnames = array("Date", "Day", "Order", "Service Notes", "Introit",
        "Gradual", "Propers Note", "Color", "Theme", "Block", "Block Notes",
        "Lesson 1", "Lesson 2", "Gospel", "Psalm", "Collect", "Hymnbook",
        "Hymnnumber", "Hymnnote", "Hymnlocation", "Hymntitle");
    $fieldselection = array('date', 'dayname', 'rite', 'servicenotes',
        'introit', 'gradual', 'propersnote', 'color', 'theme', 'blabel',
        'bnotes', 'blesson1', 'blesson2', 'bgospel', 'bpsalm', 'bcollect',
        'book', 'number', 'note', 'location', 'title');
    $csvex = new CSVExporter($q, $filebase, "utf-8",
        $fieldnames, $fieldselection);
    $csvex->export();
}

if ('synonyms' == $_GET['export']) {
    $q = $dbh->prepare("SELECT canonical, synonym
        FROM `{$dbp}churchyear_synonyms`
        ORDER BY canonical");
    if (! $q->execute()) {
        echo array_pop($q->errorInfo());
        exit(0);
    }
    $q->setFetchMode(PDO::FETCH_NUM);
    $collector = array();
    while ($row = $q->fetch()) {
        if (! array_key_exists($row[0]), $collector)
            $collector[$row[0]] = array($row[0]);
        $collector[$row[0]][] = $row[1];
    }
    $out = array();
    foreach (array_keys($collector) as $key) {
        $out[] = $collector[$key];
    }
    $filebase = "synonyms";
    $csvex = new CSVExporter(new ArrayIterator($out), $filebase);
    $csvex->export();
}

if ('churchyear' == $_GET['export']) {
    $q = $dbh->prepare("SELECT cy.`dayname`, cy.`season`, cy.`base`,
        cy.`offset`, cy.`month`, cy.`day`,
        cy.`observed_month`, cy.`observed_sunday`
        FROM `{$dbp}churchyear` AS cy
        LEFT OUTER JOIN `{$dbp}churchyear_order` AS cyo
            ON (cy.season = cyo.name)
            ORDER BY cyo.idx, cy.offset, cy.month, cy.day");
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $filebase = "churchyear";
    $fieldnames = array("Day", "Season", "Base", "Offset", "Month", "Day",
        "Observed Month", "Observed Sunday");
    $fieldselection = array("dayname", "season", "base", "offset", "month",
        "day", "observed_month", "observed_sunday");
    $csvex = new CSVExporter($q, $filebase, "utf-8", $fieldnames,
        $fieldselection);
    $csvex->export();
}

// Below here requires $auth
if (! $auth) {
    setMessage("Access denied.");
    header("Location: index.php");
    exit(0);
}

if ($_GET['lectionary']) {
    $lectname = $_GET['lectionary'];
    $q = $dbh->prepare("SELECT `dayname`, `lesson1`, `lesson2`,
        `gospel`, `psalm`, `s2lesson`, `s2gospel`, `s3lesson`, `s3gospel`,
        `hymnabc`, `hymn` FROM `{$dbp}churchyear_lessons`
        WHERE `lectionary` = :lect");
    $q->bindValue(":lect", $lectname);
    $q->setFetchMode(PDO::FETCH_NUM);
    if (! $q->execute()) {
        echo array_pop($q->errorInfo());
        exit(0);
    }
    $filebase = $_GET['lectionary'];
    $fieldnames = array("Dayname", "Lesson 1", "Lesson 2", "Gospel", "Psalm",
        "Series 2 Lesson", "Series 2 Gospel", "Series 3 Lesson",
        "Series 3 Gospel", "Week Hymn", "Year Hymn");
    $csvex = new CSVExporter($q, $filebase, "utf-8", $fieldnames);
    $csvex->export();
}


?>
