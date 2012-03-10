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
<!DOCTYPE html>
<html lang="en">
<?=html_head("Modify Service Planning Records")?>
<body>
<script type="text/javascript">
    auth = "<?=authId()?>";
    $(document).ready(function() {
        setupLogin("<?=authId()?>");
    });
</script>
    <header>
    <div id="login"><?=loginForm()?></div>
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
<input type="submit" value="Apply">
</form>
<?
$q = $dbh->query("SELECT DATE_FORMAT(days.caldate, '%c/%e/%Y') as date,
    hymns.book, hymns.number, hymns.note,
    hymns.location, days.name as dayname, days.rite,
    days.pkey as id, days.servicenotes, names.title
    FROM {$dbp}hymns AS hymns
    RIGHT OUTER JOIN {$dbp}days AS days ON (hymns.service = days.pkey)
    LEFT OUTER JOIN {$dbp}names AS names ON (hymns.number = names.number)
        AND (hymns.book = names.book)
    ORDER BY days.caldate DESC, hymns.service DESC,
        hymns.location, hymns.sequence
    {$limit}");
modify_records_table($q, "delete.php");
?>
</div>
</body>
</html>
