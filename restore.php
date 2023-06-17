<?php /* Select a dump file to upload, then execute it.
    Copyright (C) 2023 Jesse Jacobsen

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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */
require("./init.php");
requireAuth("index.php", 3);
$dumpfile = "./restore-{$db->getName()}.txt";
$fnmatches = array();
if (! preg_match('/^(services|churchyear)-(\d+\.\d+\.\d+)_(\d+[[:alpha:]]{3}\d{4}-\d{4}).dump$/',
    $_FILES['backup_file']['name'], $fnmatches))
    {
    setMessage("Please choose a backup dump file with its original name. (Uploaded {$_FILES['backup_file']['name']})");
    header("location: admin.php");
    exit(0);
} else {
    $dbstate = getDBState(false);
    $dbversion = $dbstate->get('dbversion');
    $timestamp = $fnmatches[3];
    $version = $fnmatches[2];
    $dbrequired = implode('.', array_splice(explode('.', $dbversion), 0, -1));
    $dboffered=implode('.',array_splice(explode('.', $version), 0, -1));
    if ($dbrequired != $dboffered) {
        setMessage("The chosen dumpfile was from a different version of the ".
            "Service Planner.  This installation is version {$dbversion}, ".
            "but the dumpfile is from version {$version}. ".
            "To restore this dumpfile, use an installation of any ".
            "Services version beginning with '{$dbrequired}'.");
        header("location: admin.php");
        exit(0);
    }
}
if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $dumpfile)) {
    // Insert $dbp into dumpfile.
    $dumplines = file($dumpfile, FILE_IGNORE_NEW_LINES);
    $newdumplines = array();
    $dbp = $db->getPrefix();
    foreach ($dumplines as $line) {
        array_push($newdumplines,
            preg_replace(
                array(
                    '/^(DROP TABLE [^`]*`)([^`]+)/',
                    '/^(LOCK TABLES `)([^`]+)/',
                    '/^(INSERT INTO `)([^`]+)/',
                    '/^(CREATE TABLE `)([^`]+)/',
                    '/(ALTER TABLE `)([^`]+)/',
                    '/(REFERENCES `)([^`]+)/',
                    '/(CONSTRAINT `)([^`]+)/'
                ), "\\1${dbp}\\2", $line));
    }
    $dumpfh = fopen($dumpfile, 'wb');
    fwrite($dumpfh, implode("\n", $newdumplines));
    fclose($dumpfh);
    if (touch("./.my.cnf") && chmod(".my.cnf", 0600)) {
        $fp = fopen(".my.cnf", "w");
        fwrite($fp, "[client]
        user=\"{$db->getUser()}\"
        password=\"{$db->getPassword()}\"\n") ;
        fclose($fp);
        $cmdline = "mysql --defaults-file=.my.cnf -h {$db->getHost()} {$db->getName()} ".
            "-e 'source ${dumpfile}';";
        $result = system($cmdline, $return);
        unlink($dumpfile);
        unlink("./.my.cnf");
        if (0 == $return) {
            setMessage("Restore succeeded of data dumped at {$timestamp}.");
            header("Location: index.php");
            exit(0);
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en"><head><title>Problem Executing Restore</title></head>
    <body><h1>Problem Executing Restore</h1>
    <p>Command: <pre><?=$cmdline?></p>
    <p>Exit code: <?=$return?></p>
    <p>Output: <pre><?=$result?></pre></p>
    </body></html>
    <?
} else {
    setMessage("Problem uploading backup file.");
    header("Location: records.php");
}
?>
