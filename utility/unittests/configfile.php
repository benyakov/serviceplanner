<? require('../configfile.php');
unlink('./test.ini');
echo "Testing global variables...<br>\n";

echo "Simple set...";
$cf = new Configfile('./test.ini', false);
$cf->set('bleh', 'blue');
$cf->save();
$fp = fopen('./test.ini', 'rb');
$contents = fread($fp, 1024);
fclose($fp);
if ($contents != 'bleh = "blue"'."\n")
    die("Configfile failed.\n");
unset($cf);
echo " passed<br>";

echo "Open/get, set and get...";
$cf = new Configfile('./test.ini', false);
if ($cf->get('bleh') != "blue")
    die("Configfile failed: value of key 'bleh' was {$cf->get('bleh')}.");
$cf->set('item2', 5);
$cf->save();
unset($cf);
$cf = new Configfile('./test.ini', false);
if ($cf->get('item2') != 5)
    die("Configfile failed: value of 'item2' was ".var_dump($cf->get('item2'))
        .".");
echo " passed<br>";

echo "Globals as arrays...";
unlink('./test.ini');
$cf = new Configfile('./test.ini', false);
$cf->set('foo', 'bar', 'baz');
$cf->save();
unset($cf);
$cf = new Configfile('./test.ini', false);
if ($cf->get('foo', 'bar') != 'baz')
    die("Configfile failed: value of 'foo.bar' was ".
        var_dump($cf->get('foo', 'bar'))
        .".");
unset($cf);
unlink('./test.ini');
echo " passed<br>";
?>
