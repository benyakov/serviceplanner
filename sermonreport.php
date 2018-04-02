<? /* Printable sermon report
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
requireAuth();
?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit a Sermon Plan")?>
    <body>
        <div id="content-container">
        <span class="nonprinting">
        <p><a href="sermon.php?id=<?=getGET('id')?>">Edit This Plan</a>
        | <a href="sermons.php">Browse All Sermon Plans</a></p>
        </span>
        <h1>Sermon Plan</h1>
    <?
        $q = $db->prepare("SELECT s.bibletext, s.outline,
            s.notes, DATE_FORMAT(d.caldate, '%e %b %Y') as date,
            d.name AS day, d.rite
            FROM `{$db->getPrefix()}sermons` AS s
            JOIN `{$db->getPrefix()}days` AS d ON (s.service=d.pkey)
            WHERE service=:id");
        $q->execute(array("id"=>getGET('id')))
            or die(array_pop($q->errorInfo()));
        $row = $q->fetch(PDO::FETCH_ASSOC);
    ?>
        <dl>
            <dt>Date</dt>
            <dd><?=$row['date']?></dd>
            <dt>Day</dt>
            <dd><?=$row['day']?></dd>
            <dt>Rite</dt>
            <dd><?=$row['rite']?></dd>
            <dt>Text</dt>
            <dd><?=$row['bibletext']?></dd>
            <dt>Outline<dt>
            <dd><pre><?=$row['outline']?></pre></dd>
            <dt>Notes<dt>
            <dd><?=translate_markup($row['notes'])?></dd>
        </dl>
        </div>
    </body>
</html>
