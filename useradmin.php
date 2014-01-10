<? /* Interface for user administration
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
require('./init.php');

$flag = $_GET['flag'];
$authdata = $_SESSION[$sprefix]['authdata'];
$auth = auth();

if ( $flag=="edit" ) {
    adminOnly($authdata['userlevel']);
    $id = $_GET['id'];
    $q = $db->prepare("SELECT * FROM `{$db->getPrefix()}users` WHERE `uid`=:id");
    $q->bindParam(":id", $id);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    editUserForm($row, "Edit");
} elseif ( $flag=="update" ) {
    adminOnly($authdata['userlevel']);
    $uname = $_POST['username'];
    $updatesession = false;
    $pwstr = "";
    if (array_key_exists("pw", $_POST) && $_POST['pw']) {
        $pwstr = "`password`=:pw,";
        $newpw = $_POST['pw'];
    }
    $ulevel = $_POST['userlevel'];
    $fname = htmlentities($_POST['fname']);
    $lname = htmlentities($_POST['lname']);
    $email = $_POST['email'];
    $uid = intval($_POST['uid']);
    $q = $db->prepare("UPDATE `{$db->getPrefix()}users` SET {$pwstr}
        `fname`=:fname, `lname`=:lname, `userlevel`=:ulevel,
        `email`=:email WHERE `username`=:uname");
    $q->bindParam(':fname', $fname);
    $q->bindParam(':lname', $lname);
    $q->bindParam(':ulevel', $ulevel);
    $q->bindParam(':email', $email);
    $q->bindParam(':uname', $uname);
    if ($pwstr) $q->bindParam(':pw', hashPassword($_POST['pw']));
    $q->execute();
    if ( $uname==$authdata['login'] ) {
        if ($newpw) auth($uname, $newpw);
        else {
            $q = $db->prepare("SELECT password FROM `{$db->getPrefix()}users`
                WHERE `uid` = :uid");
            $q->bindParam(':uid', $uid);
            $q->execute();
            if ($pwhash = $q->fetchColumn(0)) {
                $_SESSION[$sprefix]["authdata"] = array_merge(
                    $_SESSION[$sprefix]["authdata"],
                    array(
                        "fullname"=>"$fname $lname",
                        "login"=>$uname,
                        "password"=>$pwhash,
                        "userlevel"=>$ulevel));
                $auth = auth();
            }
        }
    }
    header("location:useradmin.php");
} elseif ( $flag=="delete" ) {
    adminOnly($authdata['userlevel']);
    $id = $_GET['id'];
    if ($authdata['uid'] != $id) {
        $q = $db->prepare("DELETE FROM `{$db->getPrefix()}users`
            WHERE `uid`=:id");
        $q->bindParam(':id', $id);
        $q->execute();
    }
    header("location:useradmin.php");
} elseif ( $flag=="add" ) {
    adminOnly($authdata['userlevel']);
    editUserForm();
} elseif ( $flag=="insert" ) {
    $ulevel = intval($_POST['userlevel']);
    if ($ulevel > 0 && $auth < 3) {
        setMessage("Access Denied");
        header("location:index.php");
        exit(0);
    }
    $uname = $_POST['username'];
    $fname = htmlentities($_POST['fname']);
    $lname = htmlentities($_POST['lname']);
    $email = $_POST['email'];
    $db->beginTransaction();
    // Check for existing user name
    $qu = $db->prepare("SELECT * FROM `{$db->getPrefix()}users`
        WHERE `username`=:uname");
    $qu->bindParam(":uname", $uname);
    $qu->execute();
    $unameerror = $emailerror = "";
    if ( $qu->rowCount() ) {
        $unameerror = $uname;
    }
    // Check for existing email address
    $qe = $db->prepare("SELECT * FROM `{$db->getPrefix()}users`
        WHERE `email`=:email");
    $qe->bindParam(":email", $email);
    $qe->execute();
    if ( $qe->rowCount() ) {
        $emailerror = $email;
    }
    if ( $unameerror || $emailerror ) {
        $db->rollBack();
        $elementValues = array("", "", "", $fname, $lname, $ulevel, $email);
        editUserForm($elementValues, "Add", $unameerror, $emailerror);
    } else {
        $q = $db->prepare("INSERT INTO {$db->getPrefix()}users
            SET `username`=:uname, `password`=:pw, `fname`=:fname,
            `lname`=:lname, `userlevel`=:ulevel, `email`=:email");
        $q->bindParam(":uname", $uname);
        $q->bindParam(":pw", hashPassword($_POST['pw']));
        $q->bindParam(":fname", $fname);
        $q->bindParam(":lname", $lname);
        $q->bindParam(":ulevel", $ulevel);
        $q->bindParam(":email", $email);
        $q->execute();
        header("location:useradmin.php");
        $db->commit();
    }
} elseif ( $flag=="add" ) {
    editUserForm();
} elseif ( $flag=="changepw" || $flag=="reset") {
    if (! array_key_exists('auth', $_GET)
        || $flag == "changepw") authOnly($authdata['userlevel']);
    changePW($flag);
} elseif ($flag=="updatepw" && array_key_exists('auth', $_POST)) {
    // Password reset
    $q = $db->prepare("UPDATE `{$db->getPrefix()}users` SET `password`=:pw,
        `resetkey`=DEFAULT
        WHERE `resetkey`=:resetkey AND `resetexpiry` >= NOW()");
    $q->bindParam(':resetkey', $_POST['auth']);
    $q->bindParam(':pw', hashPassword($_POST['pw']));
    $q->execute();
    if ($q->rowCount()) {
        setMessage("Password changed.");
    } else {
        setMessage("Problem changing password");
    }
    header("Location: index.php");
    exit(0);
} elseif ( $flag=="updatepw" ) {
    // Password change
    authOnly($authdata['userlevel']);
    $db->beginTransaction();
    $q = $db->prepare("SELECT `password` FROM `{$db->getPrefix()}users`
        WHERE `uid`=:id");
    $q->bindParam(':id', $_POST['id']);
    $q->execute();
    $currentpw = $q->fetchColumn(0);
    $q = $db->prepare("UPDATE `{$db->getPrefix()}users` SET `password`=:pw
        WHERE `uid`=:id AND `password`=:oldpw");
    $q->bindParam(':id', $_POST['id']);
    $q->bindParam(':pw', hashPassword($_POST['pw']));
    $compare = crypt($_POST['oldpw'], $currentpw);
    $q->bindParam(':oldpw', $compare);
    $q->execute();
    if (! $q->rowCount()) {
        $db->rollBack();
        setMessage("Problem saving new password. ".$q->queryString);
    } else {
        $db->commit();
        $_SESSION[$sprefix]['authdata']['password'] = $pw;
        setMessage("Password changed.");
    }
    header("Location: index.php");
    exit(0);
} elseif ( $flag=="deleteme" ) {
    authOnly($authdata['userlevel']);
    $id = $_GET['id'];
    if ($authdata['uid'] == $id) {
        $q = $db->prepare("DELETE FROM `{$db->getPrefix()}users`
            WHERE `uid`=:id");
        $q->bindParam(':id', $id);
        $q->execute();
    }
} elseif ( $flag=="inituser") {
    $db->beginTransaction();
    // Check that the table is really empty.
    $q = $db->query("SELECT `username` from `{$db->getPrefix()}users`
                LIMIT 1");
    if ($q->fetch()) {
        $db->rollBack();
        setMessage("Access denied.  Users already exist.");
        header("Location: index.php");
        exit(0);
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
    $q->execute() or dieWithRollback($q);
    $db->commit();
    session_destroy();
    require("./setup-session.php");
    auth($_POST['username'], $_POST['pw']);
    $dbstate = new Configfile("../dbstate.ini", false);
    $dbstate->set('has-user', 1);
    $dbstate->save() or die("Problem saving dbstate file.");
    setMessage("Initial user has been set up.");
    header("Location: index.php");
} elseif ($authdata['userlevel'] == 3) {
    checkPasswordAuth();
    userList();
} else {
    setMessage("Access denied.");
    header("Location: index.php");
    exit(0);
}

/***************************************
******** user admin functions **********
***************************************/

