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
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
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
        fields.push(reprField(f));
    }
    $("#fieldcontainer").append(fields.join("\n"));
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
    // TODO: Write php responses to the following get/post requests
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
                $("#dialog").close();
                rv = $.parseJSON(rv);
                if (rv[0]) {
                    loadFieldContainer();
                }
                setMessage(rv[1]);
            });
}

function generateFieldOptionList(selected="") {
    var customfields = $.parseJSON(sessionStorage.getItem("customfields"));
    var options = Array();
    for (f in customfields) {
        if (f.name == selected) selectedflag = "selected";
        else selectedflag = "";
        options.push("<option value=\""+f.name+"\" "+selectedflag+">"
            +f.name+"</option>");
    }
    return options.join("\n");
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

$q = queryAllHymns($limit=int $config["custom view"]["limit"],
    $future=bool $config["custom view"]["future"]);
// Group by service
$servicelisting = array();
$service = array();
$prevservice = false;
foreach ($q as $hymndata) {
    if ($hymndata['service'] != $prevservice) {
        $servicelisting[] = $service
        $service = array($hymndata);
        $prevservice = $hymndata['service'];
    } else {
        $service[] = $hymndata;
    }
}

// Display the table
if (! $config["custom view"]["start"]) {
    $config["custom view"]["start"] = "<table>";
    $config->save();
}
echo $config["custom view"]["start"];
foreach ($servicelisting as $service) {
    displayService($service, $config);
}
echo $config["custom view"]["end"];
?>
</div>
<div id="dialog"></div>
</body>
</html>


<?
define displayService($service, $config) {
    echo "<tr class=\"customservice\">\n";
    foreach ($config['custom view']['fields'] as $field) {
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
        if (array_key_exists($field, $service[0])) {
            $service[0][$field];
        } else {
            echo "Unknown Field: <span class=\"unknown-field\">"
                .htmlentities($field)."</span>";
        }
        echo "</td>";
    }
    echo "\n</tr>";
}
