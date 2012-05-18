<? require('functions.php'); ?>

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

function updateExisting() {
    var dateEntered = Date.parse($(this).val())/1000;
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
                    } else {
                        $('.existingservice').prop('disabled', false);
                        $("#liturgicalname").prop('disabled', false);
                        $("#rite").prop('disabled', false);
                        $("#servicenotes").prop('disabled', false);
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
    if ($(".message").length > 0) {
        $(".message").html(timestamp + " " + msg).slideDown()
        .delay(5000).slideUp();
    } else {
        $("body>header").append('<div class="message">'+
            timestamp+" "+msg+'</div>');
        $(".message").delay(5000).slideUp();
    }
}

function updateDay(params) {
    displayId = "#"+params.old_dayname;
    if ($(displayId)) {
        // update existing display
    } else {
        // Insert a new service where the date exists in the current year
    }
}

function submitDayform() {
    var dayParams = {
        submit_day: 1,
        old_dayname: $("#old-dayname").val(),
        dayname: $("#dayname").val(),
        season: $("#season").val(),
        base: $("#base").val(),
        offset: $("#offset").val(),
        month: $("#month").val(),
        day: $("#day").val(),
        observed_month: $("#observed-month").val(),
        observed_sunday: $("#observed-sunday").val()
    }
    var jqxhr = $.post("churchyear.php", dayParams,
        function(result) {
            if (result[0]) {
                dayParams.currentYear = result[2];
                updateDay(dayParams);
            }
            setMessage(result[1]);
        }
    );
}

$(document).ready(function() {
    $("#loginform").submit(function(evt) {
        evt.preventDefault();
        submitLogin();
    });
});



// vim: set ft=javascript :
