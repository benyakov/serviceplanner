<?
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
    $q = $dbh->prepare("SELECT `email`, `username`
        FROM `{$tablepre}users` WHERE {$where}");
    $q->bindParam(':where', $svalue);
    $q->execute();
    if (! $q->fetch()) {
        setMessage("No matching user found.");
        header("Location: index.html");
        exit(0);
    }
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $resetkey = md5($row['username'].date('%c').$row['email']);
        $q1 = $dbh->prepare("UPDATE `{$tablepre}users`
            SET `resetkey` = '{$resetkey}',
            `resetexpiry` = DATE_ADD(NOW(),INTERVAL 6 DAY)
            WHERE {$where}";
        $q1->bindParam(':where', $svalue);
        $q1->execute();
        $resetkey = urlencode($resetkey);
        $mailresult = mail($to=$row['email'], $subject=$__('pwresetsubject'),
            $additional_headers="From: noreply@{$_SERVER['HTTP_HOST']}",
            $message=$__('pwresetmessage').
            "\n\nTo reset the password for {$row['username']}, use this link:\n".
            "http://{$serverdir}/useradmin.php?flag=reset&auth={$resetkey}");
        if (! $mailresult)
            die("Problem sending password reset email to {$row['email']}");
        setMessage("Password reset message has been sent.")
        header("Location: index.html");
    }

} else {
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Reset Password");?>
<body>
<h1>Reset Password</h1>

<p style="explanation">When you submit this page with your username or email
address, an email will be sent to the address of a matching user.  This message
will contain a link that will allow the recipient to reset that user's
password.  The link will expire after six days.</p>

<table>
<form action="<?= $_SERVER['PHP_SELF'] ?>?action=sendpw" method="post">
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
