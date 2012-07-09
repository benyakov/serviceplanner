<? /* Initialization used by all entry points
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
$thisdir = dirname(__FILE__);
chdir($thisdir);
require("./version.php");
if (! file_exists("options.php")) {
    copy("options.php.sample", "options.php");
}
require("./options.php");
require("./setup-session.php");
require("./functions.php");
$serverdir = dirname($_SERVER['PHP_SELF']);
$script_basename = basename($_SERVER['PHP_SELF'], '.php');
if ((! file_exists("db-connection.php") and
    (! is_link($_SERVER['SCRIPT_FILENAME'])))) {
        header("Location: {$serverdir}/utility/setup-dbconfig.php");
        exit(0);
}
require("./utility/configfile.php");
$configfile = new Configfile("./dbstate.ini", false);
$upgradedb = false;
if (false === $configfile->get('dbversion')) {
    $upgradedb = true;
    $oldversion = "";
} else {
    $dbcurrent = explode('.', trim($configfile->get('dbversion')));
    if (! ($version['major'] == $dbcurrent[0]
        && $version['minor'] == $dbcurrent[1])) {
        $upgradedb = true;
        $oldversion = "{$dbcurrent[0]}.{$dbcurrent[1]}";
    }
}
if ($upgradedb) {
    $newversion = "{$version['major']}.{$version['minor']}";
    header("Location: {$serverdir}/utility/upgrades/{$oldversion}to{$newversion}.php");
    exit(0);
}
if (! $configfile->get("has-user") || $_GET['flag'] == 'inituser') {
    header("Location: {$serverdir}/utility/inituser.php");
    exit(0);
}
require("./db-connection.php");
if (! $_GET['flag'] == "inituser") {
    $auth = auth();
}
if ((! $configfile->get("churchyear-filled")) or
    ($_GET['flag'] == 'fill-churchyear' && $auth))
{
    require('./utility/fillservicetables.php');
    $configfile->store("churchyear-filled", 1);
    $configfile->save() or die("Problem saving dbstate file.");
}
if ((! $configfile->get("has-churchyear-functions")) or
    ($_GET['flag'] == 'create-churchyear-functions' && $auth))
{
    $functionsfile = "./utility/churchyearfunctions.sql";
    $functionsfh = fopen($functionsfile, "rb");
    $functionstext = fread($functionsfh, filesize($functionsfile));
    fclose($functionsfh);
    $q = $dbh->prepare(replaceDBP($functionstext));
    $q->execute() or die("Problem creating functions<br>".
        array_pop($q->errorInfo()));
    $q->closeCursor();
    $configfile->store('has-churchyear-functions', 1);
    $configfile->save() or die("Problem saving dbstate file.");
}
?>
