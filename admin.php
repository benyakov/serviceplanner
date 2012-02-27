<?
require("./init.php");
if (! $auth) {
    header('Location: index.html');
}
?>
<html>
<?=html_head("Housekeeping")?>
<body>
    <?=sitetabs($sitetabs, $script_basename)?>
    <div id="content-container">
    <h1>Housekeeping</h1>
    <p>This page contains the links for backing up the database, restoring it,
    and the initial setup of the database.   It is recommended that you back up
    often.  You get to decide what that means.  It would also be a good idea to
    practice restoring at least once, to make sure it works.  (If it doesn't,
    your database may lose data.)</p>

    <p>To make the information available to others in the public, it is
    recommended that you make links to the files (linked here) <a
    href="index.php">index.php</a> and <a
    href="servicerecords.php">servicerecords.php</a> only.</p>

    <p>The first file linked above will show a page without the navigation tabs
    or the ability to change things, <em>but only</em> if it is accessed from
    <em>outside</em> the installation of this application on the web server.
    That can be accomplished on a Unix system by creating a symbolic link to
    it, for example, from the parent directory of the installation like this:
    "<tt>ln -s services/index.php hymns.php</tt>".  Then the URL <em>to the new
symbolic link</em> can be given to others, e.g. organists.</p>

    <p>The second file linked above can be distributed directly as a web page
    link, or via a symbolic link created in the manner described above.
    However, it's probably a better idea to create a symbolic link for that,
    too, lest anyone find the pages that modify the database by playing with
    the URL.</p>

    <ul>
    <li><a href="setupdb.php">Initial Database Setup</a> FYI only.  Don't run
    it again.</li>
    <li><a href="dump.php">Save a Backup of the Database</a></li>
    <li><a href="restore.php">Restore from a Saved Backup</a></li>
    </ul>
    </div>
</body>
</html>
