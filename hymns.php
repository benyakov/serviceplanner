<?
require("functions.php");
require("db-connection.php");
?>
<html>
<?=html_head("Upcoming Hymns")?>
<body>
<h1>Upcoming Hymns</h1>
<?php
$sql = "SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    hymns.book, hymns.number, hymns.note, hymns.location,
    days.name as dayname, days.rite, names.title
    FROM hymns LEFT OUTER JOIN days ON (hymns.service = days.pkey)
    LEFT OUTER JOIN names ON (hymns.number = names.number)
    AND (hymns.book = names.book)
    WHERE days.caldate >= CURDATE()
    ORDER BY days.caldate, hymns.location, hymns.sequence";

$result = mysql_query($sql) or die(mysql_error()) ;
display_records_table($result);
?>
</body>
</html>
