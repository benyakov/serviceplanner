<? /* Administrative interface
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
    header('Location: index.php');
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Housekeeping")?>
<body>
<script type="text/javascript">
    auth = "<?=authId()?>";
    function saveCorsfile() {
        $.post("utility/savecorsfile.php", {
            ajax: "ajax",
            contents: $("#contents").val() },
            function(result) {
                $("#contents").val(result);
            })
    }
    $(document).ready(function() {
        $("#corsform").submit(function(evt) {
            saveCorsfile();
            evt.preventDefault();
        });
        $("#purge-churchyear").click(function(evt) {
            evt.preventDefault();
            if (confirm("This will lose any changes to"+
                " the days in the church year table.")) {
                window.location.assign("churchyear.php?request=purgetables");
            }
        });
    });
</script>
    <?pageHeader();
    siteTabs($auth); ?>
    <div id="content-container">
    <h1>Housekeeping</h1>
    <p>This page contains the links for backing up the database and restoring
    it.  It is recommended that you back up
    often.  You get to decide what that means.  It would also be a good idea to
    practice restoring at least once, to make sure it works.  (If it doesn't,
    your database may lose data.)</p>

    <p>At the bottom of this page is a way to import hymn titles from
    other installations of this web application living in the same
    database.  All you need to know is the database prefix used in the
    installation from which you'd like to import the hymn titles.</p>

    <h2>Making data available elsewhere on your web site</h2>

    <p>To make the information available to others in the public, it is
    recommended that you make links to the files (linked here)
    <a href="index.php">index.php</a> and
    <a href="servicerecords.php">servicerecords.php</a>.</p>

    <p>The first file linked above will show a page without the navigation tabs
    or the ability to change things, <em>but only</em> if it is accessed from
    <em>outside</em> the installation of this application on the web server.
    That can be accomplished on a Unix system by creating a symbolic link to
    it, for example, from the parent directory/folder of the installation like
    this:
    "<tt>ln -s services/index.php hymns.php</tt>".  Then the web address
    <em>to the new symbolic link</em> can be given to others,
    e.g. organists.</p>

    <p>The second file linked above can be shared directly as a web page
    link, or via a symbolic link created in the manner described above.
    However, providing it through another place on your web server might
    create an additional layer of security for your data, in case you are
    concerned about such things.</p>

    <h2>Logins allow the whole thing to be publicly available</h2>

    <p>Since pages that modify the database are now login-restricted,
    it's also possible to allow the whole world to see the whole
    installation with less risk that anyone would mess with your data.  You
    may have to explain to your organist or secretary why they don't need to
    log in.</p>

    <h2>Mashing up pages from here into your own web site.</h2>

    <p>One other possibility for those with their own web site elsewhere is to
    include one of the above pages in your own page via a Javascript mash-up.
    Simply insert the following code at the appropriate places in your web
    page:</p>

    <dl>
        <dt>In the page header (if it's not already there), insert:</dt>
        <dd class="honorspaces">
&lt;script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"&gt;&lt;/script&gt;
        </dd>
        <dt>Where you want the listing of all records to appear in the page body, insert:</dt>
        <dd class="honorspaces">
&lt;div id="services-import"&gt;&lt;/div&gt;
&lt;script type="text/javascript"&gt;
$(document).ready(function(){var a="http://www.bethanythedalles.org/services-devel/servicerecords.php";$("#services-import").load(a+" #content-container",function(){if(!$("#services-import").has("#content-container").length){$.ajax(a,{dataType:"jsonp",jsonp:"jsonpreq",success:function(a,b){$("#services-import").html(a)}})}})})
&lt;/script&gt;
        </dd>
        <dt>Where you want the listing of <em>future</em> hymns to appear in the page body, insert:</dt>
        <dd class="honorspaces">
&lt;div id="services-import"&gt;&lt;/div&gt;
&lt;script type="text/javascript"&gt;
$(document).ready(function(){var a="http://www.bethanythedalles.org/services-devel/index.php";$("#services-import").load(a+" #content-container",function(){if(!$("#services-import").has("#content-container").length){$.ajax(a,{dataType:"jsonp",jsonp:"jsonpreq",success:function(a,b){$("#services-import").html(a)}})}})})
&lt;/script&gt;
        </dd>
        <dt>Finally, save your server's domain name here
        (or multiple servers' domain names, one per line),
        like "http://www.mydomain.com":<dt>
        <dd>
<?
    if (file_exists("corsfile.txt")) {
        $corsfilecontents = str_replace(
            array('<', '>'),
            array('&lt;', '&gt;'),
            file_get_contents("corsfile.txt"));
    } else {
        $corsfilecontents = "";
    }
?>
        <form id="corsform" action="utility/savecorsfile.php" method="post">
        <textarea name="contents" id="contents"
            required><?=$corsfilecontents?></textarea>
        <button type="submit" name="submit">Save</button>
        </form>
        </dd>
    </dl>

    <p>Note that you can apply your own css stylesheet to the resulting
    imported information to make it look nicer in the context of your web
    site.</p>

    <h2>The Broom Closet</h2>

    <ul>
    <li><a href="dump.php">Save a Backup of the Database</a>
        When restoring, be sure to restore into a Services installation
        with the same version listed below.</li>
    <li><form id="restore-backup" action="restore.php" method="post"
        enctype="multipart/form-data">
        <label for="backup_file">Upload a backup (dump) file to restore.</label>
        <input id="backup_file" type="file" name="backup_file"
            required placeholder="Select local file">
        <button type="submit">Send</button>
        <button type="reset">Reset</button>
    </form>  (Caution: This will replace all current data, and things could go wrong.  Test before relying upon it!)</li>
    <li><a href="dump.php?only=churchyear">Save a Backup of Your Church Year Modifications</a></li>  See the note above about restoring to the same version.  Use the field above to install a backup of your church year data.</li>
    <li><form id="export-lectionary" action="export.php" method="get">
    <label for="lectionary">Export single lectionary as CSV.</label>
    <input type="text" name="lectionary" id="lectionary" required
        placeholder="Lectionary name">
    <button type="submit" id="submit">Export Lectionary</button>
    </form></li>
    <li><form id="import-hymns" action="importhymns.php" method="post">
    <label for="prefix">Merge hymn titles from a co-installation.</label>
    <input type="text" name="prefix" pattern="[\w\d]+" id="prefix"
        required placeholder="Database Prefix of Source Installation">
    <button type="submit" id="submit">Import Titles</button>
    </form></li>
    <li>Restore church year to default:
    <a href="" id="purge-churchyear"
        title="Purge Church Year Tables">Days in Church Year</a> or
    <a href="churchyear.php?request=dropfunctions"
        title="Drop Church Year Functions">Church Year Functions</a></li>
    <li>Manually repopulate empty
        <a href="admin.php?flag=fill-churchyear">church year data</a> or missing
        <a href="admin.php?flag=create-churchyear-functions">church year functions</a>.
        This should happen automatically most of the time, and should not be
        tried unless needed.</li>
    <li>Manually re-create <a href="admin.php?flag=create-views">synonym coordination views</a> in the database.  This should also happen automatically when needed.</li>
    <li><a href="hymnindex.php?drop=yes">Drop and re-create hymn cross-reference table</a>.  This is needed when the table has been changed in a new version.</li>
    <li><form id="authcookie-age" action="utility/authcookieage.php" method="post">
    <label for="cookie-age">The maximum number of days you
      wish the Service Planner to remember your login session.</label>
    <input type="number" name="cookie-age" value="<?=getAuthCookieMaxAge()/(60*60*24)?>" size="4">
    <button type="submit" id="submit">Set login limit</button>
    This extends your normal login session, which expires after a few hours,
    usually just before you submit a meticulously-prepared service.
    </form></li>
    <li>You are using Services version <?
        echo "{$version['major']}.{$version['minor']}.{$version['tick']}";
?>.  Refer to this version number, and include the address
    <?=$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']?> when writing bug reports.  You may send them via
    email to <a href="mailto: jmatjac@gmail.com">the author</a>.
    </li>
    </ul>

    <h2>Config Settings</h2>
    <form id="configsettings" action="utility/savesettings.php" method="post">
    <dl>
    <dt>Preferred Bible Abbreviation from <a href="http://www.biblegateway.com/versions/" title="BibleGateway.com">Bible Gateway</a></dt>
    <dd><input type="text" id="biblegwversion" name="biblegwversion"
        value="<?=$config->get('biblegwversion')?>" placeholder="Unset">
    </dl>
    <button type="submit">Submit</button><button type="reset">Reset</button>
    </form>
    </div>
</body>
</html>
