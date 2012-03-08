<?
require("./init.php");
// functionality needed for login js at bottom of ecmascript.js

if ($_POST['action'] == 'logout') {
    session_destroy();
    require("./setup_session.php");
} else {
    auth($_POST['username'], $_POST['password']) ;
}
echo json_encode(authId());

?>
