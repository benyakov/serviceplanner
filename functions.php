<?php

function auth($login = '', $passwd = '') {
    global $dbp, $sprefix, $dbh;
	$authdata = $_SESSION[$sprefix]['authdata'];
	if ( is_array( $authdata ) ) {
		$check = $authdata['login'];
		$pw = $authdata['password'];
		$register = false;
	} elseif (!empty($login)) {
		$check = $login;
		$pw = md5($passwd);
		$register = true;
	} else {
		return false;
	}

    $q = $dbh->prepare("SELECT * FROM `{$tablepre}users`
        WHERE `username` = :check");
    $q->bindParam(':check', $check);
    $q->execute();
	$row = $q->fetch(PDO::FETCH_ASSOC);
	if ( $row["password"] == $pw ) {
		if ($register) {
            $_SESSION[$sprefix]["authdata"] = array(
                "login"=>$row["username"],
                "password"=>$row["password"],
                "uid"=>$row["uid"]);
		}
		return true;
	} else {
        unset( $_SESSION[$sprefix]['authdata'] );
        return false;
    }
}

function authId() {
    // Return the current username from session or false
	$authdata = $_SESSION[$sprefix]['authdata'];
	if ( is_array( $authdata ) ) {
		return $authdata['login'];
    } else {
        return false;
    }
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
        $rv[] = "<script type=\"text/javascript\" src=\"{$here}/ecmascript.js\"></script>";
    }
    $rv[] = "</head>";
    return implode("\n", $rv);
}

function quote_array($ary) {
    // reduce ugliness (Note: connect to mysql before using.)
    return array_map($dbh->quote, $ary);
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


?>
