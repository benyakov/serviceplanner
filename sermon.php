<? /* Interface for editing a sermon
    Copyright (C) 2012 Jesse Jacobsen

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    Send feedback or donations to: Jesse Jacobsen <jmatjac@gmail.com>

    Mailed donation may be sent to:
    Bethany Lutheran Church
    2323 E. 12th St.
    The Dalles, OR 97058
    USA
 */
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (array_key_exists('manuscript', $_GET)) {
    // Send the sermon manuscript, or a message saying it ain't there
    $q = $dbh->prepare("SELECT manuscript, mstype FROM {$dbp}sermons
        WHERE service=:id");
    $q->bindParam(":id", $_GET['id']);
    $q->execute();
    $q->bindColumn(1, $mss, PDO::PARAM_LOB);
    $q->bindColumn(2, $mstype, PDO::PARAM_STR, 256);
    $q->fetch(PDO::FETCH_BOUND);
    if (! $mstype) {
        setMessage("No manuscript has been saved for this sermon.");
        header("Location: sermon.php?id={$_GET['id']}");
        exit(0);
    }
    header("Content-type: {$mstype}");
    header("Content-disposition: attachment; filename=sermonmanuscript");
    fpassthru($mss);
    exit(0);
}
if (! array_key_exists('stage', $_GET)) {
    if (! is_numeric($_GET['id'])) {
        setMessage("Need a service first to edit a sermon plan.");
        header("Location: modify.php");
        exit(0);
    } else {
        $id = $_GET['id'];
    }
    ?><!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit a Sermon Plan")?>
    <body>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#deletems').change(function() {
                if ($(this).prop('checked')) {
                    $('#manuscript_file').val('')
                        .prop('disabled', true);
                } else {
                    $('#manuscript_file').prop('disabled', false);
                }
            });
        });
    </script>
    <? pageHeader();
    siteTabs($auth, "sermons"); ?>
        <div id="content-container">
        <p><a href="sermonreport.php?id=<?=${id}?>">Printable Sermon Report</a>
        | <a href="sermons.php">Browse All Sermon Plans</a></p>
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
            days.pkey as id, days.servicenotes, names.title
            FROM {$dbp}hymns AS hymns
            LEFT OUTER JOIN {$dbp}days AS days ON (hymns.service = days.pkey)
            LEFT OUTER JOIN {$dbp}names AS names ON
                (hymns.number = names.number)
                AND (hymns.book = names.book)
            WHERE days.pkey = :id
            ORDER BY days.caldate DESC, hymns.location,
                hymns.sequence");
    $q->bindParam(":id", $id);
    $q->execute() or die(array_pop($q->errorInfo()));
    modify_records_table($q, "delete.php");

    $q = $dbh->prepare("SELECT bibletext, outline, notes, mstype
        FROM {$dbp}sermons WHERE service=:id");
    $q->bindParam(":id", $id);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if ($row['mstype']) { ?>
        <div id="manuscript-link"><a href="http://<?=$this_script."?manuscript=1&id=".$id?>">Download Manuscript</a> (<?=$row['mstype']?>)</div>
    <? } ?>
        <div id="sermondata">
        <form action="http://<?=$this_script?>?stage=2" method="POST"
            enctype="multipart/form-data">
        <input type="hidden" id="service" name="service" value="<?=$id?>">
        <label for="manuscript_file">Upload new manuscript:</label><br />
        <input type="file" name="manuscript_file" id="manuscript_file"
            placeholder="Enter filename">
        <input type="checkbox" name="deletems" id="deletems">
        <label for="deletems">Delete saved manuscript file.</label><br />
        <label for="bibletext">Text:</label><br />
        <input type="text" id="bibletext" name="bibletext"
        size="80" maxlength="80" class="entryline"
        value="<?=trim($row['bibletext'])?>"
        placeholder="Enter a biblical text reference as basis for the sermon."><br />
        <label for="outline">Outline:</label><br />
        <textarea id="outline" name="outline"
         placeholder="Enter a bare outline of the sermon flow"><?=trim($row['outline'])?></textarea><br />
        <label for="notes">Notes:</label><br />
        <textarea id="notes" name="notes"
         placeholder="Include extra notes on the sermon"><?=trim($row['notes'])?></textarea><br />
        <button type="submit" value="Commit">Commit</button>
        <button type="reset">Reset</button>
        </form>
        </div>
    </div>
    </body>
    </html>
<?
} elseif (2 == $_GET["stage"])
{
    $dbh->beginTransaction();
    $msfile = "manuscript-{$dbconnection['dbname']}.txt";
    if (! move_uploaded_file($_FILES['manuscript_file']['tmp_name'], $msfile)
            || $_POST['deletems']) {
        $fp = tmpfile();
        $ft = "";
    } else {
        $fp = fopen($msfile, 'rb');
        $ft = $_FILES['manuscript_file']['type'];
    }
    if ($ft || $_POST['deletems']) {
        // Update the saved file blob and type field
        // This is handled separately from the other data updates
        $q = $dbh->prepare("INSERT INTO {$dbp}sermons
            (manuscript, mstype, service)
            VALUES (?, ?, ?");
        $q->bindParam(1, $fp, PDO::PARAM_LOB);
        $q->bindParam(2, $ft);
        $q->bindParam(3, $_POST['service']);
        if (! $q->execute()) {
            $q = $dbh->prepare("UPDATE {$dbp}sermons
                SET manuscript=?, mstype=?
                WHERE service=?");
            $q->bindParam(1, $fp, PDO::PARAM_LOB);
            $q->bindParam(2, $ft);
            $q->bindParam(3, $_POST['service']);
            $q->execute() or die(array_pop($q->errorInfo()));
        }
    }
    // Insert or update the sermon plans.
    $q = $dbh->prepare("INSERT INTO {$dbp}sermons
        (bibletext, outline, notes, service)
        VALUES (:bibletext, :outline, :notes, :id)");
    $q->bindParam(':bibletext', $_POST['bibletext']);
    $q->bindParam(':outline', $_POST['outline']);
    $q->bindParam(':notes', $_POST['notes']);
    $q->bindParam(':id', $_POST['service']);
    if (! $q->execute()) {
        $q = $dbh->prepare("UPDATE {$dbp}sermons
            SET bibletext = :bibletext,
            outline = :outline, notes = :notes
            WHERE service = :id");
        $q->bindParam(':bibletext', $_POST['bibletext']);
        $q->bindParam(':outline', $_POST['outline']);
        $q->bindParam(':notes', $_POST['notes']);
        $q->bindParam(':id', $_POST['service']);
        $q->execute() or die(array_pop($q->errorInfo()));
    }
    $dbh->commit();
    fclose($fp);
    $now = strftime('%T');
    setMessage("Sermon plans saved at {$now} server time.");
    header("Location: http://{$this_script}?id={$_POST['service']}");
}
