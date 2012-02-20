<? // Set up db.php database configuration file.
chdir("..");
require("./init.php");
chdir("utility");
if (array_key_exists("step", $_POST) && $_POST['step'] == '2') {
    // Process the form (second time around)
    if (file_exists("../db-connection.php")) {
        header ("Location: http://{$serverdir}/index.php?message=".urlencode(__('Database configuration already exists.  To reconfigure, delete db-connection.php and try again.')));
        exit(0);
    }

    $fp = fopen("../db-connection.php", "w");
    fwrite($fp, "<? // Do not change this file unless you know what you are doing.
// This tells the web application how to connect to your database.
try{
    \$dbh = new PDO('mysql:host={$_POST['dbhost']};dbname={$_POST['dbname']}',
        '{$_POST['dbuser']}', '{$_POST['dbpassword']}');
} catch (PDOException \$e) {
    die(\"Database Error: {\$e->getMessage()} </br>\");
}
\$dbp = '{$_POST['dbtableprefix']}';
\$dbconnection = array(
    'dbhost'=>\"{$_POST['dbhost']}\",
    'dbname'=>\"{$_POST['dbname']}\",
    'dbuser'=>\"{$_POST['dbuser']}\",
    'dbpassword'=>\"{$_POST['dbpassword']}\");
?>
");
    fclose($fp);
    chmod("../db.php", 0600);
    header ("Location: http://{$serverdir}/index.php");
    exit(0);
} else {
    // Display the form (first time around)
?>
<!DOCTYPE html>
    <html lang="en">
        <head>
            <title>New Installation</title>
            <link rel="stylesheet" type="text/css" href="../style.css">
        </head>
    <body><h1>New Installation</h1>

    <table border=0 cellspacing=7 cellpadding=0>
    <form name="configForm" method="POST" action=".">
        <input type="hidden" name="step" value="2"/>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels"><?=__('dbhost')?></span></td>
            <td><input required type="text" name="dbhost" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels"><?=__('dbname')?></span></td>
            <td><input required type="text" name="dbname" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels"><?=__('dbuser')?></span></td>
            <td><input required type="text" name="dbuser" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels"><?=__('dbpassword')?></span></td>
            <td><input required type="text" name="dbpassword" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels"><?=__('dbtableprefix')?></span></td>
            <td><input type="text" name="dbtableprefix" size="25" value=""/></td>
        </tr>
        <tr>
            <td><input type="submit" name="submit" value="<?= __('submit') ?>"/></td>
        </tr>
    </form>
    </table>
    </body></html>
<?
}
// vim: set tags+=../../**/tags :
?>
