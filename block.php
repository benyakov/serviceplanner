<?php /* Interface for maintaining block plans
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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */


function bibleLinkOrHelp($text, $output)
{
    if ($text) return $output;
    else return "Text not found. Have you entered a single day name?";
}
require("init.php");
$auth = authLevel();

/* Show the lesson choice in the block plan display
 */
function showLesson($lectionary, $series) {
    if (preg_match("/(?i)historic/", $lectionary)) {
        $s = $series;
    } elseif ("custom" == $lectionary) {
        $lectionary = "Custom: ";
        $s = $series;
    } else {
        $s = "";
    }
    return "{$lectionary} {$s}";
}

/* If the given string is an array key, return the value as an html value
 * attribute.  Otherwise, an empty string.
 */
function ifVal($ary, $key) {
    if (array_key_exists($key, $ary)) return "value=\"{$ary[$key]}\"";
    else return "";
}

/* Display the form for a block plan, including values if provided.
 */
function blockPlanForm($vals=array()) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    if ($vals['l1lect'] == 'custom') $vals['l1custom'] = $vals['l1series'];
    else $vals['l1custom'] = "";
    if ($vals['l2lect'] == 'custom') $vals['l2custom'] = $vals['l2series'];
    else $vals['l2custom'] = "";
    if ($vals['golect'] == 'custom') $vals['gocustom'] = $vals['goseries'];
    else $vals['gocustom'] = "";
    if ($vals['pslect'] == 'custom') $vals['pscustom'] = $vals['psseries'];
    else $vals['pscustom'] = "";
    if ($vals['smlect'] == 'custom') $vals['smcustom'] = $vals['smseries'];
    else $vals['smcustom'] = "";
    $q = $dbh->prepare("SELECT lectionary FROM `{$dbp}churchyear_lessons`
        GROUP BY lectionary");
    $q->execute() or die(array_pop($q->errorInfo()));
    $lects = $q->fetchAll(PDO::FETCH_COLUMN, 0);
    array_unshift($lects, "custom");
    $series = array("first", "second", "third");
    $q = $dbh->prepare("SELECT class FROM `{$dbp}churchyear_collects`
        GROUP BY class");
    $q->execute() or die(array_pop($q->errorInfo()));
    $collect_classes = $q->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!$vals['l1lect']) $vals['l1lect'] = 'historic';
    if (!$vals['l2lect']) $vals['l2lect'] = 'historic';
    if (!$vals['golect']) $vals['golect'] = 'historic';
    if (!$vals['pslect']) $vals['pslect'] = 'historic';
    if (!$vals['colect']) $vals['colect'] = 'historic';
    if (!$vals['smlect']) $vals['smlect'] = 'historic';
    require_once("./churchyear/functions.php");
?>
    <form id="block-plan-form" action="block.php" method="post">
    <input type="hidden" name="id" value="<?=$vals['id']?>">
    <label for="label">Label</label>
    <input type="text" id="label" name="label" required <?=ifVal($vals, 'label')?>><br>
    <section id="block-dates">
    <label for="startdate">Start</label>
    <input type="date" id="startdate" name="startdate" <?=ifVal($vals, 'blockstart')?> required>
    <div id="startday"><?=implode(", ", get_days_for_date(new DateTime($vals['blockstart'])))?></div><br>
    <label for="enddate">End</label>
    <input type="date" id="enddate" name="enddate" <?=ifVal($vals, 'blockend')?> required>
    <div id="endday"><?=implode(", ", get_days_for_date(new Datetime($vals['blockend'])))?></div><br>
    <div id="overlap-notice"></div>
    </section>
    <section id="block-series">
    <p>It is possible to select any available lectionary for the lections, but
not every lectionary is <em>meant</em> to provide lections. This is
counterintuitive. Most lectionaries are meant only to provide
preaching texts, despite their label as a "lectionary." This also applies to
the second and third series of lections provided in the <em>Evangelical
Lutheran Hymnary</em> (ELH). In practically all services, the lections read should
come from a reading lectionary in common use, either the so-called "Historic"
(one-year) lectionary (first series in the ELH), or the so-called
"Common" (three-year) lectionary popularized among Lutherans in the early
1970's by the Inter-Lutheran Commission on Worship (ILCW). Variants of
these exist. In particular, the Historic lectionary did not have Old Testament
lections for some time, so they have been added in differing ways. Lectionaries
can be added to the Service Planner on the Housekeeping tab.</p>
<table>
    <tr><td></td><th>Lectionary</th><th>Series</th><th>Custom Readings</th></tr>
    <tr><td><label>Lesson 1</label></td>
    <td><select name="l1lect" id="l1lect">
<? foreach ($lects as $l) { ?>
<option value="<?=$l?>" <?=($l==$vals['l1lect'])?"selected=\"selected\"":""?>><?=$l?></option>
<? } ?>
        </select></td>
    <td><select name="l1series" id="l1series">
<? foreach ($series as $s) { ?>
<option value="<?=$s?>" <?=($s==$vals['l1series'])?"selected=\"selected\"":""?>><?=$s?></option>
<? } ?>
    </select></td>
    <td><input name="l1custom" id="l1custom" value="<?=$vals['l1custom']?>"></td></tr>
    <tr><td><label>Lesson 2</label></td>
    <td><select name="l2lect" id="l2lect">
<? foreach ($lects as $l) { ?>
<option value="<?=$l?>" <?=($l==$vals['l2lect'])?"selected=\"selected\"":""?>><?=$l?></option>
<? } ?>
        </select></td>
    <td><select name="l2series" id="l2series">
<? foreach ($series as $s) { ?>
<option value="<?=$s?>" <?=($s==$vals['l2series'])?"selected=\"selected\"":""?>><?=$s?></option>
<? } ?>
    </select></td>
    <td><input name="l2custom" id="l2custom" value="<?=$vals['l2custom']?>"></td></tr>
    <tr><td><label>Gospel</label></td>
    <td><select name="golect" id="golect">
<? foreach ($lects as $l) { ?>
<option value="<?=$l?>" <?=$l==$vals['golect']?"selected=\"selected\"":""?>><?=$l?></option>
<? } ?>
        </select></td>
    <td><select name="goseries" id="goseries">
<? foreach ($series as $s) { ?>
<option value="<?=$s?>" <?=($s==$vals['goseries'])?"selected=\"selected\"":""?>><?=$s?></option>
<? } ?>
    </select></td>
    <td><input name="gocustom" id="gocustom" value="<?=$vals['gocustom']?>"></td></tr>
    <tr><td><label>Psalm</label></td>
    <td><select name="pslect" id="pslect">
<? foreach ($lects as $l) { ?>
<option value="<?=$l?>" <?=$l==$vals['pslect']?"selected=\"selected\"":""?>><?=$l?></option>
<? } ?>
        </select></td>
    <td><select name="psseries" id="psseries">
<? foreach ($series as $s) { ?>
<option value="<?=$s?>" <?=($s==$vals['psseries'])?"selected=\"selected\"":""?>><?=$s?></option>
<? } ?>
    </select></td>
    <td><input name="pscustom" id="pscustom" value="<?=$vals['pscustom']?>"></td></tr>
    <tr><td><label>Collect</label></td>
    <td><select name="colect" id="colect">
<? foreach ($lects as $l) { ?>
<option value="<?=$l?>" <?=$l==$vals['colect']?"selected=\"selected\"":""?>><?=$l?></option>
<? } ?>
        </select></td>
    <td><select name="coclass" id="coclass">
<? foreach ($collect_classes as $c) { ?>
<option value="<?=$c?>" <?=$c==$vals['coclass']?"selected=\"selected\"":""?>><?=$c?></option>
<? } ?>
    </select></td>
    </tr>
    <tr><td><label>Sermon</label><br>
        <select name="smtype" id="smtype">
        <option value="lesson1">Lesson 1 (OT)</option>
        <option value="lesson2">Lesson 2 (Ep)</option>
        <option value="gospel">Gospel</option>
        </select></td>
    <td><select name="smlect" id="smlect">
<? foreach ($lects as $l) { ?>
<option value="<?=$l?>" <?=$l==$vals['smlect']?"selected=\"selected\"":""?>><?=$l?></option>
<? } ?>
        </select></td>
    <td><select name="smseries" id="smseries">
<? foreach ($series as $s) { ?>
<option value="<?=$s?>" <?=($s==$vals['smseries'])?"selected=\"selected\"":""?>><?=$s?></option>
<? } ?>
    </select></td>
    <td><input name="smcustom" id="smcustom" value="<?=$vals['smcustom']?>"></td></tr>
    </table>
    </section>
    <p><input type="checkbox" name="weeklygradual" id="weeklygradual"
        <?=$vals['weeklygradual']?"checked":""?> value="use">
    <label for="dailygradual">Show Weekly Gradual rather than Seasonal Gradual</label></p>
    <label for="notes">Block Notes</label><br>
    <textarea name="notes" id="notes"><?=$vals['notes']?$vals['notes']:''?></textarea><br>
    <button type="submit">Submit</button>
    <button type="reset">Reset</button>
    </form>
<?
}

