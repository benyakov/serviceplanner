<?php

function display_records_table()
{ // Show a table of the data in the query
    ?><table>
        <tr class="heading"><th>Date &amp; Location</th><th colspan=3>Liturgical Day Name: Service/Rite</th></tr>
        <tr><td>&nbsp;</td><th>Book &amp; #</th><th>Note</th><th>Title</th></tr>
    <?
     = "";
     = "";
     = "";
    while ( = mysql_fetch_assoc($result))
    {
        if (!  (['date'] == $date &&
                ['dayname'] == $name &&
                ['location'] == $location))
        {// Display the heading line
            echo "<tr class=\"heading\"><td>${row['date']} ${row['location']}</td>
                <td colspan=3>${row['dayname']}: ${row['rite']}</td></tr>\n";
             = $row['date'];
             = $row['dayname'];
             = $row['location'];
        }
        // Display this hymn
        echo "<tr><td>&nbsp;</td>
            <td>${row['book']} ${row['number']}</td>
            <td>${row['note']}</td><td>${row['title']}</td>";
    }
    echo "</table>\n";
}


function modify_records_table(, $action)
{ // Show a table of the data in the query
  // with links to edit each record, and checkboxes to delete records.
    ?><form action="<?=?>" method="POST">
      <input type="submit" value="Delete"><input type="reset" value="Clear">
      <table>
        <tr class="heading"><th>Date &amp; Location</th><th colspan=3>Liturgical Day Name: Service/Rite</th></tr>
        <tr><td>&nbsp;</td><th>Book &amp; #</th><th>Note</th><th>Title</th></tr>
    <?
     = "";
     = "";
     = "";
    while ( = mysql_fetch_assoc($result))
    {
        if (!  (['date'] == $date &&
                ['dayname'] == $name &&
                ['location'] == $location))
        {// Display the heading line
            echo "<tr class=\"heading\"><td>
            <input type=\"checkbox\" name=\"${row['id']}_${row['location']}\" id=\"check_${row['id']}_${row['location']}\">
            ${row['date']} ${row['location']}</td>
            <td colspan=3><a href=\"edit.php?id=${row['id']}\">Edit</a> |
            <a href=\"sermon.php?id=${row['id']}\">Sermon</a> |
            ${row['dayname']}: ${row['rite']}</td></tr>\n";
             = $row['date'];
             = $row['dayname'];
             = $row['location'];
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

function html_head ()
{
    require_once("options.php");
    return ("<head>
    <title>${title}</title>
    <link type=\"text/css\" rel=\"stylesheet\" href=\"${install_path}style.css\"> </head>");
}

function mysql_esc_array()
{ // reduce ugliness (Note: connect to mysql before using.)
    return array_map(mysql_real_escape_string, );
}

function mysql_esc()
{ // further reduce ugliness (Note: connect to mysql before using.)
    return mysql_real_escape_string();
}


?>
