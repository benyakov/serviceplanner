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

class Configfile
{
    private $IniFile;
    private $IniData;
    private $HasSections;
    private $Sections = Array();
    private $Extensions = Array();

    public function __construct($FileName, $HasSections=false,
        $RequestSect=false)
    {
        $this->IniFile = $FileName;
        $this->HasSections = $HasSections;
        if (! file_exists($FileName)) {
            touch($FileName);
        }
        $this->IniData = $this->_parse($FileName, $HasSections, $RequestSect);
    }

    public function debugData() {
        print_r($this->IniData);
    }

    /**
     * Get a value, providing either
     * - a key as a single argument for a top-level value/array
     * - a progressive series of keys as arguments for a deeper value/array
     */
    public function get() {
        if (func_num_args() < 1)
            throw new ConfigfileError("No key supplied to get");
        elseif (func_num_args() == 1) {
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
        } else {
            $args = func_get_args();
            $data = $this->IniData;
            $used = Array();
            while ($arg = shift($args))
                $used[] = $arg;
                if (is_array($data) && isset($data[$arg]))
                    $data = $data[$arg];
                else
                    throw new ConfigfileError("Unknown key: ".
                        implode(", ", $used));
        }
    }

    /**
     * Return a reference to a section
     */
    public function &getSection($Key) {
        if (in_array($Key, $this->Sections))
            return $this->IniData[$Key];
        else
            throw new ConfigfileError("Unknown section: {$Key}");
    }

    /**
     * Force a value onto the top level of the structure
     */
    public function store($Key, $Value) {
        $this->IniData[$Key] = $Value;
    }

    /**
     * Set a value, provided as the last of at least two arguments.
     * The first series of arguments are progressive keys to the structure.
     */
    public function set() {
        if (func_num_args() < 2)
            throw new ConfigfileError("deepSet needs at least 2 args.");
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

    /**
     * Parse with extensions
     */
    private function _parse($filename, $process_sections = true,
        $getsection = null)
    {
        $ini = parse_ini_file($filename, $process_sections);
        if ($ini === false)
            throw new ConfigfileError('Unable to parse ini file.');
        if (!$process_sections && $getsection) {
            $values = $process_sections ? $ini[$getsection] : $ini;
            $result = $this->_processSection($values);
            $this->Sections[] = $getsection;
        } else {
            $result = array();
            foreach ($ini as $section => $values) {
                if (!is_array($values)) continue;
                unset($ini[$section]);
                $expand = explode(':', $section);
                if (count($expand) == 2) {
                    $section = trim($expand[0]);
                    $source = trim($expand[1]);
                    if (!isset($result[$source]))
                        throw new ConfigfileError("No $source to expand $section");
                    $sectionResult = $this->_processSection($values);
                    $result[$section] = $this->_mergeRecursive($result[$source], $sectionResult);
                    $this->Extensions[$section] = $source;
                } else
                    $result[$section] = $this->_processSection($values);
                $this->Sections[] = $section;
            }
            $result += $ini;
        }
        return $result;
    }

    /**
     * Save the file
     */
    public function save() {
        $out = array();
        if ($this->HasSections)
            foreach ($this->IniData as $key => $val)
                if (is_array($val))
                    if (in_array($key, $this->Sections)) {
                        if (isset($this->Extensions[$key]))
                            $extension = ":{$this->Extensions[$key]}";
                        else
                            $extension = "";
                        $out[] = "[{$key}{$extension}]";
                        $out[] = $this->_serializeSection($val, $key);
                    } else
                        $out += _recursiveWriteArrayAssign($key, $val);
                else
                    $out[] = $this->_writeSimpleAssign($key, $val);
        else $out[] = $this->_serializeSection($this->IniData);
        return $this->_rewriteWithLock(implode("\n", $out)."\n");
    }

    /**
     * Process a single section with values.
     */
    private function _processSection($values) {
        $result = array();
        foreach ($values as $key => $value) {
            $keys = explode('.', $key);
            $result = $this->_recurseValue($result, $keys, $value);
        }
        return $result;
    }

    /**
     * Create the values recursively.
     */
    private function _recurseValue($array, $keys, $value) {
        $key = array_shift($keys);
        if (count($keys) > 0) {
            if (!isset($array[$key]))
                $array[$key] = array();
            $array[$key] = $this->_recurseValue($array[$key], $keys, $value);
        } else
            $array = $this->_mergeValue($array, $key, $value);
        return $array;
    }

    /**
     * Merge a value with the previous value, as an array if needed.
     */
    private function _mergeValue($array, $key, $value) {
        if (!isset($array[$key]))
            $array[$key] = $value;
        else {
            if (is_array($value))
                $array[$key] += $value;
            else
                $array[$key][] = $value;
        }
        return $array;
    }

    /**
     * Recursively merge arrays
     */
    private function _mergeRecursive($left, $right) {
        // merge arrays if both variables are arrays
        if (is_array($left) && is_array($right))
            // loop through each right array's entry and merge it into $a
            foreach ($right as $key => $value)
                if (isset($left[$key]))
                    $left[$key] = $this->_mergeRecursive($left[$key], $value);
                else
                    $left[$key] = $value;
        else // one of values is not an array
            $left = $right;
        return $left;
    }

    /**
     * Return a formatted scalar value
     */
    private function _writeVal($Val) {
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

    /**
     * Return a simple value assignment
     */
    private function _writeSimpleAssign($key, $val) {
        return "{$key} = ".$this->_writeVal($val);
    }

    /**
     * Make section into a string for saving and return it.
     * The optional $section argument will allow trimming
     * overlap already included in extended sections.
     */
    private function _serializeSection($ary, $section="") {
        $out = Array();
        if (isset($this->Extensions[$section]))
            $check = $this->IniData[$this->Extensions[$section]];
        else
            $check = Array();
        foreach ($ary as $key => $val)
            if (isset($check[$key]) && $check[$key] == $val)
                continue;
            if (is_array($val))
                $out += $this->_recursiveWriteArrayAssign($key, $val);
            else
                $out[] = $this->_writeSimpleAssign($key, $val);
        return implode("\n", $out);
    }

    /**
     * Return formatted array assignments in an array of assignment lines
     */
    private _recursiveWriteArrayAssign($key, $val) {
        $rv = Array();
        if (is_array($val)) {
            $subassignments = Array();
            foreach ($val as $k=>$v)
                foreach ($this->recursiveWriteArrayAssign($k, $v) as $newline)
                   $rv[] = "{$key}.".$newline
        } elseif (is_numeric($key) && ($key == (int) $key))
            $rv[] = "[{$key}] = ".$this->_writeVal($val);
        else
            $rv[] = "{$key} = ".$this->_writeVal($val);
        return $rv;
    }

    /**
     * Write out the file with a file lock
     */
    private function _rewriteWithLock($Contents) {
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
}

?>
