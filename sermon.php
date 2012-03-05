<?
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! array_key_exists('stage', $_GET)) {
    ?><!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit a Sermon Plan")?>
    <body>
        <? showMessage(); ?>
        <div id="content-container">
        <p><a href="sermonreport.php?id=<?=${id}?>">Printable Sermon Report</a>
        | <a href="sermons.php">Browse All Sermon Plans</a>
        | <a href="modify.php">Back to Service Listing</a></p>
        <h1>Edit a Sermon Plan</h1>
        <p class="explanation">You can delete the whole service, hymns, sermon
        plan, and all, from here.  To edit this service or modify the chosen
        hymns individually, use the link below.</p>
        <a href="edit.php?id=<?=urlencode($id)?>">Edit the Service</a>.</p>
    <?
    $q = $dbh->prepare("SELECT
            DATE_FORMAT(days.caldate, '%e %b %Y') as date,
            hymns.book, hymns.number, hymns.note,
            hymns.location, days.name as dayname, days.rite,
            days.pkey as id, names.title
            FROM {$$dbp}hymns AS hymns
            LEFT OUTER JOIN {$dbp}days AS days ON (hymns.service = days.pkey)
            LEFT OUTER JOIN {$dbp}names AS names ON
                (hymns.number = names.number)
                AND (hymns.book = names.book)
            WHERE days.pkey = :id
            ORDER BY days.caldate DESC, hymns.location,
                hymns.sequence");
    $q->bindParam(":id", $id);
    $q->execute() or die(array_pop($q->errorInfo()));
    modify_records_table($result, "delete.php");

    $q = $dbh->query("SELECT bibletext, outline, notes
        FROM {$dbp}sermons WHERE service='{$_GET['id']}'");
    $row = $q->fetch(PDO::FETCH_ASSOC);
    ?>
        <form action="http://<?=$this_script?>?stage=2" method="POST">
        <input type="hidden" id="service" name="service" value="<?=$id?>">
        <p>
        <label for="bibletext">Text:</label><br />
        <input type="text" id="bibletext" name="bibletext"
        size="80" maxlength="80" class="entryline"
        value="<?=trim($row['bibletext'])?>"><br />
        <label for="outline">Outline:</label><br />
        <textarea id="outline" name="outline"><?=trim($row['outline'])?></textarea><br />
        <label for="notes">Notes:</label><br />
        <textarea id="notes" name="notes"><?=trim($row['notes'])?></textarea><br />
        <input type="submit" value="Commit"><input type="reset">
        </form>
    </div>
    </body>
    </html>
<?
} elseif (2 == $_GET["stage"])
{
    // Insert or update the sermon plans.
    $q = $dbh->prepare("INSERT INTO {$dbp}sermons
        (bibletext, outline, notes, service)
        VALUES (:bibletext, :outline, :notes, :id)";
    $q->bindParam(':bibletext', $_POST['bibletext']);
    $q->bindParam(':outline', $_POST['outline']);
    $q->bindParam(':notes', $_POST['notes']);
    $q->bindParam(':id', $_POST['service']);
    if (! $q->execute()) {
        $q = $dbh->prepare("UPDATE {$dbp}sermons
            SET bibletext = :bibletext,
            outline = :outline, notes = :notes
            WHERE service = :id";
        $q->bindParam(':bibletext', $_POST['bibletext']);
        $q->bindParam(':outline', $_POST['outline']);
        $q->bindParam(':notes', $_POST['notes']);
        $q->bindParam(':id', $_POST['service']);
        $q->execute() or die(array_pop($q->errorInfo()));
    }
    $now = strftime('%T');
    setMessage("Sermon plans saved at {$now} server time.");
    header("Location: http://{$this_script}?id={$id}");
}
