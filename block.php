<? /* Interface for maintaining block plans
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
require("init.php");
$auth = auth();

/* If the given string is an array key, return the value as an html value
 * attribute.  Otherwise, an empty string.
 */
function ifVal($ary, $key) {
    if (array_key_exists($key, $ary)) return "value=\"{$ary[$key]}\"";
    else return "";
}

/* If the given value is in the array at the given key, return "selected".
 * Otherwise, an empty string.
 */
function ifSel($ary, $key, $val) {
    if (array_key_exists($key, $ary) && $ary[$key] == $val) return "selected";
    else return "";
}

/* Display the form for a block plan, including values if provided.
 */
function blockPlanForm($vals=array()) {
    if ($vals['l1lect'] == 'custom') $vals['l1custom'] = $vals['l1series'];
    else $vals['l1custom'] = "";
    if ($vals['l2lect'] == 'custom') $vals['l2custom'] = $vals['l2series'];
    else $vals['l2custom'] = "";
    if ($vals['golect'] == 'custom') $vals['gocustom'] = $vals['goseries'];
    else $vals['gocustom'] = "";
    if ($vals['pslect'] == 'custom') $vals['pscustom'] = $vals['psseries'];
    else $vals['pscustom'] = "";
?>
    <form id="block-plan-form" action="block.php" method="post">
    <input type="hidden" name="id" value="<?=$vals['id']?>">
    <label for="label">Label</label>
    <input type="text" id="label" name="label" required <?=ifVal($vals, 'label')?>><br>
    <section id="block-dates">
    <label for="startdate">Start</label>
    <input type="date" id="startdate" name="startdate" <?=ifVal($vals, 'blockstart')?> required>
    <div id="startday"><?=implode(", ", daysForDate($vals['blockstart']))?></div><br>
    <label for="enddate">End</label>
    <input type="date" id="enddate" name="enddate" <?=ifVal($vals, 'blockend')?> required>
    <div id="endday"><?=implode(", ", daysForDate($vals['blockend']))?></div><br>
    <div id="overlap-notice"></div>
    </section>
    <section id="block-series">
    <table>
    <tr><td></td><th>Lectionary</th><th>Series</th><th>Custom Readings</th></tr>
    <tr><td><label>Lesson 1</label></td>
    <td><input name="l1lect" id="l1lect" value="<?=$vals['l1lect']?>"></td>
    <td><input name="l1series" id="l1series" value="<?=$vals['l1series']?>"></td>
    <td><input name="l1custom" id="l1custom" value="<?=$vals['l1custom']?>"></td></tr>
    <tr><td><label>Lesson 2</label></td>
    <td><input name="l2lect" id="l2lect" value="<?=$vals['l2lect']?>"></td>
    <td><input name="l2series" id="l2series" value="<?=$vals['l2series']?>"></td>
    <td><input name="l2custom" id="l2custom" value="<?=$vals['l2custom']?>"></td></tr>
    <tr><td><label>Gospel</label></td>
    <td><input name="golect" id="golect" value="<?=$vals['golect']?>"></td>
    <td><input name="goseries" id="goseries" value="<?=$vals['goseries']?>"></td>
    <td><input name="gocustom" id="gocustom" value="<?=$vals['gocustom']?>"></td></tr>
    <tr><td><label>Psalm</label></td>
    <td><input name="pslect" id="pslect" value="<?=$vals['pslect']?>"></td>
    <td><input name="psseries" id="psseries" value="<?=$vals['psseries']?>"></td>
    <td><input name="pscustom" id="pscustom" value="<?=$vals['pscustom']?>"></td></tr>
    <tr><td><label Collect</label></td>
    <td><input name="colect" id="colect" value="<?=$vals['colect']?>"></td>
    <td><input name="coclass" id="coclass" value="<?=$vals['coclass']?>"></td>
    </tr>
    </section>
    <label for="notes">Block Notes</label><br>
    <textarea name="notes" id="notes"><?=$vals['notes']?$vals['notes']:''?></textarea><br>
    <button type="submit">Submit</button>
    <button type="reset">Reset</button>
    </form>
<?
}

/* block.php with $_POST
 * Process the submitted block form
 */
