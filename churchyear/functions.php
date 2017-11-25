<? /* Church year interface
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

function query_churchyear($json=false) {
    /* Return an executed query for all rows of the churchyear db
     */
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT cy.`dayname`, cy.`season`, cy.`base`,
        cy.`offset`, cy.`month`, cy.`day`,
        cy.`observed_month`, cy.`observed_sunday`,
        `{$dbp}next_in_year`(cy.`dayname`) AS next
        FROM `{$dbp}churchyear` AS cy
        LEFT OUTER JOIN `{$dbp}churchyear_order` AS cyo
            ON (cy.season = cyo.name)
            ORDER BY cyo.idx, cy.offset, cy.month, cy.day");
    if (! $q->execute()) {
        if ($json) {
            echo json_encode(array(false, array_pop($q->errorInfo())));
        } else {
            echo "Problem querying database:" . array_pop($q->errorInfo());
        }
        exit(0);
    } else {
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}

function churchyear_listing($rows) {
    /* Given an array of matched db rows,
     * list all items in a table with edit/delete links.
     */
    ob_start();
?>
<table id="churchyear-listing">
<tr><td></td><th>Name</th><th>Next</th><th>Season</th><th>Base Day</th>
    <th>Days Offset</th><th>Month</th>
    <th>Day</th><th>Observed Month</th><th>Observed Sunday</th></tr>
<? $even = "";
    foreach ($rows as $row) {
        if ($even == "class=\"even\"") {
            $even = "";
        } else {
            $even = "class=\"even\"";
        }
?>
    <tr id="row_<?=$row['dayname']?>" <?=$even?>>
    <td class="controls">
    <a class="edit" href="" data-day="<?=$row['dayname']?>">Edit</a><br>
    <a class="delete" href="" data-day="<?=$row['dayname']?>">Delete</a></td>
    <td class="dayname"><a href="" class="synonym"
            data-day="<?=$row['dayname']?>">=</a>
        <a href="" data-day="<?=$row['dayname']?>"
            class="propersname"><?=$row['dayname']?></a></td>
    <td class="next"><?=$row['next']?></td>
    <td class="season"><?=$row['season']?></td>
    <td class="base"><?=$row['base']?></td>
    <td class="offset"><?=$row['offset']?></td>
    <td class="month"><?=$row['month']?></td>
    <td class="day"><?=$row['day']?></td>
    <td class="observed-month"><?=$row['observed_month']?></td>
    <td class="observed-sunday"><?=$row['observed_sunday']?></td></tr>
<?  } ?>
</table>
<?
    return ob_get_clean();
}

function reconfigureNonfestival($type) {
    /* Given either "Historic", "ILCW", or "RCL", reconfigure the settings for
     * the affected days in the Church Year to skip Sundays at the appropriate
     * times.
     */
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $dbh->beginTransaction();
    $q = $dbh->prepare("UPDATE `{$dbp}churchyear` SET
        `base` = :base,
        `offset` = :offset,
        WHERE `dayname` = :dayname");
    $base = $offset = $dayname = "";
    $q->bindParam(":base", $base);
    $q->bindParam(":offset", $offset);
    $q->bindParam(":dayname", $dayname);
    if ("Historic" == $type) {
        $base = "Easter";
        for ($i = 1; $i <= 24; $i++) {
            $offset = 56 + $i * 7;
            $dayname = "Trinity {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $base = "Christmas 1";
        $offset = -49;
        $dayname = "Third Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -42;
        $dayname = "Second Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -35;
        $dayname = "Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $base = "Michaelmas 1";
        for ($i = 1; $i <= 7; $i++) {
            $offset = ($i-1) * 7;
            $dayname = "Michaelmas {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
    } elseif ("ILCW" == $type) {
        $base = "Easter";
        for ($i = 1; $i <= 24; $i++) {
            $offset = 56 + $i * 7;
            $dayname = "Trinity {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $offset = ++$i * 7;
        $dayname = "Third Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = ++$i * 7;
        $dayname = "Second Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -35;
        $base = "Christmas 1";
        $dayname = "Last";
        $q->execute() or die(array_pop($q->errorInfo()));
    } elseif ("RCL" == $type) {
        $base = "Christmas 1";
        $offset = -35;
        $dayname = "Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        for ($i = 26, $offset = -42; $i >= 1; $i--, $offset-=7) {
            $dayname = "Trinity {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
    }
    $dbh->commit();
}
?>
