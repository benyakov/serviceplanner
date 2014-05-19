<?php
/* DB Administration via xmlrpc calls
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

/**
 * A Series of steps taken in sequence via Javascript, to populate
 * the church year tables with default data.
 */
if ("churchyeartables-1" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_historic($db);
    fill_order($db);
    fill_synonyms($db);
    echo json_encode("First group of tables repopulated.");
}
if ("churchyeartables-2" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_propers($db);
    echo json_encode("Second group of tables repopulated.");
}
if ("churchyeartables-3" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_lessons($db);
    echo json_encode("Third group of tables repopulated.");
}
if ("churchyeartables-4" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_collect_texts($db);
    echo json_encode("Fourth group of tables repopulated.");
}
if ("churchyeartables-5" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_collect_indexes($db);
    echo json_encode("Fifth group of tables repopulated.");
}
if ("churchyeartables-6" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_graduals($db);
    echo json_encode("Sixth group of tables repopulated.");
}
?>
