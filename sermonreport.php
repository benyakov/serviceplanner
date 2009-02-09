<?
require("functions.php");
require("db-connection.php");
?>
    <html>
    <?=html_head("Edit a Sermon Plan")?>
    <body>
        <span class="nonprinting">
        <p><a href="records.php">Browse Records Records</a></p>
        <p><a href="enter.php">Enter New Service Records</a></p>
        <p><a href="modify.php">Modify Service Records</a></p>
        <p><a href="hymns.php">Upcoming Hymns</a></p>
        </span>
        <h1>Sermon Plan</h1>
    <?
        $sql = "SELECT sermons.bibletext, sermons.outline, sermons.notes,
            DATE_FORMAT(days.caldate, '%e %b %Y') as date,
            days.name, days.rite
            FROM sermons JOIN days ON (sermons.service=days.pkey)
            WHERE service='${_GET['id']}'";
        $result = mysql_query($sql) or die(mysql_error());
        $row = mysql_fetch_assoc($result);
    ?>
        <dl>
            <dt>Date</dt>
            <dd><?=$row['date']?></dd>
            <dt>Day</dt>
            <dd><?=$row['day']?></dd>
            <dt>Rite</dt>
            <dd><?=$row['rite']?></dd>
            <dt>Text</dt>
            <dd><?=$row['text']?></dd>
            <dt>Outline<dt>
            <dd><pre><?=$row['outline']?></pre></dd>
            <dt>Notes<dt>
            <dd><pre><?=$row['notes']?></pre></dd>
        </dl>
    </body>
</html>
