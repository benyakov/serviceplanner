<? /* Upgrade from version 0.5 to 0.6
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
if (! (isset($newversion) && isset($oldversion))) {
    echo "Error: This upgrade must be run automatically.";
}
if ("0.5." != substr($oldversion, 0, 4).'.') {
    die("Can't upgrade from 0.5.x, since the current db version is {$oldversion}.");
}
$db = new DBConnection();
$db->beginTransaction();
// Delete churchyear data.
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear_graduals`");
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear_collects_index`");
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear_collects`");
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear_synonyms`");
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear_lessons`");
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear_propers`");
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear_order`");
$db->exec("DELETE FROM `{$db->getPrefix()}churchyear`");
$dbstate = getDBState(true);
if (is_object($dbstate)) {
    $rm[] = "Deleting churchyear data (will restore defaults)";
    $dbstate->set("churchyear-filled", 0);
    $dbstate->save();
} else {
    $rm[] = "Problem saving dbstate config file.  Aborting upgrade.";
    $db->rollback();
    die(implode("<br />\n", $rm));
}
$rm[] = "Modifying churchyear_synonyms table, making synonyms unique...";
$q = $db->prepare("ALTER TABLE `{$db->getPrefix()}churchyear_synonyms`
    MODIFY `synonym` varchar(255) UNIQUE NOT NULL");
if (! $q->execute())
    die("Problem modifying synonym field in churchyear_synonyms table: ".
        array_pop($q->errorInfo()));
$q = $db->prepare("ALTER TABLE `{$db->getPrefix()}churchyear_synonyms`
    MODIFY `canonical` varchar(255) NOT NULL");
if (! $q->execute())
    die("Problem modifying canonical field in in churchyear_synonyms table: ".
        array_pop($q->errorInfo()));
$rm[] = "Done.  Writing new dbversion to dbstate file.";
$db->commit();
$newversion = "{$version['major']}.{$version['minor']}.{$version['tick']}";
$dbstate->set('dbversion', $newversion);
$dbstate->save() or die("Problem saving dbstate file.");
unset($dbstate);
$rm[] = "Upgraded to {$newversion}";
setMessage(implode("<br />\n", $rm));
?>
