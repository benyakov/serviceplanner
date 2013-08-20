<? /* Interface for dumping database
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

require("./init.php");
if (! $auth) {
    setMessage("Access denied.");
    header("Location: index.php");
    exit(0);
}
$tabledescfiles = array(
    "./utility/createtables.sql",
);
function gettablename ($line) {
    if (preg_match('/TABLE `(\w+)/', $line, $matches)) {
        return $matches[1];
    } else {
        return False;
    }
}
function adddbpfix ($name) {
    $dbh = new DBConnection();
    return "{$dbh->prefix}{$name}";
}
function churchyeartable ($name) {
    return strpos($name, 'churchyear') !== false;
}
$tabledesclines = array();
foreach ($tabledescfiles as $tabledescfile) {
    $tabledesclines =
        array_merge($tabledesclines,
            file($tabledescfile, FILE_IGNORE_NEW_LINES));
}
$tablenamelines = array_filter($tabledesclines, 'gettablename');
$tablenames = array_map('gettablename', $tablenamelines);
$realtablenames = array_map(adddbpfix, $tablenames);
$dbversion = $dbstate->get('dbversion');
$timestamp = date("dMY-Hi");
if ('churchyear' == $_GET['only']) {
    $finaltablenames = array_filter($realtablenames, 'churchyeartable') ;
    $dlfilename = "churchyear-{$dbversion}_{$timestamp}.dump";
} else {
    $finaltablenames = $realtablenames;
    $dlfilename = "services-{$dbversion}_{$timestamp}.dump";
}
$tablenamestring = implode(" ", $finaltablenames);
if (touch(".my.cnf") && chmod(".my.cnf", 0600)) {
    header("Content-type: text/plain");
    header("Content-disposition: attachment; filename={$dlfilename}");
    $fp = fopen("./.my.cnf", "w");
    fwrite($fp, "[client]
    user=\"{$dbconnection['dbuser']}\"
    password=\"{$dbconnection['dbpassword']}\"\n") ;
    fclose($fp);
    $rv = 0;
    passthru("mysqldump --defaults-file=.my.cnf -h {$dbconnection['dbhost']} {$dbconnection['dbname']} {$tablenamestring}", $rv);
    unlink("./.my.cnf");
    if ($rv != 0) {
        echo "mysqldump returned {$rv}";
    }
} else {
    echo "Problem dumping database tables.";
}
?>
