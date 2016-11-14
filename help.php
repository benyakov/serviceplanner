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
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
    exit(0);
} ?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Help")?>
<body>
<?pageHeader();
  siteTabs($auth, "admin");?>
    <div id="content-container">
    <div class="maxcolumn">
    <h1>Help Contents</h1>

    <p>To return to the regular Housekeeping tab, just click on it.</p>
    <h2>Introductory Tour</h2>
    <ol>
    <li><a href="#anonymous_tabs">Anonymous Access Tabs</a> (Upcoming Hymns, Service Records, Cross Ref, and Report)</li>
    <li><a href="#report">Report</a></li>
    <li><a href="#modify_services">Modify Services</a></li>
    <li><a href="#block_plans">Block Plans</a></li>
    <li><a href="#sermon_plans">Sermon Plans</a></li>
    <li><a href="#church_year">Church Year</a></li>
    <li><a href="#housekeeping">Housekeeping</a></li>
    </ol>
    <h2>Guides and Tips</h2>
    <ul>
    <li><a href="#workflow_example">Workflow</a></li>
    <li><a href="#adding_hymns">Adding Hymns to a Service</a></li>
    <li><a href="#changing_service">Changing a Service</a></li>
    <li><a href="#last_use">When did I last use that hymn?</a></li>
    <li><a href="#backups">Data Storage and Backups</a></li>
    </ul>
    <h2>Advanced Features</h2>
    <ul>
    <li><a href="#coinstallation">Populating Hymn Titles from a Co-installation</a></li>
    <li><a href="#mashups">Inserting a Service Listing into Another Web Page</a></li>
    <li><a href="#restoring_defaults">Restoring Defaults</a></li>
    <li><a href="#bug_reporting">Bug Reporting</a></li>
    </ul>


    <h1><a name="tour">Introductory Tour</a></h1>

    <p>So you have your own installation of the service planner, and now
you'd like to know how to use it.  This introduction should help.  I'll
try to keep it brief, at the risk of leaving out some important or
advanced things.  Feel free to skim to the part(s) you want to read.</p>

    <p>It will help to have a web browser window open to the service
planner beside this introduction, so you can try things yourself as you
read.</p>

    <p>The pages of the service planner are arranged in a series of tabs
accessible across the top of the window.  They are independent of one another,
and you need to save any changes there may be in one tab before switching to
another.  Anyone can use the service planner, but unless you log in, you will
only see a limited number of tabs.  Login links and related information are at
the very top of the page on the left side.  Privileged users will also see a
"User Administration" link at the center of the very top when they are logged
in.  That's where you manage other users, if you need any.</p>

    <p>On the right of the very top is an Adjust Styles link, which opens a
dialog box on the screen allowing you to make some adjustments to the
layout of the service listing.  Unless you are dissatisfied with the
listing at some point, just ignore that link.  If you click it anyway,
clicking it again will make the style adjuster dialog disappear again.</p>

    <h2><a name="tabs">Tabs</a></h2>

    <h3><a name="anonymous_tabs">Anonymous Access Tabs</a></h3>

    <p>There are four tabs available to anonymous (not logged-in) users:
Upcoming Hymns, Service Records, Cross Ref, and Report.  The first simply
lists all hymns in their services from this day forward, in chronological
order.  The very next service is at the top of the list.  "Service Records"
is similar, but it shows <em>all</em> services that have been planned, with
the latest (farthest in the future) at the top of the list.  The Cross Ref
tab displays a cross-reference table of hymn numbers from various books.
Clicking on a blue heading causes the page to reload, sorted on that
column. The Report tab shows a simple listing of services and hymns that
may be customized by privileged users. It's available for those who need
to print a simpler sheet of information, or one showing custom
fields.</p>

    <p>On the Service Records page and the soon-to-be-introduced Modify
Services page, for your convenience, there is a <a name="thisweek">Jump to
This Week</a> link-button at the top right of the page, which will scroll the
page to contemporary planned services.  (That's different from planned
contemporary services, which the service planner does not support.)</p>

    <p>Under the main heading is a form allowing you to adjust the time
