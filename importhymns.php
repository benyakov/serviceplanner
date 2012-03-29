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
    $rowcount = $dbh->exec("INSERT IGNORE INTO `{$dbp}names` AS n1
        (book, number, title)
        SELECT n2.book, n2.number, n2.title
            FROM `{$_POST['prefix']}names` AS n2");
    setMessage($rowcount . " hymn names imported.");
    header('Location: admin.php');
} else {
    setMessage("Bad prefix: `{$_POST['prefix']}'");
    header('Location: admin.php');
}

?>
