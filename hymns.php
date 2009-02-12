<?
require("functions.php");
require("db-connection.php");
?>
<html>
<?=html_head("Upcoming Hymns")?>
<body>

<h1>Upcoming Hymns</h1>
<div id="content_container">
<?php
$sql = "SELECT DATE_FORMAT(${dbp}days.caldate, '%e %b %Y') as date,
    ${dbp}hymns.book, ${dbp}hymns.number, ${dbp}hymns.note,
    ${dbp}hymns.location, ${dbp}days.name as dayname, ${dbp}days.rite,
    ${dbp}names.title
    FROM ${dbp}hymns
    LEFT OUTER JOIN ${dbp}days ON (${dbp}hymns.service = ${dbp}days.pkey)
    LEFT OUTER JOIN ${dbp}names ON (${dbp}hymns.number = ${dbp}names.number)
        AND (${dbp}hymns.book = ${dbp}names.book)
    WHERE ${dbp}days.caldate >= CURDATE()
    ORDER BY ${dbp}days.caldate, ${dbp}hymns.location, ${dbp}hymns.sequence";

$result = mysql_query($sql) or die(mysql_error()) ;
display_records_table($result);
?>
</div>
</body>
</html>
