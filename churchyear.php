<?
/* Edit the days in the church year
 */
require("init.php");
$auth = auth();

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
    $q = $dbh->prepare("SELECT calc_date_in_year(:year, :dayname,
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

if (! $dbh->query("SELECT 1 FROM `{$dbp}churchyear`")) {
    $dbh->beginTransaction();
    /* Create the church year table */
    $q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear` (
        `dayname` varchar(255),
        `season` varchar(64) default NULL,
        `base` varchar(255) default NULL,
        `offset` smallint default 0,
        `month` tinyint default 0,
        `day`   tinyint default 0,
        `observed_month` tinyint default 0,
        `observed_sunday` tinyint default 0,
        PRIMARY KEY (`dayname`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8") ;
    $q->execute() or die(array_pop($q->errorInfo()));
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
    $q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear_order` (
        `name` varchar(32),
        `idx` smallint UNIQUE,
        PRIMARY KEY (`name`))");
    $q->execute() or die(array_pop($q->errorInfo()));
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
        (\"Michaelmas\", 9)");
    if (! $q->execute()) {
        echo "Problem inserting seasons: " . array_pop($q->errorInfo());
        exit(0);
    }
    // Define helper functions on the db for getting the dates of days
    $functionsfile = "utility/churchyearfunctions.sql";
    $functionsfh = fopen($functionsfile, "rb");
    $result = $dbh->exec(fread($functionsfh, filesize($functionsfile)));
    fclose($functionsfh);
    $dbh->commit();
}

/* churchyear.php?params=dayname
 * Returns the db parameters for dayname in the church year as json.
 */

if ($_GET['params']) {
    if (! $auth) {
        echo json_encode("Access denied.  Please log in.");
        exit(0);
    }
    /* header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
     */
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
        <button id="dayform_submit" type="submit" name="submit">Submit</button>
        <button type="reset" name="reset">Reset</button>
    </form>
    <p>Calculated dates include:</p>
    <div id="calculated-dates">
<?
    $thisyear = date('Y');
    for ($y = $thisyear-5; $y<=$thisyear+5; $y++) {
        echo date("mdY", get_date_for($_GET['dayname'], $y, $specifics))." ";
    }
?>
    </div>
    <script type="text/javascript">
    function calcEaster(year) {
        // Borrowed from Emacs
        var a = year % 19;
        var b = year / 100;
        var c = year % 100;
        var d = b / 4;
        var e = b % 4;
        var f = (b+8)/25;
        var g = (b-f+1)/3;
        var h = (19*a+b-d-g+15)%30;
        var i = c/4;
        var k = c%4;
        var l = (32+2*e+2*i-h-k)%7;
        var m = (a+11*h+22*l)/451;
        var month = (h+l-7*m+114)/31;
        var p = (h+l-7*m+114)%31;
        var day = p+1;
        return new Date(year, month, day);
    }
    function calcChristmas1(year) {
        var christmas = new Date(year, 12, 25);
        if (christmas.getDay() == 0) {
            return christmas;
        } else {
            return new Date(christmas.valueOf() +
                (7-christmas.getDay())*24*60*60*1000);
        }
    }
    function calcMichaelmas1(year, callback) {
        var michaelmas = new Date(year, 9, 29);
        if (sessionStorage.michaelmasObserved != -1 && michaelmas.getDay == 6) {
            return new Date(year, 9, 30);
        } else {
            var oct1 = new Date(year, 10, 1);
            return new Date(oct1.valueOf() +
                (7-oct1.getDay())*24*60*60*1000);
        }
    }
    function getDateFor(year) {
        // With the current settings of the form, calculate the date
        // in the given year
        if (! $("#base").val()) {
            return new Date(year, $("#month"), $("#day"));
        } else if ("Easter" == $("#base").val()) {
            return new Date(calcEaster(year).valueOf() +
                $("#offset")*24*60*60*1000);
        } else if ("Christmas 1" == $("#base").val()) {
            return new Date(calcChristmas1(year).valueOf() +
                $("#offset")*24*60*60*1000);
        } else if ("Michaelmas 1" == $("#base").val()) {
            return new Date(calcMichaelmas1(year).valueOf() +
                $("#offset")*24*60*60*1000);
        }
    }
    $("#base, #offset, #month, #day, #observed_month, #observed_sunday")
        .change(function() {
            var decade = new Array();
            var now = new Date();
            var thisyear = now.getFullYear();
            for (y=thisyear-5; y<=thisyear+5; y++) {
                decade.push(getDateFor(y).toLocaleDateString());
            }
            $("#calculated-dates").html(decade.join(" "));
        });
    $("#dayform_submit").click(function() {
        if ($('#dayname') == "Michaelmas") {
           sessionStorage.michaelmasObserved = $("#observed-sunday").val();
        }
        $.post('churchyear.php', {
                submit_day: 1,
                dayname: $("#dayname").val(),
                season: $("#season").val(),
                base: $("#base").val(),
                offset: $("#offset").val(),
                month: $("#month").val(),
                day: $("#day").val(),
                observed_month: $("#observed-month").val(),
                observed_sunday: $("#observed-sunday").val()
            }, function(result) {
                if (result[1]) {
                    // Update the relevant part of the table
                    $("#row_"+$("#dayname").val()).html(
                        '<td class="edit" data-day="'+$("#day").val()+'"</td>'
                        +'<td>'+$("#season").val()+'</td>'
                        +'<td>'+$("#base").val()+'</td>'
                        +'<td>'+$("#offset").val()+'</td>'
                        +'<td>'+$("#month").val()+'</td>'
                        +'<td>'+$("#day").val()+'</td>'
                        +'<td>'+$("#observed-month").val()+'</td>'
                        +'<td>'+$("#observed-sunday").val()+'</td>'
                    );
                }
                $("#dialog").dialog("close");
                setMessage(result);
            });
     });
    </script>
<?
    exit(0);
}

/* churchyear.php with POST of [submitday=>1]
 * Saves the submitted POST data to the included dayname.
 */

if ($_POST['submit_day']==1) {
    if (! $auth) {
        echo json_encode(array(0, "Access denied. Please log in."));
        exit(0);
    }

    // Update/save supplied values for the given day
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
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
    if ($q->execute()) {
        echo json_encode(array(1, "New day {$_POST['dayname']} saved.",
        get_date_for($_POST['dayname'], date('Y'), $_POST)));
        exit(0);
    } else {
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
        if ($q->execute()) {
            echo json_encode(array(1, "Day {$_POST['dayname']} updated.",
            get_date_for($_POST['dayname'], date('Y'), $_POST)));
        } else {
            echo json_encode(array(0, "Problem saving: ". array_pop($q->errorInfo())));
        }
        exit(0);
    }
}
if (! $auth) {
    setMessage("Access denied.  Please log in.");
    header("location: index.php");
    exit(0);
}

$q = $dbh->prepare("SELECT cy.`dayname`, cy.`season`, cy.`base`,
    cy.`offset`, cy.`month`, cy.`day`,
    cy.`observed_month`, cy.`observed_sunday`
    FROM `{$dbp}churchyear` AS cy
    JOIN `{$dbp}churchyear_order` AS cyo ON (cy.season = cyo.name)
    ORDER BY cyo.idx, cy.offset, cy.month, cy.day
    ");
if (! $q->execute()) {
    echo "Problem querying database:" . array_pop($q->errorInfo());
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Church Service")?>
<body>
<script type="text/javascript">
    $(document).ready(function() {
        $(".edit > a").click(function() {
            $(this).attr('href', 'javascript: void(0);');
            $("#dialog")
                .load(encodeURI("churchyear.php?dayname="
                    +$(this).attr("data-day")))
                .dialog({modal: true,
                    width: $(window).width()*0.7,
                    maxHeight: $(window).height()*0.7,
                    close: function() {
                        setMessage("Edit cancelled.");
                    }});
        });
        $.get("churchyear.php", { params: "Michaelmas" },
            function(params) {
                sessionStorage.michaelmasObserved = params['observed_sunday'];
            });
    });
</script>
<header>
<?=getLoginForm()?>
<?=getUserActions()?>
<? showMessage(); ?>
</header>
<?=sitetabs($sitetabs, $script_basename)?>
<div id="content-container">
<h1>Church Year Configuration</h1>
<table id="churchyear-listing">
<tr><td></td><th>Name</th><th>Season</th><th>Base Day</th><th>Days Offset</th><th>Month</th>
    <th>Day</th><th>Observed Month</th><th>Observed Sunday</th></tr>
<? while ($row = $q->fetch(PDO::FETCH_ASSOC)) { ?>
<tr id="row_<?=$row['dayname']?>">
    <td class="edit"><a href="" data-day="<?=$row['dayname']?>">Edit</a></td>
    <td class="dayname"><?=$row['dayname']?></td>
    <td><?=$row['season']?></td>
    <td><?=$row['base']?></td>
    <td><?=$row['offset']?></td>
    <td><?=$row['month']?></td>
    <td><?=$row['day']?></td>
    <td><?=$row['observed_month']?></td>
    <td><?=$row['observed_sunday']?></td></tr>
<? } ?>
</table>
</div>
<div id="dialog"></div>
</body>
</html>



