<?php /* Creation/display of hymn cross-reference index
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

if (getGET('drop') == 'yes' && 3 == authLevel()) {
    $db->query("DROP TABLE `{$db->getPrefix()}xref`");
    setMessage("Cross-reference table repopulated.");
}

if (! $db->query("SELECT 1 FROM `{$db->getPrefix()}xref`")) {
    /**** To create the cross reference table ****/

    $q = $db->prepare("CREATE TABLE `{$db->getPrefix()}xref` (
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

    $db->beginTransaction();
    $fh = fopen("./utility/hymnindex.csv", "r");
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
        $q = $db->prepare("INSERT INTO `{$db->getPrefix()}xref` (title, text, lsb, tlh, lw, lbw, elh, cw, wov, hs98)
            VALUES ({$r[0]}, {$r[1]}, {$r[2]}, {$r[3]}, {$r[4]}, {$r[5]}, {$r[6]}, {$r[7]}, {$r[8]}, {$r[9]})");
        $q->execute() or die("\n".__FILE__.":".__LINE__." ".array_pop($q->errorInfo())." Record data: ".implode(" ", $r));
    }
    $db->commit();
}
/* To Display the cross-reference table */


if (isset($_GET['sort']) && strpos($_GET['sort'], ';') == False) {
    $sanitized_sort_field = "`{$_GET['sort']}`";
    $sort_by = " ORDER BY ({$sanitized_sort_field} = \"\") DESC, ".
        " {$sanitized_sort_field}";
    $sorted_on = $_GET['sort'];
} else {
    $sort_by = "";
    $sorted_on = "";
}
$q = $db->prepare("SELECT * FROM `{$db->getPrefix()}xref{$sort_by}`") ;
if (!$q->execute()) die(array_pop($q->errorInfo()));
?><!DOCTYPE html>
<html lang="en">
<?=html_head("Hymn Cross Reference")?>
<body>
    <?  pageHeader();
    siteTabs(); ?>
<div id="content-container">
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
$rownum = 0;
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $r = array();
    foreach ($row as $k => $v) {
        if (is_null($v)) { $v = ''; }
        $r[$k] = $v;
    }
    if ($rownum++ % 2 == 0) $sortrowbg = ' class="even"';
    else $sortrowbg = ' class="odd"';
    echo "<tr${sortrowbg}>
        <td class=\"title\">{$r['title']}</td>
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
