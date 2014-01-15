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

if ($_GET['flag'] == 'inituser') { # This IS the entry point, no init.php
    require("../setup-session.php");
    require("./dbconnection.php");
    $db = new DBConnection("..");
    $db->beginTransaction();
    echo "Starting process.";
    // Check that the table is really empty.
    $q = $db->query("SELECT `username` from `{$db->getPrefix()}users`
                LIMIT 1");
    echo "After empty table check";
    if ($q->fetch()) {
        echo "Check failed, rolling back.";
        $db->rollBack();
        die("Access denied.  Users already exist.");
    }
    // Save the posted user
    $q = $db->prepare("INSERT INTO `{$db->getPrefix()}users`
        SET `username`=:username, `password`=:pw,
        `fname`=:fname, `lname`=:lname,
        `userlevel`=:ulevel, `email`=:email");
    echo "Statement prepared.";
    $q->bindParam(':username', $_POST['username']);
    $q->bindParam(':fname', $_POST['fname']);
    $q->bindParam(':lname', $_POST['lname']);
    $q->bindParam(':ulevel', $_POST['ulevel']);
    $q->bindParam(':email', $_POST['email']);
    $q->bindParam(':pw', hashPassword($_POST['pw']));
    echo "Params bound";
    $q->execute() or die(array_pop($q->errorInfo()));
    echo "Executed user insert.";
    $db->commit();
    echo "Committed user to db.";
    session_destroy();
    require("../setup-session.php");
    require("../authfunctions.php");
    auth($_POST['username'], $_POST['pw']);
    require("./configfile.php");
    $dbstate = new Configfile("../dbstate.ini", false);
    $dbstate->set('has-user', 1);
    $dbstate->save() or die("Problem saving dbstate file.");
    require("../functions.php");
    setMessage("Initial user has been set up.");
    header("Location: index.php");
    exit(0);
}

// Test the existence of a table
$q = $db->query("SHOW TABLES LIKE '{$db->getPrefix()}days'");
if (!$q->rowCount()) {
    require("./utility/setupdb.php");
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
<form action="utility/inituser.php?flag=inituser" method="post">
        <input type="hidden" name="ulevel" value="3"/>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="fname">First Name</label></td>
            <td><input type="text" maxlength="20" name="fname"></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="lname">Last Name</label></td>
            <td><input type="text" maxlength="30" name="lname" ></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="email">Email</label></td>
            <td><input type="email" maxlength="40" name="email" required></td>
        </tr>
        <tr>
            <td nowrap valign="top" align="right" nowrap>
            <label for="username">User Name</label></td>
            <td><input type="text" maxlength="15" name="username" required></td>
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
