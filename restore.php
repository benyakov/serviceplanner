<? // Select a dump file to upload, then execute it.
require("./init.php");
if (! $auth) {
    header("location: index.php");
    exit(0);
}
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
echo "<html>\n";
html_head("Restore from Database Backup");
if (! array_key_exists("stage", $_GET)) {
?>
    <body>
    <h1>Restore from Database Backup</h1>
    <p>Please select the backup (dump) file.</p>
    <form action="http://<?=$this_script?>?stage=2" enctype="multipart/form-data"
        method="POST">
    <input type="file" name="backup_file" size="50">
    <input type="submit" value="Send"><input type="reset">
    </form>
    </body>
    </html>
    <?
} elseif (2 == $_GET['stage']) {
    $dumpfile = "restore-${dbname}.txt";
    if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $dumpfile))
    {
        $cmdline = "mysql -u ${dbuser} -p${dbpw} -h ${dbhost} ${dbname} ".
            "-e 'source ${dumpfile}';";
        $result = system($cmdline, $return);

        unlink($dumpfile);
        if (0 == $return)
        {
            $_SESSION[$sprefix]['message'] = "Restore succeeded.";
            header("Location: records.php");
        } else {
            ?>
            <html><head><title>Problem Executing Restore</title></head>
            <body><h1>Problem Executing Restore</h1>
            <p>Command: <pre><?=$cmdline?></p>
            <p>Exit code: <?=$return?></p>
            <p>Output: <pre><?=$result?></pre></p>
            </body></html>
            <?
        }
    } else {
        $_SESSION[$sprefix]['message'] = "Problem uploading backup file.";
        header("Location: records.php");
    }
    exit(0);
}
?>
