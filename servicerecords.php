<?
require("./init.php");
$cors = checkCorsAuth();
if ($jsonp=checkJsonpReq()) {
    ob_start();
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Planning Records")?>
<body>
    <? if ($jsonp) {
        ob_clean();
    } ?>
    <div id="content-container">
    <? include("records-table.php"); ?>
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
