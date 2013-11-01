<? /* Interface for specifying and showing a customized service listing

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
require("./init.php");

/* Note on storage in $config['custom view']:
 *
 * $config['custom view']['fields'] is an enumerated array
 * containing the names of the chosen fields.
 *
 * $config['custom view']['field-order'] is an enumerated array,
 * in which the keys represent field ordering, and
 * the values are indexes into $custom['custom view']['fields'].
 */
if ("customfields" == $_GET['action']) { // Works
    // Expecting JSON array of objects {order: X, name: Y}
    $rv = Array();
    // Set up default if nothing is configured
    if (! $config->exists('custom view', 'fields')) {
        $config->set('custom view', 'fields', 0, "date");
        $config->set('custom view', 'field-order', '[]', 0);
        $config->save();
    }
    // Pull a data structure from the configuration
    for ($i=0, $len = count($config->get('custom view','fields'));
        $len>$i; $i++)
    {
        $rv[] = Array("order"=>$config->get('custom view', 'field-order', $i),
            "name"=>$config->get('custom view', 'fields',
                $config->get('custom view', 'field-order', $i)));
    }
    echo json_encode($rv);
    exit(0);
} elseif ("available" == $_GET['action']) { // Works
    $q = queryAllHymns(1);
    $rec = $q->fetch(PDO::FETCH_ASSOC);
    echo json_encode(array_keys($rec));
    exit(0);
} elseif ("left" == $_GET['move-field']) {
    // FIXME: These don't work.  Perhaps implement a transpose() method
    // on the $config object?
    validateAuth(true);
    if (1 > $_GET['index']) {
        echo json_encode(Array(0, "Can't move before the beginning."));
        exit(0);
    }
    $currentloc = (int) $_GET['index'];
    $config->transpose(Array('custom view', 'field-order'),
        $currentloc, $currentloc-1);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
} elseif ("right" == $_GET['move-field']) {
    validateAuth(true);
    if ((count($config->get('custom view', 'field-order'))-2)<$_GET['index']) {
        echo json_encode(Array(0, "Can't move after the end."));
        exit(0);
    }
    $currentloc = (int) $_GET['index'];
    $config->transpose(Array('custom view', 'field-order'),
        $currentloc, $currentloc+1);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
} elseif (isset($_GET['delete-field'])) {
    // FIXME: perhaps implement a deepUnset() method on the config object?
    validateAuth(true);
    if (0 > $_GET['delete-field'] or
        count($config->get('custom view','field-order')) <=
        $_GET['delete-field'])
    {
        echo json_encode(Array(0, "Can't delete a nonexistent item."));
        exit(0);
    }
    $delloc = (int) $_GET['index'];
    $config->del('custom view', 'fields',
        $config->get('custom view', 'field-order', $delloc));
    $config->del('custom view', 'field-order', $delloc);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
} elseif (isset($_GET['insert'])) { // Works; tested 10/17/13
    validateAuth(true);
    $newindex = (int) $_GET['insert'];
    $newslot = count($config->get('custom view', 'fields'));
    if (0 > $newindex or $newslot < $newindex) {
        echo json_encode(Array(0, "Can't insert beyond the end."));
        exit(0);
    }
    $config->set('custom view', 'fields', '[]', $_POST['selection']);
    $newfieldorder = Array();
    foreach ($config->get('custom view', 'field-order') as $key=>$val)
        if ($key >= $newindex) $newfieldorder[$key+1] = $val;
        else $newfieldorder[$key] = $val;
    $newfieldorder[$newindex] = $newslot;
    $config->set('custom view', 'field-order', $newfieldorder);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
}

?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Custom View of Service Records")?>
<body>
<script type="text/javascript">
function loadFieldContainer() {
    var xhr = $.getJSON("<?=$this_script?>",
        { "action": "customfields" },
        fillFieldContainer);
}

function fillFieldContainer(result) {
    var fields = Array();
    for (f in result) {
        fields.push(reprField(result[f]));
    }
    $("#fieldcontainer").html(fields.join("\n"));
    setupFields();
}

function reprField(field) {
    var rv = Array("<div class=\"customfield\" data-order=\""
        +field.order+"\" id=\"customfield-"+field.order+"\">");
    rv.push("<a href=\"javascript: void();\" class=\"field-left\">&lt;</a>&nbsp;");
    rv.push("<a href=\"javascript: void();\" class=\"field-right\">&gt;</a>&nbsp;");
    rv.push("<a href=\"javascript: void();\" class=\"field-delete\">-</a>&nbsp;");
    rv.push("<a href=\"javascript: void();\" class=\"field-insert\">+</a><br>");
    rv.push(field.name);
    rv.push("</div>");
    return rv.join("\n");
}

