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
if (! $auth) {
    header("location: index.php");
    exit(0);
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! array_key_exists("stage", $_GET))
{
    ?>
    <h1>Edit a Service</h1>
    <?
        $q = $dbh->prepare("SELECT
            DATE_FORMAT(days.caldate, '%Y-%m-%d') as date,
            hymns.book, hymns.number, hymns.note,
            hymns.pkey as hymnid, hymns.location,
            hymns.sequence, days.name as dayname, days.rite, days.block,
            days.servicenotes
            FROM `${dbp}hymns` AS hymns
            RIGHT OUTER JOIN `{$dbp}days` AS days ON (hymns.service=days.pkey)
            WHERE days.pkey = ?
            ORDER BY days.caldate DESC, hymns.location, hymns.sequence");
        $q->execute(array($_GET['id'])) or die(array_pop($q->errorInfo()));
        $row = $q->fetch(PDO::FETCH_ASSOC);
        ?>
        <form action="http://<?=$this_script?>?stage=2" method="POST">
        <button type="submit" value="Commit">Commit</button>
        <button type="reset">Reset</button>
        <input type="hidden" id="id" name="id" value="<?=$_GET['id']?>">
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
        <table id="hymnentries"><tbody>
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
                        value="<?=$row['sequence']?>" class="edit-sequence">
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
                <td><input class="edit-number" type="number" min="0"
                    id="number_<?=$row['hymnid']?>" size="5"
                    name="number_<?=$row['hymnid']?>" value="<?=$row['number']?>">
                </td>
                <td><input type="text" id="note_<?=$row['hymnid']?>" size="30"
                     maxlength="100" name="note_<?=$row['hymnid']?>"
                     value="<?=$row['note']?>" class="edit-note">
                </td>
                <td><input type="text" id="location_<?=$row['hymnid']?>"
                    name="location_<?=$row['hymnid']?>"
                    value="<?=$row['location']?>" class="edit-location">
                </td>
                <td><input type="text" id="title_<?=$row['hymnid']?>" size="50"
                     maxlength="50" name="title_<?=$row['hymnid']?>"
                     value="<?=$row['title']?>" class="edit-title"
                     data-hymn="<?=$row['hymnid']?>">
                    <a href="#" data-hymn="<?=$row['hymnid']?>"
                     class="hidden save-title command-link"
                     id="savetitle_<?=$row['hymnid']?>">Save</a>
                </td>
            </tr>
            <?
            $row = $q->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        <tr class="table-template" data-index="0">
            <td>
                <input type="checkbox" id="delete_new" name="delete_new">
            </td>
            <td>
                <input type="number" id="sequence_new" value="1"
                    size="2" name="sequence_new" class="edit-sequence">
            </td>
            <td>
                <select id="book_new" name="book_new">
                <? foreach ($option_hymnbooks as $hymnbook) { ?>
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
            <td><input type="text" id="location_new"
                name="location_new" class="edit-location">
            </td>
            <td><input type="text" id="title_new" size="50"
                 maxlength="50" name="title_new" class="edit-title">
                <a href="#" data-hymn="<?=$i?>"
                 class="hidden save-title command-link"
                 id="savetitle_new">Save</a>
            </td>
        </tr>
        </tbody></table>
        <a id="addHymn" class="jsonly command-link" tabindex="200"
            href="#" >Add another hymn.</a>
        <button type="submit" value="Commit">Commit</button>
        <button type="reset">Reset</button>
        </form>
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
    $q = $dbh->prepare("INSERT INTO {$dbp}names (title, number, book)
        VALUES (:title, :number, :book)");
    $q->bindParam(":title", $ititle);
    $q->bindParam(":number", $inumber);
    $q->bindParam(":book", $ibook);
    $qu = $dbh->prepare("UPDATE {$dbp}names SET title = :title
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
    $q = $dbh->prepare("UPDATE `{$dbp}days` SET `caldate`=:date,
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
    $q = $dbh->prepare("UPDATE {$dbp}hymns
        SET number=:number,
        note=:note, location=:location,
        book=:book, sequence=:sequence
        WHERE pkey=:hymnid");
    $qi = $dbh->prepare("INSERT INTO {$dbp}hymns
        (service, location, book, number, note, sequence)
        VALUES (:service, :location, :book, :number, :note, :sequence)");
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
    $q = $dbh->prepare("DELETE FROM {$dbp}hymns
        WHERE pkey = :hymnid");
    $hymnid = 0;
    $q->bindParam(":hymnid", $hymnid);
    foreach ($todelete as $id) {
        $hymnid = $id;
        $q->execute() or dieWithRollback($q, $q->queryString);
    }
    $dbh->commit();
    setMessage("Edit complete.");
    header("Location: modify.php");
    exit(0);
}
?>

