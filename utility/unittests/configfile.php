<? require('../configfile.php');
$cf = new Configfile('./test.ini', false);
$cf->store('bleh', 'blue');
$cf->save();
$fp = fopen('./test.ini', 'rb');
$contents = fread($fp, 1024);
fclose($fp);
if ($contents != 'bleh = "blue"'."\n") {
    echo "Configfile failed.\n";
    exit(1);
}
unset($cf);
$cf = new Configfile('./test.ini', false);
if ($cf->get('bleh') != "blue") {
    echo "Configfile failed: value of key 'bleh' was {$cf->get('bleh')}.";
    exit(1);
}
$cf->store('item2', 5);
$cf->save();
unset($cf);
$cf = new Configfile('./test.ini', false);
if ($cf->get('item2') != 5) {
    echo "Configfile failed: value of 'item2' was ".var_dump($cf->get('item2'))
        .".";
    exit(1);
}
echo "Configfile passed.\n";
unlink('./test.ini');
?>
