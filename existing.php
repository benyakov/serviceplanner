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
$date = date("Y-m-d", $_GET['date']);
$q = $dbh->query("SELECT name as dayname, rite, pkey as service,
    servicenotes, block
    FROM `{$dbp}days`
    WHERE `caldate` = '{$date}'
    ORDER BY dayname");
$q->execute() or die(array_pop($q->errorInfo()));
if ($q->rowCount()) {
    echo "<fieldset><legend>Existing Services</legend><ul>";
    $tabindex = 3;
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $thisname = "existing_{$row['service']}";
        $servicenoteFormatted = translate_markup($row['servicenotes']);
        echo "<li><input type=\"checkbox\" tabindex=\"{$tabindex}\" class=\"existingservice\" name=\"{$thisname}\" id=\"{$thisname}\" data-block=\"{$row['block']}\"><label for=\"{$thisname}\">{$row['dayname']} ({$row['rite']})</label><br/><div class=\"servicenote\">{$servicenoteFormatted}</div></li>";
        if ($tabindex < 25) $tabindex++;
    }
    echo "</ul></fieldset>";
} else {
    echo "";
}
?>
