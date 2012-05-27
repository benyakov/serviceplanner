<?
require("./init.php");
if (! $auth) {
    header("Location: index.php");
    exit(0);
}
$tabledescfiles = array(
    "./utility/createtables.sql",
    "./utility/dynamictables.sql"
);
function gettablename ($line) {
    if (preg_match('/TABLE `(\w+)/', $line, $matches)) {
        return $matches[1];
    } else {
        return False;
    }
}
function adddbpfix ($name) {
    global $dbp;
    return "{$dbp}{$name}";
}
$tabledesclines = array();
foreach ($tabledescfiles as $tabledescfile) {
    $tabledesclines =
        array_merge($tabledesclines,
            file($tabledescfile, FILE_IGNORE_NEW_LINES));
}
$tablenamelines = array_filter($tabledesclines, gettablename);
$tablenames = array_map(gettablename, $tablenamelines);
$finaltablenames = array_map(adddbpfix, $tablenames);
$tablenamestring = implode(" ", $finaltablenames);
if (touch(".my.cnf") && chmod(".my.cnf", 0600)) {
    header("Content-type: text/plain");
    $timestamp = date("dMY-Hi");
    header("Content-disposition: attachment; filename=services-{$timestamp}.dump");
    $fp = fopen(".my.cnf", "w");
    fwrite($fp, "[client]
    user=\"{$dbconnection['dbuser']}\"
    password=\"{$dbconnection['dbpassword']}\"\n") ;
    fclose($fp);
    $rv = 0;
    passthru("mysqldump --defaults-file=.my.cnf -h {$dbconnection['dbhost']} {$dbconnection['dbname']} {$tablenamestring}", $rv);
    unlink(".my.cnf");
    if ($rv != 0) {
        echo "mysqldump returned {$rv}";
    }
} else {
    echo "Problem dumping database tables.";
}
?>
