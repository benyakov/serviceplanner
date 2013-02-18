<? /* Display for printing a service
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
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
echo "<!DOCTYPE html>\n<html lang=\"en\">\n";
echo html_head("Print a Service");
$backlink = "index.php";
?>
<body>
<div id="content-container">
<?
    $q = queryService($dbh, $_GET['id']);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    ?>
    <h1>Service on <?=$row['date']?></h1>
    <h2><?=$row['dayname']?></h2>
    <p class="nonprinting"><a href="<?=$backlink?>">All Upcoming Services</a><p>
    <dl>
        <dt>Order/Rite</dt> <dd><?=$row['rite']?> </dd>
        <dt>Service Notes</dt> <dd> <?=translate_markup(trim($row['servicenotes']))?> </dd>
        <? if ($row['introit']) { ?>
        <dt>Introit</dt> <dd class="introittext maxcolumn"><?=$row['introit']?></dd>
        <? }
        if ($row['propersnote']) { ?>
        <dt>Propers Note</dt> <dd> <?=translate_markup(trim($row['propersnote']))?></dd>
        <?}
        if ($row['color']) { ?>
        <dt>Color</dt> <dd><?=$row['color']?></dd>
        <?}
        if ($row['theme']) { ?>
        <dt>Theme</dt> <dd><?=$row['theme']?></dd>
        <?}?>
    </dl>
    <? if ($row['block']) { ?>
    <div class="blockdisplay">
    <h4>Block: <?=$row['blabel']?></h4>
        <div class="blocknotes">
            <?=translate_markup($row['bnotes'])?>
        </div>
        <dl class="blocklessons">
            <dt>Lesson 1</dt><dd><?=$row['blesson1']?></dd>
            <dt>Lesson 2</dt><dd><?=$row['blesson2']?></dd>
            <dt>Gospel</dt><dd><?=$row['bgospel']?></dd>
            <dt>Psalm</dt><dd><?=$row['bpsalm']?></dd>
        </dl>
        <h5>Collect: (<?=$row['bcollectclass']?>)</h5>
        <div class="collecttext maxcolumn">
            <?=$row['bcollect']?>
        </div>
    </div>
    <? } ?>
    <table id="print-hymns-table"><tbody>
    <tr class="heading"><th>Book</th><th>#</th><th>Note</th>
        <th>Location</th><th>Title</th></tr>
    <?
    while ($row) {
        if ('' == $row['number']) {
            $row = $q->fetch(PDO::FETCH_ASSOC);
            continue;
        }
        ?>
        <tr>
            <td><?=$row['book']?></td>
            <td><?=$row['number']?></td>
            <td><?=$row['note']?></td>
            <td><?=$row['location']?></td>
            <td><?=$row['title']?></td>
        </tr>
        <?
        $row = $q->fetch(PDO::FETCH_ASSOC);
    }
    ?>
    </tbody></table>
    </div>
</body>
</html>

