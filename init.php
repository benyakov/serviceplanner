<? /* Initialization used by all entry points
    Copyright (C) 2014 Jesse Jacobsen

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
$serverdir = dirname($_SERVER['PHP_SELF']);
chdir($thisdir);
require("./version.php");
require("./setup-session.php");
require("./functions.php");
require("./utility/configfile.php");
require("./utility/dbconnection.php");
$script_basename = basename($_SERVER['PHP_SELF'], '.php');
$dbstate = getDBState();

if ($_GET['flag'] == 'inituser') {
    $db = new DBConnection();
    require("./init/inituser.php");
}

// Make sure database is set up
require("./init/dbsetup.php");

// Perform any necessary upgrades
require("./init/upgrades.php");

// Make sure a user has been configured
require("./init/checkuser.php");

$db = new DBConnection();
if (! array_key_exists('username', $_POST)) $auth = auth();

// Save settings in request
if ($_GET['flag'] == 'savesettings') {
    require("./init/savesettings.php");
}

// Check churchyear data and functions
require("./init/checkchurchyear.php");

// release file lock.
unset($dbstate);

// Load runtime options
if (! file_exists('./options.ini'))
    require('./utility/setup-options.php');
?>
