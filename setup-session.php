<?
session_set_cookie_params(604800);
session_start();
$sprefix = realpath(dirname(__FILE__));
if (! (array_key_exists($sprefix, $_SESSION))) {
    $_SESSION[$sprefix] = array();
}
?>
