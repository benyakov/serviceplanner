<?php
if ("churchyeartables-1" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    historic($db);
    order($db);
    synonyms($db);
    echo json_encode("First group of tables repopulated.");
}
if ("churchyeartables-2" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    propers($db);
    lessons($db);
    echo json_encode("Second group of tables repopulated.");
}
if ("churchyeartables-3" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    collects($db);
    graduals($db);
    echo json_encode("Third group of tables repopulated.");
}
?>
