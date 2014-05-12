<? /* Set up the options to reasonable defaults
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

// List of possible hymnbooks to draw from
$option_hymnbooks = array(
    "ELH",
    "TLH",
    "CW",
    "LSB",
    "LW"
);

// How many hymns can be entered at once
$option_hymncount = 8;

// How many old hymn use dates should be listed when verifying
// new hymn titles to enter into a service?
$option_used_history = 5;

// Site tabs are for switching pages/functions in the application
// This determines which tabs are visible on pages that use them.
// You probably don't need to change this, but if you wish,
// you can comment out one or more tabs by prepending "//" to the line,
// or you can modify the values in the array (not the keys) to change
// the text displayed on the tabs.
$sitetabs = array(
    "index"=>"Upcoming Hymns"
    ,"report"=>"Report"
    ,"modify"=>"Modify Services"
    ,"block"=>"Block Plans"
    ,"sermons"=>"Sermon Plans"
    ,"hymnindex"=>"Cross Ref"
    ,"churchyear"=>"Church Year"
    ,"admin"=>"Housekeeping"
);

// This is the same as above, and will be substituted for it when the web
// site user has not logged in.  This does not prevent the user from trying
// to access the other tabs manually, but it does allow for a less confusing
// interface.
$sitetabs_anonymous = array(
    "index"=>"Upcoming Hymns"
    ,"records"=>"Service Records"
    ,"report"=>"Report"
    ,"hymnindex"=>"Cross Ref"
);

// Default order for the presentation of hymns and services on the Modify tab.
// May be "Future" or "All".
$modifyorder = "All";

// Default shelf-life of auth cookies, which allow the Service Planner to
// remember the logins of users past the expiration of the current session.
// This can be customized on the Admin page.
$authcookie_shelf_life = 60*60*24*7;  // 1 week (in seconds)

if (file_exists("./options.php")) {
    require("./options.php");
    unlink("./options.php");
}

$options = new Configfile("./options.ini", true, true, true);
$options->set('hymnbooks', $option_hymnbooks);
$options->set('hymncount', $option_hymncount);
$options->set('used_history', $option_used_history);
foreach ($sitetabs as $k=>$v)
    $options->set('sitetabs', $k, $v);
foreach ($sitetabs_anonymous as $k=>$v)
    $options->set('anonymous sitetabs', $k, $v);
$options->set('modifyorder', $modifyorder);
if (! $authcookie_shelf_life) {
    $authcookie_shelf_life = 60*60*24*7;
}
$options->set('authcookie_shelf_life', $authcookie_shelf_life);
$options->save();
unset($options);

?>
