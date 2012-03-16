<?
require('./init.php');

$flag = $_GET['flag'];
$authdata = $_SESSION[$sprefix]['authdata'];
$serverdir = $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$auth = auth();

if ( $auth == 3 ) {
    if ( $flag=="edit" ) {
        $id = $_GET['id'];
        $q = $dbh->prepare("SELECT * FROM `{$dbp}users` WHERE `uid`=:id");
        $q->bindParam(":id", $id);
        $q->execute();
        $row = $q->fetch();
        editUserForm($row, "Edit");
    } elseif ( $flag=="update" ) {
        $uname = $_POST['username'];
        if ($_POST['pw'] == __('no change'))
            { $pwstr = ''; }
        else
            { $pwstr = "`password`='".md5($_POST['pw'])."',"; }
        $ulevel = $_POST['userlevel'];
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $email = $_POST['email'];
        $q = $dbh->prepare("UPDATE `{$dbp}users` SET {$pwstr}
            `fname`=:fname, `lname`=:lname, `userlevel`=:ulevel,
            `email`=:email WHERE `username`=:uname");
        $q->bindParam(':fname', $fname);
        $q->bindParam(':lname', $lname);
        $q->bindParam(':ulevel', $ulevel);
        $q->bindParam(':email', $email);
        $q->bindParam(':uname', $uname);
        $q->execute();
        if ( $uname==$authdata['login'] ) {
            $_SESSION[$sprefix]['authdata']['password'] = $pw;
        }
        header("location:useradmin.php");
    } elseif ( $flag=="delete" ) {
        $id = $_GET['id'];
        if ($authdata['uid'] != $id) {
            $q = $dbh->prepare("DELETE FROM `{$dbp}users`
                WHERE `uid`=:id");
            $q->bindParam(':id', $id);
            $q->execute();
        }
        header("location:useradmin.php");
    } else {
        userList();
    }

} elseif ( $flag=="insert" ) {
    $ulevel = intval($_POST['userlevel']);
    if ($ulevel > 0 && $auth < 3) {
        setMessage("Access Denied");
        header("location:index.php");
        exit(0);
    }
    $uname = $_POST['username'];
    $pw = md5($_POST['pw']);
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $dbh->beginTransaction();
    // Check for existing user name
    $qu = $dbh->prepare("SELECT * FROM `{$dbp}users`
        WHERE `username`=:uname");
    $qu->bindParam(":uname", $uname);
    $qu->execute();
    $unameerror = $emailerror = "";
    if ( $qu->rowCount() ) {
        $unameerror = $uname;
    }
    // Check for existing email address
    $qe = $dbh->prepare("SELECT * FROM `{$dbp}users`
        WHERE `email`=:email");
    $qe->bindParam(":email", $email);
    $qe->execute();
    if ( $qe->rowCount() ) {
        $emailerror = $email;
    }
    if ( $unameerror || $emailerror ) {
        $dbh->rollBack();
        $elementValues = array("", "", $pw, $fname, $lname, $ulevel, $email);
        editUserForm($elementValues, "Add", $unameerror, $emailerror);
    } else {
        $q = $dbh->prepare("INSERT INTO {$dbp}users
            SET `username`=:uname, `password`=:pw, `fname`=:fname,
            `lname`=:lname, `userlevel`=:ulevel, `email`=:email");
        $q->bindParam(":uname", $uname);
        $q->bindParam(":pw", $pw);
        $q->bindParam(":fname", $fname);
        $q->bindParam(":lname", $lname);
        $q->bindParam(":ulevel", $ulevel);
        $q->bindParam(":email", $email);
        $q->execute();
        header("location:useradmin.php");
        $dbh->commit();
    }

} elseif ( $flag=="add" ) {
    editUserForm();
} elseif ( is_numeric($auth) ) {
    if ( $flag=="changepw" ) {
        changePW();
    } elseif ( $flag=="updatepw" ) {
        $un = $_POST['un'];
        $pw = md5($_POST['pw']);
        $id = $_POST['id'];
        $q = $dbh->prepare("UPDATE `{$dbp}users` SET `password`='$pw'
            WHERE `uid`=:id");
        $q->bindParam(':id', $id);
        $q->execute();
        $_SESSION[$sprefix]['authdata']['password'] = $pw;
        setMessage("Password changed.");
        header("Location: http://{$serverdir}/index.php");
        exit(0);
    } elseif ( $flag=="deleteme" ) {
        $id = $_GET['id'];
        if ($authdata['uid'] == $id) {
            $q = $dbh->prepare("DELETE FROM `{$dbp}users`
                WHERE `uid`=:id");
            $q->bindParam(':id', $id);
            $q->execute();
        }
    } else {
        header("location:index.php");
    }
} else {
    if ( $flag=="inituser") {
        $dbh->beginTransaction();
        // Check that the table is really empty.
        $q = $dbh->query("SELECT `username` from `{$dbp}users`
                    LIMIT 1");
        if ($q->fetch()) {
            $dbh->rollback();
            setMessage("Access denied.  Users already exist.");
            header("Location: http://{$serverdir}/index.php");
            exit(0);
        }
        // Save the posted user
        $pw = md5($_POST['pw']);
        $q = $dbh->prepare("INSERT INTO `{$dbp}users`
            SET `username`=:username, `password`='{$pw}',
            `fname`=:fname, `lname`=:lname,
            `userlevel`=:ulevel, `email`=:email");
        $q->bindParam(':username', $_POST['username']);
        $q->bindParam(':fname', $_POST['fname']);
        $q->bindParam(':lname', $_POST['lname']);
        $q->bindParam(':ulevel', $_POST['ulevel']);
        $q->bindParam(':email', $_POST['email']);
        $q->execute() or dieWithRollback($q);
        $dbh->commit();
        session_destroy();
        require("./setup-session.php");
        auth($_POST['username'], $_POST['pw']);
        setMessage("Initial user has been set up.");
        header("Location: http://{$serverdir}/index.php");
    } elseif ($flag=="reset" && array_key_exists('auth', $_GET)) {
        changePW();
    } elseif ($flag=="updatepw" && array_key_exists('auth', $_POST)) {
        $pw = md5($_POST['pw']);
        $q = $dbh->prepare("UPDATE `{$dbp}users` SET `password`='$pw',
            `resetkey`=DEFAULT
            WHERE `resetkey`=:resetkey AND `resetexpiry` >= NOW()");
        $q->bindParam(':resetkey', $_POST['auth']);
        $q->execute();
        if ($q->rowCount()) {
            setMessage("Password changed.");
        } else {
            setMessage("Problem changing password");
        }
        header("Location: http://{$serverdir}/index.php");
        exit(0);

    } else {
        setMessage("Access denied.");
        header("Location: http://{$serverdir}/index.php");
    }
}

/***************************************
******** user admin functions **********
***************************************/

function changePW() {
    global $dbp, $dbh;

    if ($_GET['auth']) { // password reset request
        $q = $dbh->prepare("SELECT `uid`, `username` FROM `{$dbp}users`
            WHERE `resetkey` = :resetkey
            AND `resetexpiry` >= NOW() LIMIT 1");
        $q->bindParam(':resetkey', $_GET['auth']);
        $q->execute();
        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $username = $row['username'];
            $id = $row['uid'];
        } else {
            $serverdir = $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            setMessage("Invalid or expired reset authorization.");
            header ("Location: http://{$serverdir}/index.php");
        }
    } else {
        $username = $_SESSION[$sprefix]['authdata']['login'];
        $id = $_SESSION[$sprefix]['authdata']['uid'];
    }
?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("Change Password")?>
    <body>
        <? passwordFormManagement(); ?>
    <h1>Change Password</h1>
    <form id="pwform" action="useradmin.php?flag=updatepw" method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="un" value="<?= $username ?>">
    <input type="hidden" name="auth" value="<?= $_GET['auth'] ?>">
    <table>
    <tr>
        <td></td>
        <td><?=$username?></td>
    </tr>
    <tr>
        <td align="right"><label for="pw">Password</label></td>
        <td><input id="pw" type="password" name="pw" value="" required></td>
    </tr>
    <tr>
        <td align="right"><label for="pwconfirm">Confirm Password</label></td>
        <td><input id="pwconfirm" type="password" name="pwconfirm" value="" required></td>
    </tr>
    <tr>
        <td colspan="2" align="right">
            <button id="submit" type="submit" value="Change Password">Change Password</button>
        </td>
    </tr>
    </table>
    </form>

    </body>
    </html>
<?
}

function editUserForm($elementValues="", $mode="Add",
    $unameerror = "", $emailerror = "") {
    global $authdata;
    if ($mode=="Edit") {
        $username = $elementValues[1];
        $password = ""; // $elementValues[2];
        $fname = $elementValues[3];
        $lname = $elementValues[4];
        $userlevel = $elementValues[5];
        $email = $elementValues[6];

        $userlevel_selected = array(
            0 => ($userlevel == 0) ? "selected" : "",
            1 => ($userlevel == 1) ? "selected" : "",
            2 => ($userlevel == 2) ? "selected" : "",
            3 => ($userlevel == 3) ? "selected" : "");
        if ($username == $authdata['login']) { $editorstr=$userstr = ""; }
    } else {
        $username=$password=$fname=$lname=$userlevel=$email="";
        $userlevel_selected = array(0 => "", 1 => "", 2 => "", 3 => "");
    }
?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("User")?>
    <body>
        <? passwordFormManagement(); ?>
    </script>
    <h1>Edit User</h1>
    <form id="userform" action="useradmin.php" method="post">
    <table>
    <tr>
        <td align="right"><label for="username">User name</label></td>
        <td><input type="text" name="username" value="<?=$username?>"
                required></td>
    </tr>
    <tr>
        <td align="right"><label for="pw">Password</label></td>
        <td><input type="password" name="pw" value="" <?=($mode=="Add")?"required":""?>></td>
    </tr>
    <tr>
        <td align="right"><label for="pwconfirm">Confirm password</label></td>
        <td><input type="password" name="pwconfirm" value="" <?=($mode=="Add")?"required":""?>></td>
    </tr>
    <? if ($authdata['userlevel'] == 3) { ?>
    <tr>
        <td align="right"><label for="userlevel">User Level</label></td>
        <td><select name="userlevel">
            <option value="0" <?=$userlevel_selected[0]?>>User</option>
            <option value="1" <?=$userlevel_selected[1]?>>User</option>
            <option value="2" <?=$userlevel_selected[2]?>>User</option>
            <option value="3" <?=$userlevel_selected[3]?>>Admin</option>
            </select>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td align="right"><label for="fname">First name</label></td>
        <td><input type="text" name="fname" value="<?=$fname?>" required></td>
    </tr>
    <tr>
        <td align="right"><label for="lname">Last name</label></td>
        <td><input type="text" name="lname" value="<?=$lname?>"></td>
    </tr>
    <tr>
        <td align="right"><label for="email">Email</label></td>
        <td><input type="email" name="email" value="<?=$email?>" required></td>
    </tr>

    <tr>
    <td colspan="2" align="right">
        <button id="submit" type="submit" value="<?=$mode?> User"><?=$mode?> User</button>
        </td>
    </tr>
    </table>
    </form>

    </body></html>
<?
}


function userList() {
    global $authdata, $dbp, $dbh;
?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("User List");?>
    <body>
    <script language="JavaScript">
        function deleteConfirm(user, uid) {
            var msg = "Are you sure you want to delete \"" + user + "\"?";
            if (user == "<?= $authdata['login'] ?>") {
                alert("You can't delete yourself.");
                return;
            } else if (confirm(msg)) {
                location.replace("useradmin.php?flag=delete&id=" + uid);
            } else {
                return;
            }
        }
    </script>
    <header>
    <h1>User List</h1>

    <nav>
        [ <a href="useradmin.php?flag=add">Add User</a> | <a href="index.php">Return to Services</a> ]
    </nav>
    </header>

    <table id="userlist">
    <tr class="headings">
        <td>User Name></td>
        <td>Name</td>
        <td>Email</td>
        <td>User Level</td>
        <td>Edit</td>
        <td>Delete</td>
    </tr>
<?
    $q = $dbh->query("SELECT * FROM `{$dbp}users`");
    while( $row = $q->fetch() ) {
        //$userlevel = ($row[5] == 2) ? __('admin') : __('editor');
        $userlevel = $row[5];
?>
        <tr>
            <td><?=$row[1]?></td>
            <td><?=$row[3]?> <?=$row[4]?></td>
            <td><?=$row[6]?></td>
            <td><?=$userlevel?></td>
            <td><a href="useradmin.php?flag=edit&id=<?=$row[0]?>">Edit</a></td>
            <td><a href="#" onClick="deleteConfirm('<?=$row[1]?>', '<?=$row[0]?>');">Delete</a></td>
        </tr>
<?
    }
    ?> </table> <?
}

function passwordFormManagement() {
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
        $("#submit").prop("disabled", true);
        $("#pwconfirm").keyup(function() {
            if ($("#pwconfirm").val() == $("#pw").val()) {
                $("#submit").prop("disabled", false);
            } else {
                $("#submit").prop("disabled", true);
            }
        });
    });
    </script>
    <?
}
// vim: set tags+=../../**/tags :
?>
