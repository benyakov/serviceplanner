<?php
require_once("functions.php");
require_once("db-connection.php");
if (array_key_exists('listinglimit', $_GET) &&
    is_numeric($_GET['listinglimit'])) {
    $_SESSION[$sprefix]["listinglimit"] = $_GET['listinglimit'];
}
if (is_numeric($_SESSION[$sprefix]["listinglimit"])) {
    if ($_SESSION[$sprefix]["listinglimit"] > 0) {
        $limit = " LIMIT {$_SESSION[$sprefix]["listinglimit"]}";
    } else {
        $limit = "";
    }
} else {
    $_SESSION[$sprefix]["listinglimit"] = $listinglimit;
    $limit = " LIMIT {$_SESSION[$sprefix]["listinglimit"]}";
}
?>
<h1>Service Planning Records</h1>
<form action="http://<?=$this_script?>" method="GET">
<label for="listinglimit">Listing Limit (0 for None):</label>
<input type="text" id="listinglimit" name="listinglimit"
    value="<?=$_SESSION[$sprefix]["listinglimit"]?>">
<input type="submit" value="Apply">
</form>
<?php
$sql = "SELECT DATE_FORMAT({$dbp}days.caldate, '%e %b %Y') as date,
    {$dbp}hymns.book, {$dbp}hymns.number, {$dbp}hymns.note,
    {$dbp}hymns.location, {$dbp}days.name as dayname, {$dbp}days.rite,
    {$dbp}names.title
    FROM {$dbp}hymns
    RIGHT OUTER JOIN {$dbp}days ON ({$dbp}hymns.service = {$dbp}days.pkey)
    LEFT OUTER JOIN {$dbp}names ON ({$dbp}hymns.number = {$dbp}names.number)
        AND ({$dbp}hymns.book = {$dbp}names.book)
    ORDER BY {$dbp}days.caldate DESC, {$dbp}hymns.service DESC,
        {$dbp}hymns.location, {$dbp}hymns.sequence
    {$limit}";
$result = mysql_query($sql) or die(mysql_error()) ;
display_records_table($result);
?>
