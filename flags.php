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
$uid = authUid();
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! (isset($_POST['step']) || isset($_POST['json']))) {
    if ("get" == $_GET['action'] &&
        is_numeric($_GET['service']) && $_GET['occurrence'])
    {
        // Return a json-formatted flag list snippet.
        $q = getFlagsFor($_GET['service'], $_GET['occurrence']);
        $results = $q->fetchAll(PDO::FETCH_ASSOC);
        $rv = array();
        foreach ($results as $flag) {
            $flag = array_map(function($v) {return htmlspecialchars($v);}, $flag);
            if (3 == authLevel()) {
                $deletelink = "<a class=\"delete-flag\" href=\"#\" data-flagid=\"{$flag['flagid']}\" data-userid=\"{$uid}\"></a>";
            } else { $deletelink = ""; }
            $rv[] = "<div class=\"flag-repr\">
                <div class=\"flag-name\">{$deletelink}{$flag['flag']}<br><span class=\"flag-creator\">{$flag['user']}</span></div>
                <div class=\"flag-value\">{$flag['value']}</div>
                </div>";
        }
        $formatted = implode("\n", $rv);
        echo(json_encode(array(count($results), $formatted)));
        exit(0);
    }

    // Display flags modification page
    if (! (is_numeric($_GET['id']) and $_GET['occurrence']) ) {
        setMessage("Need both a service and occurrence to see service flags. ".
            "Have you chosen a occurrence by adding hymns?");
        header("Location: modify.php");
        exit(0);
    } else {
        $id = $_GET['id'];
        $occurrence = $_GET['occurrence'];
        $urloccurrence = urlencode($occurrence);
        $htmloccurrence = htmlspecialchars($occurrence);
    }
    ?><!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit Service Flags")?>
    <body>
    <script type="text/javascript">
        $(document).ready(function() {
            setupFlags();
        });
    </script>
    <? pageHeader();
    siteTabs("modify"); ?>
        <div id="content-container">
        <div class="quicklinks"><a href="modify.php">Back to Service Listing</a>
        </div>
        <h1>Service Flags</h1>
        <p class="explanation">This page allows you to see the flags on a
service and either add to them or change them.</p>

<?  echo generateFlagsForm($id, $occurrence);
    $q = queryService($id);
    display_records_table($q, "delete.php");
    ?>
    </div>
    </body>
    </html>
<?

} elseif (isset($_POST['json'])
    && ! isset($_POST['submit'])
    && ! isset($_POST['step']))
{
    echo json_encode(array(true, generateFlagsForm($_POST['service'], $_POST['occurrence'])));
    exit(0);

} elseif ("add_flag" == $_POST["step"]) {

    $uid = checkPostUser();
    $q = $db->prepare("INSERT INTO `{$db->getPrefix()}service_flags`
        (`service`, `occurrence`, `flag`, `value`, `uid`)
        VALUES (:service, :occurrence, :flag, :value, :uid)");
    $q->bindParam(":service", $_POST['service']);
    $q->bindParam(":occurrence", $_POST['occurrence']);
    $q->bindParam(":flag", $_POST['flag']);
    $q->bindParam(":value", $_POST['value']);
    $q->bindParam(":uid", $uid);
    $q->execute() or die(array_pop($q->errorInfo()));

    $message = "Service flag added.";
    if (isset($_POST['json'])) {
        echo json_encode(array(true, $message));
        exit(0);
    }
    setMessage($message);
    header("Location: {$protocol}://{$this_script}?id={$_POST['service']}&occurrence={$_POST['occurrence']}");

} elseif ("delete_flag" == $_POST["step"]) {

    $uid = checkPostUser();
    if ($_POST['delete_flag']) {
        //print_r($_POST);
        //exit(0);
        $flag_id = $_POST['delete_flag'];
        // Check that this flag is owned by the current user.
        $q = $db->prepare("DELETE FROM `{$db->getPrefix()}service_flags`
            WHERE `pkey` = :flag_id AND `uid` = :user");
        $q->bindParam(":flag_id", $flag_id);
        $q->bindParam(":user", $uid);
        $q->execute() or die(array_pop($q->errorInfo()));
        if (1 > $q->rowCount()) {
            $message = "Flag was not deleted. Are you sure it was yours?";
            $json = json_encode(array(false, $message));

        } else {
            $message = "Service flag deleted.";
            $json = json_encode(array(true, $message));
        }
        if (isset($_POST['json'])) {
            echo $json;
            exit(0);
        }
        setMessage($message);
        header("Location: {$protocol}://{$this_script}?id={$_POST['service']}&occurrence={$_POST['occurrence']}");
    } else {
        $message = "Couldn't identify a flag to delete.";
        if (isset($_POST['json'])) {
            echo json_encode(array(false, $message));
            exit(0);
        }
        setMessage($message);
        header("Location: index.php");
    }

} elseif ("change_flags" == $_POST["step"]) {

    $uid = checkPostUser();
    requireAuth("index.php", 3);
    unset($_POST['step']); unset($_POST['user']);
    $message = array();
    $matches = array();
    $deletes = array();
    $flags = array();
    foreach ($_POST as $key => $value) {
        if (preg_match('/(\d+)_delete/', $key, $matches)) {
            $deletes[] = $matches[1];
        }
        if (preg_match('/(\d+)_flag/', $key, $matches)) {
            $flags[$matches[1]]['flag'] = $value;
        }
        if (preg_match('/(\d+)_value/', $key, $matches)) {
            $flags[$matches[1]]['value'] = $value;
        }
    }
    $deletecount = 0;
    $flag_id = 0;
    $db->beginTransaction();
    $q = $db->prepare("DELETE FROM `{$db->getPrefix()}service_flags`
        WHERE `pkey` = :flag_id");
    $q->bindParam(":flag_id", $flag_id);
    foreach ($deletes as $fid) {
        if (isset($flags[$fid])) {
            unset($flags[$fid]);
        }
        $flag_id = $fid;
        $q->execute() or die(array_pop($q->errorInfo()));
        $deletecount += $q->rowCount();
    }
    $message[] = "Deleted {$deletecount} flags.";

    // Get current flag:value pairs to see what's changed.
    $q = $db->prepare("SELECT `flag`, `value` FROM `{$db->getPrefix()}service_flags`
        WHERE `service` = :service AND `occurrence` = :occurrence");
    $q->bindParam(":service", $_POST['service']);
    $q->bindParam(":occurrence", $_POST['occurrence']);
    $q->execute() or die(array_pop($q->errorInfo()));
    $pairs = array();
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $pairs[$row['flag']] = $row['value'];
    }

    // Check what's actually changed
    $unchanged = array();
    foreach ($flags as $fid => $data) {
        if ($pairs[$data['flag']] == $data['value']) {
            $unchanged[] = $fid;
        }
    }

    // Remove unchanged flags from update list
    foreach ($unchanged as $u) {
        unset($flags[$u]);
    }

    // Update remaining flags
    $updatecount = 0;
    $flag = "";
    $value = '';
    $flag_id = 0;
    $q = $db->prepare("UPDATE `{$db->getPrefix()}service_flags`
        SET `flag` = :flag, `value` = :value, `uid` = :uid
        WHERE `pkey` = :flag_id");
    $q->bindParam(":flag", $flag);
    $q->bindParam(":value", $value);
    $q->bindParam(":uid", $uid);
    $q->bindParam(":flag_id", $flag_id);
    foreach ($flags as $fid => $data) {
        $flag_id = $fid;
        $flag = $data['flag'];
        $value = $data['value'];
        $q->execute() or die(array_pop($q->errorInfo()));
        $updatecount += $q->rowCount();
    }
    $message[] = "{$updatecount} service flags updated.";
    $db->commit();
    if (isset($_POST['json'])) {
        echo json_encode(array("true", implode("<br>", $message)));
        exit(0);
    }
    setMessage(implode("<br>", $message));
    header("Location: {$protocol}://{$this_script}?id={$_POST['service']}&occurrence={$_POST['occurrence']}");

}

function checkPostUser() {
    if ($_POST["user"] == authUid()) {
        return $_POST["user"];
    } else {
        $message = "Access denied.";
        if (isset($_POST['json'])) {
            echo json_encode(array(0, $message));
        } else {
            setMessage($message);
            header("Location: index.php");
        }
        exit(0);
    }
}

function generateFlagsForm($id, $occurrence) {
    global $db;
    $uid = authUid();
    ob_start();
    $q = $db->prepare("SELECT d.rite, DATE_FORMAT(d.caldate, '%c/%e/%Y') AS date,
        f.flag, f.value, f.pkey AS flag_id, f.`uid`, CONCAT(u.fname, ' ', u.lname) AS user
        FROM `{$db->getPrefix()}days` AS d
        JOIN `{$db->getPrefix()}service_flags` AS f ON (d.pkey=f.service)
        JOIN `{$db->getPrefix()}users` AS u ON (u.`uid` = f.`uid`)
        WHERE d.pkey = :day
        AND f.occurrence = :occurrence ");
    $q->bindParam(":day", $id);
    $q->bindParam(":occurrence", $occurrence);
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

    //echo ("Found ".count($rows). " at {$occurrence} for {$id}.");

    ?><h1><?=htmlspecialchars($rows[0]['rite'])?> at <?=htmlspecialchars($occurrence)?>
        on <?=$rows[0]['date']?></h1>
      <h2>Current Flags</h2><?

    $authlevel = authLevel();
    if ($has_flags) {
        if (3 == $authlevel) { // Is Admin
        // Display a form of service flags for privileged users to edit
    ?>
            <form id="service_flags" action="<?= $_SERVER['PHP_SELF'] ?>"
                method="post">

                <input type="hidden" name="step" value="change_flags">
                <input type="hidden" name="service" value="<?=$id?>">
                <input type="hidden" name="occurrence" value="<?=htmlspecialchars($occurrence)?>">
                <input type="hidden" name="user" value="<?=$uid?>">
                <dl class="flags">
    <?      foreach ($rows as $row) { ?>
                <dt><input type="text" name="<?="{$row['flag_id']}_flag"?>"
                    value="<?=htmlspecialchars($row['flag'])?>"><br>
                    [<?=htmlspecialchars($row['user'])?>]</dt>
                <dd><input class="flag-value" type="text" name="<?="{$row['flag_id']}_value"?>"
                    value="<?=htmlspecialchars($row['value'])?>" placeholder="No value"></dd>
                <dd><input type="checkbox" name="<?="{$row['flag_id']}_delete"?>">
                    (delete)</dd>
    <?      } ?>
                </dl>

                <button id="submit" name="submit">Submit Flag Changes</button>
            </form>
    <?

        } else {
        // Display a list for less privileged users
    ?>
            <form id="service_flags" action="<?= $_SERVER['PHP_SELF'] ?>"
                method="post">
            <input type="hidden" name="step" value="delete_flag">
            <input type="hidden" name="user" value="<?=$uid?>">
            <input type="hidden" name="service" value="<?=$id?>">
            <input type="hidden" name="occurrence" value="<?=htmlspecialchars($occurrence)?>">
            <dl class="flags">
    <?      foreach ($rows as $row) {
    ?>         <dt><?=htmlspecialchars($row['flag'])?> <br>
                [<?=htmlspecialchars($row['user'])?>] </dt>
               <dd><?=htmlspecialchars($row['value'])?>
    <?
                if ($uid == $row['uid']) {
                  ?><br><button name="delete_flag"
                        data-id="<?=$row['flag_id']?>"
                        value="<?=$row['flag_id']?>">Delete</button><?
                } ?>
                <dd>
    <?      } ?>
            </dl>
            </table>
            </form>
    <?
        }
    }
    ?><hr><h2>Set a New Flag</h2><?
    // Display a form for privileged and less-privileged users to add flags
?>
    <form id="add_flag" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
        <input type="hidden" name="step" value="add_flag">
        <input type="hidden" name="user" value="<?=$uid?>">
        <input type="hidden" name="service" value="<?=$id?>">
        <input type="hidden" name="occurrence" value="<?=$occurrence?>">
        <?
        if (3 == $authlevel) { ?>
            <input type="text" name="flag" placeholder="Name of flag">
        <? } else {
            $options = getOptions();
            ?> <select name="flag" id="flag"> <?
            foreach ($options->get("addable_service_flags") as $opt)
                echo "<option value=\"{$opt}\">{$opt}</option>\n";
            ?> </select>
            <?
        }
    ?>
        <input class="flag-value" type="text" id="value" name="value"
            placeholder="Flag value (optional)">
        <button id="submit" name="submit">Submit New Flag</button>
        </form> <br><?
    return ob_get_clean();
}
