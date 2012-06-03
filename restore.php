<? /* Select a dump file to upload, then execute it.
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
    header("location: index.php");
    exit(0);
}
$dumpfile = "restore-{$dbconnection['dbname']}.txt";
if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $dumpfile))
{
    $cmdline = "mysql -u {$dbconnection['dbuser']} -p{$dbconnection['dbpassword']} -h {$dbconnection['dbhost']} {$dbconnection['dbname']} ".
        "-e 'source ${dumpfile}';";
    $result = system($cmdline, $return);
    unlink($dumpfile);
    if (0 == $return)
    {
        setMessage("Restore succeeded.");
        header("Location: records.php");
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en"><head><title>Problem Executing Restore</title></head>
        <body><h1>Problem Executing Restore</h1>
        <p>Command: <pre><?=$cmdline?></p>
        <p>Exit code: <?=$return?></p>
        <p>Output: <pre><?=$result?></pre></p>
        </body></html>
        <?
    }
} else {
    setMessage("Problem uploading backup file.");
    header("Location: records.php");
}
?>