window for which services will be displayed.  Two buttons below that
switch the display mode from showing only future services (like the
Upcoming Hymns tab) and showing all services (the default behavior).</p>

    <h3><a name="user_tabs">Privileged User Tabs</a></h3>

    <h4><a name="report">Report</h4>

    <p>While the Report tab is available to anonymous users, it must be
configured by a privileged user before it will show anything.
Privileged users can configure the display using the blue form that
appears on that page.</p>

    <p>Instead of a time span, this tab will list only up to a certain
number of hymns, regardless of their assignment to services. The Limit
setting controls this, but when set to zero, the limit is disabled.</p>

    <p>Checking the Future box configures the page to show Future hymns,
in chronological order, instead of all planned hymns in
reverse-chronological order.</p>

    <p>The Start HTML and End HTML text boxes allow privileged
users to modify the way the list is displayed. If you would
like a certain kind of heading, for example, you could put
<pre><?=htmlspecialchars("<h1>Hymns at St. Peter Lutheran Church</h1>")?></pre> at the beginning of the Start HTML field. You may
wish to consult a reference on simple HTML markup, but be cautious about
pasting bits of code in there from the Internet, unless you know what
you are doing. Some code can be malicious.</p>

    <p>A line showing currently-selected fields appears below the
configuration form. Each field is configured with a name and a width for
display purposes. The red characters are links for moving fields
relative to each other (&lt; and &gt;), for deleting fields (-), and for
adding new fields (+).  The fields configured there will appear in the
Report tab listing.  Please note that you will need at least one service
planned before you can add fields to the Report tab.</p>

    <h4><a name="modify_services">Modify Services</a></h4>

    <p>When you log in, the Service Records page is replaced with the
much more powerful Modify Services page. This is where you add new
services or change ones you've already planned.</p>

    <h5><a name="service_format">Standard Service Format</h5>

    <p>The Upcoming Hymns, Service Records, and Modify Services tabs
share the same basic format for displaying each service.</p>

    <p>Each service is listed under its own heading line containing the date
and location of the service and liturgical day name.  If the service is flagged
as one where communion is offered, a boxed C appears after the liturgical day name.
Below that line appear the <em>Evangelical Lutheran Hymnary (ELH)</em>'s
topical description for hymns on that day, as well as the liturgical color for
paraments.</p>

    <p>The actual hymns are listed line-by-line, with the abbreviation for the
hymnbook, the number, any notes for that hymn (verse/stanza numbers, etc.), and
the title.</p>

    <p>Between the heading line and the list of hymns, there is service
information, including any special notes about that service, the
Introit, and the gradual. Some services may be assigned to named blocks
(introduced below) for planning purposes. Those associated with blocks
also contain a rectangle with a heading inside that says "Block: " and
the name of the block to which the service belongs. The block specifies
which propers will be used for that service, and they are automatically
looked up and displayed within the block rectangle.</p>

    <h5>Buttons For Each Service</h5>

    <p>On the Modify Service page, each heading line also contains
buttons for adding more hymns to a service (possibly in a different
location), editing the service as it is currently entered, adding a
sermon, or printing just that one service with its hymns (handy for
organists!). A checkbox appears to the left of the date in each service
heading line, for the purpose of deleting whole services, with all their
hymn selections. The checkboxes are used in conjunction with the Delete
button above or below the service listing.</p>

    <h4><a name="block_plans">Block Plans</a></h4>

    <p>The Block Plans tab allows you to create a block of services planned
together, falling within a particular span of time.  The timespan of multiple
blocks may overlap, so that you might have one block for Lent Sundays and
another for Lent midweek services, and perhaps another for nonspecific services
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

    <p>The Sermon Plans tab is where you will find plans for sermons,
once you have created them. The Service Planner is organized around
the whole service, so in order to create a sermon plan, you will
need to have a service already in the system for that particular
day. It need not have any hymns, but it must exist. Then, on the <a
href="#modify_services">Modify Services</a> page, where the service is
listed, you can add a sermon by clicking the Sermon link in the heading
for that service.</p>

    <p>The only thing that may not be obvious here is that when a manuscript
