<?
/* Edit the days in the church year
 */
require("init.php");
$auth = auth();
$cy_begin_marker = "# BEGIN Church Year Tables";
$cy_end_marker = "# END Church Year Tables";
$create_tables_file = "./utility/dynamictables.sql";

function query_churchyear($json=false) {
    /* Return an executed query for all rows of the churchyear db
     */
    global $dbh, $dbp;
    $q = $dbh->prepare("SELECT cy.`dayname`, cy.`season`, cy.`base`,
        cy.`offset`, cy.`month`, cy.`day`,
        cy.`observed_month`, cy.`observed_sunday`
        FROM `{$dbp}churchyear` AS cy
        LEFT OUTER JOIN `{$dbp}churchyear_order` AS cyo
            ON (cy.season = cyo.name)
            ORDER BY cyo.idx, cy.offset, cy.month, cy.day");
    if (! $q->execute()) {
        if ($json) {
            echo json_encode(array(false, array_pop($q->errorInfo())));
        } else {
            echo "Problem querying database:" . array_pop($q->errorInfo());
        }
        exit(0);
    } else {
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}

function churchyear_listing($rows) {
    /* Given an array of matched db rows,
     * list all items in a table with edit/delete links.
     */
    ob_start();
?>
<table id="churchyear-listing">
<tr><td></td><th>Name</th><th>Season</th><th>Base Day</th><th>Days Offset</th><th>Month</th>
    <th>Day</th><th>Observed Month</th><th>Observed Sunday</th></tr>
<? $even = "";
    foreach ($rows as $row) {
        if ($even == "class=\"even\"") {
            $even = "";
        } else {
            $even = "class=\"even\"";
        }
?>
    <tr id="row_<?=$row['dayname']?>" <?=$even?>>
    <td class="controls">
    <a class="edit" href="" data-day="<?=$row['dayname']?>">Edit</a><br>
    <a class="delete" href="" data-day="<?=$row['dayname']?>">Delete</a></td>
    <td class="dayname"><a href="" class="synonym"
            data-day="<?=$row['dayname']?>">=</a>
        <a href="" data-day="<?=$row['dayname']?>"
            class="propers"><?=$row['dayname']?></a></td>
    <td class="season"><?=$row['season']?></td>
    <td class="base"><?=$row['base']?></td>
    <td class="offset"><?=$row['offset']?></td>
    <td class="month"><?=$row['month']?></td>
    <td class="day"><?=$row['day']?></td>
    <td class="observed-month"><?=$row['observed_month']?></td>
    <td class="observed-sunday"><?=$row['observed_sunday']?></td></tr>
<?  } ?>
</table>
<?
    return ob_get_clean();
}

function replaceDBP($text, $prefix=false) {
    // Where replace occurrences of {{DBP}} with $prefix or $dbp in text.
    global $dbp;
    if ($prefix !== false) {
        return str_replace('{{DBP}}', $prefix, $text);
    } else {
        return str_replace('{{DBP}}', $dbp, $text);
    }
}

/* Create the church year table if necessary.
 */
$dbh->beginTransaction();
$tableTest = $dbh->query("SELECT 1 FROM `{$dbp}churchyear`");
if (! ($tableTest && $tableTest->fetchAll())) {
    require('./utility/createservicetables.php');
}
/* (Re-)Create church year functions if necessary
 */
$result = $dbh->query("SHOW FUNCTION STATUS LIKE '{$dbp}easter_in_year'");
if (! $result->fetchAll(PDO::FETCH_NUM)) {
    // Define helper functions on the db for getting the dates of days
    $functionsfile = "./utility/churchyearfunctions.sql";
    $functionsfh = fopen($functionsfile, "rb");
    $functionstext = fread($functionsfh, filesize($functionsfile));
    fclose($functionsfh);
    $dbh->exec(replaceDBP($functionstext));
    $dbh->commit();
    $dbh->beginTransaction();
}

/* churchyear.php?dropfunctions=1
 * Drops all the churchyear functions and sets a message about
 * creating them again.
 */
if ($_GET['dropfunctions'] == 1) {
    $dbh->exec("DROP FUNCTION `{$dbp}easter_in_year`;
    DROP FUNCTION `{$dbp}christmas1_in_year`;
    DROP FUNCTION `{$dbp}michaelmas1_in_year`;
    DROP FUNCTION `{$dbp}calc_date_in_year`;
    DROP FUNCTION `{$dbp}date_in_year`;
    DROP FUNCTION `{$dbp}calc_observed_date_in_year`;
    DROP FUNCTION `{$dbp}observed_date_in_year`");
    setMessage("Church year functions dropped.  To re-create them, visit"
        ." the Church Year tab.  They will be created automatically.");
    $dbh->commit();
    header("location: index.php");
    exit(0);
}

/* churchyear.php?droptables=1
 * Drop the churchyear tables and sets a message about creating
 * them again.
 */
if ($_GET['droptables'] == 1) {
    $dbh->exec("DROP TABLE `{$dbp}churchyear_synonyms`,
        `{$dbp}churchyear_propers`, `{$dbp}churchyear_order`,
        `{$dbp}churchyear`");

    setMessage("Church year tables dropped.  They will be re-created "
        ."with default values next time you visit the Church Year tab.");
    $dbh->commit();
    if (file_exists($create_tables_file)) {
        $dt = file_get_contents($create_tables_file);
        $start = strpos($dt, $cy_begin_marker);
        if ($start !== false) {
            $end = strpos($dt, $cy_end_marker) + strlen($cy_end_marker);
            $newdt = substr($dt, 0, $start) . substr($dt, $end);
            $fh = fopen($create_tables_file, "w");
            fwrite($fh, $newdt);
            fclose($fh);
        }
    }
    header("location: index.php");
    exit(0);
}

/* churchyear.php?daysfordate=date
 * Returns a comma-separated list of daynames that match the date given.
 */
if ($_GET['daysfordate']) {
    $date = date_parse($_GET['daysfordate']);
    $q = $dbh->prepare("SELECT dayname FROM `{$dbp}churchyear`
        WHERE `{$dbp}date_in_year`(:year1, dayname) = :date1
        OR `{$dbp}observed_date_in_year`(:year2, dayname) = :date1");
    $q->bindValue(':year1', $date['year']);
    $q->bindValue(':year2', $date['year']);
    $q->bindValue(':date1', $_GET['daysfordate']);
    $q->bindValue(':date2', $_GET['daysfordate']);
    $result = $q->execute();
    while ($row = $q->fetch(PDO::FETCH_NUM)) {
        $found[] = $row[0];
    }
    echo json_encode(array($result, $found));
    exit(0);
}

/* churchyear.php?params=dayname
 * Returns the db parameters for dayname in the church year as json.
 */
if ($_GET['params']) {
    if (! $auth) {
        echo json_encode("Access denied.  Please log in.");
        exit(0);
    }
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
    $q = $dbh->prepare("SELECT `season`, `base`, `offset`, `month`, `day`,
        `observed_month`, `observed_sunday`
        FROM `{$dbp}churchyear`
        WHERE `dayname` = :dayname");
    $q->bindParam(":dayname", $_GET['params']);
    if ($q->execute()) {
        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode($row);
        } else {
            echo json_encode(array());
        }
    } else {
        echo json_encode("Problem with query: ".array_pop($q->errorInfo()));
    }
    exit(0);
}

/* churchyear.php?dayname=dayname
 * Returns a form for modifying the db parameters of dayname.
 */

if ($_GET['dayname']) {
    if (! $auth) {
        echo "Access denied.  Please log in.";
        exit(0);
    }
    $q = $dbh->prepare("SELECT `season`, `base`, `offset`, `month`, `day`,
        `observed_month`, `observed_sunday`
        FROM `{$dbp}churchyear`
        WHERE `dayname` = :dayname");
    $q->bindParam(":dayname", $_GET['dayname']);
    $q->execute();
    $specifics = $q->fetch(PDO::FETCH_ASSOC);
    /* Show a day edit form with dates in the surrounding 10 years */
?>
    <form id="dayform" name="dayform" method="post">
        <input type="hidden" name="submit_day" value="1">
        <dl>
        <dt><label for="dayname">Day Name</label></dt>
        <dd><input type="text" name="dayname" id="dayname"
            value="<?=$_GET['dayname']?>"></dd>
        <dt><label for="season">Season</label></dt>
        <dd><input type="text" name="season" id="season"
            value="<?=$specifics['season']?>"></dd>
        <dt><label for="base">Base Moveable Day</label></dt>
        <dd><select name="base" id="base">
            <option value="None">None</option>
            <option value="Easter"
                <?=$specifics['base']=="Easter"?"selected=\"selected\"":""?>>Easter</option>
            <option value="Christmas 1"
                <?=$specifics['base']=="Christmas 1"?"selected=\"selected\"":""?>>Christmas 1</option>
            <option value="Epiphany 1"
                <?=$specifics['base']=="Epiphany 1"?"selected=\"selected\"":""?>>Epiphany 1</option>
            <option value="Michaelmas 1"
                <?=$specifics['base']=="Michaelmas 1"?"selected=\"selected\"":""?>>Michaelmas 1</option>
            </select></dd>
        <dt><label for="offset">Offset from Base in Days</label></dt>
        <dd><input type="number" name="offset" id="offset"
            value="<?=$specifics['offset']?>"></dd>
        <dt><label for="month">Month</label></dt>
        <dd><input name="month" id="month" type="number" min="0" max="12"
            value="<?=$specifics['month']?>"></dd>
        <dt><label for="day">Day of Month</label></dt>
        <dd><input name="day" id="day" type="number" min="0" max="31"
            value="<?=$specifics['day']?>"></dd>
        <dt><label for="observed-month">Observed Month</label></dt>
        <dd><input name="observed-month" id="observed-month"
            type="number" min="0" max="12"
            value="<?=$specifics['observed_month']?>"></dd>
        <dt><label for="observed-sunday">Observed Sunday</label></dt>
        <dd><input name="observed-sunday" id="observed-sunday"
            type="number" min="0" max="31"
            value="<?=$specifics['observed_sunday']?>"></dd>
        </dl>
        <button class="dayform_submit" type="submit" name="submit">Submit</button>
        <button type="reset" name="reset">Reset</button>
    </form>
    <p>Calculated dates include:</p>
    <div id="calculated-dates">
    </div>
<?
    exit(0);
}

/* churchyear.php with POST of [del=>dayname]
 * Deletes the specified dayname from the churchyear table.
 */

if ($_POST['del']) {
    if (! $auth) {
        echo json_encode(array(0, "Access denied. Please log in."));
        exit(0);
    }
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
    $q = $dbh->prepare("DELETE FROM `{$dbp}churchyear`
        WHERE `dayname` = :dayname");
    $q->bindValue(":dayname", $_POST['del']);
    if ($q->execute()) {
        echo json_encode(array(true,
            churchyear_listing(query_churchyear(true))));
    } else {
        echo json_encode(array(false,
        "Problem deleting: ".array_pop($q->errorInfo())));
    }
    exit(0);
}

/* churchyear.php with POST of [submitday=>1]
 * Saves the submitted POST data to the included dayname.
 */

if ($_POST['submit_day']==1) {
    if (! $auth) {
        setMessage("Access denied. Please log in.");
        header("location: index.php");
        exit(0);
    }

    // Update/save supplied values for the given day
    unset($_POST['submit_day']);
    $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear`
        (dayname, season, base, offset, month, day,
        observed_month, observed_sunday)
        VALUES (:dayname, :season, :base, :offset, :month, :day,
            :observed_month, :observed_sunday)");
    $q->bindValue(":dayname", $_POST['dayname']);
    $q->bindValue(":season", $_POST['season']);
    $q->bindValue(":base", $_POST['base']);
    $q->bindValue(":offset", $_POST['offset']);
    $q->bindValue(":month", $_POST['month']);
    $q->bindValue(":day", $_POST['day']);
    $q->bindValue(":observed_month", $_POST['observed_month']);
    $q->bindValue(":observed_sunday", $_POST['observed_sunday']);
    if (! $q->execute()) {
        $q = $dbh->prepare("UPDATE `{$dbp}churchyear`
            SET season=:season,
            base=:base, offset=:offset,
            month=:month, day=:day,
            observed_month=:observed_month, observed_sunday=:observed_sunday
            WHERE dayname=:dayname");
        $q->bindValue(":dayname", $_POST['dayname']);
        $q->bindValue(":season", $_POST['season']);
        $q->bindValue(":base", $_POST['base']);
        $q->bindValue(":offset", $_POST['offset']);
        $q->bindValue(":month", $_POST['month']);
        $q->bindValue(":day", $_POST['day']);
        $q->bindValue(":observed_month", $_POST['observed_month']);
        $q->bindValue(":observed_sunday", $_POST['observed_sunday']);
        if (! $q->execute()) {
            setMessage("Problem saving: ". array_pop($q->errorInfo()));
        }
        header("location: churchyear.php");
        exit(0);
    }
}

if ($_POST['synonyms']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $synonyms = explode("\n", $_POST['synonyms']);
    $canonical = $_POST['canonical'];
    // Remove current entries for canonical
    $q = $dbh->prepare("DELETE FROM `{$dbp}churchyear_synonyms`
        WHERE canonical = ?");
    $success = $q->execute(array($canonical));
    if ($success) {
        $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_synonyms`
            (canonical, synonym) VALUES (?, ?)");
        foreach ($synonyms as $synonym) {
            $success = $q->execute(array($canonical, $synonym));
            if (! $success) break;
        }
        if ($success) $dbh->commit();
    }
    echo json_encode($success);
    exit(0);
}

if ($_GET['synonyms']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $q = $dbh->prepare("SELECT `synonym` FROM `{$dbp}churchyear_synonyms`
        WHERE `canonical` = ?");
    if ($q->execute(array($_GET['synonyms']))) {
        $rv = array();
        while ($aval = $q->fetch(PDO::FETCH_NUM)) {
            array_push($rv, $aval[0]);
        }
        if ($rv) echo json_encode(array(true, $rv));
        else echo json_encode(array(false));
    } else echo json_encode(array(false));
    exit(0);
}

if ($_POST['propers']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $q = $dbh->prepare("UPDATE `{$dbp}churchyear_propers` SET
        color=?, collect=?, collect2=?, collect3=?,
        oldtestament=?, oldtestament2=?, oldtestament3=?, gospel=?,
        gospel2=?, gospel3=?, epistle=?, epistle2=?, epistle3=?,
        psalm=?, psalm2=?, psalm3=?, theme=?, note=?
        WHERE dayname=?");
    if ($q->execute(array($_POST['color'], $_POST['collect'],
        $_POST['collect2'], $_POST['collect3'], $_POST['oldtestament'],
        $_POST['oldtestament2'], $_POST['oldtestament3'], $_POST['gospel'],
        $_POST['gospel2'], $_POST['gospel3'], $_POST['epistle'],
        $_POST['epistle2'], $_POST['epistle3'], $_POST['psalm'],
        $_POST['psalm2'], $_POST['psalm3'], $_POST['theme'], $_POST['note'],
        $_POST['propers']))) {
        echo json_encode(array($q->rowCount()));
        $dbh->commit();
    } else {
        echo json_encode(array(false, array_pop($q->errorInfo())));
    }
    exit(0);
}

if ($_GET['propers']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $q = $dbh->prepare("SELECT color, collect, collect2, collect3,
        oldtestament, oldtestament2, oldtestament3,
        epistle, epistle2, epistle3,
        gospel, gospel2, gospel3, psalm, psalm2, psalm3, theme, note
        FROM `{$dbp}churchyear_propers` WHERE dayname = :dayname");
    if (! ($q->execute(array("dayname"=>$_GET['propers']))
        && $rvdata = $q->fetch(PDO::FETCH_ASSOC))) {
        $rvdata = array("color"=>"", "collect"=>"", "collect2"=>"",
            "collect3"=>"", "oldtestament"=>"", "oldtestament2"=>"",
            "oldtestament3"=>"", "epistle"=>"", "epistle2"=>"",
            "epistle3"=>"", "gospel"=>"", "gospel2"=>"", "gospel3"=>"",
            "psalm"=>"", "psalm2"=>"", "psalm3"=>"", "theme"=>"",
            "note"=>"");
    }
    ob_start();
?>
    <form id="propersform" method="post">
    <input type="hidden" name="propers" value="<?=$_GET['propers']?>">
    <div class="formblock"><label for="color">Color</label><br>
    <input type="text" value="<?=$rvdata['color']?>" name="color"></div>
    <div class="formblock"><label for="theme">Theme</label><br>
    <input type="text" value="<?=$rvdata['theme']?>" name="theme"></div>
    <div class="formblock fullwidth"><label for="note">Note</label><br>
    <textarea name="note"><?=$rvdata['note']?></textarea></div>
    <div class="propersbox">
    <div class="formblock"><label for="oldtestament">Old Testament</label><br>
    <input type="text" value="<?=$rvdata['oldtestament']?>" name="oldtestament"></div>
    <div class="formblock"><label for="epistle">Epistle</label><br>
    <input type="text" value="<?=$rvdata['epistle']?>" name="epistle"></div>
    <div class="formblock"><label for="gospel">Gospel</label><br>
    <input type="text" value="<?=$rvdata['gospel']?>" name="gospel"></div>
    <div class="formblock"><label for="psalm">Psalm</label><br>
    <input type="text" value="<?=$rvdata['psalm']?>" name="psalm"></div>
    </div><div class="propersbox">
    <div class="formblock"><label for="oldtestament2">Old Testament 2</label><br>
    <input type="text" value="<?=$rvdata['oldtestament2']?>" name="oldtestament2"></div>
    <div class="formblock"><label for="epistle2">Epistle 2</label><br>
    <input type="text" value="<?=$rvdata['epistle2']?>" name="epistle2"></div>
    <div class="formblock"><label for="gospel2">Gospel 2</label><br>
    <input type="text" value="<?=$rvdata['gospel2']?>" name="gospel2"></div>
    <div class="formblock"><label for="psalm2">Psalm 2</label><br>
    <input type="text" value="<?=$rvdata['psalm2']?>" name="psalm2"></div>
    </div><div class="propersbox">
    <div class="formblock"><label for="oldtestament3">Old Testament 3</label><br>
    <input type="text" value="<?=$rvdata['oldtestament3']?>" name="oldtestament3"></div>
    <div class="formblock"><label for="epistle3">Epistle 3</label><br>
    <input type="text" value="<?=$rvdata['epistle3']?>" name="epistle3"></div>
    <div class="formblock"><label for="gospel3">Gospel 3</label><br>
    <input type="text" value="<?=$rvdata['gospel3']?>" name="gospel3"></div>
    <div class="formblock"><label for="psalm3">Psalm 3</label><br>
    <input type="text" value="<?=$rvdata['psalm3']?>" name="psalm3"></div>
    </div>
    <div class="formblock fullwidth"><label for="collect">Collect</label><br>
    <textarea name="collect"><?=$rvdata['collect']?></textarea></div>
    <div class="formblock fullwidth"><label for="collect2">Collect 2</label><br>
    <textarea name="collect2"><?=$rvdata['collect2']?></textarea></div>
    <div class="formblock fullwidth"><label for="collect3">Collect 3</label><br>
    <textarea name="collect3"><?=$rvdata['collect3']?></textarea></div>

    <button type="submit" id="submit">Submit</button>
    <button type="reset">Reset</button>
    </form>
<?
    echo json_encode(array(true, ob_get_clean()));
    exit(0);
}

if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
    exit(0);
}

?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Church Service")?>
<body>
<script type="text/javascript">
    $(document).ready(function() {
        setupEdit();
        setupDelete();
        $.get("churchyear.php", { params: "Michaelmas" },
            function(params) {
                sessionStorage.michaelmasObserved = params['observed_sunday'];
            });
        $(".synonym").click(function(evt) {
            evt.preventDefault();
            var loc = $(this).offset();
            var orig = $(this).attr("data-day");
            $.get("churchyear.php", {synonyms: orig},
                function(rv) {
                    rv = eval(rv);
                    if (rv[0]) {
                        var lines = rv[1].join("\n");
                    } else {
                        var lines = "";
                    }
                    $("#dialog").html('<form id="synonymsform" method="post">'
                        +'<textarea id="synonyms">'+lines+'</textarea><br>'
                        +'<button type="submit" id="submit">Submit</button>'
                        +'<button type="reset" id="reset">Reset</button>'
                        +'</form>');
                    $("#synonymsform").submit(function(evt) {
                        evt.preventDefault();
                        $.post("churchyear.php",
                    {synonyms: $("#synonyms").val(),
                     canonical: orig}, function(rv) {
                                $("#dialog").dialog("close");
                                rv = eval(rv);
                                if (rv) {
                                    setMessage("Saved synonyms.");
                                } else {
                                    setMessage("Failed to save synonyms");
                                }
                        });
                    });
                    $("#dialog").dialog({modal: true,
                        title: "Synonyms for "+orig,
                        width: $(window).width()*0.4,
                        maxHeight: $(window).height()*0.4,
                        position: [30, loc.top],
                    });
                });
        });
        $(".propers").click(function(evt) {
            evt.preventDefault();
            var loc = $(this).offset();
            var orig = $(this).attr("data-day");
            $.get("churchyear.php", {propers: orig},
                function(rv) {
                    rv = eval(rv);
                    if (! rv[0]) {
                        return;
                    }
                    $("#dialog").html(rv[1]);
                    $("#propersform").submit(function(evt) {
                        evt.preventDefault();
                        $.post("churchyear.php", $("#propersform").serialize(),
                            function(rv) {
                                $("#dialog").dialog("close");
                                rv = eval(rv);
                                if (rv) {
                                    setMessage("Saved propers.");
                                } else {
                                    setMessage("Failed to save propers");
                                }
                        });
                    });
                    $("#dialog").dialog({modal: true,
                        title: "Propers for "+orig,
                        width: $(window).width()*0.7,
                        maxHeight: $(window).height()*0.7,
                        position: "center"
                    });
                });
        });
    });

    function setupEdit() {
        // Set up edit links
        $(".edit").click(function(evt) {
            evt.preventDefault();
            var dtitle = $(this).attr("data-day");
            $("#dialog")
                .load(encodeURI("churchyear.php?dayname="
                    +$(this).attr("data-day")), function() {
                        $("#dialog").dialog({modal: true,
                            position: "center",
                            title: dtitle,
                            width: $(window).width()*0.7,
                            maxHeight: $(window).height()*0.7,
                            create: function() {
                                setupDialog();
                            },
                            open: function() {
                                setupDialog();
                            }});
                    });
        });
    }

    function setupDelete() {
        // Set up delete links
        $(".delete").click(function(evt) {
            evt.preventDefault();
            var dayname = $(this).attr("data-day");
            if (confirm("Delete the day '"+dayname+"'?")) {
                $.post("churchyear.php", {del: dayname}, function(rv) {
                    if (rv[0]) {
                        $("#churchyear-listing").replaceWith(rv[1]);
                        setupEdit();
                        setupDelete();
                    } else {
                        setMessage(rv[1]);
                    }
                });
            }
        });
    }

    function setupDialog() {
        function getDecadeDates() {
            // Return a 10-year span of matching dates.
            var decade = new Array();
            var now = new Date();
            var thisyear = now.getFullYear();
            for (y=thisyear-5; y<=thisyear+5; y++) {
                decade.push(getDateFor(y).toDateString());
            }
            return decade.join(", ");
        }
        var origdates = getDecadeDates();
        $("#calculated-dates").html(origdates);
        $("#base, #offset, #month, #day, #observed_month, #observed_sunday")
            .change(function() {
                var newdates = getDecadeDates();
                $("#calculated-dates").html(newdates);
            });
        $("#dayform").submit(function() {
            if ($('#dayname') == "Michaelmas") {
               sessionStorage.michaelmasObserved = $("#observed-sunday").val();
            }
         });
    }
</script>
<header>
<?=getLoginForm()?>
<?=getUserActions()?>
<? showMessage(); ?>
</header>
<?=sitetabs($sitetabs, $script_basename)?>
<div id="content-container">
<h1>Church Year Configuration</h1>
<?=churchyear_listing(query_churchyear())?>
</div>
<div id="dialog"></div>
</body>
</html>
<?
$dbh->commit();
?>
