<? /* Make sure churchyear data, functions, and views are in place
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

if (3 != authLevel()) {
    return; // Nothing to see here, execution resumes in prior script.
}
/*
if ('reset-flag-cache' == getGET('flag')) {
    require_once("./flags.php");
    $lw = new LogWriter('./cache/log');
    if (! file_exists("./cache/flags/")) {
        mkdir("./cache/flags/", 0750, true);
    }
    $flag_services = scandir("./cache/flags/");
    foreach ($flag_services as $service_folder) {
        $flag_items = scandir("./cache/flags/{$service_folder}/");
        foreach ($flag_items as $content_file) {
            unlink("./cache/flags/{$service_folder}/{$content_file}");
        }
        rmdir("./cache/flags/{$service_folder}");
    }
    unlink("./cache/flags/log");
    setMessage("Flag cache has been reset (deleted).");
}

