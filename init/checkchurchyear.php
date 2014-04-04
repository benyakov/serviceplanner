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

// Churchyear data
if ((! ($dbstate->exists("churchyear-filled") &&
        $dbstate->get("churchyear-filled"))) or
    ($_GET['flag'] == 'fill-churchyear' && $auth))
{
    require('./utility/fillservicetables.php');
    $dbstate->store("churchyear-filled", 1);
    $dbstate->save() or die("Problem saving dbstate file.");
}

// Churchyear db functions
if ((! ($dbstate->exists("has-churchyear-functions") &&
        $dbstate->get("has-churchyear-functions"))) or
    ($_GET['flag'] == 'create-churchyear-functions' && $auth))
{
    $functionsfile = "./utility/churchyearfunctions.sql";
    $functionsfh = fopen($functionsfile, "rb");
    $functionstext = fread($functionsfh, filesize($functionsfile));
    fclose($functionsfh);
    $q = $db->prepare(replaceDBP($functionstext));
    $q->execute() or die("Problem creating functions<br>".
        array_pop($q->errorInfo()));
    $q->closeCursor();
    $dbstate->store('has-churchyear-functions', 1);
    $dbstate->save() or die("Problem saving dbstate file.");
}

// Churchyear table views
if ((! ($dbstate->exists("has-views") && $dbstate->get("has-views"))) or
        ($_GET['flag'] == 'create-views'))
{
    require('./utility/createviews.php');
        $dbstate->store('has-views', 1);
        $dbstate->save() or die("Problem saving dbstate file.");
}
