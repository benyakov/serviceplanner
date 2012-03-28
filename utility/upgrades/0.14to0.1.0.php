<?
// Check if the user has a userlevel already (he shouldn't).
chdir('../..');
require('./setup-session.php');
require('./functions.php');
validateAuth($require=false);
// Check dbversion.txt file.  It should not exist.
if (file_exists("./dbversion.txt")) {
    $fh = fopen("./dbversion.txt");
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
CREATE TABLE `users` (
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
