<?
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Planning Records")?>
<body>
    <? if (array_key_exists('message', $_SESSION[$sprefix])) { ?>
        <div class="message"><?=$_SESSION[$sprefix]['message']?></div>
        <? unset $_SESSION[$sprefix]['message'];
    }
    echo sitetabs($sitetabs, $script_basename);
    ?><div id="content-container">
    <div id="goto-now"><a href="#now">Jump to This Week</a></div>
    <?
    include("records-table.php");
    ?>
    </div>
</body>
</html>
