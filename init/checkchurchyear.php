<? /* Make sure churchyear data, functions, and views are in place
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

if (! (isset($auth) && $auth)) {
    return; // Back to including script.
}

$dbstate = getDBState(true);
// Churchyear data; holds the fill-step completed (of 6)
if (($dbstate->getDefault(0, "churchyear-filled") < 6) or
    ('fill-churchyear' == getGET('flag')))
{
    fillServiceTables();
}

// Churchyear db functions
if ((! $dbstate->getDefault(false, "has-churchyear-functions")) or
    ('create-churchyear-functions' == getGET('flag')))
{
    $functionsfile = "./utility/churchyearfunctions.sql";
    $functionsfh = fopen($functionsfile, "rb");
    $functionstext = fread($functionsfh, filesize($functionsfile));
    fclose($functionsfh);
    $q = $db->prepare(replaceDBP($functionstext));
    $q->execute() or die("Problem creating functions<br>".
        array_pop($q->errorInfo()));
    $q->closeCursor();
    $dbstate->set('has-churchyear-functions', 1);
    $dbstate->save() or die("Problem saving dbstate file.");
}

// Churchyear table views
if ((! $dbstate->getDefault(false, "has-views")) or
        ('create-views' == getGET('flag')))
{
    require('./utility/createviews.php');
        $dbstate->set('has-views', 1);
        $dbstate->save() or die("Problem saving dbstate file.");
}
unset($dbstate);
