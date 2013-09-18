/* Javascript code for main churchyear page.
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
$(document).ready(function() {
    setupEdit();
    setupDelete();
    setupSynonym();
    setupPropers();
    $.get("churchyear.php", { request: "params",
        params: "Michaelmas" },
        function(params) {
            sessionStorage.michaelmasObserved = params['observed_sunday'];
        });
});

function getDateFor(year) {
    // With the current settings of the form, calculate the date
    // in the given year
    var offset = new Number($("#offset").val());
    if ($("#base").val() == "None") {
        if ($("#observed-month").val()) {
            if (Number($("#observed-sunday").val())>0) {
                var odate = new Date(year, $("#observed-month").val()-1, 1);
                odate.setDate(odate.getDate() + (7-odate.getDay()));
                odate.setDate(odate.getDate() +
                    ($("#observed-sunday").val()-1));
                return odate;
            } else {
                var odate = new Date(year, $("#observed-month").val(), 0);
                odate.setDate(odate.getDate() - odate.getDay());
                odate.setDate(odate.getDate() +
                    (Number($("#observed-sunday").val())+1));
                return odate;
            }
        } else {
            return new Date(year, $("#month").val()-1, $("#day").val());
        }
    } else if ("Easter" == $("#base").val()) {
        var base = calcEaster(year);
        base.setDate(base.getDate()+offset);
        return base;
    } else if ("Christmas 1" == $("#base").val()) {
        var base = calcChristmas1(year);
        base.setDate(base.getDate()+offset);
        return base;
    } else if ("Michaelmas 1" == $("#base").val()) {
        var base = calcMichaelmas1(year);
        base.setDate(base.getDate()+offset);
        return base;
    } else if ("Epiphany 1" == $("#base").val()) {
        var base = calcEpiphany1(year);
        base.setDate(base.getDate()+offset);
        return base;
    }
}

function setupPropers() {
    $(".propersname").click(function(evt) {
        evt.preventDefault();
        var loc = $(this).offset();
        var orig = $(this).attr("data-day");
        $.get("churchyear.php", {propers: orig},
            function(rv) {
                rv = $.parseJSON(rv);
                if (! rv[0]) {
                    return;
                }
                $("#dialog").html(rv[1])
                    .dialog({modal: true,
                        title: "Propers for "+orig,
                        width: $(window).width()*0.7,
                        height: "auto",
                        maxHeight: $(window).height()*0.9,
                        position: "center"
                    });
            });
    });
}

function setupCommitSynonymsForm() {
    $("#commitsynonyms").submit(function(evt) {
        evt.preventDefault();
        $.post("churchyear.php",
            {commitsynonyms: $("#commitsynonymsfield").val()},
             function(rv) {
                $("#dialog").dialog("close");
                rv = $.parseJSON(rv);
                setMessage(rv[1]);
             });
    });
}

function setupSynonym() {
    $(".synonym").click(function(evt) {
        evt.preventDefault();
        var loc = $(this).offset();
        var orig = $(this).attr("data-day");
        $.get("churchyear.php", {request: "synonyms",
            name: orig},
            function(rv) {
                rv = $.parseJSON(rv);
                if (rv[0]) {
                    var lines = rv[1].join("\n");
                } else {
                    var lines = "";
                }
                $("#dialog").html('<p>Each line is a synonym. '
                    +'Add new synonyms at the bottom, '
                    +'or rename one by changing it in-place, '
                    +'or delete one by making it a blank line.</p>\n'
                    +'<form id="synonymsform" method="post">'
                    +'<textarea id="synonyms">'+lines+'</textarea><br>'
                    +'<button type="submit" id="submit">Submit</button>'
                    +'<button type="reset" id="reset">Reset</button>'
                    +'</form>');
                $("#synonymsform").submit(function(evt) {
                    evt.preventDefault();
                    $.post("churchyear.php",
                        {synonyms: $("#synonyms").val(),
                         canonical: orig,
                         submitsynonyms: 'true'},
                         function(rv) {
                            $("#dialog").dialog("close");
                            rv = $.parseJSON(rv);
                            if ('confirm' == rv[0]) {
                                $("#dialog").html(rv[1]);
                                $("#dialog").dialog("open");
                            } else if (rv[0]) {
                                setMessage(rv[1]);
                            } else {
                                setMessage("Failed to save synonyms: "+
                                    rv[1]);
                            }
                        });
                });
                $("#dialog").dialog({modal: true,
                    title: "Synonyms for "+orig,
                    width: $(window).width()*0.4,
                    height: "auto",
                    maxHeight: $(window).height()*0.7,
                    position: [30, loc.top]
                });
            });
    });
}

function setupEdit() {
    // Set up edit links
    $(".edit").click(function(evt) {
        evt.preventDefault();
        var dtitle = $(this).attr("data-day");
        $("#dialog")
            .load(encodeURI("churchyear.php?requestform=dayname&dayname="
                +$(this).attr("data-day")), function() {
                    $("#dialog").dialog({modal: true,
                        position: "center",
                        title: dtitle,
                        width: $(window).width()*0.7,
                        height: "auto",
                        maxHeight: $(window).height()*0.9,
                        open: function() {
                            setupEditDialog();
                        }});
                });
    });
}

function setupDelete() {
    // Set up delete links
    $(".delete").click(function(evt) {
        evt.preventDefault();
        var dayname = $(this).attr("data-day");
        if (confirm("Delete the day '"+dayname+"'?")) {
            $.post("churchyear.php", {del: dayname}, function(rv) {
                if (rv[0]) {
                    $("#churchyear-listing").replaceWith(rv[1]);
                    setupEdit();
                    setupDelete();
                    setupSynonym();
                    setupPropers();
                } else {
                    setMessage(rv[1]);
                }
            });
        }
    });
}

function setupCollectDialog(addlink) {
    $("#collect-dropdown").change(function() {
        var choice = $(this).val();
        if (choice != "new") {
            $.get("churchyear.php", { request: "collect", id: choice },
                function(rv) {
                    rv = $.parseJSON(rv);
                    $("#collect-text").val(rv[0]);
                    $("#collect-class").val(rv[1]);
            });
            $("#collect-class").attr('disabled', true);
        } else {
            $("#collect-text").val("");
            $("#collect-class").attr('disabled', false);
        }
    });
    $("#collect-form").submit(function(evt) {
        evt.preventDefault();
        var data = $(this).serialize();
        $.post('churchyear.php', data, function(rv) {
            $("#dialog2").dialog("close");
            rv = $.parseJSON(rv);
            if (rv[0]) {
                $("#dialog").html(rv[2]);
            }
            setMessage(rv[1]);
        });
    });
}

function setupCollectDeleteDialog() {
    $(".detach-collect").click(function(evt) {
        evt.preventDefault();
        $("#dialog2").dialog("close");
        $.get("churchyear.php", {
            detachcollect: $(this).attr("data-cid"),
            lectionary: $(this).attr("data-lectionary"),
            dayname: $(this).attr("data-dayname") }, function(rv) {
                rv = $.parseJSON(rv);
                if (rv[0]) {
                    $("#dialog").html(rv[2]);
                }
                setMessage(rv[1]);
        });
    });
    $("#delete-collect-confirm").submit(function(evt) {
        evt.preventDefault();
        var vals = $(this).serialize();
        $.post("churchyear.php", vals, function(rv) {
            $("#dialog2").dialog("close");
            rv = $.parseJSON(rv);
            if (rv[0]) {
                $("#dialog").html(rv[2]);
            }
            setMessage(rv[1]);
        });
    });
}

function getDecadeDates() {
    // Return a 10-year span of matching dates.
    var decade = new Array();
    var now = new Date();
    var thisyear = now.getFullYear();
    for (y=thisyear-5; y<=thisyear+5; y++) {
        decade.push(getDateFor(y).toDateString());
    }
    return decade.join(", ");
}

function setupEditDialog() {
    var origdates = getDecadeDates();
    $("#calculated-dates").html(origdates);
    $("#base, #offset, #month, #day, #observed_month, #observed_sunday")
        .change(function() {
            var newdates = getDecadeDates();
            $("#calculated-dates").html(newdates);
    });
    $("#dayform").submit(function() {
        if ($('#dayname') == "Michaelmas") {
           sessionStorage.michaelmasObserved = $("#observed-sunday").val();
        }
    });
    $("#dayform").submit(function(evt) {
        evt.preventDefault();
        var vals = $(this).serialize();
        $.post("churchyear.php", vals, function(rv) {
            $("#dialog").dialog("close");
            rv = $.parseJSON(rv);
            if (rv[0]) {
                $("#churchyear-listing").replaceWith(rv[2]);
                setupEdit();
                setupDelete();
                setupSynonym();
                setupPropers();
            }
            setMessage(rv[1]);
        });
    });
}

function setupPropersDialog() {
    $("#tabs").tabs();
    $(".delete-these-propers").click(function() {
        if (confirm("Delete propers?"+
           " (Listed collects will be detached "+
           "from this day & lectionary.)")) {
            var id = $(this).attr("data-id");
            $.get("churchyear.php", { delpropers: id }, function(rv) {
                rv = $.parseJSON(rv);
                if (rv[0]) {
                    $("#dialog").html(rv[2]);
                }
                setMessage(rv[1]);
            });
        }
    });
    $(".add-collect").click(function() {
        $.get("churchyear.php", {
            requestform: 'collect',
            lectionary: $(this).attr("data-lectionary"),
            dayname: $("#propers").val()},
            function(rv) {
                if (! ($("#dialog2").length)) {
                    $("#dialog").after('<div id="dialog2"></div>');
                }
                $("#dialog2").html(rv);
                $("#dialog2").dialog({modal: true,
                    stack: true,
                    position: "center",
                    title: "New Collect",
                    width: $(window).width()*0.65,
                    height: "auto",
                    maxHeight: $(window).height()*0.7,
                    open: function() {
                        setupCollectDialog(this);
                    },
                    close: function() {
                        $("#dialog2").html("");
                    }});
        });
    });
    $(".delete-collect").click(function(){
        $.get("churchyear.php", { requestform: "delete-collect",
            cid: $(this).attr("data-id"),
            dayname: $('#propers').val()}, function(rv) {
                if (! ($("#dialog2").length)) {
                    $("#dialog").after('<div id="dialog2"></div>');
                }
                $("#dialog2").html(rv);
                $("#dialog2").dialog({modal: true,
                    position: "center",
                    title: "Confirm Delete Collect?",
                    width: $(window).width()*0.65,
                    height: "auto",
                    maxHeight: $(window).height()*0.7,
                    open: function() {
                        setupCollectDeleteDialog();
                    },
                    close: function() {
                        $("#dialog2").html("");
                    }});
        });
    });
    $("#new-lectionary").change(function() {
        var currentLects = $("#lectionaries-for-dayname").val().split("\n");
        if (-1 < currentLects.indexOf($(this).val())) {
            setMessage("Propers already exist for today in that lectionary! "+
                "Please edit the existing propers instead, "+
                "or use a different lectionary.");
            $(this).val("");
            $(this).focus();
        }
    });
    $(".propersform").submit(function(evt) {
        evt.preventDefault();
        $.post("churchyear.php", $(this).serialize(),
            function(rv) {
                rv = $.parseJSON(rv);
                setMessage(rv[1]);
        });
    });
    $("#newlessons").submit(function(evt) {
        evt.preventDefault();
        $.post("churchyear.php", $(this).serialize(),
            function(rv) {
                rv = $.parseJSON(rv);
                if (rv[0]) {
                    $("#dialog").html(rv[2]);
                }
                setMessage(rv[1]);
        });
    });
}
