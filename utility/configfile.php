<?php
/* Manage a configuration file
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

class ConfigfileError extends Exception { }

class Configfile implements ArrayAccess
{
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

    public function debugData() {
        print_r($this->IniData);
    }

    public function &get($Key) {
        if (! (is_string($Key) or is_int($Key))) {
            echo var_dump($Key)."is an invalid configfile key. "
                ."Use a string or integer.";
            exit(1);
        }
        if (isset($this->IniData[$Key])) {
            return $this->IniData[$Key];
        } else {
            return null;
        }
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function store($Key, $Value) {
        $this->IniData[$Key] = $Value;
    }

    public function deepSet() {
        if ($this->HasSections) $max_args = 4;
        else $max_args = 3;
        if (func_num_args() < 2 || func_num_args() > $max_args)
            throw new ConfigfileError("deepSet needs 2 to $max_args args.");
        // Note: It will create deeper arrays with more args,
        // but ini file syntax won't handle it.
        $args = func_get_args();
        $structure = &$this->IniData;
        while (count($args) > 2) {
            $k = array_shift($args);
            if (is_array($structure)) {
                if (isset($structure[$k])) {
                    $temp = &$structure[$k];
                    unset($structure);
                    $structure = &$temp;
                    unset($temp);
                } else {
                    $structure[$k] = array();
                    $temp = &$structure[$k];
                    unset($structure);
                    $structure = &$temp;
                    unset($temp);
                }
            } else
                throw new ConfigFileError("Can't deepSet below a scalar.");
        }
        if ('[]' == $args[0])
            $structure[] = $args[1];
        else
            $structure[$args[0]] = $args[1];
    }

    public function offsetSet($offset, $value) {
        $this->store($offset, $value);
    }

    public function offsetExists($offset) {
        return isset($this->IniData[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->IniData[$offset]);
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

    private function writeSimpleValue($key, $val) {
        return "{$key} = ".$this->writeVal($val);
    }

    private function writeArrayValue($key, $val) {
        $out = array();
        foreach ($val as $k => $v) {
            $out[] = "{$key}[{$k}] = ".$this->writeVal($v);
        }
        return implode("\n", $out);
    }

    private function serializeSection($Ary) {
        $out = array();
        foreach ($Ary as $key => $val) {
            if (is_array($val)) {
                $out[] = $this->writeArrayValue($key, $val);
            } else {
                $out[] = $this->writeSimpleValue($key, $val);
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
                if (is_array($val)) {
                    $out[] = "[{$key}]";
                    $out[] = $this->serializeSection($val);
                } else {
                    $out[] = $this->writeSimpleValue($key, $val);
                }
            }
        } else {
            $out[] = $this->serializeSection($this->IniData);
        }
        return $this->rewriteWithLock(implode("\n", $out)."\n");
    }
}

?>
