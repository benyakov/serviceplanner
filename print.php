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
<h1>Print a Single Service</h1>
<p><a href="<?=$backlink?>">All Upcoming Services</a><p>
<?
    $q = $dbh->prepare("SELECT
        DATE_FORMAT(days.caldate, '%c/%e/%Y') as date,
        hymns.book, hymns.number, hymns.note, hymns.location,
        days.name as dayname, days.rite, days.servicenotes
        FROM ${dbp}hymns AS hymns
        RIGHT OUTER JOIN {$dbp}days AS days ON (hymns.service=days.pkey)
        WHERE days.pkey = '{$_GET['id']}'
        ORDER BY days.caldate DESC, hymns.location, hymns.sequence");
    $q->execute() or die(array_pop($q->errorInfo()));
    $row = $q->fetch(PDO::FETCH_ASSOC);
    ?>
    <dl>
        <dt>Date</dt> <dd><?=$row['date']?></dd>
        <dt>Day Name</dt> <dd><?=$row['dayname']?> </dd>
        <dt>Order/Rite</dt> <dd><?=$row['rite']?> </dd>
        <dt>Service Notes</dt> <dd> <?=trim($row['servicenotes'])?> </dd>
    </dl>
    <table><tbody>
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
            <td><?=$hymnbook == $row['book']?></td>
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

