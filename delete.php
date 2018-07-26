<? /* Interface for deleting services
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
requireAuth("index.php", 3, "Access denied. Please log in as Admin.");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$ajax = getGET('ajaxconfirm');
if ((! isset($_GET['stage'])) || $ajax) {
    // Put items to delete into an array.
    $todelete = array();
    foreach ($_POST as $posted=>$value) {
        if (preg_match('/(\d+)_(.*)/', $posted, $matches)) {
            $todelete[str_replace('_', ' ', $matches[2])][] = $matches[1];
        }
    }
    $_SESSION[$sprefix]['stage1'] = $todelete;
    if (! $ajax) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("Delete Confirmation")?>
    <body>
        <div id="content-container">
        <p><a href="modify.php">Abort</a><p>
    <? } ?>
        <h1>Confirm Deletions</h1>
        <?
        $db->beginTransaction();
        foreach ($todelete as $loc => $deletions) {
            if (0 == strlen($loc)) {
                $whereclause = "";
            } else {
                $whereclause = "AND hymns.occurrence = :occurrence";
            }
            //$deletions = implode(", ", array_map($db->quote, $deletions));
            $delphitems = array_map(
                function($n){return ":d".$n;},
                range(0, count($deletions)-1));
            $delplaceholders = implode(",", $delphitems);
            $q = $db->prepare("
                SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
                hymns.book, hymns.number, hymns.note,
                hymns.occurrence, days.name as dayname, days.rite,
                days.pkey as id, days.servicenotes, names.title
                FROM {$db->getPrefix()}hymns AS hymns
                RIGHT OUTER JOIN {$db->getPrefix()}days AS days
                    ON (hymns.service = days.pkey)
                LEFT OUTER JOIN {$db->getPrefix()}names AS names
                    ON (hymns.number = names.number)
                        AND (hymns.book = names.book)
                WHERE days.pkey IN({$delplaceholders})
                {$whereclause}
                ORDER BY days.caldate DESC, hymns.service DESC,
                    hymns.occurrence, hymns.sequence");
            foreach ($deletions as $k => $v) {
                $q->bindValue(":d{$k}", $v);
            }
            if ($whereclause) {
                $q->bindValue(":occurrence", $loc);
            }
            $q->execute();
            display_records_table($q);
        }
        $db->commit();
        ?>
        <form action="<?=$protocol?>://<?=$this_script."?stage=2"?>" method="POST">
        <button type="submit">Confirm</button>
        </form>
    <? if (! $ajax) { ?>
        </div>
    </body>
    </html>
    <?  }
} elseif ("2" == getGET('stage')) {
    //// Delete and acknowledge deletion.
    $db->beginTransaction();
    foreach ($_SESSION[$sprefix]['stage1'] as $loc => $deletions) {
        // Set up placeholders and values
        $delphitems = array_map(
            function($n){return ":d".$n;},
            range(0, count($deletions)-1));
        $delplaceholders = implode(",", $delphitems);
        // Delete flags for this service/occurrence
        $q = $db->prepare("DELETE FROM `{$db->getPrefix()}service_flags`
            WHERE service IN ({$delplaceholders}) AND occurrence = :occurence");
        foreach ($deletions as $k => $v) {
            $q->bindValue(":d{$k}", $v);
        }
        $q->bindValue(":occurrence", $loc);
        $q->execute();
        // Check to see if service has hymns at another occurrence
        $q = $db->prepare("SELECT number
                FROM {$db->getPrefix()}hymns as hymns
                JOIN {$db->getPrefix()}days as days
                ON (hymns.service = days.pkey)
                WHERE hymns.occurrence != :occurrence
                    AND days.pkey IN ({$delplaceholders})
                LIMIT 1");
        foreach ($deletions as $k => $v) {
            $q->bindValue(":d{$k}", $v);
        }
        $q->bindValue(":occurrence", $loc);
        $q->execute();
        if ($q->fetch()) {
            // If so, delete only the hymns.
            $q = $db->prepare("DELETE FROM `{$db->getPrefix()}hymns`
                USING `{$db->getPrefix()}hymns` as hymns
                JOIN `{$db->getPrefix()}days` as days
                ON (hymns.service = days.pkey)
                WHERE hymns.occurrence = :occurrence
                    AND days.pkey IN ({$delplaceholders})");
            foreach ($deletions as $k => $v) {
                $q->bindValue(":d{$k}", $v);
            }
            $q->bindValue(":occurrence", $loc);
            $q->execute();
        } else {
            // If not, delete the service (should cascade to hymns)
            $q = $db->prepare("DELETE FROM `{$db->getPrefix()}days`
                WHERE `pkey` IN({$delplaceholders})");
            foreach ($deletions as $k => $v) {
                $q->bindValue(":d{$k}", $v);
            }
            $q->execute();
        }
    }
    $db->commit();
    setMessage("Deletion(s) complete.");
    header("Location: modify.php");
}

