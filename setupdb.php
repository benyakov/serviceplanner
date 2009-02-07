<?
require("db-connection.php");
$dumpfile="createtables.sql";
$cmdline = "mysql -u ${dbuser} -p${dbpw} -h ${dbhost} ${dbname} ".
    "-e 'source ${dumpfile}';";
$result = system($cmdline, $return);
if (0 == $return)
{
    header("Location: records.php?message=".urlencode("Setup succeeded."));
} else {
    ?>
    <html><head><title>Problem Executing Restore</title></head>
    <body><h1>Problem Executing Restore</h1>
    <ul>
    <li>Are the tables already created?</li>
    <li>Make sure createtables.sql is there.</li>
    <li>Make sure db-connection.php exists and has correct information.</li>
    </ul>
    </body></html>
    <?
}
?>
