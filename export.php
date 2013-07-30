<? /* Interface for exporting to CSV
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

if ($_GET['lectionary']) {
    $lectname = $_GET['lectionary'];
    $q = $dbh->prepare("SELECT `lectionary`, `dayname`, `lesson1`, `lesson2`,
        `gospel`, `psalm`, `s2lesson`, `s2gospel`, `s3lesson`, `s3gospel`,
        `hymnabc`, `hymn` FROM `{$dbp}churchyear_lessons`
        WHERE `lectionary` = :lect");
    $q->bindValue(":lect", $lectname);
    if (! $q->execute()) {
        echo array_pop($q->errorInfo());
        exit(0);
    }
    foreach ($q->fetch(PDO::FETCH_ASSOC) as $row) {

    }




?>
