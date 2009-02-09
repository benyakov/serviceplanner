<html>
<?
require("functions.php");
require("db-connection.php");
echo html_head("Sermon Plans");
$sql = "SELECT sermons.bibletext, sermons.outline, sermons.notes,
    sermons.service, DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    days.name, days.rite
    FROM sermons JOIN days ON (sermons.service=days.pkey)";
$result = mysql_query($sql) or die(mysql_error());
?>
<body>
    <p><a href="records.php">Browse Records Records</a></p>
    <p><a href="enter.php">Enter New Service Records</a></p>
    <p><a href="modify.php">Modify Service Records</a></p>
    <p><a href="hymns.php">Upcoming Hymns</a></p>
    <h1>Sermon Plans</h1>
    <table>
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
</body>
</html>