function setupFields() {
    $("a.field-left").click(function(evt) {
        evt.preventDefault();
        var order = $(this).parent().data("order");
        $.getJSON("<?=$this_script?>",
            { "move-field": "left",
            "index": order },
            fillFieldContainer);
    });
    $("a.field-right").click(function(evt) {
        evt.preventDefault();
        var order = $(this).parent().data("order");
        $.getJSON("<?=$this_script?>",
            { "move-field": "right",
            "index": order },
            fillFieldContainer);
    });
    $("a.field-delete").click(function(evt) {
        evt.preventDefault();
        var order = $(this).parent().data("order");
        $.getJSON("<?=$this_script?>",
            { "delete-field": order },
            fillFieldContainer);
    });
    $("a.field-insert").click(function(evt) {
        evt.preventDefault();
        var order = $(this).parent().data("order");
        $("#dialog").html("<form id=\"selectfieldform\">"
            +"<select id=\"fieldselector\" name=\"fieldselector\">"
            +generateFieldOptionList()+"</select><br>"
            +"<button type=\"submit\">Insert Field</button>\n"
            +"</form>")
            .dialog({modal: true,
                position: "center",
                title: "Insert Field At "+order,
                width: $(window).width()*0.4,
                open: function() { setupInsertDialog(order); },
                close: function() { $("#dialog").empty(); }
            });
    });
}

function setupInsertDialog(order) {
    $("#selectfieldform").submit(function(evt) {
        evt.preventDefault();
        $.post("<?=$this_script?>?insert="+order,
            { selection: $("#fieldselector").val() },
            function(rv) {
                $("#dialog").dialog("close");
                rv = $.parseJSON(rv);
                if (rv[0]) {
                    loadFieldContainer();
                }
                setMessage(rv[1]);
            });
    });
}

function generateFieldOptionList(selected) {
    if (typeof selected == 'undefined') selected = "";
    var customfields = $.parseJSON(sessionStorage.getItem("customfields"));
    var options = Array();
    for (f in customfields) {
        if (customfields[f] == selected) selectedflag = "selected";
        else selectedflag = "";
        options.push("<option value=\""+customfields[f]+"\" "+selectedflag+">"
            +customfields[f]+"</option>");
    }
    return options.join("\n");
}

function loadAvailableFields() {
    var xhr = $.get("<?=$this_script?>",
        { "action": "available" },
        function(rv) {
            sessionStorage.setItem("customfields", rv);
        });
}

$(document).ready(function() {
    loadFieldContainer();
    loadAvailableFields();
});
</script>
<? pageHeader();
siteTabs($auth, "index"); ?>
<div id="content-container">
<? if ($auth) {
    echo "<div id=\"fieldcontainer\"></div>";
}

/* FIXME: Add config for custom view variables:
 * limit, future, start, end
 */
// Set up reasonable defaults, if necessary
$saveconfig = false;
if (! $config->exists("custom view", "limit")) {
    $config->set("custom view", "limit", 100);
    $saveconfig = true;
}
if (! $config->exists("custom view", "future")) {
    $config->set("custom view", "future", 1);
    $saveconfig = true;
}
if (! $config->exists("custom view", "start")) {
    $config->set("custom view", "start", "<table>");
    $saveconfig = true;
}
if (! $config->exists("custom view", "end")) {
    $config->set("custom view", "end", "</table>");
    $saveconfig = true;
}
if ($saveconfig) $config->save();

$q = queryAllHymns($limit=(int) $config->get("custom view", "limit"),
    $future=(bool) $config->get("custom view", "future"));
// Group by service
$servicelisting = array();
$service = array();
$prevservice = false;
foreach ($q as $hymndata) {
    if ($hymndata['serviceid'] != $prevservice) {
        $servicelisting[] = $service;
        $service = array($hymndata);
        $prevservice = $hymndata['serviceid'];
    } else {
        $service[] = $hymndata;
    }
}

// Display the table
echo $config->get("custom view", "start");
foreach ($servicelisting as $service) {
    if (! $service) continue;
    displayService($service, $config);
}
echo $config->get("custom view", "end");
?>
</div>
<div id="dialog"></div>
</body>
</html>


<?
function displayService($service, $config) {
    foreach ($config->get('custom view', 'fields') as $field) {
        // Special field names
        if ("hymn numbers" == $field) {
            echo "<td class=\"customservice-hymnnumbers\">";
            foreach ($service as $hymn) {
                echo "{$hymn["number"]}<br>";
            }
            echo "</td>";
            continue;
        } elseif ("hymn books" == $field) {
            echo "<td class=\"customservice-hymnbooks\">";
            foreach ($service as $hymn) {
                echo "{$hymn["book"]}<br>";
            }
            echo "</td>";
            continue;
        } elseif ("hymn notes" == $field) {
            echo "<td class=\"customservice-hymnnotes\">";
            foreach ($service as $hymn) {
                echo "{$hymn["note"]}<br>";
            }
            echo "</td>";
            continue;
        } elseif ("hymn locations" == $field) {
            echo "<td class=\"customservice-hymnlocation\">";
            foreach ($service as $hymn) {
                echo "{$hymn["location"]}<br>";
            }
            echo "</td>";
            continue;
        } elseif ("hymn titles" == $field) {
            echo "<td class=\"customservice-hymntitle\">";
            foreach ($service as $hymn) {
                echo "{$hymn["title"]}<br>";
            }
            echo "</td>";
            continue;
        }
        // DB fields
        echo "<td class=\"customservice-dbfield\">";
        if (isset($service[0][$field])) {
            echo $service[0][$field];
        } else {
            echo "Unknown Field: <span class=\"unknown-field\">"
                .htmlentities($field)."</span>";
        }
        echo "</td>";
    }
    echo "\n</tr>";
}

