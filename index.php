<?php /* Main entry point
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
$thisdir = dirname(__FILE__);
require("{$thisdir}/init.php");
$cors = checkCorsAuth();
if (is_link($_SERVER['SCRIPT_FILENAME']) || $cors ) {
    $displayonly = true;
} else {
    $displayonly = false;
}
// Check for a content-only request.
if ($contentonly = checkContentReq()) {
    // Get the main content
    ob_start();
    ?>
    <h1>Upcoming Hymns</h1> <div id="service-filter"></div>
    <?php
    $q = queryFutureHymns();
    display_records_table($q);
    ?><p id="query_time">Main MySQL query response time: <?=$GLOBALS['query_elapsed_time']?></p><?php
    $rawcontent = ob_get_clean();
    echo json_encode($rawcontent);
    exit(0);
}
// Check for a cross-site jsonp request.
if ($jsonp = checkJsonpReq()) {
    $displayonly = true;
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Upcoming Hymns")?>
<body>
<script type="text/javascript">
    function refreshContent() {
        var xhr = $.getJSON("index.php", {contentonly: "t"},
            function(rv) {
                $("#content-container").html(rv);
                setCSSTweaks();
                setupStyleAdjusterLocs();
                setupFlags();
                $.appear('.service-flags', {interval:0.1, force_process: "t"});
                setupFilterForm(true);
                contractAllListings('records-listing');
                setupListingExpansion();
            });
    }
    $(document).ready(function() {
        refreshContent();
        svch.addHandler('login', refreshContent);
    });
</script>
<?
pageHeader($displayonly);
siteTabs(False, $displayonly);
if ($jsonp) {
    ob_clean();
} ?>
<div id="content-container">
<?
if ($jsonp) {
    echo $rawcontent;
} else {
    ?><p id="waiting-for-content">Please wait. Requesting page content...</p><?php
}

?>
</div>
<?  if ($jsonp) {
        $output = json_encode(addcslashes(ob_get_clean(), "'"));
        echo $jsonp . '(' . $output . ')';
        ob_start();
} ?>
<div id="dialog"></div>
</body>
</html>
<?  if ($jsonp) {
    ob_end_clean();
} ?>
