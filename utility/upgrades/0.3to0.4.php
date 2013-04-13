<? /* Upgrade from version 0.2 to 0.3
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
// Check the userlevel.
chdir('../..');
require('./setup-session.php');
require('./functions.php');
validateAuth($require=true);
// Check dbversion.txt
require("./utility/configfile.php");
$configfile = new Configfile("./dbstate.ini", false);
$version = $configfile->get('dbversion');
if ("0.3." != substr($version, 0, 4)) {
    die("Can't upgrade from 0.3.x, since the current db version is {$version}.");
}
// Update the database
require('./db-connection.php');
$rm = array();
$rm[] = "Adding churchyear-graduals table.";
$q = $dbh->prepare("CREATE TABLE `churchyear_graduals` (
    `season`    varchar(64),
    `gradual`   text
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
if (! $q->execute()) {
    $rm[] = "Error: ".array_pop($q->errorInfo());
    exit(0);
}
// Re-create views
$rm[] = "Re-creating database views.";
require("./utility/createviews.php");
// Wrap up.
$rm[] = "Done.  Writing new dbversion to dbstate file.";
// Store the new dbversion.
require('./version.php');
$configfile->store('dbversion',
    "{$version['major']}.{$version['minor']}.{$version['tick']}");
$configfile->save() or die("Problem saving dbstate file.");
// redirect with a message.
setMessage(implode("<br />\n", $rv));
$serverdir = dirname(dirname(dirname($_SERVER['PHP_SELF'])));
header("Location: {$serverdir}");

?>

