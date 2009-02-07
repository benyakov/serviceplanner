<?php
require_once("functions.php");
require_once("db-connection.php");
?>
<h1>Service Planning Records</h1>
<?php 
$sql = "SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date, 
    hymns.book, hymns.number, hymns.note, hymns.location, 
    days.name as dayname, days.rite, names.title 
    FROM hymns LEFT OUTER JOIN days ON (hymns.service = days.pkey)
    LEFT OUTER JOIN names ON (hymns.number = names.number) 
    AND (hymns.book = names.book)
    ORDER BY days.caldate DESC, hymns.location, hymns.sequence";
$result = mysql_query($sql) or die(mysql_error()) ;
display_records_table($result);
?>
