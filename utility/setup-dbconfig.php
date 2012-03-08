<? // Set up database configuration file.
require("../options.php");
require("../setup-session.php");
require("../functions.php");
$auth = auth();
$serverdir = dirname(dirname($_SERVER['PHP_SELF']));
if (array_key_exists("step", $_POST) && $_POST['step'] == '2') {
    // Process the form (second time around)
    if (file_exists("../db-connection.php")) {
        setMessage("Database configuration already exists.  To reconfigure, delete db-connection.php and any unwanted tables, then try again.");
        header ("Location: {$serverdir}/index.php");
        exit(0);
    }

    // Escape string-ending characters to avoid PHP injection
    $post = str_replace('\\', '\\\\', $_POST);
    $post = str_replace('\'', '\\\'', $post);

    $fp = fopen("../db-connection.php", "w");
    fwrite($fp, "<? // Do not change this file unless you know what you are doing.
// This tells the web application how to connect to your database.
try{
    \$dbh = new PDO('mysql:host={$post['dbhost']};dbname={$post['dbname']}',
        '{$post['dbuser']}', '{$post['dbpassword']}');
} catch (PDOException \$e) {
    die(\"Database Error: {\$e->getMessage()} </br>\");
}
\$dbp = '{$post['dbtableprefix']}';
\$dbconnection = array(
    'dbhost'=>'{$post['dbhost']}',
    'dbname'=>'{$post['dbname']}',
    'dbuser'=>'{$post['dbuser']}',
    'dbpassword'=>'{$post['dbpassword']}');
?>
");
    fclose($fp);
    chmod("../db-connection.php", 0600);
    require("../db-connection.php");
    // Test the existence of a table
    $q = $dbh->query("SHOW TABLES LIKE '{$dbp}days'");
    if ($q->rowCount()) {
        header("Location: {$serverdir}/index.php");
    } else {
        header("Location: setupdb.php");
    }
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
    <form name="configForm" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
        <input type="hidden" name="step" value="2"/>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Host</span></td>
            <td><input required type="text" name="dbhost" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Name</span></td>
            <td><input required type="text" name="dbname" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database User</span></td>
            <td><input required type="text" name="dbuser" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Password</span></td>
            <td><input required type="text" name="dbpassword" size="25" value=""/></td>
        </tr>
        <tr>
            <td valign="top" align="right" nowrap>
            <span class="form_labels">Database Table Prefix</span></td>
            <td><input type="text" name="dbtableprefix" size="25" value=""/></td>
        </tr>
        <tr>
            <td><input type="submit" name="submit" value="Submit"/></td>
        </tr>
    </form>
    </table>
    </body></html>
<?
}
// vim: set tags+=../../**/tags :
?>
