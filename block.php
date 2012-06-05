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

// Display the block planning table
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
}
$result = $dbh->exec("SELECT blockstart, blockend, label, notes, oldtestament,
    epistle, gospel, psalm, collect, seq FROM blocks
    ORDER BY (blockstart, blockend)");
?><!DOCTYPE html>
<html lang="en">
<?=html_head("Block Planning")?>
<body>
    <header>
    <?=getLoginForm()?>
    <?=getUserActions()?>
    <? showMessage(); ?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <table id="block-listing">
    <tr><th>Start</th><th>End</th><th colspan="2">Label</th></tr>
    <tr><th>OT</th><th>Epistle</th><th>Gospel</th></th><th>Psalm</th></tr>
    <tr><th colspan="4">Notes</th><th>Collect</th>
    <?
while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
    <tr><td><?=$row['blockstart']?></td><td><?=$row['blockend']?></td>
        <td><?=$row['label']?></td>
        <td><a title="edit" href="" data-seq="<?=$row['seq']?>" class="edit">Edit</a>
        <a title="delete" href="" data-seq="<?=$row['seq']?>" class="delete">Delete</a></td></tr>
    <tr><td><?=$row['oldtestament']?></td><td><?=$row['epistle']?></td>
        <td><?=$row['gospel']?></td><td><?=$row['psalm']?></td></tr>
    <tr><th colspan="4"><?=$row['notes']?></td><td><?=$row['collect']?></td></tr>
<? } ?>
    </table>
    </div>
</body>
</html>
