<? /* Listing of sermons
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
requireAuth("index.php", 2);
?>
<!DOCTYPE html>
<html lang="en">
<? echo html_head("Sermon Plans"); ?>
<body>
<?
$q = $db->query("SELECT sermons.bibletext, sermons.outline,
    sermons.notes, sermons.service, sermons.mstype,
    DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    days.name, days.rite
    FROM `{$db->getPrefix()}sermons` AS sermons
    JOIN `{$db->getPrefix()}days` AS days
        ON (sermons.service=days.pkey)
    ORDER BY days.caldate DESC");
$q->execute() or die(array_pop($q->errorInfo));
?>
    <? pageHeader();
    siteTabs(); ?>
    <div id="content-container">
    <h1>Sermon Plans</h1>
    <p class="explanation">This is a listing of sermon plans you have created.
    To create a sermon plan, first create a service
    with zero or more hymns.  On the tab to modify services is a link
    to create or edit a sermon plan associated with it.</p>
    <table id="sermonplan-listing">
    <tr class="heading"><th>Date</th><th>Day</th><th>Text</th><th>Rite</th></tr>
    <tr class="heading"><th colspan="3">Outline</th><th>Notes</th></tr>
    <?
while ($row = $q->fetch(PDO::FETCH_ASSOC))
{
?>
    <tr class="table-topline smaller">
        <td><?=$row['date']?></td><td><?=$row['name']?></td>
        <td><?=$row['bibletext']?>
    <? if ($row['mstype']) { ?>
        <a href="sermon.php?manuscript=1&id=<?=$row['service']?>">mss</a>
    <? } ?>
        </td><td><?=$row['rite']?></td></tr>
    <tr><td colspan="3" class="table-preformat">
            <pre><?=$row['outline']?></pre><br />
            <a class="menulink" href="sermon.php?id=<?=$row['service']?>">Edit</a>
        </td>
        <td class="table-leftborder table-preformat">
            <?=translate_markup($row['notes'])?>
        </td>
    </tr>
<?
}
?>
    </table>
    </div>
</body>
</html>
