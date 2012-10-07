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
$q = $dbh->prepare("SELECT DATE_FORMAT(d.caldate, '%c/%e/%Y') as date,
    h.book, h.number, h.note, h.location, d.name as dayname, d.rite,
    d.pkey as id, d.servicenotes, n.title, d.block,
    b.label as blabel, b.notes as bnotes,
    (CASE b.l1lect
        WHEN 'historic' THEN
        (CASE b.l1series
            WHEN 'first' THEN
                (SELECT lesson1 FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.l1lect)
            WHEN 'second' THEN
                (SELECT s2lesson FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.l1lect)
            WHEN 'third' THEN
                (SELECT s3lesson FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.l1lect)
            END)
        WHEN 'custom' THEN b.l1series
        ELSE
        (SELECT lesson1 FROM `{$dbp}churchyear_lessons` AS cl
            WHERE cl.dayname=d.name AND cl.lectionary=b.l1lect)
        END)
        AS blesson1,
    (CASE b.l2lect
        WHEN 'historic' THEN
        (CASE b.l2series
            WHEN 'first' THEN
                (SELECT lesson2 FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.l2lect)
            WHEN 'second' THEN
                (SELECT s2lesson FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.l2lect)
            WHEN 'third' THEN
                (SELECT s3lesson FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.l2lect)
            END)
        WHEN 'custom' THEN b.l2series
        ELSE
        (SELECT lesson2 FROM `{$dbp}churchyear_lessons` AS cl
            WHERE cl.dayname=d.name AND cl.lectionary=b.l2lect)
        END)
        AS blesson2,
    (CASE b.golect
        WHEN 'historic' THEN
        (CASE b.goseries
            WHEN 'first' THEN
                (SELECT gospel FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.golect)
            WHEN 'second' THEN
                (SELECT s2gospel FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.golect)
            WHEN 'third' THEN
                (SELECT s3gospel FROM `{$dbp}churchyear_lessons` AS cl
                    WHERE cl.dayname=d.name AND cl.lectionary=b.golect)
            END)
        WHEN 'custom' THEN b.goseries
        ELSE
        (SELECT gospel FROM `{$dbp}churchyear_lessons` AS cl
            WHERE cl.dayname=d.name AND cl.lectionary=b.golect)
        END)
        AS bgospel,
    (SELECT psalm FROM `{$dbp}churchyear_lessons` AS cl
        WHERE cl.dayname=d.name AND cl.lectionary=b.pslect) AS bpsalm,
        c.collect AS bcollect,
        b.coclass AS bcollectclass
    FROM {$dbp}hymns AS h
    RIGHT OUTER JOIN `{$dbp}days` AS d ON (h.service = d.pkey)
    LEFT OUTER JOIN `{$dbp}names` AS n ON (h.number = n.number)
        AND (h.book = n.book)
    LEFT OUTER JOIN `{$dbp}blocks` AS b ON (b.id = d.block)
    LEFT JOIN `{$dbp}churchyear_propers` AS cyp ON (cyp.dayname = d.name)
    LEFT JOIN `{$dbp}churchyear_collect_index` AS ci
        ON (ci.dayname = d.name AND ci.lectionary = b.colect)
    LEFT JOIN `{$dbp}churchyear_collects` AS c
        ON (c.id = ci.id AND c.class = b.coclass)
        WHERE d.pkey = ?
        ORDER BY d.caldate DESC, h.location, h.sequence");
    $q->execute(array($_GET['id'])) or die(array_pop($q->errorInfo()));
    $row = $q->fetch(PDO::FETCH_ASSOC);
    ?>
    <h1>Service on <?=$row['date']?></h1>
    <h2><?=$row['dayname']?></h2>
    <p class="nonprinting"><a href="<?=$backlink?>">All Upcoming Services</a><p>
    <dl>
        <dt>Order/Rite</dt> <dd><?=$row['rite']?> </dd>
        <dt>Service Notes</dt> <dd> <?=translate_markup(trim($row['servicenotes']))?> </dd>
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
        <h5>Collect: <?=$row['bcollectclass']?></h5>
        <p><?=$row['bcollect']?></p>
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

