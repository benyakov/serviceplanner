<?php /* Service flags management
    Copyright (C) 2016 Jesse Jacobsen

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
if (! array_key_exists('stage', $_GET)) {
    if (! (is_numeric($_GET['id']) and $_GET['location']) ) {
        setMessage("Need both a service and location to see service flags. ".
            "Have you chosen a location by adding hymns?");
        header("Location: modify.php");
        exit(0);
    } else {
        $id = $_GET['id'];
        $location = $_GET['location'];
    }
    ?><!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit Service Flags")?>
    <body>
    <script type="text/javascript">
        $(document).ready(function() {
        });
    </script>
    <? pageHeader();
    siteTabs($auth, "modify"); ?>
        <div id="content-container">
        <div class="quicklinks"><a href="modify.php">Back to Service Listing</a>
        </div>
        <h1>Service Flags</h1>
        <p class="explanation">This page allows you to see the flags on a
service and either add to them or change them.</p>
<?
//    $q = $db->prepare("SELECT d.rite, DATE_FORMAT(d.caldate, '%c/%e/%Y') AS d.date,
//        f.flag, f.value, f.id AS flag_id, f.`uid`, CONCAT(u.fname, ' ', u.lname) AS user
//        FROM `{$db->getPrefix()}days` AS d
//        JOIN `{$db->getPrefix()}service_flags` AS f
//        JOIN `{$db->getPrefix()}users` AS u ON (u.`uid` == f.`uid`)
//        WHERE d.pkey = :day");
//        AND f.location = :location");
    $q = $db->prepare("SELECT d.rite, DATE_FORMAT(d.caldate, '%c/%e/%Y') AS date,
        f.flag, f.value, f.pkey AS flag_id, f.`uid`, CONCAT(u.fname, ' ', u.lname) AS user
        FROM `{$db->getPrefix()}days` AS d
        JOIN `{$db->getPrefix()}service_flags` AS f ON (d.pkey=f.service)
        JOIN `{$db->getPrefix()}users` AS u ON (u.`uid` = f.`uid`)
        WHERE d.pkey = :day
        AND f.location = :location ");
    $q->bindParam(":day", $id);
    $q->bindParam(":location", $location);
    $q->execute() or die(array_pop($q->errorInfo()));
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    if (0 == count($rows)) { // No flags found, just need service info.
        $has_flags = false;
        $q = $db->prepare("SELECT rite, DATE_FORMAT(caldate, '%c/%e/%Y') AS date
            FROM `{$db->getPrefix()}days`
            WHERE pkey = :day");
        $q->bindParam(":day", $id);
        $q->execute() or die(array_pop($q->errorInfo()));
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $has_flags = true;
    }

    //echo ("Found ".count($rows). " at {$location} for {$id}.");

    ?><h1><?=$rows[0]['rite']?> at <?=htmlentities($location)?>
        on <?=$rows[0]['date']?></h1>
      <h2>Current Flags</h2><?

    $authlevel = authLevel();
    $uid = authUid();
    if ($has_flags) {
        if (3 == $authlevel) { // Is Admin
        // Display a form of service flags for privileged users to edit
    ?>
            <form id="service_flags" action="<?= $_SERVER['PHP_SELF']."?stage=2" ?>"
                method="post">

                <input type="hidden" name="step" value="change_flags">
                <dl class="flags">
    <?      foreach ($rows as $row) { ?>
                <dt><input type="text" name="<?="{$row['flag_id']}_flag"?>"
                    value="<?=$row['flag']?>"><br>
                    [<?=$row['user']?>]</dt>
                <dd><input class="flag-value" type="text" name="<?="{$row['flag_id']}_value"?>"
                    value="<?=$row['value']?>" placeholder="No value"></dd>
                <dd><input type="checkbox" name="<?="{$row['flag-id']}_delete"?>">
                    (delete)</dd>
    <?      } ?>
                </dl>

                <button id="submit" type="submit">Submit Flag Changes</button>
            </form>
    <?

        } else {
        // Display a table for less privileged users
    ?>
            <form id="service_flags" action="<?= $_SERVER['PHP_SELF']."?stage=2" ?>"
                method="post">
            <dl class="flags">
    <?      foreach ($rows as $row) {
    ?>         <dt><?=$row['flag']?> <br>
    <?
                if ($uid == $row['uid']) {
                    ?> <button name="delete_flag"
                        data-id="<?=$row['flag_id']?>">Delete</button><?
                } else {
                    ?>[<?=$row['user']?>]<?
                }
                ?></dt>
                <dd><?=$row['value']?></dd>
            </dl>
    <?      } ?>
            </table>
            </form>
    <?
        }
    }
    ?><hr><h2>Set a New Flag</h2><?
    // Display a form for privileged and less-privileged users to add flags
?>
    <form id="add_flag" action="<?= $_SERVER['PHP_SELF']."?stage=2" ?>" method="post">
        <input type="hidden" name="step" value="add_flag">
        <input type="hidden" name="user" value="<?=$uid?>">
        <?
        if (3 == $authlevel) { ?>
            <input type="text" name="flag" placeholder="Name of flag">
        <? } else {
            $options = getOptions();
            ?> <select name="flag" id="flag"> <?
            foreach ($options->get("addable_service_flags") as $opt)
                echo "<option name=\"{$opt}\">{$opt}</option>\n";
            ?> </select>
            <?
        }
    ?>
        <input class="flag-value" type="text" id="value" name="value"
            placeholder="No value">
        <button id="submit" name="submit">Submit New Flag</button>
        </form> <br><?
    $q = queryService($id);
    display_records_table($q, "delete.php");
    ?>
    </div>
    </body>
    </html>
<?
} elseif (2 == $_GET["stage"])
{
    setMessage("Service flags updated at {$now} server time.");
    header("Location: {$protocol}://{$this_script}?id={$service}");
}
