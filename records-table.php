<?php
$cors = checkCorsAuth($reqheaders);
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
<button type="submit" value="Apply">Apply</button>
</form>
<?php
$q = $dbh->query("SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    hymns.book, hymns.number, hymns.note, hymns.location,
    days.name as dayname, days.rite, days.servicenotes,
    names.title
    FROM {$dbp}hymns as hymns
    RIGHT OUTER JOIN {$dbp}days AS days
        ON (hymns.service = days.pkey)
    LEFT OUTER JOIN {$dbp}names AS names
        ON (hymns.number = names.number)
        AND (hymns.book = names.book)
    ORDER BY days.caldate DESC, hymns.service DESC,
        hymns.location, hymns.sequence
    {$limit}");
display_records_table($q);
?>
