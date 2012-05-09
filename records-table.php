<?php
if (array_key_exists('listinglimit', $_GET) &&
    is_numeric($_GET['listinglimit'])) {
    $_SESSION[$sprefix]["listinglimit"] = $_GET['listinglimit'];
} elseif (! array_key_exists('listinglimit', $_SESSION[$sprefix])) {
    $_SESSION[$sprefix]['listinglimit'] = $listinglimit;
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
$q = queryAllHymns($dbh, $dbp, $_SESSION[$sprefix]['listinglimit']);
display_records_table($q);
?>
