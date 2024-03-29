<?php /* Initialization used by all entry points
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
if (empty($_SERVER['HTTPS']) || 'off' == $_SERVER['HTTPS']) {
    $protocol = "http";
} else {
    $protocol = "https";
}
$thisdir = __DIR__;
$serverdir = dirname($_SERVER['PHP_SELF']);
chdir($thisdir);
require("./version.php");
require("./setup-session.php");
require("./functions.php");
require("./utility/configfile.php");
require("./utility/dbconnection.php");
$script_basename = basename($_SERVER['PHP_SELF'], '.php');

if ('dbinit' == getGET('flag')) {
    require("./init/dbinit.php");
}

if ('inituser' == getGET('flag')) {
    $db = new DBConnection();
    require("./init/inituser.php");
}

// Make sure database connection is configured.
if (! file_exists("./dbconnection.ini"))
    require("./init/dbinit.php");
$db = new DBConnection();

// Make sure database is set up
require("./init/dbsetup.php");

// Perform any necessary upgrades
require("./init/upgrades.php");

// Load runtime options
if (! file_exists('./options.ini'))
    require('./init/setupoptions.php');

// Make sure a user has been configured
require("./init/checkuser.php");

if (! array_key_exists('username', $_POST)) $auth = auth();

// Save settings in request
if ('savesettings' == getGET('flag')) {
    require("./init/savesettings.php");
}

// Check churchyear data and functions
require("./init/checkchurchyear.php");

// Check for flag cache adjustments
require("./init/flags.php");

?>
