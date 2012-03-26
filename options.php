<?
// List of possible hymnbooks to draw from
$option_hymnbooks = array(
    "ELH",
    "TLH",
    "CW",
    "LSB",
    "LW"
);

// How many hymns can be entered at once
$option_hymncount = 8;

// How many old hymn use dates should be listed when verifying
// new hymn titles to enter into a service?
$option_used_history = 5;

// Site tabs are for switching pages/functions in the application
// This determines which tabs are visible on pages that use them.
// You probably don't need to change this, but if you wish,
// you can comment out one or more tabs by prepending "//" to the line,
// or you can modify the values in the array (not the keys) to change
// the text displayed on the tabs.
$sitetabs = array(
    "index"=>"Upcoming Hymns"
    ,"records"=>"Service Records"
    ,"modify"=>"Modify Services"
    ,"enter"=>"Add Service"
    ,"sermons"=>"Sermon Plans"
    ,"hymnindex"=>"Cross Ref"
    ,"admin"=>"Housekeeping"
);

// This is the same as above, and will be substituted for it when the web
// site user has not logged in.  This does not prevent the user from trying
// to access the other tabs manually, but it does allow for a less confusing
// interface.
$sitetabs_anonymous = array(
    "index"=>"Upcoming Hymns"
    ,"records"=>"Service Records"
    ,"hymnindex"=>"Cross Ref"
);

// Default limit for the comprehensive (not future) service listings.
// When this has not been manually set by the user, this will be the number
// of hymns included in the list.
$listinglimit = 200;

// Location of a PHP "library" directory on the web server where you might
// have e.g. markup-processing packages installed.
// For example, If you download Markdown
// (http://michelf.com/projects/php-markdown/) and place markdown.php or
// a link to it in a specific directory, you can point to that directory
// with $phplibrary.  Then, sermon notes will automatically be formatted
// using Markdown when displayed.
$phplibrary = "../../php";
// Do not change this unless you know what you're doing.
// It takes a relative $phplibrary and makes it absolute,
// which allows the path to work even when this application is symlinked.
if (strpos($phplibrary, '/') != 0) {
    $phplibrary = dirname(__FILE__) . DIRECTORY_SEPARATOR . $phplibrary;
}

?>
