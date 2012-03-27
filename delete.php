<?
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$ajax = $_GET['ajaxconfirm'];
if ((! array_key_exists("stage", $_GET)) || $ajax) {
    // Put items to delete into an array.
    $todelete = array();
    foreach ($_POST as $posted=>$value) {
        if (preg_match('/(\d+)_(.*)/', $posted, $matches)) {
            $todelete[str_replace('_', ' ', $matches[2])][] = $matches[1];
        }
    }
    $_SESSION[$sprefix]['stage1'] = $todelete;
    if (! $ajax) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <?=html_head("Delete Confirmation")?>
    <body>
        <div id="content-container">
        <p><a href="modify.php">Abort</a><p>
    <? } ?>
        <h1>Confirm Deletions</h1>
        <?
        print_r($_POST);
        $dbh->beginTransaction();
        foreach ($todelete as $loc => $deletions) {
            if (0 == strlen($loc)) {
                $whereclause = "";
            } else {
                $whereclause = "AND hymns.location = :location";
            }
            $deletions = implode(", ", array_map($dbh->quote, $deletions));
            $q = $dbh->prepare("
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
                WHERE days.pkey IN({$deletions})
                {$whereclause}
                ORDER BY days.caldate DESC, hymns.service DESC,
                    hymns.location, hymns.sequence");
            if ($whereclause) {
                $q->bindValue(":location", $loc);
            }
            $q->execute() or dieWithRollback($q, ".");
            display_records_table($q);
        }
        $dbh->commit();
        ?>
        <form action="http://<?=$this_script."?stage=2"?>" method="POST">
        <button type="submit">Confirm</button>
        </form>
    <? if (! $ajax) { ?>
        </div>
    </body>
    </html>
    <?  }
} elseif ("2" == $_GET['stage']) {
    //// Delete and acknowledge deletion.
    $dbh->beginTransaction();
    foreach ($_SESSION[$sprefix]['stage1'] as $loc => $deletions) {
        // Check to see if service has hymns at another location
        $deletions = implode(", ", array_map($dbh->quote, $deletions));
        $q = $dbh->prepare("SELECT number
                FROM {$dbp}hymns as hymns
                JOIN {$dbp}days as days
                ON (hymns.service = days.pkey)
                WHERE hymns.location != :location
                    AND days.pkey IN({$deletions})
                LIMIT 1");
        $q->bindValue(":location", $loc);
        $q->execute();
        if ($q->fetch()) {
            // If so, delete only the hymns.
            $q = $dbh->prepare("DELETE FROM {$dbp}hymns as hymns
                USING hymns JOIN {$dbp}days as days
                    ON (hymns.service = days.pkey)
                WHERE days.pkey IN({$deletions})
                  AND hymns.location = :location");
            $q->bindValue(":location", $loc);
            $q->execute() or dieWithRollback($q, ".");
        } else {
            // If not, delete the service (should cascade to hymns)
            $q = $dbh->prepare("DELETE FROM `{$dbp}days`
                WHERE `pkey` IN({$deletions})");
            $q->execute() or die(array_pop($q->errorInfo()));
        }
    }
    $dbh->commit();
    setMessage("Deletion(s) complete.");
    header("Location: modify.php");
}

