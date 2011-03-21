<?php
require("../db-connection.php");
mysql_query("ALTER TABLE {$dbp}days ADD COLUMN `servicenotes` TEXT default NULL
    AFTER `rite`") or die (mysql_error());

echo "Update 0.13-0.14 Successful.";
?>

