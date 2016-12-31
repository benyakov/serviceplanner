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

$authdata = $_SESSION[$sprefix]['authdata'];
$db = new DBConnection();
$db->beginTransaction();
$q = $db->prepare("CREATE TABLE `{$db->getPrefix()}service_flags` (
  `pkey` int(10) unsigned NOT NULL auto_increment,
  `service` int(10) unsigned,
  `occurrence` varchar(50),
  `flag` varchar(100) NOT NULL,
  `value` varchar(100) default NULL,
  `uid` tinyint,
  KEY `pkey` (`pkey`),
  FOREIGN KEY (`service`) REFERENCES `{$db->getPrefix()}days` (`pkey`)
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
$q->execute() or die(array_pop($q->errorInfo()));
$db->commit();
$rm[] = "Created service_flags table.";
$db->beginTransaction();
// Populate with existing communion service data.
$uid = (int)$authdata['uid'];
$q = $db->prepare("INSERT INTO `{$db->getPrefix()}service_flags`
    (`service`, `occurrence`, `flag`, `uid`)
    SELECT d.pkey, h.location, 'communion', {$uid} FROM
    `{$db->getPrefix()}days` AS d
    JOIN `{$db->getPrefix()}hymns` AS h ON (d.pkey = h.service)
    WHERE d.communion = 1 AND h.location IS NOT NULL
    GROUP BY d.pkey, h.location");
$q->execute() or die(array_pop($q->errorInfo()));
$rm[] = "Populated service flags table with communion flags.";
// Delete communion field
$q = $db->prepare("ALTER TABLE `{$db->getPrefix()}days`
    DROP COLUMN `communion`");
$q->execute() or die(array_pop($q->errorInfo()));
$rm[] = "Removed communion column from days table.";

// Change "location" to "occurrence"
$q = $db->prepare("ALTER TABLE `{$db->getPrefix()}hymns`
    CHANGE `location` `occurrence` varchar(50) default NULL");
$q->execute() or die(array_pop($q->errorInfo()));
$db->commit();


$options = new Configfile("./options.ini", true, true, true);
// These services flags can be set by less privileged users to indicate possibilities
// that may have to be approved by someone overseeing the service.
$addable_service_flags = array(
    "Organist Available",
    "Altar Guild Available",
    "Choir Available",
    "Soloist Available",
    "Lector Available",
    "Acolyte Available",
    "Assistant Available");
$options->set('addable_service_flags', $addable_service_flags);

// This is used to set whether service occurrences should be aggregated in the listing
$options->set('combineoccurrences', 0);
$options->save();
unset($options);
$rm[] = "Updated options";

$dbstate = getDBState(true);
$newversion = "{$version['major']}.{$version['minor']}.{$version['tick']}";
$dbstate->set('dbversion', $newversion);
$dbstate->save() or die("Problem saving dbstate file.");
unset($dbstate);
$rm[] = "Upgraded to {$newversion}";
setMessage(implode("<br />\n", $rm));
header("Location: admin.php");
?>
