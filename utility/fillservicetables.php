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
$fh = fopen("./utility/historictable.csv", "r");
$headings = fgetcsv($fh);
$q = $dbh->prepare("INSERT INTO {$dbp}churchyear
    (season, dayname, base, offset, month, day,
        observed_month, observed_sunday)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
while (($record = fgetcsv($fh, 250)) != FALSE) {
    $r = array();
    foreach ($record as $field) {
        $f = trim($field);
        $r[] = $f;
    }
    $q->execute($r) or dieWithRollback($q, "\n".__FILE__.":".__LINE__);
}

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
$q->execute() or die(array_pop($q->errorInfo());

// Populate synonyms table
$fh = fopen("./utility/synonyms.csv", "r");
$q = $dbh->prepare("INSERT INTO {$dbp}churchyear_synonyms
    (canonical, synonym) VALUES (?, ?)");
while (($record = fgetcsv($fh)) != FALSE) {
    $q->execute(array($record[0], $record[1]))
        or die(array_pop($q->errorInfo())."{$record[0]}, {$record[1]}");
}

$fh = fopen("./utility/propers.csv", "r");
$headings = fgetcsv($fh);
$q = $dbh->prepare("INSERT INTO {$dbp}churchyear_propers
    (dayname, color, collect, collect2, oldtestament,
    gospel, gospel2, gospel3, epistle, epistle2, epistle3,
    theme, psalm)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
while (($record = fgetcsv($fh, 250)) != FALSE) {
    $r = array();
    foreach ($record as $field) {
        $f = trim($field);
        if (! $f) {
            $f = PDO::PARAM_NULL;
        }
        $r[] = $f;
    }
    if (count($headings) != count($r)) {
        print_r($headings);
        print_r($r);
    }
    $dict = array_combine($headings, $r);
    $q->execute(array($dict['dayname'], $dict['Color'], $dict['Collect'],
        $dict['Deitrich Collect'], $dict['Old Testament'],
        $dict['Gospel'], $dict['Series 2 Gospel'], $dict['Series 3 Gospel'],
        $dict['Epistle'], $dict['Series 2 Lesson'], $dict['Series 3 Lesson'],
        $dict['Theme'], $dict['Psalm']))
        or dieWithRollback($q, "\n".__FILE__.":".__LINE__.$dict['dayname']);
}
