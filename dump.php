<?
require("db-connection.php");
$tabledescfile = "createtables.sql";
$tabledesclines = file($tabledescfile, FILE_IGNORE_NEW_LINES);
function gettablename ($line)
{
    if (preg_match('/TABLE `(\w+)/', $line, $matches))
    {
        return $matches[1];
    } else {
        return False;
    }
}
$tablenamelines = array_filter($tabledesclines, gettablename);
$tablenames = array_map(gettablename, $tablenamelines);
function addtableprefix ($name)
{
    global $dbp;
    return "${dbp}${name}";
}
$finaltablenames = array_map(addtableprefix, $tablenames);
$tablenamestring = implode(" ", $finaltablenames);
header("Content-type: text/plain");
$timestamp = date("dMY-Hi");
header("Content-disposition: attachment; filename=services-${timestamp}.dump");
// Including the password here is insecure on a shared machine
// because the invocation will appear in the list of processes.
// But it's easy.
passthru("mysqldump -u ${dbuser} -p${dbpw} -h ${dbhost} ${dbname} ${tablenamestring}");
?>
