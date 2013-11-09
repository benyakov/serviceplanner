<? /* Library for authentication functions

    Copyright (C) 2013 Jesse Jacobsen

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


function auth($login = '', $passwd = '') {
    global $sprefix;
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
	$authdata = $_SESSION[$sprefix]['authdata'];
    if (is_array($authdata) && empty($login)
        && $authdata["authtype"] == "password")
    {
        $q = $dbh->prepare("SELECT 1 FROM `{$dbp}users`
            WHERE `username` = :login AND `password` = :password
            AND `uid` = :uid AND `userlevel` = :userlevel
            AND CONCAT_WS(' ', `fname`, `lname`) = :fullname");
        $q->bindValue(':login', $authdata["login"]);
        $q->bindValue(':password', $authdata["password"]);
        $q->bindValue(':uid', $authdata["uid"]);
        $q->bindValue(':userlevel', $authdata["userlevel"]);
        $q->bindValue(':fullname', $authdata["fullname"]);
        $q->execute();
        if ($q->fetch()) {
            authcookie(true);
            return true;
        } else return false;
	} elseif (!empty($login)) {
		$check = $login;
        $q = $dbh->prepare("SELECT password, fname, lname,
           username, uid, userlevel FROM `{$dbp}users`
            WHERE `username` = :check");
        $q->bindValue(':check', $check);
        if (! $q->execute()) die(array_pop($q->errorInfo()));
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ( $row["password"] == crypt($passwd, $row["password"]) ) {
                $_SESSION[$sprefix]["authdata"] = array(
                    "fullname"=>"{$row['fname']} {$row['lname']}",
                    "login"=>$check,
                    "password"=>$row["password"],
                    "uid"=>$row["uid"],
                    "userlevel"=>$row["userlevel"],
                    "authtype"=>"password");
            authcookie(true);
            return true;
        } else {
            unset( $_SESSION[$sprefix]['authdata'] );
            setMessage("Passwords don't match.");
            return false;
        }
    } elseif (! authcookie()) {
        unset($_SESSION[$sprefix]['authdata']);
        return false;
    } else return true;
}

function authId($authdata=false) {
    // Return the current username from parameter or session, or false
    global $sprefix;
    $authdata = $authdata?$authdata:
        (isset($_SESSION[$sprefix]['authdata'])?
            $_SESSION[$sprefix]['authdata']:0);
	if ( is_array( $authdata ) ) {
		return $authdata['fullname'];
    } else {
        return false;
    }
}

function authcookie($authorized=null) {
    // Set the authorization cookies, if $authorized or not.
    // Return whether valid auth cookie exists.
    global $sprefix;
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $max_age = getAuthCookieMaxAge();
    if (! file_exists("authcookies")) mkdir("authcookies");
    if (is_null($authorized)) {
        // Check cookie
        if (! (isset($_COOKIE['auth']) &&
            file_exists("authcookies/{$_COOKIE['auth']['user']}")))
            return false;
        $userdir = "authcookies/{$_COOKIE['auth']['user']}";
        // Comb user's auth tokens and remove expired ones
        $userdirp = opendir($userdir);
        while ($seriesfile = readdir($userdirp)) {
            if (in_array($seriesfile, array('.', '..'))) continue;
            if (time() - filemtime("{$userdir}/{$seriesfile}")
                > $max_age)
                unlink("{$userdir}/{$seriesfile}");
        }
        closedir($userdirp);
        // Check against saved auth tokens
        if (! (isset($_COOKIE['auth']['series']) &&
               file_exists("{$userdir}/{$_COOKIE['auth']['series']}")))
            return false;
        $token = file_get_contents("{$userdir}/{$_COOKIE['auth']['series']}");
        if (! $_COOKIE['auth']['token'] == $token) {
            setMessage("Someone has stolen your session. Check your security! Forgetting all of your remembered sessions.");
            return false;
        }
        $q = $dbh->prepare("SELECT fname, lname, username, uid,
            userlevel, password FROM `{$dbp}users`
            WHERE `username` = :check");
        $q->bindValue(':check', $_COOKIE['auth']['user']);
        if (! $q->execute()) die(array_pop($q->errorInfo()));
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $_SESSION[$sprefix]["authdata"] = array(
            "fullname"=>"{$row['fname']} {$row['lname']}",
            "login"=>$_COOKIE['auth']['user'],
            "password"=>$row["password"],
            "uid"=>$row["uid"],
            "userlevel"=>$row["userlevel"],
            "authtype"=>"cookie");
        setAuthCookie($_COOKIE['auth']['user'], $_COOKIE['auth']['series'],
            $max_age);
        return true;
    }
    if ($authorized) {
        if ($_COOKIE['auth']['series']) $series = $_COOKIE['auth']['series'];
        else $series = genCookieAuthString();
        setAuthCookie($_SESSION[$sprefix]["authdata"]["login"], $series,
            $max_age);
    } else {
        delAuthCookie();
    }
    return false;
}

function setAuthCookie($user, $series, $age) {
    if (! file_exists("authcookies/{$user}"))
        mkdir("authcookies/{$user}");
    $token = genCookieAuthString();
    $timestamp = time()+$age;
    setcookie('auth[series]', $series, $timestamp);
    setcookie('auth[token]', $token, $timestamp);
    setcookie('auth[user]', $user, $timestamp);
    file_put_contents("authcookies/{$user}/{$series}", $token);
}

function getAuthCookieMaxAge() {
    // Gets the current maximum age of an auth cookie in seconds.
    global $authcookie_shelf_life;
    $authcookie_max_age = 60*60*24*7;
    if ($authcookie_shelf_life)
        $authcookie_max_age = $authcookie_shelf_life;
    $config = getConfig($false);
    if ($config->exists('authcookie_max_age'))
        $authcookie_max_age = $config->get('authcookie_max_age');
    return $authcookie_max_age;
}

function delAuthCookie() {
    unlink("authcookies/{$_COOKIE['auth']['user']}/{$_COOKIE['auth']['series']}");
    $timestamp = time()-3600;
    setcookie('auth[user]', '', $timestamp);
    setcookie('auth[series]', '', $timestamp);
    setcookie('auth[token]', '', $timestamp);
}

function genCookieAuthString() {
    return substr(str_shuffle('01234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_.~'), 0, 28);
}

function authLevel($authdata=false) {
    // Return the auth level from parameter or session, or 0
    global $sprefix;
    $authdata = $authdata?$authdata:
        (isset($_SESSION[$sprefix]['authdata'])?
            $_SESSION[$sprefix]['authdata']:0);
    if ($authdata) {
        return $authdata['userlevel'];
    } else {
        return 0;
    }
}

function validateAuth($require) {
    global $serverdir, $sprefix;
    if (isset($_SESSION[$sprefix]['authdata'])) {
        if (authLevel() < 3) {
            require("../functions.php");
            setMessage("Access denied");
            header("Location: {$serverdir}/index.php");
        }
    } elseif ($require) {
        setMessage("Access denied");
        header("Location: {$serverdir}/index.php");
    }
}

function checkCorsAuth() {
    if ($_SERVER['HTTP_ORIGIN']) {
        $corsfile = explode("\n", file_get_contents("corsfile.txt"));
        if ($_SERVER['HTTP_HOST'] == $_SERVER['HTTP_ORIGIN']) {
            return false;
        } elseif ($corsfile && in_array($_SERVER['HTTP_ORIGIN'], $corsfile)) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            return true;
        } else {
            ?><!DOCTYPE=html>
            <html lang="en">
            <head><title>Access Denied</title></head>
            <body><p><?=$_SERVER['HTTP_ORIGIN']?> is not set up for a CORS
            mashup.  If you can log in to
            <?="{$_SERVER['HTTP_HOST']}/{$serverdir}/admin.php"?>,
            then you need to save "<?=$_SERVER['HTTP_ORIGIN']?>" in the form box
            under the heading "Mashing up pages from here into your own web
            site."</p></body></html>
            <?
            exit(0);
        }
    } else return false;
}

function requireAuth($location="index.php", $message="Access denied.") {
    setMessage($message);
    header("Location: {$location}");
    exit(0);
}

// vim: set foldmethod=indent :
?>
