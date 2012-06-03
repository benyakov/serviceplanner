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
// Write database version to dbversion.txt
$fh = fopen("../dbversion.txt", "w");
fwrite($fh, "{$version['major']}.{$version['minor']}.{$version['tick']}");
fclose($fh);
// Create the initial user
require("inituser.php");
?>
