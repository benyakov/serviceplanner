<?php /* Administrative interface
    Copyright (C) 2023 Jesse Jacobsen

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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */

require("./init.php");
requireAuth("index.php", 3);
$this_script = "{$protocol}://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
if ('dellect' == getPOST('action')) {
    $db = new DBConnection();
    $q = $db->prepare("DELETE FROM `{$db->getPrefix()}churchyear_lessons`
        WHERE `lectionary`=?");
    if ($q->execute(Array(getPOST('lectionary')))) {
        echo json_encode(Array(1, "Lectionary deleted.", getLectionaryNames()));
    } else {
        echo json_encode(Array(0, "Couldn't delete that lectionary"));
    }
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
        var lectionaryUploadName = document.getElementById('lectionary_name');
        lectionaryUploadName.oninvalid = function(event) {
            event.target.setCustomValidity('Use letters, numbers, -, and _ characters in the lectionary name.');
        }
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
        $("#minimizetips").click(function(evt) {
            evt.preventDefault();
            if ($(this).html() == "[-]") {
                $("#integrationtips").hide();
                $(this).html("[+]");
            } else if ($(this).html() == "[+]") {
                $("#integrationtips").show();
                $(this).html("[-]");
            } else {
                $(this).html("[-]");
            }
        }).click();
        $("#minimizesettings").click(function(evt) {
            evt.preventDefault();
            if ($(this).html() == "[-]") {
                $("#configsettings").hide();
                $(this).html("[+]");
            } else if ($(this).html() == "[+]") {
                $("#configsettings").show();
                $(this).html("[-]");
            } else {
                $(this).html("[-]");
            }
        })
        $("#minimizecydata").click(function(evt) {
            evt.preventDefault();
            if ($(this).html() == "[-]") {
                $("#cydata").hide();
                $(this).html("[+]");
            } else if ($(this).html() == "[+]") {
                $("#cydata").show();
                $(this).html("[-]");
            } else {
                $(this).html("[-]");
            }
        })
        $("#minimizebackups").click(function(evt) {
            evt.preventDefault();
            if ($(this).html() == "[-]") {
                $("#backups").hide();
                $(this).html("[+]");
            } else if ($(this).html() == "[+]") {
                $("#backups").show();
                $(this).html("[-]");
            } else {
                $(this).html("[-]");
            }
        })
        $("#minimizetweaks").click(function(evt) {
            evt.preventDefault();
            if ($(this).html() == "[-]") {
                $("#tweaks").hide();
                $(this).html("[+]");
            } else if ($(this).html() == "[+]") {
                $("#tweaks").show();
                $(this).html("[-]");
            } else {
                $(this).html("[-]");
            }
        })
        $("#minimizeautochurchyear").click(function(evt) {
            evt.preventDefault();
            if ($(this).html() == "[-]") {
                $("#autochurchyear").hide();
                $(this).html("[+]");
            } else if ($(this).html() == "[+]") {
                $("#autochurchyear").show();
                $(this).html("[-]");
            } else {
                $(this).html("[-]");
            }
        })
        if (! window.location.hash) {
            $("#minimizetweaks").click();
            $("#minimizebackups").click();
            $("#minimizecydata").click();
            $("#minimizesettings").click();
            $("#minimizeautochurchyear").click();
        }
        $("#delete-lectionary").submit(function(evt) {
            evt.preventDefault();
            var lectname = $("#deleted-lect").val();
            if (confirm("Really delete all data for '"+lectname+"'?")) {
                var xhr = $.post("<?=$this_script?>", { action: "dellect",
                    lectionary: lectname}, function(rv) {
                        rv = $.parseJSON(rv);
                        if (rv[0]) {
                            setMessage(rv[1]);
                            var $dl = $("#deleted-lect");
                            $dl.empty();
                            var $el = $("#exported-lect");
                            $el.empty();
                            for (key in rv[2]) {
                                var value = rv[2][key];
                                $dl.append($("<option></option>")
                                    .attr("value", value).text(value));
                                $el.append($("<option></option>")
                                    .attr("value", value).text(value));
                            };
                        } else {
                            setMessage(rv[1]);
                        }
                    });
            }
        });
    });
