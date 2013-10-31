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
class ConfigfileUnknownKey extends ConfigfileError { }

class Configsection
{
    private $ConfigKey;
    private $ConfigData;
    private $Extends;
    /**
     * $extend: A flag to suppress extending another section temporarily.
     */
    public  $extend = true;

    public function __construct($key, $structure, $extends=NULL) {
        $this->ConfigKey = $key;
        $this->ConfigData = $structure;
        $this->Extends = $extends;
    }

    /**
     * Allow reading of internals, like Extends
     */
    function __get($item) {
        switch ($item) {
        case 'Extends':
            return $this->Extends;
        default:
            throw new ConfigfileError("Unknown attribute: {$item}");
        }
    }

    /**
     * Return the configuration key for the extended section
     */
    public function getExtends() {
        if (isset($this->Extends))
            return $this->Extends->ConfigKey;
        else
            return NULL;
    }

    /**
     * Delete the existing extension
     */
    public function delExtends() {
        $this->setExtends(NULL);
    }

    /**
     * Change which section this one extends.  Expects a Configsection.
     */
    public function setExtends($configsection) {
        $this->Extends = $configsection;
    }

    /**
     * Set a value, provided as the last of at least two arguments.
     * The first series of arguments are progressive keys to the structure.
     */
    public function set() {
        $argcount = func_num_args();
        if ($argcount < 2)
            throw new ConfigfileError("Set needs at least 2 args.  "
                ."Got ".print_r(func_get_args(), true));
        $args = $origargs = func_get_args();
        $structure = &$this->ConfigData;
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
                throw new ConfigFileError("Can't set below a scalar.");
        }
        if ('[]' == $args[0])
            $structure[] = $args[1];
        elseif ($args[1] == NULL)
            unset($structure[$args[0]]);
        else
            $structure[$args[0]] = $args[1];
    }

    /**
     * Return the data structure for
     */
    public function dump($extend=false) {
        if ($extend)
            return array_replace_recursive($this->Extends->dump(),
                $this->ConfigData);
        else
            return $this->ConfigData;
    }

    /**
     * Return whether a given series of keys leads to a set value,
     * without defaulting to an extended section.
     */
    public function exists() {
        $args = func_get_args();
        try {
            call_user_func_array(Array($this, "get"), $args);
            return true;
        }
        catch (ConfigfileUnknownKey $e) {
            return false;
        }
    }

    /**
     * Get a value, providing either
     * - a key as a single argument for a top-level value/array
     * - a progressive series of keys as arguments for a deeper value/array
     * - an unknown key will default to an extended section, if it exists.
     */
    public function get() {
        if (func_num_args() < 1)
            throw new ConfigfileError("No key supplied to get");
        elseif (func_num_args() == 1) {
            $key = func_get_args();
            $key = $key[0];
            if (! (is_string($key) or is_int($key)))
                throw new ConfigfileError(
                    print_r($key, true)."is an invalid configfile key. "
                    ."Use a string or integer.");
            if (isset($this->ConfigData[$key]))
                return $this->ConfigData[$key];
            elseif ($this->extend && $this->_isExtendable()
                && $this->Extends->exists($key))
                return $this->Extends->get($key);
            else
                throw new ConfigfileUnknownKey(
                    "Unknown key in section {$this->ConfigKey}: ".$key);
        } else {
            $args = $origargs = func_get_args();
            //echo "\$args is ".print_r($args, true)."<br>\n"; // FIXME
            $data = $this->ConfigData;
            $used = Array();
            while (($key = array_shift($args))!==NULL) {
                $used[] = $key;
                $final = (count($args) == 0);
                if (is_array($data)){
                    if (isset($data[$key]))
                        if ($final)
                            return $data[$key];
                        else
                            $data = $data[$key];
                    elseif ($this->extend && $this->_isExtendable())
                        try {
                            return call_user_func_array(
                                Array($this->Extends, "get"), $origargs);
                        } catch (ConfigfileUnknownKey $e) {
                            throw new ConfigfileUnknownKey(
                                "With extended section in "
                                ."{$this->ConfigKey}: ".
                                $e->getMessage());
                        }
                    else {
                        throw new ConfigfileUnknownKey(
                            "Unknown key in section {$this->ConfigKey}: ".
                            implode(", ", $used));
                    }
                } else
                    throw new ConfigfileUnknownKey(
                        "Unknown key in section {$this->ConfigKey}: ".
                        implode(", ", $used));
            }
        }
    }

    private function _isExtendable() {
        return (is_object($this->Extends)
            && is_a($this->Extends, "Configsection"));
    }
}

class Configfile
{
    private $IniFile;
    private $IniData;
    private $SectionData = Array();
    private $HasSections;
    private $Sections = Array();
    private $Extensions = Array();

