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
        if (preg_match('/(\d+)_(.+)/', $posted, $matches))
        {
            $todelete[] = array("index" => $matches[1], "loc" => $matches[2]);
        }
    }
    $_SESSION['stage1'] = $todelete;
    ?>
    <html>
    <?=html_head("Delete Confirmation")?>
    <body>
        <p><a href=\"records.php\">Records</a><p>
        <h1>Confirm Deletions</h1>
        <ol>
        <?
        require("db-connection.php");
        foreach ($todelete as $deletion)
        {
            $sql = "SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
                hymns.book, hymns.number, hymns.note,
                hymns.location, days.name as dayname, days.rite, names.title
                FROM hymns LEFT OUTER JOIN days ON (hymns.service = days.pkey)
                LEFT OUTER JOIN names ON (hymns.number = names.number)
                    AND (hymns.book = names.book)
                WHERE days.pkey = ${deletion['index']}
                    AND hymns.location = '${deletion['loc']}'
                ORDER BY days.caldate DESC, location";
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
    </body>
    </html>
    <?
} elseif ("2" == $_GET['stage']) {
    //// Delete and acknowledge deletion.
    require("db-connection.php");
    foreach ($_SESSION['stage1'] as $todelete)
    {
        // Check to see if service has hymns at another location
        $sql = "SELECT number FROM hymns JOIN days
                ON (hymns.service = days.pkey)
                WHERE hymns.location != '${todelete['loc']}'
                  AND days.pkey = ${todelete['index']}";
        $result = mysql_query($sql) or die(mysql_error());

        if (! mysql_fetch_array($result))
        { // If not, delete the service (should cascade to hymns)
            $sql = "DELETE FROM days
                WHERE pkey = ${todelete['index']}";
            mysql_query($sql) or die(mysql_error());
        } else { // If so, delete only the hymns.
            $sql = "DELETE FROM hymns
                USING hymns JOIN days ON (hymns.service = days.pkey)
                WHERE days.pkey = ${todelete['index']}
                  AND hymns.location = ${todelete['location']}";
            mysql_query($sql) or die (mysql_error());
        }

    }
    header("Location: modify.php?message=".urlencode("Deletion(s) complete."));
    exit(0);
}

