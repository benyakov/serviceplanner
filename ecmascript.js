function addHymn() {
    $("#hymnentries > li").eq(-1).clone().appendTo("#hymnentries");
    var oldBookId = $("#hymnentries > li").eq(-1).children().attr("id");
    var hymnIndex = Number(oldBookId.split("_")[1]) + 1;
    $("#hymnentries > li").eq(-1).children().filter('[id^="book"]').attr("id", "book_"+hymnIndex).attr("name", "book_"+hymnIndex);
    $("#hymnentries > li").eq(-1).children().filter('[id^="number"]').attr("id", "number_"+hymnIndex).attr("name", "number_"+hymnIndex);
    $("#hymnentries > li").eq(-1).children().filter('[id^="note"]').attr("id", "note_"+hymnIndex).attr("name", "number_"+hymnIndex);
    $("#hymnentries > li").eq(-1).toggleClass('even odd');
}

function showJsOnly() {
    $(".jsonly").removeClass("jsonly");
}

$(document).ready(function() {
    showJsOnly();
    $("#date").focus(function() {
        $("#date").datepicker();
    })
})
