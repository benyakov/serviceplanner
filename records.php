<?
require("functions.php");
require("options.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
?>
<html>
<?=html_head("Service Planning Records")?>
<body>
    <? if ($_GET['message']) { ?>
        <p class="message"><?=htmlspecialchars($_GET['message'])?></p>
    <? }
    echo sitetabs($sitetabs, $script_basename);
    ?><div id="content_container"><?
    include("records-table.php");
    ?>
    </div>
</body>
</html>
