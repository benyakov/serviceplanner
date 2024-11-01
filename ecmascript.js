/* Javascript library
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
    if ($("#hymnentries tr").length > 2) {
        $("#hymnentries > tbody > tr").eq(-1).find('[id^=sequence]')
            .val(Number($("#hymnentries > tbody > tr").eq(-2)
                    .find('[id^=sequence]').val())+1);
    }
    $("#hymnentries > tbody > tr").eq(-1).attr("data-index",
        Number($("#hymnentries > tbody > tr").eq(-1).attr("data-index")) + 1);
    $("#hymnentries > tbody > tr").eq(-1).clone()
        .appendTo("#hymnentries > tbody");
    var indexStart = Number($("#hymnentries > tbody > tr").eq(-2)
        .attr('data-index'));
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=delete]')
        .attr("id", "delete_new-"+indexStart)
        .attr("name", "delete_new-"+indexStart)
        .attr("disabled", true);
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=sequence]')
        .attr("id", "sequence_new-"+indexStart)
        .attr("name", "sequence_new-"+indexStart);
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=book]')
        .attr("id", "book_new-"+indexStart)
        .attr("name", "book_new-"+indexStart);
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=number]')
        .attr("id", "number_new-"+indexStart)
        .attr("name", "number_new-"+indexStart)
        .val("")
        .change(function(evt) {
            if (evt.which == 9 ||
                evt.which == 17) return;
            $(this).doTimeout('fetch-hymn-title', 250, fetchHymnTitle)
        });
        //.change(fetchHymnTitle); // Causes duplicate requests
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=note]')
        .attr("id", "note_new-"+indexStart)
        .attr("name", "note_new-"+indexStart)
        .val("");
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=occurrence]')
        .attr("id", "occurrence_new-"+indexStart)
        .attr("name", "occurrence_new-"+indexStart);
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=title]')
        .attr("id", "title_new-"+indexStart)
        .attr("name", "title_new-"+indexStart)
        .attr("data-hymn", indexStart)
        .val("")
        .change(function() {
                var listingord = $(this).attr("data-hymn");
                $(this).removeClass("data-saved");
                $("#savetitle_new-"+listingord).show();
        });
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=savetitle]')
        .attr("id", "savetitle_new-"+indexStart)
        .attr("data-hymn", indexStart)
        .click(function(evt) {
                evt.preventDefault();
                var listingord = $(this).attr("data-hymn");
                var xhr = $.getJSON("enter.php",
                        { sethymntitle: $("#title_new-"+listingord).val(),
                        number: $("#number_new-"+listingord).val(),
                        book: $("#book_new-"+listingord).val() },
                        function(result) {
                            if (result[0]) {
                                $("#title_new-"+listingord).addClass("data-saved");
                                $("#savetitle_new-"+listingord).hide();
                            }
                            setMessage(result[1]);
                        });
        });
    $("#hymnentries > tbody > tr").eq(-2).find('[id^=past]')
        .attr("id", "past_new-"+indexStart)
        .attr("data-hymn", indexStart)
    $("#hymnentries > tbody > tr").eq(-2).removeClass("table-template");
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
        .change(function(evt) {
            if (evt.which == 9 ||
                evt.which == 17) return;
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
    $("#hymnentries > li").eq(-1).children().filter('[id^="savetitle"]')
        .attr("id", "savetitle_"+hymnIndex)
        .attr("tabindex", tabindexStart+8);
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
                            .prop('disabled', true)
                            .addClass('disabled-input');
                        var details = sessionStorage.getItem("ExistingServices");
                        details = $.parseJSON(details);
                        var option = $(this).data('option');
                        $("#liturgicalname")
                            .val(details[option]['dayname'])
                            .prop('disabled', true)
                            .addClass('disabled-input');
                        $("#rite")
                            .val(details[option]['rite'])
                            .prop('disabled', true)
                            .addClass('disabled-input');
                        $("#servicenotes")
                            .val(details[option]['servicenotes'])
                            .prop('disabled', true)
                            .addClass('disabled-input');
                        $("#block")
                            .val(details[option]['block'])
                            .prop('disabled', true)
                            .addClass('disabled-input');
                        $("#block").trigger("change");
                    } else {
                        $('.existingservice')
                            .prop('disabled', false)
                            .removeClass('disabled-input');
                        $("#liturgicalname").prop('disabled', false)
                            .removeClass('disabled-input');
                        $("#rite").prop('disabled', false)
                            .removeClass('disabled-input');
                        $("#servicenotes").prop('disabled', false)
                            .removeClass('disabled-input');
                        $("#block").val('None')
                            .prop('disabled', false)
                            .removeClass('disabled-input');
                        $("#block").trigger("change");
                    }
                })
            })
}

function fetchHymnTitle() {
    var id = $(this).attr("id").split("_");
    var entryNumber = id[1];
    var hymnNumber = $(this).val();
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
                var xref = result[2];
                if (hymnTitle) {
                    $("#title_"+entryNumber).val(hymnTitle).show();
                    if (! xref) {
                        $("#title_"+entryNumber).addClass("data-saved");
                        $("#savetitle_"+entryNumber).hide();
                    } else {
                        $("#savetitle_"+entryNumber).show();
                    }
                } else {
                    $("#title_"+entryNumber).val("")
                        .attr("placeholder", "<Please enter a title.>")
                        .removeClass("data-saved")
                        .show();
                    $("#savetitle_"+entryNumber).show();
                }
                var past = new Array;
                var occstr;
                for (service in pastServices) {
                    occstr = pastServices[service]['occurrence']
                        ?" (" + pastServices[service]['occurrence'] + ")"
                        :"";
                    if (pastServices[service]['date']) {
                        past.push(pastServices[service]['date'] + occstr);
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
        password: $("#password").val(),
        activated: $("#sitetabs>li.activated").data("name") },
        function(result) {
            setupLogin(result);
            if (result['userlevel']) {
                setMessage("Logged in.");
                svch.execHandlers('login');
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
    $("#loginform").off('click').submit(function(evt) {
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
            window.location.href="index.php";
        });
}

function setMessage(msg) {
    var timestamp = (new Date).toTimeString();
    if (localStorage.getItem('messages')) {
        var msgs = JSON.parse(localStorage.getItem('messages'));
    } else {
        var msgs = [];
    }
    msgs.push(msg);
    localStorage.setItem('messages', JSON.stringify(msgs));
    if ($("#message").length > 0) {
        $("#message").html(timestamp + " " + msg)
    } else {
        $("body>header").append('<div style="display: none" id="message">'+
            timestamp+" "+msg+'</div>');
    }
    $("#message").data("seconds", 0);
    displayMessage();
}

function displayMessage() {
    if ($("#message").data("seconds") == 0) {
        $("#message").data("seconds", 5);
        waitAndCheck(hideMessage);
    } else {
        $("#message").data("seconds", 5);
    }
    $("#message").show();
}

function waitAndCheck(doAfter) {
    if ($("#message").data("seconds") == 0) {
        doAfter();
        return;
    } else {
        window.setTimeout(function() {
            var secs = $("#message").data("seconds");
            if (secs > 0) {
                $("#message").data("seconds", secs-1);
                waitAndCheck(doAfter);
            }
        }, 1000);
    }
}

function hideMessage() {
    $("#message").hide();
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
function calcAdvent4(year) {
    var base = new Date(year, 11, 25); // Christmas
    if (base.getDay() == 0) {
        return new Date(year, 11, 18);
    } else {
        base.setDate(base.getDate() - base.getDay());
        return base;
    }
}
function calcChristmas1(year) {
    var base = new Date(year, 11, 25); // Christmas
    var offset = new Number(7-base.getDay());
    base.setDate(base.getDate() + offset);
    return base;
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
    var michaelmas = new Date(year, 8, 29); // Sep 30
    if (sessionStorage.michaelmasObserved != -1 && michaelmas.getDay == 6) {
        return new Date(year, 8, 30);
    } else {
        var m1 = new Date(year, 9, 1); // Oct 1
        var offset = new Number(7-m1.getDay());
        if (offset == 7) offset = 0;
        m1.setDate(m1.getDate() + offset);
        return m1;
    }
}
function getDayFor(datestr, target) {
    try {
        var sdate = dateValToSQL(datestr);
    } catch(err) {
        return;
    }
    $.get("churchyear.php", {daysfordate: sdate},
        function(rv) {
            rv = eval(rv);
            if (rv.length) {
                if (target.is("input")) {
                    target.val(rv.join(" | "));
                } else {
                    target.html(rv.join(" | "));
                }
            }
        });
}

function updateBlocksAvailable(datestr) {
    try {
        var dateval = dateValToSQL(datestr);
    } catch(err) {
        return;
    }
    $.get("block.php", {available: dateval}, function(rv) {
        rv = eval(rv);
        $("#block").empty();
        $("#block").append('<option value="None">None</option>');
        if (rv[0]) {
            for (var blockId in rv[1]) {
                $("#block").append('<option value="'+blockId+'">'+rv[1][blockId]+'</option>');
            }
        }
        var block_default = $("#block").attr("data-default");
        if (block_default) {
            $("#block").val(block_default);
        }
    });
}

function dateValToSQL(dateval) {
    var dateobj = new Date(dateval);
    return dateobj.toISOString().split("T")[0];
}

function openStyler() {
    if ($(this).attr('data-state') == "open") {
        $("#stylerdialog").dialog("close");
        $(this).attr('data-state', "closed");
    } else {
        $(this).attr('data-state', "open");
        $("#stylerdialog").dialog({title: "Style Adjuster",
            width: $(window).width()*0.4,
            maxHeight: $(window).height()*0.9,
            position: "right"
        });
    }
}

function openMsgViewer() {
    if ($(this).attr('data-state') == "open") {
        $("#msgdialog").dialog("close");
        $(this).attr('data-state', "closed");
    } else {
        $(this).attr('data-state', "open");
        var messages = JSON.parse(localStorage.getItem("messages"));
        if (! messages) {
            messages = ["No messages."];
        }
        var display = '<form><button id="clearMessages">Clear Messages</button></form>';
        display += "<p>(Most recent at the top.)</p>";
        for (var i = messages.length-1; i > -1; i--) {
            display += "<p>"+messages[i]+"</p>";
        }
        $("#msgdialog").html(display);
        $("#clearMessages").click(function() {
            localStorage.removeItem('messages');
            $("#msgdialog").dialog("close");
            $(this).attr('data-state', "closed");
            return;
        });
        $("#msgdialog").dialog({title: "Message Viewer",
            width: $(window).width()*0.5,
            maxHeight: $(window).height()*0.9,
            position: { my: "center", at: "center", of: window}
        });
    }
}

function updateCSS() {
    localStorage.setItem("basefont", $("#basefont").val());
    localStorage.setItem("hymnfont", $("#hymnfont").val());
    localStorage.setItem("notefont", $("#notefont").val());
    localStorage.setItem("blockdisplay", $("#cssblockdisplay").is(':checked'));
    localStorage.setItem("propers", $("#csspropers").is(':checked'));
    var occboxes = $(".cssadjusterloc").get();
    if (localStorage.getItem('occurrences'))
        var locarray = $.parseJSON(localStorage.getItem('occurrences'));
    else var locarray = new Object();
    for (domitem in occboxes) {
        var name = $(occboxes[domitem]).attr('name');
        var checked = $(occboxes[domitem]).is(':checked');
        locarray[name] = checked;
    }
    localStorage.setItem("occurrences", JSON.stringify(locarray));
    setCSSTweaks();
}

function setCSSTweaks() {
    if (localStorage.getItem("basefont"))
        $("body").css("font-size", localStorage.getItem("basefont")+"px");
    if (localStorage.getItem("hymnfont"))
        $(".hymn-number,.note,.title").css("font-size", localStorage.getItem("hymnfont")+"%");
    if (localStorage.getItem("notefont"))
        $(".servicenote").css("font-size", localStorage.getItem("notefont")+"%");
    if (localStorage.getItem("blockdisplay") !== null &&
        localStorage.getItem("blockdisplay") == "false")
        $(".blockdisplay").addClass("hidden");
    else $(".blockdisplay").removeClass("hidden");
    if (localStorage.getItem("propers") !== null &&
        localStorage.getItem("propers") == "false")
        $(".propers").addClass("hidden");
    else $(".propers").removeClass("hidden");
    if (localStorage.getItem("occurrences")) {
        var occurrences = $.parseJSON(localStorage.getItem("occurrences"));
        for (occ in occurrences) {
            if (occurrences[occ]) {
                $('tr[data-occ="'+occ+'"]').show();
            } else {
                $('tr[data-occ="'+occ+'"]').hide();
            }
        }
    }
}

var zeropadding = "000000000000";
function zeroPad(number, width) {
    var input = number + "";
    return zeropadding.slice(0, width-input.length) + input;
}

function scrollTarget(place) {
    window.location.hash = place;
    window.scrollTo(0, $(window).scrollTop()-60);
    $('a[name="'+place+'"]').addClass('highlight');
}

function ServiceHandlers() {
    this.handlers = {};
    this.addHandler = addHandler;
    function addHandler(eventname, handler) {
        if (! this.handlers[eventname]) {
            this.handlers[eventname] = Array();
        }
        this.handlers[eventname].push(handler);
    }
    this.execHandlers = execHandlers;
    function execHandlers(eventname) {
        if (eventname in this.handlers)
            for (i in this.handlers[eventname])
                this.handlers[eventname][i]();
    }
    return this;
    function clearHandlers(eventname) {
        if (eventname in this.handlers)
            delete this.handlers[eventname];
    }
}

var svch = new ServiceHandlers();

function setupSortableList() {
    $("#sortablelist").sortable({
        items: "tr[class!='heading']",
        update: function(event, ui) {
            var ids = $(this).sortable("toArray");
            ids = $.grep(ids, function(e, i){ // Remove empties
                return e != "";
            });
            ids = $.map(ids, function(o, i) { // Turn into id selectors
                return "#"+o;
            });
            var jqObj = $(ids.join()); // Default separator is a comma
            jqObj.filter(":visible").each(function(i) {
                $(this).find(".edit-sequence").val(i+1);
            });
        }
    });
}

function setupFlags() {
    $(".flagbutton").click(onFlagButtonClick);
    $(".service-flags").on('appear dblclick', function(evt) {
        if (! $(this).data('loaded')) {
            pullFlags(1, $(this));
        }
        $(this).data('loaded', true);
    });
    $.force_appear();
}

function flagFormSubmit(evt) {
    evt.preventDefault();
    var data = $(this).serializeArray();
    data.push({name: "json", value: 1});
    data.push({name: "submit", value: 1});
    var service = $(this).find("[name='service']").val();
    var these_flags = $("[class='service-flags'][data-service='"+service+"']");
    $.post(this.action, $.param(data), function(rv) {
        var returnval = $.parseJSON(rv);
        $('#dialog').dialog('close');
        setMessage(returnval[1]);
        $(these_flags).each(pullFlags);
        return false;
    });
    return false;
}

function onFlagButtonClick(evt) {
    evt.preventDefault();
    $.post("flags.php", { 'json': 1,
        'service': $(this).parents('tr').data('service'),
        'occurrence': $(this).parents('tr').data('occ')
    }, function (rv) {
        var returnval = $.parseJSON(rv);
        $('#dialog').html(returnval[1]);
        // Set up dialog to submit json
        $('#dialog').find('#service_flags')
            .submit(flagFormSubmit);
        $('#dialog').find('#new_flag_submit')
            .click(newFlagSubmit);
    });
    $("#dialog").dialog({title: "Service Occurrence Flags",
        width: $(window).width()*0.9,
        maxHeight: $(window).height()*0.9,
        position: { my: "top", at: "top", of: window}
    })
        .on('dialogclose', function(event) {
            $('#dialog').html('');
            /*refreshContent();*/
        });
}

