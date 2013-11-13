<? /* Handles login/logout
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
require("./init.php");
$options = getOptions();

if (array_key_exists('action', $_GET) && $_GET['action'] == 'logout') {
    session_destroy();
    authcookie(False);
    require("./setup-session.php");
    $auth = false;
} else {
    $auth = auth($_POST['username'], $_POST['password']) ;
}

$authid = authId();
if (array_key_exists('ajax', $_POST) || array_key_exists('ajax', $_GET)) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
    if (array_key_exists('authdata', $_SESSION[$sprefix])) {
        $rv = $_SESSION[$sprefix]['authdata'];
        unset($rv['password']);
        $rv['actions'] = getUserActions($bare=true);
        $rv['loginform'] = getLoginForm($bare=true);
        $st = $options->get('sitetabs');
    } else {
        $rv = array('userlevel' => 0);
        $st = $options->get('anonymous sitetabs');
    }
    $stkeys = array_keys($st);
    $rv['actions'] = getUserActions($bare=true);
    $rv['loginform'] = getLoginForm($bare=true);
    $rv['sitetabs'] = gensitetabs($st, $stkeys[0], $bare=true);
    echo json_encode($rv);
} else {
    $redirect = $_SESSION['HTTP_REFERER']?
            $_SESSION['HTTP_REFERER'] : "index.php";
    if ($authid) {
        setMessage("You are logged in.");
    } else {
        setMessage("You are logged out.");
    }
    header("Location: {$redirect}");
}

?>
