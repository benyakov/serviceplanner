<?

class Configfile{
    private $IniFile;
    private $IniData;
    private $HasSections;

    public function __construct($FileName, $HasSections=false) {
        $this->IniFile = $FileName;
        $this->HasSectons = $HasSections;
        if (! file_exists($FileName)) {
            touch($FileName);
        }
        $this->IniData = parse_ini_file($FileName, $HasSections);
    }

    public function get($Key) {
        if (array_key_exists($Key, $this->IniData)) {
            return $this->IniData[$Key];
        } else {
            return null;
        }
    }

    public function store($Key, $Value) {
        $this->IniData[$Key] = $Value;
    }

    private function writeVal($Val) {
        if (is_numeric($Val)) {
            return $Val;
        } elseif (false === strpos($Val, '"')) {
            return "\"{$Val}\"";
        } elseif (false === strpos($Val, "'")) {
            return "'{$Val}'";
        } else {
            return '"'.preg_replace('/"{1}/', '\"', $Val).'"';
        }
    }

    private function stringifySection($Ary) {
        $out = array();
        foreach ($Ary as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $out[] = "{$key}[{$k}] = ".writeVal($v);
                }
            } else {
                $out[] = "{$key} = ".writeVal($val);
            }
        }
        return implode("\r\n", $out);
    }

    private function rewriteWithLock($Contents) {
        if ($fh = fopen($this->IniFile, 'wb')) {
            $starttime = microtime();
            do {
                $writeable = flock($fh, LOCK_EX);
                if (!$writeable) usleep(round(rand(0, 100) * 1000));
            } while ((!$writeable) && ((microtime()-$starttime) < 1000));
            if ($writeable) {
                fwrite($fh, $Contents);
                flock($fh, LOCK_UN);
                fclose($fh);
                return true;
            } else {
                fclose($fh);
                return false;
            }
        } else {
            return false;
        }
    }

    public function save() {
        $out = array();
        foreach ($this->IniData as $key => $val) {
            if ($this->HasSections) {
                $out[] = "[{$key}]";
                $out[] = $this->stringifySection[$val];
            } else {
                $this->stringifySection[$val];
            }
        }
        return $this->rewriteWithLock(implode("\r\n", $out));
    }
}
?>
