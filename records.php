<?require("functions.php");?>
<html>
<?=html_head("Service Planning Records")?>
<body>
    <? if ($_GET['message']) { ?>
        <p class="message"><?=htmlspecialchars($_GET['message'])?></p>
    <? } ?>
    <p><a href="enter.php">Enter New Records</a></p>
    <p><a href="modify.php">Modify Records</a></p>
    <p><a href="hymns.php">Upcoming Hymns</a></p>
    <p><a href="sermons.php">Sermon Plans</a></p>
    <? include("records-table.php"); ?>
</body>
</html>
