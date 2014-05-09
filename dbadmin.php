<?php
if ("churchyeartables-1" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_historic($db);
    fill_order($db);
    fill_synonyms($db);
    echo json_encode("First group of tables repopulated.");
}
if ("churchyeartables-2" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_propers($db);
    echo json_encode("Second group of tables repopulated.");
}
if ("churchyeartables-3" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_lessons($db);
    echo json_encode("Third group of tables repopulated.");
}
if ("churchyeartables-4" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_collect_texts($db);
    echo json_encode("Fourth group of tables repopulated.");
}
if ("churchyeartables-5" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_collect_indexes($db);
    echo json_encode("Fifth group of tables repopulated.");
}
if ("churchyeartables-6" == $_GET['action'] ) {
    $selective = true;
    require("./init.php");
    require_once("./utility/fillservicetables.php");
    fill_graduals($db);
    echo json_encode("Sixth group of tables repopulated.");
}
?>
