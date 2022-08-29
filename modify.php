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
requireAuth("index.php", 2, "Access denied. Please log in with sufficient privileges.");
/** For debugging
unset($_SESSION[$sprefix]["lowdate"]);
unset($_SESSION[$sprefix]["highdate"]);
unset($_SESSION[$sprefix]["allfuture"]);
 */
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;

if ("Apply" == getGET('submit')) {
    if (getGET('allfuture')) {
        $allfuture = $_SESSION[$sprefix]["allfuture"] = true;
    } else {
        $allfuture = $_SESSION[$sprefix]["allfuture"] = false;
    }
} elseif (isset($_SESSION[$sprefix]["allfuture"]))
    $allfuture = $_SESSION[$sprefix]["allfuture"];
else $allfuture = false;

$options = getOptions(true);
if (getGET('lowdate')) {
    $lowdate = new DateTime(getGET('lowdate'));
    $_SESSION[$sprefix]["lowdate"] = $lowdate;
} elseif (!$_SESSION[$sprefix]["lowdate"]) {
    $lowdate = new DateTime();
    $lowdate->sub(new DateInterval("P".
        $options->getDefault('1', 'past-range')."W"));
    //$lowdate->sub(new DateInterval("P1M"));
    $_SESSION[$sprefix]["lowdate"] = $lowdate;
} else $lowdate = $_SESSION[$sprefix]['lowdate'];

if (getGET('highdate')) {
    $highdate = new DateTime(getGET('highdate'));
    $_SESSION[$sprefix]["highdate"] = $highdate;
} elseif (!getIndexOr($_SESSION[$sprefix],"highdate")) {
    $highdate = new DateTime();
    $highdate->add(new DateInterval("P".
        $options->getDefault('1', 'future-range')."W"));
    $_SESSION[$sprefix]["highdate"] = $highdate;
} else $highdate = $_SESSION[$sprefix]['highdate'];

if ("All" == getGET('submit'))
    $_SESSION[$sprefix]['modifyorder'] = "All";
elseif ("Future" == getGET('submit'))
    $_SESSION[$sprefix]['modifyorder'] = "Future";
if (! array_key_exists('modifyorder', $_SESSION[$sprefix]))
    $_SESSION[$sprefix]['modifyorder'] =
        $options->getDefault('All', 'modifyorder');
else
    $options->set('modifyorder', $_SESSION[$sprefix]['modifyorder']);
unset($options);

