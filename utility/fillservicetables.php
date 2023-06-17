<?php /* Populate service tables with default values.
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

/****
 * These are broken up into functions so that they can be run separately to
 * spread the db response time across multiple requests.
 *
 * require() this file with $selective set to true, then call the function.
 */

if (! isset($selective) && $selective) {
    fill_historic($db);
    fill_order($db);
    fill_synonyms($db);
    fill_propers($db);
    fill_lessons($db);
    fill_collect_texts($db);
    fill_collect_indexes($db);
    fill_graduals($db);
}

function fill_historic($db) {
    $fh = fopen("./utility/churchyear/historictable.csv", "r");
    $headings = fgetcsv($fh);
    $q = $db->prepare("INSERT INTO {$db->getPrefix()}churchyear
        (season, dayname, base, offset, month, day,
            observed_month, observed_sunday)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $daynames = array();
    while (($record = fgetcsv($fh)) != FALSE) {
        $r = array();
        foreach ($record as $field) {
            $f = trim($field);
            $r[] = $f;
        }
        $daynames[] = $r[1];
        $q->execute($r) or die(array_pop($q->errorInfo()));
    }
    fclose($fh);
}

function fill_order($db) {
    // Make sure it's empty.
    $db->exec("DELETE FROM `{$db->getPrefix()}churchyear_order`");
    $q = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear_order`
        (name, idx) VALUES
        (\"Advent\", 1),
        (\"Christmas\", 2),
        (\"Epiphany\", 3),
        (\"Pre-lent\", 4),
        (\"Lent\", 5),
        (\"Easter\", 6),
        (\"Pentecost\", 7),
        (\"Trinity\", 8),
        (\"Michaelmas\", 9),
        (\"\", 32)");
    $q->execute() or die("Problem populating churchyear_order:" .
        array_pop($q->errorInfo()));
}

function fill_synonyms($db) {
    $fh = fopen("./utility/churchyear/synonyms.csv", "r");
    $qs = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear_synonyms`
        (canonical, synonym) VALUES (:canonical, :synonym)");
    while (($record = fgetcsv($fh)) != FALSE) {
        $canonical = $record[0];
        foreach (array_slice($record, 1) as $synonym) {
            $qs->bindValue(":canonical", $canonical);
            $qs->bindValue(":synonym", $synonym);
            $qs->execute()
                or die(var_export($qs->errorInfo())." with {$canonical}, {$synonym}");
        }
    }
    fclose($fh);
}

function fill_propers($db) {
    $fh = fopen("./utility/churchyear/propers.csv", "r");
    $headings = fgetcsv($fh);
    $qp = $db->prepare("INSERT INTO {$db->getPrefix()}churchyear_propers
        (dayname, color, theme, introit, gradual)
        VALUES (?, ?, ?, ?, ?)");
    $hcount = count($headings);
    while (($record = fgetcsv($fh)) != FALSE) {
        $rcount = count($record);
        if ($rcount < $hcount)
            $record = array_merge($record, array_fill(0, $hcount-$rcount, NULL));
        $dict = array_combine($headings, $record);
        $qp->bindValue(1, $dict['dayname'], paramStrNull($dict['dayname']));
        $qp->bindValue(2, $dict['color'], paramStrNull($dict['color']));
        $qp->bindValue(3, $dict['theme'], paramStrNull($dict['theme']));
        $qp->bindValue(4, $dict['introit'], paramStrNull($dict['introit']));
        $qp->bindValue(5, $dict['gradual'], paramStrNull($dict['gradual']));
        $qp->execute() or die(__FILE__.":".__LINE__.$dict['dayname'].
                array_pop($qp->errorInfo()));
    }
    fclose($fh);
}

function fill_lessons($db) {
    $fh = fopen("./utility/churchyear/lessons.csv", "r");
    $headings = fgetcsv($fh);
    $ql = $db->prepare("INSERT INTO {$db->getPrefix()}churchyear_lessons
        (dayname, lectionary, lesson1, lesson2, gospel, psalm,
        s2lesson, s2gospel, s3lesson, s3gospel, hymnabc, hymn, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    while (($record = fgetcsv($fh)) != FALSE) {
        $dict = array_combine($headings, $record);
        $ql->bindValue(1, $dict['dayname'], paramStrNull($dict['dayname']));
        $ql->bindValue(2, $dict['type'], paramStrNull($dict['type']));
        $ql->bindValue(3, $dict['lesson1'], paramStrNull($dict['lesson1']));
        $ql->bindValue(4, $dict['lesson2'], paramStrNull($dict['lesson2']));
        $ql->bindValue(5, $dict['gospel'], paramStrNull($dict['gospel']));
        $ql->bindValue(6, $dict['psalm'], paramStrNull($dict['psalm']));
        $ql->bindValue(7, $dict['s2lesson'], paramStrNull($dict['s2lesson']));
        $ql->bindValue(8, $dict['s2gospel'], paramStrNull($dict['s2gospel']));
        $ql->bindValue(9, $dict['s3lesson'], paramStrNull($dict['s3lesson']));
        $ql->bindValue(10, $dict['s3gospel'], paramStrNull($dict['s3gospel']));
        $ql->bindValue(11, $dict['hymnabc'], paramStrNull($dict['hymnabc']));
        $ql->bindValue(12, $dict['hymn'], paramStrNull($dict['hymn']));
        $ql->bindValue(13, $dict['note'], paramStrNull($dict['hymn']));
        $ql->execute() or die(__FILE__.":".__LINE__.$dict['dayname']);
    }
    fclose($fh);
}

function fill_collect_texts($db) {
    $fh = fopen("./utility/churchyear/collecttext.csv", "r");
    $headings = fgetcsv($fh);
    $ql = $db->prepare("INSERT INTO {$db->getPrefix()}churchyear_collects
        (id, class, collect)
        VALUES (?, ?, ?)");
    while (($record = fgetcsv($fh)) != FALSE) {
        $dict = array_combine($headings, $record);
        $ql->bindValue(1, $dict['id'],
            getParamType($dict['id'], PDO::PARAM_INT));
        $ql->bindvalue(2, $dict['type'], paramStrNull($dict['type']));
        $ql->bindvalue(3, $dict['collect'], paramStrNull($dict['collect']));
        $ql->execute() or die(__FILE__.":".__LINE__.$dict['dayname']);
    }
    fclose($fh);
}

function fill_collect_indexes($db) {
    $fh = fopen("./utility/churchyear/collectindex.csv", "r");
    $headings = fgetcsv($fh);
    $ql = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear_collect_index`
        (dayname, lectionary, id)
        VALUES (?, ?, ?)");
    while (($record = fgetcsv($fh)) != FALSE) {
        $dict = array_combine($headings, $record);
        $ql->bindValue(1, $dict['dayname'], paramStrNull($dict['dayname']));
        $ql->bindValue(2, $dict['type'], paramStrNull($dict['number']));
        $ql->bindValue(3, $dict['number'],
            getParamType($dict['number'], PDO::PARAM_INT));
        $ql->execute() or die(__FILE__.":".__LINE__.$dict['dayname']);
    }
    fclose($fh);
}

function fill_graduals($db) {
    $fh = fopen("./utility/churchyear/graduals.csv", "r");
    $headings = fgetcsv($fh);
    $ql = $db->prepare("INSERT INTO `{$db->getPrefix()}churchyear_graduals`
        (season, gradual)
        VALUES (?, ?)");
    while (($record = fgetcsv($fh)) != FALSE) {
        $dict = array_combine($headings, $record);
        $ql->bindValue(1, $dict['season'], paramStrNull($dict['season']));
        $ql->bindValue(2, $dict['gradual'], paramStrNull($dict['gradual']));
        $ql->execute() or die(__FILE__.":".__LINE__.$dict['season']);
    }
    fclose($fh);
}

function getParamType($value, $nonnulltype) {
    if ($value)
        return $nonnulltype;
    else
        return PDO::PARAM_NULL;
}

function paramStrNull($value) {
    return getParamType($value, PDO::PARAM_STR);
}
