<?php /* Interface for resetting the password
    Copyright (C) 2023 Jesse Jacobsen

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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */
require("./init.php");

$serverdir = $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

if ( $_POST ) {
    if ( array_key_exists('cancel', $_POST) ) {
        setMessage("Password reset cancelled.");
        header("Location: index.php");
        exit(0);
    }
    $where = "";
    if ($_POST['username']) {
        $svalue = $_POST['username'];
        $where .= "`username` = :value"; }
    if ($_POST['email']) {
        if ($where) { $where .= " AND "; }
        $svalue = $_POST['email'];
        $where .= "`email` = :value";
    }
    $q = $db->prepare("SELECT `email`, `username`
        FROM `{$db->getPrefix()}users` WHERE {$where}");
    $q->bindParam(':value', $svalue);
    $q->execute();
    if (! $result = $q->fetchAll(PDO::FETCH_ASSOC)) {
        setMessage("No matching user found.");
        header("Location: index.php");
        exit(0);
    }
    foreach ($result as $row) {
        $resetkey = md5($row['username'].date('%c').$row['email']);
        $q1 = $db->prepare("UPDATE `{$db->getPrefix()}users`
            SET `resetkey` = '{$resetkey}',
            `resetexpiry` = DATE_ADD(NOW(),INTERVAL 6 DAY)
            WHERE {$where}");
        $q1->bindParam(':value', $svalue);
        $q1->execute();
        $resetkey = urlencode($resetkey);
        $mailresult = mail($to=$row['email'],
            $subject="Password reset for {$db->getPrefix()} services at {$_SERVER['HTTP_HOST']}",
            $message=<<<EOM
Someone has requested to reset your password in the {$db->getPrefix()} service planning
application at {$_SERVER['HTTP_HOST']}.  If it was not you, you can safely
ignore this message.

To reset the password for {$row['username']}, use this link:
{$protocol}://{$serverdir}/useradmin.php?flag=reset&auth={$resetkey}

The link will expire after six days.
EOM
        , $additional_headers="From: noreply@{$_SERVER['HTTP_HOST']}"
        );
        if (! $mailresult) {
            setMessage("Problem sending password reset email to {$row['email']}");
        } else {
            setMessage("Password reset message has been sent.");
        }
        header("Location: index.php");
    }

} else {
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Reset Password");?>
<body>
<h1>Reset Password</h1>

<p class="explanation">When you submit this page with your username or email
address, an email will be sent to the address of a matching user.  This message
will contain a link that will allow the recipient to reset that user's
password.  The link will expire after six days.</p>

<table>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
        <tr>
            <td><label for="username">User name</label></td>
            <td><input type="text" name="username"></td>
        </tr>
        <tr>
            <td><label for="email">Email</label></td>
            <td><input type="email" name="email"></td>
        </tr>
        <tr><td colspan="2" align="right">
        <button type="submit" name="submit"
            value="Find and Send">Find and Send</button>
        <button type="submit" name="cancel"
            value="Cancel">Cancel</button>
        </td>
        </tr>
</form>
</table>
</body></html>
<?
}
?>
