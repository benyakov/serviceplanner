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
if (array_key_exists('manuscript', $_GET)) {
    // Send the sermon manuscript, or a message saying it ain't there
    $q = $db->prepare("SELECT manuscript, mstype FROM `{$db->getPrefix()}sermons`
        WHERE service=:id");
    $q->bindParam(":id", $_GET['id']);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (! $row['manuscript']) {
        setMessage("No manuscript has been saved for this sermon.");
        header("Location: sermon.php?id={$_GET['id']}");
        exit(0);
    }
    $mss = fopen($row['manuscript'], 'rb');
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
        });
    </script>
    <? pageHeader();
    siteTabs($auth, "sermons"); ?>
        <div id="content-container">
        <div class="quicklinks"><a href="sermonreport.php?id=<?=${id}?>">Printable Sermon Report</a>
        <a href="sermons.php">Browse All Sermon Plans</a></div>
        <h1>Edit a Sermon Plan</h1>
        <p class="explanation">This page is for planning a sermon for a
    particular service.  The service is displayed below.  You can also store
    your sermon manuscript file in the system, from this page.</p>
    <?
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
    <?
    $q = queryService($id);
    display_records_table($q, "delete.php");
    ?>
    </div>
    </body>
    </html>
<?
} elseif (2 == $_GET["stage"])
{
    if (is_digits($_POST['service'])) {
        $service = str_pad($_POST['service'], 4, '0', STR_PAD_LEFT);
    } else {
        setMessage("Unrecognized service ID: ".htmlspecialchars($_POST['service']));
        header("Location: {$protocol}://{$this_script}");
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
        $dest1 = substr($service, 0, 2);
        $dest2 = substr($service, 2, 2);
        if (! file_exists("{$thisdir}/{$dest1}/{$dest2}/{$service}"))
            mkdir("{$thisdir}/{$dest1}/{$dest2}/{$service}", 0750, TRUE);
        $dest = "{$thisdir}/{$dest1}/{$dest2}/{$service}/manuscript";
        if (file_exists($dest)) unlink($dest);
        rename($msfile, $dest);
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
    $q->bindParam(':bibletext', $_POST['bibletext']);
    $q->bindParam(':outline', $_POST['outline']);
    $q->bindParam(':notes', $_POST['notes']);
    $q->bindParam(':id', $service);
    if (! $q->execute()) {
        $q = $db->prepare("UPDATE `{$db->getPrefix()}sermons`
            SET bibletext = :bibletext,
            outline = :outline, notes = :notes
            WHERE service = :id");
        $q->bindParam(':bibletext', $_POST['bibletext']);
        $q->bindParam(':outline', $_POST['outline']);
        $q->bindParam(':notes', $_POST['notes']);
        $q->bindParam(':id', $service);
        $q->execute() or die(array_pop($q->errorInfo()));
    }
    $db->commit();
    $now = strftime('%T');
    setMessage("Sermon plans saved at {$now} server time.");
    header("Location: {$protocol}://{$this_script}?id={$service}");
}
