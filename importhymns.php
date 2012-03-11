<?
require("./init.php");
if (! $auth) {
    header('Location: index.php');
    exit(0);
}
if ($_POST['prefix'] && strpos($_POST['prefix'], ' ') === false) {
    $q = $dbh->query("SHOW TABLES LIKE '{$_POST['prefix']}names'");
    if (! count($q->fetchAll())) {
        setMessage("No names table exists with prefix `{$_POST['prefix']}'");
        header('Location: admin.php');
        exit(0);
    }
    $q = $dbh->exec("INSERT INTO `{$dbp}names` AS n1
        COLUMNS (book, number, title)
        (SELECT book, number, title FROM `{$_POST['prefix']}names` AS n2
            WHERE (NOT (n1.book = n2.book AND n1.number = n2.number)))");
    setMessage($q->rowCount() . " hymn names imported.");
    header('Location: admin.php');
} else {
    setMessage("Bad prefix: `{$_POST['prefix']}'");
    header('Location: admin.php');
}

?>