function pullFlags(index, row) {
    if (! ($(row).data('service')
        && $(row).data('occ'))) {
        return true;
    }
    var xhr = $.getJSON("flags.php", {
        action: 'get',
        service: $(row).data('service'),
        occurrence: $(row).data('occ') },
        function(result) {
            if (result[0]>0) {
                $(row).children().eq(0).html(result[1]);
                $(row).find(".delete-flag").click(function(evt) {
                    evt.preventDefault();
                    var c = confirm("Are you sure you want to delete this flag?");
                    var flagid = $(this).data('flagid');
                    var userid = $(this).data('userid');
                    var deleteval = flagid + "_delete";
                    var params = { 'step': "change_flags",
                            'json': 1,
                            'service': $(row).data('service'),
                            'occurrence': $(row).data('occ'),
                            'user': userid };
                    params[deleteval] = 'on';
                    var these_flags = $(this).parents('tr.service-flags');
                    if (c == true) {
                        $.post('flags.php', params,
                            function(response) {
                                setMessage(response[1]);
                                $(these_flags).each(pullFlags);
                            }, 'json');
                    }
                });
            } else if (result[0] == 0) {
                $(row).children().eq(0).html("");
            } else {
                setMessage("Couldn't get flags. "+result[1]);
            }
            $(row).find(".flagbutton").unbind('click').click(onFlagButtonClick);
        });
}

