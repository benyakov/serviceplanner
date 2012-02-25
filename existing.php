<?
require("db-connection.php");
require("functions.php");
require("options.php");
require("setup-session.php");
$date = date("Y-m-d", $_GET['date']);
$q = $dbh->query("SELECT name as dayname, rite, pkey as service, servicenotes
    FROM {$dbp}days
    WHERE `caldate` = '{$date}'
    ORDER BY dayname");
$q->execute() or die(array_pop($q->errorInfo()));
if ($q->rowCount()) {
    echo "<fieldset><legend>Existing Services</legend><ul>";
    $tabindex = 3;
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $thisname = "existing_{$row['service']}";
        $servicenoteFormatted = translate_markup($row['servicenotes']);
        echo "<li><input type=\"checkbox\" tabindex=\"{$tabindex}\" class=\"existingservice\" name=\"{$thisname}\" id=\"{$thisname}\"><label for=\"{$thisname}\">{$row['dayname']} ({$row['rite']})</label><br/><div class=\"servicenote\">{$servicenoteFormatted}</div></li>";
        if ($tabindex < 25) $tabindex++;
    }
    echo "</ul></fieldset>";
} else {
    echo "";
}
?>
