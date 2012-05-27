<?
/* Edit the days in the church year
 */
require("init.php");
$auth = auth();
$cy_begin_marker = "# BEGIN Church Year Tables";
$cy_end_marker = "# END Church Year Tables";
$create_tables_file = "./utility/dynamictables.sql";

function get_date_for($dayname, $year, $specifics) {
    /* TODO: Compare the db calculation below to a php implementation
     * of the same thing. */
    $start = microtime();
    $rv = db_calc_date_for($dayname, $year, $specifics);
    $dbruntime = microtime() - $start;
    // Insert php implementation here.
    return $rv;
}

function db_calc_date_for($dayname, $year, $specifics) {
    /* Given a liturgical day name, a year, and some defining specifics,
     * return the db's stored function calculation of the day's date
     * in that year. */
    global $dbh;
    $q = $dbh->prepare("SELECT `{$dbp}calc_date_in_year`(:year, :dayname,
        :base, :offset, :month, :day)");
    $q->bindParam(":year", $year);
    $q->bindParam(":dayname", $dayname);
    $q->bindParam(":base", $specifics["base"]);
    $q->bindParam(":offset", $specifics["offset"]);
    $q->bindParam(":month", $specifics["month"]);
    $q->bindParam(":day", $specifics["day"]);
    $q->execute();
    return $q->fetchColumn(0);
        //strptime($q->fetchColumn(0), "Y-m-d");
}

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
    <td class="dayname"><?=$row['dayname']?></td>
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
    $allsql = array();
    $sql = 'CREATE TABLE `{{DBP}}churchyear` (
        `dayname` varchar(255),
        `season` varchar(64) default "",
        `base` varchar(255) default NULL,
        `offset` smallint default 0,
        `month` tinyint default 0,
        `day`   tinyint default 0,
        `observed_month` tinyint default 0,
        `observed_sunday` tinyint default 0,
        PRIMARY KEY (`dayname`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
    $q = $dbh->prepare(replaceDBP($sql)) ;
    $q->execute() or die(array_pop($q->errorInfo()));
    $allsql[] = replaceDBP($sql, "");
    $fh = fopen("historictable.csv", "r");
    $headings = fgetcsv($fh);
    while (($record = fgetcsv($fh, 250)) != FALSE) {
        $r = array();
        $record = quote_array($record);
        foreach ($record as $field) {
            $f = trim($field);
            if (! $f) {
                $f = "NULL";
            } elseif (! is_numeric($f)) {
                $f = "'$f'";
            }
            $r[] = $f;
        }
        $q = $dbh->prepare("INSERT INTO {$dbp}churchyear (season, dayname, base, offset, month, day, observed_month, observed_sunday)
            VALUES ({$r[0]}, {$r[1]}, {$r[2]}, {$r[3]}, {$r[4]}, {$r[5]}, {$r[6]}, {$r[7]})");
        $q->execute() or dieWithRollback($q, "\n".__FILE__.":".__LINE__);
    }
    // Define helper table for ordering the presentation of days
    $sql = "CREATE TABLE `{{DBP}}churchyear_order` (
        `name` varchar(32),
        `idx` smallint UNIQUE,
        PRIMARY KEY (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    $q = $dbh->prepare(replaceDBP($sql));
    $q->execute() or die(array_pop($q->errorInfo()));
    $allsql[] = replaceDBP($sql, "");
    $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_order`
        (name, idx) VALUES
        (\"Advent\", 1),
        (\"Christmas\", 2),
        (\"Epiphany\", 3),
        (\"Pre-lent\", 4),
        (\"Lent\", 5),
        (\"Easter\", 6),
        (\"Pentecost\", 7),
        (\"Trinity\", 8),
        (\"Michaelmas\", 9),
        (\"\", 32)");
    if (! $q->execute()) {
        echo "Problem inserting seasons: " . array_pop($q->errorInfo());
        exit(0);
    }
    // Write table descriptions to createtables.sql
    $tabledesc = $cy_begin_marker."\n"
        .implode("\n", $allsql)."\n"
        .$cy_end_marker."\n";
    if (! file_exists($create_tables_file)) {
        touch($create_tables_file);
    }
    $createtables = file_get_contents($create_tables_file);
    if (false === strpos($createtables, $cy_begin_marker)) {
        $fh = fopen($create_tables_file, "a");
        fwrite($fh, $tabledesc);
        fclose($fh);
    } else {
        $start = strpos($createtables, $cy_begin_marker);
        $len = strpos($createtables, $cy_end_marker) - $start;
        $newcontents = substr_replace($createtables, $tabledesc, $start, $len);
        $fh = fopen($create_tables_file, "w");
        fwrite($fh, $newcontents);
        fclose($fh);
    }
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
    $dbh->exec("DROP TABLE `{$dbp}churchyear`, `{$dbp}churchyear_order`");
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
    });

    function setupEdit() {
        // Set up edit links
        $(".edit").click(function(evt) {
            evt.preventDefault();
            $("#dialog")
                .load(encodeURI("churchyear.php?dayname="
                    +$(this).attr("data-day")), function() {
                        $("#dialog").dialog({modal: true,
                            width: $(window).width()*0.7,
                            maxHeight: $(window).height()*0.7,
                            close: function() {
                                setMessage("Edit cancelled.");
                            },
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
