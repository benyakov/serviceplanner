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

/* If the given values are equal, return "disabled".
 * Otherwise, an empty string.
 */
function disableUnless($value, $test) {
    if ($value != $test) return "disabled";
    else return "";
}

/* Display the form for a block plan, including values if provided.
 */
function blockPlanForm($vals=array()) {
    if ($vals['oldtestament'])
        if (is_numeric($vals['oldtestament']))
            $vals['otcustom'] = "";
        else {
            $vals['otcustom'] = $vals['oldtestament'];
            $vals['oldtestament'] = "custom";
        }
    if ($vals['epistle'])
        if (is_numeric($vals['epistle']))
            $vals['epcustom'] = "";
        else {
            $vals['epcustom'] = $vals['epistle'];
            $vals['epistle'] = "custom";
        }
    if ($vals['gospel'])
        if (is_numeric($vals['gospel']))
            $vals['gocustom'] = "";
        else {
            $vals['gocustom'] = $vals['gospel'];
            $vals['gospel'] = "custom";
        }
    if ($vals['psalm'])
        if (is_numeric($vals['psalm']))
            $vals['pscustom'] = "";
        else {
            $vals['pscustom'] = $vals['psalm'];
            $vals['psalm'] = "custom";
        }
    if ($vals['collect'])
        if (is_numeric($vals['collect']))
            $vals['cocustom'] = "";
        else {
            $vals['cocustom'] = $vals['collect'];
            $vals['collect'] = "custom";
        }
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
    <label for="oldtestament">OT Series</label>
    <select name="oldtestament" id="oldtestament">
        <option value="1" <?=ifSel($vals, 'oldtestament', 1)?>>First</option>
        <option value="2" <?=ifSel($vals, 'oldtestament', 2)?>>Second</option>
        <option value="3" <?=ifSel($vals, 'oldtestament', 3)?>>Third</option>
        <option value="custom" <?=ifSel($vals, 'oldtestament', "custom")?>>Custom</option>
    </select>
    <label for="otcustom">Custom</label>
    <input type="text" name="otcustom" id="otcustom"
    <?=disableUnless($vals['oldtestament'], 'custom')?> <?=ifVal($vals, 'otcustom')?>><br>
    <label for="epistle">Epistle Series</label>
    <select name="epistle" id="epistle">
        <option value="1" <?=ifSel($vals, 'epistle', 1)?>>First</option>
        <option value="2" <?=ifSel($vals, 'epistle', 2)?>>Second</option>
        <option value="3" <?=ifSel($vals, 'epistle', 3)?>>Third</option>
        <option value="custom" <?=ifSel($vals, 'epistle', "custom")?>>Custom</option>
    </select>
    <label for="epcustom">Custom</label>
    <input type="text" name="epcustom" id="epcustom"
    <?=ifVal($vals, 'epcustom')?><?=disableUnless($vals['epistle'], 'custom')?>><br>
    <label for="gospel">Gospel Series</label>
    <select name="gospel" id="gospel">
        <option value="1" <?=ifSel($vals, 'gospel', 1)?>>First</option>
        <option value="2" <?=ifSel($vals, 'gospel', 2)?>>Second</option>
        <option value="3" <?=ifSel($vals, 'gospel', 3)?>>Third</option>
        <option value="custom" <?=ifSel($vals, 'gospel', 'custom')?>>Custom</option>
    </select>
    <label for="gocustom">Custom</label>
    <input type="text" name="gocustom" id="gocustom"
    <?=disableUnless($vals['gospel'], 'custom')?> <?=ifVal($vals, 'gocustom')?>><br>
    <label for="psalm">Psalm Series</label>
    <select name="psalm" id="psalm">
        <option value="1" <?=ifSel($vals, 'psalm', 1)?>>First</option>
        <option value="2" <?=ifSel($vals, 'psalm', 2)?>>Second</option>
        <option value="3" <?=ifSel($vals, 'psalm', 3)?>>Third</option>
        <option value="custom" <?=ifSel($vals, 'psalm', 'custom')?>>Custom</option>
    </select>
    <label for="pscustom">Custom</label>
    <input type="text" name="pscustom" id="pscustom"
    <?=disableUnless($vals['psalm'], 'custom')?> <?=ifVal($vals, 'pscustom')?>><br>
    <label for="collect">Collect Series</label>
    <select name="collect" id="collect">
        <option value="1" <?=ifSel($vals, 'collect', 1)?>>First</option>
        <option value="2" <?=ifSel($vals, 'collect', 2)?>>Second</option>
        <option value="3" <?=ifSel($vals, 'collect', 3)?>>Third</option>
        <option value="custom" <?=ifSel($vals, 'collect', 'custom')?>>Custom</option>
    </select>
    <label for="cocustom">Custom</label>
    <input type="text" name="cocustom" id="cocustom"
    <?=disableUnless($vals['collect'], 'custom')?> <?=ifVal($vals, 'cocustom')?>><br>
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
    if ("custom" == $_POST['oldtestament']) {
        $_POST['oldtestament'] = $_POST['otcustom'];
    }
    if ("custom" == $_POST['epistle']) {
        $_POST['epistle'] = $_POST['epcustom'];
    }
    if ("custom" == $_POST['gospel']) {
        $_POST['gospel'] = $_POST['gocustom'];
    }
    if ("custom" == $_POST['psalm']) {
        $_POST['psalm'] = $_POST['pscustom'];
    }
    if ("custom" == $_POST['collect']) {
        $_POST['collect'] = $_POST['cocustom'];
    }
    if ($_POST['id']) { // Update existing record
        $q = $dbh->prepare("UPDATE `{$dbp}blocks`
            SET label = ?, blockstart = ?, blockend = ?, notes = ?,
            oldtestament = ?, epistle = ?, gospel = ?, psalm = ?,
            collect = ?
            WHERE id = ?");
        $binding = array($_POST['label'], $_POST['startdate'],
            $_POST['enddate'], $_POST['notes'], $_POST['oldtestament'],
            $_POST['epistle'], $_POST['gospel'], $_POST['psalm'],
            $_POST['collect'], $_POST['id']);
    } else { // Create new record
        $q = $dbh->prepare("INSERT INTO `{$dbp}blocks`
            (label, blockstart, blockend, notes, oldtestament, epistle,
            gospel, psalm, collect)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $binding = array($_POST['label'], $_POST['startdate'],
            $_POST['enddate'], $_POST['notes'], $_POST['oldtestament'],
            $_POST['epistle'], $_POST['gospel'], $_POST['psalm'],
            $_POST['collect']);
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
    DATE_FORMAT(blockend, '%c/%e/%Y') AS blockend, label, notes, oldtestament,
    epistle, gospel, psalm, collect, id FROM `{$dbp}blocks`
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
        $('#collect').change(function() {
            if ($(this).val() == "custom") {
                $("#cocustom").attr("disabled", false);
            } else {
                $("#cocustom").attr("disabled", true);
            }
        });
        $('#psalm').change(function() {
            if ($(this).val() == "custom") {
                $("#pscustom").attr("disabled", false);
            } else {
                $("#pscustom").attr("disabled", true);
            }
        });
        $('#oldtestament').change(function() {
            if ($(this).val() == "custom") {
                $("#otcustom").attr("disabled", false);
            } else {
                $("#otcustom").attr("disabled", true);
            }
        });
        $('#epistle').change(function() {
            if ($(this).val() == "custom") {
                $("#epcustom").attr("disabled", false);
            } else {
                $("#epcustom").attr("disabled", true);
            }
        });
        $('#gospel').change(function() {
            if ($(this).val() == "custom") {
                $("#gocustom").attr("disabled", false);
            } else {
                $("#gocustom").attr("disabled", true);
            }
        });
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
                            create: function() {
                                setupEntryDialog();
                            },
                            open: function() {
                                setupEntryDialog();
                            }});
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
                                create: function() {
                                    setupEntryDialog();
                                },
                                open: function() {
                                    setupEntryDialog();
                                }});
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
