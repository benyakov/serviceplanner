<?
// Path from web server's document root where this is installed.
$install_path = "/services/";

// List of possible hymnbooks to draw from
$option_hymnbooks = array(
    "TLH",
    "ELH"
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
    "records"=>"Service Records"
    ,"modify"=>"Modify Services"
    ,"enter"=>"Enter New Service"
    ,"sermons"=>"Sermon Plans"
    ,"admin"=>"Housekeeping"
);

?>
