<?
require("db-connection.php");
require("functions.php");
require("options.php");
require("setup-session.php");
$date = date("Y-m-d", $_GET['date']);
$sql = "SELECT name as dayname, rite, pkey as service, servicenotes
    FROM {$dbp}days
    WHERE `caldate` = '{$date}'
    ORDER BY dayname";
$result = mysql_query($sql) or die(mysql_error().$sql);
if (mysql_num_rows($result)) {
    echo "<fieldset><legend>Existing Services</legend><ul>";
    $tabindex = 3;
    while ($row = mysql_fetch_assoc($result)) {
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
