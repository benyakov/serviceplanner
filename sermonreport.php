<?
require("init.php");
?>
    <html>
    <?=html_head("Edit a Sermon Plan")?>
    <body>
        <div id="content-container">
        <span class="nonprinting">
        <p><a href="sermon.php?id=<?=$_GET['id']?>">Edit This Plan</a>
        | <a href="sermons.php">Browse All Sermon Plans</a></p>
        </span>
        <h1>Sermon Plan</h1>
    <?
        $q = $dbh->prepare("SELECT sermons.bibletext, sermons.outline,
            sermons.notes, DATE_FORMAT(days.caldate, '%e %b %Y') as date,
            days.name, days.rite
            FROM {$dbp}sermons AS sermons
            JOIN {$dbp}days AS DAYS
                ON (sermons.service=days.pkey)
            WHERE service=:id");
        $q->bindParam('id', $_GET['id']);
        $q->execute() or die(array_pop($q->errorInfo()));
        $row = $q->fetch(PDO::FETCH_ASSOC);
    ?>
        <dl>
            <dt>Date</dt>
            <dd><?=$row['date']?></dd>
            <dt>Day</dt>
            <dd><?=$row['day']?></dd>
            <dt>Rite</dt>
            <dd><?=$row['rite']?></dd>
            <dt>Text</dt>
            <dd><?=$row['bibletext']?></dd>
            <dt>Outline<dt>
            <dd><pre><?=$row['outline']?></pre></dd>
            <dt>Notes<dt>
            <dd><?=translate_markup($row['notes'])?></dd>
        </dl>
        </div>
    </body>
</html>
