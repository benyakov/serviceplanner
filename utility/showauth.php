<?php /* Display the authdata for the current user.
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
chdir("..");
require("./init.php");
function displayarray($array) {
    if (! is_array($array)) {
        echo "Not an array:";
        print_r($array);
    }
    echo "<dl>\n";
    foreach ($array as $k=>$v) {
        echo "<dt>{$k}</dt><dd>\n";
        if (is_array($v)) {
            displayarray($v);
        } else {
            echo "<dd>{$v}</dd>";
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
<title>Current User's Session Data</title>
</head>
<body>
<h1>Current User's Session data</h1>
<?displayarray($_SESSION[$sprefix]);?>
</body>
</html>
