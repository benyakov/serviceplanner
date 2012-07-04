<? /* Javascript library
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
require('functions.php'); ?>

function addHymn() {
    if ($("#hymnentries").is("table")) {
        addHymnToTable();
    } else {
        addHymnToList();
    }
}

function incrElement(elem) {
    elem.val(Number(elem.val()) + 1);
}

function addHymnToTable() {
    $("#hymnentries > tbody > tr").eq(-1).clone()
        .appendTo("#hymnentries > tbody");
    var tabindexStart = Number($("#hymnentries > tbody > tr").eq(-1)
        .find('[id^=delete]').attr("tabindex"));
    $("#hymnentries > tbody > tr").eq(-1).find('[id^=delete]')
        .attr("id", "delete_new-"+tabindexStart)
        .attr("name", "delete_new-"+tabindexStart)
        .attr("tabindex", tabindexStart+7)
        .attr("disabled", true);
    incrElement($("#hymnentries > tbody > tr").eq(-1).find('[id^=sequence]')
        .attr("id", "sequence_new-"+tabindexStart)
        .attr("name", "sequence_new-"+tabindexStart)
        .attr("tabindex", tabindexStart+8));
    $("#hymnentries > tbody > tr").eq(-1).find('[id^=book]')
        .attr("id", "book_new-"+tabindexStart)
        .attr("name", "book_new-"+tabindexStart)
        .attr("tabindex", tabindexStart+9)
        .val("");
    $("#hymnentries > tbody > tr").eq(-1).find('[id^=number]')
        .attr("id", "number_new-"+tabindexStart)
        .attr("name", "number_new-"+tabindexStart)
        .attr("tabindex", tabindexStart+10)
        .val("")
        .keyup(function() {
            $(this).doTimeout('fetch-hymn-title', 250, fetchHymnTitle)
        })
        .change(fetchHymnTitle);
    $("#hymnentries > tbody > tr").eq(-1).find('[id^=note]')
        .attr("id", "note_new-"+tabindexStart)
        .attr("name", "note_new-"+tabindexStart)
        .attr("tabindex", tabindexStart+11)
        .val("");
    $("#hymnentries > tbody > tr").eq(-1).find('[id^=location]')
        .attr("id", "location_new-"+tabindexStart)
        .attr("name", "location_new-"+tabindexStart)
        .attr("tabindex", tabindexStart+12);
    $("#hymnentries > tbody > tr").eq(-1).find('[id^=title]')
        .attr("id", "title_new-"+tabindexStart)
        .attr("name", "title_new-"+tabindexStart)
        .attr("tabindex", tabindexStart+13)
        .val("");
    $("#hymnentries > tbody > tr").eq(-1).show();
}

function addHymnToList() {
    $("#hymnentries > li").eq(-1).clone().appendTo("#hymnentries");
    var oldBookId = $("#hymnentries > li").eq(-1).children().attr("id");
    var hymnIndex = Number(oldBookId.split("_")[1]) + 1;
    var tabindexStart = Number($("#hymnentries >li").eq(-1).children().filter('[id^="book"]').attr("tabindex"));
    $("#hymnentries > li").eq(-1).children().filter('[id^="book"]')
        .attr("id", "book_"+hymnIndex)
        .attr("name", "book_"+hymnIndex)
        .attr("tabindex", tabindexStart+4);
    $("#hymnentries > li").eq(-1).children().filter('[id^="number"]')
        .attr("id", "number_"+hymnIndex)
        .attr("name", "number_"+hymnIndex)
        .attr("tabindex", tabindexStart+5)
        .keyup(function() {
            $(this).doTimeout('fetch-hymn-title', 250, fetchHymnTitle)
        })
        .change(fetchHymnTitle);
    $("#hymnentries > li").eq(-1).children().filter('[id^="note"]')
        .attr("id", "note_"+hymnIndex)
        .attr("name", "note_"+hymnIndex)
        .attr("tabindex", tabindexStart+6);
    $("#hymnentries > li").eq(-1).children().filter('[id^="title"]')
        .attr("id", "title_"+hymnIndex)
        .attr("name", "title_"+hymnIndex)
        .attr("tabindex", tabindexStart+7);
    $("#hymnentries > li").eq(-1).children().filter('[id^="past"]')
        .text("")
        .hide();
    $("#hymnentries > li").eq(-1).toggleClass('even odd');
}

function showJsOnly() {
    $(".jsonly").removeClass("jsonly");
}

function updateExisting(dateitem) {
    var dateEntered = Date.parse(dateitem)/1000;
    if (! dateEntered) return;
    var xhr = $.get("existing.php", { date: dateEntered },
            function(newBloc) {
                $("#existing-services").html(newBloc).show();
                $('.existingservice').change(function() {
                    if ($(this).prop('checked')) {
                        $('.existingservice').not(this)
                            .prop('checked', false)
                            .prop('disabled', true);
                        $("#liturgicalname").prop('disabled', true);
                        $("#rite").prop('disabled', true);
                        $("#servicenotes").prop('disabled', true);
                        $("#block").prop('disabled', true);
                    } else {
                        $('.existingservice').prop('disabled', false);
                        $("#liturgicalname").prop('disabled', false);
                        $("#rite").prop('disabled', false);
                        $("#servicenotes").prop('disabled', false);
                        $("#block").prop('disabled', false);
                    }
                })
            })
}

function fetchHymnTitle() {
    var hymnNumber = $(this).val();
    var id = $(this).attr("id").split("_");
    var entryNumber = id[1];
    var use_xref = $("#xref-names:checked").val() || "off";
    if (! hymnNumber) {
        $("#title_"+entryNumber).val("").hide();
        $("#past_"+entryNumber).text("").hide();
        return;
    }
    var hymnBook = $("#book_"+entryNumber).val();
    var jqxhr = $.getJSON("hymntitle.php",
            { number: hymnNumber, book: hymnBook, xref: use_xref },
            function(result) {
                var hymnTitle = result[0];
                var pastServices = result[1];
                if (hymnTitle) {
                    $("#title_"+entryNumber).val(hymnTitle).show();
                } else {
                    $("#title_"+entryNumber).val("")
                        .attr("placeholder", "Please enter a title.")
                        .show();
                }
                var past = new Array;
                var locstr;
                for (service in pastServices) {
                    locstr = pastServices[service]['location']
                        ?" (" + pastServices[service]['location'] + ")"
                        :"";
                    if (pastServices[service]['date']) {
                        past.push(pastServices[service]['date'] + locstr);
                    }
                }
                if (past) {
                    $("#past_"+entryNumber).text(past.join(", ")).show();
                } else {
                    $("#past_"+entryNumber).text("").hide();
                }
            });
}

function submitLogin() {
    var jqxhr = $.post("login.php", {
        ajax: "ajax",
        username: $("#username").val(),
        password: $("#password").val() },
        function(result) {
            setupLogin(result);
            if (result['userlevel']) {
                setMessage("Logged in.");
            } else {
                setMessage("Login failed.");
            }
        }
    );
}

function setupLogin(authactions) {
    // Set up the login form or logout link
    $("#useractions").html(authactions['actions']);
    $("#login").html(authactions['loginform']);
    $("#loginform").submit(function(evt) {
        evt.preventDefault();
        submitLogin();
    });
    $("#login > a").keydown(function(evt) {
        // Don't logout if the character is a tab or shift-tab
        if (evt.which != 9 &&
            evt.which != 17) {
            $(self).attr("href", "javascript: void(0);");
            logout(null);
        }
    }).click(function(evt) {
        $(self).attr("href", "javascript: void(0);");
        logout(evt);
    });
    $("#sitetabs").html(authactions['sitetabs']);
}

function logout(evt) {
    if (evt) { evt.preventDefault(); }
    var jqxhr = $.getJSON("login.php", {
        action: 'logout',
        ajax: true },
        function(result) {
            setupLogin(result);
            setMessage("Logged out.");
        });
}

function setMessage(msg) {
    var timestamp = (new Date).toTimeString();
    if ($("#message").length > 0) {
        $("#message").html(timestamp + " " + msg).slideDown()
        .delay(5000).slideUp();
    } else {
        $("body>header").append('<div id="message">'+
            timestamp+" "+msg+'</div>');
        $("#message").delay(5000).slideUp();
    }
}

function calcEaster(year) {
    // Borrowed from Emacs
    var msInDay = 1000*60*60*24;
    var century = Math.floor(1 + (year / 100));
    // Age of moon for April 5
    var shiftedEpact = ((14 + (11 * (year % 19)) // Nicean rule
        - Math.floor((3 * century) / 4)          // Gregorian Century rule
        + Math.floor(((8 * century) + 5) / 25)   // Metonic cycle corrctn
        + (30 * century))                        // To keep value positive
        % 30);
    // Adjust for 29.5 day month
    if (shiftedEpact == 0 ||
        (shiftedEpact == 1 && 10 < (year % 19))) {
            var adjustedEpact = shiftedEpact + 1;
        } else {
            var adjustedEpact = shiftedEpact;
        }
    var apr19 = new Date(year, 3, 19, 12);  // Hour needed for accuracy
    var paschalMoon = Math.round(apr19.getTime()/msInDay) - adjustedEpact;
    var paschalMoonDate = new Date(paschalMoon*msInDay);
    var paschalMoonDay = paschalMoonDate.getDay();
    var easter = new Date((paschalMoon+(7-paschalMoonDay))*msInDay);
    return easter;
}
function calcChristmas1(year) {
    var base = new Date(year, 11, 25); // Christmas
    if (base.getDay() == 0) {
        return base;
    } else {
        var offset = new Number(7-base.getDay());
        base.setDate(base.getDate() + offset);
        return base;
    }
}
function calcEpiphany1(year) {
    var base = new Date(year, 0, 6); // Epiphany
    if (base.getDay() == 0) {
        return base;
    } else {
        var offset = new Number(7-base.getDay());
        base.setDate(base.getDate() + offset);
        return base;
    }
}
function calcMichaelmas1(year) {
    var michaelmas = new Date(year, 8, 29);
    if (sessionStorage.michaelmasObserved != -1 && michaelmas.getDay == 6) {
        return new Date(year, 8, 30);
    } else {
        var base = new Date(year, 9, 1); // Oct 1
        var offset = new Number(7-base.getDay());
        base.setDate(base.getDate() + offset);
        return base
    }
}
function getDayFor(datestr, target) {
    var sdate = dateValToSQL(datestr);
    $.get("churchyear.php", {daysfordate: sdate},
        function(rv) {
            rv = eval(rv);
            if (rv[0]) {
                if (rv[1] != null) {
                    var names = eval(rv[1]);
                    if (target.is("input")) {
                        target.val(names.join(", "));
                    } else {
                        target.html(names.join(", "));
                    }
                }
            }
        });
}

function updateBlocksAvailable(datestr) {
    var dateval = dateValToSQL(datestr);
    $.get("block.php", {available: dateval}, function(rv) {
        rv = eval(rv);
        $("#block").empty();
        $("#block").append('<option value="None">None</option>');
        if (rv[0]) {
            for (var blockId in rv[1]) {
                $("#block").append('<option value="'+blockId+'">'+rv[1][blockId]+'</option>');
            }
        }
    });
}

function dateValToSQL(dateval) {
    var dateobj = new Date(dateval);
    return dateobj.toISOString().split("T")[0];
}

$(document).ready(function() {
    $("#loginform").submit(function(evt) {
        evt.preventDefault();
        submitLogin();
    });
    $("#message").delay(5000).slideUp();
});



// vim: set ft=javascript :
