<?
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! array_key_exists("stage", $_GET)) {
    // Put items to delete into an array.
    $todelete = array();
    foreach ($_POST as $posted=>$value) {
        if (preg_match('/(\d+)_(.*)/', $posted, $matches)) {
            $todelete[] = array("index" => $matches[1],
                "loc" => str_replace('_', ' ', $matches[2]));
        }
    }
    $_SESSION[$sprefix]['stage1'] = $todelete;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("Delete Confirmation")?>
    <body>
        <div id="content-container">
        <p><a href="modify.php">Abort</a><p>
        <h1>Confirm Deletions</h1>
        <ol>
        <?
        $dbh->beginTransaction();
        foreach ($todelete as $deletion) {
            if (0 == strlen($deletion['loc'])) {
                $whereclause = "";
            } else {
                $whereclause = "AND hymns.location = '{$deletion['loc']}'";
            }
            $q = $dbh->query("
                SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
                hymns.book, hymns.number, hymns.note,
                hymns.location, days.name as dayname, days.rite,
                days.pkey as id, days.servicenotes, names.title
                FROM {$dbp}hymns AS hymns
                RIGHT OUTER JOIN {$dbp}days AS days
                    ON (hymns.service = days.pkey)
                LEFT OUTER JOIN {$dbp}names AS names
                    ON (hymns.number = names.number)
                        AND (hymns.book = names.book)
                WHERE days.pkey = '{$deletion['index']}'
                {$whereclause}
                ORDER BY days.caldate DESC, hymns.service DESC,
                    hymns.location, hymns.sequence")
                or dieWithRollback($q, ".");
            echo "<li>\n";
            display_records_table($q);
            echo "</li>\n";
        }
        $dbh->commit();
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
    $dbh->beginTransaction();
    foreach ($_SESSION[$sprefix]['stage1'] as $todelete) {
        // Check to see if service has hymns at another location
        $q = $dbh->prepare("SELECT number
                FROM {$dbp}hymns as hymns
                JOIN {$dbp}days as days
                ON (hymns.service = days.pkey)
                WHERE hymns.location != '{$todelete['loc']}'
                  AND days.pkey = {$todelete['index']}");
        $q->execute();

        if (! $q->fetch())) {
            // If not, delete the service (should cascade to hymns)
            $q = $dbh->prepare("DELETE FROM `{$dbp}days`
                WHERE `pkey` = '{$todelete['index']}'");
            $q->execute() or die(array_pop($q->errorInfo()));
        } else { // If so, delete only the hymns.
            $q = $dbh->prepare("DELETE FROM {$dbp}hymns as hymns
                USING hymns JOIN {$dbp}days as days
                    ON (hymns.service = days.pkey)
                WHERE days.pkey = {$todelete['index']}
                  AND hymns.location = '{$todelete['loc']}'");
            $q->execute() or dieWithRollback($q, ".");
        }

    }
    $dbh->commit();
    $_SESSION[$sprefix]['message'] = "Deletion(s) complete.";
    header("Location: modify.php");
    exit(0);
}

