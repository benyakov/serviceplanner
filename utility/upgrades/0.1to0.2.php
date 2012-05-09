<?
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
    if ("0.1." != substr($version, 0, 4)) {
        die("Can't upgrade from 0.1.x, since the current db version is {$version}.");
    }
}
// Update the database definition
$rv = array();
require('./db-connection.php');
$rv[] = "Adding sermon file to database definition.";
$q = $dbh->exec("ALTER TABLE `{$dbp}sermons`
    ADD COLUMN `manuscript` BLOB AFTER `notes`");
$q = $dbh->exec("ALTER TABLE `{$dbp}sermons`
    ADD COLUMN `mstype` varchar(50) default NULL
    AFTER `manuscript`");
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
