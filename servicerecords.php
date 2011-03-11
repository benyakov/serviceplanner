<?
require("functions.php");
require("setup-session.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
?>
<html>
<?=html_head("Service Planning Records")?>
<body>
    <div id="content-container">
    <? include("records-table.php"); ?>
    </div>
</body>
</html>
