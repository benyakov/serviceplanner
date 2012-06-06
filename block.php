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

/* block?action=new
 * Show an empty block edit form
 */
if ($_GET['action'] == "new") {
    if (! $auth) {
        echo json_encode(array(false, ""));
        exit(0);
    }
    ob_start();
    // TODO: Put the following form into a function that accepts defaults
    // so that we can use it for editing too.
?>
    <form id="block-plan-form" action="block.php" method="post">
    <label for="label">Label</label><input type="text" id="label" name="label"><br>
    <label for="startdate">Start</label>
    <input type="date" id="startdate" name="startdate">
    <div id="startday"></div><br>
    <label for="startdate">End</label>
    <input type="date" id="enddate" name="enddate">
    <div id="endday"></div><br>
    <label for="oldtestament">Old Testament</label>
    <select name="oldtestament" id="oldtestament">
        <option value="1">First</option>
        <option value="2">Second</option>
        <option value="3">Third</option>
        <option value="Custom">Custom</option>
    </select>
    <label for="otcustom">Custom OT Series</label>
    <input type="text" name="otcustom" id="otcustom"><br>
    <label for="epistle">Epistle</label>
    <select name="epistle" id="epistle">
        <option value="1">First</option>
        <option value="2">Second</option>
        <option value="3">Third</option>
        <option value="Custom">Custom</option>
    </select>
    <label for="epcustom">Custom Epistle Series</label>
    <input type="text" name="epcustom" id="epcustom"><br>
    <label for="gospel">Gospel</label>
    <select name="gospel" id="gospel">
        <option value="1">First</option>
        <option value="2">Second</option>
        <option value="3">Third</option>
        <option value="Custom">Custom</option>
    </select>
    <label for="gocustom">Custom Gospel Series</label>
    <input type="text" name="gocustom" id="gocustom"><br>
    <label for="psalm">Psalm</label>
    <select name="psalm" id="psalm">
        <option value="1">First</option>
        <option value="2">Second</option>
        <option value="3">Third</option>
        <option value="Custom">Custom</option>
    </select>
    <label for="pscustom">Custom Psalm Series</label>
    <input type="text" name="pscustom" id="pscustom"><br>
    <label for="collect">Collect</label>
    <select name="collect" id="collect">
        <option value="1">First</option>
        <option value="2">Second</option>
        <option value="3">Third</option>
        <option value="Custom">Custom</option>
    </select>
    <label for="cocustom">Custom Collect Series</label>
    <input type="text" name="cocustom" id="cocustom"><br>
    <label for="notes">Block Notes</label>
    <textarea name="notes" id="notes"></textarea><br>
    <button type="submit">Submit</button>
    <button type="reset">Reset</button>
    </form>
<?
    echo json_encode(array(true, ob_get_clean()));
    exit(0);
}

// Display the block planning table
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
}

$q = $dbh->prepare("SELECT blockstart, blockend, label, notes, oldtestament,
    epistle, gospel, psalm, collect, seq FROM blocks
    ORDER BY (blockstart, blockend)");
?><!DOCTYPE html>
<html lang="en">
<?=html_head("Block Planning")?>
<body>
    <script type="text/javascript">
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
    <tr><th>OT</th><th>Epistle</th><th>Gospel</th></th><th>Psalm</th></tr>
    <tr><th colspan="4">Notes</th><th>Collect</th>
    <?
if ($q->execute()) {
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) { ?>
    <tr class="heading"><td><?=$row['blockstart']?></td>
        <td><?=$row['blockend']?></td>
        <td><?=$row['label']?></td>
        <td><a title="edit" href="" data-seq="<?=$row['seq']?>" class="edit">Edit</a>
        <a title="delete" href="" data-seq="<?=$row['seq']?>" class="delete">Delete</a></td></tr>
    <tr><td><?=$row['oldtestament']?></td><td><?=$row['epistle']?></td>
        <td><?=$row['gospel']?></td><td><?=$row['psalm']?></td></tr>
    <tr><th colspan="4"><?=$row['notes']?></td><td><?=$row['collect']?></td></tr>
<? }
} ?>
    </table>
    </div>
    <div id="dialog"></div>
</body>
</html>
