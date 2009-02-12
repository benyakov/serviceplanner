<?
require("db-connection.php");
$dumpfile="createtables.sql";
//$cmdline = "mysql -u ${dbuser} -p${dbpw} -h ${dbhost} ${dbname} ".
    "-e 'source ${dumpfile}';";
//$result = system($cmdline, $return);
$dumplines = file($dumpfile, FILE_IGNORE_NEW_LINES);
// Separate SQL statements into an array.
$queries = array();
foreach ($dumplines as $line)
{
    if (preg_match('/^CREATE/', $line)) // A new query
    {
        if ($count($queries))
        {
            array_push($queries, implode(" ", $query));
            $query = array();
        }
        array_push($query,
            // If needed, add a prefix to the table names
            preg_replace(
                    array(
                        '/^(CREATE TABLE `)([^`]+)',
                        '/(REFERENCES `)([^`]+)'
                    ), "\\1${dbp}\\2", $line));
    }
}
array_push($queries, implode(" ", $query));
// Execute each SQL query.
foreach ($queries as $query) {
    mysql_query($query) or die(mysql_error());
}

header("Location: records.php?message=".urlencode("Setup succeeded."));
?>
