<? /* Set up database configuration file.
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
$serverdir = dirname(dirname($_SERVER['PHP_SELF']));
if (array_key_exists("step", $_POST) && $_POST['step'] == '2') {
    // Process the form (second time around)
    if (file_exists("../db-connection.php")) {
        setMessage("Database configuration already exists.  To reconfigure, delete db-connection.php and any unwanted tables, then try again.");
        header ("Location: {$serverdir}/index.php");
        exit(0);
    }

    // Escape string-ending characters to avoid PHP injection
    $post = str_replace('\\', '\\\\', $_POST);
    $post = str_replace('\'', '\\\'', $post);

    $fp = fopen("../db-connection.ini", "w");
    fwrite($fp, "# Do not change this file unless you know what you are doing.
# This tells the web application how to connect to your database.
dbhost = '{$post['dbhost']}'
dbname = '{$post['dbname']}'
dbuser = '{$post['dbuser']}'
dbpassword = '{$post['dbpassword']}'
");
    fclose($fp);
    chmod("../db-connection.php", 0600);
    require("../db-connection.php");
    // Test the existence of a table
    $q = $dbh->query("SHOW TABLES LIKE '{$dbp}days'");
    if ($q->rowCount()) {
        header("Location: {$serverdir}/index.php");
    } else {
        header("Location: setupdb.php");
    }
} else {
    // Display the form (first time around)
?>
<!DOCTYPE html>
    <html lang="en">
        <head>
            <title>New Installation</title>
            <link rel="stylesheet" type="text/css" href="../style.css">
        </head>
    <body><h1>New Installation</h1>

    <table border=0 cellspacing=7 cellpadding=0>
    <form name="configForm" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
        <input type="hidden" name="step" value="2"/>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Host</span></td>
            <td><input required type="text" name="dbhost" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Name</span></td>
            <td><input required type="text" name="dbname" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database User</span></td>
            <td><input required type="text" name="dbuser" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Password</span></td>
            <td><input required type="text" name="dbpassword" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Table Prefix</span></td>
            <td><input type="text" name="dbtableprefix" size="25" value=""/></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="submit" value="Submit"/></td>
        </tr>
    </form>
    </table>
    </body></html>
<?
}
// vim: set tags+=../../**/tags :
?>
