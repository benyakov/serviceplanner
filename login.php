<?
require("./init.php");

if (array_key_exists('action', $_GET) && $_GET['action'] == 'logout') {
    session_destroy();
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
        $st = $sitetabs;
    } else {
        $rv = array('userlevel' => 0);
        $st = $sitetabs_anonymous;
    }
    $stkeys = array_keys($st);
    $rv['actions'] = getUserActions($bare=true);
    $rv['loginform'] = getLoginForm($bare=true);
    $rv['sitetabs'] = sitetabs($st, $stkeys[0], $bare=true);
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
