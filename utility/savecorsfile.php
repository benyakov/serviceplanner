<?
chdir("..");
require("./init.php");
chdir("utility");
if ($_POST['contents']) {
    $fh = fopen("../corsfile.txt", "w");
    fwrite($fh, $_POST['contents']);
    fclose($fh);
}

if (array_key_exists('ajax', $_POST)) {
    echo $_POST['contents'];
} else {
    setMessage("File written.");
    header("Location: http://{$_SERVER['HTTP_HOST']}/"
        .dirname($serverdir)."admin.php");
}
?>
