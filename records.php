<?
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Planning Records")?>
<body>
    <header>
    <?=getLoginForm()?>
    <?=getUserActions()?>
    <?showMessage();?>
    </header>
    <? if ($auth) {
        echo sitetabs($sitetabs, $script_basename);
    } else {
        echo sitetabs($sitetabs_anonymous, $script_basename);
    } ?>
    <div id="content-container">
    <div id="goto-now"><a href="#now">Jump to This Week</a></div>
    <?
    include("records-table.php");
    ?>
    </div>
</body>
</html>
