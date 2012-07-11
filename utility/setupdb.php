<? /* Initial database setup script
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
require("../options.php");
require("../setup-session.php");
require("../functions.php");
validateAuth($require=false);
require("../db-connection.php");
require("../version.php");

$dumpfile="createtables.sql";
$dumplines = file($dumpfile, FILE_IGNORE_NEW_LINES);
// Separate SQL statements into an array.
$query = array();
$tables = array();
$queries = array();
foreach ($dumplines as $line) {
    if (preg_match('/^CREATE/', $line)) { // A new query
        if (count($query) > 0) {
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
    if (strpos($line, 'CREATE TABLE') > -1) {
        $tables[] = preg_replace('/^(CREATE TABLE `)([^`]+).*$/', "{$dbp}\\2",
            $line);
    }
}
$queries[] = implode("\n", $query);
if ($_GET['drop'] = "first") {
    $dropresults = array();
    foreach ($tables as $table) {
        $q = $dbh->prepare("DROP TABLE `{$table}`");
        if ($q->execute()) $dropresults[] = "Table `{$table}` dropped.";
        else $dropresults[] = array_pop($q->errorinfo());
    }
}
// Execute each SQL query.
$dbh->beginTransaction();
foreach ($queries as $query) {
    $q = $dbh->prepare($query);
    if (! $q->execute()) {
        $dbh->rollback();
        ?>
        <!DOCTYPE html>
        <html lang="en"><head><title>Setup Failed</title></head>
        <body><h1>Setup Failed</h1>
        <p>Failed SQL Query:</p>
        <pre><?=$query?></pre>
        <h2>Description of the problem:</h2>
        <?print_r($q->errorInfo());?>
        </body></html>
        <?
        exit(1);
    }
}
$dbh->commit();
// Write database version to dbstate file.
require("configfile.php");
$dbstate = new Configfile("../dbstate.ini", false);
$dbstate->store('dbversion',
    "{$version['major']}.{$version['minor']}.{$version['tick']}");
$dbstate->save();
// Create the initial user
require("inituser.php");
?>
