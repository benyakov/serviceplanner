<? /* Determine and perform upgrades needed
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


function needsUpgrade() {
    global $version;
    $dbstate = getDBState(true);
    if (! $dbstate->exists('dbversion')) {
        if (file_exists("./dbversion.txt")) {
            $dp = fopen("./dbversion.txt", "rb");
            $oldversion = explode('.', fread($dp, 64));
            return "{$oldversion[0]}.{$oldversion[1]}";
        } else {
            // Set to current version and cross fingers...
            $dbstate->set('dbversion',
                "{$version['major']}.{$version['minor']}.{$version['tick']}");
            $dbstate->save();
            return false;
        }
    } else {
        $dbcurrent = explode('.', trim($dbstate->get('dbversion')));
        if (! ($version['major'] == $dbcurrent[0]
            && $version['minor'] == $dbcurrent[1]))
            return "{$dbcurrent[0]}.{$dbcurrent[1]}";
        else
            return false;
    }
    unset($dbstate);
}

while ($oldversion = needsUpgrade()) {
    if (! file_exists("./dbconnection.ini")) {
        require("./utility/setup-dbconfig.php");
    }
    $finalversion = "{$version['major']}.{$version['minor']}";
    $newversion = "{$version['major']}.{$version['minor']}";
    $upgradefile = "./utility/upgrades/{$oldversion}to{$newversion}.php";
    if (file_exists($upgradefile)) {
        require($upgradefile);
    } else {
        $available = glob("./utility/upgrades/{$oldversion}*.php");
        if ($available)
            require($available[0]);
    }
}
?>
