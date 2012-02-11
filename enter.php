<?php
require("functions.php");
require("options.php");
require("setup-session.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;

function errormsg($msg) {
    // Head back to start with an error message.
    global $this_script;
    header("Location: http://${this_script}?error=".urlencode($msg));
    exit(0);
}

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

if (! array_key_exists('stage', $_GET)) {
    # Initial entry form
    if (array_key_exists('stage1', $_SESSION[$sprefix])) {
        $s = $_SESSION[$sprefix]['stage1'];
    } else {
        $s = array();
    }
    require("options.php");
    if (array_key_exists("date", $_GET)) {
        $date = $_GET['date'];
    } else {
        $date = $s['date'];
    }
?>
<!DOCTYPE HTML>
<html>
<?=html_head("Service Entry Form: ${this_script}", $five=true)?>
<body>
    <header>
    <? if ($_GET['error']) { ?>
        <p class="errormessage"><?=htmlspecialchars($_GET['error'])?></p>
    <? } ?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <article id="content-container">
    <header>
    <h1>Service Entry Form</h1>
    <p class="explanation">This form allows you to enter a new service,
    optionally including several hymns for use at a certain location.  (The
    possible hymnbooks, and the number of hymn lines can be configured in the
    file "options.php" on the webserver.)  After you submit this page, you will
    be asked to confirm whether you intend to create a new service or add hymns
    to an existing service.</p>
    </header>
    <form action="http://<?=$this_script.'?stage=2'?>" method="post">
    <ul>
    <li>
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" value="<?=$date?>">
    </li>
    <li>
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" value="<?=$s['location']?>">
    </li>
    <li>
        <label for="liturgical_name">Liturgical Name:</label>
        <input type="text" id="liturgical_name" name="liturgical_name" size="50" maxlength="50" value="<?=$s['liturgical_name']?>">
    </li>
    <li>
        <label for="rite">Rite or Order:</label>
        <input type="text" id="rite" name="rite" size="50" maxlength="50" value="<?=$s['rite']?>">
    </li>
    <li class="vcenter">
        <label for="servicenotes">Service Notes:</label>
        <textarea id="servicenotes" name="servicenotes"><?=trim($s['servicenotes'])?></textarea>
    </li>
    </ul>
    <h2>Hymns to Enter (Book, Number, Note)</h2>
    <ol id="hymnentries">
    <? for ($i=1; $i<=$option_hymncount; $i++) { ?>
    <li class="<?= $i%2==0?"even":"odd" ?>">
        <select id="book_<?=$i?>" name="book_<?=$i?>">
        <? foreach ($option_hymnbooks as $hymnbook) { ?>
            <option <? if ($hymnbook == $s["book_".$i]) echo "selected"; ?>><?=$hymnbook?></option>
        <? } ?>
        </select>
        <input type="text" id="number_<?=$i?>" name="number_<?=$i?>" value="<?=$s["number_".$i]?>" size="5">
        <input type="text" id="note_<?=$i?>" name="note_<?=$i?>" size="80" maxlength="100" value="<?=$s["note_".$i]?>">
    </li>
    <? } ?>
    </ol>
    <a class="jsonly" href="javascript: void(0);" onclick="addHymn()">Add another hymn.</a>
    <input type="submit" value="Send"><input type="reset">
    </form>
    </article>
</body>
</html>
<?
} elseif (2 == $_GET['stage']) {
    // Check for missing data
    // print_r($_GET); print_r($_POST); exit(0);
    require("options.php");
    $_SESSION[$sprefix]['stage1'] = $_POST;
    if (! (array_key_exists('date', $_POST)
            && $_POST['date'])) {
        errormsg("Please enter a date.");
    }
    if (! (array_key_exists('location', $_POST)
            && $_POST['location'])) {
        errormsg("Please enter a location.");
    }
    ?>
    <html>
    <?=html_head("Confirmation (Entry Step 2)", $five=true)?>
    <body>
    <header>
    <? if ($_GET['error']) { ?>
        <p class="errormessage"><?=htmlspecialchars($_GET['error'])?></p>
    <? } ?>
    <p><a href="enter.php">Back to start</a></p>
    </header>
    <article id="content-container">
    <h1>Confirmation (Entry Step 2)</h1>
    <p class="explanation">In this final step for entering a service, you are
    presented with a list of existing services on the date you chose, together
    with the option of creating a new service.  Please confirm what you'd like
    to do. If the hymn titles for your chosen hymns have already been entered,
    you can see those here.  If they haven't, you have the chance to enter the
    titles.  You can always change them later by editing this service from the
    "Modify Services" tab.</p>
    <dl>
        <dt>Date</dt><dd><?=$_POST['date']?></dd>
        <dt>Location</dt><dd><?=$_POST['location']?></dd>
        <dt>Notes</dt><dd><?=translate_markup($_POST['servicenotes'])?></dd>
    </dl>
    <form action="http://<?=$this_script."?stage=3"?>" method="post">
    <section>
    <h2>Choose the Service</h2>
    <?
    // Check to see if this service is already entered.
    require("db-connection.php");
    $location = mysql_esc($_POST['location']);
    $date = strftime("%Y-%m-%d", strtotime($_POST['date']));
    $_SESSION[$sprefix]['stage1']['date'] = $date;
    $sql = "SELECT 1 FROM {$dbp}days
        LEFT JOIN {$dbp}hymns ON ({$dbp}hymns.service = {$dbp}days.pkey)
        WHERE {$dbp}days.caldate = '{$date}'";
    $result = mysql_query($sql) or die(mysql_error().$sql);
    echo "<ul>\n";
    if (mysql_fetch_row($result)) {
        /// Service already entered.  Ask if entered hymns s/b appended
        // Get the max sequence number at this location
        $sql = "SELECT MAX({$dbp}hymns.sequence) as maxseq
            FROM {$dbp}days JOIN {$dbp}hymns
            ON ({$dbp}hymns.service = {$dbp}days.pkey)
            WHERE {$dbp}days.caldate = '{$date}'
                AND {$dbp}hymns.location = '{$location}'
            GROUP BY ({$dbp}hymns.service)";
        $result = mysql_query($sql) or die(mysql_error().$sql);
        if ($row = mysql_fetch_array($result))
        {
            $maxseq = $row[0];
        } else {
            $maxseq = 0;
        }
        // Get the list of entered hymns for this date, all services/locations.
        $sql = "SELECT {$dbp}hymns.book, {$dbp}hymns.number, {$dbp}hymns.note,
            {$dbp}hymns.location, {$dbp}days.servicenotes,
            {$dbp}days.name as dayname, {$dbp}days.rite,
            {$dbp}days.pkey as service, {$dbp}names.title
            FROM {$dbp}days
            LEFT JOIN {$dbp}hymns ON ({$dbp}hymns.service = {$dbp}days.pkey)
            LEFT JOIN {$dbp}names ON ({$dbp}hymns.number = {$dbp}names.number
                AND {$dbp}hymns.book = {$dbp}names.book)
            WHERE {$dbp}days.caldate = '{$date}'
            ORDER BY dayname, location";
        $result = mysql_query($sql) or die(mysql_error().$sql);
        $dayname = "";
        while ($row = mysql_fetch_assoc($result)) {
            if ($dayname != $row['dayname']) {
                if ("" != $dayname) echo "</li>"; // close prior <li>
                echo "<li><input type=\"radio\" name=\"services\"
                    value=\"{$row['service']}_{$maxseq}\">
                    Add to '{$row['dayname']}' using '{$row['rite']}'\n";
                if ($row['servicenotes']) {
                    echo "<div>".translate_markup($row['servicenotes'])."</div>\n";
                }
                $dayname = $row['dayname'];
            }
            if ($row['number']) {
                echo "<p class=\"hymnlist\">{$row['location']}: ".
                    "{$row['book']} {$row['number']} ".
                    "{$row['note']} <em>{$row['title']}</em></p>\n" ;
            }
        }
        echo "</li>\n";
    }
    echo "<li><input type=\"radio\" name=\"services\" value=\"new\">".
        " Enter '{$_POST['liturgical_name']}' as a new service, using
        '{$_POST['rite']}'.</li>\n";
    echo "</ul>\n";
?>
    </section>
    <section>
<?
    echo "<h2>Confirm or Enter Hymn Titles</h2>\n";
    // Combine entered pieces into an array.
    $entered_hymns = entered_hymns($_POST);
    if (0 == entered_hymncount($entered_hymns)) {
        echo "<p>No hymns entered.</p>";
    } else {
        // Output array to confirm/enter hymn titles
        echo "<ul>\n";
        foreach ($entered_hymns as $hymn) {
            if (! $hymn['number']) { continue; }
            $sql = "SELECT `title` FROM `${dbp}names`
                WHERE number = '${hymn['number']}'
                AND book = '${hymn['book']}'";
            $result = mysql_query($sql) or die(mysql_error());
            if ($titlerec = mysql_fetch_row($result)) {
                $title = $titlerec[0];
                $extra = 'class="verified"';
            } else {
                if (array_key_exists('stage2', $_SESSION[$sprefix])
                    && array_key_exists("${hymn['book']}_${hymn['number']}",
                    $_SESSION[$sprefix]['stage2'])) {
                    $title =
                        $_SESSION[$sprefix]['stage2']["${hymn['book']}_${hymn['number']}"];
                } else {
                    $title = "No title found. Please enter one.";
                }
                $extra = 'class="unverified"';
            }
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
    }
    ?>
    </section>
    <input type="submit" value="Send"><input type="reset">
    </form>
    </article>
    </body>
    </html>
    <?
} elseif (3 == $_GET['stage']) {
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