file has been uploaded and saved in the Service planner, a link with the
letters "mss" appears in the listing of sermon plans right next to the text.
Clicking that link will download the saved file.</p>

    <h4><a name="church_year">Church Year</a></h4>

    <p>The Church Year tab is magical (figuratively).  This is where
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

    <p>This is where you can perform maintenance and get some
extra information about the Service Planner. The page is mostly
self-documented, but it contains some advanced concepts. You can safely
ignore what you don't understand. One item that you should not ignore
is under the heading "The Broom Closet," and the subheading "Backups."
You should use the database backup link periodically to download a
backup file containing all of your data. You should use the suggested
filename, because it contains the version of the Service Planner you
are backing up. If you ever need to restore that data, you will need a
compatible installation of the software, and that version number makes
it possible.</p>

    <p>The other immediately noteworthy item, if you plan to use the
block planning feature, is farther down, under "Config Settings." The
setting entitled "Preferred Bible Abbreviation from Bible Gateway" is
where you set your preference for the Bible version in which to read
the lections. If you save an abbreviation like "NKJV" there, then your
service listings will not only include the lection references in their
block plan rectangles, but those references will be links to the texts
via BibleGateway.com. If you don't save an abbreviation here, then the
lection references will not be links.<p>

    <h2>Usage Tips</h2>

    <h3><a name="workflow_example">Workflow</a></h3>

    <p>Here's an outline of how the author uses the service planner.  You may
prefer another workflow, but if you want somewhere to start, look through the
following.  If you want to use a block plan, which automatically displays the
block's set of propers for each service, then you may want to set that up first
on the Block Plans tab.</p>

    <ol>
    <li>Log in and go to the Modify Services tab.</li>
    <li>Click the New Service button at the top.</li>
    <li>Fill out the form, beginning with the date of the service.  When you
choose the date, the Service Planner will automatically check to see if it
matches with one of the days in the Church Year.  If it does, then <em>all</em>
matches will be placed automatically into the Liturgical Name field.</li>
    <ol>
    <li>Caution: If you press Enter or Return in a data field, your browser
will submit the form as it stands. If the service is incomplete, you can then
delete it and start over, or edit the incomplete service to finish it.</li>
    <li>Hymns from multiple locations may be associated with the same
service, since one service may be repeated at multiple places.</li>
    <li>Even if you generally plan services for only one location, it's a good
idea to have something in the location field, even if it's cryptic, like
GLC.</li>
    </ol>
    <li>If no "Liturgical Name" matches were found for the date, perhaps
because it's an occasional service, I will put the occasion into the Liturgical
Name field.  If multiple "Liturgical Name" matches were found, choose only one
and delete the rest.  Anything could be written in this field, but the Service
Planner uses it to find propers automatically for this service.  If the field
does not exactly contain a recognized liturgical name, then no propers will be
found.  (You can add new liturgical days or adjust their names on the
<a href="#church_year">Church Year</a> tab.)</li>
    <li>Each service can have its own notes, which may contain anything you
like.  This would be a good place to note deviations from the printed
liturgical rite, plan for extra things like choir pieces, or include the name
of the organist for that service.  If there are different organists for each
location, they should all be specified here.</li>
    <li>After you have set up a block plan that spans the date of this service,
that block plan will be available as a choice in the Block Plan drop-down
control.  Choosing one is optional, but they are useful for displaying propers
in the hymn listing that are associated with the block of services.</li>
    <li><a name="unknownhymns">If</a> you haven't saved many verified
hymn titles, you may want to check the "Attempt to provide unknown
titles..." box. The cross-reference tab contains a table of hymns,
including titles, which come from another source. When you enter a
hymn number, if this checkbox is activated, the Service Planner will
automatically pick one of the titles listed on that table for the chosen
hymn number, and include it in this form. There is no way to guarantee
that the title will be accurate, but they often are. You can fix it, if
necessary. On the other hand, the titles chosen when you submit this
form are reliable. If one of those has already been entered into the
system, it will be preferred over the cross-reference table's title
for the purpose of automatically filling out this form. If no title is
found, then a blank field will appear for you to enter the hymn title by
hand.</li>
    <li>Each hymn you enter can have a note associated with it. This is
