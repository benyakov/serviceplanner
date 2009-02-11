<?
require("functions.php");
require("db-connection.php");
?>
    <html>
    <?=html_head("Edit a Sermon Plan")?>
    <body>
        <div id="content_container">
        <span class="nonprinting">
        <p><a href="sermon.php?id=<?=$_GET['id']?>">Edit This Plan</a>
        | <a href="sermons.php">Browse All Sermon Plans</a></p>
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
        </div>
    </body>
</html>
