<? require('functions.php'); ?>

function addHymn() {
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
        .attr("tabindex", tabindexStart+5);
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
    if (! hymnNumber) {
        $("#title_"+entryNumber).val("").hide();
        $("#past_"+entryNumber).text("").hide();
        return;
    }
    var hymnBook = $("#book_"+entryNumber).val();
    var jqxhr = $.getJSON("hymntitle.php",
            { number: hymnNumber, book: hymnBook },
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
            logout(null);
        }
    }).click(function(evt) {
        logout(evt);
    });
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
        $(".message").html(timestamp + " " + msg);
    } else {
        $("body>header").append('<div class="message">'+
            timestamp+" "+msg+'</div>');
    }
}

$(document).ready(function() {
    $("#loginform").submit(function(evt) {
        evt.preventDefault();
        submitLogin();
    });
});

// vim: set ft=javascript :
