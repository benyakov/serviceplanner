<?  /* Save settings provided in $_GET or $_POST
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

$config = getConfig(true);
// Check for set values and store them.
if (isset($_POST["biblegwversion"]) && $auth) {
    $config->set("biblegwversion", $_POST['biblegwversion']);
    setMessage("Bible Gateway version has been set.");
}

if (isset($_POST["cookie-age"]) && $auth) {
    $config->set('authcookie_max_age', intval($_POST['cookie-age']*60*60*24));
    setMessage("Set max authorization cookie age.");
}

if (isset($_POST['sitetabs-config']) && $auth) {
    if (! $_POST['sitetabs-config']) {
        $config->del("sitetabs");
        setMessage("Deleted Sitetabs");
    } else {
        $newsitetabs = array();
        foreach (explode("\n", $_POST['sitetabs-config']) as $line) {
            if (! $line) continue;
            $eline = explode(":", $line);
            if (count($eline) < 2) {
                setMessage("Malformed Sitetabs Line: ".htmlspecialchars($line));
            } else {
                $newsitetabs[$eline[0]] = trim($eline[1]);
            }
        }
        $config->set('sitetabs', $newsitetabs);
        setMessage("Set Sitetabs");
    }
}

if (isset($_POST['sitetabs-config-anon']) && $auth) {
    if (! $_POST['sitetabs-config-anon']) {
        $config->del("anonymous sitetabs");
        setMessage("Deleted Anonymous Sitetabs");
    } else {
        $newsitetabs = array();
        foreach (explode("\n", $_POST['sitetabs-config-anon']) as $line) {
            if (! $line) continue;
            $eline = explode(":", $line);
            if (count($eline) < 2) {
                setMessage("Malformed Anonymous Sitetabs Line: ".htmlspecialchars($line));
            } else {
                $newsitetabs[$eline[0]] = trim($eline[1]);
            }
        }
        $config->set('anonymous sitetabs', $newsitetabs);
        setMessage("Set Anonymous Sitetabs");
    }
}

$config->save();
unset($config);
?>
