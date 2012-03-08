<?php

require('./init.php');

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

if (! $dbh->query("SELECT 1 FROM {$dbp}xref")) {
    /**** To create the cross reference table ****/

    $q = $dbh->prepare("CREATE TABLE `{$dbp}xref` (
        `title` varchar(80),
        `text` varchar(60),
        `elh` smallint,
        `tlh` smallint,
        `lsb` smallint,
        `cw` smallint,
        `lw` smallint,
        `lbw` smallint,
        `hs98` smallint,
        `wov` smallint,
        `pkey` int(10) unsigned NOT NULL auto_increment,
        KEY `pkey` (`pkey`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8") ;
    $q->execute() or die(array_pop($q->errorInfo()));

    $dbh->beginTransaction();
    $fh = fopen("hymnindex.csv", "r");
    $headings = fgetcsv($fh);
    while (($record = fgetcsv($fh, 250)) != FALSE) {
        $r = array();
        $record = quote_array($record);
        foreach ($record as $field) {
            $f = trim($field);
            if (! $f) {
                $f = "NULL";
            } else {
                $f = "'$f'";
            }
            $r[] = $f;
        }
        $q = $dbh->prepare("INSERT INTO {$dbp}xref (title, text, lsb, tlh, lw, lbw, elh, cw, wov, hs98)
            VALUES ({$r[0]}, {$r[1]}, {$r[2]}, {$r[3]}, {$r[4]}, {$r[5]}, {$r[6]}, {$r[7]}, {$r[8]}, {$r[9]})");
        $q->execute() or dieWithRollback($q, "\n".__FILE__.":".__LINE__);
    }
    $dbh->commit();
}
/* To Display the cross-reference table */

if (array_key_exists('sort', $_GET)) {
    $sort_by = " ORDER BY {$_GET['sort']}";
    $sorted_on = $_GET['sort'];
} else {
    $sort_by = "";
    $sorted_on = "";
}
$q = $dbh->query("SELECT * FROM {$dbp}xref{$sort_by}") ;
?>
<html lang="en">
<?=html_head("Hymn Cross Reference")?>
<body>
<script type="text/javascript">
    auth = "<?=authId()?>";
    $(document).ready(function() {
        setupLogin();
    });
</script>
    <header>
    <div id="login"><?=loginForm()?></div>
    <?showMessage();?>
    </header>
<?= sitetabs($sitetabs, $script_basename); ?>
<div id="content-container">
<?  if ($sorted_on) { ?>
<div id="goto-now"><a href="#sortstart">Jump to Beginning of Sorted</a></div>
<?  } ?>
<h1>Cross Reference Table</h1>
<table id="xref-listing" cols="8">
<thead>
<tr>
<td><?=linksort("Title", "title")?></td>
<td><?=linksort("Text", "text")?></td>
<td><?=linksort("ELH", "elh")?></td>
<td><?=linksort("TLH", "tlh")?></td>
<td><?=linksort("LSB", "lsb")?></td>
<td><?=linksort("CW", "cw")?></td>
<td><?=linksort("LW", "lw")?></td>
<td><?=linksort("LBW", "lbw")?></td>
<td><?=linksort("HS '98", "hs98")?></td>
<td><?=linksort("WOV", "wov")?></td>
</tr>
</thead>
<tbody>
<?
$marked_sortstart = FALSE;
$sortmarker = "";
$cursortvalue = "";
$sortrowbg = "";
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
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
        <td class=\"title\">{$sortmarker}${r['title']}</td>
        <td class=\"text\">{$r['text']}</td>
        <td class=\"elh\">{$r['elh']}</td>
        <td class=\"tlh\">{$r['tlh']}</td>
        <td class=\"lsb\">{$r['lsb']}</td>
        <td class=\"cw\">{$r['cw']}</td>
        <td class=\"lw\">{$r['lw']}</td>
        <td class=\"lbw\">{$r['lbw']}</td>
        <td class=\"hs98\">{$r['hs98']}</td>
        <td class=\"wov\">{$r['wov']}</td>
        </tr>";
}
?>
</tbody>
</table>
</div>
</body>
</html>
