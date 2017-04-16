<?php
/* Download an archive of file uploads
    Copyright (C) 2017 Jesse Jacobsen

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
$thisdir = dirname(__FILE__);
require("{$thisdir}/init.php");

if ($_POST['restore_backup_file']) {
    $backupfile = "./uploads-to-restore.tar";
    if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $backupfile)) {
        $cmdline = "tar -xf $backupfile --keep-newer-files";
        $result = system($cmdline, $return);
        unlink($backupfile);
        if (0 == $return) {
            setMessage("Restore succeeded of upload archive.");
            header("Location: admin.php");
            exit(0);
        }
    } else {
        setMessage("Problem with uploaded file.");
        header("Location: admin.php");
        exit(0);
    }
} elseif (file_exists("./uploads")) { // Download the tar file
    header("Content-type: application/x-tar");
    header("Content-disposition: attachment; filename=service-uploads.tar");
    $rv = 0;
    passthru("tar -c uploads", $rv) ;
    if ($rv != 0) {
        echo "tar returned {$rv}";
    }
} else {
    setMessage("There are no uploaded files to save.");
    header("Location: admin.php");
    exit(0);
}

