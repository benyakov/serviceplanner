<?
$thisdir = dirname(__FILE__);
chdir($thisdir);
require("./version.php");
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
$upgradedb = false;
if (! file_exists("dbversion.txt")) {
    $upgradedb = true;
    $oldversion = "";
} else {
    $fh = fopen("dbversion.txt", "rb");
    $dbcurrent = explode('.', trim(fread($fh, 64)));
    fclose($fh);
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
if (! (file_exists("has-user.txt") || $_GET['flag'] == 'inituser')) {
    header("Location: {$serverdir}/utility/inituser.php");
    exit(0);
}
require("./db-connection.php");
if (! $_GET['flag'] == "inituser") {
    $auth = auth();
}
?>
