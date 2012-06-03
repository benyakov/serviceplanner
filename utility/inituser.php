<? /* Create initial user
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
require("../db-connection.php");
$serverdir = dirname(dirname($_SERVER['PHP_SELF']));
// Test the existence of a table
$q = $dbh->query("SHOW TABLES LIKE '{$dbp}days'");
if (!$q->rowCount()) {
    header("Location: setupdb.php");
}
?>
<!DOCTYPE html>
<html lang=en>
<head>
<title>Set Up Initial User</title>
<link rel="stylesheet" type="text/css" href="../style.css">
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<script type="text/javascript" src="http://<?=$_SERVER['SERVER_NAME'].$serverdir?>/ecmascript.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $("#formsubmit").attr('disabled', true);
    $("#password2").keyup(function () {
        if ($(this).val() == $("#password").val() &&
            $(this).val() != "") {
            $("#formsubmit").attr('disabled', false);
        } else {
            $("#formsubmit").attr('disabled', true);
        }

    });
});
</script>
</head>
<body>
<h1>Set Up Initial User</h1>
<table>
<form action="../useradmin.php?flag=inituser" method="post">
        <input type="hidden" name="ulevel" value="3"/>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="fname">First Name</label></td>
            <td><input type="text" name="fname"></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="lname">Last Name</label></td>
            <td><input type="text" name="lname" ></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="email">Email</label></td>
            <td><input type="email" name="email" required></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="username">User Name</label></td>
            <td><input type="text" name="username" required></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="password">Password</label></td>
            <td><input id="password" type="password" name="pw" required></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="password2">Repeat Password</label></td>

            <td><input id="password2" type="password" name="pwconfirm" required></td>
        </tr>
        <tr><td colspan="2" align="right">
            <button id="formsubmit" type="submit" value="Submit">Submit</button>
        <td><tr>
</form>
</table>
</body>
</html>
