<?
require("./init.php");
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
header("Content-type: application/json");

$q = $dbh->prepare("SELECT `names`.`title` as title,
    `hymns`.`location` as location,
    DATE_FORMAT(`days`.`caldate`, '%e %b %Y') as date
    FROM `{$dbp}names` AS `names`
    LEFT OUTER JOIN `{$dbp}hymns` AS `hymns`
      ON (`names`.`book` = `hymns`.`book`
      AND `names`.`number` = `hymns`.`number`)
    LEFT OUTER JOIN `{$dbp}days` AS `days`
      ON (`days`.`pkey` = `hymns`.`service`)
    WHERE `names`.`book` = :book
    AND `names`.`number` = :number
    ORDER BY `days`.`caldate` DESC LIMIT {$option_used_history}");
$q->bindParam(':book', $_GET['book']);
$q->bindParam(':number', $_GET['number']);
$q->execute() or die(array_pop($q->errorInfo()));
$lastusedary = array();
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $title = $row['title'];
    $lastusedary[] = array(
        'date' => $row['date'],
        'location' => $row['location']
    );
}
if ($title) {
    echo json_encode(array($title, $lastusedary));
    exit(0);
}
$bookname = strtolower($_GET['book']);
$q = $dbh->prepare("SELECT `title` from `{$dbp}xref`
    WHERE `{$_GET['book']}` = :number LIMIT 1");
$q->bindParam(':number', $_GET['number']);
if ($q->execute() && ($row = $q->fetch())) {
    $title = $row[0];
} else {
    $title = "";
}
echo json_encode(array($title, array()));
?>
