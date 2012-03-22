<?
require("./init.php");
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
    exit(0);
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (array_key_exists('listinglimit', $_GET) &&
    is_numeric($_GET['listinglimit'])) {
    $_SESSION[$sprefix]["listinglimit"] = $_GET['listinglimit'];
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Modify Service Planning Records")?>
<body>
    <header>
    <?=getLoginForm()?>
    <?=getUserActions()?>
    <? showMessage(); ?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <div id="goto-now"><a href="#now">Jump to This Week</a></div>
<h1>Modify Service Planning Records</h1>
<p class="explanation">This listing of hymns allows you to delete whole
services, with all associated hymns at that location. To delete only certain
hymns, edit the service using the "Edit" link.  To create or edit a sermon plan
for that service, use the "Sermon" link.</p>
<form action="http://<?=$this_script?>" method="GET">
<label for="listinglimit">Listing Limit (0 for None):</label>
<input type="text" id="listinglimit" name="listinglimit"
    value="<?=$_SESSION[$sprefix]["listinglimit"]?>">
<button type="submit" value="Apply">Apply</button>
</form>
<?
$q = queryAllHymns($dbh, $dbp, $_SESSION[$sprefix]['listinglimit']);
modify_records_table($q, "delete.php");
?>
</div>
</body>
</html>
