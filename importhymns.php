<? /* Interface for importing hymn names from a shared installation
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
    header('Location: index.php');
    exit(0);
}
if ($_POST['prefix'] && strpos($_POST['prefix'], ' ') === false) {
    $namestable = $dbh->quote("{$_POST['prefix']}names");
    $q = $dbh->query("SHOW TABLES LIKE '{$namestable}'");
    if (! count($q->fetchAll())) {
        setMessage("No names table exists with prefix `".htmlentities($_POST['prefix'])."'");
        header('Location: admin.php');
        exit(0);
    }
    $rowcount = $dbh->exec("INSERT IGNORE INTO `{$dbp}names`
        (book, number, title)
        SELECT n2.book, n2.number, n2.title
            FROM `{$namestable}` AS n2");
    setMessage($rowcount . " hymn names imported.");
    header('Location: admin.php');
} else {
    setMessage("Bad prefix: `".htmlentities({$_POST['prefix']})."'");
    header('Location: admin.php');
}

?>
