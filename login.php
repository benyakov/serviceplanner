<?
require("./init.php");
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
header("Content-type: application/json");

if ($_POST['action'] == 'logout') {
    session_destroy();
    require("./setup_session.php");
} else {
    auth($_POST['username'], $_POST['password']) ;
}

$authid = authId();

if ($_POST['ajax'] == true) {
    echo json_encode($authid);
} else {
    if ($authid) {
        setMessage("You are logged in.");
    } else {
        setMessage("You are logged out.");
    }
    header("Location: ".$_SESSION['HTTP_REFERER']?
        $_SESSION['HTTP_REFERER'] : "index.html");
}

?>
