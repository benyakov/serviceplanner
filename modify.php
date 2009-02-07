<?
require("functions.php");
require("db-connection.php");
?>
<html>
<?=html_head("Modify Service Planning Records")?>
<body>
    <? if ($_GET['message']) { ?>
        <p class="message"><?=$_GET['message']?></p>
    <? } ?>
    <p><a href="records.php">Browse Records</a></p>
    <p><a href="enter.php">Enter New Records</a></p>
    <p><a href="hymns.php">Upcoming Hymns</a></p>
<h1>Modify Service Planning Records</h1>
<?php
$sql = "SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    hymns.book, hymns.number, hymns.note, hymns.location,
    days.name as dayname, days.rite, days.pkey as id, names.title
    FROM hymns LEFT OUTER JOIN days ON (hymns.service = days.pkey)
    LEFT OUTER JOIN names ON (hymns.number = names.number)
    AND (hymns.book = names.book)
    ORDER BY days.caldate DESC, hymns.location, hymns.sequence";
$result = mysql_query($sql) or die(mysql_error()) ;
modify_records_table($result, "delete.php");
?>
</body>
</html>
