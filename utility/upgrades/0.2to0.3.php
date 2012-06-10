<? /* Upgrade from version 0.1 to 0.2
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
if (file_exists("./dbversion.txt")) {
    $fh = fopen("./dbversion.txt", "rb");
    $version = trim(fread($fh, 32));
    fclose($fh);
    if ("0.2." != substr($version, 0, 4)) {
        die("Can't upgrade from 0.1.x, since the current db version is {$version}.");
    }
}
// Update the database definition
$rv = array();
require('./db-connection.php');
$rv[] = "Creating table for block planning...";
$dbh->beginTransation();
$q = $dbh->prepare("CREATE TABLE `{$dbp}blocks` (
  `blockstart` date,
  `blockend` date,
  `label` varchar(128),
  `notes` text,
  `oldtestament` varchar(64),
  `epistle` varchar(64),
  `gospel` varchar(64),
  `psalm` varchar(64),
  `collect` varchar(64),
  `id` integer auto_increment,
  UNIQUE KEY `span` (`blockstart`, `blockend`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
if (!$q->execute()) {
    $rv[] = "Couldn't create blocks table: " . array_pop($q->errorInfo());
} else {
    $q = $dbh->prepare("ALTER TABLE `{$dbp}days`
        ADD COLUMN `block` integer AFTER `servicenotes` default NULL");
    if ($q->execute()) {
        $rv[] = "Done.";
        // write a new dbversion.txt
        require('./version.php');
        $fh = fopen("./dbversion.txt", "wb");
        fwrite($fh, "{$version['major']}.{$version['minor']}.{$version['tick']}");
        fclose($fh);
    } else {
        $rv[] = "Couldn't add column to days table: " . array_pop($q->errorInfo());
    }
}
// redirect with a message.
setMessage(implode("<br />\n", $rv));
$serverdir = dirname(dirname(dirname($_SERVER['PHP_SELF'])));
header("Location: {$serverdir}");

?>

