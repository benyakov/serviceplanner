<?
require('./init.php');

$flag = $_GET['flag'];
$authdata = $_SESSION[$sprefix]['authdata'];
$serverdir = $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$auth = auth();
$_ = '__';

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
        changePW($_GET['auth']);
    } elseif ($flag=="updatepw" && array_key_exists('auth', $_POST)) {
        $pw = md5($_POST['pw']);
        $q = $dbh->prepare("UPDATE `{$dbp}users` SET `password`='$pw',
            `resetkey`=DEFAULT
            WHERE `resetkey`=:resetkey AND `resetexpiry` >= NOW()");
        $q->bindParam(':resetkey', $_POST['auth']);
        $q->execute();
        if ($q->fetch()) {
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

function changePW($authcode="") {
    global $dbp, $dbh;

    if ($authcode) { // password reset request
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
    <html lang="en"><head>
    <title><?=__('changepw')?></title>
    <link rel="stylesheet" type="text/css" href="css/adminpgs.css">
    <script type=text/javascript>
    function validate(f, next) {
        var regex = /\W+/;
        var pw = f.pw.value;
        var str = "";
        if (pw == "") { str += "\n<?=__('pwblank')?>"; }
        if (pw != f.pwconfirm.value) { str += "\n<?=__('pwmatch')?>"; }
        if (pw.length < 4) { str += "\n<?=__('pwlength')?>"; }
        if (regex.test(pw)) { str += "\n<?=__('pwchars')?>"; }

        if (str == "") {
            f.method = "post";
            f.action = "http://<?=$serverdir?>/useradmin.php?flag="+next;
            f.submit();
        } else {
            alert(str);
            return false;
        }
    }
    </script>
    </head></body>
    <form onSubmit="return validate(this, 'updatepw');">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="un" value="<?= $username ?>">
    <input type="hidden" name="auth" value="<?= $authcode ?>">
    <table cellpadding="2" cellspacing="2" border="0">
    <tr>
        <td colspan="2" class="user-edit-header"><span class="edit_user_header"><?=__('chpassheader')?></span></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('username')?>:</span></td>
        <td><span class="edit_user_label"><?=$username?></span></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('password')?>:</span></td>
        <td><input type="password" name="pw" size="29" maxlength="25" value=""></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('pwconfirm')?>:</span></td>
        <td><input type="password" name="pwconfirm" size="29" maxlength="25" value=""></td>
    </tr>
    <tr>
        <td colspan="2" align="right"><input type="submit" value="<?=__('changepw')?>">
        &nbsp;  <input type="button" value="<?=__('cancel')?>" onClick="location.replace('index.php');">
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

        $header = __('edituser');

        $userlevel_selected = array(
            0 => ($userlevel == 0) ? "selected" : "",
            1 => ($userlevel == 1) ? "selected" : "",
            2 => ($userlevel == 2) ? "selected" : "",
            3 => ($userlevel == 3) ? "selected" : "");

        $formaction = "f.action = \"useradmin.php?flag=update\";";
        $unameinput = "<span class=\"edit_user_label\">{$username}</span><input type=\"hidden\" name=\"username\" value=\"{$username}\">\n";

        if ($username == $authdata['login']) { $editorstr=$userstr = ""; }

    } else {

        $username=$password=$fname=$lname=$userlevel=$email="";
        $header = __('adduser');
        $formaction = "f.action = \"useradmin.php?flag=insert\";";
        $unameinput = "<input type=\"text\" name=\"username\" size=\"29\" maxlength=\"20\" value=\"\">";
        $userlevel_selected = array(0 => "", 1 => "", 2 => "", 3 => "");

    }
?>
    <!DOCTYPE html>
    <html lang="en"><head>
    <title>Flexical:  <?=$mode?> Calendar User</title>
    <link rel="stylesheet" type="text/css" href="css/adminpgs.css">

    <script language="JavaScript">

        function validate(f) {
            var regex = /\W+/;
            var un = f.username.value;
            var pw = f.pw.value;

            var str = "";
            if (f.fname.value == "") { str += "\n<?=__('fnameblank')?>"; }
            if (f.lname.value == "") { str += "\n<?=__('lnameblank')?>"; }
            if (f.email.value == "") { str += "\n<?=__('emailblank')?>"; }
            if (un == "") { str += "\n<?=__('unameblank')?>"; }
            if (un.length < 4) { str += "\n<?=__('unamelength')?>"; }
            if (regex.test(un)) { str += "\n<?=__('unameillegal')?>"; }
            if (pw != "<?=__('no change')?>") {
                if (pw == "") { str += "\n<?=__('pwblank')?>"; }
                if (pw != f.pwconfirm.value) { str += "\n<?=__('pwmatch')?>"; }
                if (pw.length < 4) { str += "\n<?=__('pwlength')?>"; }
                if (regex.test(pw)) { str += "\n<?=__('pwchars')?>"; }
            }

            if (str == "") {
                f.method = "post";
                <?= $formaction ?>
                f.submit();
            } else {
                alert(str);
                return false;
            }
        }

    </script>
    </head><body>

<?
    if ( !empty($unameerror) ) {
        echo "<p><span class=\"bad_user_name\">" . __('userinuse') . "</span></p>";
    }
    if ( !empty($emailerror) ) {
        echo "<p><span class=\"bad_user_name\">" . __('emailinuse') . "</span></p>";
    }
?>
    <form onSubmit="return validate(this);">
    <table cellpadding="2" cellspacing="2" border="0">
    <tr>
        <td colspan="2" class="user-edit-header"><span class="edit_user_header"><?=$header?>:</span></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('username')?>:</span></td>
        <td><?=$unameinput?></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('password')?>:</span></td>
        <td><input type="password" name="pw" size="29" maxlength="20" value="<?=($mode=="Add")?"":__('no change')?>"></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('pwconfirm')?>:</span></td>
        <td><input type="password" name="pwconfirm" size="29" maxlength="20" value="<?=($mode=="Add")?"":__('no change')?>"></td>
    </tr>
    <? if ($authdata['userlevel'] == 3) { ?>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('userlevel')?>:</span></td>
        <td><select name="userlevel">
            <option value="0" <?=$userlevel_selected[0]?>>
                <?=__('subscriberoption')?></option>
            <option value="1" <?=$userlevel_selected[1]?>>
                <?=__('useroption')?></option>
            <option value="2" <?=$userlevel_selected[2]?>>
                <?=__('editoroption')?></option>
            <option value="3" <?=$userlevel_selected[3]?>>
                <?=__('adminoption')?></option>
            </select>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('fname')?>:</span></td>
        <td><input type="text" name="fname" size="29" maxlength="20" value="<?=$fname?>"></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('lname')?>:</span></td>
        <td><input disable type="text" name="lname" size="29" maxlength="30" value="<?=$lname?>"></td>
    </tr>
    <tr>
        <td align="right"><span class="edit_user_label"><?=__('email')?>:</span></td>
        <td><input type="text" name="email" size="29" maxlength="40" value="<?=$email?>"></td>
    </tr>

    <tr>
        <td colspan="2" align="right"><input type="submit" value="<?=$mode?> User">
        &nbsp;  <input type="button" value="cancel" onClick="location.replace('useradmin.php');">
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
    <html lang="en"><head><title>User List</title>
    <link rel="stylesheet" type="text/css" href="styles.css">

    <script language="JavaScript">
        function deleteConfirm(user, uid) {
            var msg = "<?=__('deleteconf')?>: \"" + user + "\"?";

            if (user == "<?= $authdata['login'] ?>") {
                alert("<?=__('deleteown')?>");
                return;
            } else if (confirm(msg)) {
                location.replace("useradmin.php?flag=delete&id=" + uid);
            } else {
                return;
            }
        }
    </script>
    </head>

    <body>
    <table cellpadding="0" cellspacing="0" border="0" width="600">
    <tr>
        <td class="user-edit-header"><span class="edit_user_header"><?=__('ulistheader')?></span></td>
        <td align="right" valign="bottom"><span class="user_list_options">[ <a href="useradmin.php?flag=add"><?=__('adduser')?></a> | <a href="index.php"><?=__('return')?></a> ]</span></td>
    </tr>
    </table>

    <table cellpadding="0" cellspacing="0" border="0" width="600" bgcolor="#000000">
    <tr><td>

    <table cellspacing="1" cellpadding="3" border="0" width="100%">
    <tr bgcolor="#666666">
        <td><span class="user_table_col_label"><?=__('username')?></span></td>
        <td><span class="user_table_col_label"><?=__('name')?></span></td>
        <td><span class="user_table_col_label"><?=__('email')?></span></td>
        <td><span class="user_table_col_label"><?=__('userlevel')?></span></td>
        <td><span class="user_table_col_label"><?=__('edit')?></span></td>
        <td><span class="user_table_col_label"><?=__('delete')?></span></td>
    </tr>

<?
    $q = $dbh->query("SELECT * FROM `{$dbp}users`");
    $bgcolor = "#ffffff";

    while( $row = $q->fetch() ) {
        //$userlevel = ($row[5] == 2) ? __('admin') : __('editor');
        $userlevel = $row[5];

        echo "<tr bgcolor=\"$bgcolor\">\n";
        echo "  <td><span class=\"user_table_txt\">{$row[1]}</td>\n";
        echo "  <td><span class=\"user_table_txt\">{$row[3]} {$row[4]}</span></td>\n";
        echo "  <td><span class=\"user_table_txt\">{$row[6]}</span></td>\n";
        echo "  <td><span class=\"user_table_txt\">{$userlevel}</span></td>\n";
        echo "  <td><span class=\"user_table_txt\"><a href=\"useradmin.php?flag=edit&id=" . $row[0] . "\">" . __('edit') . "</a></span></td>\n";
        echo "  <td><span class=\"user_table_txt\"><a href=\"#\" onClick=\"deleteConfirm('{$row[1]}', '{$row[0]}');\">".__('delete')."</a></span></td>\n";
        echo "</tr>\n";

    if ( $bgcolor == "#ffffff" )
        $bgcolor = "#dddddd";
    else
        $bgcolor = "#ffffff";
    }

    echo "</table></td></tr></table>";
}
// vim: set tags+=../../**/tags :
?>
