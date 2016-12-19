<? /* Upgrade from version 0.16 to 0.17
    Copyright (C) 2016 Jesse Jacobsen

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
if ("0.16." != substr($oldversion, 0, 5).'.') {
    die("Can't upgrade from 0.16.x, since the current db version is {$oldversion}.");
}

$db = new DBConnection();
$db->beginTransaction();
$q = $db->prepare("CREATE TABLE `service_flags` (
  `pkey` int(10) unsigned NOT NULL auto_increment,
  `service` int(10) unsigned,
  `location` varchar(50),
  `flag` varchar(100) NOT NULL,
  `value` varchar(100) default NULL,
  KEY `pkey` (`pkey`),
  CONSTRAINT `service_flags_ibfk_1` FOREIGN KEY (`svc`) REFERENCES `days` (`pkey`)
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
$q->execute() or die(array_pop($q->errorInfo()));
// Populate with existing communion service data.
$q = $db->prepare("INSERT INTO `{$db->getPrefix()}service_flags`
    (`day`, `location`, `flag`)
    SELECT d.day, h.location, 'communion' FROM
    `days` AS d
    JOIN `hymns` AS h");
$q->execute() or die(array_pop($q->errorInfo()));
// Delete communion field
$q = $db->prepare("ALTER TABLE `{$db->getPrefix()}days`
    DROP COLUMN `communion`");
$q->execute() or die(array_pop($q->errorInfo()));
$db->commit();

$dbstate = getDBState(true);
$newversion = "{$version['major']}.{$version['minor']}.{$version['tick']}";
$dbstate->set('dbversion', $newversion);
$dbstate->save() or die("Problem saving dbstate file.");
unset($dbstate);
$rm[] = "Upgraded to {$newversion}";
setMessage(implode("<br />\n", $rm));
header("Location: admin.php?flag=create-views");
?>