$dbp = $db->getPrefix();

/* block.php with $_POST
 * Process the submitted block form
 */
if (getPOST('label')) {
    requireAuth("index.php", 3);
    $_POST['startdate'] = date('Y-m-d', strtotime($_POST['startdate']));
    $_POST['enddate'] = date('Y-m-d', strtotime($_POST['enddate']));
    if ("custom" == $_POST['l1lect']) $_POST['l1series'] = $_POST['l1custom'];
    if ("custom" == $_POST['l2lect']) $_POST['l2series'] = $_POST['l2custom'];
    if ("custom" == $_POST['golect']) $_POST['goseries'] = $_POST['gocustom'];
    if ("custom" == $_POST['pslect']) $_POST['psseries'] = $_POST['pscustom'];
    if ("custom" == $_POST['smlect']) $_POST['smseries'] = $_POST['smcustom'];
    if (isset($_POST['weeklygradual']) && "use" == $_POST['weeklygradual'])
        $_POST['weeklygradual'] = true;
    else
        $_POST['weeklygradual'] = false;
    $binding = array($_POST['label'], $_POST['startdate'],
        $_POST['enddate'], $_POST['notes'], $_POST['l1lect'],
        $_POST['l1series'], $_POST['l2lect'], $_POST['l2series'],
        $_POST['golect'], $_POST['goseries'], $_POST['pslect'],
        $_POST['psseries'], $_POST['colect'], $_POST['coclass'],
        $_POST['smtype'], $_POST['smlect'], $_POST['smseries'],
        $_POST['weeklygradual']);
    if ($_POST['id']) { // Update existing record
        array_push($binding, $_POST['id']);
        $q = $db->prepare("UPDATE `{$db->getPrefix()}blocks`
            SET label = ?, blockstart = ?, blockend = ?, notes = ?,
            l1lect = ?, l1series = ?, l2lect = ?, l2series = ?,
            golect = ?, goseries = ?, pslect = ?, psseries = ?,
            colect = ?, coclass = ?, smtype = ?, smlect = ?, smseries = ?,
            weeklygradual = ?
            WHERE id = ?");
    } else { // Create new record
        $q = $db->prepare("INSERT INTO `{$db->getPrefix()}blocks`
            (label, blockstart, blockend, notes, l1lect, l1series, l2lect,
            l2series, golect, goseries, pslect, psseries, colect, coclass,
            smtype, smlect, smseries, weeklygradual)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    }
    if (! $q->execute($binding)) {
        setMessage("Problem saving block:" . array_pop($q->errorInfo()));
    }
}

/* block.php?action=new
 * Show an empty block edit form
 */
if (getGET('action') == "new") {
    requireAuth("index.php", 3);
    blockPlanForm();
    exit(0);
}

/* block.php?action=edit&id=N
 * Edit the block indicated by the id
 */
if (getGET('action') == "edit" && getGET('id')) {
    requireAuth("index.php", 3);
    $q = $db->prepare("SELECT
        DATE_FORMAT(blockstart, '%Y-%m-%d') AS blockstart,
        DATE_FORMAT(blockend, '%Y-%m-%d') AS blockend, label, notes, weeklygradual,
        l1lect, l1series, l2lect, l2series, golect, goseries, pslect, psseries,
        colect, coclass, smlect, smseries, id FROM `{$db->getPrefix()}blocks`
        WHERE id = ?");
    if ($q->execute(array(getGET('id'))) && $row = $q->fetch(PDO::FETCH_ASSOC)) {
        blockPlanForm($row);
    } else {
        echo array_pop($q->errorInfo());
    }
    exit(0);
}

/* block.php?delete=N
 * Delete the block with id N
 */
if (getGET('delete')) {
    requireAuth("index.php", 3);
    $q = $db->prepare("DELETE FROM `{$db->getPrefix()}blocks` WHERE id = ?");
    if ($q->execute(array(getGET('delete')))) {
        setMessage($q->rowCount() . " blocks deleted.");
    } else {
        setMessage("Problem deleting block: ".array_pop($q->errorInfo()));
    }
}

/* block.php?available=date
 * Return a json list of blocks available for the date given
 */
if (getGET('available')) {
    $q = $db->prepare("SELECT id, label FROM `{$db->getPrefix()}blocks`
        WHERE blockstart <= ? AND blockend >= ?");
    if ($q->execute(array(getGET('available'), getGET('available')))) {
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
if (getGET('overlapstart') && getGET('overlapend')) {
    $q = $db->prepare("SELECT label FROM `{$db->getPrefix()}blocks`
        WHERE (blockstart < :date1 AND blockend > :date1)
        OR (blockstart < :date2 AND blockend > :date2)
        OR (:date1 < blockstart AND :date2 > blockend)");
    $q->bindParam(":date1", getGET('overlapstart'));
    $q->bindParam(":date2", getGET('overlapend'));
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

/* block.php?id=N&get=blockitems
 * Return a list of items
 */
if ("blockitems" == getGET('get') && is_numeric(getGET('id')) && getGET('day')) {
    requireAuth("index.php", 2);
    $q = $db->prepare("SELECT l1lect, l1series, l2lect, l2series,
        golect, goseries, smlect, smseries, smtype, notes, pslect, psseries,
        weeklygradual, colect, coclass,
        l1lect != \"custom\" AS l1link,
        l2lect != \"custom\" AS l2link,
        golect != \"custom\" AS golink,
        pslect != \"custom\" AS pslink,
        smlect != \"custom\" AS smlink
        FROM `{$dbp}blocks`
        WHERE id = :block");
    $q->bindValue(":block", getGET('id'));
    $q->execute() or die("Problem getting blockconfig");
    $blockconfig = $q->fetch(PDO::FETCH_ASSOC);
    $q = $db->prepare("SELECT dayname, lectionary, lesson1, lesson2, gospel,
        psalm, s2lesson, s2gospel, s3lesson, s3gospel, hymnabc, hymn, note
        FROM `{$dbp}synlessons` as cl
        WHERE cl.dayname = :dayname");
    $dayname = getGET('day');
    if (false !== strpos($dayname, '|')) {
        $dayname = trim(substr($dayname, 0, strpos($dayname, '|')));
    }
    $q->bindValue(":dayname", $dayname);
    $q->execute() or die("Problem getting synlessons");
    $raw_synlessons = $q->fetchAll(PDO::FETCH_ASSOC);
    $synlessons = array();
    foreach ($raw_synlessons as $one_set) {
        $synlessons[$one_set['lectionary']] = $one_set;
    }
    /******
     * Select the appropriate lesson
     */
    function select_lesson($type, $lectionary, $series, $synlessons) {
        switch ($lectionary) {
            case "historic":
                switch ($series) {
                    case "first":
                        return $synlessons[$lectionary][$type];
                    case "second":
                        switch ($type) {
                            case "gospel":
                                return $synlessons[$lectionary]["s2gospel"];
                            default:
                                return $synlessons[$lectionary]["s2lesson"];
                        }
                    case "third":
                        case "gospel":
                            return $synlessons[$lectionary]["s3gospel"];
                        default:
                            return $synlessons[$lectionary]["s3lesson"];
                }
                die("Unknown series for historic.");
            case "custom":
                return $series;
            default:
                return $synlessons[$lectionary][$type];
        }
    }
    $blesson1 = select_lesson("lesson1", $blockconfig['l1lect'],
        $blockconfig['l1series'], $synlessons);
    $blesson2 = select_lesson("lesson2", $blockconfig['l2lect'],
        $blockconfig['l2series'], $synlessons);
    $bgospel = select_lesson("gospel", $blockconfig['golect'],
        $blockconfig['goseries'], $synlessons);
    $bsermon = select_lesson($blockconfig['smtype'], $blockconfig['smlect'],
        $blockconfig['smseries'], $synlessons);
    switch ($blockconfig['pslect']) {
        case "custom":
            $bpsalm = $blockconfig['psseries'];
        default:
            $bpsalm = $synlessons[$blockconfig['pslect']]['psalm'];
    }
    $bsmtextnote = $synlessons[$blockconfig['smlect']]['note'];
    $bsmhymnabc = $synlessons[$blockconfig['smlect']]['hymnabc'];
    $bsmhymn = $synlessons[$blockconfig['smlect']]['hymn'];
    $q = $db->prepare("SELECT collect FROM `{$dbp}churchyear_collects` AS cyc
        JOIN `{$dbp}churchyear_collect_index` AS cci
        ON (cyc.id = cci.id)
        WHERE cci.dayname=:dayname AND cci.lectionary=:colect
        AND cyc.class=:coclass LIMIT 1");
    $q->bindValue(":colect", $blockconfig['colect']);
    $q->bindValue(":coclass", $blockconfig['coclass']);
    $q->bindValue(":dayname", $dayname);
    $q->execute() or die("Problem getting collect");
    $raw_collect = $q->fetch(PDO::FETCH_ASSOC);
    $bcollect = $raw_collect['collect'];

    $cfg = getConfig(true);
    $rv = array(
    "Lesson 1"=>bibleLinkOrHelp($blesson1,
            linkbgw($cfg, $blesson1, $blockconfig['l1link'], true)),
    "Lesson 2"=>bibleLinkOrHelp($blesson2,
        linkbgw($cfg, $blesson2, $blockconfig['l2link'], true)),
    "Gospel"=>bibleLinkOrHelp($bgospel,
        linkbgw($cfg, $bgospel, $blockconfig['golink'], true)),
    "Psalm"=>bibleLinkOrHelp($bpsalm,
        linkbgw($cfg, "Psalm {$bpsalm}", $blockconfig['pslink'], true)),
    "Sermon"=>bibleLinkOrHelp($bsermon,
        linkbgw($cfg, $bsermon, $blockconfig['smlink'], true)),
    "Collect"=>bibleLinkOrHelp($bcollect, $bcollect),
    "Sermon Text Note"=>translate_markup($bsmtextnote),
    "Sermon Day Hymn"=>$bsmhymnabc,
    "Sermon Hymn" =>$bsmhymn,
    "Block Notes" =>$blockconfig['notes']
    );
    unset($cfg);
    echo json_encode($rv);
    exit(0);
}

// Display the block planning table
requireAuth("index.php", 3);

?><!DOCTYPE html>
<html lang="en">
<?=html_head("Block Planning")?>
<body>
    <script type="text/javascript">
    function gencheckCustom(abbr) {
        return function() {
            if ($('#'+abbr+'custom').val()) {
                $('#'+abbr+'series').attr('disabled', true)
                    .hide();
                $('#'+abbr+'lect').val('custom');
            } else {
                $('#'+abbr+'series').attr('disabled', false)
                    .show();
                if ($('#'+abbr+'lect').val() == 'custom') {
                    $('#'+abbr+'lect').val('historic');
                }
            }
        }
    }
    function gencheckHistoric(abbr) {
        return function() {
            if (/historic/i.test($('#'+abbr+'lect').val())) {
                $('#'+abbr+'series').attr('disabled', false)
                    .show();
                $('#'+abbr+'custom').val('')
            } else {
                $('#'+abbr+'series').attr('disabled', true)
                    .hide();
            }
        }
    }
    var checkCustomL1 = gencheckCustom("l1");
    var checkHistoricL1 = gencheckHistoric("l1");
    var checkCustomL2 = gencheckCustom("l2");
    var checkHistoricL2 = gencheckHistoric("l2");
    var checkCustomGo = gencheckCustom("go");
    var checkHistoricGo = gencheckHistoric("go");
    var checkCustomPs = gencheckCustom("ps");
    var checkHistoricPs = gencheckHistoric("ps");
    var checkCustomSm = gencheckCustom("sm");
    var checkHistoricSm = gencheckHistoric("sm");
    var checkCustomCo = function() {
        if ($('#colect').val() == 'custom') {
            $('#coclass').hide();
        } else {
            $('#coclass').show();
        }
    };
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
        if (! Modernizr.inputtypes.date) {
            $('#startdate').datepicker({showOn:"button", numberOfMonths:[1,2],
                stepMonths: 2});
        }
        $('#enddate').unbind('change')
            .bind('change', function() {
                getDayFor($(this).val(), $("#endday"));
                var thisDate = new Date($(this).val());
                $("#startdate").attr("max", thisDate.toISOString().split("T")[0]);
                if ($("#startdate").val() && $("#enddate").val()) {
                    checkOverlap();
                }
            })
        if (! Modernizr.inputtypes.date) {
            $('#enddate').datepicker({showOn:"button", numberOfMonths:[1,2],
                stepMonths: 2});
        }
        checkCustomPs();
        checkCustomL1();
        checkCustomL2();
        checkCustomGo();
        checkCustomSm();
        $('#pscustom').change(function() {
            checkCustomPs();
        });
        $('#l1custom').change(function() {
            checkCustomL1();
        });
        $('#l2custom').change(function() {
            checkCustomL2();
        });
        $('#gocustom').change(function() {
            checkCustomGo();
        });
        $('#smcustom').change(function() {
            checkCustomSm();
        });
        checkHistoricL1();
        checkHistoricL2();
        checkHistoricGo();
        checkHistoricPs();
        checkHistoricSm();
        $('#l1lect').change(function() {
            checkHistoricL1();
        });
        $('#l2lect').change(function() {
            checkHistoricL2();
        });
        $('#golect').change(function() {
            checkHistoricGo();
        });
        $('#smlect').change(function() {
            checkHistoricSm();
        });
        $('#pslect').change(checkHistoricPs);
        checkCustomCo();
        $('#colect').change(checkCustomCo);
    }
    $(document).ready(function() {
        $("#new-block").click(function(evt) {
            evt.preventDefault();
            $("#dialog").load(encodeURI("block.php?action=new"), function() {
                $("#dialog").dialog({modal: true,
                            position: { my: "center", at: "center", of: window},
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
                                position: { my: "center", at: "center", of: window},
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
    <?pageHeader();
    siteTabs(); ?>
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
$q = $db->prepare("SELECT DATE_FORMAT(blockstart, '%c/%e/%Y') AS blockstart,
    DATE_FORMAT(blockend, '%c/%e/%Y') AS blockend, label, weeklygradual, notes, l1lect,
    l1series, l2lect, l2series, golect, goseries, pslect, psseries,
    colect, coclass, smtype, smlect, smseries, id
    FROM `{$db->getPrefix()}blocks` AS b
    ORDER BY b.blockstart DESC, b.blockend DESC");
$sermon_types = array("lesson1"=>"Lesson 1 (OT)",
    "lesson2"=>"Lesson 2 (Ep)",
    "gospel"=>"Gospel");
if ($q->execute()) {
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) { ?>
    <tr class="heading">
        <td colspan="2"><?=$row['blockstart']?> to <?=$row['blockend']?></td>
        <td colspan="3"><?=$row['label']?>
        <div class="blocklinks"><a title="edit" href="" data-id="<?=$row['id']?>" class="edit">Edit</a>
        <a title="delete" href="" data-label="<?=$row['label']?>" data-id="<?=$row['id']?>" class="delete">Delete</a></div></td>
        <td class="blocknotes">Gradual:<br>
            <?=$row['weeklygradual']?"weekly":"seasonal"?></td></tr>
    <tr><td class="otcell"><b>Lesson 1:</b>
        <?=showLesson($row['l1lect'], $row['l1series'])?></td>
        <td class="epcell"><b>Lesson 2:</b>
        <?=showLesson($row['l2lect'], $row['l2series'])?></td>
        <td class="gocell"><b>Gospel:</b>
        <?=showLesson($row['golect'], $row['goseries'])?></td>
        <td class="pscell"><b>Psalm:</b>
        <?=showLesson($row['pslect'], $row['psseries'])?></td>
        <td class="smcell"><b>Sermon:</b>
        <?=showLesson($row['smlect'], $row['smseries'])?>
        <?=$sermon_types[$row['smtype']]?></td>
        <td class="cocell"><b>Collect:</b>
        <?=$row['colect']?>
        <? if ($row['colect'] != "custom") {?> (<?=$row['coclass']?>)<?};?>
        </td></tr>
    <tr><td colspan="5"><?=translate_markup($row['notes'])?></td></tr>
<? }
} else echo "Problem getting blocks: " . array_pop($q->errorInfo()); ?>
    </table>
    </div>
    <div id="dialog"></div>
</body>
</html>
