<?
require("./init.php");
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Upcoming Hymns")?>
<script type="text/javascript">
    auth = "<?=authId()?>";
    <? if (! is_link($_SERVER['SCRIPT_FILENAME'])) {
        ?>
    $(document).ready(function() {
        setupLogin();
    });
    <? } ?>
</script>
<body>
    <header>
    <div id="login"></div>
    <?showMessage();?>
    </header>
<?
if (! is_link($_SERVER['SCRIPT_FILENAME'])) {
    echo sitetabs($sitetabs, $script_basename);
}   ?>
<div id="content-container">
<h1>Upcoming Hymns</h1>
<?php
$q = $dbh->prepare("SELECT DATE_FORMAT(days.caldate, '%e %b %Y') as date,
    hymns.book, hymns.number, hymns.note,
    hymns.location, days.name as dayname, days.rite,
    days.servicenotes, names.title
    FROM {$dbp}hymns AS hymns
    LEFT OUTER JOIN {$dbp}days AS days
        ON (hymns.service = days.pkey)
    LEFT OUTER JOIN {$dbp}names AS names
        ON (hymns.number = names.number)
        AND (hymns.book = names.book)
    WHERE days.caldate >= CURDATE()
    ORDER BY days.caldate, hymns.service,
        hymns.location, hymns.sequence");
$q->execute() or die(array_pop($q->errorInfo()));

display_records_table($q);
?>
</div>
</body>
</html>
