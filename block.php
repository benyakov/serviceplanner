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
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
}

/* block?action=new
 * Show an empty block edit form
if ($_GET['action'] == "new") {

}
 */

// Display the block planning table
$q = $dbh->prepare("SELECT blockstart, blockend, label, notes, oldtestament,
    epistle, gospel, psalm, collect, seq FROM blocks
    ORDER BY (blockstart, blockend)");
?><!DOCTYPE html>
<html lang="en">
<?=html_head("Block Planning")?>
<body>
    <script type="text/javascript">
    $(document).ready(function() {
        return;
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
    <tr><td><?=$row['blockstart']?></td><td><?=$row['blockend']?></td>
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
</body>
</html>
