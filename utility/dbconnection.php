<?php /* Singleton DB Connection object
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

class DBConnection {
    private static $instance;
    public static $connection;
    private static $handle;
    public static $prefix;

    public function __construct($pathtoroot="") {
        if (self::$instance) {
            return self::$instance;
        } else {
            self::$instance = $this;
            $this->setupConnection($pathtoroot);
            return self::$instance;
        }
    }
    private function setupConnection($pathtoroot) {
        if ($pathtoroot) chdir($pathtoroot);
        require_once("./utility/configfile.php");
        $cf = new ConfigFile("dbconnection.ini");
        self::$connection = array(
            "host"=>$cf->get("dbhost"),
            "name"=>$cf->get("dbname"),
            "user"=>$cf->get("dbuser"),
            "password"=>$cf->get("dbpassword"),
            "prefix"=>$cf->get("prefix"));
        list($dbhost, $dbname, $dbuser, $dbpassword, $prefix) = array(
            $cf->get("dbhost"), $cf->get("dbname"),
            $cf->get("dbuser"), $cf->get("dbpassword"),
            $cf->get("prefix"));
        self::$handle = new PDO("mysql:host={$dbhost};dbname={$dbname}",
            "{$dbuser}", "{$dbpassword}");
        // This was the default behavior prior to PHP 8. After that DBO assumes
        // an exception-handling style of programming, which is generally not used here.
        self::$handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    }
    public function getHost() {
        return self::$connection['host'];
    }
    public function getName() {
        return self::$connection['name'];
    }
    public function getUser() {
        return self::$connection['user'];
    }
    public function getPassword() {
        return self::$connection['password'];
    }
    public function getPrefix() {
        return self::$connection['prefix'];
    }
    public function quote() {
        return call_user_func_array(array(self::$handle, "quote"), func_get_args());
    }
    public function __call($name, $args) {
        if (! self::$handle)
            $this->setupConnection();
        return call_user_func_array(array(self::$handle, $name), $args);
    }
}
