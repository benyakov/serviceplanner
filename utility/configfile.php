<?  /* Manage a configuration file
    Copyright (C) 2012 Jesse Jacobsen

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    Send feedback or donations to: Jesse Jacobsen <jmatjac@gmail.com>

    Mailed donation may be sent to:
    Bethany Lutheran Church
    2323 E. 12th St.
    The Dalles, OR 97058
    USA
 */

class Configfile{
    private $IniFile;
    private $IniData;
    private $HasSections;

    public function __construct($FileName, $HasSections=false) {
        $this->IniFile = $FileName;
        $this->HasSections = $HasSections;
        if (! file_exists($FileName)) {
            touch($FileName);
        }
        $this->IniData = parse_ini_file($FileName, $HasSections);
    }

    public function get($Key) {
        if (! (is_string($Key) or is_int($Key))) {
            echo var_dump($Key)."is an invalid configfile key. "
                ."Use a string or integer.";
            exit(1);
        }
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

    private function serializeSection($Ary) {
        $out = array();
        foreach ($Ary as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $out[] = "{$key}[{$k}] = ".$this->writeVal($v);
                }
            } else {
                $out[] = "{$key} = ".$this->writeVal($val);
            }
        }
        return implode("\n", $out);
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
        if ($this->HasSections) {
            foreach ($this->IniData as $key => $val) {
                    $out[] = "[{$key}]";
                    $out[] = $this->serializeSection($val);
            }
        } else {
            $out[] = $this->serializeSection($this->IniData);
        }
        return $this->rewriteWithLock(implode("\n", $out)."\n");
    }
}
?>