function changePW($flag) {
    global $sprefix;
    $db = new DBConnection();
    $dbp = $db->getPrefix();

    if ($flag=="reset") { // password reset request
        $q = $db->prepare("SELECT `uid`, `username` FROM `{$dbp}users`
            WHERE `resetkey` = :resetkey
            AND `resetexpiry` >= NOW() LIMIT 1");
        $q->bindParam(':resetkey', $_GET['auth']);
        $q->execute();
        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $username = $row['username'];
            $id = $row['uid'];
        } else {
            setMessage("Invalid or expired reset authorization.");
            header ("Location: index.php");
            exit(0);
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
    <form id="pwform" action="useradmin.php?flag=updatepw"
        autocomplete="off" method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <? if ($flag=="reset") { ?>
    <input type="hidden" name="auth" value="<?= $_GET['auth'] ?>">
    <? } ?>
    <table>
    <tr>
        <td></td>
        <td><?=htmlentities($username)?></td>
    </tr>
    <? if ($flag=="changepw") { ?>
    <tr>
        <td align="right"><label for="oldpw">Old Password</label></td>
        <td><input id="oldpw" type="password" name="oldpw" value="" required></td>
    </tr>
    <? } ?>
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
            <button id="submit" type="submit" disabled
                value="Change Password">Change Password</button>
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
        $uid = $elementValues['uid'];
        $username = $elementValues['username'];
        $password = ""; // $elementValues['password'];
        $fname = $elementValues['fname'];
        $lname = $elementValues['lname'];
        $userlevel = $elementValues['userlevel'];
        $email = $elementValues['email'];
        $title = "Edit User";

        $userlevel_selected = array(
            0 => ($userlevel == 0) ? "selected" : "",
            1 => ($userlevel == 1) ? "selected" : "",
            2 => ($userlevel == 2) ? "selected" : "",
            3 => ($userlevel == 3) ? "selected" : "");
        if ($username == $authdata['login']) { $editorstr=$userstr = ""; }
        $flag = "update";
        $rdonly = " readonly ";
    } else {
        $username=$password=$fname=$lname=$userlevel=$email="";
        $userlevel_selected = array(0 => "", 1 => "", 2 => "", 3 => "");
        $title = "Register User";
        $flag = "insert";
        $rdonly = "";
    }
?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head($title)?>
    <body>
        <? passwordFormManagement($mode); ?>
    </script>
    <h1><?=$title?></h1>
    <table>
    <form id="userform" action="useradmin.php?flag=<?=$flag?>"
        method="post" autocomplete="off">
    <input type="hidden" name="uid" value="<?=$uid?>">
    <tr>
        <td align="right"><label for="username">User name</label></td>
        <td><input type="text" maxlength="15" name="username"
            value="<?=$username?>" required <?=$rdonly?>></td>
    </tr>
    <tr>
        <td align="right"><label for="pw">Password</label></td>
        <td><input id="pw" type="password" name="pw" value="" <?=($mode=="Add")?"required":"placeholder=\"Unchanged\""?>></td>
    </tr>
    <tr>
        <td align="right"><label for="pwconfirm">Confirm password</label></td>
        <td><input id="pwconfirm" type="password" name="pwconfirm" value="" <?=($mode=="Add")?"required":"placeholder=\"Unchanged\""?>></td>
    </tr>
    <? if ($authdata['userlevel'] == 3) { ?>
    <tr>
        <td align="right"><label for="userlevel">User Level</label></td>
        <td><select name="userlevel">
            <option value="1" <?=$userlevel_selected[1]?>>User</option>
            <option value="2" <?=$userlevel_selected[2]?>>User</option>
            <option value="3" <?=$userlevel_selected[3]?>>Admin</option>
            </select>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td align="right"><label for="fname">First name</label></td>
        <td><input type="text" name="fname" maxlength="20" value="<?=$fname?>" required></td>
    </tr>
    <tr>
        <td align="right"><label for="lname">Last name</label></td>
        <td><input type="text" name="lname" maxlength="30" value="<?=$lname?>"></td>
    </tr>
    <tr>
        <td align="right"><label for="email">Email</label></td>
        <td><input type="email" name="email" maxlength="40" value="<?=$email?>" required></td>
    </tr>

    <tr>
    <td colspan="2" align="right">
    <button id="submit" type="submit" <?=$mode=="Add"?"disabled":""?>
            value="<?=$mode?> User"><?=$mode?> User</button>
        </td>
    </tr>
    </form>
    </table>

    </body></html>
<?
}


function userList() {
    global $authdata;
    $db = new DBConnection();
    $dbp = $db->getPrefix();
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
        <a class="menulink" href="useradmin.php?flag=add">Add User</a> <a  class="menulink" href="index.php">Return to Services</a>
    </nav>
    </header>

    <div id="content-container">

    <table id="userlist">
    <tr class="headings">
        <td>User Name</td>
        <td>Name</td>
        <td>Email</td>
        <td>User Level</td>
        <td>Edit</td>
        <td>Delete</td>
    </tr>
<?
    $q = $db->query("SELECT * FROM `{$dbp}users`");
    while( $row = $q->fetch() ) {
        //$userlevel = ($row[5] == 2) ? __('admin') : __('editor');
        $userlevel = $row[5];
?>
        <tr>
            <td><?=htmlentities($row[1])?></td>
            <td><?=$row[3]?> <?=$row[4]?></td>
            <td><?=htmlentities($row[6])?></td>
            <td><?=$userlevel?></td>
            <td><a href="useradmin.php?flag=edit&id=<?=$row[0]?>">Edit</a></td>
            <td><a href="#" onClick="deleteConfirm('<?=$row[1]?>', '<?=$row[0]?>');">Delete</a></td>
        </tr>
<?
    }
    ?> </table>
    </div>
    </body>
    </html><?
}

function passwordFormManagement($mode="") {
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
        $("#pwconfirm").keyup(function() {
            if ($("#pwconfirm").val() == $("#pw").val()) {
                $("#submit").attr("disabled", false);
            } else {
                $("#submit").attr("disabled", true);
            }
        });
        $("#pw").keyup(function() {
            if ($("#pwconfirm").val() == $("#pw").val()) {
                $("#submit").attr("disabled", false);
            } else {
                $("#submit").attr("disabled", true);
            }
            <? if ($mode == "Edit") { ?>
            if ($("#pw").val()) {
                $("#pwconfirm").attr("required", true);
            } else {
                $("#pwconfirm").attr("required", false);
            }
            <? } ?>
        });
    });
    </script>
    <?
}

