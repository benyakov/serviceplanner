<?
require("functions.php");
require("db-connection.php");
require("options.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
?>
<html>
<?=html_head("Modify Service Planning Records")?>
<body>
    <? if ($_GET['message']) { ?>
        <p class="message"><?=htmlspecialchars($_GET['message'])?></p>
    <? } ?>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content_container">
    <div id="goto_now"><a href="#now">Jump to This Week</a></div>
<h1>Modify Service Planning Records</h1>
<p class="explanation">This listing of hymns allows you to delete whole
services, with all associated hymns at that location. To delete only certain
hymns, edit the service using the "Edit" link.  To create or edit a sermon plan
for that service, use the "Sermon" link.</p>
<?php
$sql = "SELECT DATE_FORMAT(${dbp}days.caldate, '%e %b %Y') as date,
    ${dbp}hymns.book, ${dbp}hymns.number, ${dbp}hymns.note,
    ${dbp}hymns.location, ${dbp}days.name as dayname, ${dbp}days.rite,
    ${dbp}days.pkey as id, ${dbp}names.title
    FROM ${dbp}hymns
    RIGHT OUTER JOIN ${dbp}days ON (${dbp}hymns.service = ${dbp}days.pkey)
    LEFT OUTER JOIN ${dbp}names ON (${dbp}hymns.number = ${dbp}names.number)
        AND (${dbp}hymns.book = ${dbp}names.book)
    ORDER BY ${dpb}hymns.service DESC, ${dbp}days.caldate DESC,
        ${dbp}hymns.location, ${dbp}hymns.sequence";
$result = mysql_query($sql) or die(mysql_error()) ;
modify_records_table($result, "delete.php");
?>
</div>
</body>
</html>
