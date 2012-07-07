<?/* Populate service tables with default values.
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
$fh = fopen("./utility/churchyear/historictable.csv", "r");
$headings = fgetcsv($fh);
$q = $dbh->prepare("INSERT INTO {$dbp}churchyear
    (season, dayname, base, offset, month, day,
        observed_month, observed_sunday)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$daynames = array();
$propernames = array();
while (($record = fgetcsv($fh)) != FALSE) {
    $r = array();
    foreach ($record as $field) {
        $f = trim($field);
        $r[] = $f;
    }
    $daynames[] = $r[1];
    $q->execute($r) or die(array_pop($q->errorInfo()));
}

// Make sure it's empty.
$dbh->exec("DELETE FROM `{$dbp}churchyear_order`");
$q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_order`
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

// Populate synonyms table
$fh = fopen("./utility/churchyear/synonyms.csv", "r");
$qs = $dbh->prepare("INSERT INTO `{$dbp}churchyear_synonyms`
    (canonical, synonym) VALUES (:canonical, :synonym)");
while (($record = fgetcsv($fh)) != FALSE) {
    $canonical = $record[0];
    foreach (array_slice($record, 1) as $synonym) {
        $qs->bindValue(":canonical", $canonical);
        $qs->bindValue(":synonym", $synonym);
        $qs->execute()
            or die(var_export($q->errorInfo())." with {$canonical}, {$synonym}");
    }
}

// Fill propers table
$fh = fopen("./utility/churchyear/propers.csv", "r");
$headings = fgetcsv($fh);
$qp = $dbh->prepare("INSERT INTO {$dbp}churchyear_propers
    (dayname, color, theme, introit)
    VALUES (?, ?, ?, ?)");
while (($record = fgetcsv($fh)) != FALSE) {
    $r = blanksToNull($record);
    $dict = array_combine($headings, $r);
    $propernames[] = $dict['dayname'];
    $qp->execute(array($dict['dayname'], $dict['color'],
        $dict['theme'], $dict['introit']))
        or die(__FILE__.":".__LINE__.$dict['dayname'].
            array_pop($qp->errorInfo()));
}

// Fill lessons table
$fh = fopen("./utility/churchyear/lessons.csv", "r");
$headings = fgetcsv($fh);
$ql = $dbh->prepare("INSERT INTO {$dbp}churchyear_lessons
    (dayname, lectionary, lesson1, lesson2, gospel, psalm,
    s2lesson, s2gospel, s3lesson, s3gospel, hymnabc, hymn)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
while (($record = fgetcsv($fh)) != FALSE) {
    $r = blanksToNull($record);
    $dict = array_combine($headings, $r);
    $ql->execute(array($dict['dayname'], $dict['type'], $dict['lesson1'],
        $dict['lesson2'], $dict['gospel'], $dict['psalm'],
        $dict['s2lesson'], $dict['s2gospel'], $dict['s3lesson'],
        $dict['s3gospel'], $dict['hymnabc'], $dict['hymn']))
        or die(__FILE__.":".__LINE__.$dict['dayname']);
}

// Fill collect text
$fh = fopen("./utility/churchyear/collecttext.csv", "r");
$headings = fgetcsv($fh);
$ql = $dbh->prepare("INSERT INTO {$dbp}churchyear_collects
    (id, class, collect)
    VALUES (?, ?, ?)");
while (($record = fgetcsv($fh)) != FALSE) {
    $r = blanksToNull($record);
    $dict = array_combine($headings, $r);
    $ql->execute(array($dict['id'], $dict['type'], $dict['collect']))
        or die(__FILE__.":".__LINE__.$dict['dayname']);
}

// Fill collect indexes
$fh = fopen("./utility/churchyear/collectindex.csv", "r");
$headings = fgetcsv($fh);
$ql = $dbh->prepare("INSERT INTO `{$dbp}churchyear_collect_index`
    (dayname, lectionary, id)
    VALUES (?, ?, ?)");
while (($record = fgetcsv($fh)) != FALSE) {
    $r = blanksToNull($record);
    $dict = array_combine($headings, $r);
    $ql->execute(array($dict['dayname'], $dict['type'], $dict['number']))
        or die(__FILE__.":".__LINE__.$dict['dayname']);
}

function blanksToNull($arrayin) {
    $r = array();
    foreach ($arrayin as $field) {
        $f = trim($field);
        if (! $f) {
            $f = PDO::PARAM_NULL;
        }
        $r[] = $f;
    }
    return $r;
}
