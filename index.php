<?
require("./init.php");
$cors = checkCorsAuth();
if (is_link($_SERVER['SCRIPT_FILENAME']) || $cors ) {
    $displayonly = true;
} else {
    $displayonly = false;
}
if ($jsonp = checkJsonpReq()) {
    $displayonly = true;
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Upcoming Hymns")?>
<body>
    <header>
    <? if (!$displayonly) {
        echo getLoginForm();
        echo getUserActions();
    } ?>
    <?showMessage();?>
    </header>
<?
if (! $displayonly) {
    echo sitetabs($sitetabs, $script_basename);
}   ?>
<? if ($jsonp) {
    ob_clean();
} ?>
<div id="content-container">
<h1>Upcoming Hymns</h1>
<?php
$q = queryAllHymns($dbh, $dbp, $limit=0, $future=true);
display_records_table($q);
?>
</div>
<?  if ($jsonp) {
        $output = json_encode(addcslashes(ob_get_clean(), "'"));
        echo $jsonp . '(' . $output . ')';
        ob_start();
} ?>
</body>
</html>
<?  if ($jsonp) {
    ob_end_clean();
} ?>
