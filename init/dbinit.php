<?php /* Make sure database connection is initialized.
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
if ($_GET['flag'] == 'dbinit') {
    // Escape string-ending characters to avoid PHP injection
    $post = str_replace('\\', '\\\\', $_POST);
    $post = str_replace('\'', '\\\'', $post);
    // Test connection
    try {
        $handle = new PDO("mysql:host={$post['dbhost']};dbname={$post['dbname']}",
            "{$post['dbuser']}", "{$post['dbpassword']}");
        unset($handle);
    } catch (PDOException $e) {
        header("Location: {$_SERVER['PHP_SELF']}?connectionerror=1");
        exit(0);
    }
    $dbc = new Configfile("./dbconnection.ini", false, true);
    $dbc->set("dbhost", $post['dbhost']);
    $dbc->set("dbname", $post['dbname']);
    $dbc->set("dbuser", $post['dbuser']);
    $dbc->set("dbpassword", $post['dbpassword']);
    $dbc->set("prefix", $post['dbtableprefix']);
    $dbc->save();
    unset($dbc); // Close ini file
    chmod("./dbconnection.ini", 0600);
} else {
    // Display the form (first time around)
?>
<!DOCTYPE html>
    <html lang="en">
    <?=html_head("Initialize Database Connection")?>
    <body>
        <? if ($_GET['connectionerror']) { ?>
        <p id="message">Error: Could not connect with given settings.</p>
        <? } ?>

        <h1>Initialize Database Connection</h1>

    <table border=0 cellspacing=7 cellpadding=0>
    <form name="configForm" method="POST" action="<?=$_SERVER['PHP_SELF']?>?flag=dbinit">
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
    exit(0);
}
?>
