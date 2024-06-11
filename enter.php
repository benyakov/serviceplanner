<?php /* Interface for entering a new service
    Copyright (C) 2023 Jesse Jacobsen

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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */
require("./init.php");
$options = getOptions();
requireAuth("index.php", 2, "Access denied.  Please log in with sufficient privileges.");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;

if (isset($_GET['date'])) {
    $date = getGET('date');
}
if (array_key_exists("date", $_POST)) {
    processFormData();
    exit(0);
} elseif (isset($_GET['sethymntitle'])) {
    setHymnTitle();
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Entry Form: ${this_script}")?>
<body>
    <script type="text/javascript">
    function updateFromDate(dateitem) {
        var dateval = $(dateitem).val();
        if (dateval) {
            if (Modernizr.inputtypes.date
                || dateval.match(/\d{4}-\d{1,2}-\d{1,2}/))
            {
                var dvparts = dateval.split("-");
                dateval = dvparts[1]+'/'+dvparts[2]+'/'+dvparts[0];
            }
            getDayFor(dateval, $("#liturgicalname"));
            updateExisting(dateval);
            updateBlocksAvailable(dateval);
        }
    }
    function setupBlockInfo(blockval) {
        var xhr = $.getJSON("block.php",
            { "get": "blockitems",
            "id": blockval,
            "day": $("#liturgicalname").val() },
            function(result) {
                $("#block-show").html("");
                for (var key in result) {
                    $("#block-show").append("<option value=\""+key+"\">"+key+"</option>");
                }
                sessionStorage.setItem("blockValues", JSON.stringify(result));
                $("#block-show").change(function() {
                    if ("None" != $(this).val()) updateBlockInfo();
                    else $("#block-info").hide();
                }).change();
            });
            $("#block-show-div").show();
    }
    function updateBlockInfo() {
        var blockvalues = $.parseJSON(sessionStorage.getItem("blockValues"));
        $("#block-info").html("<div>"+
            blockvalues[$('#block-show').val()]+
            "</div>")
        .show();
    }
    $(document).ready(function() {
        $("#existing-services").hide();
        $("#block-info").hide();
        $("#block-show-div").hide();
        $("#block").change(function(){
            if ("None" != $(this).val()) {
                setupBlockInfo($(this).val());
            } else {
                $("#block-info").hide();
                $("#block-show-div").hide();
            }
        });
        $("#date").keyup(function(){
            $(this).doTimeout('update-existing', 500, function() {
                updateFromDate(this);
            })})
            .change(function() {
                if (! isNaN(Date.parse($(this).val()))) {
                    var input = new Date($(this).val());
                    if (! Modernizr.inputtypes.date) {
                        $(this).val(zeroPad(input.getMonth()+1, 2)+'/'+
                                    zeroPad(input.getDate(), 2)+'/'+
                                    input.getFullYear());
                    }
                    updateFromDate(this);
                } else {
                    setMessage("Date not recognized.");
                    $(this).focus();
                }
            })
            .focus();
        if (! Modernizr.inputtypes.date) {
            $("#date").datepicker({showOn:"button", numberOfMonths: [1,2],
                stepMonths: 2, onClose: function() {
                    $("#occurrence").focus();
                }});
        }
        updateFromDate($("#date"));
        $(".edit-number").change(function(evt){
            if (evt.which != 9 &&
                evt.which != 17) {
                $(this).doTimeout('fetch-hymn-title', 250, fetchHymnTitle);
                var listingord = $(this).attr("data-hymn");
                $("#savetitle_"+listingord).hide();
            }
        });
            //.focusout(fetchHymnTitle);  // Results in second request
        $("#addHymn").click(function(evt) {
            evt.preventDefault();
            addHymn();
        });
        $(".edit-title").change(function() {
            var listingord = $(this).attr("data-hymn");
            $(this).removeClass("data-saved");
            $("#savetitle_"+listingord).show();
        });
        $(".save-title").click(function(evt) {
            evt.preventDefault();
            var listingord = $(this).attr("data-hymn");
            var xhr = $.getJSON("<?$this_script?>",
                    { sethymntitle: $("#title_"+listingord).val(),
                    number: $("#number_"+listingord).val(),
                    book: $("#book_"+listingord).val() },
                    function(result) {
                        if (result[0]) {
                            $("#title_"+listingord).addClass("data-saved");
                            $("#savetitle_"+listingord).hide();
                        }
                        setMessage(result[1]);
                    });
        });
        showJsOnly();
    });
    auth = "<?=authId()?>";
    </script>
    <? pageHeader();
    siteTabs("modify"); ?>
    <div id="content-container">
    <header>
    <h1>Service Entry Form</h1>
    <p class="explanation">This form allows you to enter a new service,
    or to add hymns to an existing service.
    The available hymnbooks can be configured on the Housekeeping page.
    You can use <a href="http://michelf.com/projects/php-markdown/concepts/">Markdown syntax</a>
    to format the service notes.</p>
    </header>
    <form action="<?=$protocol?>://<?=$this_script?>" method="post">
    <section id="existing-services">
    </section>
    <section id="service-items">
    <ul>
    <li>
        <label for="date">Date:</label><br>
        <input tabindex="1" type="date" id="date"
            name="date" value="" autofocus required>
    </li>
    <li>
        <label for="occurrence">Occurrence:</label><br>
        <input tabindex="2" type="text" required
        id="occurrence" name="occurrence" placeholder="Required"
        value="<?=$options->getDefault("", "defaultoccurrence")?>" >
    </li>
    <li>
        <label for="liturgicalname">Liturgical Name:</label><br>
        <input tabindex="26" type="text"
            id="liturgicalname" name="liturgicalname" value="">
    </li>
    <li>
        <label for="rite">Rite or Order:</label><br>
        <input tabindex="27" type="text" id="rite" name="rite" value="">
    </li>
    <li class="vcenter">
        <label for="servicenotes">Service Notes:</label><br>
        <textarea tabindex="29" id="servicenotes"
            name="servicenotes"></textarea>
    </li>
    <li class="block-info">
        <table><tr><td>
        <label for="block">Block Plan:</label><br>
        <select tabindex="30" id="block" name="block">
            <option value="None" selected>None</option>
        </select>
        <div id="block-show-div">
        <label for="block-show">Show:</label>
        <select tabindex="31" id="block-show">
            <option value="None" selected>None</option>
        </select>
        </div>
        </td><td>
        <div id="block-info"> </div>
        </td></tr></table>
    </li>
    </ul>
    </section>
    <h2>Hymns to Enter (Book, Number, Note)</h2>
    <input type="checkbox" id="xref-names" name="xref-names" tabindex="40">
    <label for="xref-names">Attempt to provide unknown hymn titles using the
cross-reference table.</label>
    <ol id="hymnentries">
<?  $tabsperhymnline = 6;
    for ($i=1, $hymncount=$options->get('hymncount'); $i<=$hymncount; $i++) {
        $tabindex = $i*$tabsperhymnline + 51; ?>
    <li class="<?= $i%2==0?"even":"odd" ?>">
        <select tabindex="<?=$tabindex?>" id="book_<?=$i?>" name="book_<?=$i?>">
        <? foreach ($options->get('hymnbooks') as $hymnbook) { ?>
            <option><?=$hymnbook?></option>
        <? } ?>
        </select>
        <input tabindex="<?=$tabindex+1?>" data-hymn="<?=$i?>" type="number" min="0" id="number_<?=$i?>" name="number_<?=$i?>" value="" class="edit-number" placeholder="<#>">
        <input tabindex="<?=$tabindex+2?>" type="text" id="note_<?=$i?>" name="note_<?=$i?>" class="edit-note" maxlength="100" value="" placeholder="<note>">
        <input tabindex="<?=$tabindex+3?>" data-hymn="<?=$i?>" type="text" id="title_<?=$i?>" name="title_<?=$i?>" class="edit-title hidden">
        <a tabindex="<?=$tabindex+4?>" href="#" data-hymn="<?=$i?>" class="hidden save-title command-link" id="savetitle_<?=$i?>">Save Title</a>
        <div id="past_<?=$i?>" class="hymn-past"></div>
    </li>
    <? } ?>
    </ol>
    <a id="addHymn" class="jsonly command-link" tabindex="200"
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
    $options = getOptions();
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $dbh->beginTransaction();
    $feedback='<ol>';
    $date = strftime("%Y-%m-%d", strtotime($_POST['date']));
    $existingKey = array_slice(preg_grep('/^existing_/', array_keys($_POST)), -1);
    if ($existingKey) {
        preg_match('/existing_(\d+)/', $existingKey[0], $matches);
        $serviceid = $matches[1];
    } else {
        $serviceid = false;
    }
    if (! $serviceid) { // Create a new service
        $q = $dbh->prepare("INSERT INTO `{$dbp}days`
            (caldate, name, rite, servicenotes, block)
            VALUES (:date, :dayname, :rite, :servicenotes, :block)");
        $q->bindParam(':date', $date);
        $q->bindParam(':dayname', $_POST['liturgicalname']);
        $q->bindParam(':rite', $_POST['rite']);
        $q->bindParam(':servicenotes', $_POST['servicenotes']);
        if (is_numeric($_POST['block'])) {
            $q->bindParam(':block', $_POST['block']);
        } else {
            $q->bindValue(':block', NULL);
        }
        $q->execute() or die(end($q->errorInfo()));
        // Grab the pkey of the newly inserted row.
        $q = $dbh->prepare("SELECT LAST_INSERT_ID()");
        $q->execute() or die(end($q->errorInfo()));
        $row = $q->fetch();
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
    $hymns = array_filter($hymns, function ($s) { return ($s["number"] or $s["number"]==0); });
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
            $q->execute() or die(".");
            if ($q->rowCount()) {
                $feedback .="<li>Updated name '{$h["title"]}' for {$h["book"]} {$h["number"]}.</li>";
            } else {
                $feedback .="<li>Title for hymn \"{$h["book"]} {$h["number"]}\" unchanged.</li>";
            }
        }
    }
    //// Enter hymns and occurrence on selected date
    if ($hymns) {
        $sqlhymns = array();
        $saved = array();
        $q = $dbh->prepare("SELECT MAX(`sequence`) FROM `{$dbp}hymns`
            WHERE `service`=:serviceid
            AND `occurrence`=:occurrence");
        $q->bindParam(':occurrence', $_POST['occurrence']);
        $q->bindParam(':serviceid', $serviceid);
        $q->execute() or die(".");
        $sequenceMax = (int) array_slice($q->fetch(), -1);
        $q = $dbh->prepare("INSERT INTO `{$dbp}hymns`
            (service, occurrence, book, number, note, sequence)
            VALUES (:service, :occurrence, :book, :number, :note, :sequence)");
        foreach ($hymns as $sequence => $ahymn) {
            if (! is_numeric($ahymn['number'])) continue;
            $realsequence = $sequence + $sequenceMax;
            $q->bindValue(":service", $serviceid);
            $q->bindValue(":occurrence", $_POST['occurrence']);
            $q->bindValue(":book", $ahymn['book']);
            $q->bindValue(":number", $ahymn['number']);
            $q->bindValue(":note", $ahymn['note']);
            $q->bindValue(":sequence", $realsequence);
            $q->execute() or die($q->queryString);
            $saved[] = "{$ahymn['book']} {$ahymn['number']}";
        }
        if ($q->rowCount()) {
            $feedback .="<li>Saved hymns: <ol><li>" . implode("</li><li>", $saved) . "</li></ol></li></ol>\n";
        }
    }
    //// Set up automatic flags, if a flagestalt is set.
    if ($flagestalt = $options->getDefault(0, "flagestalt")) {
        $q = $dbh->prepare("INSERT INTO `{$dbh->getPrefix()}service_flags`
            (`service`, `occurrence`, `flag`, `value`, `uid`)
            VALUES (:service, :occurrence, :flag, :value, :uid)");
        $q->bindValue(":service", $serviceid);
        $q->bindValue(":occurrence", $_POST['occurrence']);
        $qf = $dbh->prepare("SELECT flag, value, uid
            FROM `{$dbh->getPrefix()}service_flags`
            WHERE service=:service AND occurrence=:occurrence");
        $qf->bindValue(":service", $flagestalt['service']);
        $qf->bindValue(":occurrence", $flagestalt['occurrence']);
        $qf->execute() or die($q->queryString);
        $flagtext = $flagval = $flaguid = 0;
        $q->bindParam(":flag", $flagtext);
        $q->bindParam(":value", $flagval);
        $q->bindParam(":uid", $flaguid);
        while ($flag_contents = $qf->fetch(PDO::FETCH_ASSOC)) {
            $flagtext = $flag_contents["flag"];
            $flagval = $flag_contents["value"];
            $flaguid = $flag_contents["uid"];
            $q->execute() or die($q->errorString);
        }
        unset($q);
    }
    $dbh->commit();
    setMessage($feedback);
    header("Location: modify.php");
}
function setHymnTitle() {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $dbh->beginTransaction();
    $q = $dbh->prepare("SELECT `title`, `number` FROM `{$dbp}names`
        WHERE `book` = :book AND `number` = :number");
    $q->bindValue(':book', getGET('book'));
    $q->bindValue(':number', getGET('number'));
    $q->execute() or die(end($q->errorInfo()));
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $oldtitle = $row['title'];
    if ($row && $row['number']) {
        //Update
        $q = $dbh->prepare("UPDATE `{$dbp}names` SET `title` = :title
            WHERE `book` = :book AND `number` = :number");
        $message = "Hymn title for {$_GET['book']} {$_GET['number']} updated "
            . " from \"{$oldtitle}\" to \"{$_GET["sethymntitle"]}\".";
    } else {
        //Insert
        $q = $dbh->prepare("INSERT INTO `{$dbp}names`
            (book, number, title)
            VALUES (:book, :number, :title)");
        $message = "Hymn title for {$_GET['book']} {$_GET['number']} set "
            . " to \"{$_GET["sethymntitle"]}\".";
    }
    $q->bindValue(':book', getGET('book'));
    $q->bindValue(':number', getGET('number'));
    $q->bindValue(':title', getGET('sethymntitle'));
    if ($q->execute()) {
        $dbh->commit();
        echo json_encode(array(true, $message));
    } else {
        $dbh->rollBack();
        echo json_encode(array(false, end($q->errorInfo())));
    }
}

