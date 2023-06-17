<?php /* Manage a configuration file
    Copyright (C) 2023 Jesse Jacobsen

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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */

/*** Handy for debugging
* $logfile = fopen("somefile.log", "a+");
* function _log($text) {
*     global $logfile;
*     fwrite($logfile, "(cf) ".$text."\n");
* }
***/

class ConfigfileError extends Exception { }
class ConfigfileUnknownKey extends ConfigfileError { }
class ConfigfileSaveError extends ConfigfileError { }

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
    public function __get($item) {
        switch ($item) {
        case 'Extends':
            return $this->Extends;
        default:
            throw new ConfigfileError("Unknown attribute: {$item}");
        }
    }

    /**
     * Rename this section's configuration key
     */
    public function rename($newname) {
        $this->ConfigKey = $newname;
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
        elseif ($args[1] === NULL)
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
        $extend = $this->extend;
        $this->extend = false;
        try {
            call_user_func_array(Array($this, "get"), $args);
            $this->extend = $extend;
            return true;
        } catch (ConfigfileUnknownKey $e) {
            $this->extend = $extend;
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
    private $IniFP = NULL;
    private $Locktype;
    private $IniFile;
    private $IniData = array();
    private $SectionData = array();
    private $HasSections;
    private $RawValues;
    private $Sections = array();
    private $Extensions = array();

    /*** Handy for debugging with object members
    * private function _chk($txt) {
    *     if (count($this->Sections) > 2) {
    *         _log($txt.print_r($this->Sections, true));
    *     } else {
    *         _log("Normal!".print_r($this->Sections, true));
    *     }
    * }
    ***/

    public function __construct($FileName, $HasSections=false, $RawValues=true,
        $WriteLock=true)
    {
        $this->IniFile = $FileName;
        if (is_array($HasSections)) {
            $params = $HasSections;
            $this->HasSections = isset($params['hasSections'])?
                (bool) $params['hasSections']:false;
            $this->RawValues = isset($params['rawValues'])?
                (bool) $params['rawValues']:false;
            if (isset($params['writeLock']))
                $this->Locktype = $params['writeLock']===true?$this->Locktype=LOCK_EX
                    :$this->Locktype=LOCK_SH;
            else $this->Locktype = $this->Locktype=LOCK_EX;
        } else {
            $this->HasSections = $HasSections;
            $this->RawValues = $RawValues;
            if ($WriteLock)
                $this->Locktype=LOCK_EX;
            else
                $this->Locktype=LOCK_SH;
        }
        if (! file_exists($FileName)) {
            touch($FileName);
            $this->_openWithLock($this->IniFile);
        } else {
            $this->IniData = $this->_parse();
        }
    }

    public function __destruct() {
        if ($this->IniFP != NULL) {
            flock($this->IniFP, LOCK_UN);
            fclose($this->IniFP);
        }
    }

    /**
     * Get a value, providing either
     * - a key as a single argument for a top-level value/array
     * - a progressive series of keys as arguments for a deeper value/array
     */
    public function get() {
        $argcount = func_num_args();
        $args = func_get_args();
        if ($argcount < 1)
            throw new ConfigfileError("No key supplied to get");
        elseif ($argcount == 1) {
            if (is_array($args[0])) // Take single array arg as address
                return call_user_func_array(Array($this, "get"), $args[0]);
        }
        foreach ($args as $key)
            if (! ((is_string($key) && $key!="") or is_int($key)))
                throw new ConfigfileError("'"
                    .print_r($key, true)."' is an invalid configfile key. "
                    ."Use a non-empty string or integer.");
        if ($argcount == 1) {
            $key = $args[0];
            if (is_array($key)) // Take single array arg as address
                return call_user_func_array(Array($this, "get"), $key);
            if (isset($this->IniData[$key]))
                return $this->IniData[$key];
            elseif ($this->HasSections && in_array($key, $this->Sections)) {
                return $this->SectionData[$key]->dump();
            } else {
                throw new ConfigfileUnknownKey(print_r($key, true));
            }
        } elseif ($this->HasSections) {
            $sectionname = array_shift($args);
            if (isset($this->SectionData[$sectionname]))
                return call_user_func_array(
                    Array($this->SectionData[$sectionname], "get"), $args);
            else
                throw new ConfigfileUnknownKey(print_r($sectionname, true));
        } else {
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
     * Return whether a given series of keys leads to a set value.
     */
    public function exists() {
        $args = func_get_args();
        try {
            call_user_func_array(Array($this, "get"), $args);
            return true;
        } catch (ConfigfileUnknownKey $e) {
            return false;
        }
    }

    /**
     * Return a value like get, using the second+ arguments,
     * but if the value does not exist,
     * return the value of the first argument as default
     */
    public function getDefault() {
        $argcount = func_num_args();
        $args = func_get_args();
        if ($argcount < 2)
            throw new ConfigfileError("Insufficient argcount for getDefault");
        $default = array_shift($args);
        if (call_user_func_array(Array($this, "exists"), $args))
            return call_user_func_array(Array($this, "get"), $args);
        else
            return $default;
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
     * Remove a section
     */
    public function delSection($Key) {
        if (in_array($Key, $this->Sections)) {
            unset($this->SectionData[$Key]);
            array_splice($this->Sections,
                array_search($Key, $this->Sections), 1);
        } else
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
        elseif (func_num_args() == 1 && $this->HasSections
            && in_array(func_get_arg(0), $this->Sections))
            $this->delSection(func_get_arg(0));
        else {
            $args = func_get_args();
            $args[] = NULL;
            call_user_func_array(Array($this, "set"), $args);
        }
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
     * Helper method to create a new section.
     */
    public function createSection($sectname) {
        if (! $this->HasSections)
            throw new ConfigfileError("Can't create a section in a file without them.");
        if ($this->exists($sectname)) {
            $tmp = $this->get($sectname);
            $this->del($sectname);
            $this->createSection($sectname);
            $this->set($sectname, $tmp);
        } else {
            $this->set($sectname, "@@createsection@@", true);
            $this->del($sectname, "@@createsection@@");
        }
    }

    /**
     * Add a section at $k with $data
     */
    private function _addSection($k, $data, $source=NULL) {
        $this->SectionData[$k] = new Configsection($k, $data, $source);
        if (! in_array($k, $this->Sections))
            $this->Sections[] = $k;
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
        if ($this->HasSections &&
            ($argcount > 2 || ($argcount == 2 && is_array($args[1]))))
        {
            if (in_array($args[0], $this->Sections) && $argcount > 2) {
                // Set in the configsection object
                call_user_func_array(Array($this->SectionData[$args[0]], 'set'),
                     array_slice($args, 1));
            } else {
                // Create a new configsection object.
                $k = array_shift($args);
                if (is_array($args[0]))
                    $args = $args[0];
                else
                    $args = $this->_deepCreate($args);
                $this->_addSection($k, $args);
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
            elseif ($args[1] === NULL)
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
        if ($this->Locktype != LOCK_EX)
            throw new ConfigfileError("Can't save. File lock not exclusive.");
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
                    $this->SectionData[$section]->dump());
            }
        }
        return $this->_writeWithLock(implode("\n", $out)."\n");
    }

    /**
     * Exchange the objects behind the given section keys.
     */
    private function _swapSections($key1, $key2) {
        $hold = $this->SectionData[$key1];
        $this->SectionData[$key1] = $this->SectionData[$key2];
        $this->SectionData[$key2] = $hold;
        $this->SectionData[$key1]->rename($key1);
        $this->SectionData[$key2]->rename($key2);
    }

    /**
     * Exchange the keys between a section and a global value
     */
    private function _swapGlobalWithSection($globalkey, $sectionkey) {
        $this->SectionData[$globalkey] = $this->SectionData[$sectionkey];
        $this->SectionData[$globalkey]->rename($globalkey);
        $this->IniData[$sectionkey] = $this->IniData[$globalkey];
        unset($this->SectionData[$sectionkey]);
        unset($this->IniData[$globalkey]);
    }

    /**
     * Exchange keys between two values which are not section objects
     */
    private function _swapNonSectionKeys($prefix, $key1, $key2) {
        $loc1 = array_merge($prefix, Array($key1));
        $loc2 = array_merge($prefix, Array($key2));
        $val1 = $this->get($loc1);
        $set1 = array_merge($prefix, Array($key1, $this->get($loc2)));
        $this->set($set1);
        $set2 = array_merge($prefix, Array($key2, $val1));
        $this->set($set2);
    }

    /**
     * Exchange the values of the two coordinate keys at the given location.
     */
    public function transpose($location, $key1, $key2) {
        $parent = $this->get($location);
        $key1addr = array_merge($location, Array($key1));
        $key2addr = array_merge($location, Array($key2));
        if (! (is_array($parent) && $this->exists($key1addr)
            && $this->exists($key2addr)))
            throw new ConfigfileError("Can't transpose {$key1} and {$key2} "
                ."at ".print_r($location, true));
        if (0 == count($location) && $this->HasSections) {
            if (in_array($key1, $this->Sections))
                if (in_array($key2, $this->Sections))
                    $this->_swapSections($key1, $key2);
                else
                    $this->_swapGlobalWithSection($key2, $key1);
            else
                if (in_array($key2, $this->Section))
                    $this->_swapGlobalWithSection($key1, $key2);
                else
                    $this->_swapNonSectionKeys(Array(), $key1, $key2);
        } else
            $this->_swapNonSectionKeys($location, $key1, $key2);
    }

    /**
     * Parse with extensions
     */
    private function _parse()
    {
        $this->Sections = array();
        $this->SectionData = array();
        $this->Extensions = array();
        $this->IniData = array();
        if ($this->RawValues) $rawflag = INI_SCANNER_RAW;
        else $rawflag = INI_SCANNER_NORMAL;
        if ($this->_openWithLock($this->IniFile)) {
            $fstat = fstat($this->IniFP);
            if ($fstat['size'] > 0) {
                $ini = parse_ini_string(fread($this->IniFP, $fstat['size']),
                    $this->HasSections, $rawflag);
            } else {
                $ini = Array();
            }
        } else
            throw new ConfigfileError("Couldn't get config file lock.");
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
                    $this->_addSection($section, $this->_processSection($values), $source);
                } else {
                    $this->_addSection($section, $this->_processSection($values));
                }
            }
            foreach ($this->SectionData as $section) {
                if (NULL == $section->Extends) continue;
                if (isset($this->SectionData[$section->Extends]))
                    $section->setExtends($this->SectionData[$section->Extends]);
                else
                    throw new ConfigfileError("Undefined source section: '"
                        .$section->Extends."'.");
            }
        } else {
            $ini = $this->_processSection($ini);
        }
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
        if (is_string($Val) && strpos($Val, ";") !== false)
            return "\"$Val\"";
        else
            return $Val;
    }

    /**
     * Write: Return a simple value assignment
     */
    private function _writeSimpleAssign($key, $val) {
        return "{$key} = ".$this->_writeVal($val);
    }

    /**
     * Make section into a string for saving and return it.
     * TODO: The optional $section argument will allow trimming
     * overlap already included in extended sections.
     */
    private function _serializeSection($sectdata) {
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
            if (is_int($key))
                return Array("{$pre}[] = {$this->_writeVal($val)}");
            else
                return Array("{$pre}{$join}{$key} = {$this->_writeVal($val)}");
        } else {
            $rv = Array();
            ksort($val);
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
    private function _writeWithLock($Contents) {
        fseek($this->IniFP, 0);
        ftruncate($this->IniFP, 0);
        $result = fwrite($this->IniFP, $Contents);
        // Close (to allow others to open) and reopen
        flock($this->IniFP, LOCK_UN);
        fclose($this->IniFP);
        $this->IniFP = NULL;
        // Save for integrity check
        $iChk = array(
            "Sections" => $this->Sections,
            "SectionData" => $this->SectionData,
            "Extensions" => $this->Extensions,
            "IniData" => $this->IniData);
        $this->IniData = $this->_parse();
        if (! ($iChk["Sections"]    == $this->Sections))
        {
            print_r($iChk['Sections']);
            print_r($this->Sections);
            throw new ConfigfileSaveError(
                "Error Saving Sections to Configfile: Possible Data Loss!");
        }
        // Checking these as serial json to relax the comparison standards
        if (! (json_encode($iChk["SectionData"]) == json_encode($this->SectionData)))
        {
            echo "<pre>\n";
            print_r($iChk['SectionData']);
            print_r($this->SectionData);
            echo "</pre>\n";
            throw new ConfigfileSaveError(
                "Error Saving SectionData to Configfile: Possible Data Loss!");
        }
        if (! ($iChk["Extensions"]  == $this->Extensions))
        {
            print_r($iChk['Extensions']);
            print_r($this->Extensions);
            throw new ConfigfileSaveError(
                "Error Saving Extensions to Configfile: Possible Data Loss!");
        }
        if (! ($iChk["IniData"]     == $this->IniData) )
        {
            print_r($iChk['IniData']);
            print_r($this->IniData);
            throw new ConfigfileSaveError(
                "Error Saving IniData to Configfile: Possible Data Loss!");
        }
        return $result;
    }

    private function _openWithLock($filename) {
        if ($this->Locktype == LOCK_SH) $fileMode = "r";
        else $fileMode = "r+";
        $this->IniFP = fopen($this->IniFile, $fileMode);
        if (flock($this->IniFP, $this->Locktype))
            return true;
        else
            return false;
    }
}

// vim: set foldmethod=indent :
?>
