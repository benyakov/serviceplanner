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
            echo "<tr class=\"heading\"><td>${row['date']} ${row['location']}</td>
                <td colspan=3>${row['dayname']}: ${row['rite']}</td></tr>\n";
            $date = $row['date'];
            $name = $row['dayname'];
            $location = $row['location'];
        }
        // Display this hymn
        echo "<tr><td>&nbsp;</td>
            <td>${row['book']} ${row['number']}</td>
            <td>${row['note']}</td><td>${row['title']}</td>";
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
            echo "<tr class=\"heading\"><td>
            <input type=\"checkbox\" name=\"${row['id']}_${row['location']}\" id=\"check_${row['id']}_${row['location']}\">
            ${row['date']} ${row['location']}</td>
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
            <td>${row['note']}</td><td>${row['title']}</td>";
    }
    ?>
    </table>
    <input type="submit" value="Delete"><input type="reset" value="Clear">
    </form>
    <?
}

function html_head($title)
{
    require_once("options.php");
    return ("
    <head>
    <title>${title}</title>
    <link type=\"text/css\" rel=\"stylesheet\" href=\"${install_path}style.css\">
    <link type=\"text/css\" rel=\"stylesheet\" media=\"print\" href=\"${install_path}print.css\">
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
    echo "<ul id=\"sitetabs\">\n<li class=\"sitetabs-spacer\">&nbsp;</li>";
    foreach ($tabs as $name => $activated) {
        if ($activated) {
            $class = ' class="activated"';
        } else {
            $class = "";
        }
        $tabtext = $sitetabs[$name];
        echo "<li$class><a href=\"${name}.php\">$tabtext</a></li>\n";
        echo "<li class=\"sitetabs-spacer\">&nbsp;</li>";
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
?>