    public function __construct($FileName, $HasSections=false) {
        $this->IniFile = $FileName;
        $this->HasSections = $HasSections;
        if (! file_exists($FileName)) {
            touch($FileName);
            $this->IniData = Array();
        } else {
            $this->IniData = $this->_parse($FileName, $HasSections);
        }
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
            $key = func_get_args();
            $key = $key[0];
            echo "Key is $key<br>";
            if (! (is_string($key) or is_int($key)))
                if (is_array($key)) {
                    return call_user_func_array(Array($this, "get"), $key);
                } else
                    throw new ConfigfileError(
                        print_r($key, true)."is an invalid configfile key. "
                        ."Use a string or integer.");
            if (isset($this->IniData[$key]))
                return $this->IniData[$key];
            elseif ($this->HasSections && isset($this->Sections[$key])) {
                echo "Returning a section for $key<br>"; // FIXME
                return $this->SectionData[$key]->dump();
            } else
                return NULL;
        } elseif ($this->HasSections) {
            $args = func_get_args();
            $sectionname = array_shift($args);
            return call_user_func_array(Array($this->SectionData[$sectionname],
                "get"), $args);
        } else {
            $args = func_get_args();
            $data = $this->IniData;
            $used = Array();
            while (($key = array_shift($args)) !== NULL) {
                $used[] = $key;
                $final = (count($args) == 0);
                // Apply the key
                if (is_array($data)) {
                    if (isset($data[$key]))
                        if ($final)
                            return $data[$key];
                        else
                            $data = $data[$key];
                    else
                        throw new ConfigfileUnknownKey(
                            "Unknown global key: ".
                            implode(", ", $used));
                } else
                    throw new ConfigfileUnknownKey(
                        "Scalar value found; can't index: ".
                        implode(", ", $used));
            }
        }
    }

    /**
     * Return a section
     */
    public function getSection($Key) {
        if (in_array($Key, $this->Sections))
            return $this->SectionData[$Key];
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
     * Delete a key/value pair from the structure,
     * specified by arguments being a progressive set of keys
     * A convenience shortcut for ->set(key..., NULL)
     */
    public function del() {
        if (func_num_args() < 1)
            throw new ConfigfileError("del needs at least 1 arg.");
        $args = func_get_args();
        $args[] = NULL;
        call_user_func_array(Array($this, "set"), $args);
    }


    /**
     * Return a nested array with the inner value of the last argument.
     */
    private function _deepCreate($args) {
        $argcount = count($args);
        if ($argcount < 2)
            throw new ConfigfileError("deepCreate needs at least 2 args.");
        $rv = array_pop($args);
        while (($k = array_pop($args)) !== NULL)
            if ("[]" == $k)
                $rv = Array($rv);
            else
                $rv = Array($k => $rv);
        return $rv;
    }

    /**
     * Set a value, provided as the last of at least two arguments.
     * The first series of arguments are progressive keys to the structure.
     */
    public function set() {
        $argcount = func_num_args();
        $args = $origargs = func_get_args();
        if ($argcount < 2)
            if (is_array($args[0]))
                return call_user_func_array(Array($this, "set"), $args[0]);
            else
                throw new ConfigfileError("Set needs at least 2 args.");
        if ($this->HasSections && $argcount > 2) {
           if (in_array($args[0], $this->Sections)) {
               // Set in the configsection object
               call_user_func_array(Array($this->SectionData[$args[0]], 'set'),
                    array_slice($args, 1));
           } else {
               // Create a new configsection object.
               $k = array_shift($args);
               $args = $this->_deepCreate($args);
               $this->SectionData[$k] = new Configsection($k, $args);
               $this->Sections[] = $k;
           }
        } else {
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
                    throw new ConfigFileError("Can't set below a scalar.");
            }
            if ('[]' == $args[0])
                $structure[] = $args[1];
            elseif ($args[1] == NULL)
                unset($structure[$args[0]]);
            else
                $structure[$args[0]] = $args[1];
        }
    }

    /**
     * Return the name of another section extended by $section
     */
    public function getExtension($section) {
        return $this->Extensions[$section];
    }

    /**
     * Delete an extension of $source by $section
     */
    public function delExtension($section, $source) {
        if (isset($this->Extensions[$section]) &&
            $this->Extensions[$section] == $source)
        {
            $this->SectionData[$section]->delExtends();
            unset($this->Extensions[$section]);
        } else
            throw new ConfigfileError("Extension '{$section}:{$source}' does not exist.");
    }

    /**
     * Set $section as an extension of $source section
     */
    public function setExtension($section, $source) {
        if (! in_array($source, $this->Sections))
            throw new ConfigfileError("Source section '{$source}' not set.");
        if (! in_array($section, $this->Sections))
            throw new ConfigfileError("Section '{$section}' not set.");
        if (isset($this->Extends[$source])
            && $this->Extends[$source]==$section)
            throw new ConfigfileError("Setting $section to extend $source "
                ."would be a circular extension.");
        $this->SectionData[$section]->setExtends($this->SectionData[$source]);
        $this->Extensions[$section] = $source;
    }

    /**
     * Return a list of sections
     */
    public function getSections() {
        return $this->Sections;
    }

    /**
     * Save the file
     */
    public function save() {
        $out = array();
        foreach ($this->IniData as $key => $val)
            if (is_array($val)) {
                $out += $this->_recursiveWriteArrayAssign($key, $val);
            } else
                $out[] = $this->_writeSimpleAssign($key, $val);
        if ($this->HasSections) {
            foreach ($this->Sections as $section) {
                if (isset($this->Extensions[$section]))
                    $extension = " : {$this->Extensions[$section]}";
                else
                    $extension = "";
                $out[] = "[{$section}{$extension}]";
                $out[] = $this->_serializeSection(
                    $this->SectionData[$section]->dump(), $section);
            }
        }
        return $this->_rewriteWithLock(implode("\n", $out)."\n");
    }

    /**
     * Exchange the values of the two coordinate keys at the given location.
     */
    public function transpose($location, $key1, $key2) {
        $parent = $this->get($location);
        echo "Parent is '".print_r($parent, true)."'<br>";
        if (! (is_array($parent) && array_key_exists($key1, $parent)
            && array_key_exists($key2, $parent)))
            throw new ConfigfileError("Can't transpose {$key1} and {$key2} "
                ."at ".print_r($location, true));
        $loc1 = array_merge($location, Array($key1));
        $loc2 = array_merge($location, Array($key2));
        $val1 = $this->get($loc1);
        $set1 = array_merge($location, Array($key1, $this->get($loc2)));
        $this->set($set1);
        $set2 = array_merge($location, Array($key2, $val1));
        $this->set($set2);
    }

    /**
     * Parse with extensions
     */
    private function _parse($filename, $process_sections=true) {
        $ini = parse_ini_file($filename, $process_sections);
        if ($ini === false)
            throw new ConfigfileError('Unable to parse ini file.');
        // Process sections first
        if ($this->HasSections) {
            foreach ($ini as $section => $values) {
                if (!is_array($values)) continue;
                unset($ini[$section]);
                $expand = explode(':', $section);
                if (count($expand) == 2) {
                    $section = trim($expand[0]);
                    $source = trim($expand[1]);
                    $this->Extensions[$section] = $source;
                    $this->SectionData[$section] =
                        new Configsection($section,
                            $this->_processSection($values), $source);
                } else {
                    $this->SectionData[$section] =
                        new Configsection($section,
                            $this->_processSection($values));
                }
                $this->Sections[] = $section;
            }
            foreach ($this->SectionData as $section) {
                if (NULL == $section->Extends) continue;
                if (isset($this->SectionData[$section->Extends]))
                    $section->setExtends($this->SectionData[$section->Extends]);
                else
                    throw new ConfigfileError("Undefined source section: '"
                        .$section->Extends."'.");
            }
        } else
            $ini = $this->_processSection($ini);
        return $ini;
    }

    /**
     * Parse a single section with values.
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
     * Parse: Create the values recursively.
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
     * Parse: Merge a value with the previous value, as an array if needed.
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
     * Parse: Recursively merge arrays
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
     * Write: Return a formatted scalar value
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
     * Write: Return a simple value assignment
     */
    private function _writeSimpleAssign($key, $val) {
        return "{$key} = ".$this->_writeVal($val);
    }

    /**
     * Make section into a string for saving and return it.
     * The optional $section argument will allow trimming
     * overlap already included in extended sections.
     */
    private function _serializeSection($sectdata, $section="") {
        $out = Array();
        foreach ($sectdata as $key => $val)
            if (is_array($val))
                $out = array_merge($out,
                    $this->_recursiveWriteArrayAssign($key, $val));
            else
                $out[] = $this->_writeSimpleAssign($key, $val);
        return implode("\n", $out);
    }

    /**
     * Return formatted array assignments in an array of assignment lines
     */
    private function _recursiveWriteArrayAssign($key, $val, $pre="", $join="")
    {
        if (!is_array($val)) {
            if (is_numeric($key) && ($key == (int) $key))
                return Array("{$pre}[] = {$this->_writeVal($val)}");
            else
                return Array("{$pre}{$join}{$key} = {$this->_writeVal($val)}");
        } else {
            $rv = Array();
            foreach ($val as $k=>$v) {
                $result = $this->_recursiveWriteArrayAssign($k, $v,
                    "{$pre}{$join}{$key}", ".");
                $rv = array_merge($rv, $result);
            }
        }
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

// vim: set foldmethod=indent :
?>
