<?
/* Edit the days in the church year
 */
require("init.php");

function get_date_for($dayname, $year, $specifics) {
    /* TODO: Compare the db calculation below to a php implementation
     * of the same thing. */
    $start = microtime();
    $rv = db_calc_date_for($dayname, $year, $specifics);
    $dbruntime = microtime() - $start;
    // Insert php implementation here.
    return $rv
}

function db_calc_date_for($dayname, $year, $specifics) {
    /* Given a liturgical day name, a year, and some defining specifics,
     * return the db's stored function calculation of the day's date
     * in that year. */
    $q = $dbh->prepare("SELECT `season`, `base`, `offset`, `month`, `day`, `observed_month`, `observed_sunday`");
    $q = $dbh->query("SELECT calc_date_in_year(:year, :dayname,
        :base, :offset, :month, :day)");
    $q->bindParam(":year", $year);
    $q->bindParam(":dayname", $dayname);
    $q->bindParam(":base", $specifics["base"]);
    $q->bindParam(":offset", $specifics["offset"]);
    $q->bindParam(":month", $specifics["month"]);
    $q->bindParam(":day", $specifics["day"]);
    $q->execute();
    return strptime($q->fetchColumn(0), "Y-m-d");
}

if (! $dbh->query("SELECT 1 FROM `{$dbp}churchyear`")) {
    $dbh->beginTransaction();
    /* Create the church year table */
    $q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear` (
        `dayname` varchar PRIMARY KEY,
        `season` varchar default NULL,
        `base` varchar default NULL,
        `offset` smallint default 0,
        `month` tinyint default 0,
        `day`   tinyint default 0,
        `observed_month` tinyint default 0,
        `observed_sunday` tinyint default 0
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
        `name` varchar PRIMARY KEY,
        `index` smallint UNIQUE)");
    $q->execute() or die(array_pop($q->errorInfo()));
    $q->exec("INSERT INTO {$dbp}churchyear_order (name, index) VALUES
        (\"Advent\", 1),
        (\"Christmas\", 2),
        (\"Epiphany\", 3),
        (\"Pre-lent\", 4),
        (\"Lent\", 5),
        (\"Easter\", 6),
        (\"Pentecost\", 7),
        (\"Trinity\", 8),
        (\"Michaelmas\", 9)");
    // Define helper functions on the db for getting the dates of days
    $functionsfile = "utility/churchyearfunctions.sql";
    $functionsfh = fopen($functionsfile, "rb");
    $result = $dbh->exec(readfile($functionsfh, filesize($functionsfile));
    fclose($functionsfh);
    $dbh->commit();
}

if ($_GET['dayname']) {
    if (! $auth) {
        echo "Access denied.  Please log in.";
        exit(0);
    }

    $q = $dbh->prepare("SELECT `season`, `base`, `offset`, `month`, `day`, `observed_month`, `observed_sunday`
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
            value="<?=$specifics['dayname']?>"></dd>
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
        echo date("mdY", get_date_for($_GET['dayname'], $y, $specifics));
    }
?>
    </div>
    <script type="javascript">
    $("#dayform base|offset|month|day|observed_month|observed_sunday").change();
    $("#dayform_submit").click(function() {
        // TODO
        // Submit the form to churchyear.php
        // Put the return value in message or the relevant table line
        // Close the dialog
        return;
     }
    // Flesh this out so that changes to the form update the dates listed.
    </script>
<?
}

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

$q = $dbh->query("SELECT cy.`season`, cy.`base`, cy.`offset`, cy.`month`,
    cy.`day`, cy.`observed_month`, cy.`observed_sunday`
    FROM `{$dbp}churchyear` AS cy
    JOIN `{$dbp}churchyear_order` AS cyo ON (cy.season = cyo.season)
    ORDER BY cyo.index, cy.offset, cy.month, cy.day
    ");
$q->execute();
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Church Service")?>
<body>
<script type="javascript">
    $(document).ready(function() {
        $("td.edit").click(function() {
            $("#dialog")
                .load("churchyear.php?dayname=".$(this).attr("data-day"))
                .dialog({modal: true,
                    width: $(window).width()*0.7,
                    maxHeight: $(window).height()*0.7,
                    close: function() {
                        setMessage("Edit cancelled.");
                    }});
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
<tr><td></td><th>Season</th><th>Base Day</th><th>Days Offset</th><th>Month</th>
    <th>Day</th><th>Observed Month</th><th>Observed Sunday</th></tr>
<? while ($row = $q->fetch(PDO::FETCH_ASSOC)) { ?>
<tr id="row_<?=$row['day']?>">
    <td class="edit" data-day="<?=$row['day']?>"</td>
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



