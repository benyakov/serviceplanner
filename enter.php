<?php
require("functions.php");
require("options.php");
require("setup-session.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;

if (array_key_exists("date", $_GET)) {
    $date = $_GET['date'];
}
if (array_key_exists("date", $_POST)) {
    processFormData();
    exit(0);
}
?>
<!DOCTYPE HTML>
<html lang="en">
<?=html_head("Service Entry Form: ${this_script}", $five=true)?>
    <script type="text/javascript">
    $(document).ready(function() {
        $("#existing-services").hide();
        showJsOnly();
        $("#date").datepicker({showOn:"both"});
        $("#date").keyup(updateExisting)
            .change(updateExisting);
        $(".hymn-number").keyup(fetchHymnTitle)
            .blur(fetchHymnTitle);
    })
    </script>
<body>
    <header>
    <? if ($_GET['error']) { ?>
        <p class="errormessage"><?=htmlspecialchars($_GET['error'])?></p>
    <? } ?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <header>
    <h1>Service Entry Form</h1>
    <p class="explanation">This form allows you to enter a new service,
    or to add hymns to an existing service.
    The available hymnbooks can be configured in the
    file "options.php" on the webserver. </p>
    </header>
    <form action="http://<?=$this_script?>" method="post">
    <section id="existing-services">
    </section>
    <section id="service-items">
    <ul>
    <li>
        <label for="date">Date:</label>
        <input tabindex="1" type="date" id="date"
            name="date" value="<?=$date?>" autofocus required>
    </li>
    <li>
        <label for="location">Location:</label>
        <input tabindex="25" type="text" required
            id="location" name="location" value="" >
    </li>
    <li>
        <label for="liturgical_name">Liturgical Name:</label>
        <input tabindex="26" type="text"
            id="liturgicalname" name="liturgicalname" size="50"
            maxlength="50" value="">
    </li>
    <li>
        <label for="rite">Rite or Order:</label>
        <input tabindex="27" type="text" id="rite" name="rite"
            size="50" maxlength="50" value="">
    </li>
    <li class="vcenter">
        <label for="servicenotes">Service Notes:</label>
        <textarea tabindex="28" id="servicenotes"
            name="servicenotes"></textarea>
    </li>
    </ul>
    </section>
    <h2>Hymns to Enter (Book, Number, Note)</h2>
    <ol id="hymnentries">
    <? for ($i=1; $i<=$option_hymncount; $i++) {
        $tabindex = $i*4 + 51; ?>
    <li class="<?= $i%2==0?"even":"odd" ?>">
        <select tabindex="<?=$tabindex?>" id="book_<?=$i?>" name="book_<?=$i?>">
        <? foreach ($option_hymnbooks as $hymnbook) { ?>
            <option><?=$hymnbook?></option>
        <? } ?>
        </select>
        <input tabindex="<?=$tabindex+1?>" type="number" min="1" id="number_<?=$i?>" name="number_<?=$i?>" value="" class="hymn-number">
        <input tabindex="<?=$tabindex+2?>" type="text" id="note_<?=$i?>" name="note_<?=$i?>" class="hymn-note" maxlength="100" value="">
        <input tabindex="<?=$tabindex+3?>" type="text" id="title_<?=$i?>" name="title_<?=$i?>" class="hymn-title">
        <div id="past_<?=$i?>" class="hymn-past"></div>
    </li>
    <? } ?>
    </ol>
    <a class="jsonly" tabindex="200"
        href="javascript: void(0);" onclick="addHymn()">Add another hymn.</a>
    <button tabindex="201" type="submit" value="Send">Send</button>
    <button tabindex="202" type="reset">Reset</button>
    </form>
    </div>
</body>
</html>
<?
function existing($str) {
    if (strpos($str, "existing_")) return true;
    else return false;
}
function processFormData() {
    // Insert data into db
    // echo "POST:"; print_r($_POST); exit(0);
    require("db-connection.php");
    //// Add a new service, if needed.
    $feedback='<ol>';
    $date = strftime("%Y-%m-%d", strtotime($_POST['date']));
    $location = mysql_esc($_POST['location']);
    $existingKey = array_pop(preg_grep('/^existing_/', array_keys($_POST)));
    if ($existingKey) {
        preg_match('/existing_(\d+)/', $existingKey, $matches);
        $serviceid = $matches[1];
    } else {
        $serviceid = false;
    }
    if (! $serviceid) { // Create a new service
        $dayname = mysql_esc($_POST['liturgicalname']);
        $rite = mysql_esc($_POST['rite']);
        $servicenotes = mysql_esc($_POST['servicenotes']);
        $sql = "INSERT INTO {$dbp}days (caldate, name, rite, servicenotes)
            VALUES ('{$date}', '{$dayname}', '{$rite}', '{$servicenotes}')";
        mysql_query($sql) or die(mysql_error());
        // Grab the pkey of the newly inserted row.
        $sql = "SELECT LAST_INSERT_ID()";
        $result = mysql_query($sql) or die(mysql_error());
        $row = mysql_fetch_row($result);
        $serviceid = $row[0];
        $feedback .= "<li>Saved a new service on '{$date}' for
            '{$dayname}'.</li>";
    }
    ////  Enter new/updated hymn titles (2 steps for clarity)
    // Build an array of hymnbook_hymnnumber items from $_POST
    $hymns = array();
    $altfields = "book|number|note|title";
    foreach ($_POST as $key => $value) {
        if (preg_match("/({$altfields})_(\d+)/", $key, $matches)) {
            if (! array_key_exists($matches[2], $hymns)) {
                $hymns[$matches[2]] = array($matches[1]=>$value);
            } else {
                $hymns[$matches[2]][$matches[1]] = $value;
            }
        }
    }
    // Remove blank hymn entries
    $hymns = array_filter($hymns, create_function('$s','return $s["number"];'));
    // Insert each hymn title
    foreach ($hymns as $ahymn) {
        $h = mysql_esc_array($ahymn);
        // Check to see if the hymn is already entered.
        $sql = "INSERT INTO {$dbp}names (book, number, title)
            VALUES ('{$h["book"]}', '{$h["number"]}', '{$h["title"]}')";
        if (mysql_query($sql)) {
            $feedback .= "<li>Saved name '{$h["title"]}' for {$h["book"]} {$h["number"]}.</li>";
        } else {
            $sql = "UPDATE {$dbp}names SET title='${h["title"]}'
                WHERE book='${h["book"]}' AND number='${h["number"]}'";
            $feedback.=$sql;
            mysql_query($sql) or die(mysql_error());
            if (mysql_affected_rows()) {
                $feedback .="<li>Updated name '{$h["title"]}' for {$h["book"]} {$h["number"]}.</li>";
            } else {
                $feedback .="<li>Title for hymn \"{$h["book"]} {$h["number"]}\" unchanged.</li>";
            }
        }
    }
    //// Enter hymns and location on selected date
    if ($hymns) {
        $sqlhymns = array();
        $saved = array();
        $sql = "SELECT MAX(`sequence`) FROM `{$dbp}hymns`
            WHERE `service`='{$serviceid}'
            GROUP BY `service`";
        $result = mysql_query($sql) or die(mysql_error());
        $sequenceMax = array_pop(mysql_fetch_row($result));
        foreach ($hymns as $sequence => $ahymn)
        {
            if (! intval($ahymn['number'])) continue;
            $hymn = mysql_esc_array($ahymn);
            $realsequence = $sequence + $sequenceMax;
            $sqlhymns[] = "('{$serviceid}', '{$location}', '{$hymn['book']}',
                '{$hymn['number']}', '{$hymn['note']}', '{$realsequence}')";
            $saved[] = "{$ahymn['book']} {$ahymn['number']}";
        }
        $sql = "INSERT INTO `{$dbp}hymns`
            (service, location, book, number, note, sequence)
            VALUES ".implode(", ", $sqlhymns);
        $result = mysql_query($sql) or die(mysql_error());
        if (mysql_affected_rows($result)) {
            $feedback .="<li>Saved hymns: <ol><li>" . implode("</li><li>", $saved) . "</li></ol></li></ol>\n";
        }
    }
    header("Location: modify.php?message=" . urlencode($feedback));
}
?>
