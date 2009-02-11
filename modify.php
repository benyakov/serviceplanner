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
<h1>Modify Service Planning Records</h1>
<p class="explanation">Hymns are grouped by location.  Deleting the service at
any location will delete hymns at all locations for that service.  To delete
only certain hymns, edit the service.</p>
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
</div>
</body>
</html>
