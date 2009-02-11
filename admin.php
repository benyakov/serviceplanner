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
    and the initial setup of the database.  <b>Don't run the setup after you've
    already got things set up.  That wouldn't make any sense, and could cause
    problems.  You have been warned.</b>  It is recommended that you back up
    often.  (You get to decide what that means.)  It would also be a good idea
    to practice restoring at least once to a spare database, to make sure you
    know how.  Just edit the file db-connection.php so that the proper database
    will be used.</p>

    <p>This is not a high security web application.  If someone knows how to
    point their browser at the pages that modify the database, they can delete
    or change everything you have there.  So don't link to those pages from
    public-facing web sites.  Password-based security may be in the future, but
    so far I'm not convinced it's necessary.  For now, keep backups.</p>

    <p>To make the information available to others in the public, it is
    recommended that you make a symbolic link on the webserver from some other
    place to the files hymns.php and servicerecords.php.  They intentionally
    lack links to the rest of the interface.  Clever people could still find
    the real installation directory, so it is recommended that you create a
    symlink right there called "index.php" pointing to either hymns.php or
    servicerecords.php.  That should make it sufficiently impractical for the
    ill-intentioned to find the other pages.</p>

    <p>Setup, backups and restores are done with mysql utilities that should be
    available on the web server.  For convenience, these are run in a somewhat
    insecure way, which could allow someone with sufficient access to the web
    server to see your mysql user name and login.  It's not a bad problem, but
    keep that in mind when you decide how often to back up.</p>

    <ul>
    <li><a href="setupdb.php">Initial Database Setup</a></li>
    <li><a href="dump.php">Save a Backup of the Database</a></li>
    <li><a href="restore.php">Restore from a Saved Backup</a></li>
    </ul>
    </div>
</body>
</html>
