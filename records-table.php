<? /* Re-usable fragment showing the records table
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
$options = getOptions();
unset($options);
if (array_key_exists('allfuture', $_GET)) {
    $allfuture = $_GET['allfuture'];
    $_SESSION[$sprefix]["allfuture"] = $allfuture;
} elseif (!$_SESSION[$sprefix]["allfuture"]) {
    $allfuture = "checked";
    $_SESSION[$sprefix]["allfuture"] = $allfuture;
} else $allfuture = $_SESSION[$sprefix]["allfuture"];

if (array_key_exists('lowdate', $_GET)) {
    $lowdate = new DateTime($_GET['lowdate']);
    $_SESSION[$sprefix]["lowdate"] = $lowdate->format("Y-m-d");
} elseif (!$_SESSION[$sprefix]["lowdate"]) {
    $lowdate = new DateTime();
    $lowdate->sub(new DateInterval("P1M");
    $_SESSION[$sprefix]["lowdate"] = $lowdate->format("Y-m-d");
} else $lowdate = $_SESSION[$sprefix]['lowdate'];

if (array_key_exists('highdate', $_GET)) {
    $highdate = new DateTime($_GET['highdate']);
    $_SESSION[$sprefix]["highdate"] = $highdate->format("Y-m-d");
} elseif (!$_SESSION[$sprefix]["highdate"]) {
    $highdate = new DateTime();
    $_SESSION[$sprefix]["highdate"] = $highdate->format("Y-m-d");
} else $lowdate = $_SESSION[$sprefix]['lowdate'];

?>
<h1>Service Planning Records</h1>
<form action="http://<?=$this_script?>" method="GET">
<label for="allfuture">Include all future services.</label>
<input type="checkbox" id="allfuture" name="allfuture" <?=$allfuture?>>
<label for="lowdate">From</label>
<input type="date" id="lowdate" name="lowdate"
    value="<?=$lowdate?>">
<label for="highdate">To</label>
<input type="date" id="highdate" name="highdate"
    value="<?=$highdate?>">
<button type="submit" value="Apply">Apply</button>
</form>
<?php
$q = queryServiceDateRange($lowdate, $highdate, (bool)$allfuture);
display_records_table($q);
?>
