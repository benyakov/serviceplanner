<?
require("functions.php");
require("options.php");
if ((! file_exists("db-connection.php") and
    (! is_link($_SERVER['SCRIPT_FILENAME']))))
{   ?>
    <html><?=html_head("Unconfigured Service Planner")?><body>
    <h1>Unconfigured</h1>
    <p>This is an unconfigured installation of the service planner.
    To configure it, make the appropriate settings in db_connection.php on
    the web server.
    A documented sample file is provided.</p>
    </body></html>
    <?
    exit();
}
require("db-connection.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
?>
<html>
<?=html_head("Upcoming Hymns")?>
<body>
<?
if (! is_link($_SERVER['SCRIPT_FILENAME']))
{
    echo sitetabs($sitetabs, $script_basename);
}   ?>
<div id="content-container">
<h1>Upcoming Hymns</h1>
<?php
$sql = "SELECT DATE_FORMAT({$dbp}days.caldate, '%e %b %Y') as date,
    {$dbp}hymns.book, {$dbp}hymns.number, {$dbp}hymns.note,
    {$dbp}hymns.location, {$dbp}days.name as dayname, {$dbp}days.rite,
    {$dbp}days.servicenotes, {$dbp}names.title
    FROM {$dbp}hymns
    LEFT OUTER JOIN {$dbp}days ON ({$dbp}hymns.service = {$dbp}days.pkey)
    LEFT OUTER JOIN {$dbp}names ON ({$dbp}hymns.number = {$dbp}names.number)
        AND ({$dbp}hymns.book = {$dbp}names.book)
    WHERE {$dbp}days.caldate >= CURDATE()
    ORDER BY {$dbp}days.caldate, {$dbp}hymns.service,
        {$dbp}hymns.location, {$dbp}hymns.sequence";

$result = mysql_query($sql) or die(mysql_error()) ;
display_records_table($result);
?>
</div>
</body>
</html>