function newFlagSubmit(evt) {
    evt.preventDefault();
    var data = $('#add_flag').serializeArray();
    data.push({name: "json", value: 1});
    var service = $("#add_flag [name='service']").val();
    var these_flags = $("[class='service-flags'][data-service='"+service+"']");
    $.post('flags.php', data,
        function(result) {
            var r = $.parseJSON(result);
            var prev = $('#add_flag_report').html();
            $('#add_flag_report').html(prev+r);
            $(these_flags).each(pullFlags);
        });
}

function setupFilterForm() {
    $("#service-filter")
        .empty()
        .html('<p><form id="filterform" data-filtered="0">'
        +'<input type="text" name="filterstring" id="filterinput" placeholder="Filter by flag text">'
        +'<label for="expandfiltered">Expand</label>'
        +'<input type="checkbox" name="expandfiltered" id="expandfiltered" value="checked">'
        +'<button type="submit" id="filtersubmit">Set Flag Filter</button>'
        +'</form></p>');
    $("#filterform").submit(toggleFilter);
}

function toggleFilter(evt) {
    evt.preventDefault();
    if ($("#records-listing").length) {
        var table = "records-listing";
    } else if ($("#modify-listing").length) {
        var table = "modify-listing";
    } else {
        return false;
    }
    if ("0" == $("#filterform").data("filtered")) {
        $("#"+table+">tbody>tr").hide()
        var filter_text = $('#filterinput').val();
        var filter_reg = new RegExp(filter_text, "i");
        var services = $("tr.service-flags div").filter(function() {
                return filter_reg.test($(this).text());
        })
            .parents("tr[data-service]")
            .map(function(){return $(this).data('service');})
            .get();
        services = Array.from(new Set(services)); // Make unique
        $(services)
            .each(
                function(index){
                    var selector = "#"+table+">tbody>tr[data-service="
                        +this+"]";
                    $(selector).show();
        });
        $("#filterform").data("filtered", 1);
        $("#filterinput")
            .prop("disabled", true)
            .addClass("disabled-input");
        $("#filtersubmit").html("Remove Flag Filter");
    } else {
        $("#filterform").data("filtered", true);
            $("#"+table+">tbody>tr").show();
        $("#filterform").data("filtered", 0);
        $("#filterinput")
            .prop('disabled', false)
            .removeClass("disabled-input");
        $("#filtersubmit").html("Set Flag Filter");
    }
    if (! $('#expandfiltered').is(':checked')) {
        contractAllListings(table);
    } else {
        $('.expandservice').filter(":visible")
            .removeClass("contracted")
            .html('-');
    }
    return false;
}