function hashPassword($pw) {
    $saltchars = explode(' ', '. / 0 1 2 3 4 5 6 7 8 9 A B C D E F G H I J K L M N O P Q R S T U V W X Y Z a b c d e f g h i j k l m n o p q r s t u v w x y z');
    $randexes = array_rand($saltchars, 22);
    $saltarray = array();
    foreach ($randexes as $r) $saltarray[] = $saltchars[$r];
    $salt = implode("", $saltarray);
    $algo = '$2a'; // Blowfish
    $cost = '$07';
    return crypt($pw, crypt($pw, "$algo$cost\$$salt\$"));
}

function adminOnly($authlevel) {
    checkPasswordAuth();
    if ($authlevel != 3) {
        setMessage("Access Denied");
        header("Location: index.php");
        exit(0);
    }
}

function authOnly($authlevel) {
    checkPasswordAuth();
    if (! is_numeric($authlevel)) {
        setMessage("Access Denied");
        header("Location: index.php");
        exit(0);
    }
}

function checkPasswordAuth() {
    global $sprefix;
    if ('password' != $_SESSION[$sprefix]['authdata']['authtype']) {
        authcookie(False);
        session_destroy();
        require("./setup-session.php");
        setMessage("Please authenticate your identity and try again.");
        header("location: index.php");
        exit(0);
    }
}

// vim: set tags+=../../**/tags :
?>
