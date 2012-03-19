<?php

function auth($login = '', $passwd = '') {
    global $dbp, $sprefix, $dbh;
	$authdata = $_SESSION[$sprefix]['authdata'];
	if (is_array($authdata) && (empty($login))) {
        $q = $dbh->prepare("SELECT * FROM `{$dbp}users`
            WHERE `username` = :login AND `password` = :password
            AND `uid` = :uid AND `userlevel` = :userlevel
            AND CONCAT_WS(' ', `fname`, `lname`) == :fullname");
        $q->bindParam(':login', $authdata["login"]);
        $q->bindParam(':password', $authdata["password"]);
        $q->bindParam(':uid', $authdata["uid"]);
        $q->bindParam(':userlevel', $authdata["userlevel"]);
        $q->bindParam(':fullname', $authdata["fullname"]);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($q->fetch()) {
            return true;
        } else {
            return false;
        }
	} elseif (!empty($login)) {
		$check = $login;
        $q = $dbh->prepare("SELECT * FROM `{$dbp}users`
            WHERE `username` = :check");
        $q->bindParam(':check', $check);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ( $row["password"] == crypt($passwd, $row["password"]) ) {
                $_SESSION[$sprefix]["authdata"] = array(
                    "fullname"=>"{$row['fname']} {$row['lname']}",
                    "login"=>$row["username"],
                    "password"=>$row["password"],
                    "uid"=>$row["uid"],
                    "userlevel"=>$row["userlevel"]);
            return true;
        } else {
            unset( $_SESSION[$sprefix]['authdata'] );
            return false;
        }
	} else {
		return false;
	}
}

function authId($authdata=false) {
    // Return the current username from parameter or session, or false
    global $sprefix;
    $authdata = $authdata?$authdata:
        (array_key_exists('authdata', $_SESSION[$sprefix])?
            $_SESSION[$sprefix]['authdata']:0);
	if ( is_array( $authdata ) ) {
		return $authdata['fullname'];
    } else {
        return false;
    }
}

function checkCorsAuth() {
    if ($_SERVER['HTTP_ORIGIN']) {
        $corsfile = explode("\n", file_get_contents("corsfile.txt"));
        if ($_SERVER['HTTP_HOST'] == $_SERVER['HTTP_ORIGIN']) {
            return false;
        } elseif ($corsfile && in_array($_SERVER['HTTP_ORIGIN'], $corsfile)) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            return true;
        } else {
            ?><!DOCTYPE=html>
            <html lang="en">
            <head><title>Access Denied</title></head>
            <body><p><?=$_SERVER['HTTP_ORIGIN']?> is not set up for a CORS
            mashup.  If you can log in to
            <?="{$_SERVER['HTTP_HOST']}/{$serverdir}/admin.php"?>,
            then you need to save "<?=$_SERVER['HTTP_ORIGIN']?>" in the form box
            under the heading "Mashing up pages from here into your own web
            site."</p></body></html>
            <?
            exit(0);
        }
    } else return false;
}

function checkJsonpReq() {
    return $_GET['jsonpreq'];
}

function display_records_table($q) {
    // Show a table of the data in the query $result
    ?><table id="records-listing">
        <tr class="heading"><th>Date &amp; Location</th><th colspan=2>Liturgical Day Name: Service/Rite</th></tr>
        <tr><th>Book &amp; #</th><th>Note</th><th>Title</th></tr>
    <?
    $date = "";
    $name = "";
    $location = "";
    $rowcount = 1;
    $inarticle = false;
    while ($row = $q->fetch(PDO::FETCH_ASSOC)); {
        if (!  ($row['date'] == $date &&
                $row['dayname'] == $name &&
                $row['location'] == $location))
        {// Display the heading line
            if (is_within_week($row['date']))
            {
                $datetext = "<a name=\"now\">{$row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            if ($inarticle) {
                echo "</article>\n";
            }
            echo "<article>\n";
            echo "<tr class=\"heading\"><td>{$datetext} {$row['location']}</td>
                <td colspan=2>{$row['dayname']}: {$row['rite']}</td></tr>\n";
            if ($row['servicenotes']) {
                echo "<tr class=\"heading\">".
                     "<td colspan=3 class=\"servicenote\">".
                     translate_markup($row['servicenotes'])."</td></tr>\n";
            }
            $date = $row['date'];
            $name = $row['dayname'];
            $location = $row['location'];
        }
        // Display this hymn
        if (0 == $rowcount % 2) {
            $oddness = " class=\"even\"";
        } else {
            $oddness = "";
        }
        echo "<tr{$oddness}><td class=\"hymn-number\">{$row['book']} {$row['number']}</td>
            <td class=\"note\">{$row['note']}</td><td class=\"title\">{$row['title']}</td>";
        $rowcount += 1;
    }
    echo "</article>\n";
    echo "</table>\n";
}


function modify_records_table($q, $action) {
    // Show a table of the data in the query $q
  // with links to edit each record, and checkboxes to delete records.
    ?><form action="<?=$action?>" method="POST">
      <input type="submit" value="Delete"><input type="reset" value="Clear">
      <table id="modify-listing">
        <tr class="heading"><th>Date &amp; Location</th><th colspan=2>Liturgical Day Name: Service/Rite</th></tr>
        <tr><th>Book &amp; #</th><th>Note</th><th>Title</th></tr>
    <?
    $date = "";
    $name = "";
    $location = "";
    $rowcount = 1;
    $inarticle = false;
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if (!  ($row['date'] == $date &&
                $row['dayname'] == $name &&
                $row['location'] == $location)) {
            // Display the heading line
            if (is_within_week($row['date']))
            {
                $datetext = "<a name=\"now\">{$row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            $urldate=urlencode($row['date']);
            if ($inarticle) {
                echo "</article>\n";
            }
            echo "<article>\n";
            $inarticle = true;
            echo "<tr class=\"heading\"><td>
            <input type=\"checkbox\" name=\"{$row['id']}_{$row['location']}\" id=\"check_{$row['id']}_{$row['location']}\">
            {$datetext} <a href=\"enter.php?date={$urldate}\" title=\"Add another service or hymns on {$row['date']}.\">[add]</a> {$row['location']}</td>
            <td colspan=2><a href=\"edit.php?id={$row['id']}\">Edit</a> |
            <a href=\"sermon.php?id={$row['id']}\">Sermon</a> |
            {$row['dayname']}: {$row['rite']}</td></tr>\n";
            $date = $row['date'];
            $name = $row['dayname'];
            $location = $row['location'];
            if ($row['servicenotes']) {
                echo "<tr class=\"heading\">".
                     "<td colspan=3 class=\"servicenote\">".
                     translate_markup($row['servicenotes'])."</td></tr>\n";
            }
        }
        // Display this hymn
        if (0 == $rowcount % 2) {
            $oddness = " class=\"even\"";
        } else {
            $oddness = "";
        }
        echo "<tr{$oddness}><td class=\"hymn-number\">{$row['book']} {$row['number']}</td>
            <td class=\"note\">{$row['note']}</td><td class=\"title\">{$row['title']}</td></tr>\n";
        $rowcount += 1;
    }
    echo "</article>\n";
    ?>
    </table>
    <input type="submit" value="Delete"><input type="reset" value="Clear">
    </form>
    <?
}

function html_head($title, $five=false) {
    $rv[] = '<meta charset="utf-8" />';
    $rv[] = "<head><title>{$title}</title>";
    if (is_link($_SERVER['SCRIPT_FILENAME']))
    {   // Find the installation for css and other links
        $here = dirname(readlink($_SERVER['SCRIPT_FILENAME']));
        $rv[] = "<style type=\"text/css\">";
        $rv[] = get_style("{$here}/style");
        $rv[] = "</style>";
        $rv[] = "<style type=\"text/css\" media=\"print\">";
        $rv[] = get_style("{$here}/print");
        $rv[] = "</style>";
    } else {
        $here = dirname($_SERVER['SCRIPT_NAME']);
        $rv[] = "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$here}/style.css\">";
        $rv[] = "<link type=\"text/css\" rel=\"stylesheet\" media=\"print\" href=\"{$here}/print.css\">";
        if ($five) {
            $rv[] = "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$here}/style5.css\">";
        }
        $rv[] = "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js\"></script>";
        $rv[] = "<link href=\"http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\"/>
        <script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js\"></script>
        <script type=\"text/javascript\" src=\"jquery.ba-dotimeout.min.js\"></script>";
        $rv[] = "<script type=\"text/javascript\" src=\"{$here}/ecmascript.js.php\"></script>";
    }
    $rv[] = "</head>";
    return implode("\n", $rv);
}

function quote_array($ary) {
    // reduce ugliness (Note: connect to mysql before using.)
    return str_replace("'", "''", $ary);
}

function sitetabs($sitetabs, $action) {
    $tabs = array_fill_keys(array_keys($sitetabs), 0);
    $tabs[$action] = 1;
    echo "<nav><div id=\"sitetabs-background\">";
    echo "<ul id=\"sitetabs\">\n";
    foreach ($tabs as $name => $activated) {
        if ($activated) {
            $class = ' class="activated"';
        } else {
            $class = "";
        }
        $tabtext = $sitetabs[$name];
        echo "<li{$class}><a href=\"{$name}.php\">{$tabtext}</a></li>\n";
    }
    echo "</ul></div></nav>\n";
}

function translate_markup($text) {
    global $phplibrary;
    if (include_once($phplibrary.DIRECTORY_SEPARATOR."markdown.php"))
    {
        return Markdown($text);
    } else {
        return $text;
    }
}

function is_within_week($dbdate) {
    // True if the given date is within a week *after* today.
    $db = strtotime($dbdate);
    $now = getdate(time());
    $weekahead = mktime(0,0,0,$now['mon'],$now['mday']+8,$now['year']);
    if ($db <= $weekahead) return True; else return False;
}

function get_style($filename) {
    // Include the style file indicated, adding ".css"
    $file = "{$filename}.css";
    if (file_exists($file)) {
        return file_get_contents($file);
    }
}

function dieWithRollback($q, $errorstr = "") {
    // Rollback the db and die with errorInfo and errorstr
    // If errorstr evaluates false, don't return anything.
    global $dbh;
    if ($errorstr && $q) {
        $error = array_pop($q->errorInfo()) . " " . $errorstr;
    } else {
        $error = "";
    }
    $dbh->rollback();
    die($error);
}

function showMessage() {
    global $sprefix;
    if (array_key_exists('message', $_SESSION[$sprefix])) { ?>
        <div class="message"><?=$_SESSION[$sprefix]['message']?></div>
        <? unset($_SESSION[$sprefix]['message']);
    }
}

function setMessage($text) {
    global $sprefix;
    $_SESSION[$sprefix]['message'] = $text;
}

function getLoginForm($bare=false) {
    global $auth;
    if ($bare) {
        $rv = "";
    } else {
        $rv = '<div id="login">';
    }
    if ($auth) {
        $rv .= "{$auth['fullname']} <a href=\"login.php?action=logout\" name=\"Log out\" title=\"Log out\">Log out</a>";
    } else {
        $rv .= '<form id="loginform" method="post" action="login.php">
        <label for="username">User Name</label>
        <input id="username" type="text" name="username" required>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <button type="submit" value="submit">Log In</button>
        </form>';
    }
    if ($bare) {
        return $rv;
    } else {
        return $rv .= '</div>';
    }
}

function getUserActions($bare=false) {
    global $auth;
    if ($bare) {
        $rv = "";
    } else {
        $rv = '<div id="useractions">';
    }
    if ($auth) {
        if ($auth<3) {
            $rv .= '<a href="useradmin.php?flag=changepw"
                title="Update Password">Update Password</a>';
        } else {
            $rv .= '<a href="useradmin.php"
                title="User Administration">User Administration</a>';
        }
    } else {
        $rv .= '<a href="resetpw.php"
        title="Reset Password">Reset Password</a>
        | <a href="useradmin.php?flag=add"
        title="Register">Register</a>';
    }
    if ($bare) {
        return $rv;
    } else {
        return $rv .= '</div>';
    }
}

function jsString($s, $q="'") {
    return str_replace( array($q, "\n"), array("\\$q", "\\n"), $s);
}

?>
