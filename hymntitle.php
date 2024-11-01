<?php /* ajax showing hymn title
    Copyright (C) 2023 Jesse Jacobsen

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    Send feedback or donations to: Jesse Jacobsen <jmatjac@gmail.com>

    Mailed donation may be sent to:
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */
require("./init.php");
$options = getOptions();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
header("Content-type: application/json");

$q = $db->prepare("SELECT `names`.`title` as title,
    `hymns`.`occurrence` as occurrence,
    DATE_FORMAT(`days`.`caldate`, '%e %b %Y') as date
    FROM `{$db->getPrefix()}names` AS `names`
    LEFT OUTER JOIN `{$db->getPrefix()}hymns` AS `hymns`
      ON (`names`.`book` = `hymns`.`book`
      AND `names`.`number` = `hymns`.`number`)
    LEFT OUTER JOIN `{$db->getPrefix()}days` AS `days`
      ON (`days`.`pkey` = `hymns`.`service`)
    WHERE `names`.`book` = :book
    AND `names`.`number` = :number
    ORDER BY `days`.`caldate` DESC LIMIT {$options->get('used_history')}");
$q->bindValue(':book', getGET('book'));
$q->bindValue(':number', getGET('number'));
$q->execute() or die(array_pop($q->errorInfo()));
$lastusedary = array();
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $title = $row['title'];
    $lastusedary[] = array(
        'date' => $row['date'],
        'occurrence' => $row['occurrence']
    );
}
if (0 == getGET('number')) {
    $lastusedary = array();
}
if (isset($title) || getGET('xref')=="off") {
    echo json_encode(array($title, $lastusedary, false));
    exit(0);
}
$bookname = strtolower(getGET('book'));
$q = $db->prepare("SELECT `title` from `{$db->getPrefix()}xref`
    WHERE `{$bookname}` = :number LIMIT 1");
$q->bindParam(':number', getGET('number'));
if ($q->execute() && ($row = $q->fetch())) {
    $title = $row[0];
} else {
    $title = "";
}
echo json_encode(array($title, $lastusedary, true));
?>
