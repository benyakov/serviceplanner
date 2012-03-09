<?
require("./init.php");
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
header("Content-type: application/json");

echo json_encode($_POST);
exit(0);

if ($_POST['action'] == 'logout') {
    session_destroy();
    require("./setup_session.php");
} else {
    auth($_POST['username'], $_POST['password']) ;
}
echo json_encode(authId() || "logged out");

?>
