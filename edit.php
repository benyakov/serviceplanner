<?
require("./init.php");
if (! $auth) {
    header("location: index.php");
    exit(0);
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! array_key_exists("stage", $_GET))
{
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n";
    echo html_head("Edit a Service");
    $backlink = "modify.php";
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
        showJsOnly();
        $("#date").datepicker({showOn:"both"});
        $(".hymn-number").keyup(fetchHymnTitle)
            .blur(fetchHymnTitle)
            .blur();
    })
    </script>
    <body>
    <div id="content-container">
    <h1>Edit a Service</h1>
    <p class="explanation">You can change any service- or hymn-related
    information on this page.  To add hymns to a service that are not
    already listed here, use the "Add Hymns" link.</p>
    <p><a href="<?=$backlink?>">Cancel Edit</a><p>
    <?
        $q = $dbh->prepare("SELECT
            DATE_FORMAT(days.caldate, '%c/%e/%Y') as date,
            hymns.book, hymns.number, hymns.note,
            hymns.pkey as hymnid, hymns.location,
            hymns.sequence, days.name as dayname, days.rite,
            days.servicenotes
            FROM ${dbp}hymns AS hymns
            RIGHT OUTER JOIN {$dbp}days AS days ON (hymns.service=days.pkey)
            WHERE days.pkey = '{$_GET['id']}'
            ORDER BY days.caldate DESC, hymns.location, hymns.sequence");
        $q->execute() or die(array_pop($q->errorInfo()));
        $row = $q->fetch(PDO::FETCH_ASSOC);
        ?>
        <form action="http://<?=$this_script?>?stage=2" method="POST">
        <button type="submit" value="Commit">Commit</button>
        <button type="reset">Reset</button>
        <input type="hidden" id="id" name="id" value="<?=$_GET['id']?>">
        <dl>
            <dt>Date</dt>
            <dd>
                <input type="text" id="date" name="date"
                 value="<?=$row['date']?>" required>
            </dd>
            <dt>Day Name</dt>
            <dd>
                <input type="text" id="dayname" name="dayname"
                 value="<?=$row['dayname']?>" size="50" maxlength="50">
            </dd>
            <dt>Order/Rite</dt>
            <dd>
                <input type="text" id="rite" name="rite"
                 value="<?=$row['rite']?>" size="50" maxlength="50">
            </dd>
            <dt>Service Notes</dt>
            <dd>
                <textarea id="servicenotes" name="servicenotes"><?=trim($row['servicenotes'])?></textarea>
            </dd>
        </dl>
        <p><a href="enter.php?date=<?=str_replace(' ', '', $row['date'])?>">Add Hymns at Any Location</a></p>
        <table>
        <tr class="heading"><th>Del</th><th>Seq</th><th>Book</th><th>#</th><th>Note</th>
            <th>Location</th><th>Title</th></tr>
        <?
        while ($row) {
            if ('' == $row['number']) {
                $row = $q->fetch(PDO::FETCH_ASSOC);
                continue;
            }
            ?>
            <tr>
                <td>
                    <input type="checkbox" id="delete_<?=$row['hymnid']?>"
                        name="delete_<?=$row['hymnid']?>">
                </td>
                <td>
                    <input type="number" id="sequence_<?=$row['hymnid']?>"
                        size="2" name="sequence_<?=$row['hymnid']?>"
                        value="<?=$row['sequence']?>" class="hymn-number"
                        class="hymn-sequence">
                </td>
                <td>
                    <select id="book_<?=$row['hymnid']?>" name="book_<?=$row['hymnid']?>">
                    <? foreach ($option_hymnbooks as $hymnbook) { ?>
                        <option <?
                            if ($hymnbook == $row['book']) echo "selected";
                                ?>><?=$hymnbook?></option>
                    <? } ?>
                    </select>
                </td>
                <td><input class="hymn-number" type="number"
                    id="number_<?=$row['hymnid']?>" size="5"
                    name="number_<?=$row['hymnid']?>" value="<?=$row['number']?>">
                </td>
                <td><input type="text" id="note_<?=$row['hymnid']?>" size="30"
                     maxlength="100" name="note_<?=$row['hymnid']?>"
                     value="<?=$row['note']?>" class="hymn-note">
                </td>
                <td><input type="text" id="location_<?=$row['hymnid']?>"
                    name="location_<?=$row['hymnid']?>"
                    value="<?=$row['location']?>" class="hymn-location">
                </td>
                <td><input type="text" id="title_<?=$row['hymnid']?>" size="50"
                     maxlength="50" name="title_<?=$row['hymnid']?>"
                     value="<?=$row['title']?>" class="hymn-title">
                </td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td colspan="3"><div class="hymn-past" id="past_<?=$row['hymnid']?>"></div></td>
            </tr>
            <?
            $row = $q->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        </table>
        <button type="submit" value="Commit">Commit</button>
        <button type="reset">Reset</button>
        </form>
        <p><a href="<?=$backlink?>">Cancel Edit</a><p>
        </div>
    </body>
    </html>
    <?
} elseif (2 == $_GET['stage']) {
    //// Commit changes to db
    // Pull out changes for each table into separate arrays

    $dbh->beginTransaction();
    $tohymns = array();
    $tonames = array();
    $todays = array();
    $todelete = array();
    $id = "";
    foreach ($_POST as $key => $value) {
        if (in_array($key, array("date", "dayname", "rite", "servicenotes"))) {
            $todays[$key] = $value;
        } elseif (preg_match('/delete_(\d+)/', $key, $matches)) {
            $todelete[] = $matches[1];
        } elseif (preg_match('/(\w+)_(\d+)/', $key, $matches)) {
            if ("title" == $matches[1])
            {
                $tonames[$matches[2]] = $value;
            } else {
                $tohymns[$matches[2]][$matches[1]] = $value;
            }
        }
    }
    print_r($todays);
    $dbh->rollBack();

    // Update hymn names
    foreach ($tonames as $key => $value) {
        if (! $value) { continue; }
        $q = $dbh->prepare("INSERT INTO {$dbp}names (title, number, book)
            VALUES (:title, :number, :book)");
        $q->bindParam(":title", $value);
        $q->bindParam(":number", $tohymns[$key]["number"]);
        $q->bindParam(":book", $tohymns[$key]["book"]);
        if (! $q->execute()) {
            $qu = $dbh->prepare("UPDATE {$dbp}names SET title = :title
                WHERE number = :number
                AND book = :book");
            $qu->bindParam(":title", $value);
            $qu->bindParam(":number", $tohymns[$key]["number"]);
            $qu->bindParam(":book", $tohymns[$key]["book"]);
            $qu->execute() or dieWithRollback($q, $q->queryString);
        }
    }
    // Update day information
    $q = $dbh->prepare("UPDATE `{$dbp}days` SET `caldate`=:date,
        `name`=:name, `rite`=:rite,
        `servicenotes`=:servicenotes
        WHERE `pkey` = :id");
    $q->bindParam(":date", strftime("%Y-%m-%d", strtotime($todays['date'])));
    $q->bindParam(":name", $todays['dayname']);
    $q->bindParam(":rite", $todays['rite']);
    $q->bindParam(":servicenotes", $todays['servicenotes']);
    $q->bindParam(":id", $_POST['id']);
    $q->execute() or dieWithRollback($q, $q->queryString);

    // Update hymns
    foreach ($tohymns as $hymnid => $h) {
        if (in_array($hymnid, $todelete)) { continue; }
        $q = $dbh->prepare("UPDATE {$dbp}hymns
            SET number=:number,
            note=:note, location=:location,
            book=:book, sequence=:sequence
            WHERE pkey=:hymnid");
        foreach ($h as $k=>$v) {
            $q->bindParam(":{$k}", $v);
        }
        $q->bindParam(":hymnid", $hymnid);
        $q->execute() or dieWithRollback($q, $q->queryString);
    }

    // Delete tagged hymns
    foreach ($todelete as $hymnid) {
        $q = $dbh->prepare("DELETE FROM {$dbp}hymns
            WHERE pkey = :hymnid");
        $q->bindParam(":hymnid", $hymnid);
        $q->execute() or dieWithRollback($q, $q->queryString);
    }
    setMessage("Edit complete.");
    header("Location: modify.php");
    exit(0);
}
?>

