<?php /* Create initial user
    Copyright (C) 2014 Jesse Jacobsen

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

if ($_GET['flag'] == 'inituser') {
    $db->beginTransaction();
    // Check that the table is really empty.
    $q = $db->query("SELECT `username` from `{$db->getPrefix()}users`
                LIMIT 1");
    if ($q && $q->fetch()) {
        $db->rollBack();
        die("Access denied.  Users already exist.");
    }
    // Save the posted user
    $q = $db->prepare("INSERT INTO `{$db->getPrefix()}users`
        SET `username`=:username, `password`=:pw,
        `fname`=:fname, `lname`=:lname,
        `userlevel`=:ulevel, `email`=:email");
    $q->bindParam(':username', $_POST['username']);
    $q->bindParam(':fname', $_POST['fname']);
    $q->bindParam(':lname', $_POST['lname']);
    $q->bindParam(':ulevel', $_POST['ulevel']);
    $q->bindParam(':email', $_POST['email']);
    $q->bindParam(':pw', hashPassword($_POST['pw']));
    $q->execute() or die(array_pop($q->errorInfo()));
    $db->commit();
    session_destroy();
    require("./setup-session.php");
    auth($_POST['username'], $_POST['pw']);
    $dbstate->set('has-user', 1);
    $dbstate->save() or die("Problem saving dbstate file.");
    setMessage("Initial user has been set up.");
} else {
?>
<!DOCTYPE html>
<html lang=en>
<?=html_head("Initial User")?>
<body>
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
<h1>Set Up Initial User</h1>
<table>
<form action="<?=$_SERVER['PHP_SELF']?>?flag=inituser" method="post">
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
<? } ?>
