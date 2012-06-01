<?
$allsql = array();
$sql = 'CREATE TABLE `{{DBP}}churchyear` (
    `dayname` varchar(255),
    `season` varchar(64) default "",
    `base` varchar(255) default NULL,
    `offset` smallint default 0,
    `month` tinyint default 0,
    `day`   tinyint default 0,
    `observed_month` tinyint default 0,
    `observed_sunday` tinyint default 0,
    PRIMARY KEY (`dayname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8';
$q = $dbh->prepare(replaceDBP($sql)) ;
$q->execute() or die(array_pop($q->errorInfo()));
$allsql[] = replaceDBP($sql, "");
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
// Define helper table for ordering the presentation of days
$sql = "CREATE TABLE `{{DBP}}churchyear_order` (
    `name` varchar(32),
    `idx` smallint UNIQUE,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$q = $dbh->prepare(replaceDBP($sql));
$q->execute() or die(array_pop($q->errorInfo()));
$allsql[] = replaceDBP($sql, "");
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
if (! $q->execute()) {
    echo "Problem inserting seasons: " . array_pop($q->errorInfo());
    exit(0);
}
// Define table containing synonyms for the day names
$sql = "CREATE TABLE `{{DBP}}churchyear_synonyms` (
    `canonical` varchar(255),
    `synonym`   varchar(255),
    INDEX (`canonical`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$q = $dbh->prepare(replaceDBP($sql));
$q->execute() or die(array_pop($q->errorInfo()));
$allsql[] = replaceDBP($sql, "");
// Populate synonyms table
$fh = fopen("./utility/synonyms.csv", "r");
$q = $dbh->prepare("INSERT INTO {$dbp}churchyear_synonyms
    (canonical, synonym) VALUES (?, ?)");
while (($record = fgetcsv($fh)) != FALSE) {
    $q->execute(array($record[0], $record[1]))
        or die(array_pop($q->errorInfo())."{$record[0]}, {$record[1]}");
}
// Define table containing propers for the day names
$sql = "CREATE TABLE `{{DBP}}churchyear_propers` (
    `dayname`   varchar(255),
    `color`     varchar(32),
    `collect`   text,
    `collect2`  text,
    `collect3`  text,
    `oldtestament` varchar(56),
    `oldtestament2` varchar(56),
    `oldtestament3` varchar(56),
    `gospel`    varchar(56),
    `gospel2`   varchar(56),
    `gospel3`   varchar(56),
    `epistle`   varchar(56),
    `epistle2`  varchar(56),
    `epistle3`  varchar(56),
    `psalm`     varchar(56),
    `psalm2`     varchar(56),
    `psalm3`     varchar(56),
    `theme`     varchar(56),
    `note`      text,
    FOREIGN KEY (`dayname`) REFERENCES `{{DBP}}churchyear` (`dayname`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$q = $dbh->prepare(replaceDBP($sql));
$q->execute() or die(array_pop($q->errorInfo()));
$allsql[] = replaceDBP($sql, "");
// Fill churchyear_propers with data
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
// Write table descriptions to createtables.sql
$tabledesc = $cy_begin_marker."\n"
    .implode("\n", $allsql)."\n"
    .$cy_end_marker."\n";
if (! file_exists($create_tables_file)) {
    touch($create_tables_file);
}
$createtables = file_get_contents($create_tables_file);
if (false === strpos($createtables, $cy_begin_marker)) {
    $fh = fopen($create_tables_file, "a");
    fwrite($fh, $tabledesc);
    fclose($fh);
} else {
    $start = strpos($createtables, $cy_begin_marker);
    $len = strpos($createtables, $cy_end_marker) - $start;
    $newcontents = substr_replace($createtables, $tabledesc, $start, $len);
    $fh = fopen($create_tables_file, "w");
    fwrite($fh, $newcontents);
    fclose($fh);
}
?>
