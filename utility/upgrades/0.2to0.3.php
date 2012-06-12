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
// Update the database
require('./db-connection.php');
$rv = array();
$rv[] = "Adding block column to days table.";
$q = $dbh->prepare("ALTER TABLE `{$dbp}days`
    ADD COLUMN `block` integer AFTER `servicenotes` default NULL");
$q->execute() or die(array_pop($q->errorInfo()));
$rv[] = "Creating table for block planning.";
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
$q->execute() or die(array_pop($q->errorInfo()));
$rv[] = "Creating churchyear table.";
$q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear` (
    `dayname` varchar(255),
    `season` varchar(64) default \"\",
    `base` varchar(255) default NULL,
    `offset` smallint default 0,
    `month` tinyint default 0,
    `day`   tinyint default 0,
    `observed_month` tinyint default 0,
    `observed_sunday` tinyint default 0,
    PRIMARY KEY (`dayname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
$q->execute() or die(array_pop($q->errorInfo()));
// Define helper table for ordering the presentation of days
$rv[] = "Creating table for ordering seasons.";
$q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear_order` (
    `name` varchar(32),
    `idx` smallint UNIQUE,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
$q->execute() or die(array_pop($q->errorInfo()));
// Define table containing synonyms for the day names
$rv[] = "Creating synonyms table.";
$q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear_synonyms` (
    `canonical` varchar(255),
    `synonym`   varchar(255),
    INDEX (`canonical`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
$q->execute() or die(array_pop($q->errorInfo()));
// Define table containing propers for the day names
$rv[] = "Creating propers table.";
$q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear_propers` (
    `dayname`   varchar(255),
    `color`     varchar(32),
    `theme`     varchar(64),
    `note`      text,
    FOREIGN KEY (`dayname`) REFERENCES `{$dbp}churchyear` (`dayname`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
$q->execute() or die(array_pop($q->errorInfo()));
// Define table containing lessons, multiple sets for each day name
$rv[] = "Creating table for lessons.";
$q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear_lessons` (
    `dayname`   varchar(255),
    `label`     varchar(56),
    `oldtestament`  varchar(64),
    `epistle`   varchar(64),
    `gospel`    varchar(64),
    `psalm`     varchar(64),
    `collect`   text,
    `introit`   text,
    FOREIGN KEY (`dayname`) REFERENCES `{$dbp}churchyear` (`dayname`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8");
// Populate church year tables with default values
$rv[] = "Populating church year tables with default values.";
require("./utility/fillservicetables");
// Wrap up.
$rv[] = "Done.  Writing new dbversion.txt.";
// write a new dbversion.txt
require('./version.php');
$fh = fopen("./dbversion.txt", "wb");
fwrite($fh, "{$version['major']}.{$version['minor']}.{$version['tick']}");
fclose($fh);
// redirect with a message.
setMessage(implode("<br />\n", $rv));
$serverdir = dirname(dirname(dirname($_SERVER['PHP_SELF'])));
header("Location: {$serverdir}");

?>

