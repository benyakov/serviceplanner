<?php /* Interface for exporting data to a non-db format
    Copyright (C) 2023 Jesse Jacobsen

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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */

require("./init.php");
require("./utility/csv.php");

// Exports here don't require auth
if (is_numeric(getGET('service'))) {
    $q = queryService(getGET('service'));
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $csvex = new CSVExporter($q);
    $csvex->setCharset("utf-8");
    $csvex->setFieldnames(array("Date", "Day", "Order", "Service Notes",
        "Introit", "Gradual", "Propers Note", "Color", "Theme", "Block",
        "Block Notes", "Lesson 1", "Lesson 2", "Gospel", "Psalm", "Collect",
        "Hymnbook", "Hymnnumber", "Hymnnote", "Hymnoccurrence", "Hymntitle"));
    $csvex->setFieldselection(array('date', 'dayname', 'rite', 'servicenotes',
        'introit', 'gradual', 'propersnote', 'color', 'theme', 'blabel',
        'bnotes', 'blesson1', 'blesson2', 'bgospel', 'bpsalm', 'bcollect',
        'book', 'number', 'note', 'occurrence', 'title'));
    $csvex->setFilebaseIndex("dayname");
    $csvex->export();
    exit(0);
}

if ('synonyms' == getGET('export')) {
    $q = $db->prepare("SELECT canonical, synonym
        FROM `{$db->getPrefix()}churchyear_synonyms`
        ORDER BY canonical");
    if (! $q->execute()) {
        echo array_pop($q->errorInfo());
        exit(0);
    }
    $q->setFetchMode(PDO::FETCH_NUM);
    $collector = array();
    while ($row = $q->fetch()) {
        if (! array_key_exists($row[0], $collector))
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
    exit(0);
}

if ('churchyear' == getGET('export')) {
    $q = $db->prepare("SELECT `dayname`, `season`, `base`,
        `offset`, `month`, `day`, `observed_month`, `observed_sunday`
        FROM `{$db->getPrefix()}churchyear` AS cy
        LEFT OUTER JOIN `{$db->getPrefix()}churchyear_order` AS cyo
            ON (cy.season = cyo.name)
            ORDER BY cyo.idx, cy.offset, cy.month, cy.day");
    if (! $q->execute()) {
        echo array_pop($q->errorInfo());
        exit(0);
    }
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $csvex = new CSVExporter($q);
    $csvex->setFilebase("churchyear");
    $csvex->setCharset("utf-8");
    $csvex->setFieldnames(array("Dayname", "Season", "Base", "Offset", "Month",
        "Day", "Observed Month", "Observed Sunday"));
    $csvex->setFieldselection(array("dayname", "season", "base", "offset",
        "month", "day", "observed_month", "observed_sunday"));
    $csvex->export();
    exit(0);
}

if ('churchyear-propers' == getGET('export')) {
    $q = $db->prepare("SELECT cp.dayname, color, theme, introit, gradual, note
        FROM `{$db->getPrefix()}churchyear_propers` AS cp
        JOIN `{$db->getPrefix()}churchyear` as cy
            ON (cp.dayname = cy.dayname)
        LEFT OUTER JOIN `{$db->getPrefix()}churchyear_order` AS cyo
            ON (cy.season = cyo.name)
            ORDER BY cyo.idx, cy.offset, cy.month, cy.day");
    $q->execute() or die(array_pop($q->errorInfo()));
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $csvex = new CSVExporter($q);
    $csvex->setFileBase("churchyear_general_propers");
    $csvex->setCharset("utf-8");
    $csvex->setFieldnames(array("Dayname", "Color", "Theme", "Introit",
        "Gradual", "Note"));
    $csvex->setFieldselection(array("dayname", "color", "theme", "introit",
        "gradual", "note"));
    $csvex->export();
    exit(0);
}

if ('collects' == getGET('export')) {
    $q = $db->prepare("SELECT id, class, collect
        FROM `{$db->getPrefix()}churchyear_collects`
        WHERE class = :class
        ORDER BY id");
    $q->bindParam(":class", getGET('class'));
    $q->execute() or die(array_pop($q->errorInfo()));
    $csvex = new CSVExporter($q);
    $csvex->setFileBase("churchyear_collects");
    $csvex->setCharset("utf-8");
    $csvex->setFieldnames(array("ID", "Class", "Collect"));
    $csvex->setFieldselection(array("id", "class", "collect"));
    $csvex->export();
    exit(0);
}

if ('collectassignments' == getGET('export')) {
    $q = $db->prepare("SELECT cci.id, lectionary, dayname
        FROM `{$db->getPrefix()}churchyear_collect_index` AS cci
        JOIN `{$db->getPrefix()}churchyear_collects` AS cc
        ON (cci.id=cc.id)
        WHERE cc.class = :class
        ORDER BY lectionary, cci.id");
    $q->bindParam(":class", getGET('class'));
    $q->execute() or die(array_pop($q->errorInfo()));
    $csvex = new CSVExporter($q);
    $csvex->setFileBase("churchyear_collects_assignments");
    $csvex->setCharset("utf-8");
    $csvex->setFieldnames(array("ID", "Lectionary", "Dayname"));
    $csvex->setFieldselection(array("id", "lectionary", "dayname"));
    $csvex->export();
    exit(0);
}

// Below here requires auth
requireAuth();

if (getGET('lectionary')) {
    $lectname = getGET('lectionary');
    $db->beginTransaction();
    $q = $db->prepare("SELECT COUNT(*) as c
        FROM `{$db->getPrefix()}churchyear_lessons`
        WHERE `lectionary` = :lect");
    $q->bindParam(":lect", $lectname);
    if (! ($q->execute() and 0 < intval($q->fetchColumn()))) {
        setMessage("No lectionary data for '".htmlentities($lectname)
            ."'.");
        header("location: admin.php");
        exit(0);
    }
    $q = $db->prepare("SELECT `cl`.`dayname`, `lesson1`, `lesson2`,
        `gospel`, `psalm`, `s2lesson`, `s2gospel`, `s3lesson`, `s3gospel`,
        `hymnabc`, `hymn`, `note` FROM `{$db->getPrefix()}churchyear_lessons` AS cl
        JOIN `{$db->getPrefix()}churchyear` as cy
            ON (cl.dayname = cy.dayname)
        LEFT OUTER JOIN `{$db->getPrefix()}churchyear_order` AS cyo
            ON (cy.season = cyo.name)
        WHERE `cl`.`lectionary` = :lect
        ORDER BY cyo.idx, cy.offset, cy.month, cy.day");
    $q->bindValue(":lect", $lectname);
    $q->setFetchMode(PDO::FETCH_NUM);
    if (! $q->execute()) {
        echo array_pop($q->errorInfo());
        exit(0);
    }
    $fieldnames = array("Dayname", "Lesson 1", "Lesson 2", "Gospel", "Psalm",
        "Series 2 Lesson", "Series 2 Gospel", "Series 3 Lesson",
        "Series 3 Gospel", "Week Hymn", "Year Hymn", "Note");
    $csvex = new CSVExporter($q, $lectname, "utf-8", $fieldnames);
    $csvex->export();
    $db->commit();
}


?>
