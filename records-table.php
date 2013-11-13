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
if (array_key_exists('listinglimit', $_GET) &&
    is_numeric($_GET['listinglimit'])) {
    $_SESSION[$sprefix]["listinglimit"] = $_GET['listinglimit'];
} elseif (! array_key_exists('listinglimit', $_SESSION[$sprefix])) {
    $_SESSION[$sprefix]['listinglimit'] = $options->get('listinglimit');
}
unset($options);
?>
<h1>Service Planning Records</h1>
<form action="http://<?=$this_script?>" method="GET">
<label for="listinglimit">Listing Limit (0 for None):</label>
<input type="text" id="listinglimit" name="listinglimit"
    value="<?=$_SESSION[$sprefix]["listinglimit"]?>">
<button type="submit" value="Apply">Apply</button>
</form>
<?php
$q = queryAllHymns($_SESSION[$sprefix]['listinglimit']);
display_records_table($q);
?>
