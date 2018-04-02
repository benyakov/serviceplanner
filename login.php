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

if ('logout' == getGET('action')) {
    session_destroy();
    authcookie(False);
} else {
    $auth = auth(getPOST('username'), getPOST('password')) ;
}

$authid = authId();
if (getPOST('ajax') || getGET('ajax')) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
    $config = getConfig();
    $options = getOptions();
    if (array_key_exists('authdata', $_SESSION[$sprefix])) {
        $rv = $_SESSION[$sprefix]['authdata'];
        unset($rv['password']);
        $rv['actions'] = getUserActions($bare=true);
        $rv['loginform'] = getLoginForm($bare=true);
    } else {
        $rv = array('userlevel' => 0);
    }
    if (2 <= $rv['userlevel']) {
        $st = $config->getDefault($options->get('sitetabs'), 'sitetabs');
    } else {
        $st = $config->getDefault($options->get('anonymous sitetabs'),
            'anonymous sitetabs');
    }
    $stkeys = array_keys($st);
    $activated = $stkeys[0];
    if (getPOST('activated')) {
        if (getPOST('activated') == "records")
            $_POST['activated'] = "modify";
        $activated = getPOST('activated');
    }
    $rv['actions'] = getUserActions($bare=true);
    $rv['loginform'] = getLoginForm($bare=true);
    $rv['sitetabs'] = gensitetabs($st, $activated, $bare=true);
    echo json_encode($rv);
} else {
    $redirect = getIndexOr($_SESSION,'HTTP_REFERER',"index.php");
    if ($authid) {
        setMessage("You are logged in.");
    } else {
        setMessage("You are logged out.");
    }
    header("Location: {$redirect}");
}

?>
