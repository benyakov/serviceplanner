<?
require("db-connection.php");
require("functions.php");
require("options.php");
require("setup-session.php");

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
header("Content-type: application/json");

$sql = "SELECT `names`.`title`,
    DATE_FORMAT(`days`.`caldate`, '%e %b %Y') as date,
    `hymns`.`location` as location
    FROM `{$dbp}names` AS `names`
    JOIN `{$dbp}hymns` AS `hymns`
      ON (`names`.`book` = `hymns`.`book`
      AND `names`.`number` = `hymns`.`number`)
    JOIN `{$dbp}days` AS `days`
      ON (`days`.`pkey` = `hymns`.`service`)
    WHERE `names`.`book` = '{$_GET['book']}'
    AND `names`.`number` = '{$_GET['number']}'
    ORDER BY `days`.`caldate` DESC LIMIT {$option_used_history}";
$result = mysql_query($sql) or die(mysql_error().$sql);
$lastusedary = array();
if (mysql_num_rows($result)) {
    while ($row = mysql_fetch_assoc($result)) {
        $title = $row['title'];
        $lastusedary[] = array(
            'date' => $row['date'],
            'location' => $row['location']
        );
    }
    echo json_encode(array($title, $lastusedary));
} else {
    echo json_encode("");
}
?>
