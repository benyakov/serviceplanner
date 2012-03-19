<?
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
if (! (file_exists("has-user.txt") || $_GET['flag'] == 'inituser')) {
    header("Location: {$serverdir}/utility/inituser.php");
    exit(0);
}
require("./db-connection.php");
if (! $_GET['flag'] == "inituser") {
    $auth = auth();
}
?>
