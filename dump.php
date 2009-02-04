<?
require("db-connection.php");
header("Content-type: text/plain");
header("Content-disposition: attachment; filename=services.dump");
// Including the password here is insecure on a shared machine
// because the invocation will appear in the list of processes.
// But it's easy.
passthru("mysqldump -u ${dbuser} -p${dbpw} -h ${dbhost} ${dbname}");
?>
