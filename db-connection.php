<?
$dbhost = "mysql.bethanythedalles.org";
$dbuser = "btd_jesse";
$dbpw = "teeccino";
$dbname = "btd_services";
//$dbname = "btd_serv2";
mysql_connect($dbhost, $dbuser, $dbpw) or die(mysql_error());
mysql_select_db($dbname) or die(mysql_error());
?>
