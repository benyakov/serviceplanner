<? /* Interface for modifying services from the listing
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
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
    exit(0);
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if (! array_key_exists('modifyorder', $_SESSION[$sprefix])) {
    $_SESSION[$sprefix]['modifyorder'] = $modifyorder;
}
if (array_key_exists('listinglimit', $_GET) &&
    is_numeric($_GET['listinglimit'])) {
    $_SESSION[$sprefix]["listinglimit"] = $_GET['listinglimit'];
    if ("Apply" != $_GET['submit']) {
        $_SESSION[$sprefix]["modifyorder"] = $_GET['submit'];
    }
} elseif (! array_key_exists('listinglimit', $_SESSION[$sprefix])) {
    $_SESSION[$sprefix]['listinglimit'] = $listinglimit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Modify Service Planning Records")?>
<body>
    <script type="text/javascript">
        function setupEditDialog() {
            showJsOnly();
            if (! Modernizr.inputtypes.date) {
                $("#date").datepicker({showOn:"both"});
            }
            $("#date").change(function() {
                updateBlocksAvailable($(this).val());
            });
            $(".edit-number").keyup(function(evt) {
                if (evt.which != 9 &&
                    evt.which != 17) {
                    $(this).doTimeout('fetch-hymn-title', 250, fetchHymnTitle);
                }
            })
                .change(fetchHymnTitle);
            $("#addHymn").click(function(evt) {
                evt.preventDefault();
                addHymn();
            });
            $(".edit-number").each(fetchHymnTitle);
            updateBlocksAvailable($("#date").val());
        }
        $(document).ready(function() {
            $("button.deletesubmit").click(function(evt) {
                evt.preventDefault();
                var submitData = $("#delete-service").serialize();
                if (! submitData) {
                    setMessage("No services are selected for deletion.");
                } else {
                    $.post("delete.php?ajaxconfirm=1", submitData,
                        function(data) {
                            $("#dialog").html(data)
                                .dialog({modal: true,
                                    width: $(window).width()*0.7,
                                    maxHeight: $(window).height()*0.7,
                                    close: function() {
                                        setMessage("Deletion cancelled.");
                                    }});
                    });
                }
            });
            $('.edit-service').click(function(evt) {
                evt.preventDefault();
                $("#dialog")
                    .load(encodeURI("edit.php?id="+$(this).attr('data-id')),
                    function() {
                    $("#dialog").dialog({modal: true,
                                position: "center",
                                title: "Edit a Service",
                                width: $(window).width()*0.95,
                                maxHeight: $(window).height()*0.7,
                                open: function() { setupEditDialog(); },
                                close: function() { $("#dialog").empty(); }});
                });


            });
            $('#thisweek').click(function(evt) {
                evt.preventDefault();
                scrollTarget("now");
            });
        });
    </script>
    <? pageHeader();
    siteTabs($auth); ?>
    <div id="content-container">
    <div class="quicklinks"><a href="enter.php" title="New Service">New Service</a>
    <a id="thisweek" href="#now">Jump to This Week</a></div>
<h1>Modify Service Planning Records</h1>
<p class="explanation">This listing of hymns allows you to delete whole
services, with all associated hymns at that location. To delete only certain
hymns, edit the service using the "Edit" link.  To create or edit a sermon plan
for that service, use the "Sermon" link.</p>
<form action="http://<?=$this_script?>" method="GET">
<label for="listinglimit">Listing Limit (0 for None):</label>
<input type="text" id="listinglimit" name="listinglimit"
    value="<?=$_SESSION[$sprefix]["listinglimit"]?>">
<button type="submit" name="submit" value="Apply">Apply</button>
<?
    $disabled = "";
    if ("Future" == $_SESSION[$sprefix]['modifyorder']) $disabled = "disabled";
?>
<button id="futurebutton" type="submit" name="submit" value="Future" <?=$disabled?>>Show Future Only (Chron.)</button>
<?
    $disabled = "";
    if ("All" == $_SESSION[$sprefix]['modifyorder']) $disabled = "disabled";
?>
<button id="allbutton" type="submit" name="submit" value="All" <?=$disabled?>>Show All (Rev. Chron.)</button>
</form>
<?
if ("Future" == $_SESSION[$sprefix]['modifyorder']) $future = true;
else $future = false;
$q = queryAllHymns($dbh, $dbp, $_SESSION[$sprefix]['listinglimit'], $future);
modify_records_table($q, "delete.php");
?>
</div>
<div id="dialog"></div>
</body>
</html>
