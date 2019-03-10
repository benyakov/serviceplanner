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
if ("churchyeartables" == $_GET['action'] ) { // Don't use getGET here
    require_once("./init.php");
    $dbstate = getDBState(true);
    $cfilled = $dbstate->getDefault(0, "churchyear-filled");
    if ($cfilled < 6) {
        $selective = true;
        require_once("./utility/fillservicetables.php");
        if (0 == $cfilled) {
            fill_historic($db);
            fill_order($db);
            fill_synonyms($db);
            $dbstate->set("churchyear-filled", 1);
            echo json_encode(array(1, "First group of tables repopulated."));
        } elseif (1 == $cfilled) {
            fill_propers($db);
            $dbstate->set("churchyear-filled", 2);
            echo json_encode(array(2, "Second group of tables repopulated."));
        } elseif (2 == $cfilled) {
            fill_lessons($db);
            $dbstate->set("churchyear-filled", 3);
            echo json_encode(array(3, "Third group of tables repopulated."));
        } elseif (3 == $cfilled) {
            fill_collect_texts($db);
            $dbstate->set("churchyear-filled", 4);
            echo json_encode(array(4, "Fourth group of tables repopulated."));
        } elseif (4 == $cfilled) {
            fill_collect_indexes($db);
            $dbstate->set("churchyear-filled", 5);
            echo json_encode(array(5, "Fifth group of tables repopulated."));
        } elseif (5 == $cfilled) {
            fill_graduals($db);
            $dbstate->set("churchyear-filled", 6);
            echo json_encode(array(6, "Sixth group of tables repopulated."));
        }
    } else echo json_encode(array(6, "All churchyear tables filled."));
    $dbstate->save();
    unset($dbstate);
    exit(0);
}
?>