</script>
    <?pageHeader();
    siteTabs(); ?>
    <div id="content-container">
    <div class="maxcolumn centered">
    <h1>Housekeeping</h1>
    <p class="explanation">Back up, restore, export, import, and configure your
service planner here.  Keeping frequent backups is always highly
recommended!</p>

    <p>You are using Service Planner version
        <?= "{$version['major']}.{$version['minor']}.{$version['tick']}" ?>.
        Refer to this version number, and include the address
    <?=$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']?> when writing bug reports.  You may send them via
    email to <a href="mailto: jesse@ma-amad.com">the author</a>.
    </p>

    <h2>Tips for Web Site Integration</h2>

    <a href="javascript:void(0);" id="minimizetips">[-]</a>

    <div id="integrationtips">
    <h3>Making data available elsewhere on your web site</h3>

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

    <h3>Logins allow the whole thing to be publicly available</h3>

    <p>Since pages that modify the database are now login-restricted,
    it's also possible to allow the whole world to see the whole
    installation with less risk that anyone would mess with your data.
    For users that might contribute flags to services, you can create
    limited access "Basic User" accounts.</p>

    <h3>Mashing up pages from here into your own web site.</h3>

    <p>One other possibility for those with their own web site elsewhere is to
    include one of the above pages in your own page via a Javascript mash-up.
    Simply insert the following code at the appropriate places in your web
    page:</p>

    <dl>
        <dt>In the page header (if it's not already there), insert:</dt>
        <dd class="honorspaces">
&lt;script type="text/javascript" src="<?=$protocol?>://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"&gt;&lt;/script&gt;
        </dd>
        <dt>Where you want the listing of all records to appear in the page body, insert:</dt>
        <dd class="honorspaces">
&lt;div id="services-import"&gt;&lt;/div&gt;
&lt;script type="text/javascript"&gt;
$(document).ready(function(){var a="<?=$protocol?>://<?=$_SERVER['SERVER_NAME']?>/<?=dirname($_SERVER['PHP_SELF'])?>/servicerecords.php";$("#services-import").load(a+" #content-container",function(){if(!$("#services-import").has("#content-container").length){$.ajax(a,{dataType:"jsonp",jsonp:"jsonpreq",success:function(a,b){$("#services-import").html(a)}})}})})
&lt;/script&gt;
        </dd>
        <dt>Where you want the listing of <em>future</em> hymns to appear in the page body, insert:</dt>
        <dd class="honorspaces">
&lt;div id="services-import"&gt;&lt;/div&gt;
&lt;script type="text/javascript"&gt;
$(document).ready(function(){var a="<?=$protocol?>://<?=$_SERVER['SERVER_NAME']?>/<?=dirname($_SERVER['PHP_SELF'])?>/index.php";$("#services-import").load(a+" #content-container",function(){if(!$("#services-import").has("#content-container").length){$.ajax(a,{dataType:"jsonp",jsonp:"jsonpreq",success:function(a,b){$("#services-import").html(a)}})}})})
&lt;/script&gt;
        </dd>
        <dt>Finally, save your server's domain name here
        (or multiple servers' domain names, one per line),
        like "<?=$protocol?>://www.mydomain.com":<dt>
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

    </div>

    <h2>The Broom Closet</h2>
    <h3>Backups</h3>

    <a href="javascript:void(0);" id="minimizebackups">[-]</a>
    <div id="backups">
    <h4>Database</h4>
    <p class="explanation">
        When restoring, be sure to restore into a Services installation
        with the same version. The first and second numbers are the most important.
        You are using Service Planner version
        <?= "{$version['major']}.{$version['minor']}.{$version['tick']}" ?>.
    </p>
    <ol>
    <li><a href="dump.php">Save a Backup of the Database</a></li>
    <li><a href="dump.php?only=churchyear">Save a Backup of Your Church Year Modifications</a></li>
    <li><form id="restore-backup" action="restore.php" method="post"
        enctype="multipart/form-data">
        <label for="backup_file">Upload a backup (dump) file to restore.</label>
        <input id="backup_file" type="file" name="backup_file"
            required placeholder="Select local file">
        <button type="submit">Send</button>
    </form>  (Caution: This will replace all current data, and things could go wrong.  Test before relying upon it!)</li>
    </ol>
    <h4><a name="uploaded-files">Uploaded Files</a></h4>
    <p class="explanation">At this time, these are sermon manuscripts in
directories/folders located through the database. That means the service
planner won't know anything about new files you may upload this way.  You can
use this to restore uploaded files if they have been corrupted or lost on the
web server.</p>
    <ol>
    <li><a href="uploads.php">Save a Backup of Uploaded Files</a> </li>
    <li><form id="restore-uploads" action="uploads.php" method="post"
        enctype="multipart/form-data">
        <input type="hidden" name="restore_backup_file" value="true">
        <label for="backup_file">Upload a files-backup (tar) file to restore.</label>
        <input id="backup_file" type="file" name="backup_file"
            required placeholder="Select local file">
        <button type="submit">Send</button>
    </form> (Caution: This will overwrite existing uploads if they occupy the same location in the archive, and the existing file is older than the archived version.)</li>
    </ol>
    </div>

    <h3>Exporting and Importing Church Year Data</h3>

    <a href="javascript:void(0);" id="minimizecydata">[-]</a>
    <div id="cydata">
    <p class="explanation">There are several parts to the church year data, and
you can export many of them to CSV (comma-separated value) files, which are
editable in a text editor or spreadsheet.  Files of the same format can then be
imported, either to replace existing data, or to provide supplementary or
alternative data. For example, if you wish to create a new series of collects
or a new lectionary for preaching, you would export one that is closest, and
then make all of your changes in the CSV file. Later, you could upload it with
a new name for the lectionary or collect series. When importing data, be sure
to have a recent backup first, at least of your church year data.</p>
    <dl>
    <dt><a name="lectionary-export">Lectionary</a></dt><dd><form id="export-lectionary" action="export.php" method="get">
    <label for="lectionary">Export single lectionary as CSV.</label>
    <select name="lectionary" id="exported-lect">
    <? foreach (getLectionaryNames() as $lname) { ?>
        <option name="<?=$lname?>"><?=$lname?></option>
    <? } ?>
    </select>
    <button type="submit">Export Lectionary</button>
    </form></dd>
    <dd><form id="delete-lectionary" action="<?=$this_script?>" method="post">
    <label for="lectionary">Delete single lectionary (use caution).</label>
    <select name="lectionary" id="deleted-lect">
    <? foreach (getLectionaryNames() as $lname) { ?>
        <option name="<?=$lname?>"><?=$lname?></option>
    <? } ?>
    </select>
    <button type="submit">Delete All Days In Lectionary</button>
    </form></dd>
    <dd> <form id="import-lectionary" action="import.php" method="post"
            enctype="multipart/form-data">
        <input type="hidden" name="import" value="lectionary">
        <fieldset><legend>Import Lectionary</legend>
        <input type="file" id="lectionary_file" name="import-file" required
            placeholder="Select local file."><br>
        <label for="lectionary_name">Name for imported lectionary</label>
        <input type="text" id="lectionary_name" name="lectionary_name"
            required pattern="[-a-zA-Z0-9_]{1,55}" placeholder="Enter name."><br>
        <input type="checkbox" id="lect_replace" name="replace">
        <label for="lect_replace">Replace all existing records for this lectionary?</label><br>
        <button type="submit">Import Lectionary</button>
    </fieldset></form></dd>
    <dt>General Propers</dt>
    <dd><a href="export.php?export=churchyear-propers">Export General Propers for the Church Year</a></dd>
    <dd><form id="import-churchyear-propers" action="import.php" method="post"
        enctype="multipart/form-data">
        <input type="hidden" name="import" value="churchyear-propers">
        <fieldset><legend>Import General Church Year Propers</legend>
        <input type="file" id="churchyear_propers_file" name="import-file" required
            placeholder="Select local file."><br>
        <input type="checkbox" id="churchyear_propers_replace" name="replace">
        <label for="churchyear_propers_replace">Replace all existing general propers?</label><br>
        <button type="submit">Import General Propers</button>
        </fieldset></form></dd>
    <dt>Synonyms</dt>
    <dd><a href="export.php?export=synonyms">Export Synonyms for Church Year Day Names</a></dd>
    <dd class="explanation"><p>If a canonical
        name does not already exist in the church year when you try to
        import a set of synonyms for it, the import process will create one
        with only a name. You must finish setting it up later.</p>
        <p>Replacing all existing synonyms can result in the deletion of some
        existing synonyms. When a synonym is deleted, all propers related to
        it will be deleted as well. You may want to back up your propers
        first.</p></dd>
    <dd>
        <form id="import-synonyms" action="import.php" method="post"
            enctype="multipart/form-data">
        <input type="hidden" name="import" value="synonyms">
        <fieldset><legend>Import Synonyms</legend>
        <input type="file" id="synonyms_file" name="import-file" required
            placeholder="Select local file."><br>
        <input type="checkbox" id="synonyms_replace" name="replace">
        <label for="synonyms_replace">Replace all existing synonyms?</label><br>
        <button type="submit">Import Synonyms</button>
    </fieldset></form></dd>
    <dt>Church Year Configuration</dt>
    <dd><a href="export.php?export=churchyear">Export Church Year Configuration</a> (controlling when each day falls)</dd>
    <dd><form id="import-churchyear" action="import.php" method="post"
            enctype="multipart/form-data">
        <input type="hidden" name="import" value="churchyear">
        <fieldset><legend>Import Church Year</legend>
        <input type="file" id="churchyear_file" name="import-file" required
            placeholder="Select local file."><br>
        <input type="checkbox" id="churchyear_replace" name="replaceall">
        <label for="churchyear_replace">Replace all existing church year data?</label><br>
        <input type="checkbox" id="churchyear_delete" name="deletemissing">
        <label for="churchyear_delete">Delete days not in imported data?</label><br>
        <button type="submit">Import Church Year Data</button>
    </fieldset></form></dd>
    <dt>Collects by Series</dt>
    <dd><form id="export-collect-series" action="export.php" method="get">
        <input type="hidden" name="export" value="collects">
        <fieldset><legend>Export a Series of Collects</legend>
        <label for="export-collect-class">Class of Collects to Export:</label>
        <select name="class" id="export-collect-class">
        <? foreach (getCollectClasses() as $cname) { ?>
            <option name="<?=$cname?>"><?=$cname?></option>
        <? } ?>
        </select><br>
        <button type="submit">Export</button>
        </fieldset>
    </form></dd>
    <dd><form id="export-collect-assignments" action="export.php" method="get">
        <input type="hidden" name="export" value="collectassignments">
        <fieldset><legend>Export Collect Series Day Assignments</legend>
        <label for="export-assignments-class">Class of Collect Assignments to Export:</label>
        <select name="class" id="export-assignments-class">
        <? foreach (getCollectClasses() as $cname) { ?>
            <option name="<?=$cname?>"><?=$cname?></option>
        <? } ?>
        </select><br>
        <button type="submit">Export</button>
        </fieldset>
    </form></dd>
    <dd><form id="import-collects" action="import.php" method="post"
            enctype="multipart/form-data">
        <input type="hidden" name="import" value="churchyear-collects">
        <fieldset><legend>Import Collects</legend>
        <label for="collects_file">Collect Series Data File</label>
        <input type="file" id="collects_file" name="import-file" required
            placeholder="Select collects file."><br>
        <label for="collect_assignments_file">Collect Series Assignments File</label>
        <input type="file" id="collect_assignments_file"
            name="import-assignments-file" required
            placeholder="Select assignments file."><br>
        <label for="collect_series">New name for imported series of collects</label>
        <input type="text" id="collect_series" name="collect-series"
            placeholder="Enter name" required><br>
        <p>This will replace any existing series with the same name.</p>
        <button type="submit">Import Collects Series</button>
    </fieldset></form></dd>
    </dl>
    </div>

    <h3><a name="tweaking">Tweaking Your Installation</a></h3>

    <a href="javascript:void(0);" id="minimizetweaks">[-]</a>
    <div id="tweaks">
    <ol>
    <li><form id="import-hymns" action="import.php" method="post">
    <input type="hidden" name="import" value="hymnnames">
    <label for="prefix">Merge hymn titles from a co-installation.</label>
    <input type="text" name="prefix" pattern="[\w\d]+" id="prefix"
        required placeholder="Database Prefix of Source Installation">
    <button type="submit">Import Titles</button>
    </form></li>
    <li><a name="restore-church-year">Restore church year to default:</a>
    <a href="" id="purge-churchyear"
        title="Purge Church Year Tables">Church Year Data</a> or
    <a href="churchyear.php?request=dropfunctions"
        title="Drop Church Year Functions">Church Year Functions</a></li>
    <li>Manually repopulate empty
        <a href="admin.php?flag=fill-churchyear">church year data</a> or missing
        <a href="admin.php?flag=create-churchyear-functions">church year functions</a>.
        This should happen automatically most of the time, and should not be
        tried unless needed.</li>
    <li>Manually re-create <a href="admin.php?flag=create-views">synonym coordination views</a> in the database.  This should also happen automatically when needed.</li>
    <li><a href="hymnindex.php?drop=yes">Drop and re-create hymn cross-reference table</a>.  This is needed when the table has been changed in a new version.</li>
    <li><a href="admin.php?flag=reset-flag-cache">Reset (delete) the cache for service flags</a>. This is almost never needed, but can be convenient.</li>
    </ol>

    <? // Use standard lookup function, providing default and returning seconds
    $akmax = floor(getAuthCookieMaxAge()/(24*60*60));  ?>
    </div>

    <h3><a name="config-settings">Config Settings</a></h2><? $config = getConfig(false); ?>
    <a href="javascript:void(0);" id="minimizesettings">[-]</a>
    <div id="configsettings">
    <form id="configsettings" action="<?=$_SERVER['PHP_SELF']?>?flag=savesettings" method="post">
    <dl>
    <dt><a name="bible-link-template">Bible Link Template</a></dt>
    <dd class="explanation">To use a Bible lookup website other that Bible Gateway, write the url here, with the text "{{}}" (double braces)
    where the Bible reference should be inserted. Leave this blank to use Bible Gateway instead.</dd>
    <dd><input type="text" id="biblelinktemplate" name="biblelinktemplate"
        value="<?=$config->getDefault("", "biblelinktemplate")?>" placeholder="Use Bible Gateway">
    <dt><a name="biblegateway-abbreviation">Preferred Bible Abbreviation</a> from <a href="http://www.biblegateway.com/versions/" title="BibleGateway.com">Bible Gateway</a></dt>
    <dd class="explanation">All-caps version abbreviations as used by the Bible Gateway web site. This is used to generate links for lectionary texts.
Multiple abbreviations may be separated by a semicolon, like "SBLGNT;WLC;NKJV",
which gives a 3-column Greek/Hebrew/English interlinear.</dd>
    <dd><input type="text" id="biblegwversion" name="biblegwversion"
        value="<?=$config->getDefault("", "biblegwversion")?>" placeholder="Unset">
    <dt>Site Tab Selection & Order</dt>
    <dd class="explanation">Each line represents a single navigation tab. Each tab contains the tab
name, followed by a colon (:), followed by a label for the tab. Tab names may
include "index", "report", "modify", "block", "sermons", "hymnindex",
"churchyear", "admin".  The tabs will appear left-to-right in the same order
they appear here.</dd>
    <dd><textarea id="sitetabs-config" class="sitetabsconfig"
        name="sitetabs-config"><?
    $options = getOptions();
    foreach ($config->getDefault($options->get("sitetabs"),
        "sitetabs") as $k=>$v)
        echo "$k:$v\n";
    ?></textarea></dd>
    <dt><a name="combine-occurrences">Should</a> the service listings combine multiple occurrences into one listing?</dt>
    <dd class="explanation">When not combined, each group of hymns will contain
    hymns planned for only one service occurrence. When combined, each group of
    hymns will contain hymns planned for <em>all</em> occurrences of this service,
    in the order of their sequence numbers. There will also be more than one row of
    flags, each with a label for the occurrence it describes.
    <a href="help.php#two-occurrence-formats">See the help for more.</a></dd>
    <dd><input type="checkbox" id="combineoccurrences" name="combineoccurrences"
        <?=($options->getDefault("0", "combineoccurrences") == 1)?"checked":""?>>
        <label for="combineoccurrences">Combine Occurrences</label></dd>
    <dt>Anonymous Site Tab Selection & Order</dt>
    <dd class="explanation">Site tabs that appear for anonymous users.  See the explanation above.
The only tabs accessible to anonymous users are "index", "records",
"hymnindex", and "report".</dd>
    <dd><textarea id="sitetabs-config-anon" class="sitetabsconfig"
        name="sitetabs-config-anon"><?
    foreach ($config->getDefault($options->get("anonymous sitetabs"),
        "anonymous sitetabs") as $k=>$v)
        echo "$k:$v\n";
    ?></textarea></dd>
    <dt><a name="default-occurrence">Default Service Occurrence</a></dt>
    <dd class="explanation">A default occurrence to pre-fill the Occurrence field
    when entering new hymns or services.</dd>
    <dd><input type="text" id="defaultoccurrence" name="defaultoccurrence"
        value="<?=$options->getDefault("", "defaultoccurrence")?>" placeholder="Unset">
    </dd>
    <dt>Maximum Auth Cookie Age</dt>
    <dd class="explanation">The maximum number of days you
      wish the Service Planner to remember your login session.
      This extends your normal login session, which expires after a few hours,
      usually just before you submit a meticulously-prepared service.</dd>
    <dd><input type="number" name="cookie-age"
         value="<?=$akmax?>" size="4"></dd>
    <dt>Hymnbooks Available</dt>
    <dd class="explanation">List of hymnbook abbreviations that will be
available for specifying hymns. The first will be the default book.</dd>
    <dd><textarea id="hymnbooks-option" class="hymnbooksconfig"
        name="hymnbooks-option"><?
    echo implode("\n", $options->get('hymnbooks'));
    ?></textarea></dd>
    <dt>Hymn Count</dt>
    <dd class="explanation">The number of hymn slots available when entering a
    service</dd>
    <dd><input type="number" id="hymncount-option" name="hymncount-option"
         value="<?=$options->get('hymncount')?>"></dd>
    <dt>Hymn Last Used Count</dt>
    <dd class="explanation">When entering or adding to a service, typing a hymn
    number automatically displays the last few dates that particular hymn has
    been used, and where. This is the number of prior dates that will be
    listed.</dd>
    <dd><input type="number" id="usedhistory-option" name="usedhistory-option"
        value="<?=$options->get('used_history')?>"></dd>
    <dt>Default Modify Tab Mode</dt>
    <dd class="explanation">The "modify" tab can show the listing of hymns and
    services in two ways: "future services only," which lists them in
    chronological order, or "all," which lists them in reverse chronological
    order.  It can be changed on the page itself; this just sets the
    default behavior.</dd>
    <dd><select id="modifyorder-option" name="modifyorder-option">
    <?foreach (array("Future", "All") as $moopt) {
        if ($moopt == $options->getDefault('All', 'modifyorder')) $selected = " selected";
        else $selected = "";
        echo "<option name='$moopt'$selected>$moopt</option>\n";
    }?>
        </select></dd>
    <dt><a name="addable-flags">Addable Service Flags</a></dt>
    <dd class="explanation">Users with admin privileges can add any service
    flags they like other logged-in users can only add the service flags listed
    here. Write one per line.</dd>
    <dd><textarea id="service-flags-option" class="serviceflagsconfig"
        name="service-flags-option"><?
    echo implode("\n", $options->get('addable_service_flags'));
    ?></textarea></dd>
    <dt><a name="default-modify-past-range">Default Modify Past Range</a></dt>
    <dd class="explanation">When restarting a new PHP session, how many weeks in
    the past should the Modify tab display? See the next item for a note about
    PHP sessions.</dd>
    <dd><input type="number" id="default-modify-past-range-option"
         name="default-modify-future-range-option"
         value="<?=$options->getDefault(1, 'past-range')?>"></dd>
    <dt><a name="default-modify-future-range">Default Modify Future Range</a></dt>
    <dd class="explanation">When restarting a new PHP session, how many weeks
    in the future should the Modify tab display? PHP sessions keep track of
    information like this while using the website, but they do expire after some
    time. Though the service planner can remember your login, it will lose anything
    stored only in the current PHP session.</dd>
    <dd><input type="number" id="default-modify-future-range-option"
         name="default-modify-future-range-option"
         value="<?=$options->getDefault(1, 'future-range')?>"></dd>
    </dl>
    <button type="submit">Submit</button><button type="reset">Reset</button>
    </form>
    </div>
    <h3><a name="churchyear_setup">Automatic Church Year Setup</a></h3>

    <a href="javascript:void(0);" id="minimizeautochurchyear">[-]</a>
    <div id="autochurchyear">
    <dl>
    <dt><a name="nonfestival-skip">When should we skip Sundays in the non-festival half of the church year?</a></dt>
    <dd class="explanation">Select which system should be used to skip
    Sundays/propers in the non-festival half of the year. Note: This will
    reconfigure the affected days listed on the Church Year tab. "Historic" means
    the season will observe Michaelmas on the last Sunday of September, then skip
    to Trinity 19 as Michaelmas 2, and afterward skip remaining Sundays after the
    fourth-last Sunday to observe Trinity 25, 26, and 27 at the end. "ILCW" means
    the season will not skip any Sundays until just before the last Sunday of the
    Church Year (Pentecost 28 or Trinity 27). "RCL" means the season will skip a
    series of propers immediately after Trinity, so that the remaining propers will
    fit weekly through the end.</dd>
    <dd><form id="nonfestival-setup" action="churchyear.php?flag=savesettings" method="post">
        <input type="hidden" name="reconfigure" value="nonfestivalskip">
        <select id="nonfestivalskip-option" name="nonfestivalskip-option">
    <?foreach (array("Historic", "ILCW", "RCL") as $nfsopt) {
        if ($nfsopt == $options->getDefault('Historic', 'nonfestival_skip'))
            $selected = " selected";
            else $selected = "";
        echo "<option name='$nfsopt'$selected>$nfsopt</option>\n";
    }?>
    </select> <button type="submit">Reconfigure Church Year</button> </dd>
    </dl>
    </div>

    </div>
    </div>
</body>
</html>
