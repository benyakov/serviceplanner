<? /* Upgrade from unversioned early releases to 0.1
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
// Check if the user has a userlevel already (he shouldn't).
chdir('../..');
require('./setup-session.php');
require('./functions.php');
validateAuth($require=false);
// Check dbversion.txt file.  It should not exist.
if (file_exists("./dbversion.txt")) {
    $fh = fopen("./dbversion.txt", "rb");
    $version = trim(fread($fh, 32));
    fclose($fh);
    die("Can't upgrade from 0.14, since the current db version is {$version}.");
}
$rv = array();
require('./db-connection.php');
// Rewrite database connection script
$rv[] = "Rewriting db connection script.";
try {
    $fp = fopen("./db-connection.php", "w");
    fwrite($fp, "<? // Do not change this file unless you know what you are doing.
// This tells the web application how to connect to your database.
try{
\$dbh = new PDO('mysql:host={$dbhost};dbname={$dbname}',
    '{$dbuser}', '{$dbpw}');
} catch (PDOException \$e) {
die(\"Database Error: {\$e->getMessage()} </br>\");
}
\$dbp = '{$dbp}';
\$dbconnection = array(
'dbhost'=>'{$dbhost}',
'dbname'=>'{$dbname}',
'dbuser'=>'{$dbuser}',
'dbpassword'=>'{$dbpw}');
?>
");
    fclose($fp);
    chmod("./db-connection.php", 0600);
} catch(Exception $e) {
    die(implode("\n", $rv)."\n".$e);
}
// Add new table
$rv[] = "Adding new users table.";
$sql = "
CREATE TABLE `{$dbp}users` (
  `uid` smallint NOT NULL auto_increment,
  `username` char(15) NOT NULL,                                                   `password` varchar(1024) NOT NULL,
  `fname` char(20) NOT NULL,
  `lname` char(30) NOT NULL,
  `userlevel` tinyint NOT NULL default '0',
  `email` char(40) default NULL,
  `resetkey` text default NULL,
  `resetexpiry` datetime default NULL,
  PRIMARY KEY (`uid`)
) TYPE=InnoDB DEFAULT CHARSET=utf8";
mysql_query($sql) or die(implode("\n", $rv)."\n".mysql_error());
// Initial user will be created when the application is accessed next
if (file_exists("./has-user.txt")) {
    unlink("./has-user.txt");
}
// Write database version to dbversion.txt
$rv[] = "Writing new dbversion.txt.";
try {
    $fh = fopen("./dbversion.txt", "w");
    fwrite($fh, "0.1.0");
    fclose($fh);
    $serverdir = dirname(dirname(dirname($_SERVER['PHP_SELF'])));
    header("Location: {$serverdir}");
} catch (Exception $e) {
    die(implode("\n", $rv)."\n".$e);
}

?>
