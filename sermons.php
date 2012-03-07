<!DOCTYPE html>
<html lang="en">
<?
require("./init.php");
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
    exit(0);
}
echo html_head("Sermon Plans");
?>
<script type="text/javascript">
    auth = "<?=authId()?>";
    <? if (! is_link($_SERVER['SCRIPT_FILENAME'])) {
        ?>
    $(document).ready(function() {
        setupLogin();
    });
    <? } ?>
</script>
<?
$q = $dbh->query("SELECT sermons.bibletext, sermons.outline,
    sermons.notes, sermons.service,
    DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    days.name, days.rite
    FROM {$dbp}sermons AS sermons JOIN {$dbp}days AS days
        ON (sermons.service=days.pkey)
    ORDER BY days.caldate DESC");
$q->execute() or die(array_pop($q->errorInfo));
?>
<body>
    <header>
    <div id="login"></div>
    <?showMessage();?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <h1>Sermon Plans</h1>
    <p class="explanation">This is a listing of sermon plans you have created.
    To create a sermon plan, first create a service
    with zero or more hymns.  On the tab to modify services is a link
    to create or edit a sermon plan associated with it.</p>
    <table id="sermonplan-listing">
    <tr class="heading"><th>Date</th><th>Day</th><th>Text</th><th>Rite</th></tr>
    <tr class="heading"><th colspan="3">Outline</th><th>Notes</th></tr>
    <?
while ($row = $q->fetch(PDO::FETCH_ASSOC))
{
?>
    <tr class="table-topline">
        <td><?=$row['date']?></td><td><?=$row['name']?></td>
        <td><?=$row['bibletext']?></td><td><?=$row['rite']?></td></tr>
    <tr><td colspan="3" class="table-preformat">
            <pre><?=$row['outline']?></pre><br />
            <a href="sermon.php?id=<?=$row['service']?>">Edit</a>
        </td>
        <td class="table-leftborder table-preformat">
            <?=translate_markup($row['notes'])?>
        </td>
    </tr>
<?
}
?>
    </table>
    </div>
</body>
</html>
