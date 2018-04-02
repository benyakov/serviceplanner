<? /* Interface for editing a service
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
$options = getOptions();
requireAuth("index.php", 3);
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! isset($_GET['stage']))
{
    ?>
    <h1>Edit a Service</h1>
    <?
        $q = $db->prepare("SELECT
            DATE_FORMAT(days.caldate, '%Y-%m-%d') as date,
            hymns.book, hymns.number, hymns.note,
            hymns.pkey as hymnid, hymns.occurrence,
            hymns.sequence, days.name as dayname, days.rite, days.block,
            days.servicenotes
            FROM `{$db->getPrefix()}hymns` AS hymns
            RIGHT OUTER JOIN `{$db->getPrefix()}days` AS days ON (hymns.service=days.pkey)
            WHERE days.pkey = ?
            ORDER BY days.caldate DESC, hymns.occurrence, hymns.sequence");
        $q->execute(array(getGET('id'))) or die(array_pop($q->errorInfo()));
        $row = $q->fetch(PDO::FETCH_ASSOC);
        ?>
        <form action="<?=$protocol?>://<?=$this_script?>?stage=2" method="POST">
        <button type="submit" value="Commit">Commit</button>
        <button type="reset">Reset</button>
        <input type="hidden" id="id" name="id" value="<?=getGET('id')?>">
        <dl>
            <dt>Date</dt>
            <dd>
                <input type="date" id="date" name="date"
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
            <dt>Block Plan</dt>
            <dd>
                <select id="block" name="block"
                    data-default="<?=$row['block']?>">
                    <option value="None" selected>None</option>
                </select>
            </dd>
        </dl>
        <table id="hymnentries"><tbody id="sortablelist">
        <tr class="heading"><td></td><th>Del</th><th>Seq</th><th>Book</th><th>#</th><th>Note</th>
            <th>Occurrence</th><th>Title</th><th>Recent Uses</th></tr>
        <?
        while ($row) {
            if ('' == getIndexOr($row, 'number')) {
                $row = $q->fetch(PDO::FETCH_ASSOC);
                continue;
            }
            ?>
                <tr class="ui-state-default" id="hymn-<?=getIndexOr($row, 'hymnid')?>">
                <td><span class="ui-icon ui-icon-arrowthick-2-n-s"></td>
                <td>
                    <input type="checkbox" id="delete_<?=getIndexOr($row, 'hymnid')?>"
                        name="delete_<?=getIndexOr($row, 'hymnid')?>">
                </td>
                <td>
                    <input type="number" id="sequence_<?=getIndexOr($row, 'hymnid')?>"
                        size="2" name="sequence_<?=getIndexOr($row, 'hymnid')?>"
                        value="<?=getIndexOr($row, 'sequence')?>" class="edit-sequence">
                </td>
                <td>
                    <select id="book_<?=getIndexOr($row, 'hymnid')?>" name="book_<?=getIndexOr($row, 'hymnid')?>">
                    <? foreach ($options->get('hymnbooks') as $hymnbook) { ?>
                        <option <?
                            if ($hymnbook == getIndexOr($row, 'book')) echo "selected";
                                ?>><?=$hymnbook?></option>
                    <? } ?>
                    </select>
                </td>
                <td><input class="edit-number" type="number" min="0"
                    id="number_<?=getIndexOr($row, 'hymnid')?>" size="5"
                    name="number_<?=getIndexOr($row, 'hymnid')?>" value="<?=getIndexOr($row, 'number')?>">
                </td>
                <td><input type="text" id="note_<?=getIndexOr($row, 'hymnid')?>" size="30"
                     maxlength="100" name="note_<?=getIndexOr($row, 'hymnid')?>"
                     value="<?=getIndexOr($row, 'note')?>" class="edit-note">
                </td>
                <td><input type="text" id="occurrence_<?=getIndexOr($row, 'hymnid')?>"
                    name="occurrence_<?=getIndexOr($row, 'hymnid')?>"
                    value="<?=getIndexOr($row, 'occurrence')?>" class="edit-occurrence">
                </td>
                <td><input type="text" id="title_<?=getIndexOr($row, 'hymnid')?>" size="50"
                     maxlength="50" name="title_<?=getIndexOr($row, 'hymnid')?>"
                     value="<?=getIndexOr($row, 'title')?>" class="edit-title"
                     data-hymn="<?=getIndexOr($row, 'hymnid')?>">
                    <a href="#" data-hymn="<?=getIndexOr($row, 'hymnid')?>"
                     class="hidden save-title command-link"
                     id="savetitle_<?=getIndexOr($row, 'hymnid')?>">Save</a>
                </td>
                <td id="past_<?=getIndexOr($row, 'hymnid')?>"></td>
            </tr>
            <?
            $row = $q->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        <tr class="table-template ui-state-default" data-index="0" id="hymn-0">
            <td><span class="ui-icon ui-icon-arrowthick-2-n-s"></td>
            <td>
                <input type="checkbox" id="delete_new" name="delete_new">
            </td>
            <td>
                <input type="number" id="sequence_new" value="1"
                    size="2" name="sequence_new" class="edit-sequence">
            </td>
            <td>
                <select id="book_new" name="book_new">
                <? foreach ($options->get('hymnbooks') as $hymnbook) { ?>
                    <option><?=$hymnbook?></option>
                <? } ?>
                </select>
            </td>
            <td><input class="edit-number" type="number"
                id="number_new" size="5" name="number_new">
            </td>
            <td><input type="text" id="note_new" size="30"
                 maxlength="100" name="note_new" class="edit-note">
            </td>
            <td><input type="text" id="occurrence_new"
                value="<?=$options->getDefault("", "defaultoccurrence")?>"
                name="occurrence_new" class="edit-occurrence">
            </td>
            <td><input type="text" id="title_new" size="50"
                 maxlength="50" name="title_new" class="edit-title">
                <a href="#" data-hymn="<?=$i?>"
                 class="hidden save-title command-link"
                 id="savetitle_new">Save</a>
            </td>
            <td id="past_new"></td>
        </tr>
        </tbody></table>
        <a id="addHymn" class="jsonly command-link" tabindex="200"
            href="#" >Add another hymn.</a>
        <button type="submit" value="Commit">Commit</button>
        <button type="reset">Reset</button>
        </form>
        </div>
    <script type="text/javascript">
        setupSortableList();
    </script>
    <?
} elseif (2 == getGET('stage')) {
    //// Commit changes to db
    // Pull out changes for each table into separate arrays
    $db->beginTransaction();
    $tohymns = array();
    $tonames = array();
    $todays = array();
    $todelete = array();
    $id = "";
    foreach ($_POST as $key => $value) {
        if (in_array($key, array("date", "dayname", "rite",
            "servicenotes", "block")))
        {
            $todays[$key] = $value;
        } elseif (preg_match('/delete_(\d+)/', $key, $matches)) {
            $todelete[] = $matches[1];
        } elseif (preg_match('/(\w+)_([-0-9a-z]+)/', $key, $matches)) {
            if ("title" == $matches[1]) {
                $tonames[$matches[2]] = $value;
            } elseif ($matches[2] != "new") {
                $tohymns[$matches[2]][$matches[1]] = $value;
            }
        }
    }
    // Update hymn names
    $ititle = $inumber = $ibook = 0;
    $q = $db->prepare("INSERT INTO {$db->getPrefix()}names (title, number, book)
        VALUES (:title, :number, :book)");
    $q->bindParam(":title", $ititle);
    $q->bindParam(":number", $inumber);
    $q->bindParam(":book", $ibook);
    $qu = $db->prepare("UPDATE {$db->getPrefix()}names SET title = :title
        WHERE number = :number
        AND book = :book");
    $qu->bindParam(":title", $ititle);
    $qu->bindParam(":number", $inumber);
    $qu->bindParam(":book", $ibook);
    foreach ($tonames as $key => $value) {
        if (! $value) { continue; }
        $ititle = $value;
        $inumber = $tohymns[$key]["number"];
        $ibook = $tohymns[$key]["book"];
        if (! $q->execute()) {
            $qu->execute() or die(array_pop($qu->errorInfo()));
        }
    }
    // Update day information
    $q = $db->prepare("UPDATE `{$db->getPrefix()}days` SET `caldate`=:date,
        `name`=:name, `rite`=:rite,
        `servicenotes`=:servicenotes, `block`=:block
        WHERE `pkey` = :id");
    $q->bindValue(":date", strftime("%Y-%m-%d", strtotime($todays['date'])));
    $q->bindValue(":name", $todays['dayname']);
    $q->bindValue(":rite", $todays['rite']);
    $q->bindValue(":servicenotes", $todays['servicenotes']);
    $q->bindValue(":block", $todays['block']);
    $q->bindValue(":id", $_POST['id']);
    $q->execute() or dieWithRollback($q, $q->queryString);

    // Update hymns
    $q = $db->prepare("UPDATE {$db->getPrefix()}hymns
        SET number=:number,
        note=:note, occurrence=:occurrence,
        book=:book, sequence=:sequence
        WHERE pkey=:hymnid");
    $qi = $db->prepare("INSERT INTO {$db->getPrefix()}hymns
        (service, occurrence, book, number, note, sequence)
        VALUES (:service, :occurrence, :book, :number, :note, :sequence)");
    $qi->bindValue(":service", $_POST['id']);
    foreach ($tohymns as $hymnid => $h) {
        if (in_array($hymnid, $todelete)) { continue; }
        foreach ($h as $k=>$v) {
            $q->bindValue(":{$k}", $v);
            $qi->bindValue(":{$k}", $v);
        }
        if (is_numeric($hymnid)) {
            $q->bindValue(":hymnid", $hymnid);
            $q->execute() or dieWithRollback($q, "Couldn't update hymn.");
        } else {
            $qi->execute() or dieWithRollback($q, "Couldn't insert new hymn.");
        }
    }

    // Delete tagged hymns
    $q = $db->prepare("DELETE FROM {$db->getPrefix()}hymns
        WHERE pkey = :hymnid");
    $hymnid = 0;
    $q->bindParam(":hymnid", $hymnid);
    foreach ($todelete as $id) {
        $hymnid = $id;
        $q->execute() or dieWithRollback($q, $q->queryString);
    }
    $db->commit();
    setMessage("Edit complete.");
    header("Location: modify.php");
    exit(0);
}
?>

