<?
/* Edit the days in the church year
 */
require("init.php");
if (! $dbh->query("SELECT 1 FROM `{$dbp}churchyear`")) {
    /* Create the church year table */
    $q = $dbh->prepare("CREATE TABLE `{$dbp}churchyear` (
        `dayname` varchar NOT NULL,
        `season` varchar default NULL,
        `base` varchar default NULL,
        `offset` smallint default 0,
        `month` tinyint default 0,
        `day`   tinyint default 0,
        `observed_month` tinyint default 0,
        `observed_sunday` tinyint default 0,
        KEY `dayname` (`dayname`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8") ;
    $q->execute() or die(array_pop($q->errorInfo()));
    $dbh->beginTransaction();
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
    $result = $dbh->exec("
        DELIMITER $$;
        CREATE FUNCTION easter_in_year(p_year INT) RETURNS DATE
        DETERMINISTIC
        BEGIN
        SET @iD=0,@iE=0,@iQ=0,@iMonth=0,@iDay=0;
        SET @iD = 255 - 11 * (p_year % 19);
        SET @iD = IF (@iD > 50,(@iD-21) % 30 + 21,@iD);
        SET @iD = @iD - IF(@iD > 48, 1 ,0);
        SET @iE = (p_year + FLOOR(p_year/4) + @iD + 1) % 7;
        SET @iQ = @iD + 7 - @iE;
        IF @iQ < 32 THEN
            SET @iMonth = 3;
            SET @iDay = @iQ;
        ELSE
            SET @iMonth = 4;
            SET @iDay = @iQ - 31;
        END IF;
        RETURN STR_TO_DATE(CONCAT(p_year,'-',@iMonth,'-',@iDay),'%Y-%m-%d');
        END$$

        CREATE FUNCTION christmas1_in_year(p_year INT) RETURNS DATE
        DETERMINISTIC
        BEGIN
        SET @wdchristmas = DAYOFWEEK(CONCAT_WS('-', p_year, 12, 25));
        IF @wdchristmas = 1 THEN
            RETURN CONCAT_WS('-', p_year, 12, 25);
        ELSE
            RETURN DATE_ADD(CONCAT_WS('-', p_year, 12, 25), 8-@wdchristmas);
        END$$

        CREATE FUNCTION michaelmas1_in_year(p_year INT) RETURNS DATE
        DETERMINISTIC
        BEGIN
        SELECT observed_sunday FROM {$dbp}churchyear
            WHERE dayname=\"Michaelmas\" INTO @mike_observed;
        SET @michaelmas = STR_TO_DATE(CONCAT_WS('-', p_year, 9, 29));
        SET @wdmichaelmas = DAYOFWEEK(@michaelmas);
        IF @mike_observed != -1 && @wdmichaelmas = 7 THEN
            RETURN CONCAT_WS('-', p_year, 9, 30);
        END IF;
        SET @oct1 = CONCAT_WS('-', p_year, 10, 1);
        SET @oct1wd = DAYOFWEEK(@oct1);
        RETURN DATE_ADD(@oct1, 8-@oct1wd DAYS);
        END$$

        CREATE FUNCTION date_in_year (p_year INT, p_dayname STRING)
        RETURNS DATE
        BEGIN
        SELECT base, offset, month, day
            FROM {$dbp}churchyear
            WHERE dayname=p_dayname
            INTO base, offset, month, day;
        IF **IS_NULL(base) THEN RETURN CONCAT_WS('-', p_year, month, day);
        IF base = \"Easter\" THEN
            RETURN DATE_ADD(easter_in_year(p_year), offset DAYS);
        ELSEIF base = \"Christmas 1\" THEN
            RETURN DATE_ADD(christmas1_in_year(p_year), offset DAYS);
        ELSEIF base = \"Michaelmas 1\" THEN
            RETURN DATE_ADD(michaelmas1_in_year(p_year), offset DAYS);
        END IF;
        END$$

        CREATE FUNCTION observed_date_in_year (p_year INT, p_dayname STRING)
        RETURNS DATE
        BEGIN
        SELECT base, offset, observed_month, observed_sunday
            FROM {$dbp}churchyear
            WHERE dayname=p_dayname
            INTO base, offset, observed_month, observed_sunday;
        IF **IS_NULL(base) THEN
            IF **IS_NULL(observed_month) THEN
                SET @actual = date_in_year(p_year, p_dayname)
                IF DAYOFWEEK(@actual) > 1 THEN
                    RETURN DATE_ADD(@actual, 8-DAYOFWEEK(@actual) DAYS);
            END IF;
            IF observed_sunday > 0 THEN
                SET @firstofmonth =
                    CONCAT_WS('-', p_year, observed_month, 1);
                IF DAYOFWEEK(@firstofmonth) > 1 THEN
                    SET @firstofmonth = DATE_ADD(@firstofmonth, 8-DAYOFWEEK(@firstofmonth) DAYS);
                END IF;
                RETURN DATE_ADD(@firstofmonth, (observed_sunday-1)*7 DAYS);
            ELSE
                SET @lastofmonth =
                DATE_SUB(DATE_ADD(
                    CONCAT_WS('-', p_year, observed_month)
                    , 1 MONTH), 1 DAY);
                IF DAYOFWEEK(@lastofmonth > 1) THEN
                    SET @lastofmonth = DATE_SUB(@lastofmonth,
                        DAYOFWEEK(@lastofmonth)-1);
                END IF;
                RETURN DATE_ADD(@lastofmonth, (observed_sunday+1)*7 DAYS);
            END IF;
            END IF;
        END IF;
        END$$

        DELIMITER ;
        ");
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
    <div id="edit-day">
    <form id="dayform" name="dayform" method="post">
        <input type="hidden" name="submit-day" value="1">
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
        <button type="submit" name="submit">Submit</button>
        <button type="reset" name="reset">Reset</button>
    </form>
    <p>Calculated dates include:
<?
    $thisyear = date('Y');
    for ($y = $thisyear-5; $y<=$thisyear+5; $y++) {
        echo date("mdY", get_date_for($_GET['dayname'], $y, $specifics));
    }
    echo "</p>";
}

if ($_POST['submit-day']==1) {
    if (! $auth) {
        echo json_encode(array(0, "Access denied. Please log in."));
        exit(0);
    }

    // Update/save supplied values for the given day
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
    header("Content-type: application/json");
    unset($_POST['submit-day']);
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

$q = $dbh->query("SELECT `season`, `base`, `offset`, `month`, `day`, `observed_month`, `observed_sunday`
    FROM `{$dbp}churchyear`");
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Church Service")?>
<body>
<header>
<?=getLoginForm()?>
<?=getUserActions()?>
<? showMessage(); ?>
</header>
<?=sitetabs($sitetabs, $script_basename)?>
<div id="content-container">
<h1>Church Year Configuration</h1>


</div>
</body>
</html>