where you record which verses/stanzas you want to sing. You could also
include other information, like whether it's a communion distribution
hymn. (A future version may add a separate field for the use/role of
each hymn, if it doesn't complicate the whole thing too much.)</li>
    <li>If you wish to avoid confusing your organists and others by including
an entry for <em>every</em> possible hymn location in your service, even when
you don't wish to sing a hymn at that point, then a good practice is to use
hymn number zero (0) for the liturgical hymn-points that you don't want to
use.  You can make the hymn title something like "(Info)" and include as a note
something like "Omit."  This is a suggested convention, so if you want to
do something else, go right ahead.  For your convenience, hymns numbered zero
will be listed without the hymn book and hymn number.</li>
    <li>If you're planning an extra long service with more than 8 hymns, then
you can click the "Add another hymn" link as many times as necessary to get all
of your hymns entered. (You can also add hymns later, once the service has been
saved.)</li>
    <li>When satisfied, click the "Send" button at the bottom of the form.  If
you click "Reset" instead, then the information in the form will return to its
original state.</li>
    </ol>

    <h3><a name="adding_hymns">Adding Hymns to a Service</a></h3>

    <p>Sometimes you want to add hymns, or create an additional service on the
same day as one already in the system.  If you click the Add button in the
heading line of a service on the Modify Services page, or if you create a new
service using the same date as a service already in the system, the resulting
form will automatically include a list of existing services on the right side,
with some abbreviated information about each one.  If you want to add another,
separate service on the same day, you can safely ignore that list of existing
services.  However, if you want to add hymns to one of the existing services,
then you can activate its checkbox.  The form will allow you to specify a
location (in case your new hymns are for the same service somewhere else), but
you won't be able to change the Liturgical Name, the Rite or Order, Service
Notes, or Block Plan.  That's because those things are already saved in the
service.  (If you want to change them, you can edit the service instead of
entering new hymns.  See below.)</p>

    <h3><a name="changing_service">Changing a Service</a></h3>

    <p>To edit an existing service, click the Edit button in the heading line
for the service you want to edit.  The resulting form will allow you to change
any of the service information, including hymns.  It will include <em>all</em>
hymns for that service, no matter what location is associated with them.  The
form will also allow you to change the sequence of the hymns by changing the
numbers in the Seq column.  The only significance of the numbers is that the
hymns will be listed sequentially, from the lowest to highest numbers.  It
doesn't really matter what numbers you use, where the range begins or ends, or
whether there are gaps in the range.  Finally, you can also delete hymns from
the service by activating their checkbox in the Del column.  Click the Commit
button to enact all of the changes you wish to make.</p>

    <h3><a name="last_use">When and where did I last use that hymn?</a></h3>

    <p>While you can add hymns to an existing service through the Edit button
<em>or</em> through the Add button, the "Add" page gives you an advantage you
may consider important.  When when you enter a hymn number to add, the system
will not only look up the title automatically, but it will also list the last
few dates when you have used that particular hymn, along with the location
where you used it each time.  This should help you to see when you are
choosing the same hymn more often than you would like, before you actually
save the new hymns into that service.</p>

    <h3><a name="backups">Data Storage and Backups</a></h3>

    <p>If you have misgivings about the reliablity of the system to store all
the work you have done, then now is the time to consider backing up your
service planner, and maybe even performing a restore to make sure it works.
The author trusts the database system, but backs up his own service planner
after every planning session.</p>

    <p>On the <a href="#housekeeping">Housekeeping</a> tab, scroll to
the Broom Closet heading, and click the first link under Backups, "Save
a Backup of the Database." The system will dump out the database to a
file and send it to your browser. Save it somewhere for safekeeping,
using the suggested filename.<p>

    <p>To restore from a backup file, use the very next item in the Broom
Closet.  Click the Browse button and select your backup file.  Then, click the
"Send" button.  When the file is uploaded, it will be used to restore the
entire database.  Just be aware that any changes you have made since the backup
will be lost.</p>

    <p>The backup file contains the date of the backup, but also the version of
the service planner.  While it's under development, the database schema is
subject to change.  The version contains three numbers separated by two periods.
Whenever the second number changes, the database schema has changed.  If you
try to use a backup file from a Service Planner version older than the one you
have installed, you're asking for trouble.  In fact, the system won't let you.
That's another reason to keep frequent backups.  While it's possible to revert
the installed Service Planner to an older version (and I can do that for you)
in order to restore an outdated backup file, nobody likes extra work.</p>

    <h1>Advanced Features</h1>

    <h2><a name="coinstallation">Populating Hymn Titles from a
Co-installation</a></h2>

    <p>The service planner automatically fills in hymn titles for you as you
enter the hymns for a service.  The reliable titles are the ones for hymns you
have already entered, because you have to verify the title yourself when you
enter them.  <a href="#unknownhymns">As mentioned above</a>, the system can
also provide titles from the Cross Ref table, but these are not guaranteed to
be correct.</p>

    <p>If your Service Planner uses a shared database with other service
planners (like the ones hosted at Ma-amad.com), then you can take advantage of
the work others have done in their service planners by merging their verified
hymn titles into your own Service Planner database.  The advantage is that you
can probably trust that they're correct, and it will save you from typing in
all those hymn titles.</p>

    <p>To do this, you will use the first item in the Broom Closet under
"Tweaking your Installation" on the Housekeeping page.  Each shared
installation uses a prefix to distinguish it from the other service
planners.  You probably chose your prefix when you first signed up and
had your Service Planner installed.  If you know the prefix of another
installation with which you share the database, then you can enter that
prefix in the entry box and click the "Import Titles" button.  It should
be as easy as that.</p>

    <h2><a name="mashups">Inserting a Service Listing into Another Web Page</a></h2>

    <p>This is called a "mashup," and there are instructions on the
Housekeeping tab.  While it's a little complicated, the author has done all the
hard work for you.  All you have to do is copy some bits of provided JavaScript
code into the web page where you want the service listing to appear.  The code
is provided on the Housekeeping tab.</p>

    <p>If your web page is on a different server (for example, if your Service
Planner is hosted at Ma-amad.com), then you will have to authorize the Service
Planner to accept requests from another site's web page.  To do that, simply
type the full address of your web page's server into the text box provided on
the Housekeeping tab and click the Save button.  You can do this for as many
servers as you want by including their addresses in the same box, one per line.
To revoke the authorization, simply remove the address from this box and click
"Save."</p>

    <h2><a name="restoring_defaults">Restoring Sanity to the Church Year (and other things)</a></h2>

    <p>If you have been experimenting with the data saved on the Church
Year tab, and you have broken something, it's possible to restore all of
those values to their defaults by clicking the first link on number 2
under "Tweaking Your Installation." <b>Please note that this will lose
any custom lectionaries or other changes you have made.</b></p>

    <p>The other link, which says "Church Year Functions," is probably
something you won't ever need, but I'll explain it anyway. The Service
Planner does some of its computing on the web server, some in your
browser, and some in the database itself. Clicking this link will remove
the Service Planner's computing instructions from the database and
restore them to their default. It's probably only needed by developers
when they are working on the database.</p>

    <p>For the curious, the next two items in the Broom Closet are for
situations when something has gotten out of whack. This doesn't usually
happen, and never in normal usage. But sometimes it can get tricky to
work out bugs in the database upgrade process, and these links allow a
graceful recovery from some of the problems that may arise.</p>

    <h2><a name="bug_reporting">Bug Reporting</a></h2>

    <p>When something doesn't work right, that's a bug. Bug reports are welcome
and appreciated. You can submit bugs by emailing the author using the link
provided at the bottom of the Housekeeping tab. Please be as descriptive as
possible, saying what you expected and how that's different from what actually
happened.  Also, please include the version number of your Service Planner,
which can be found at the bottom of the Housekeeping tab. If you're not sure
it's a bug, you can also send the same thing to
<a mailto="ma-amad@googlegroups.com">ma-amad@googlegroups.com</a>.</p>

    <p>If you'd like to help more directly with development, you don't have to
learn how to write code. Ma-amad has an issue tracker at <a href="https://www.ma-amad.com/issues/" title="Issue tracker">www.ma-amad.com/issues</a>.

    <p>Some "bugs" are not really problems, but feature requests. Those
are welcome too. With those too, please be as descriptive as possible
and include the version number of your Service Planner.</p>

    </div>
    </div>
</body>
</html>

