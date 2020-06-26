<?php /* Interface for editing a sermon
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
if (isset($_GET['manuscript'])) {
    // Send the sermon manuscript, or a message saying it ain't there
    $q = $db->prepare("SELECT manuscript, mstype FROM `{$db->getPrefix()}sermons`
        WHERE service=:id");
    $q->bindValue(":id", getGET('id'));
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (! $row['manuscript']) {
        setMessage("No manuscript has been saved for this sermon.");
        header("Location: sermon.php?id={".getGET('id')."}");
        exit(0);
    }
    $mss = fopen("{$thisdir}/{$row['manuscript']}", 'rb');
    if ($mss !== FALSE) {
        header("Content-type: {$row['mstype']}");
        header("Content-disposition: attachment; filename=sermonmanuscript");
        fpassthru($mss);
        fclose($mss);
        exit(0);
    } else {
        setMessage("There was trouble downloading your manuscript.");
    }
}
if (! isset($_GET['stage'])) {
    requireAuth("index.php", 2);
    if (! is_numeric(getGET('id'))) {
        setMessage("Need a service first to edit a sermon plan.");
        header("Location: modify.php");
        exit(0);
    } else {
        $id = getGET('id');
    }
    ?><!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit a Sermon Plan", array("smaller-records-listing.css"))?>
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
            $('#delete_button').click(function(e) {
                var c = confirm("Are you sure you want to delete this sermon plan?");
                if (c == false) {
                    return false;
                }
            });
        });
    </script>
    <? pageHeader();
    siteTabs("sermons"); ?>
        <div id="content-container">
        <div class="quicklinks"><a href="sermonreport.php?id=<?=$id?>">Printable Sermon Report</a>
        <a href="sermons.php">Browse All Sermon Plans</a></div>
        <h1>Edit a Sermon Plan</h1>
        <p class="explanation">This page is for planning a sermon for a
    particular service.  The service is displayed below.  You can also store
    your sermon manuscript file in the system, from this page.</p>
    <?php
    $q = $db->prepare("SELECT bibletext, outline, notes, mstype
        FROM `{$db->getPrefix()}sermons` WHERE service=:id");
    $q->bindParam(":id", $id);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if ($row['mstype']) { ?>
        <div id="manuscript-link"><a href="<?=$protocol?>://<?=$this_script."?manuscript=1&id=".$id?>">Download Manuscript</a> (<?=$row['mstype']?>)</div>
    <? } ?>
        <div id="sermondata">
        <form action="<?=$protocol?>://<?=$this_script?>?stage=2" method="POST"
            enctype="multipart/form-data" id="sermonform">
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
        <button type="submit" name="commit" value="Commit">Commit</button>
        <button type="reset" name="reset" value="Reset">Reset</button>
        <button type="submit" id="delete_button" name="delete" value="Delete">Delete this sermon plan</button>
        </form>
        </div>
    <?php
    $q = queryService($id);
    display_records_table($q, "delete.php");
    ?>
    <h2>Full Set of Lectionary Texts</h2>
    <?php
    $q = queryLectionary($id);
    $raw_lections = $q->fetchAll(PDO::FETCH_ASSOC);
    $order = array(
                "lesson1"   => "Lesson 1",
                "lesson2"   => "Lesson 2",
                "gospel"    => "Gospel",
                "psalm"     => "Psalm",
                "s2lesson"  => "Series 2 Lesson",
                "s2gospel"  => "Series 2 Gospel",
                "s3lesson"  => "Series 3 Lesson",
                "s3gospel"  => "Series 3 Gospel",
                "hymnabc"   => "Multi-Year Hymn",
                "hymn"      => "Hymn",
                "note"      => "Note"
            );
    foreach (array_keys($order) as $k) {
        if (isset($raw_lections[$k])) {
            $lections[$k] = $raw_lections[$k];
        }
    }

    ?>
    <table id="lectionary_texts">
    <hr><?php foreach ($order as $k->$field_name) { ?>
        <th><?=$field_name?></th>
    <?php } ?>
    </hr>
    <hr><?php foreach ($order as $k) { ?>
        <td><?=$lections[$k]?></td>
    <?php } ?>
    </hr>
    </table>
    <p id="query_time">Main MySQL query response time: <?=$GLOBALS['query_elapsed_time']?></p>
    </div>
    </body>
    </html>
<?php
} elseif (2 == getGET('stage'))
{
    requireAuth("{$protocol}://{$this_script}?id={$service}", 2);
    if (is_digits($_POST['service'])) {
        $service = str_pad($_POST['service'], 4, '0', STR_PAD_LEFT);
    } else {
        setMessage("Unrecognized service ID: ".htmlspecialchars($_POST['service']));
        header("Location: {$protocol}://{$this_script}");
    }
    if ($_POST['delete']) {
        $q = $db->prepare("DELETE FROM `{$db->getPrefix()}sermons`
            WHERE service=?");
        $q->execute(array($service)) or die(array_pop($q->errorInfo()));
        setMessage("Sermon plan deleted for this service.");
        header("Location: {$protocol}://{$this_script}?id={$service}");
        exit(0);
    }
    $dest = "";
    $db->beginTransaction();
    // Move the file into the uploads archive
    // This is handled separately from the other data updates
    $msfile = "manuscript-{$dbconnection['dbname']}.txt";
    if ((! move_uploaded_file($_FILES['manuscript_file']['tmp_name'], $msfile))
            || $_POST['deletems']) {
        $ft = "";
    } else {
        $msdir = md5(file_get_contents($msfile));
        $dest1 = substr($msdir, 0, 2);
        $dest2 = substr($msdir, 2, 2);
        if (! file_exists("{$thisdir}/uploads/{$dest1}/{$dest2}/{$msdir}"))
            mkdir("{$thisdir}/uploads/{$dest1}/{$dest2}/{$msdir}", 0750, TRUE);
        $dest = "uploads/{$dest1}/{$dest2}/{$msdir}/manuscript";
        @unlink($thisdir.'/'.$dest);
        rename($msfile, $thisdir.'/'.$dest);
        $ft = $_FILES['manuscript_file']['type'];
    }
    if ($ft || $_POST['deletems']) {
        $q = $db->prepare("SELECT 1 from `{$db->getPrefix()}sermons`
            WHERE service=?");
        $q->execute(array($service))
            or die(array_pop($q->errorInfo()));
        $exists = $q->fetchColumn();
        if ($exists) {
            $q = $db->prepare("UPDATE `{$db->getPrefix()}sermons`
                SET manuscript=?, mstype=?
                WHERE service=?");
        } else {
            $q = $db->prepare("INSERT INTO `{$db->getPrefix()}sermons`
                (manuscript, mstype, service)
                VALUES (?, ?, ?)");
        }
        $q->bindParam(1, $dest);
        $q->bindParam(2, $ft);
        $q->bindParam(3, $service);
        $q->execute() or die(array_pop($q->errorInfo()));
    }
    // Insert or update the sermon plans.
    $q = $db->prepare("INSERT INTO `{$db->getPrefix()}sermons`
        (bibletext, outline, notes, service)
        VALUES (:bibletext, :outline, :notes, :id)");
    $q->bindValue(':bibletext', $_POST['bibletext']);
    $q->bindValue(':outline', $_POST['outline']);
    $q->bindValue(':notes', $_POST['notes']);
    $q->bindParam(':id', $service);
    if (! $q->execute()) {
        $q = $db->prepare("UPDATE `{$db->getPrefix()}sermons`
            SET bibletext = :bibletext,
            outline = :outline, notes = :notes
            WHERE service = :id");
        $q->bindValue(':bibletext', $_POST['bibletext']);
        $q->bindValue(':outline', $_POST['outline']);
        $q->bindValue(':notes', $_POST['notes']);
        $q->bindParam(':id', $service);
        $q->execute() or die(array_pop($q->errorInfo()));
    }
    $db->commit();
    $now = strftime('%T');
    setMessage("Sermon plans saved at {$now} server time.");
    header("Location: {$protocol}://{$this_script}?id={$service}");
}
