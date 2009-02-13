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
        <div id="content_container">
        <p><a href="sermonreport.php?id=<?=${id}?>">Printable Sermon Report</a>
        | <a href="sermons.php">Browse All Sermon Plans</a>
        | <a href="modify.php">Back to Service Listing</a></p>
        <h1>Edit a Sermon Plan</h1>
    <?
        $sql = "SELECT bibletext, outline, notes
            FROM ${dbp}sermons WHERE service='${_GET['id']}'";
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
        <p class="explanation">You can delete the whole service, hymns, sermon
        plan, and all, from here.  Note that this will delete hymns for
        <b>all</b> locations for this service, though they may be listed
        separately here.  To edit this service or modify the chosen hymns
        individually, use the link below.</p>
        <a href="edit.php?id=<?=$id?>">Edit the Service</a>.</p>
    <?
    $sql = "SELECT DATE_FORMAT(${dbp}days.caldate, '%e %b %Y') as date,
        ${dbp}hymns.book, ${dbp}hymns.number, ${dbp}hymns.note,
        ${dbp}hymns.location, ${dbp}days.name as dayname, ${dbp}days.rite,
        ${dbp}days.pkey as id, ${dbp}names.title
        FROM ${dbp}hymns
        LEFT OUTER JOIN ${dbp}days ON (${dbp}hymns.service = ${dbp}days.pkey)
        LEFT OUTER JOIN ${dbp}names ON (${dbp}hymns.number = ${dbp}names.number)
            AND (${dbp}hymns.book = ${dbp}names.book)
        WHERE ${dbp}days.pkey = '${id}'
        ORDER BY ${dbp}days.caldate DESC, ${dbp}hymns.location,
            ${dbp}hymns.sequence";
    $result = mysql_query($sql) or die(mysql_error()) ;
    modify_records_table($result, "delete.php");
    ?>
    </div>
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
    $sql = "INSERT INTO ${dbp}sermons (bibletext, outline, notes, service)
        VALUES ('${bibletext}', '${outline}', '${notes}', '${id}')";
    if (! mysql_query($sql)) {
        $sql = "UPDATE ${dbp}sermons SET bibletext = '${bibletext}',
            outline = '${outline}', notes = '${notes}'
            WHERE service = '${id}'";
        mysql_query($sql) or die(mysql_error());
    }
    $now = strftime('%T');
    header("Location: http://${this_script}?id=${id}&message=".urlencode("Sermon plans saved at ${now} server time."));
}
