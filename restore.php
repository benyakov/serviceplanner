<? // Select a dump file to upload, then execute it.
require("./init.php");
if (! $auth) {
    header("location: index.php");
    exit(0);
}
$dumpfile = "restore-{$dbname}.txt";
if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $dumpfile))
{
    $cmdline = "mysql -u {$dbconnection['dbuser']} -p{$dbconnection['dbpassword']} -h {$dbconnection['dbhost']} {$dbconnection['dbname']} ".
        "-e 'source ${dumpfile}';";
    $result = system($cmdline, $return);
    unlink($dumpfile);
    if (0 == $return)
    {
        setMessage("Restore succeeded.");
        header("Location: records.php");
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en"><head><title>Problem Executing Restore</title></head>
        <body><h1>Problem Executing Restore</h1>
        <p>Command: <pre><?=$cmdline?></p>
        <p>Exit code: <?=$return?></p>
        <p>Output: <pre><?=$result?></pre></p>
        </body></html>
        <?
    }
} else {
    setMessage("Problem uploading backup file.");
    header("Location: records.php");
}
?>