if ($_POST['label']) {
    if (! $auth) {
        setMessage("Access denied.  Please log in.");
        header("location: index.php");
        exit(0);
    }
    $_POST['startdate'] = date('Y-m-d', strtotime($_POST['startdate']));
    $_POST['enddate'] = date('Y-m-d', strtotime($_POST['enddate']));
    if ("custom" == $_POST['l1lect']) $_POST['l1series'] = $_POST['l1custom'];
    if ("custom" == $_POST['l2lect']) $_POST['l2series'] = $_POST['l2custom'];
    if ("custom" == $_POST['golect']) $_POST['goseries'] = $_POST['gocustom'];
    if ("custom" == $_POST['pslect']) $_POST['psseries'] = $_POST['pscustom'];
    $binding = array($_POST['label'], $_POST['startdate'],
        $_POST['enddate'], $_POST['notes'], $_POST['l1lect'],
        $_POST['l1series'], $_POST['l2lect'], $_POST['l2series'],
        $_POST['golect'], $_POST['goseries'], $_POST['pslect'],
        $_POST['psseries'], $_POST['colect'], $_POST['psseries']);
    if ($_POST['id']) { // Update existing record
        array_push($binding, $_POST['id']);
        $q = $dbh->prepare("UPDATE `{$dbp}blocks`
            SET label = ?, blockstart = ?, blockend = ?, notes = ?,
            l1lect = ?, l1series = ?, l2lect = ?, l2series = ?,
            golect = ?, goseries = ?, pslect = ?, psseries = ?,
            colect = ?, coclass = ?
            WHERE id = ?");
    } else { // Create new record
        $q = $dbh->prepare("INSERT INTO `{$dbp}blocks`
            (label, blockstart, blockend, notes, l1lect, l1series, l2lect,
            l2series, golect, goseries, pslect, psseries, colect, coclass)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    }
    if (! $q->execute($binding)) {
        setMessage("Problem saving block:" . array_pop($q->errorInfo()));
    }
}

/* block.php?action=new
 * Show an empty block edit form
 */
if ($_GET['action'] == "new") {
    if (! $auth) {
        setMessage("Access denied.  Please log in.");
        header("location:index.php");
        exit(0);
    }
    blockPlanForm();
    exit(0);
}

/* block.php?action=edit&id=N
 * Edit the block indicated by the id
 */
if ($_GET['action'] == "edit" && $_GET['id']) {
    if (! $auth) {
        setMessage("Access denied.  Please log in.");
        header("location:index.php");
        exit(0);
    }
    $q = $dbh->prepare("SELECT DATE_FORMAT(blockstart, '%c/%e/%Y') AS blockstart,
        DATE_FORMAT(blockend, '%c/%e/%Y') AS blockend, label, notes, l1lect,
        l1series, l2lect, l2series, golect, goseries, pslect, psseries,
        colect, coclass, id FROM `{$dbp}blocks`
    WHERE id = ?");
    if ($q->execute(array($_GET['id'])) && $row = $q->fetch(PDO::FETCH_ASSOC)) {
        blockPlanForm($row);
    } else {
        echo array_pop($q->errorInfo());
    }
    exit(0);
}

/* block.php?delete=N
 * Delete the block with id N
 */
if ($_GET['delete']) {
    if (! $auth) {
        setMessage("Access denied.  Please log in.");
        header("location:index.php");
        exit(0);
    }
    $q = $dbh->prepare("DELETE FROM `{$dbp}blocks` WHERE id = ?");
    if ($q->execute(array($_GET['delete']))) {
        setMessage($q->rowCount() . " blocks deleted.");
    } else {
        setMessage("Problem deleting block: ".array_pop($q->errorInfo()));
    }
}

/* block.php?available=date
 * Return a json list of blocks available for the date given
 */
if ($_GET['available']) {
    $q = $dbh->prepare("SELECT id, label FROM `{$dbp}blocks`
        WHERE blockstart <= ? AND blockend >= ?");
    if ($q->execute(array($_GET['available'], $_GET['available']))) {
        $rv = array();
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $rv[$row['id']] = $row['label'];
        }
        echo json_encode(array(true, $rv));
    } else {
        echo json_encode(array(false, array_pop($q->errorInfo())));
    }
    exit(0);
}

/* block.php?overlapstart=date&overlapend=date
 * Return whether the dates overlap an existing block
 */
if ($_GET['overlapstart'] && $_GET['overlapend']) {
    $q = $dbh->prepare("SELECT label FROM `{$dbp}blocks`
        WHERE (blockstart < :date1 AND blockend > :date1)
        OR (blockstart < :date2 AND blockend > :date2)
        OR (:date1 < blockstart AND :date2 > blockend)");
    $q->bindParam(":date1", $_GET['overlapstart']);
    $q->bindParam(":date2", $_GET['overlapend']);
    if ($q->execute()) {
        $rv = array();
        while ($label = $q->fetchColumn(0)) {
            array_push($rv, $label);
        }
        echo json_encode(array((bool)count($rv),
            'Overlaps "' . implode('", "', $rv) . '"'));
    } else {
        echo json_encode(array(false, array_pop($q->errorInfo())));
    }
    exit(0);
}

// Display the block planning table
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
}

?><!DOCTYPE html>
<html lang="en">
<?=html_head("Block Planning")?>
<body>
    <script type="text/javascript">
    function checkOverlap() {
        var olstart = dateValToSQL($("#startdate").val());
        var olend = dateValToSQL($("#enddate").val());
        $.get("block.php", {overlapstart: olstart, overlapend: olend},
            function(rv) {
                rv = eval(rv);
                if (rv[0]) {
                    $("#overlap-notice").html(rv[1]);
                } else {
                    $("#overlap-notice").html("");
                }
            });
    }
    function setupEntryDialog() {
        $('#startdate').unbind('change')
            .bind('change', function() {
                getDayFor($(this).val(), $("#startday"));
                var thisDate = new Date($(this).val());
                $("#enddate").attr("min", thisDate.toISOString().split("T")[0]);
                if ($("#startdate").val() && $("#enddate").val()) {
                    checkOverlap();
                }
            })
            .datepicker({showOn:"button", numberOfMonths:[2,2],
                stepMonths: 4});
        $('#enddate').unbind('change')
            .bind('change', function() {
                getDayFor($(this).val(), $("#endday"));
                var thisDate = new Date($(this).val());
                $("#startdate").attr("max", thisDate.toISOString().split("T")[0]);
                if ($("#startdate").val() && $("#enddate").val()) {
                    checkOverlap();
                }
            })
            .datepicker({showOn:"button", numberOfMonths:[2,2],
                stepMonths: 4});
        checkCustomPsalm();
        $('#pscustom').change(function() {
            checkCustomPsalm();
        });
        checkCustomL1();
        $('#l1custom').change(function() {
            checkCustomL1();
        });
        checkCustomL2();
        $('#l2custom').change(function() {
            checkCustomL2();
        });
        checkCustomGo();
        $('#gocustom').change(function() {
            checkCustomGo();
        });
        checkHistoricL1();
        checkHistoricL2();
        checkHistoricGo();
        checkHistoricPs();
        $('#l1lect').change(function() {
            checkHistoricL1();
        }
        $('#l2lect').change(function() {
            checkHistoricL2();
        }
        $('#golect').change(function() {
            checkHistoricGo();
        }
        $('#pslect').change(function() {
            checkHistoricPs();
        }
    }
    function checkCustomL1() {
        if ($('#l1custom').val()) {
            $('#l1series').attr('disabled', true)
                .hide();
            $('#l1lect').val('custom');
        } else {
            $('#l1series').val('disabled', false)
                .show();
        }
    }
    function checkHistoricL1() {
        if ("historic" == $('#l1lect').val()) {
            $('#l1series').attr('disabled', false)
                .show();
            $('#l1custom').val('')
        }
    }
    function checkCustomL2() {
        if ($('#l2custom').val()) {
            $('#l2series').attr('disabled', true)
                .hide();
            $('#l2lect').val('custom');
        } else {
            $('#l2series').val('disabled', false)
                .show();
        }
    }
    function checkHistoricl2() {
        if ("historic" == $('#l2lect').val()) {
            $('#l2series').attr('disabled', false)
                .show();
            $('#l2custom').val('')
        }
    }
    function checkCustomGo() {
        if ($('#gocustom').val()) {
            $('#goseries').attr('disabled', true)
                .hide();
            $('#golect').val('custom');
        } else {
            $('#goseries').val('disabled', false)
                .show();
        }
    }
    function checkHistoricgo() {
        if ("historic" == $('#golect').val()) {
            $('#goseries').attr('disabled', false)
                .show();
            $('#gocustom').val('')
        }
    }
    function checkCustomPsalm() {
        if ($('#pscustom').val()) {
            $('#psseries').attr('disabled', true)
                .hide();
            $('#pslect').val('custom');
        } else {
            $('#psseries').val('disabled', false)
                .show();
        }
    }
    function checkHistoricps() {
        if ("historic" == $('#pslect').val()) {
            $('#psseries').attr('disabled', false)
                .show();
            $('#pscustom').val('')
        }
    }
    $(document).ready(function() {
        $("#new-block").click(function(evt) {
            evt.preventDefault();
            $("#dialog").load(encodeURI("block.php?action=new"), function() {
                $("#dialog").dialog({modal: true,
                            position: "center",
                            title: "New Block Plan",
                            width: $(window).width()*0.7,
                            maxHeight: $(window).height()*0.7,
                            open: function() { setupEntryDialog(); },
                            close: function() { $("#dialog").empty(); }});
            });
        });
        $(".edit").click(function(evt) {
            evt.preventDefault();
            $("#dialog").load(
                encodeURI("block.php?action=edit&id="+$(this).attr("data-id")),
                function() {
                    $("#dialog").dialog({modal: true,
                                position: "center",
                                title: "Edit Block Plan",
                                width: $(window).width()*0.7,
                                maxHeight: $(window).height()*0.7,
                                open: function() { setupEntryDialog(); },
                                close: function() { $('#dialog').empty(); }});
            });
        });
        $(".delete").click(function(evt) {
            evt.preventDefault();
            if (confirm("Delete '"+$(this).attr("data-label")+"'?")) {
                location.replace("block.php?delete="+$(this).attr("data-id"));
            }
        });
    });
    </script>
    <header>
    <?=getLoginForm()?>
    <?=getUserActions()?>
    <? showMessage(); ?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <div class="quicklinks"><a href="block.php?action=new" title="New Block" id="new-block">New Block</a></div>
    <h1>Block Plans</h1>
    <p class="explanation">This page lists the block plans you have created.  A
block plan allows you to plan a series of services that may share elements in
common.  Certain common elements will be shown when services are listed, or
when new services are created.  Services may be assigned to an existing
applicable block plan when they are created or edited.</p>
    <table id="block-listing">
    <?
$q = $dbh->prepare("SELECT DATE_FORMAT(blockstart, '%c/%e/%Y') AS blockstart,
    DATE_FORMAT(blockend, '%c/%e/%Y') AS blockend, label, notes, oldtestament,
    epistle, gospel, psalm, collect, id FROM {$dbp}blocks
    ORDER BY blockstart, blockend");
if ($q->execute()) {
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) { ?>
    <tr class="heading">
        <td colspan="2"><?=$row['blockstart']?> to <?=$row['blockend']?></td>
        <td colspan="3"><?=$row['label']?>
        <div class="quicklinks">[ <a title="edit" href="" data-id="<?=$row['id']?>" class="edit">Edit</a>
        | <a title="delete" href="" data-label="<?=$row['label']?>" data-id="<?=$row['id']?>" class="delete">Delete</a> ]</div></td></tr>
    <tr><td class="otcell"><b>OT:</b> <?=ordinal($row['oldtestament'])?></td>
        <td class="epcell"><b>Epistle:</b> <?=ordinal($row['epistle'])?></td>
        <td class="gocell"><b>Gospel:</b> <?=ordinal($row['gospel'])?></td>
        <td class="pscell"><b>Psalm:</b> <?=ordinal($row['psalm'])?></td>
        <td class="cocell"><b>Collect:</b> <?=ordinal($row['collect'])?></td></tr>
    <tr><td colspan="5"><?=translate_markup($row['notes'])?></td></tr>
<? }
} else echo "Problem getting blocks: " . array_pop($q->errorInfo()); ?>
    </table>
    </div>
    <div id="dialog"></div>
</body>
</html>
