<? /* Interface for copying services
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
    echo json_encode(array(False, "Access denied.  Please log in."));
    exit(0);
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$dbh = new DBConnection();
$dbh->beginTransaction();
$q = $dbh->prepare("INSERT INTO `{$db->getPrefix()}days`
    (caldate, name, rite, servicenotes, block)
    SELECT :date, name, rite, servicenotes, block
    FROM `{$db->getPrefix()}days` WHERE pkey = :id");
$q->bindParam(':date', $_GET['chosendate']);
$q->bindParam(':id', $_GET['id']);
$q->execute() or die(json_encode(array(False, array_pop($q->errorInfo()))));
$q = $dbh->prepare("SELECT LAST_INSERT_ID()");
$q->execute() or die(json_encode(array(False, array_pop($q->errorInfo()))));
$row = $q->fetch();
$serviceid = $row[0];
$q = $dbh->prepare("INSERT INTO `{$db->getPrefix()}hymns`
    (service, occurrence, book, number, note, sequence)
    SELECT :service, occurrence, book, number, note, sequence
    FROM `{$db->getPrefix()}hymns`
    WHERE service = :id");
$q->bindParam(":service", $serviceid);
$q->bindParam(":id", $_GET['id']);
$q->execute() or die(json_encode(array(False, array_pop($q->errorInfo()))));
$q = $dbh->prepare("INSERT INTO `{$db->getPrefix()}sermons`
    (bibletext, outline, notes, manuscript, mstype, service)
    SELECT bibletext, outline, notes, manuscript, mstype, :service
    FROM `{$db->getPrefix()}sermons`
    WHERE service = :id");
$q->bindParam(":service", $serviceid);
$q->bindParam(":id", $_GET['id']);
$q->execute() or die(json_encode(array(False, array_pop($q->errorInfo()))));
$dbh->commit();
echo json_encode(array(True, "Service copied."));
exit(0);



