<html>
<?
require("functions.php");
require("db-connection.php");
require("options.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
echo html_head("Sermon Plans");
$sql = "SELECT sermons.bibletext, sermons.outline, sermons.notes,
    sermons.service, DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    days.name, days.rite
    FROM sermons JOIN days ON (sermons.service=days.pkey)";
$result = mysql_query($sql) or die(mysql_error());
?>
<body>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content_container">
    <h1>Sermon Plans</h1>
    <table id="sermonplan_listing">
    <tr class="heading"><th>Date</th><th>Day</th><th>Text</th><th>Rite</th></tr>
    <tr class="heading"><th colspan="3">Outline</th><th>Notes</th></tr>
    <?
while ($row = mysql_fetch_assoc($result))
{
?>
    <tr class="table_topline">
        <td><?=$row['date']?></td><td><?=$row['name']?></td>
        <td><?=$row['bibletext']?></td><td><?=$row['rite']?></td></tr>
    <tr><td colspan="3" class="table_preformat">
            <pre><?=$row['outline']?></pre><br />
            <a href="sermon.php?id=<?=$row['service']?>">Edit</a>
        </td>
        <td class="table_leftborder table_preformat">
            <pre><?=$row['notes']?></pre>
        </td>
    </tr>
<?
}
?>
    </table>
    </div>
</body>
</html>