// Check for a content-only request.
if (checkContentReq()) {
    if ("Future" == $_SESSION[$sprefix]['modifyorder']) $order = "ASC";
    else $order = "DESC";
    $q = queryServiceDateRange($lowdate, $highdate, $allfuture, $order);
    ob_start();
    modify_records_table($q, "delete.php");
    ?><p id="query_time">Main MySQL query response time: <?=$GLOBALS['query_elapsed_time']?></p><?php
    $refreshable = ob_get_clean();
    echo json_encode($refreshable);
    exit(0);
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
            });
                // .change(fetchHymnTitle); // Causes duplicate requests
            $("#addHymn").click(function(evt) {
                evt.preventDefault();
                addHymn();
            });
            $(".edit-title").change(function() {
                var listingord = $(this).attr("data-hymn");
                $(this).removeClass("data-saved");
                $("#savetitle_"+listingord).show();
            });
            $(".save-title").click(function(evt) {
                evt.preventDefault();
                var listingord = $(this).attr("data-hymn");
                var xhr = $.getJSON("enter.php",
                        { sethymntitle: $("#title_"+listingord).val(),
                        number: $("#number_"+listingord).val(),
                        book: $("#book_"+listingord).val() },
                        function(result) {
                            if (result[0]) {
                                $("#title_"+listingord).addClass("data-saved");
                                $("#savetitle_"+listingord).hide();
                            }
                            setMessage(result[1]);
                        });
            });
            $(".edit-number").each(fetchHymnTitle);
            updateBlocksAvailable($("#date").val());
        }
        function refreshContent() {
            var xhr = $.getJSON("modify.php", {contentonly: "t"},
                function(rv) {
                    $("#refreshable").html(rv);
                    setCSSTweaks();
                    setupStyleAdjusterLocs();
                    setupButtons();
                    setupFlags();
                    $.appear('.service-flags', {"interval":0.1});
                    setupFilterForm();
                    contractAllListings('modify-listing');
                    setupListingExpansion('modify-listing');
                });
        }
        function setupButtons() {
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
                                        $("#dialog").empty();
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
                                title: "Edit a Service",
                                maxHeight: $(window).height()*0.98,
                                width: $(window).width()*0.85,
                                open: function() { setupEditDialog(); },
                                close: function() { $("#dialog").empty(); }});
                });


            });
            $('.copy-service').click(function(evt) {
                evt.preventDefault();
                var id = $(this).data("id");
                $("#dialog")
                    .html('<form id="choosedate">'+
                    '<input type="date" id="chosendate" placeholder="Enter date">'+
                    '<button type="submit">Make Copy</button>'+
                    '</form>\n')
                    .dialog({modal: true,
                        position: {my:"left top", at:"bottom", of:this},
                        width: $(window).width()*0.4,
                        close: function() { $("#dialog").empty(); }});
                if (! Modernizr.inputtypes.date) {
                    $("#chosendate").datepicker({showOn: "button",
                        numberOfMonths: [1,2], stepMonths: 2});
                }
                $("#choosedate").submit(function(evt) {
                    evt.preventDefault();
                    var chosendate = $("#chosendate").val();
                    if (! (Modernizr.inputtypes.date
                        || chosendate.match(/\d{4}-\d{1,2}-\d{1,2}/)))
                    {
                        if (chosendate.match(/\d{1,2}\/\d{1,2}\/\d{4}/)) {
                            var d = new Date(chosendate);
                            d = d.toJSON();
                            chosendate = d.substring(0,10);
                        } else {
                            setMessage("Please use mm/dd/yyyy format.");
                            return;
                        }
                    }
                    $("#dialog").dialog("close");
                    var xhr = $.getJSON("copy.php",
                            { id: id, chosendate: chosendate },
                            function(result) {
                                if (result[0]) {
                                    setMessage(result[1]);
                                    refreshContent();
                                } else {
                                    setMessage("Copy failed.");
                                }
                            });
                });
            });
        }
        function setupMasterButtons() {
            $('#thisweek').click(function(evt) {
                evt.preventDefault();
                scrollTarget("now");
                var dest1 = $("html").scrollTop();
                $("html").scrollTop(dest1-75);
            });
            if (! Modernizr.inputtypes.date) {
                $("#lowdate").datepicker({showOn:"both",
                    numberOfMonths: [1,2],
                    stepMonths: 2});
                $("#highdate").datepicker({showOn:"both",
                    numberOfMonths: [1,2],
                    stepMonths: 2});
            };
            $('#allfuture').change(function() {
                if (this.checked) {
                    $('#highdate').prop('disabled', true)
                        .addClass('disabled-input');
                } else {
                    $('#highdate').prop('disabled', false)
                        .removeClass('disabled-input');
                }
            });
            if ($('#allfuture').is(':checked')) {
                $('#highdate').prop('disabled', true)
                    .addClass('disabled-input');

            }
        }
        $(document).ready(function() {
            refreshContent();
            setupMasterButtons();
        });
    </script>
    <? pageHeader();
    siteTabs(); ?>
    <div id="content-container">
    <div class="quicklinks"><a href="enter.php" title="New Service">New Service</a>
    <a id="thisweek" href="#now">Jump to This Week</a></div>
<h1>Modify Service Planning Records</h1>
<p class="explanation">This listing of hymns allows you to delete whole
services, with all associated hymns in the chosen service occurrence. To create
or edit a sermon plan for that service, use the "Sermon" link.  You can copy a
service to a new date using the "Copy" link. To delete only certain hymns in a
service occurrence, edit the service using the "Edit" link.</p>
<div id="service-filter"></div>
<form action="<?=$protocol?>://<?=$this_script?>" method="GET">
<input type="checkbox" id="allfuture" name="allfuture" value="checked"
 <?=($allfuture)?"checked":""?>>
<label for="allfuture">Include all future services.</label>
<label for="lowdate">From</label>
<input type="date" id="lowdate" name="lowdate"
    value="<?=$lowdate->format("Y-m-d")?>">
<label for="highdate">To</label>
<input type="date" id="highdate" name="highdate"
    value="<?=$highdate->format("Y-m-d")?>">
<button type="submit" name="submit" value="Apply">Apply</button>
<br>
<?
    $disabled = "";
    if ("Future" == $_SESSION[$sprefix]['modifyorder']) $disabled = "disabled";
?>
<button id="futurebutton" type="submit" name="submit" value="Future" <?=$disabled?>>Chronological</button>
<?
    $disabled = "";
    if ("All" == $_SESSION[$sprefix]['modifyorder']) $disabled = "disabled";
?>
<button id="allbutton" type="submit" name="submit" value="All" <?=$disabled?>>Reverse Chronological</button>
</form>
<hr>
<div id="refreshable">
<p id="waiting-for-content">Please wait. Requesting page content...</p>
</div>
</div>
<div id="dialog"></div>
</body>
</html>
