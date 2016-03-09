<? /* Upgrade from version 0.13 to 0.14
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
if ("0.13." != substr($oldversion, 0, 5).'.') {
    die("Can't upgrade from 0.13.x, since the current db version is {$oldversion}.");
}

$db = new DBConnection();
$db->beginTransaction();
$q = $db->prepare("SELECT `service`, `manuscript`
    FROM `{$db->getPrefix()}sermons`
    WHERE `manuscript` IS NOT NULL");
$q->execute() or die(array_pop($q->errorInfo()));
$moved = array();
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $service = str_pad($row['service'], 4, '0', STR_PAD_LEFT);
    $first = substr((string) $service, 0, 2);
    $second = substr((string) $service, 2, 2);
    $dirpath = "{$thisdir}/uploads/{$first}/{$second}/{$service}";
    if (! file_exists($dirpath)) mkdir($dirpath, 0750, TRUE);
    $fh = fopen("{$dirpath}/manuscript", "wb");
    fwrite($fh, $row['manuscript']);
    fclose($fh);
    $moved[] = array($row['service'], "{$dirpath}/manuscript");
}
$q = $db->prepare("ALTER TABLE `{$db->getPrefix()}sermons`
    MODIFY COLUMN `manuscript` text");
$q->execute() or die(array_pop($q->errorInfo()));
if ($moved) {
    $service = ""; $path = "";
    $q = $db->prepare("UPDATE `{$db->getPrefix()}sermons`
        SET `manuscript` = :path
        WHERE `service` = :service");
    $q->bindParam(':path', $path);
    $q->bindParam(':service', $service);
    foreach ($moved as $m)  {
        list($service, $path) = $m;
        $q->execute() or die(array_pop($q->errorInfo()));
    }
}
$db->commit();

$dbstate = getDBState(true);
$newversion = "{$version['major']}.{$version['minor']}.{$version['tick']}";
$dbstate->set('dbversion', $newversion);
$dbstate->save() or die("Problem saving dbstate file.");
unset($dbstate);
$rm[] = "Upgraded to {$newversion}";
setMessage(implode("<br />\n", $rm));
header("Location: admin.php");
?>
