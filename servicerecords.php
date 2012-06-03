<? /* List records for services via html or json
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
$thisdir = dirname(__FILE__);
require("{$thisdir}/init.php");
$cors = checkCorsAuth();
if ($jsonp=checkJsonpReq()) {
    ob_start();
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Planning Records")?>
<body>
    <? if ($jsonp) {
        ob_clean();
    } ?>
    <div id="content-container">
    <? include("records-table.php"); ?>
    </div>
    <?  if ($jsonp) {
            $output = json_encode(addcslashes(ob_get_clean(), "'"));
            echo $jsonp . '(' . $output . ')';
            ob_start();
    } ?>
</body>
</html>
<?  if ($jsonp) {
    ob_end_clean();
} ?>
