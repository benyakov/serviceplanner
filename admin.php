<?
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
    $(document).ready(function() {
        setupLogin("<?=authId()?>");
    });
</script>
    <header>
    <div id="login"><?=getLoginForm()?></div>
    <?showMessage();?>
    </header>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <h1>Housekeeping</h1>
    <p>This page contains the links for backing up the database, restoring it,
    and the initial setup of the database.   It is recommended that you back up
    often.  You get to decide what that means.  It would also be a good idea to
    practice restoring at least once, to make sure it works.  (If it doesn't,
    your database may lose data.)</p>

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

    <p>Since pages that modify the database are now login-restricted,
    it's also possible to allow the whole world to see the whole
    installation with less risk that anyone would mess with your data.  You
    may have to explain to your organist or secretary why they don't need to
    log in.</p>

    <p>One other possibility for those with their own web site elsewhere is to
    include one of the above pages in your own page via a Javascript mash-up.
    Simply insert the following code at the appropriate places in your web
    page:</p>

    <dl>
        <dt>In the page header (if it's not already there):</dt>
        <dd>
            &lt;script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"&gt;&lt;/script&gt;
        </dd>
        <dt>Where you want the hymn listing to appear in the page body:</dt>
        <dd class="honorspaces">
&lt;div id="services-import"&gt;&lt;/div&gt;
$.ajax({
    url: "http://<?=$_SERVER['HTTP_HOST']?>/<?=dirname($_SERVER["SCRIPT_NAME"])?>/servicerecords.php",
    success: function (data, status, jqxhr) {

    }
<!-- js importing
                into the above div's html.
            -->
        </dd>
    </dl>

    <h2>The Broom Closet</h2>

    <ul>
    <li><a href="utility/setupdb.php">Initial Database Setup</a> FYI only.
    Don't run it again.</li>
    <li><a href="dump.php">Save a Backup of the Database</a></li>
    <li><a href="restore.php">Restore from a Saved Backup</a></li>
    <li><form name="import_hymns" action="importhymns.php" method="post">
    <label for="prefix">Import hymn titles from a co-installation</label>
    <input type="text" name="prefix" pattern="[\w\d]+" id="prefix" required placeholder="Database Prefix of Source Installation">
    <button type="submit" id="submit">Import Titles</button>
    </form></li>
    </ul>
    </div>
</body>
</html>
