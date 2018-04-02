<? /* Return HTML for any existing service on this date
    Copyright (C) 2012 Jesse Jacobsen

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
    Bethany Lutheran Church
    2323 E. 12th St.
    The Dalles, OR 97058
    USA
 */
require("./init.php");
$date = date("Y-m-d", getGET('date'));
$db->beginTransaction();
$q = $db->prepare("SELECT d.name AS dayname, d.rite, d.pkey AS service,
    d.servicenotes, d.block
    FROM `{$db->getPrefix()}days` AS d
    WHERE `caldate` = ?
    ORDER BY dayname");
$q->execute(array($date)) or die(array_pop($q->errorInfo()));
if ($q->rowCount()) {
    echo "<fieldset><legend>Existing Services</legend><ul>";
    $tabindex = 3;
    $options = array();
    $option = 0;
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $thisname = "existing_{$row['service']}";
        $servicenoteFormatted = translate_markup($row['servicenotes']);
        echo "<li><input type=\"checkbox\" tabindex=\"{$tabindex}\" class=\"existingservice\" name=\"{$thisname}\" id=\"{$thisname}\" data-option=\"{$option}\"><label for=\"{$thisname}\"><a href=\"print.php?id={$row['service']}\" target=\"_new\">{$row['dayname']}</a> ({$row['rite']})</label><br/><div class=\"servicenote\">{$servicenoteFormatted}</div>";
        $option += 1;
        $options[] = array(
            "dayname" => $row['dayname'],
            "rite" => $row['rite'],
            "servicenotes" => $row['servicenotes'],
            "block" => $row['block']);
        $qh = $db->prepare("SELECT h.book, h.number, h.occurrence
            FROM `{$db->getPrefix()}hymns` AS h
            WHERE h.service = ?
            ORDER BY h.occurrence, h.sequence");
        $qh->execute(array($row['service'])) or die(array_pop($qh->errorInfo()));
        $hymns = array();
        if ($hrow = $qh->fetch(PDO::FETCH_ASSOC)) {
            $occ = $hrow['occurrence'];
            $hymns[] = "'{$occ}':";
            do {
                if ($occ != $hrow['occurrence']) {
                    $occ = $hrow['occurrence'];
                    $hymns[] = "<br>'{$occ}': {$hrow['book']} {$hrow['number']}";
                } else {
                    $hymns[] = "{$hrow['book']} {$hrow['number']}";
                }
            } while ($hrow = $qh->fetch(PDO::FETCH_ASSOC));
        }
        if ($hymns) echo "".implode(" ", $hymns)."";
        echo "</li>";
        if ($tabindex < 25) $tabindex++;
    }
    echo "</ul></fieldset>";
    echo "<script type=\"text/javascript\">\n"
        ."sessionStorage.setItem(\"ExistingServices\", JSON.stringify(".json_encode($options)
        ."));\n"
        ."</script>";
} else {
    echo "";
}
$db->commit();
?>
