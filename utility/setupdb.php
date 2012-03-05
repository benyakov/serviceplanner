<?
chdir("..");
require("./init.php");
chdir("./utility");
if (! $auth) {
    header("location: ../index.php");
    exit(0);
}
$dumpfile="createtables.sql";
$dumplines = file($dumpfile, FILE_IGNORE_NEW_LINES);
// Separate SQL statements into an array.
$query = array();
$queries = array();
foreach ($dumplines as $line)
{
    if (preg_match('/^CREATE/', $line)) // A new query
    {
        if (count($query) > 0)
        {
            $queries[] = implode("\n", $query);
        }
        $query = array();
    }
    // If needed, add a prefix to the table names
    $query[] = preg_replace(
                array(
                    '/^(CREATE TABLE `)([^`]+)/',
                    '/(REFERENCES `)([^`]+)/',
                    '/(CONSTRAINT `)([^`]+)/'
                ), "\\1${dbp}\\2", $line);
}
$queries[] = implode("\n", $query);
// Execute each SQL query.
$dbh->beginTransaction();
foreach ($queries as $query) {
    $result = $dbh->exec($query);
    if ($result === false) {
        $dbh->rollback();
        ?>
        <!DOCTYPE html>
        <html lang="en"><head><title>Setup Failed</title></head>
        <body><h1>Setup Failed</h1>
        <p>Failed SQL Query:</p>
        <pre><?=$query?></pre>
        </body></html>
        <?
        exit(1);
    }
}
$dbh->commit();

setMessage("Setup succeeded.");
header("Location: ../records.php");
?>
