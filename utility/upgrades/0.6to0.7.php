<? /* Upgrade from version 0.6 to 0.7
    Copyright (C) 2013 Jesse Jacobsen

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
if (! (isset($newversion) && isset($oldversion))) {
    echo "Error: This upgrade must be run automatically.";
}
if ("0.6." != substr($oldversion, 0, 4).'.') {
    die("Can't upgrade from 0.6.x, since the current db version is {$oldversion}.");
}

$options = new Configfile("./options.ini", true, true, true);
$options->set('hymnbooks', $option_hymnbooks);

$options->set('hymncount', $option_hymncount);

$options->set('used_history', $option_used_history);

$sitetabs['report'] = "Report";
foreach ($sitetabs as $k=>$v) {
    $options->set('sitetabs', $k, $v);
}

$sitetabs_anonymous['report'] = "Report";
foreach ($sitetabs_anonymous as $k=>$v) {
    $options->set('anonymous sitetabs', $k, $v);
}

$options->set('listinglimit', $listinglimit);

$options->set('modifyorder', $modifyorder);

$options->set('phplibrary', $phplibrary);

if (! $authcookie_shelf_life) {
    $authcookie_shelf_life = 60*60*24*7;
}
$options->set('authcookie_shelf_life', $authcookie_shelf_life);

$options->save();

$newversion = "{$version['major']}.{$version['minor']}.{$version['tick']}";
$dbstate->store('dbversion', $newversion);
$dbstate->save() or die("Problem saving dbstate file.");
$rm[] = "Upgraded to {$newversion}";
setMessage(implode("<br />\n", $rm));
?>
