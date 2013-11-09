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

function set_extends_manipulation($cfh) {
    $cfh->set('ext', 'ownkey', 'This rocks.');
    $cfh->setExtension('ext', 'foo');
}

function get_extends_manipulation($cfh) {
    if ($cfh->getExtension('ext') != 'foo')
        error("Configfile failed: value of getExtension('ext') was".
            print_r($cfh->getExtension('ext'), true).".");
    if ($cfh->get('ext', 'num', '1') != 'two')
        error("Configfile failed: value of 'ext.num[1]' was".
            print_r($cfh->get('ext', 'num', '1'), true).".");
    echo "&nbsp;&nbsp;&nbsp;...trying ext value on unextended object... ";
    $cfh->delExtension('ext', 'foo');
    try {
        if ($cfh->get('ext', 'bar') == "baz")
            error("Configfile failed: "
                ."unextended object failed to throw exception.");
    } catch (ConfigfileUnknownKey $e) {
        echo "Success.<br>";
    }
    echo "&nbsp;&nbsp;&nbsp;...the same when extended... ";
    $cfh->setExtension("ext", "foo");
    if ($cfh->get('ext', 'bar') != "baz")
        error("Configfile failed: "
            ."ext.bar = '".$cfh->get('ext', 'bar')."'.");
    else
        echo "Success.<br>";
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
    $configfile->transpose(Array("foo"), "bar", "bin");
    if ($configfile->get('foo', 'bin') != 'baz'
        && $configfile->get('foo', 'bar') != 'zap')
        error("Configfile failed: transpose resulted in 'foo.bin' = "
            .print_r($configfile->get('foo', 'bin'), true). " and foo.bar = "
            .print_r($configfile->get('foo', 'bar'), true));
    $configfile->transpose(Array("foo"), "bar", "bin");
}


/**
 * Tests
 */

unlink('./test.ini');
echo "Testing global variables...<br>\n";

echo "Simple set...<br>";
$cf = new Configfile('./test.ini', false, true);
$cf->set('bleh', 'blue');
$cf->save();
$fp = fopen('./test.ini', 'rb');
$contents = fread($fp, 1024);
fclose($fp);
if ($contents != 'bleh = "blue"'."\n")
    error("Configfile failed.\n");
unset($cf);

echo "Open/get, set and get...<br>";
$cf = new Configfile('./test.ini', false, true);
if ($cf->get('bleh') != "blue")
    error("Configfile failed: value of key 'bleh' was {$cf->get('bleh')}.");
$cf->set('item2', 5);
$cf->save();
unset($cf);
$cf = new Configfile('./test.ini', false, true);
if ($cf->get('item2') != 5)
    error("Configfile failed: value of 'item2' was ".var_dump($cf->get('item2'))
        .".");

echo "Globals as arrays...<br>";
unlink('./test.ini');
$cf = new Configfile('./test.ini', false, true);
set_manipulation($cf);
unset($cf);
$cf = new Configfile('./test.ini', false, true);
get_manipulation($cf);
unset($cf);
unlink('./test.ini');

echo "Manipulating values in a section...<br>\n";
$cf = new Configfile('./test.ini', true, true);
set_manipulation($cf);
$cf->save();
get_manipulation($cf);
echo "&nbsp;&nbsp;...after loading from file...<br>\n";
unset($cf);
$cf = new Configfile('./test.ini', true, true);
get_manipulation($cf);
unset($cf);
unlink('./test.ini');

echo "Testing section inheritance...<br>\n";
$cf = new Configfile('./test.ini', true, true);
set_manipulation($cf);
set_extends_manipulation($cf);
$cf->save();
get_manipulation($cf);
get_extends_manipulation($cf);
echo "&nbsp;&nbsp;...after loading from file...<br>\n";
unset($cf);
$cf = new Configfile('./test.ini', true, true);
get_manipulation($cf);
get_extends_manipulation($cf);
unset($cf);
//unlink('./test.ini');
// TODO: Test file locking?

echo "If you see no errors, all tests passed.<br>";
?>
