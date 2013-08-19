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

    public function construct() {
        if (self::$instance) {
            return self::$instance;
        } else {
            require_once("utility/configfile.php");
            $cf = new ConfigFile("dbconnection.ini");
            self::$instance = $this;
            self::$connection = array(
                "host"=>$cf->get("dbhost"),
                "name"=>$cf->get("dbname"),
                "user"=>$cf->get("dbuser"),
                "password"=>$cf->get("dbpassword"));
            self::$handle = new PDO("mysql:host={$dbhost};dbname={$dbname}",
                "{$dbuser}", "{$dbpassword}");
            return self::$instance;
        }
    }
    public function __call($name, $args) {
        if ($this->handle)
          call_user_func_array($this->handle->$name, $args);
        else
          throw new Exception("DBConnection not set up.");
    }
    public static function __callStatic($name, $args) {
        if (self::$handle)
          call_user_func_array(self::$handle->$name, $args);
        else
          throw new Exception("DBConnection not set up.");
    }
}
