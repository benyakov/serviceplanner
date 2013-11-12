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
$config = getConfig(true);

if ("customfields" == $_GET['action']) {
    // Expecting JSON array of objects {order: X, name: Y}
    if (checkFieldsSetup($config)) $config->save();
    // Pull a data structure from the configuration
    echo json_encode($config->get('custom view', 'fields'));
    exit(0);
} elseif ("servicelisting" == $_GET['action']) {
    echo json_encode(showServiceListing($config));
    exit(0);
} elseif ("available" == $_GET['action']) {
    $q = queryAllHymns(1);
    $record = $q->fetch(PDO::FETCH_ASSOC);
    $rec = array_keys($record);
    $rec = array_merge($rec, Array(
        "hymn numbers",
        "hymn books",
        "hymn notes",
        "hymn locations",
        "hymn titles"));
    echo json_encode($rec);
    exit(0);
} elseif ("left" == $_GET['move-field']) {
    validateAuth(true);
    if (1 > $_GET['index']) {
        echo json_encode(Array(0, "Can't move before the beginning."));
        exit(0);
    }
    $currentloc = (int) $_GET['index'];
    $tmpary = cfgToFieldlist($config);
    $tmpval = $tmpary[$currentloc];
    $tmpary[$currentloc] = $tmpary[$currentloc-1];
    $tmpary[$currentloc-1] = $tmpval;
    fieldlistToCfg(normFieldlist($tmpary), $config);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
} elseif ("right" == $_GET['move-field']) {
    validateAuth(true);
    if ((count($config->get('custom view', 'fields'))-2)<$_GET['index']) {
        echo json_encode(Array(0, "Can't move after the end."));
        exit(0);
    }
    $currentloc = (int) $_GET['index'];
    $tmpary = cfgToFieldlist($config);
    $tmpval = $tmpary[$currentloc];
    $tmpary[$currentloc] = $tmpary[$currentloc+1];
    $tmpary[$currentloc+1] = $tmpval;
    fieldlistToCfg(normFieldlist($tmpary), $config);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
} elseif (isset($_GET['delete-field'])) {
    validateAuth(true);
    if (0 > $_GET['delete-field'] or
        count($config->get('custom view','fields')) <=
        $_GET['delete-field'])
    {
        echo json_encode(Array(0, "Can't delete a nonexistent item."));
        exit(0);
    }
    $delloc = (int) $_GET['delete-field'];
    $tmpary = cfgToFieldlist($config);
    unset($tmpary[$delloc]);
    fieldlistToCfg(normFieldlist($tmpary), $config);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
} elseif (isset($_GET['insert'])) {
    validateAuth(true);
    $newindex = (int) $_GET['insert'];
    $newslot = count($config->get('custom view', 'fields'));
    if (0 > $newindex or $newslot < $newindex) {
        echo json_encode(Array(0, "Can't insert beyond the end."));
        exit(0);
    }
    $tmpary = cfgToFieldlist($config);
    array_splice($tmpary, $newindex, 0, Array($_POST['selection']));
    fieldlistToCfg($tmpary, $config);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
}

?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Custom View of Service Records", Array("customview.css"))?>
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

function loadServiceListing() {
    var xhr = $.getJSON("<?=$this_script?>",
        { "action": "servicelisting" },
        fillServiceListing);
}

function fillServiceListing(result) {
    $("#servicelisting").html(result);
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
            function(rv) {
                if (rv[0]) {
                    loadFieldContainer();
                    loadServiceListing();
                }
            });
    });
    $("a.field-right").click(function(evt) {
        evt.preventDefault();
        var order = $(this).parent().data("order");
        $.getJSON("<?=$this_script?>",
            { "move-field": "right",
            "index": order },
            function(rv) {
                if (rv[0]) {
                    loadFieldContainer();
                    loadServiceListing();
                }
            });
    });
    $("a.field-delete").click(function(evt) {
        evt.preventDefault();
        var order = $(this).parent().data("order");
        $.getJSON("<?=$this_script?>",
            { "delete-field": order },
            function(rv) {
                if (rv[0]) {
                    loadFieldContainer();
                    loadServiceListing();
                }
            });
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
                    loadServiceListing();
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
$saveconfig = checkFieldsSetup($config) || $saveconfig;
if ($saveconfig) $config->save();

// Display the table
echo "<div id=\"servicelisting\">";
echo showServiceListing($config);
echo "</div>";

echo "<pre>";
print_r($config->get("custom view"));
echo "</pre>";

?>
</div>
<div id="dialog"></div>
</body>
</html>


<?
function showServiceListing($config) {
    $rv = Array();
    $q = queryAllHymns($limit=(int) $config->get("custom view", "limit"),
        $future=(bool) $config->get("custom view", "future"));
    // Group by service
    $servicelisting = Array();
    $service = Array();
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
    $rv[] = $config->get("custom view", "start");
    foreach ($servicelisting as $service) {
        if (! $service) continue;
        $fieldlist = cfgToFieldlist($config);
        $rv[] = displayService($service, $fieldlist);
    }
    $rv[] = $config->get("custom view", "end");
    return implode("\n", $rv);
}
function displayService($service, $fieldlist) {
    $rv = Array();
    foreach ($fieldlist as $field) {
        // Special field names
        if ("hymn numbers" == $field) {
            $rv[] = "<td class=\"customservice-hymnnumbers\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["number"]}<br>";
            }
            $rv[] = "</td>";
            continue;
        } elseif ("hymn books" == $field) {
            $rv[] = "<td class=\"customservice-hymnbooks\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["book"]}<br>";
            }
            $rv[] = "</td>";
            continue;
        } elseif ("hymn notes" == $field) {
            $rv[] = "<td class=\"customservice-hymnnotes\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["note"]}<br>";
            }
            $rv[] = "</td>";
            continue;
        } elseif ("hymn locations" == $field) {
            $rv[] = "<td class=\"customservice-hymnlocation\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["location"]}<br>";
            }
            $rv[] = "</td>";
            continue;
        } elseif ("hymn titles" == $field) {
            $rv[] = "<td class=\"customservice-hymntitle\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["title"]}<br>";
            }
            $rv[] = "</td>";
            continue;
        }
        // DB fields
        $rv[] = "<td class=\"customservice-dbfield\">";
        if (isset($service[0][$field])) {
            $rv[] = $service[0][$field];
        } else {
            $rv[] = "Unknown Field: <span class=\"unknown-field\">"
                .$field."</span>";
        }
        $rv[] = "</td>";
    }
    $rv[] = "</tr>";
    return implode("\n", $rv);
}

function cfgToFieldlist($config) {
    $tmpary = Array();
    foreach ($config->get('custom view', 'fields') as $field)
        $tmpary[$field['order']] = $field['name'];
    return $tmpary;
}

/**
 * Normalize array keys so they don't skip numbers
 */
function normFieldlist($fieldarray) {
    $newarray = Array();
    foreach (array_keys($fieldarray) as $key)
        $newarray[] = $fieldarray[$key];
    return $newarray;
}

function fieldlistToCfg($fieldarray, $config) {
    $tmpary = Array();
    foreach ($fieldarray as $k=>$v)
        $tmpary[] = Array("name"=>$v, "order"=>$k);
    $config->set('custom view', 'fields', $tmpary);
}

 /**
  * Set up default if nothing is configured, return
  * whether or not a save is needed.
  */
function checkFieldsSetup($config) {
    if (! $config->exists('custom view', 'fields')) {
        $config->set('custom view', 'fields', '[]',
            Array("name"=>"date", "order"=>0));
        return true;
    }
    return false;
}
