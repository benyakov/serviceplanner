<?
require("./init.php");
if (! $auth) {
    header("location: index.php");
    exit(0);
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit a Sermon Plan")?>
    <body>
        <div id="content-container">
        <span class="nonprinting">
        <p><a href="sermon.php?id=<?=$_GET['id']?>">Edit This Plan</a>
        | <a href="sermons.php">Browse All Sermon Plans</a></p>
        </span>
        <h1>Sermon Plan</h1>
    <?
        $q = $dbh->prepare("SELECT s.bibletext, s.outline,
            s.notes, DATE_FORMAT(d.caldate, '%e %b %Y') as date,
            d.name, d.rite
            FROM `{$dbp}sermons` AS s
            JOIN `{$dbp}days` AS d ON (s.service=d.pkey)
            WHERE service=:id");
        $q->execute(array("id"=>$_GET['id']))
            or die(array_pop($q->errorInfo()));
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
