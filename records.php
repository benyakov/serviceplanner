<?
require("functions.php");
require("options.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
?>
<html>
<?=html_head("Service Planning Records")?>
<body>
    <? if ($_GET['message']) { ?>
        <div class="message"><?=htmlspecialchars($_GET['message'])?></div>
    <? }
    echo sitetabs($sitetabs, $script_basename);
    ?><div id="content_container">
    <div id="goto_now"><a href="#now">Jump to This Week</a></div>
    <?
    include("records-table.php");
    ?>
    </div>
</body>
</html>
