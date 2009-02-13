<html>
<?
require("db-connection.php");
require("functions.php");
require("options.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
echo html_head("Edit a Service");
if (! array_key_exists("stage", $_GET))
{
    $backlink = "modify.php";
    ?>
    <body>
    <div id="content_container">
    <h1>Edit a Service</h1>
    <p class="explanation">You can change any service- or hymn-related
    information on this page.  To add hymns to a service that are not
    already listed here, use the "Add Hymns" link.</p>
    <p><a href="<?=$backlink?>">Cancel Edit</a><p>
    <?
        $sql = "SELECT DATE_FORMAT(${dbp}days.caldate, '%e %b %Y') as date,
            ${dbp}hymns.book, ${dbp}hymns.number, ${dbp}hymns.note,
            ${dbp}hymns.pkey as hymnid, ${dbp}hymns.location,
            ${dbp}hymns.sequence, ${dbp}days.name as dayname, ${dbp}days.rite,
            ${dbp}names.title
            FROM ${dbp}hymns
            RIGHT OUTER JOIN ${dbp}days ON (${dbp}hymns.service=${dbp}days.pkey)
            LEFT OUTER JOIN ${dbp}names
                ON (${dbp}hymns.number=${dbp}names.number)
                AND (${dbp}hymns.book=${dbp}names.book)
            WHERE ${dbp}days.pkey = '${_GET['id']}'
            ORDER BY ${dbp}days.caldate DESC, ${dbp}hymns.location,
                ${dbp}hymns.sequence";
        $result = mysql_query($sql) or die(mysql_error());
        $row = mysql_fetch_assoc($result);
        ?>
        <form action="http://<?=$this_script?>?stage=2" method="POST">
        <input type="submit" value="Commit"><input type="reset">
        <input type="hidden" id="id" name="id" value="<?=$_GET['id']?>">
        <dl>
            <dt>Date</dt>
            <dd>
                <input type="text" id="date" name="date"
                 value="<?=$row['date']?>">
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
        </dl>
        <p><a href="enter.php?date=<?=str_replace(' ', '', $row['date'])?>">Add Hymns at Any Location</a></p>
        <table>
        <tr class="heading"><th>Del</th><th>Seq</th><th>Book</th><th>#</th><th>Note</th>
            <th>Location</th><th>Title</th></tr>
        <?
        while ($row)
        {
            if ('' == $row['number'])
            {
                $row = mysql_fetch_assoc($result);
                continue;
            }
            ?>
            <tr>
                <td>
                    <input type="checkbox" id="delete_<?=$row['hymnid']?>"
                        name="delete_<?=$row['hymnid']?>">
                </td>
                <td>
                    <input type="text" id="sequence_<?=$row['hymnid']?>"
                        size="2" name="sequence_<?=$row['hymnid']?>"
                        value="<?=$row['sequence']?>">
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
                <td><input type="text" id="number_<?=$row['hymnid']?>" size="5"
                    name="number_<?=$row['hymnid']?>" value="<?=$row['number']?>">
                </td>
                <td><input type="text" id="note_<?=$row['hymnid']?>" size="30"
                     maxlength="100" name="note_<?=$row['hymnid']?>"
                     value="<?=$row['note']?>">
                </td>
                <td><input type="text" id="location_<?=$row['hymnid']?>"
                    name="location_<?=$row['hymnid']?>" value="<?=$row['location']?>">
                </td>
                <td><input type="text" id="title_<?=$row['hymnid']?>" size="50"
                     maxlength="50" name="title_<?=$row['hymnid']?>"
                     value="<?=$row['title']?>">
                </td>
            </tr>
            <?
            $row = mysql_fetch_assoc($result);
        }
        ?>
        </table>
        <input type="submit" value="Commit"><input type="reset">
        </form>
        <p><a href="<?=$backlink?>">Cancel Edit</a><p>
        </div>
    </body>
    </html>
    <?
} elseif (2 == $_GET['stage']) {
    //// Commit changes to db
    // Pull out changes for each table into separate arrays

    $tohymns = array();
    $tonames = array();
    $todays = array();
    $todelete = array();
    $id = "";
    foreach ($_POST as $key => $value)
    {
        if (in_array($key, array("date", "dayname", "rite")))
        {
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

    // Update hymn names
    foreach ($tonames as $key => $value)
    {
        $title = mysql_esc($value);
        $number = mysql_esc($tohymns[$key]["number"]);
        $book = mysql_esc($tohymns[$key]["book"]);
        $sql = "INSERT INTO ${dbp}names (title, number, book)
            VALUES ('${title}', '${number}', '{$book}')";
        if (! mysql_query($sql))
        {
            $sql = "UPDATE ${dbp}names SET title = '${title}'
                WHERE number = '${number}'
                AND book = '${book}'";
            mysql_query($sql) or die(mysql_error());
        }
    }
    // Update day information
    $date = strftime("%Y-%m-%d", strtotime($todays['date']));
    $name = mysql_esc($todays['dayname']);
    $rite = mysql_esc($todays['rite']);
    $id = $_POST['id'];
    $sql = "UPDATE ${dbp}days SET caldate='${date}',
        name='${name}', rite='${rite}'
        WHERE pkey = '${id}'";
    mysql_query($sql) or die(mysql_error());

    // Update hymns
    foreach ($tohymns as $hymnid => $h)
    {
        if (in_array($hymnid, $todelete)) { continue; }
        $hymn = mysql_esc_array($h);
        $sql = "UPDATE ${dbp}hymns SET number='${hymn['number']}',
            note='${hymn['note']}', location='${hymn['location']}',
            book='${hymn['book']}', sequence='${hymn['sequence']}'
            WHERE pkey = '${hymnid}'";
        mysql_query($sql) or die(mysql_error());
    }

    // Delete tagged hymns
    foreach ($todelete as $hymnid) {
        $sql = "DELETE FROM ${dbp}hymns WHERE pkey = '${hymnid}'";
        mysql_query($sql) or die(mysql_error());
    }
    header("Location: modify.php?message=".urlencode("Edit complete."));
    exit(0);
}
?>

