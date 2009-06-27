<?php

require('db-connection.php');

function linksort($text, $sort) {
    // Return a link requesting the given sort
    return "<a class=\"sortlink\" href=\"${_SERVER['PHP_SELF']}?sort=${sort}\">${text}</a>";
}

function togglebg($current) {
    // Toggle between alternating background by returning the other class
    $light = " class=\"odd\"";
    $dark = " class=\"even\"";
    if ($current == $light) {
        return $dark;
    } else {
        return $light;
    }
}

$sql = "SELECT 1 FROM ${dbp}xref";
if (! mysql_query($sql)) {
    /**** To create the cross reference table ****/

    $sql = "CREATE TABLE `${dbp}xref` (
        `title` varchar(80),
        `text` varchar(60),
        `elh` smallint,
        `tlh` smallint,
        `lw` smallint,
        `lbw` smallint,
        `cw` smallint,
        `hs98` smallint,
        `pkey` int(10) unsigned NOT NULL auto_increment,
        KEY `pkey` (`pkey`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8" ;
    mysql_query($sql) or die(mysql_error());

    $fh = fopen("hymnindex.csv", "r");
    $headings = fgetcsv($fh);
    while (($record = fgetcsv($fh, 250)) != FALSE) {
        $r = array();
        $record[0] = mysql_real_escape_string($record[0]);
        $record[1] = mysql_real_escape_string($record[1]);
        foreach ($record as $field) {
            $f = trim($field);
            if (! $f) {
                $f = "NULL";
            } else {
                $f = "'$f'";
            }
            $r[] = $f;
        }
        $sql = "INSERT INTO ${dbp}xref (title, text, elh, tlh, lw, lbw, cw, hs98)
            VALUES (${r[0]}, ${r[1]}, ${r[2]}, ${r[3]}, ${r[4]}, ${r[5]}, ${r[6]}, ${r[7]})";
        mysql_query($sql) or die(mysql_error()."\n".__FILE__.":".__LINE__);
    }
}
/* To Display the cross-reference table */

require("functions.php");

if (array_key_exists('sort', $_GET)) {
    $sort_by = " ORDER BY ${_GET['sort']}";
    $sorted_on = $_GET['sort'];
} else {
    $sort_by = "";
    $sorted_on = "";
}
$sql = "SELECT * FROM ${dbp}xref${sort_by}" ;
$result = mysql_query($sql) or die(mysql_error());
require("options.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
?>
<html>
<?=html_head("Service Planning Records")?>
<body>
<?= sitetabs($sitetabs, $script_basename); ?>
<div id="content_container">
<?  if ($sorted_on) { ?>
<div id="goto_now"><a href="#sortstart">Jump to Beginning of Sorted</a></div>
<?  } ?>
<h1>Cross Reference Table</h1>
<table id="xref_listing" cols="8">
<thead>
<tr>
<td><?=linksort("Title", "title")?></td>
<td><?=linksort("Text", "text")?></td>
<td><?=linksort("ELH", "elh")?></td>
<td><?=linksort("TLH", "tlh")?></td>
<td><?=linksort("LW", "lw")?></td>
<td><?=linksort("LBW", "lbw")?></td>
<td><?=linksort("CW", "cw")?></td>
<td><?=linksort("HS '98", "hs98")?></td>
</tr>
</thead>
<tbody>
<?
$marked_sortstart = FALSE;
$sortmarker = "";
$cursortvalue = "";
$sortrowbg = "";
while ($row = mysql_fetch_assoc($result)) {
    $r = array();
    foreach ($row as $k => $v) {
        if (is_null($v)) { $v = ''; }
        $r[$k] = $v;
    }
    if (array_key_exists($sorted_on, $r)) {
        if ($cursortvalue != $r[$sorted_on]) {
            $sortrowbg = togglebg($sortrowbg);
            $cursortvalue = $r[$sorted_on];
        }
        if ((! $marked_sortstart) && $r[$sorted_on]) {
            $sortmarker = "<a name=\"sortstart\" />";
            $marked_sortstart = TRUE;
        } else {
            $sortmarker = "";
        }
    }
    echo "<tr${sortrowbg}>
        <td class=\"title\">${sortmarker}${r['title']}</td>
        <td class=\"text\">${r['text']}</td>
        <td class=\"elh\">${r['elh']}</td>
        <td class=\"tlh\">${r['tlh']}</td>
        <td class=\"lw\">${r['lw']}</td>
        <td class=\"lbw\">${r['lbw']}</td>
        <td class=\"cw\">${r['cw']}</td>
        <td class=\"hs98\">${r['hs98']}</td>
        </tr>";
}
?>
</tbody>
</table>
</div>
</body>
</html>
