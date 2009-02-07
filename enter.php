<?php
require("functions.php");
session_start();
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;

function errormsg($msg)
{ // Head back to start with an error message.
    global $this_script;
    header("Location: http://${this_script}?error=".urlencode($msg));
    exit(0);
}

function entered_hymns($ary)
{ // Process initially entered hymn form data into an array.
  // result is like this $array[item#][book|number|note] = value
    $entered_hymns = array();
    foreach ($ary as $key => $value)
    {
        if (preg_match('/^(book|number|note)_(\d)/', $key, $matches))
        {
            if (array_key_exists($matches[2], $entered_hymns))
            {
                $entered_hymns[$matches[2]][$matches[1]] = $value;
            } else {
                $entered_hymns[$matches[2]] = array($matches[1] => $value);
            }
        }
    }
    return $entered_hymns;
}

if (! array_key_exists('stage', $_GET))
{ # Initial entry form
    if (array_key_exists('stage1', $_SESSION))
    {
        $s = $_SESSION['stage1'];
    } else {
        $s = array();
    }
    require("options.php");
?>
<html>
<?=html_head("Service Entry Form: ${this_script}")?>
<body>
    <p><a href="records.php">Records</a><p>

    <? if ($_GET['error']) { ?>
        <p class="errormessage"><?=$_GET['error']?></p>
    <? } ?>
    <h1>Service Entry Form</h1>
    <form action="http://<?=$this_script.'?stage=2'?>" method="POST">
    <ul>
    <li>
        <label for="date">Date (as DDMonYYYY):</label>
        <input type="text" id="date" name="date" value="<?=$s['date']?>">
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
    </ul>
    <h2>Hymns to Enter (Book, Number, Note)</h2>
    <ol>
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
    <input type="submit" value="Send"><input type="reset">
    </form>
</body>
</html>
<?
} elseif (2 == $_GET['stage']) {
    // Check for missing data
    // print_r($_GET); print_r($_POST); exit(0);
    require("options.php");
    $_SESSION['stage1'] = $_POST;
    if (! (array_key_exists('date', $_POST)
            && $_POST['date'])) {
        errormsg("Please enter a date.");
    }
    if (! (array_key_exists('location', $_POST)
            && $_POST['location'])) {
        errormsg("Please enter a location.");
    }
    if (! (array_key_exists('liturgical_name', $_POST)
            && $_POST['liturgical_name'])) {
        errormsg("Please enter a liturgical name.");
    }
    ?>
    <html>
    <?=html_head("Confirmation (Entry Step 2)")?>
    <body>
    <? if ($_GET['error']) { ?>
        <p class="errormessage"><?=$_GET['error']?></p>
    <? } ?>
    <p><a href="enter.php">Back to start</a></p>
    <h1>Confirmation (Entry Step 2)</h1>
    <dl>
        <dt>Date</dt><dd><?=$_POST['date']?></dd>
        <dt>Location</dt><dd><?=$_POST['location']?></dd>
    </dl>
    <form action="http://<?=$this_script."?stage=3"?>" method="POST">
    <h2>Choose the Service</h2>
    <?
    // Check to see if this service is already entered.
    require("db-connection.php");
    $location = mysql_esc($_POST['location']);
    $date = strftime("%Y-%m-%d", strtotime($_POST['date']));
    $_SESSION['stage1']['date'] = $date;
    $sql = "SELECT 1 FROM hymns JOIN days ON (hymns.service = days.pkey)
        WHERE days.caldate = '${date}'";
    $result = mysql_query($sql) or die(mysql_error());
    echo "<ul>\n";
    if (mysql_fetch_row($result))
    {
        /// Service already entered.  Ask if entered hymns s/b appended
        // Get the max sequence number at this location
        $sql = "SELECT MAX(hymns.sequence) as maxseq
            FROM hymns JOIN days ON (hymns.service = days.pkey)
            WHERE days.caldate = '${date}'
                AND hymns.location = '${location}'
            GROUP BY (hymns.service)";
        $result = mysql_query($sql) or die(mysql_error());
        $row = mysql_fetch_array($result);
        $maxseq = $row[0];
        // Get the list of entered hymns for this date, all services/locations.
        $sql = "SELECT hymns.book, hymns.number, hymns.note, hymns.location,
            days.name as dayname, days.rite, days.pkey as service,
            names.title
            FROM hymns JOIN days ON (hymns.service = days.pkey)
            LEFT JOIN names ON (hymns.number = names.number
                AND hymns.book = names.book)
            WHERE days.caldate = '${date}'
            ORDER BY dayname, location";
        $result = mysql_query($sql) or die(mysql_error());
        $dayname = "";
        while ($row = mysql_fetch_assoc($result))
        {
            if ($dayname != $row['dayname'])
            {
                if ("" != $dayname) echo "</li>"; // close prior <li>
                echo "<li><input type=\"radio\" name=\"services\"
                    value=\"${row['service']}_${maxseq}\">
                    Add to '${row['dayname']}' using '${row['rite']}'\n";
                $dayname = $row['dayname'];
            }
            echo "<p class=\"hymnlist\">${row['location']}: ".
                "${row['book']} ${row['number']} ".
                "${row['note']} <em>${row['title']}</em></p>\n" ;
        }
        echo "</li>\n";
    }
    echo "<li><input type=\"radio\" name=\"services\" value=\"new\">".
        " Enter '${_POST['liturgical_name']}' as a new service, using '${_POST['rite']}'.</li>\n";
    echo "</ul>\n";
    echo "<h2>Confirm or Enter Hymn Titles</h2>\n";
    // Combine entered pieces into an array.
    $entered_hymns = entered_hymns($_POST);
    // Output array to confirm/enter hymn titles
    echo "<ul>\n";
    foreach ($entered_hymns as $hymn)
    {
        if (! $hymn['number']) { continue; }
        $sql = "SELECT title FROM names
            WHERE number = '${hymn['number']}'
            AND book = '${hymn['book']}'";
        $result = mysql_query($sql) or die(mysql_error());
        if ($titlerec = mysql_fetch_row($result))
        {
            $title = $titlerec[0];
            $extra = 'class="verified"';
        } else {
            if (array_key_exists('stage2', $_SESSION)
                && array_key_exists("${hymn['book']}_${hymn['number']}",
                $_SESSION['stage2']))
            {
                $title = $_SESSION['stage2']["${hymn['book']}_${hymn['number']}"];
            } else {
                $title = "No title found. Please enter one.";
            }
            $extra = 'class="unverified"';
        }
        $sql2 = "SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
            hymns.location
            FROM hymns JOIN days ON (days.pkey = hymns.service)
            WHERE hymns.number = '${hymn['number']}'
              AND hymns.book = '${hymn['book']}'
            ORDER BY days.caldate DESC LIMIT ${option_used_history}";
        $result2 = mysql_query($sql2) or die(mysql_error());
        $lastusedary = array();
        while ($last = mysql_fetch_array($result2))
        {
            $lastusedary[] = $last[0].($last[1]?"@${last[1]}":"");
        }
        $lastused = implode(", ", $lastusedary);
        $lastused = $lastused ? $lastused : "No record.";
        echo "<li ${extra}>${hymn['book']} ${hymn['number']} ${hymn['note']} ".
            "<input type=\"text\" id=\"${hymn['book']}_${hymn['number']}\"
                name=\"${hymn['book']}_${hymn['number']}\"
                value=\"${title}\" size=\"50\" maxlength=\"50\"> ".
                "Last Used: ${lastused}</li>\n";
    }
    ?>
        </ul>
    </table>
    <input type="submit" value="Send"><input type="reset">
    </form>
    </body>
    </html>
    <?
} elseif (3 == $_GET['stage']) {
    // Insert data into db
    $_SESSION['stage2'] = $_POST;
    require("db-connection.php");
    require("options.php");
    //// Add a new service, if needed.
?>
    <html><?=html_head("Results")?>
    <body>
    <p><a href="records.php">See Records</a> |
        <a href="enter.php">Enter another service</a></p>
    <h1>Results</h1>
    <ol>
<?
    $date = $_SESSION['stage1']['date'];
    $location = mysql_esc($_SESSION['stage1']['location']);
    $maxseq = 0; // For adding hymns to an existing service
    if (! array_key_exists("services", $_POST)) {
        errormsg("Forgot to choose a service. Please try again.");
    }
    if ("new" == $_POST["services"])
    {
        $dayname = mysql_esc($_SESSION['stage1']['liturgical_name']);
        $rite = mysql_esc($_SESSION['stage1']['rite']);
        $sql = "INSERT INTO days (caldate, name, rite)
            VALUES ('${date}', '${dayname}', '${rite}')";
        mysql_query($sql) or die(mysql_error());
        // Grab the pkey of the newly inserted row.
        $sql = "SELECT LAST_INSERT_ID()";
        $result = mysql_query($sql) or die(mysql_error());
        $row = mysql_fetch_row($result);
        $serviceid = $row[0];
        ?><li>Saved a new service on <?=$date?> for <?=$dayname?>.</li>
        <?
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
    foreach ($_POST as $key => $value)
    {
        if (preg_match("/(${altbooks})_(\d+)/", $key, $matches))
        {
            $hymns[] = array($matches[1], $matches[2], $value);
        }
    }
    // Insert each hymn
    foreach ($hymns as $ahymn)
    {
        $h = mysql_esc_array($ahymn);
        // Check to see if the hymn is already entered.
        $sql = "INSERT INTO names (book, number, title)
            VALUES ('${h[0]}', '${h[1]}', '${h[2]}')";
        if (mysql_query($sql))
        {
            ?><li>Saved name '<?=$h[2]?>' for <?="${h[0]} ${h[1]}"?>.</li>
            <?
        } else {
            $sql = "UPDATE names SET title='${h[2]}'
                WHERE book='${h[0]}' AND number='${h[1]}'";
            mysql_query($sql) or die(mysql_error());
            if (mysql_affected_rows())
            {
                ?><li>Updated name '<?=$h[2]?>' for <?="${h[0]} ${h[1]}"?>.</li>
            <?
            } else {
                ?><li>Title for hymn <?="${h[0]} ${h[1]}"?> unchanged.</li>
            <?
            }
        }
    }
    //// Enter hymns and location on selected date
    $hymns = entered_hymns($_SESSION['stage1']);
    $sqlhymns = array();
    $saved = array();
    foreach ($hymns as $sequence => $ahymn)
    {
        if (! $ahymn['number']) continue;
        $hymn = mysql_esc_array($ahymn);
        $realsequence = $sequence + $maxseq;
        $sqlhymns[] = "('${serviceid}', '${location}', '${hymn['book']}',
            '${hymn['number']}', '${hymn['note']}', '${realsequence}')";
        $saved[] = "${ahymn['book']} ${ahymn['number']} (${hymn['note']})";
    }
    $sql = "INSERT INTO hymns (service, location, book, number, note, sequence)
        VALUES ".implode(", ", $sqlhymns);
    mysql_query($sql) or die(mysql_error());
    ?><li>Saved hymns :
        <ol><li><?=implode("</li><li>", $saved)?></li></ol>
      </li>
    </ol>
</body>
</html>
<?
unset($_SESSION['stage1']);
unset($_SESSION['stage2']);
}
?>
