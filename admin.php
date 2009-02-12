<?
require("functions.php");
require("options.php");
$script_basename = basename($_SERVER['SCRIPT_NAME'], ".php") ;
?>
<html>
<?=html_head("Housekeeping")?>
<body>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content_container">
    <h1>Housekeeping</h1>
    <p>This page contains the links for backing up the database, restoring it,
    and the initial setup of the database.   It is recommended that you back up
    often.  You get to decide what that means.  It would also be a good idea to
    practice restoring at least once, to make sure it works.  (If it doesn't,
    your database may lose data.)</p>

    <p>This is not a high security web application.  If someone knows how to
    point their browser at the pages that modify the database, they can delete
    or change everything you have there.  So don't link to those pages from
    public-facing web sites.  Password-based security may come in the future,
    but so far I'm not convinced it's necessary.  For now, keep backups.</p>

    <p>To make the information available to others in the public, it is
    recommended that you provide links to the files (linked here) <a
    href="hymns.php">hymns.php</a> and <a
    href="servicerecords.php">servicerecords.php</a> only.  (These can be web
    page links or symbolic links from some public place on the web server.)
    Those two intentionally lack links to the rest of the interface.</p>

    <ul>
    <li><a href="setupdb.php">Initial Database Setup</a> FYI only.  Don't run
    it again.</li>
    <li><a href="dump.php">Save a Backup of the Database</a></li>
    <li><a href="restore.php">Restore from a Saved Backup</a></li>
    </ul>
    </div>
</body>
</html>
