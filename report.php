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
    array_splice($tmpary, $newindex, 0,
        Array(Array("name"=>$_POST['selection'], "width"=>$_POST['width'])));
    fieldlistToCfg($tmpary, $config);
    $config->save();
    echo json_encode(Array(1, "Success."));
    exit(0);
} elseif (isset($_POST['limit'])) {
    if ("future" == $_POST['future'])
        $config->set("custom view", "future", 1);
    else
        $config->set("custom view", "future", 0);
    $config->set("custom view", "start", $_POST['start']);
    $config->set("custom view", "end", $_POST['end']);
    $config->set("custom view", "limit", $_POST['limit']);
    $config->save();
    echo json_encode(Array(true, "Configuration set."));
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
    rv.push("<a href=\"javascript: void();\" class=\"field-delete\">-</a>&nbsp;");
    rv.push("<a href=\"javascript: void();\" class=\"field-insert\">+</a>&nbsp;");
    rv.push("<a href=\"javascript: void();\" class=\"field-right\">&gt;</a><br>");
    rv.push(field.name);
    rv.push("["+field.width+"]");
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
            +"<table><tr>"
            +"<td><label for=\"fieldselector\">Field</label></td>"
            +"<td><select id=\"fieldselector\" name=\"fieldselector\">"
            +generateFieldOptionList()+"</select></td></tr>"
            +"<tr><td><label for=\"fieldwidth\">Width (m's)</label></td>"
            +"<td><input type=\"number\" min=\"1\" id=\"fieldwidth\" "
            +"required name=\"fieldwidth\" style=\"width: 3em\"></td></tr>"
            +"<tr><td></td>"
            +"<td><button type=\"submit\">Insert Field</button></td></tr>"
            +"</table>"
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
            { selection: $("#fieldselector").val(),
              width: $("#fieldwidth").val() },
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
siteTabs($auth); ?>
<div id="content-container">
<? // Set up reasonable defaults, if necessary
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
if ($auth) {
    echo customViewConfig($config);
    echo "<div id=\"fieldcontainer\"></div>";
}
// Display the table
echo "<div id=\"servicelisting\">";
echo showServiceListing($config);
echo "</div>";
?>

</div>
<div id="dialog"></div>
</body>
</html>


<?
function customViewConfig($cfg) {
    ob_start();
    $limit = $cfg->get("custom view", "limit");
    if ((bool) $cfg->get("custom view", "future"))
        $future = "checked";
    else
        $future = "";
    $starthtml = $cfg->get("custom view", "start");
    $endhtml = $cfg->get("custom view", "end");
?>
<div id="customviewconfig">
<form id="customviewsetup">
Limit: <input type="number" value=<?=$limit?> min=1 required id="limit"><br>
Future: <input type="checkbox" <?=$future?> value="future" id="future"><br>
Start HTML: <input type="text" id="start" required value="<?=$starthtml?>"><br>
End HTML: <input type="text" id="end" required value="<?=$endhtml?>"><br>
<button type="submit">Set</button>
</form>
</div>
<script type="text/javascript">
    $("#customviewsetup").submit(function(evt) {
        evt.preventDefault();
        $.post("<?=$this_script?>", { limit: $("#limit").val(),
            future: $("#future:checked").val(),
            start: $("#start").val(),
            end: $("#end").val() },
            function(rv) {
                rv = $.parseJSON(rv);
                setMessage(rv[1]);
                loadServiceListing();
            });
    });
</script>
<?
    return ob_get_clean();
}

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
    $servicelisting[] = $service;
    // Delete the last service when showing past hymns to avoid
    // confusion with an incomplete listing in that service
    if (! $config->get("custom view", "future"))
        unset($servicelisting[-1]);
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
        if ("hymn numbers" == $field['name']) {
            $rv[] = "<td class=\"customservice-hymnnumbers\">";
            $rv[] = "<div style=\"width: {$field['width']}em\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["number"]}<br>";
            }
            $rv[] = "</div></td>";
            continue;
        } elseif ("hymn books" == $field['name']) {
            $rv[] = "<td class=\"customservice-hymnbooks\">";
            $rv[] = "<div style=\"width: {$field['width']}em\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["book"]}<br>";
            }
            $rv[] = "</div></td>";
            continue;
        } elseif ("hymn notes" == $field['name']) {
            $rv[] = "<td class=\"customservice-hymnnotes\">";
            $rv[] = "<div style=\"width: {$field['width']}em\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["note"]}<br>";
            }
            $rv[] = "</div></td>";
            continue;
        } elseif ("hymn locations" == $field['name']) {
            $rv[] = "<td class=\"customservice-hymnlocation\">";
            $rv[] = "<div style=\"width: {$field['width']}em\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["location"]}<br>";
            }
            $rv[] = "</div></td>";
            continue;
        } elseif ("hymn titles" == $field['name']) {
            $rv[] = "<td class=\"customservice-hymntitle\">";
            $rv[] = "<div style=\"width: {$field['width']}em\">";
            foreach ($service as $hymn) {
                $rv[] = "{$hymn["title"]}<br>";
            }
            $rv[] = "</div></td>";
            continue;
        }
        // DB fields
        $rv[] = "<td class=\"customservice-dbfield\">";
        $rv[] = "<div style=\"width: {$field['width']}em\">";
        $has_markdown = Array("bnotes", "servicenotes");
        if (isset($service[0][$field['name']])) {
            if (array_search($field['name'], $has_markdown) !== false)
                $rv[] = translate_markup($service[0][$field['name']]);
            else
                $rv[] = $service[0][$field['name']];
        } else {
            $rv[] = "Unknown Field: <span class=\"unknown-field\">"
                .$field['name']."</span>";
        }
        $rv[] = "</div></td>";
    }
    $rv[] = "</tr>";
    return implode("\n", $rv);
}

function cfgToFieldlist($config) {
    $tmpary = Array();
    foreach ($config->get('custom view', 'fields') as $field)
        $tmpary[$field['order']] = Array('name'=>$field['name'],
            'width'=>$field['width']);
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
        $tmpary[] = Array("name"=>$v['name'], "width"=>$v["width"],
            "order"=>$k);
    $config->set('custom view', 'fields', $tmpary);
}

 /**
  * Set up default if nothing is configured, return
  * whether or not a save is needed.
  */
function checkFieldsSetup($config) {
    if (! $config->exists('custom view', 'fields')) {
        $config->set('custom view', 'fields', '[]',
            Array("name"=>"date", "order"=>0, "width"=>6));
        return true;
    }
    return false;
}
