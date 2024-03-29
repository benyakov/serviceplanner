<?php /* Re-usable fragment showing the records table
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
/** For debugging
unset($_SESSION[$sprefix]["lowdate"]);
unset($_SESSION[$sprefix]["highdate"]);
unset($_SESSION[$sprefix]["allfuture"]);
 */
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$options = getOptions(true);
//$date_range = setupDateRange($options);
/* This seems to work in a function, but something breaks in the way it
   gets used from a function. Weird. So it's duplicated in records-table.php
*/
    if (getGET('lowdate')) {
        $lowdate = new DateTime(getGET('lowdate'));
        $_SESSION[$sprefix]["lowdate"] = $lowdate;
    } elseif (!$_SESSION[$sprefix]["lowdate"]) {
        $lowdate = new DateTime();
        $lowdate->sub(new DateInterval("P".
            $options->getDefault('1', 'past-range')."W"));
        $_SESSION[$sprefix]["lowdate"] = $lowdate;
    } else $lowdate = $_SESSION[$sprefix]['lowdate'];

    if (getGET('highdate')) {
        $highdate = new DateTime(getGET('highdate'));
        $_SESSION[$sprefix]["highdate"] = $highdate;
    } elseif (!getIndexOr($_SESSION[$sprefix],"highdate")) {
        $highdate = new DateTime();
        $highdate->add(new DateInterval("P".
            $options->getDefault('1', 'future-range')."W"));
        $_SESSION[$sprefix]["highdate"] = $highdate;
    } else $highdate = $_SESSION[$sprefix]['highdate'];

    if ("All" == getGET('submit'))
        $_SESSION[$sprefix]['modifyorder'] = "All";
    elseif ("Future" == getGET('submit'))
        $_SESSION[$sprefix]['modifyorder'] = "Future";
    if (! array_key_exists('modifyorder', $_SESSION[$sprefix]))
        $_SESSION[$sprefix]['modifyorder'] =
            $options->getDefault('All', 'modifyorder');
    else
        $options->set('modifyorder', $_SESSION[$sprefix]['modifyorder']);
    if ("Apply" == getGET('submit')) {
        if (getGET('allfuture')) {
            $allfuture = $_SESSION[$sprefix]["allfuture"] = true;
        } else {
            $allfuture = $_SESSION[$sprefix]["allfuture"] = false;
        }
    } elseif (isset($_SESSION[$sprefix]["allfuture"]))
        $allfuture = $_SESSION[$sprefix]["allfuture"];
    else $allfuture = false;
unset($options);
/* Used to return data from the above as a function see comment above.
$lowdate = $date_range[0]; $highdate = $date_range[1]; $allfuture = $date_range[3];
*/
?>
<h1>Service Planning Records</h1>
<div id="service-filter"></div>
<form action="<?=$protocol?>://<?=$this_script?>" method="GET">
<label for="allfuture">Include all future services:</label>
<input type="checkbox" id="allfuture" name="allfuture"
    <?=($allfuture)?"checked":""?>>
<label for="lowdate">From</label>
<input type="date" id="lowdate" name="lowdate"
    value="<?=$lowdate->format("Y-m-d")?>">
<label for="highdate">To</label>
<input type="date" id="highdate" name="highdate"
    value="<?=$highdate->format("Y-m-d")?>">
<button type="submit" name="submit" value="Apply">Apply</button>
<br>
<?
    $disabled = "";
    if ("Future" == $_SESSION[$sprefix]['modifyorder']) $disabled = "disabled";
?>
<button id="futurebutton" type="submit" name="submit" value="Future" <?=$disabled?>>Chronological</button>
<?
    $disabled = "";
    if ("All" == $_SESSION[$sprefix]['modifyorder']) $disabled = "disabled";
?>
<button id="allbutton" type="submit" name="submit" value="All" <?=$disabled?>>Reverse Chronological</button>
</form>
<?php
if ("Future" == $_SESSION[$sprefix]['modifyorder']) $order = "ASC";
else $order = "DESC";
$q = queryServiceDateRange($lowdate, $highdate, $allfuture, $order);
display_records_table($q);
?>
