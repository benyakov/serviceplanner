<?
require("./init.php");

if (array_key_exists('action', $_GET) && $_GET['action'] == 'logout') {
    session_destroy();
    require("./setup-session.php");
} else {
    auth($_POST['username'], $_POST['password']) ;
}

$authid = authId();
if (array_key_exists('ajax', $_POST) || array_key_exists('ajax', $_GET)) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
    echo json_encode($authid);
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
