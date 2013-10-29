<? require('../configfile.php');

function error($msg) {
    echo "<span style=\"color: red; bgcolor: transparent\">{$msg}</span><br>\n";
    $fp = fopen('./test.ini', 'rb');
    echo "<pre>";
    echo fread($fp, 1024);
    echo "</pre>";
    fclose($fp);
}

function set_manipulation($configfile) {
    $configfile->set('foo', 'bar', 'baz');
    $configfile->set('foo', 'bin', 'zap');
    $configfile->set('foo', 'num', '[]', 'one');
    $configfile->set('foo', 'num', '[]', 'two');
    $configfile->set('foo', 'num', '[]', 'three');
    $configfile->set('foo', 'num2', Array('four', 'five', 'six'));
    $configfile->save();
}

function get_manipulation($configfile) {
    if ($configfile->get('foo', 'bar') != 'baz')
        error("Configfile failed: value of 'foo.bar' was ".
            print_r($configfile->get('foo', 'bar'), true) .".");
    if ($configfile->get('foo', 'bin') != 'zap')
        error("Configfile failed: value of 'foo.bin' was ".
            print_r($configfile->get('foo', 'bin'), true) .".");
    if ($configfile->get('foo', 'num') != Array('one', 'two', 'three'))
        error("Configfile failed: value of 'foo.num' was ".
            print_r($configfile->get('foo', 'num'), true) .".");
    if ($configfile->get('foo', 'num2', 0) != 'four')
        error("Configfile failed: value of 'foo.num2.0' was ".
            print_r($configfile->get('foo', 'num2', 0), true) .".");
}


/**
 * Tests
 */

unlink('./test.ini');
echo "Testing global variables...<br>\n";

echo "Simple set...<br>";
$cf = new Configfile('./test.ini', false);
$cf->set('bleh', 'blue');
$cf->save();
$fp = fopen('./test.ini', 'rb');
$contents = fread($fp, 1024);
fclose($fp);
if ($contents != 'bleh = "blue"'."\n")
    error("Configfile failed.\n");
unset($cf);

echo "Open/get, set and get...<br>";
$cf = new Configfile('./test.ini', false);
if ($cf->get('bleh') != "blue")
    error("Configfile failed: value of key 'bleh' was {$cf->get('bleh')}.");
$cf->set('item2', 5);
$cf->save();
unset($cf);
$cf = new Configfile('./test.ini', false);
if ($cf->get('item2') != 5)
    error("Configfile failed: value of 'item2' was ".var_dump($cf->get('item2'))
        .".");

echo "Globals as arrays...<br>";
unlink('./test.ini');
$cf = new Configfile('./test.ini', false);
set_manipulation($cf);
unset($cf);
$cf = new Configfile('./test.ini', false);
get_manipulation($cf);
unset($cf);
unlink('./test.ini');

echo "Manipulating values in a section...<br>";
$cf = new Configfile('./test.ini', true);
set_manipulation($cf);
get_manipulation($cf);
unset($cf);
unlink('./test.ini');

echo "If you see no errors, all tests passed.<br>";
?>