function contractAllListings(tableId) {
    var rows = $('#'+tableId+' > tbody > tr').not('tr.servicehead')
        .not('tr.service-flags')
        .hide();
    $('a.expandservice')
        .addClass("contracted")
        .html('+');
}

function setupListingExpansion() {
    $('.expandservice').click(toggleListing);
}

function toggleListing(evt) {
    evt.preventDefault();
    if ($(this).html() == "+") {
        showExtras($(this).parents('tr').data("service"), $(this).parents('tr').data("occ"));
    } else {
        hideExtras($(this).parents('tr').data("service"), $(this).parents('tr').data("occ"));
    }
}

function getOccSpec(serviceocc) {
    if ($("#modify-listing").length)
        var table = $("#modify-listing");
    else
        var table = $("#records-listing");
    return "[data-occ='"+serviceocc+"']"
}

function showExtras(serviceid, serviceocc) {
    if (serviceocc) { // no serviceocc in combined view
        var occspec = getOccSpec(serviceocc);
    } else {
        var occspec = "";
    }
    $("tr[data-service="+serviceid+"]"+occspec+".servicehead a.expandservice")
        .removeClass("contracted")
        .html("-");
    $("tr[data-service="+serviceid+"]"+occspec).show(400);
}

function hideExtras(serviceid, serviceocc) {
    if (serviceocc) { // no serviceocc in combined view
        var occspec = getOccSpec(serviceocc);
    } else {
        var occspec = "";
    }
    var serviceLines = $("#records-listing > tbody > tr[data-service="+serviceid+"]"+occspec);
    if (! serviceLines.length) {
        serviceLines = $("#modify-listing > tbody > tr[data-service="+serviceid+"]"+occspec);
    }
    $(serviceLines).find('a.expandservice')
        .addClass("contracted")
        .html("+");
    serviceLines.not('tr.servicehead').not('tr.service-flags').hide(400);
}

function showExtraDayname(form) {
    // Get the propers and block info for the dayname in the form.
    // Modify the DOM near the form to show both.
    // TODO
}

$(document).ready(function() {
    $("#loginform").submit(function(evt) {
        evt.preventDefault();
        submitLogin();
    });
    $("#openstyler").click(openStyler);
    $("#message").delay(8000).slideUp();
    $("#seemessages").click(openMsgViewer);
    $(".extra-dayname-form").submit(function(evt) {
        evt.preventDefault();
        showExtraDayname(this);
    });
    setCSSTweaks();
});



// vim: set ft=javascript :
