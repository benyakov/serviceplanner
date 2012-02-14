<?
require("db-connection.php");
require("functions.php");
require("options.php");
require("setup-session.php");
$sql = "SELECT `title` FROM `names`
    WHERE `book` = '{$_GET['book']}'
    AND `number` = '{$_GET['number']}'";
$result = mysql_query($sql) or die(mysql_error().$sql);
if (mysql_num_rows($result)) {
    $row = mysql_fetch_assoc($result);
    echo $row['title'];
} else {
    echo "";
}
?>
