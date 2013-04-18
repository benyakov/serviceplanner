<!DOCTYPE html>
<html lang="en">
<?
require(".functions.php");
html_head("Help");
?>
<body>
    <h1>Contents</h1>
    <ul>
    <li><a href="#tour">Introductory Tour</a></li>
    </ul>


    <h1><a name="tour">Introductory Tour</a></h1>

    <p>So you have your own installation of the service planner, and now
you'd like to know how to use it.  This introduction should help.  I'll
try to keep it brief, at the risk of leaving out some important or
advanced things.</p>

    <p>It will help to have a web browser window open to the service
planner beside this introduction, so you can try things yourself as your
read.</p>

    <p>The pages of the service planner are arranged in a series of tabs
accessible across the top of the window.  Anyone can use the service
planner, but unless you log in, you will only see a limited number of tabs.
Login links and related information are at the very top of the page on the
left side.  Privileged users will also see a "User Administration" link at
the center of the very top when they are logged in.  That's where you
manage other users, if you need any.</p>

    <p>On the right of the very top is an Adjust Styles link, which opens a
dialog box on the screen allowing you to make some adjustments to the
layout of the service listing.  Unless you are dissatisfied with the
listing at some point, just ignore that link.</p>

    <h2><a name="tabs">Tabs</a></h2>

    <h3><a name="anonymous_tabs">Anonymous Access Tabs</a></h3>

    <p>There are three tabs available to anonymous (not logged-in) users:
"Upcoming Hymns," "Service Records," and "Cross Ref."  The first simply
lists all hymns in their services from this day forward, in chronological
order.  The very next service is at the top of the list.  "Service Records"
is similar, but it shows <em>all</em> services that have been planned, with
the latest (farthest in the future) at the top of the list.  The Cross Ref
tab displays a cross-reference table of hymn numbers from various books.
Clicking on a blue heading causes the page to reload, sorted on that
column.</p>

    <p>On the Service Records page (and the soon-to-be-introduced Modify
Services page), for your convenience, there is a <a name="thisweek">Jump to
This Week</a> link-button at the top right of the page, which will scroll the
page to contemporary planned services.  (That's different from planned
contemporary services, which the service planner does not support.)</p>

    <p>Under the main heading is an adjustable Listing Limit, which allows
you to specify how many hymns should be listed on the page.  When you get a
lot of hymns in the service planner, and you load all of them, the page
refresh can get a mite slow.</p>

    <h3><a name="user_tabs">User Access Tabs</a></h3>

    <h4><a name="modify_services">Modify Services</a></h4>

    <p>When you log in, the Service Records page is replaced with the much more
powerful Modify Services page.  This is where you add new services or change
ones you've already planned.  Two more buttons behind the Listing Limit allow
you to list only future hymns (like the Upcoming Hymns page) or show all hymns
(like the Service Records page).</p>

    <p>Each service is listed under its own heading line containing the date
and location of the service and liturgical day name.  Below that line appear
the <em>Evangelical Lutheran Hymnary (ELH)</em>'s topical description for
hymns on that day, as well as the liturgical color for paraments.</p>

    <p>The actual hymns are listed line-by-line, with the abbreviation for the
hymnbook, the number, any notes for that hymn (verse/stanza numbers, etc.), and
the title.</p>

    <p>Between the heading line and the list of hymns, there is service
information, including any special notes about that service, the Introit, and
the gradual.  Some services may be assigned to named blocks (introduced below)
for planning purposes.  Those associated with blocks also contain a rectangle
with a heading inside that says "Block: " and the name of the block to which
the service belongs.  The block specifies which propers will be used for that
service, and they are automatically looked up and displayed in the block
rectangle.</p>

    <p>Each heading line also contains buttons for adding more hymns to a
service (possibly in a different location), editing the service as it is
currently entered, adding a sermon, or printing just that one service with its
hymns (handy for organists!).</p>

    <h4><a name="block_plans">Block Plans</a></h4>

    <p>The Block Plans tab allows you to create a block of services planned
together, falling within a particular span of time.  The timespan of multiple
blocks may overlap, so that you might have one block for Lent Sundays and
another for Lend midweek services, and perhaps another for nonspecific services
throughout the year.  Each service can be associated with one block, or with no
block at all.</p>

    <p>Each block associates a set of propers to be used in its services.  The
default propers available are those specified in the <em>Evangelical Lutheran
Hymnary (ELH)</em>.  A set of notes may also be included in each block, which
will be displayed alongside the propers on the Upcoming Hymns, Service Records,
or <a href="#modify_services">Modify Services</a> tabs.

    <p>If a new user doesn't see an immediate application for service blocks,
I'd recommend not using them at all.  Just ignore this tab, in that case.  You
can always add blocks later.</p>

    <h4><a name="sermon_plans">Sermon Plans</a></h4>

    <p>The Sermon Plans tab is where you will find plans for sermons
(shocking!), once you have created them.  The Service Planner is centered on
the whole service, so in order to create a sermon plan, you will have to have a
service already in the system for that particular day.  It need not have any
hymns, but it must exist.  Then, on the <a href="#modify_services">Modify
Services</a> page, where the service is listed, you can add a sermon by
clicking the (... wait for it ...) Sermon link in the heading for that
service.</p>

    <p>The only thing that may not be obvious here is that when a manuscript
file has been uploaded and saved in the Service planner, a link with the
letters "mss" appears in the listing of sermon plans right next to the text.
Clicking that link will download the saved file.</p>

    <h4><a name="church_year">Church Year</a></h4>

    <p>The Church Year tab is magical.  Figuratively, that is.  This is where
you can alter the Service Planner's knowledge of the church year and the
propers assigned to each day.  I wouldn't advise changing anything here until
you're pretty sure you understand what you want to do and how to do it.  Not
that you can break anything, but the system is somewhat sophisticated.  If you
do manage to break something and want to restore it to its original form,
that's always possible on the Housekeeping tab.</p>

    <p>One of the interesting possibilities here is that you can add new sets of propers that will become available to your block plans.</p>

    <p>Another feature of more obvious usefulness is that the second column
on the Church Year tab tells you when the next occurrence falls for
each of the computed days in the Church Year.   So if you want to know the date
of Lent 2 next time around, just find that row on the Church Year tab.</p>

    <h4><a name="housekeeping">Housekeeping</a></h4>

    <p>This is where you can perform maintenance and get some extra information
about the Service Planner.  The page is mostly self-documented, but it contains
some advanced concepts.  You can safely ignore what you don't understand.  One
item that you should not ignore is toward the bottom under the heading "The
Broom Closet."  The very first item there contains a link you should use
periodically to download a backup of all your data.  You should use the
suggested filename, because it contains the version of the Service Planner you
are backing up.  If you ever need to restore that data, you will need a
compatible installation of the software, and that version number makes it
possible.</p>

    <p>The other immediately noteworthy item, if you plan to use the block
planning feature, is at the bottom of the page.  Here is where you set your
preference for the Bible version in which to read the lections.  If you save an
abbreviation like "nkjv" there, then your service listings will not only
include the lection references in their block plan rectangles, but those
references will be links to the texts via BibleGateway.com.  If you don't save
an abbreviation here, then the lection references will not be links.<p>

    <h2>Tips</h2>



</body>
</html>

