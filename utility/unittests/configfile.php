<? require('../configfile.php');
$cf = new Configfile('./test.ini', false);
$cf->store('bleh', 'blue');
$cf->save();
$fp = fopen('./test.ini', 'rb');
$contents = fread($fp, 24);
fclose($fp);
if ($contents != 'bleh = "blue"'."\n") {
    echo "Configfile failed.\n";
    exit(0);
} else {
    echo "Configfile passed.\n";
}
unlink('./test.ini');
?>
