<? /* Exports a service in csv format
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
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$q = queryService($dbh, $_GET['id']);
$row = $q->fetch(PDO::FETCH_ASSOC);
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename={$row['dayname']}.csv");
$output = fopen('php://output', 'w');
fputcsv($output, array("Date", "Day", "Order", "Service Notes", "Introit", "Propers Note", "Color", "Theme", "Block", "Block Notes", "Lesson 1", "Lesson 2", "Gospel", "Psalm", "Collect", "Hymnbook", "Hymnnumber", "Hymnnote", "Hymnlocation", "Hymntitle"));
while ($row) {
    if ('' == $row['number']) {
        $row = $q->fetch(PDO::FETCH_ASSOC);
        continue;
    }
    fputcsv($output, array(
        $row['date'], $row['dayname'], $row['rite'], $row['servicenotes'],
        $row['introit'], $row['propersnote'], $row['color'], $row['theme'],
        $row['blabel'], $row['bnotes'], $row['blesson1'], $row['blesson2'],
        $row['bgospel'], $row['bpsalm'], $row['bcollect'],
        $row['book'], $row['number'], $row['note'], $row['location'],
        $row['title']));
    $row = $q->fetch(PDO::FETCH_ASSOC);
}
fclose($output);

