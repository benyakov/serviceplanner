<html>
<?
require("functions.php");
require("db-connection.php");
require("options.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
echo html_head("Sermon Plans");
$sql = "SELECT ${dbp}sermons.bibletext, ${dbp}sermons.outline,
    ${dbp}sermons.notes, ${dbp}sermons.service,
    DATE_FORMAT(${dbp}days.caldate, '%e %b %Y') as date,
    ${dbp}days.name, ${dbp}days.rite
    FROM ${dbp}sermons JOIN ${dbp}days
        ON (${dbp}sermons.service=${dbp}days.pkey)
    ORDER BY ${dbp}days.caldate DESC";
$result = mysql_query($sql) or die(mysql_error());
?>
<body>
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
while ($row = mysql_fetch_assoc($result))
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
