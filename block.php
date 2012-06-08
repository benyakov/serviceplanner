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
    if ($vals['oldtestament'] && is_numeric($vals['oldtestament']))
        $vals['otcustom'] = "";
    else $vals['otcustom'] = $vals['oldtestament'];
    if ($vals['epistle'] && is_numeric($vals['epistle']))
        $vals['epcustom'] = "";
    else $vals['epcustom'] = $vals['epistle'];
    if ($vals['gospel'] && is_numeric($vals['gospel']))
        $vals['gocustom'] = "";
    else $vals['gocustom'] = $vals['gospel'];
    if ($vals['psalm'] && is_numeric($vals['psalm']))
        $vals['pscustom'] = "";
    else $vals['pscustom'] = $vals['psalm'];
    if ($vals['collect'] && is_numeric($vals['collect']))
        $vals['cocustom'] = "";
    else $vals['cocustom'] = $vals['collect'];
?>
    <form id="block-plan-form" action="block.php" method="post">
    <input type="hidden" name="id" value="<?=$vals['id']?>">
    <label for="label">Label</label>
    <input type="text" id="label" name="label" required><br>
    <section id="block-dates">
    <label for="startdate">Start</label>
    <input type="date" id="startdate" name="startdate" <?=ifVal($vals, 'startdate')?> required>
    <div id="startday"></div><br>
    <label for="enddate">End</label>
    <input type="date" id="enddate" name="enddate" <?=ifVal($vals, 'enddate')?> required>
    <div id="endday"></div><br>
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
        <option value="Custom" <?=ifSel($vals, 'psalm', 'custom')?>>Custom</option>
    </select>
    <label for="pscustom">Custom</label>
    <input type="text" name="pscustom" id="pscustom"
    <?=disableUnless($vals['psalm'], 'custom')?> <?=ifVal($vals, 'pscustom')?>><br>
    <label for="collect">Collect Series</label>
    <select name="collect" id="collect">
        <option value="1" <?=ifSel($vals, 'collect', 1)?>>First</option>
        <option value="2" <?=ifSel($vals, 'collect', 2)?>>Second</option>
        <option value="3" <?=ifSel($vals, 'collect', 3)?>>Third</option>
        <option value="Custom" <?=ifSel($vals, 'collect', 'custom')?>>Custom</option>
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

/* block?action=new
 * Show an empty block edit form
 */
if ($_GET['action'] == "new") {
    if (! $auth) {
        echo "Access denied.  Please log in.";
        exit(0);
    }
    blockPlanForm();
    exit(0);
}

/* block?action=edit&id=N
 * Edit the block indicated by the id
 */
if ($_GET['action'] == "edit" && $_GET['id']) {
    if (! $auth) {
        echo "Access denied.  Please log in.";
        exit(0);
    }
    $q = $dbh->query("SELECT blockstart, blockend, label, notes, oldtestament,
    epistle, gospel, psalm, collect, id FROM blocks
    WHERE id = ?");
    if ($q->execute($_GET['id']) && $row = $q->fetch(PDO::FETCH_ASSOC)) {
        blockPlanForm($row);
        exit(0);
    } else {
        echo array_pop($q->errorInfo());
    }
}

/* block.php?overlapstart=date&overlapend=date
 * Return whether the dates overlap an existing block
 */
if ($_GET['overlapstart'] && $_GET['overlapend']) {
    $q = $dbh->prepare("SELECT 1 FROM `{$dbp}blocks`
        WHERE (blockstart < :date1 AND blockend > :date1)
        OR (blockstart < :date2 AND blockend > :date2)");
    $q->bindParam(":date1", $_GET['overlapstart']);
    $q->bindParam(":date2", $_GET['overlapend']);
    if ($q->execute()) {
        if ($q->fetch()) {
            // Don't go ahead, there is an overlap
            echo json_encode(false);
        } else {
            // Go ahead, no overlap
            echo json_encode(true);
        }
    } else {
        echo array_pop($q->errorInfo());
    }
    exit(0);
}

// Display the block planning table
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
}

$q = $dbh->prepare("SELECT blockstart, blockend, label, notes, oldtestament,
    epistle, gospel, psalm, collect FROM blocks
    ORDER BY (blockstart, blockend)");
?><!DOCTYPE html>
<html lang="en">
<?=html_head("Block Planning")?>
<body>
    <script type="text/javascript">
    function checkOverlap(evt) {
        evt.preventDefault();
        $.get("block.php", {overlapstart: $("#startdate").val(),
            overlapend: $("#enddate").val()},
            function(rv) {
                rv = eval(rv);
                if (rv[0]) {
                    $("#block-plan-form").submit();
                } else {
                    setMessage(rv[1]);
                }
            });
    }
    function setupEntryDialog() {
        $('#startdate').datepicker({showOn:"button", numberOfMonths:[2,2],
            stepMonths: 4})
            .change(function() {
                getDayFor($(this).val(), $("#startday"));
        });
        $('#enddate').change(function() {
            getDayFor($(this).val(), $("#endday"));
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
        $('#block-plan-form').submit(checkOverlap);
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
    });
    </script>
    <header>
    <?=getLoginForm()?>
    <?=getUserActions()?>
    <? showMessage(); ?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <div id="quicklinks"><a href="block.php?action=new" title="New Block" id="new-block">New Block</a></div>
    <h1>Block Planning Records</h1>
    <table id="block-listing">
    <tr><th>Start</th><th>End</th><th colspan="2">Label</th></tr>
    <tr><th>OT</th><th>Epistle</th><th>Gospel</th></th><th>Psalm</th><th>Collect</th></tr>
    <tr><th colspan="5">Notes</th>
    <?
if ($q->execute()) {
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) { ?>
    <tr class="heading"><td><?=$row['blockstart']?></td>
        <td><?=$row['blockend']?></td>
        <td><?=$row['label']?></td>
        <td><a title="edit" href="" data-id="<?=$row['id']?>" class="edit">Edit</a>
        <a title="delete" href="" data-id="<?=$row['id']?>" class="delete">Delete</a></td></tr>
    <tr><td><?=$row['oldtestament']?></td><td><?=$row['epistle']?></td>
        <td><?=$row['gospel']?></td><td><?=$row['psalm']?></td>
        <td><?=$row['collect']?></td></tr>
    <tr><th colspan="5"><?=$row['notes']?></td></tr>
<? }
} ?>
    </table>
    </div>
    <div id="dialog"></div>
</body>
</html>
