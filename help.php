<? /* Interface for modifying services from the listing
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
require("./init.php");
requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Help")?>
<body>
<?pageHeader();
  siteTabs("admin");?>
    <div id="content-container">
    <div class="maxcolumn centered">
    <h1>Help Contents</h1>

    <p>To return to the regular Housekeeping tab, just click on it.</p>
    <h2>Introductory Tour</h2>
    <ol>
    <li><a href="#anonymous_tabs">Anonymous Access</a> (Upcoming Hymns, Service Records, Cross Ref, and Report)</li>
    <li><a href="#report">Report</a></li>
    <li><a href="#modify-services">Modify Services</a></li>
    <li><a href="#block_plans">Block Plans</a></li>
    <li><a href="#sermon_plans">Sermon Plans</a></li>
    <li><a href="#church_year">Church Year</a></li>
    <li><a href="#housekeeping">Housekeeping</a></li>
    </ol>
    <h2>Guides and Tips</h2>
    <ul>
    <li><a href="#workflow_example">Workflow: Adding a Service</a></li>
    <li><a href="#adding_hymns">Adding Hymns to a Service</a></li>
    <li><a href="#changing_service">Changing a Service</a></li>
    <li><a href="#last_use">When did I last use that hymn?</a></li>
    <li><a href="#backups">Data Storage and Backups</a></li>
    </ul>
    <h2>Advanced Features</h2>
    <ul>
    <li><a href="#markdown">Markdown Formatting Markup</a></li>
    <li><a href="#style-adjuster">Style Adjuster</a></li>
    <li><a href="#coinstallation">Populating Hymn Titles from a Co-installation</a></li>
    <li><a href="#mashups">Inserting a Service Listing into Another Web Page</a></li>
    <li><a href="#restoring_defaults">Restoring Defaults</a></li>
    <li><a href="#bug_reporting">Bug Reporting</a></li>
    </ul>


    <h1><a name="tour">Introductory Tour</a></h1>

    <p>It will help to have a web browser window open to the service
planner beside this introduction, so you can try things yourself as you
read.</p>

    <p>The pages of the service planner are arranged in a series of tabs
accessible across the top of the window.  They are independent of one another,
and you need to save any changes there may be in one tab before switching to
another.  Anyone can use the service planner, but unless you log in, you will
only see a limited number of tabs.  Login links and related information are at
the very top of the page on the left side.  Privileged users will also see a
<a href="#user-administration">User Administration</a> link at the top when
they are logged in.  That's where you manage other users, if you need any.</p>

    <p>On the right of the very top is an <a href="#style-adjuster">Adjust
Styles</a> link, which opens a dialog box on the screen allowing you to make
some adjustments to the layout of the service listing.  Unless you are
dissatisfied with the listing at some point, just ignore that link.  If you
click it anyway, clicking it again will make the style adjuster dialog
disappear again.</p>

    <h2><a name="tabs">Tabs</a></h2>

    <h3><a name="anonymous_tabs">Anonymous Access</a></h3>

    <p>There are four tabs available to anonymous (not logged-in) users:
Upcoming Hymns, Service Records, Cross Ref, and Report.</p>

    <h4><a name="upcoming-hymns">Upcoming Hymns</a></h4>
    <p>This tab simply lists lists all services with their hymns from this day
forward, in chronological order.  It's a convenient place to answer the
question, "What's coming up?" Anyone in the church might be interested in this
page as a resource for the upcoming Sunday. Organists, choir directors, and
altar guilds can plan their preparations with it, and church attendees or
school teachers can use it to synchronize their daily devotions with the church
year, upcoming hymns, and the Sunday propers.  The very next service always
appears at the top of the list.</p>

    <p>Anonymous users (i.e. not signed in) will see two buttons for each
service on this page. The one labeled "Print" simply opens a page suitable for
printing the information on that service from the web browser. The one labeled
"CSV Data" is more powerful, but bears some explanation.  (If you are a simple
soul when it comes to technology, you may want to skip to the next paragraph
now.) CSV stands for "comma separated value," and represents a file format
usable on any computer system for representing data in a table (with columns and
rows, or in this case records and fields). A CSV file can be imported into any
spreadsheet, but in this case, it's provided as a way of automating
time-consuming tasks — especially bulletin creation. The Service Planner author
has written a script in the Python language that will use the file obtained from
this "CSV Data" link to <em>automatically</em> fill out a bulletin template with
hymn numbers, names, associated service occurrences, text of the propers, etc.
It even grabs the texts of the lections and inserts them.  Besides saving labor
and time, it ensures that these parts of the bulletin will be as accurate as the
data in the service planner with no extra effort. A large opportunity for human
error is removed.  Since the bulletin does have limited space, the filled
template always requires some tweaking, but most of the work is finished in an
instant. The author's bulletins are typeset using plain text files as input
(using <a href="http://latex-project.org">LaTeX</a>, which makes things like
this easy to do with a scripting language like Python.  What if you find
yourself locked into an application like MS Word? <a href="https://blogs.technet.microsoft.com/heyscriptingguy/2014/09/14/weekend-scripter-manipulating-word-and-excel-with-powershell/">You
can do something similar using Microsoft's Powershell.</a> LibreOffice has its
own scripting facility, but there are also libraries for languages like Python for
automating processes with the OpenDocument (LibreOffice) format.</p>

    <p>As with other views, the Upcoming Hymns tab is first loaded with
services contracted to save space and make it easier to find something in a
long list. See the section on <a href="#expand-contract">expanding and
contracting the service listing</a> for more information. When expanded, the
service listings show service information in <a href="#service-format">standard
format</a>.</p>

    <p>Users who are logged in can set informational <a href="#flags">flags</a>
on the services from here. The service listing can be
<a href="#flag-filters">filtered</a> by anyone on the basis of text found in
those flags.</p>

    <h4><a name="service-records">Service Records</a></h4>
    <p>The Service Records tab is similar to the <a
href="#upcoming-hymns">Upcoming Hymns</a> tab, but the listing is configurable,
and can show <em>all</em> services that have been planned.  By default, it has
the latest (farthest in the future) at the top of the list.</p>

    <p><a name="range-config"></a>Above the listing are a checkbox, two date
entry boxes, and an Apply button. The From and To date boxes allow a user to
limit the range of time for which planned services are listed. When checked,
the checkbox alters the limits, so that all services after the "From" date will be
listed, and the "To" date is ignored. The new settings take effect with the Apply
button.</p>

    <p><a name="sorting-config"></a>Sandwiched between the date range boxes and
the services themselves are two buttons that can set the chronological sort
order of the services listed. The disabled button indicates the order in use.
The default order when the page loads is reverse chronological order, so that
the "most future" service is listed at the top. This is the opposite of the <a
href="#upcoming-hymns">Upcoming Hymns</a> tab. Chronological order is like the
Upcoming Hymns tab, except the listing can begin at any date set in the From
box, and can be limited to services falling before the one in the To box.</a>

    <p><a name="jump-this-week"></a>For convenience, there is a
<a name="thisweek">Jump to This Week</a> link-button at the top right of the page,
which will scroll the page to contemporary planned services.  (That's different
from planned contemporary services, which the service planner does not
support.)</p>

    <h4><a name="cross-reference">Cross Ref</a></h4>
    <p>The Cross Ref tab displays a cross-reference table of hymn numbers from
various books.  Clicking on a blue heading causes the page to reload, sorted on
that column. This listing is based on a database table, but it's purely for
reference, and there is no configuration beyond sorting. It may be helpful to
load it in a separate browser window or tab when planning a service.<p>

    <h4>Report</h4>
    <p>The Report tab shows a simple listing of services and hymns that may be
customized by privileged users. It's available for those who need to see or
print a simpler sheet of information, or one showing custom fields.</p>

    <h3><a name="user_tabs">Privileged User Tabs</a></h3>

    <h4><a name="report">Report</h4>

    <p>While the Report tab is available to anonymous users, it must be
configured by a privileged user before it will show anything.
Privileged users can configure the display using the blue form that
appears on that page.</p>

    <p>Instead of a time span, this tab will list only up to a certain
number of hymns. The Limit setting controls this, but when set to zero, the
limit is disabled.</p>

    <p>Checking the Future box configures the page to show Future hymns,
in chronological order, instead of all planned hymns in
reverse-chronological order.</p>

    <p>The Start HTML and End HTML text boxes allow privileged
users to modify the way the list is displayed. If you would
like a certain kind of heading, for example, you could put
<pre><?=htmlspecialchars("<h1>Hymns at St. Peter Lutheran Church</h1>")?></pre>
at the beginning of the Start HTML field. You may wish to consult a reference
on simple HTML markup, but be cautious about pasting bits of code in there from
the Internet, unless you know what you are doing. Some code can be malicious.
Admin users are assumed to be cautious and wise enough not to foolishly include
code they don't understand. At a minimum, the Start HTML box should include the text
<code><?=htmlspecialchars("<table>")?></code> and the End HTML box should include the text
<code><?=htmlspecialchars("</table>")?></code>.</p>

    <p>A line showing active fields appears below the configuration form. Each
field is configured with a name and a width for its column in the table. The red
characters are links for moving fields relative to each other (&lt; and &gt;),
for deleting fields (-), and for adding new fields (+).  The fields configured
there will appear in the Report tab listing.  You will need at least one
service planned before you can add fields to the Report tab.</p>

    <p>When adding a field, a drop-down list shows possible fields
that can be chosen. Many of them are straight from the database, and their names
may seem a little cryptic. It doesn't hurt to try one, and see what kind of
data it contains.</p>

    <p>To configure this listing effectively, it's important to realize that
every row in the table represents a large array of data from the database. Each
row contains data from only one hymn. In order to make the listing appear more
friendly, some of the fields available are <em>not</em> strictly from the
database, but are list values aggregated from the service associated with that
line.  This allows the table to show something like the date, location, or day
name only once, alongside a listing of <em>all</em> hymn numbers, hymn names,
locations, or flags in that service. The aggregated field values are toward the
bottom of the drop-down list.</p>

    <p>Once a field has been added, it can only be moved or deleted, but not
modified.</p>

    <h4><a name="modify-services">Modify Services</a></h4>

    <p>When you log in, the Service Records tab is replaced with the
much more powerful Modify Services tab. This is where you add new
services or change ones you've already planned.</p>

    <h5><a name="service-format">Service Format and Occurrences</h5>

    <p>The Upcoming Hymns, Service Records, and Modify Services tabs share the
same basic format for displaying each service. There are really two formats
available. The one used on all these pages can be selected in the Housekeeping
tab, using the <a href="admin.php#combine-occurrences">Combine Occurrences
checkbox</a>.</p>

    <p><a name="two-occurrence-formats"></a>The hymns entered in the service
planner are associated with a service <em>and</em> an occurrence of that
service. That way the same basic service can be planned and reused in different
times or places, each with its own set of hymns. The two formats available for
service listings treat these occurrences in two different ways.</p>

    <ol><li>The original way lists each occurrence as a separate service. The
advantage of this is that someone interested in only that occurrence will not
be bothered by other occurrences.</li>
    <li>The combined way lists all occurrences under one service heading and
with one set of propers and notes. Hymns for all occurrences are grouped
together, and the occurrence name appears with each hymn. Each occurrence gets
its own row of <a href="#flags">flags</a>, and the button for modifying flags
appears to its right, instead of in the heading with other buttons.</li>
    </ol>

    <p>Each service is listed under its own heading line containing the date
and occurrence(s) of the service and liturgical day name. Check boxes in that
line allow the user to delete the selected service occurrence(s) using the
Delete button above or below the listing. If any informational
<a href="#flags">flags</a> are set
for this service, they will appear beneath the heading line. When a service
listing is contracted, only these parts will be visible. When it is expanded,
the following sections also appear, as long as they are not suppressed by the
<a href="#style-adjuster">Style Adjuster</a>.</p>

    <p>Beneath the <a href="#flags">flags</a> and heading line, a gray section
with general propers for the day appears.  The <em>Evangelical Lutheran Hymnary
(ELH)</em>'s topical description for hymns on that day, as well as the
liturgical color for paraments is at the top. Below them are the gradual and
introit. Most of this information comes from the <em>Hymnary</em>, but a weekly
responsive gradual is also available when using a <a href="#block_plan">block
plan</a>.</p>

    <p>Some services may be assigned to <a href="#block_plan">named blocks</a>
for planning purposes. They will also contain a light green rectangle with a
heading inside that says "Block: " and the name of the block to which the
service belongs. The block specifies which propers will be used for that
service, and they are automatically looked up and displayed within the block
rectangle. This can include two lessons, the Gospel, the Psalm, the sermon
text, and the text of the Collect. Depending on the block plan, some of these
may not contain information. The biblical text references will be links to
those texts on Bible Gateway, using the Bible version(s) configured
<a href="admin.php#biblegateway-abbreviation">on the Housekeeping page.</a>.
If a pastor chooses a sermon text other than what may be provided in the block
plan, he can indicate the text by clicking the
<a href="#service-buttons">Sermon button</a> for this service and entering the
text. It will be displayed in the Block section of the service listing, with an
asterisk on the "Sermon" label.</p>

    <p>After the general propers and block sections, a white section shows any
general notes that the planner has written for the service. These can be short
or lengthy, and may include any kind of text, including links.</p>

    <p>The actual hymns are listed line-by-line, with the abbreviation for the
hymnbook, the number, any notes for that hymn (verse/stanza numbers, etc.), and
the title. If <a href="#two-occurrence-formats">occurrences are combined</a>,
the occurrence for each hymn is listed in the rightmost column.</p>

    <h5><a name="#service-buttons">Buttons For Each Service</a></h5>

    <p>On the Modify Service page, each heading line also contains
buttons for adding more hymns to a service (in any
occurrence), editing the service as it is currently entered, adding a sermon,
or printing just that one service with its hymns (handy for organists or social
media postings). A checkbox appears to the left of the date in each service
heading line, for the purpose of deleting whole services, with all their hymn
selections. The checkboxes are used with the Delete button above or below the
service listing.</p>

    <h5><a name="flags">Informational Service Flags</a></h5>

    <p>Flags provide a convenient way to add small pieces of information about
a service that can be used to distinguish that service from others. They
include three pieces of information: a name, a value, and the user who created
the flag. A flag is attached to <em>an occurrence of a service</em> rather than
a service without an occurrence. That means a service must have at least one
hymn chosen in order to have flags added to it, since the hymns of a service
establish its occurrences. (The hymn may be hymn 0, which is used for information
and place-holding.)</p>

    <p>Any signed-in user can create a flag, but only administrative users can
create flags with arbitrary names and values. Other users can add flags named
from a list configurable <a href="admin#addable-flags">on the Housekeeping
page</a>. Administrative users can delete any flag by clicking the boxed X that
appears in its top right corner in any service listing.</p>

    <h5><a name="expand-contract">Expanding and Contracting Service Listings</a></h5>

    <p>When a service listing is first loaded, each service is contracted to
show only the heading line and flags line. A red + appears in a box on the left
side of the heading line to show that the service is contracted. Clicking that
will expand the listing and change the + to a -. Clicking the box again will
contract the listing and hide the extra information.</p>

    <h5><a name="flag-filters">Filtering a Service Listing on Flags</a></h5>

    <p>A large service listing can be a lot to pore through when looking for
one or more particular services. When flags are used consistently to provide
the relevant information about those services, a long service listing may be
easily reduced to exactly the services of interest, using the "Filter by flag
text" box at the top of the listing page. Enter the exact text to search for,
and either press Enter or click the Set Flag Filter button. If the "Expand"
check box is checked when you press Enter or click, the filtered services will
be automatically expanded to show all of their information. When a filter has
been set, the Set Flag Filter button becomes a Remove Flag Filter button, so
that you can see the unfiltered service listing. Flag filters are not saved or
remembered in any way when a page is reloaded.</p>


    <h4><a name="block_plans">Block Plans</a></h4>

    <p>The Block Plans tab allows you to create a block of services planned
together, falling within a particular span of time.  The timespan of multiple
blocks may overlap, so that you might have one block for Lent Sundays and
another for Lent midweek services, and perhaps another for nonspecific services
throughout the year.  Each service can be associated with one block, or with no
block at all. The association with a block is made via a drop-down list of
available blocks on the <a href="#entry-page">entry page</a> and the service <a
href="#edit-page">edit page</a>.</p>

    <p>Each block specifies a set of propers to be used in its services.  The
default propers available are those found in the <em>Evangelical Lutheran
Hymnary (ELH)</em> lectionaries.  (Select "historic", "ilcwa", "ilcwb", or
"ilcwc" from the Lectionary column.)  When using the historic lectionary, the
series may also be selected. Note that these were not published with the intent
that they be alternative lections, but alternative sermon texts. However, they
may be set as alternative lections for a block of services. If the "custom"
lectionary is chosen, a description of the lectionary should be entered in the
Custom Readings field. A lectionary may specify a psalm and one or more
collects for a particular day. The two sets of collects in the <em>Hymnary</em>
are included under the labels "historic" and "Dietrich".  The sermon text may
be specified as the Old Testament, Epistle, or Gospel lesson from the
lectionary (and series) of your choice. (This can be overridden by specifying a
sermon text in a <a href="#sermon_plan">sermon plan</a> for that service.) The
service planner may be supplemented with additional lectionaries by entering
individual sets of readings for the days on the <a href="#church-year">Church
Year</a> tab or by uploading a complete set on the <a
href="#housekeeping">Housekeeping</a> tab.</p>

<p>The <em>Hymnary</em> includes seasonal graduals, but one of the editors has
since developed a set of weekly responsive graduals as a supplement. A check
box on the block plan page allows for the display of the latter, rather than
the former gradual.</p>

<p>A set of notes may also be included in each block, which will be displayed
alongside the propers in the service listing.</p>

    <p>If a new user doesn't see an immediate need or use for service blocks,
they may be considered optional. Just ignore this tab, in that case.  You
can always add blocks later.</p>

    <h4><a name="sermon_plans">Sermon Plans</a></h4>

    <p>The Sermon Plans tab is where you will find plans for sermons,
once you have created them. The Service Planner is organized around
the whole service, so in order to create a sermon plan, you will
need to have a service already in the system for that particular
day. It need not have any hymns, but it must exist. Then, on the <a
href="#modify-services">Modify Services</a> listing, you can add a sermon by
clicking the Sermon link in the heading for that service.</p>

    <p>If a manuscript file has been uploaded for a sermon plan, a link with
the letters "mss" appears in the listing of sermon plans right next to the
text.  Clicking that link will download the saved file. The set of uploaded
files are not saved in the database. If work will be done on the server
installation, these should be backed up separately in case they are
accidentally deleted. If an adminstrative user wishes to keep his own archive
of these uploaded files, it may be downloaded in the <a
href="admin.php#uploaded-files">Uploaded Files</a> section of the Housekeeping
tab.</p>

    <p>The Edit page for a serman plan shows two button links at the very top
of the page, one to show a printable report, and one to browse all sermon
plans. The Printable Report contains all the fields for this sermon plan, but
does not include the service listing or the manuscript. The Outline field is
shown in a way that retains the spacing in the way it was entered, and the
Notes field is formatting using <a href="#markdown">Markdown</a>.

    <p>When a sermon text is entered in a sermon plan, it will override the
sermon text listed in the <a href="#block_plans">block plan</a> for that
service (if one has been chosen).  When this happens, an asterisk will appear
with the Sermon label.</p>

    <p>When a sermon plan is selected, the related service is displayed at the
bottom of the page for reference. (The control for <a
href="#expand-contract">contracting</a> it is disabled.)</p>

    <h4><a name="church_year">Church Year</a></h4>

    <p>The Church Year tab is magical (figuratively).  This is where
you can alter the Service Planner's knowledge of the church year and the
propers assigned to each day.  I wouldn't advise changing anything here until
you're sure you understand what you want to do and how to do it.  Not that you
can permanently break anything, but the system is sophisticated.  If you do
manage to break something and want to restore it to its original form, that's
always possible <a href="admin.php#restore-church-year">on the Housekeeping
tab</a>.</p>

    <p>The second column on the Church Year tab tells when the next occurrence
falls for each of the computed days in the Church Year.   So if you want to
know the date of Lent 2 next time around, just find that row on the Church Year
tab.</p>

    <p>One of the interesting possibilities here is that you can add new sets
of propers that will become available to your block plans. Click the name of a
day to see the propers that apply to that day. Choose the New Propers tab, and
specify the information for this day in your new lectionary. When you do this,
it's important to be perfectly consistent in your name for the lectionary. Any
time you like, the new lectionary <a href="admin.php#lectionary-export">may be
downloaded</a> as a CSV (comma-separated value) file, which is editable in a
spreadsheet and can be uploaded to the service planner again.</p>

    <p>The dialog box for editing propers also allows the editing or creation
of associated collects. These are collected under names like "historic" or
"Dietrich". Each collect may be linked to more than one set of propers in a
lectionary. This makes the series of collects available in <a
href="#block_plans">block plans</a>.</p>

    <p>The left-most tab when editing propers contains basic propers for the
day, which don't depend on a lectionary. These include the liturgical color,
theme, a note, the Introit, and the weekly gradual.</p>


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

    <p>Another immediately noteworthy item, if you use the block planning
feature, is farther down, under "Config Settings." The setting entitled
"Preferred Bible Abbreviation from Bible Gateway" is where you set your
preference for the Bible version in which to read the lections. If you save an
abbreviation like "NKJV" there, then your service listings will not only
include the lection references in their block plan rectangles, but those
references will be links to the texts via BibleGateway.com. If you don't save
an abbreviation here, then the lection references will not be links.<p>

    <h2>Usage Tips</h2>

    <h3><a name="workflow_example">Workflow: Adding a Service</a></h3>

    <p>Here's an outline of how the author uses the service planner.  You may
prefer another workflow, but if you want somewhere to start, look through the
following.  If you want to use a block plan, which automatically displays the
block's set of propers for each service, then you may want to set that up first
on the Block Plans tab. You can always go back later and associate a service
with a block plan, once it has been created.</p>

    <ol class="level1">
    <li>Log in and click the Modify Services tab.</li>
    <li>Click the New Service button at the top.</li>
    <li>Fill out the form, beginning with the date of the service.  When you
choose the date, the Service Planner will automatically check to see if it
matches with any days in the Church Year.  If it does, then <em>all</em>
matches will be placed automatically into the Liturgical Name field so that you
can more easily choose the one you want and delete the rest.</li>
    <ol class="level2">
    <li>Caution: If you press Enter or Return in a data field, your browser
will submit the form as it stands. If the service is incomplete, you can then
delete it and start over, or edit the incomplete service to finish it.</li>
    <li>Hymns from multiple occurrences may be associated with the same
service, since one service may be repeated at multiple places or times. The New
Service form allows you to enter one occurrence at a time.</li>
    <li>Even if you generally plan services for only one occurrence, it's a
good idea to have something in the occurrence field. (e.g. GLC, PLC, Concordia,
etc.) This can be filled automatically for you, if you save a default value
<a href="admin.php#default-occurrence">on the Housekeeping page</a>.</li>
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
liturgical rite, plan for extra things like choir pieces, or save information
for later use in that service.</li>
    <li>After you have set up a block plan that spans the date of this service,
that block plan will be available as a choice in the Block Plan drop-down
control.  Choosing one is optional, but they are useful for displaying propers
in the hymn listing that are associated with the block of services. A block
also has its own, separate, collection of notes.</li>
    <li><a name="unknownhymns">If</a> you haven't saved many verified
hymn titles, you may want to check the "Attempt to provide unknown
titles..." box. The cross-reference tab contains a table of hymns,
including titles, which come from another source. When you enter a
hymn number, if this checkbox is activated, the Service Planner will
automatically pick one of the titles listed on that table for the chosen
hymn number, and include it in this form. There is no way to guarantee that the
title will be accurate, but they often are. You can fix it, if necessary. On
the other hand, the titles chosen when you submit this form are considered to
be reliable. If one of those has already been entered into the system, it will
be preferred over the cross-reference table's title for the purpose of
automatically filling out this form. If no title is found, then a blank field
will appear for you to enter the hymn title by hand.</li>
    <li>Each hymn you enter can have a note associated with it. This is
where you record which verses/stanzas you want to sing. You could also
include other information, like whether it's a communion distribution
hymn. (A future version may add a separate field for the use/role of
each hymn, if it doesn't complicate the whole thing too much.)</li>
    <li>If you wish to avoid confusing your organists and others by including
an entry for <em>every</em> possible hymn occurrence in your service, even when
you don't wish to sing a hymn at that point, then a good practice is to use
hymn number zero (0) for the liturgical hymn-points that you don't want to
use.  You can make the hymn title something like "(Info)" and include as a note
something like "Omit."  This is a suggested convention, so if you want to
do something else, go right ahead.  For your convenience, hymns numbered zero
will be listed without the hymn book and hymn number.</li>
    <li>If you're planning a service with more than 8 hymns, then you can click
the "Add another hymn" link as many times as necessary to get all of your hymns
entered. You can also add hymns later, once the service has been saved.</li>
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
then you must activate its checkbox.  The form will allow you to specify a
service occurrence (in case your new hymns are for the same service somewhere
else, or at a different time), but you won't be able to change the Liturgical
Name, the Rite or Order, Service Notes, or Block Plan.  That's because those
things are already saved in the service.  (If you want to change them, you can
edit the service instead of entering new hymns.  See below.)</p>

    <h3><a name="changing_service">Changing a Service</a></h3>

    <p>To edit an existing service, click the Edit button in the heading line
for the service you want to edit.  The resulting form will allow you to change
any of the service information, including hymns.  It will include <em>all</em>
hymns for that service, no matter what occurrence is associated with them.  The
form will also allow you to change the sequence of the hymns by changing the
numbers in the Seq column.  The leftmost column in the hymn table contains a
double-ended vertical arrow in each row.  This is a handle you can use to drag
that row up or down in the list. When you drop it between two other rows, it
will remain, and the sequence numbers will be automatically updated. The only
significance of the sequence numbers is that they allow hymns to be listed
sequentially, from the lowest to highest numbers.  It doesn't matter what
numbers you use, where the range begins or ends, or whether there are gaps in
the range.  Finally, you can also delete hymns from the service by activating
their checkbox in the Del column.  Click the Commit button to enact all of the
changes you wish to make.</p>

    <h3><a name="last_use">When and where did I last use that hymn?</a></h3>

    <p>When when you enter a hymn number to add, the system will not only look
up the title automatically, but it will also list the last few dates when you
have used that particular hymn, along with the occurrence where you used it
each time.  This should help you to see when you are choosing the same hymn
more often than you would like, before you actually save the new hymns into
that service.</p>

    <h3><a name="backups">Data Storage and Backups</a></h3>

    <p>If you have misgivings about the reliablity of the system to store all
the work you have done, then now is the time to consider backing up your
service planner, and maybe even performing a restore to make sure it works.
The author trusts the database system, but backs up his own service planner
after every planning session. (more or less)</p>

    <p>On the <a href="#housekeeping">Housekeeping</a> tab, scroll to
the Broom Closet heading, and click the first link under Backups, "Save
a Backup of the Database." The system will dump out the database to a
file and send it to your browser. Save it somewhere for safekeeping,
using the suggested filename.<p>

    <p>To restore from a backup file, use item 3 in the same Broom Closet
listing, under "Backups &gt; Database".  Click the Browse button and select
your backup file.  Then, click the "Send" button.  When the file is uploaded,
it will be used to restore the entire database.  Just be aware that any changes
you have made since the backup will be lost.</p>

    <p>The backup file contains the date of the backup, but also the version of
the service planner.  The version contains three numbers separated by two periods.
Whenever the second number changes, the database schema has changed.  If you
try to use a backup file from a Service Planner version older than the one you
have installed, you're asking for trouble.  In fact, the system won't let you.
That's another reason to keep frequent backups.  While it's possible to revert
the installed Service Planner to an older version (and I can do that for you)
in order to restore an outdated backup file, nobody likes extra work.</p>

    <p>Since you might set up your Church Year in a different way from the
default setup, you can also create a backup containing only those changes. The
main difference from ordinary database backups is that when you restore this
one, it won't overwrite all your other data. (But you <em>have</em> backed it
all up anyway, right?)</p>

    <p>In addition to backing up the database, you can also back up the
collection of files you have uploaded to your service planner. The controls for
this are at Housekeeping &gt; Backups &gt; Uploaded Files. The file you will
download is an archive of the upload directory in your service planner
installation. All your uploaded files are there, though they will not have
names that make sense to you. The service planner keeps track of them using
data in the database. Because of this, the best policy is to keep a local
archive of your files <em>and</em> a database backup.</p>

    <h1>Advanced Features</h1>

    <h2><a name="markdown">Markdown Formatting Markup</a></h2>
    <p>Several data fields on various pages support <a
href="https://michelf.ca/projects/php-markdown/extra/">Markdown</a> markup.
Markdown is a simple, quick, but powerful way to include all of the basic
formatting provided in a web page, without having to learn how to code in HTML.
The markup itself is designed to make sense to humans when read. For example,
<code>writing *emphasized* text this way</code> will be processed into
something that looks like "writing <em>emphasized</em> text this way". Most
fields containing "notes" in the service planner support Markdown.</p>

    <h2><a name="style-adjuster">Style Adjuster</a></h2>
    <p>Clicking the Adjust Styles link in the top right corner opens (and
closes) the Style Adjuster dialog box. This allows you to make the following
changes to the way pages are presented. The changes are saved in your local
browser either until they are cleared from memory or you click the button
labeled Reset to Default.</p>

    <dl>
    <dt>Base font size (pixels)</dt><dd>The base font size affects all text on
the page. The default value depends on the platform and screen you're using,
but you can adjust the value here to tweak <em>everything</em> at once.</dt>
    <dt>Hymn font size (%)</dt><dd>The hymn listing text can be resized as a
percentage of the base font size. You could use this to make the hymn listing
text smaller (numbers less than 100) or larger (numbers greater than 100) than
surrounding text.</dd>
    <dt>Note font size (%)</dt><dd>The font size of service notes can be
adjusted in the same way, as a percentage of the base font size.</dd>
    <dt>Show block info?</dt><dd>This hides or shows the section of services
relating to their planning block. This only applies to service that are
connected to a block plan.</dd>
    <dt>Show propers?</dt><dd>This hides or shows the section of services
containing the propers for the day. Organists, for example, may care to see
only the hymns.</dd>
    <dt>Reset to Default</dt><dd>This button is the easiest way to remove any
custom style adjustments.</dd>
    </dl>

    <h2><a name="user-administration">User Administration</a></h2>

    <p>This link at the top of the page opens a different page for user
administration. It's mostly self-explanatory, but the User Level setting may
bear some explanation. The system requires at least one Admin user to exist.
Basic users can create flags on services from a list configurable on the
Housekeeping tab. Advanced users have no additional privileges at this time.</p>

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
planners.  Your prefix was assigned when you first signed up and
had your Service Planner installed.  If you know the prefix of another
installation with which you share the database, then you can enter that
prefix in the entry box and click the "Import Titles" button.  It should
be as easy as that.</p>

    <h2><a name="mashups">Inserting a Service Listing into Another Web Page</a></h2>

    <p>This is called a "mashup," and there are instructions on the
Housekeeping tab under "Tips for Web Site Integration." If you don't see them,
click the [+] under that heading to expand them.  While it's a little
complicated, the author has done all the hard work for you.  All you have to do
is copy some bits of provided JavaScript code into the web page where you want
the service listing to appear.  The code is provided on the Housekeeping
tab.</p>

    <p>If your web page is on a different server (for example, if your Service
Planner is hosted at Ma-amad.com), then you will have to authorize the Service
Planner to accept requests from another site's web page.  To do that, simply
type the full address of your web page's server into the text box provided on
the Housekeeping tab and click the Save button.  You can do this for as many
servers as you want by including their addresses in the same box, one per line.
To revoke the authorization, simply remove the address from this box and click
"Save."</p>

    <h2><a name="restoring_defaults">Restoring Sanity to the Church Year (and other things)</a></h2>

    <p>If you have been experimenting with the data saved on the Church Year
tab, and you have broken something, it's possible to restore all of those
values to their defaults by clicking the first link on number 2 under <a
href="admin.php#tweaking">Tweaking Your Installation</a>. <b>Please note that
this will lose any custom lectionaries or other changes you have made.</b></p>

    <p>The other link beside it, entitled "Church Year Functions," is probably
something you won't ever need, but for completeness, here's an explanation. The
Service Planner does some of its computing on the web server, some in your
browser, and some in the database itself. Clicking this link will remove the
Service Planner's computing instructions from the database and restore them to
their default.  It's probably only needed by developers when they are working
on the database.</p>

    <p>For the curious, items 3 and 4 ("Manually repopulate..." and
"Manually recreate...") in the Broom Closet are for situations when something
has gotten out of whack. This doesn't usually happen, and never in normal
usage. But sometimes it can get tricky to work out bugs in the database upgrade
process, and these links allow a graceful recovery from some of the problems
that may arise.</p>

    <p>Number 5 is self-explanatory, and number 6 can be important if you want
help from me.</p>

    <h2>Configuration Options</h2>

    <p>If you are unsatisfied with the default behavior of the service planner,
there are many ways to alter it on the Housekeeping page. Once you have had a
chance to use it for a little while and get accustomed to the way it works, it
would be worth your time to peruse the <a
href="admin.php#config-settings">Config Settings</a> section, toward the bottom
of the page. All of the settings are documented there. If you have trouble
understanding any of them or how to use them, please ask on the mailing list
identified below.</p>

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
learn how to write code. Ma-amad has an issue tracker at <a
href="https://www.ma-amad.com/issues/" title="Issue
tracker">www.ma-amad.com/issues</a>.

    <p>Some "bugs" are not really problems, but feature requests. Those
are welcome too. With those too, please be as descriptive as possible
and include the version number of your Service Planner.</p>

    </div>
    </div>
</body>
</html>

