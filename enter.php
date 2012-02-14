<?php
require("functions.php");
require("options.php");
require("setup-session.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;

function entered_hymns($ary) {
    // Process initially entered hymn form data into an array.
    // result is like this $array[item#][book|number|note] = value
    $entered_hymns = array();
    foreach ($ary as $key => $value) {
        if (preg_match('/^(book|number|note)_(\d)/', $key, $matches)) {
            if (array_key_exists($matches[2], $entered_hymns)) {
                $entered_hymns[$matches[2]][$matches[1]] = $value;
            } else {
                $entered_hymns[$matches[2]] = array($matches[1] => $value);
            }
        }
    }
    return $entered_hymns;
}

function entered_hymncount($ary) {
    // Return the number of actual entered hymns in $ary
    $count = 0;
    foreach ($ary as $hymn) {
        if (0 < strlen($hymn['number'])) {
            $count++;
        }
    }
    return $count;
}

if (array_key_exists("date", $_GET)) {
    $date = $_GET['date'];
} else {
    $date = $s['date'];
}
if (array_key_exists("date", $_POST)) {
    processFormData();
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
    <form action="http://<?=$this_script.'?stage=2'?>" method="post">
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
            id="location" name="location" value="<?=$s['location']?>" >
    </li>
    <li>
        <label for="liturgical_name">Liturgical Name:</label>
        <input tabindex="26" type="text"
            id="liturgical_name" name="liturgical_name" size="50"
            maxlength="50" value="<?=$s['liturgical_name']?>">
    </li>
    <li>
        <label for="rite">Rite or Order:</label>
        <input tabindex="27" type="text" id="rite" name="rite"
            size="50" maxlength="50" value="<?=$s['rite']?>">
    </li>
    <li class="vcenter">
        <label for="servicenotes">Service Notes:</label>
        <textarea tabindex="28" id="servicenotes"
            name="servicenotes"><?=trim($s['servicenotes'])?></textarea>
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
            <option <? if ($hymnbook == $s["book_".$i]) echo "selected"; ?>><?=$hymnbook?></option>
        <? } ?>
        </select>
        <input tabindex="<?=$tabindex+1?>" type="number" min="1" id="number_<?=$i?>" name="number_<?=$i?>" value="<?=$s["number_".$i]?>" class="hymn-number">
        <input tabindex="<?=$tabindex+2?>" type="text" id="note_<?=$i?>" name="note_<?=$i?>" class="hymn-note" maxlength="100" value="<?=$s["note_".$i]?>">
        <input tabindex="<?=$tabindex+3?>" type="text" id="title_<?=$i?>" name="title_<?=$i?>" class="hymn-title">
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
function processFormData() {
    // Check for missing data
    print_r($_GET); print_r($_POST); exit(0);
            $title = preg_replace("/\"/", "&#34;", $title);
            $sql2 = "SELECT DATE_FORMAT({$dbp}days.caldate, '%e %b %Y') as
                date,
                {$dbp}hymns.location
                FROM {$dbp}hymns
                JOIN {$dbp}days ON ({$dbp}days.pkey = {$dbp}hymns.service)
                WHERE {$dbp}hymns.number = '{$hymn['number']}'
                  AND {$dbp}hymns.book = '{$hymn['book']}'
                ORDER BY {$dbp}days.caldate DESC LIMIT {$option_used_history}";
            $result2 = mysql_query($sql2) or die(mysql_error());
            $lastusedary = array();
            while ($last = mysql_fetch_array($result2)) {
                $lastusedary[] = $last[0].($last[1]?"@${last[1]}":"");
            }
            $lastused = implode(", ", $lastusedary);
            $lastused = $lastused ? $lastused : "No record.";
            echo "<li {$extra}>{$hymn['book']} {$hymn['number']}
                {$hymn['note']} ".
                "<input type=\"text\" id=\"{$hymn['book']}_{$hymn['number']}\"
                    name=\"{$hymn['book']}_{$hymn['number']}\"
                    value=\"{$title}\" size=\"50\" maxlength=\"50\"> ".
                    "Last Used: {$lastused}</li>\n";
        }
        echo "</ul>\n";
// Old stage 3
    // Insert data into db
    $_SESSION[$sprefix]['stage2'] = $_POST;
    require("db-connection.php");
    require("options.php");
    //// Add a new service, if needed.
    $feedback='<ol>';
    $date = $_SESSION[$sprefix]['stage1']['date'];
    $location = mysql_esc($_SESSION[$sprefix]['stage1']['location']);
    $maxseq = 0; // For adding hymns to an existing service
    if (! array_key_exists("services", $_POST)) {
        errormsg("Forgot to choose a service. Please try again.");
    }
    if ("new" == $_POST["services"]) {
        $dayname = mysql_esc($_SESSION[$sprefix]['stage1']['liturgical_name']);
        $rite = mysql_esc($_SESSION[$sprefix]['stage1']['rite']);
        $servicenotes = mysql_esc($_SESSION[$sprefix]['stage1']['servicenotes']);
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
    } else {
        // If an existing service is selected, grab its pkey and maxseq.
        preg_match('/(\d+)_(\d+)/', $_POST["services"], $matches);
        $serviceid = $matches[1];
        $maxseq = $matches[2];
    }

    ////  Enter new/updated hymn titles (2 steps for clarity)
    // Build an array of hymnbook_hymnnumber items from $_POST
    $hymns = array();
    $altbooks = implode("|", $option_hymnbooks);
    foreach ($_POST as $key => $value) {
        if (preg_match("/(${altbooks})_(\d+)/", $key, $matches)) {
            $hymns[] = array($matches[1], $matches[2], $value);
        }
    }
    // Insert each hymn
    foreach ($hymns as $ahymn) {
        $h = mysql_esc_array($ahymn);
        // Check to see if the hymn is already entered.
        $sql = "INSERT INTO ${dbp}names (book, number, title)
            VALUES ('{$h[0]}', '{$h[1]}', '{$h[2]}')";
        if (mysql_query($sql)) {
            $feedback .= "<li>Saved name '{$h[2]}' for {$h[0]} {$h[1]}.</li>";
        } else {
            $sql = "UPDATE ${dbp}names SET title='${h[2]}'
                WHERE book='${h[0]}' AND number='${h[1]}'";
            mysql_query($sql) or die(mysql_error());
            if (mysql_affected_rows()) {
                $feedback .="<li>Updated name '{$h[2]}' for {$h[0]} {$h[1]}.</li>";
            } else {
                $feedback .="<li>Title for hymn \"{$h[0]} {$h[1]}\" unchanged.</li>";
            }
        }
    }
    //// Enter hymns and location on selected date
    $hymns = entered_hymns($_SESSION[$sprefix]['stage1']);
    if (0 < entered_hymncount($hymns)) {
        $sqlhymns = array();
        $saved = array();
        foreach ($hymns as $sequence => $ahymn)
        {
            if (! $ahymn['number']) continue;
            $hymn = mysql_esc_array($ahymn);
            $realsequence = $sequence + $maxseq;
            $sqlhymns[] = "('{$serviceid}', '{$location}', '{$hymn['book']}',
                '{$hymn['number']}', '{$hymn['note']}', '{$realsequence}')";
            $saved[] = "{$ahymn['book']} {$ahymn['number']} ({$hymn['note']})";
        }
        $sql = "INSERT INTO ${dbp}hymns
            (service, location, book, number, note, sequence)
            VALUES ".implode(", ", $sqlhymns);
        mysql_query($sql) or die(mysql_error());
        $feedback .="<li>Saved hymns: <ol><li>" . implode("</li><li>", $saved) . "</li></ol></li></ol>\n";
    }
    unset($_SESSION[$sprefix]['stage1']);
    unset($_SESSION[$sprefix]['stage2']);
    header("Location: modify.php?message=" . urlencode($feedback));
}
?>
