<?php

function display_records_table($result)
{ // Show a table of the data in the query $result
    ?><table id="records_listing">
        <tr class="heading"><th>Date &amp; Location</th><th colspan=3>Liturgical Day Name: Service/Rite</th></tr>
        <tr><td>&nbsp;</td><th>Book &amp; #</th><th>Note</th><th>Title</th></tr>
    <?
    $date = "";
    $name = "";
    $location = "";
    while ($row = mysql_fetch_assoc($result))
    {
        if (!  ($row['date'] == $date &&
                $row['dayname'] == $name &&
                $row['location'] == $location))
        {// Display the heading line
            if (is_within_week($row['date']))
            {
                $datetext = "<a name=\"now\">${row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            echo "<tr class=\"heading\"><td>${datetext} ${row['location']}</td>
                <td colspan=3>${row['dayname']}: ${row['rite']}</td></tr>\n";
            $date = $row['date'];
            $name = $row['dayname'];
            $location = $row['location'];
        }
        // Display this hymn
        echo "<tr><td>&nbsp;</td>
            <td>${row['book']} ${row['number']}</td>
            <td class=\"note\">${row['note']}</td><td class=\"title\">${row['title']}</td>";
    }
    echo "</table>\n";
}


function modify_records_table($result, $action)
{ // Show a table of the data in the query $result
  // with links to edit each record, and checkboxes to delete records.
    ?><form action="<?=$action?>" method="POST">
      <input type="submit" value="Delete"><input type="reset" value="Clear">
      <table id="modify_listing">
        <tr class="heading"><th>Date &amp; Location</th><th colspan=3>Liturgical Day Name: Service/Rite</th></tr>
        <tr><td>&nbsp;</td><th>Book &amp; #</th><th>Note</th><th>Title</th></tr>
    <?
    $date = "";
    $name = "";
    $location = "";
    while ($row = mysql_fetch_assoc($result))
    {
        if (!  ($row['date'] == $date &&
                $row['dayname'] == $name &&
                $row['location'] == $location))
        {// Display the heading line
            if (is_within_week($row['date']))
            {
                $datetext = "<a name=\"now\">${row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            echo "<tr class=\"heading\"><td>
            <input type=\"checkbox\" name=\"${row['id']}_${row['location']}\" id=\"check_${row['id']}_${row['location']}\">
            ${datetext} ${row['location']}</td>
            <td colspan=3><a href=\"edit.php?id=${row['id']}\">Edit</a> |
            <a href=\"sermon.php?id=${row['id']}\">Sermon</a> |
            ${row['dayname']}: ${row['rite']}</td></tr>\n";
            $date = $row['date'];
            $name = $row['dayname'];
            $location = $row['location'];
        }
        // Display this hymn
        echo "<tr><td>&nbsp;</td>
            <td>${row['book']} ${row['number']}</td>
            <td class=\"note\">${row['note']}</td><td class=\"title\">${row['title']}</td></tr>\n";
    }
    ?>
    </table>
    <input type="submit" value="Delete"><input type="reset" value="Clear">
    </form>
    <?
}

function html_head($title)
{
    if (is_link($_SERVER['SCRIPT_FILENAME']))
    {   // Find the installation for css and other links
        $here = dirname(readlink($_SERVER['SCRIPT_FILENAME']));
    } else {
    $here = dirname($_SERVER['SCRIPT_NAME']);
    }
    return ("
    <head>
    <title>${title}</title>
    <link type=\"text/css\" rel=\"stylesheet\" href=\"${here}/style.css\">
    <link type=\"text/css\" rel=\"stylesheet\" media=\"print\" href=\"${here}/print.css\">
    </head>");
}

function mysql_esc_array($ary)
{ // reduce ugliness (Note: connect to mysql before using.)
    return array_map(mysql_real_escape_string, $ary);
}

function mysql_esc($str)
{ // further reduce ugliness (Note: connect to mysql before using.)
    return mysql_real_escape_string($str);
}

function sitetabs($sitetabs, $action) {
    $tabs = array_fill_keys(array_keys($sitetabs), 0);
    $tabs[$action] = 1;
    echo "<div id=\"sitetabs_background\">";
    echo "<ul id=\"sitetabs\">\n";
    foreach ($tabs as $name => $activated) {
        if ($activated) {
            $class = ' class="activated"';
        } else {
            $class = "";
        }
        $tabtext = $sitetabs[$name];
        echo "<li$class><a href=\"${name}.php\">$tabtext</a></li>\n";
    }
    echo "</ul></div>\n";
}

function translate_markup($text)
{
    global $phplibrary;
    require("options.php");
    if (include_once($phplibrary."markdown.php"))
    {
        return Markdown($text);
    } else {
        return $text;
    }
}

function is_within_week($dbdate)
{   // True if the given date is within a week *after* today.
    $db = strtotime($dbdate);
    $now = getdate(time());
    $weekahead = mktime(0,0,0,$now['mon'],$now['mday']+8,$now['year']);
    if ($db <= $weekahead) return True; else return False;
}
?>
