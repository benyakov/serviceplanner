<?php
require("./init.php");
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
    exit(0);
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;

if (array_key_exists("date", $_GET)) {
    $date = $_GET['date'];
}
if (array_key_exists("date", $_POST)) {
    processFormData();
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Entry Form: ${this_script}")?>
<body>
    <script type="text/javascript">
    $(document).ready(function() {
        $("#existing-services").hide();
        showJsOnly();
        $("#date").datepicker({showOn:"both"});
        $("#date").keyup(function(){
            $(this).doTimeout('update-existing', 250, updateExisting)
        })
            .change(updateExisting);
        $(".hymn-number").keyup(function(evt){
            if (evt.which != 9 &&
                evt.which != 17) {
                $(this).doTimeout('fetch-hymn-title', 250, fetchHymnTitle);
            }
        })
            .change(fetchHymnTitle);
        $("#addHymn").click(function(evt) {
            evt.preventDefault();
            addHymn();
        });
    });
    auth = "<?=authId()?>";
    </script>
    <header>
    <?=getLoginForm()?>
    <?=getUserActions()?>
    <?showMessage();?>
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
        <input tabindex="2" type="text" required
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
        <input tabindex="<?=$tabindex+3?>" type="text" id="title_<?=$i?>" name="title_<?=$i?>" class="hymn-title hidden">
        <div id="past_<?=$i?>" class="hymn-past"></div>
    </li>
    <? } ?>
    </ol>
    <a id="addHymn" class="jsonly" tabindex="200"
        href="javascript: void(0);" >Add another hymn.</a>
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
    // echo "POST:"; print_r($_POST); exit(0);
    //// Add a new service, if needed.
    global $dbh, $dbp;
    $dbh->beginTransaction();
    $feedback='<ol>';
    $date = strftime("%Y-%m-%d", strtotime($_POST['date']));
    $existingKey = array_pop(preg_grep('/^existing_/', array_keys($_POST)));
    if ($existingKey) {
        preg_match('/existing_(\d+)/', $existingKey, $matches);
        $serviceid = $matches[1];
    } else {
        $serviceid = false;
    }
    if (! $serviceid) { // Create a new service
        $q = $dbh->prepare("INSERT INTO {$dbp}days
            (caldate, name, rite, servicenotes)
            VALUES (:date, :dayname, :rite, :servicenotes)");
        $q->bindParam(':date', $date);
        $q->bindParam(':dayname', $_POST['liturgicalname']);
        $q->bindParam(':rite', $_POST['rite']);
        $q->bindParam(':servicenotes', $_POST['servicenotes']);
        $q->execute() or dieWithRollback($q, ".");
        // Grab the pkey of the newly inserted row.
        $q = $dbh->prepare("SELECT LAST_INSERT_ID()");
        $q->execute() or dieWithRollback($q, ".");
        $row = $q->fetch($result);
        $serviceid = $row[0];
        $feedback .= "<li>Saved a new service on '{$date}' for
            '{$_POST['liturgicalname']}'.</li>";
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
    foreach ($hymns as $h) {
        if (! $h['title']) { continue; }
        // Check to see if the hymn is already entered.
        $q = $dbh->prepare("INSERT INTO {$dbp}names (book, number, title)
            VALUES (:book, :number, :title)");
        $q->bindParam(":book", $h["book"]);
        $q->bindParam(":number",$h["number"]);
        $q->bindParam(":title", $h["title"]);
        if ($q->execute()) {
            $feedback .= "<li>Saved name '{$h["title"]}' for {$h["book"]} {$h["number"]}.</li>";
        } else {
            $q = $dbh->prepare("UPDATE {$dbp}names SET title=:title
                WHERE book=:book AND number=:number");
            $q->bindParam(':title', $h["title"]);
            $q->bindParam(':book', $h["book"]);
            $q->bindParam(':number', $h["number"]);
            $q->execute() or dieWithRollback($q, ".");
            if ($q->rowCount()) {
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
        $q = $dbh->prepare("SELECT MAX(`sequence`) FROM `{$dbp}hymns`
            WHERE `service`=:serviceid
            AND `location`=:location");
        $q->bindParam(':location', $_POST['location']);
        $q->bindParam(':serviceid', $serviceid);
        $q->execute() or dieWithRollback($q, ".");
        $sequenceMax = array_pop($q->fetch());
        foreach ($hymns as $sequence => $ahymn)
        {
            if (! intval($ahymn['number'])) continue;
            $realsequence = $sequence + $sequenceMax;
            $sqlhymns[] = "({$dbh->quote($serviceid)},
                {$dbh->quote($_POST['location'])},
                {$dbh->quote($ahymn['book'])},
                {$dbh->quote($ahymn['number'])},
                {$dbh->quote($ahymn['note'])}, {$realsequence})";
            $saved[] = "{$ahymn['book']} {$ahymn['number']}";
        }
        $q = $dbh->prepare("INSERT INTO `{$dbp}hymns`
            (service, location, book, number, note, sequence)
            VALUES ".implode(", ", $sqlhymns));
        $q->execute() or dieWithRollback($q, $q->queryString);
        if ($q->rowCount()) {
            $feedback .="<li>Saved hymns: <ol><li>" . implode("</li><li>", $saved) . "</li></ol></li></ol>\n";
        }
    }
    $dbh->commit();
    setMessage($feedback);
    header("Location: modify.php");
}
?>
