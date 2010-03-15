<?php
require_once("functions.php");
require_once("db-connection.php");
?>
<h1>Service Planning Records</h1>
<?php
$sql = "SELECT DATE_FORMAT(${dbp}days.caldate, '%e %b %Y') as date,
    ${dbp}hymns.book, ${dbp}hymns.number, ${dbp}hymns.note,
    ${dbp}hymns.location, ${dbp}days.name as dayname, ${dbp}days.rite,
    ${dbp}names.title
    FROM ${dbp}hymns
    RIGHT OUTER JOIN ${dbp}days ON (${dbp}hymns.service = ${dbp}days.pkey)
    LEFT OUTER JOIN ${dbp}names ON (${dbp}hymns.number = ${dbp}names.number)
        AND (${dbp}hymns.book = ${dbp}names.book)
    ORDER BY ${dbp}hymns.service DESC, ${dbp}days.caldate DESC,
        ${dbp}hymns.location, ${dbp}hymns.sequence";
$result = mysql_query($sql) or die(mysql_error()) ;
display_records_table($result);
?>
