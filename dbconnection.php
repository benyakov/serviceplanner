<?/* Singleton DB Connection object
    Copyright (C) 2013 Jesse Jacobsen

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

class DBConnection {
    private static $instance;
    public static $connection;
    private static $handle;
    public static $prefix;

    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        } else {
            self::$instance = $this;
            $this->setupConnection();
            return self::$instance;
        }
    }
    private function setupConnection() {
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
    }
    public function getPrefix() {
        return self::$connection['prefix'];
    }
    public function __call($name, $args) {
        if (! self::$handle)
            $this->setupConnection();
        return call_user_func_array(array(self::$handle, $name), $args);
    }
}
