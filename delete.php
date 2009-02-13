<?
session_start();
require("functions.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! array_key_exists("stage", $_GET))
{
    // Put items to delete into an array.
    $todelete = array();
    foreach ($_POST as $posted=>$value)
    {
        if (preg_match('/(\d+)_(.*)/', $posted, $matches))
        {
            $todelete[] = array("index" => $matches[1], "loc" => $matches[2]);
        }
    }
    $_SESSION['stage1'] = $todelete;
    ?>
    <html>
    <?=html_head("Delete Confirmation")?>
    <body>
        <div id="content_container">
        <p><a href="modify.php">Abort</a><p>
        <h1>Confirm Deletions</h1>
        <ol>
        <?
        require("db-connection.php");
        foreach ($todelete as $deletion)
        {
            $sql = "SELECT DATE_FORMAT(${dbp}days.caldate, '%e %b %Y') as date,
                ${dbp}hymns.book, ${dbp}hymns.number, ${dbp}hymns.note,
                ${dbp}hymns.location, ${dbp}days.name as dayname,
                ${dbp}days.rite, ${dbp}names.title
                FROM ${dbp}hymns
                RIGHT OUTER JOIN ${dbp}days ON (${dbp}hymns.service=${dbp}days.pkey)
                LEFT OUTER JOIN ${dbp}names ON (${dbp}hymns.number=${dbp}names.number)
                    AND (${dbp}hymns.book=${dbp}names.book)
                WHERE ${dbp}days.pkey = '${deletion['index']}'
                ORDER BY ${dbp}days.caldate DESC, location";
            $result = mysql_query($sql) or die(mysql_error());
            echo "<li>\n";
            display_records_table($result);
            echo "</li>\n";
        }
        ?>
        </ol>
        <form action="http://<?=$this_script."?stage=2"?>" method="POST">
        <input type="submit" value="Confirm">
        </form>
        </div>
    </body>
    </html>
    <?
} elseif ("2" == $_GET['stage']) {
    //// Delete and acknowledge deletion.
    require("db-connection.php");
    foreach ($_SESSION['stage1'] as $todelete)
    {
        // Check to see if service has hymns at another location
        $sql = "SELECT number FROM ${dbp}hymns JOIN ${dbp}days
                ON (${dbp}hymns.service = ${dbp}days.pkey)
                WHERE ${dbp}hymns.location != '${todelete['loc']}'
                  AND ${dbp}days.pkey = ${todelete['index']}";
        $result = mysql_query($sql) or die(mysql_error());

        if (! mysql_fetch_array($result))
        { // If not, delete the service (should cascade to hymns)
            $sql = "DELETE FROM ${dbp}days
                WHERE pkey = ${todelete['index']}";
            mysql_query($sql) or die(mysql_error());
        } else { // If so, delete only the hymns.
            $sql = "DELETE FROM ${dbp}hymns
                USING ${dbp}hymns JOIN ${dbp}days
                    ON (${dbp}hymns.service = ${dbp}days.pkey)
                WHERE ${dbp}days.pkey = ${todelete['index']}
                  AND ${dbp}hymns.location = ${todelete['location']}";
            mysql_query($sql) or die (mysql_error());
        }

    }
    header("Location: modify.php?message=".urlencode("Deletion(s) complete."));
    exit(0);
}

