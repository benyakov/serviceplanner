<?
require("functions.php");
require("db-connection.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! array_key_exists('stage', $_GET)) {
    $id = mysql_esc($_GET['id']);
?>
    <html>
    <?=html_head("Edit a Sermon Plan")?>
    <body>
        <? if ($_GET['message']) { ?>
            <p class="message"><?=htmlspecialchars($_GET['message'])?></p>
        <? } ?>
        <p><a href="records.php">Browse Records Records</a></p>
        <p><a href="enter.php">Enter New Service Records</a></p>
        <p><a href="modify.php">Modify Service Records</a></p>
        <p><a href="hymns.php">Upcoming Hymns</a></p>
        <p><a href="sermonreport.php?id=<?=${id}?>">Sermon Report</a></p>
        <h1>Edit a Sermon Plan</h1>
    <?
        $sql = "SELECT bibletext, outline, notes
            FROM sermons WHERE service='${_GET['id']}'";
        $result = mysql_query($sql) or die(mysql_error());
        $row = mysql_fetch_assoc($result);
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
        <h2>Hymns for This Service</h2>
        <p class="explanation">Hymns are grouped by location.
        Deleting the service at any location will delete hymns at all locations.
        To delete only certain hymns,
        <a href="edit.php?id=<?=$id?>">edit the service</a>.</p>
    <?
    $sql = "SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
        hymns.book, hymns.number, hymns.note, hymns.location,
        days.name as dayname, days.rite, days.pkey as id, names.title
        FROM hymns LEFT OUTER JOIN days ON (hymns.service = days.pkey)
        LEFT OUTER JOIN names ON (hymns.number = names.number)
        AND (hymns.book = names.book)
        WHERE days.pkey = '${id}'
        ORDER BY days.caldate DESC, hymns.location, hymns.sequence";
    $result = mysql_query($sql) or die(mysql_error()) ;
    modify_records_table($result, "delete.php");
    ?>
    </body>
    </html>
<?
} elseif (2 == $_GET["stage"])
{
    // Insert or update the sermon plans.
    $bibletext = mysql_esc($_POST['bibletext']);
    $outline = mysql_esc($_POST['outline']);
    $notes = mysql_esc($_POST['notes']);
    $id = $_POST['service'];
    $sql = "INSERT INTO sermons (bibletext, outline, notes, service)
        VALUES ('${bibletext}', '${outline}', '${notes}', '${id}')";
    if (! mysql_query($sql)) {
        $sql = "UPDATE sermons SET bibletext = '${bibletext}',
            outline = '${outline}', notes = '${notes}'
            WHERE service = '${id}'";
        mysql_query($sql) or die(mysql_error());
    }
    $now = strftime('%T');
    header("Location: http://${this_script}?id=${id}&message=".urlencode("Sermon plans saved at ${now} server time."));
}
