<? /* Church year interface
    Copyright (C) 2012 Jesse Jacobsen

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
require("init.php");
$auth = auth();

/* Replace occurrences of {{DBP}} with $prefix or $dbp in text.
 */
function replaceDBP($text, $prefix=false) {
    global $dbp;
    if ($prefix !== false) {
        return str_replace('{{DBP}}', $prefix, $text);
    } else {
        return str_replace('{{DBP}}', $dbp, $text);
    }
}

/* Populate the church year table if necessary.
 */
$dbh->beginTransaction();
$tableTest = $dbh->query("SELECT 1 FROM `{$dbp}churchyear`");
if (! ($tableTest && $tableTest->fetchAll())) {
    require('./utility/fillservicetables.php');
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
    DROP FUNCTION IF EXISTS `{$dbp}christmas1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}michaelmas1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}epiphany1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}calc_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}calc_observed_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}observed_date_in_year`;
    DROP PROCEDURE IF EXISTS `{$dbp}get_days_for_date`");
    setMessage("Church year functions dropped.  To re-create them, visit"
        ." the Church Year tab.  They will be created automatically.");
    $dbh->commit();
    header("location: index.php");
    exit(0);
}

/* churchyear.php?purgetables=1
 * Purge the churchyear tables and set a message about populating
 * them again.
 */
if ($_GET['purgetables'] == 1) {
    $dbh->exec("DELETE FROM `{$dbp}churchyear_collects_index`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_collects`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_synonyms`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_lessons`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_propers`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_order`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear`");

    setMessage("Church year tables purged.  They will be re-populated "
        ."with default values next time you visit the Church Year tab.");
    $dbh->commit();
    header("location: index.php");
    exit(0);
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

/* churchyear.php?daysfordate=date
 * Returns a comma-separated list of daynames that match the date given.
 */
if ($_GET['daysfordate']) {
    echo json_encode(array($result, daysForDate($_GET['daysfordate'])));
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
        (season, base, offset, month, day,
        observed_month, observed_sunday, dayname)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $bound = array($_POST['dayname'], $_POST['season'], $_POST['base'],
        $_POST['offset'], $_POST['month'], $_POST['day'],
        $_POST['observed_month'], $_POST['observed_sunday']);
    if (! $q->execute($bound)) {
        $q = $dbh->prepare("UPDATE `{$dbp}churchyear`
            SET season=?, base=?, offset=?, month=?, day=?,
            observed_month=?, observed_sunday=?
            WHERE dayname=?");
        if (! $q->execute($bound)) {
            setMessage("Problem saving: ". array_pop($q->errorInfo()));
        }
        header("location: churchyear.php");
        exit(0);
    }
}

/* churchyear.php with $_POST of synonyms (lines) and canonical (dayname)
 * Update synonyms for canonical.
 */
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

/* churchyear.php?synonyms=dayname
 * Get synonyms for dayname.
 */
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

/* churchyear.php?collect=get&id=[id]
 * Return the collect text for the given collect id
 */
if ($_GET['collect'] == "get") {
    $q = $dbh->query("SELECT collect FROM `{$dbp}churchyear_collects`
        WHERE id = ?");
    $q->execute(array($_GET['id']));
    return json_encode($q->fetchColumn(0));
}

/* churchyear.php?collect=form&lectionary=[lect]&dayname=[name]
 * Return a form for the new collect dialog.
 */
if ($_GET['collect'] == "form") {
    if (! $auth) {
        echo "Access denied.  Please log in.";
        exit(0);
    }
    $q = $dbh->query("SELECT c.class, i.dayname, i.lectionary, i.id
        FROM `{$dbp}churchyear_collects` AS c
        JOIN `{$dbp}churchyear_collect_index` AS i ON (c.id == i.id)
        WHERE i.dayname != ? AND i.lectionary != ?");
    $q->execute(array($_GET['dayname'], $_GET['lectionary']));
    ?>
    <form action="churchyear.php" id="collect-form" method="post">
        <h3>Collect used by "<?=$_GET['lectionary']?>" for
        "<?=$_GET['dayname']?>"</h3>
        <input type="hidden" name="lectionary" value="<?=$_GET['lectionary']?>">
        <input type="hidden" name="dayname" value="<?=$_GET['dayname']?>">
        <select name="existing-collect" id="collect-dropdown">
        <option value="new">New</option>
        <? while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value=\"{$row['id']}\">".
                "{$row['dayname']} in {$row['lectionary']} ({$row['class']})".
                "</option>";
        } ?>
        </select>
        <textarea name="collect-text" id="collect-text">
        </textarea>
        <button type="submit">Submit</button>
    </form>
    <?
}

/* churchyear.php with $_POST data from propers form
 * Update propers for the dayname.
 */
if ($_POST['propers']) {
    // TODO: update this for new form (yikes!)
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $qi = $dbh->prepare("INSERT INTO `{$dbp}churchyear_propers`
        (color, theme, note, dayname)
        VALUES (?, ?, ?, ?)");
    $qu = $dbh->prepare("UPDATE `{$dbp}churchyear_propers` SET
        color=?, theme=?, note=? WHERE dayname=?");
    $valarray = array($_POST['color'], $_POST['theme'], $_POST['note'],
        $_POST['propers']);
    if (!($qi->execute($valarray) || $qu->execute($valarray))) {
        echo json_encode(array_pop($q->errorInfo()));
        exit(0);
    }
    $i=1;
    $qi = $dbh->prepare("INSERT INTO `{$dbp}churchyear_lessons`
        (oldtestament, epistle, gospel, psalm, introit, collect, label, dayname)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $qu = $dbh->prepare("UPDATE `{$dbp}churchyear_lessons`
        SET oldtestament=?, epistle=?, gospel=?, psalm=?, introit=?,
        collect=?, label=?, dayname=? WHERE id=?");
    while (array_key_exists("lessons-{$i}", $_POST)) {
        $id = $_POST["lessons-{$i}"];
        $values_insert = array($_POST["ot-{$id}"], $_POST["ep-{$id}"],
            $_POST["go-{$id}"], $_POST["ps-{$id}"], $_POST["in-{$id}"],
            $_POST["co-{$id}"], $_POST["la-{$id}"], $_POST['propers']);
        $values_update = $values_insert;
        array_push($values_update, $_POST["id-{$id}"]);
        $valarray
        if (! ($qi->execute($values_insert) || $qu->execute($values_update))) {
            echo json_encode(array_pop($q->errorInfo()));
        }
        $i++;
    }
    exit(0);
}

/* churchyear.php?propers=dayname
 * Show populated form for the propers of the given dayname
 */
if ($_GET['propers']) {
    $dayname = $_GET['propers'];
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $q = $dbh->prepare("SELECT pr.color, pr.theme, pr.introit, pr.note,
        l.lesson1, l.lesson2, l.gospel, l.psalm, l.s2lesson, l.s3gospel,
        l.s3lesson, l.s3gospel, l.id, l.lectionary, l.hymnabc, l.hymn
        FROM `{$dbp}churchyear_propers` AS pr
        LEFT OUTER JOIN `{$dbp}churchyear_lessons` AS l
            ON (pr.dayname = l.dayname)
            WHERE pr.dayname = ?
        ORDER BY l.lectionary");
    if (! $q->execute(array($dayname]))) {
        die(array_pop($q->errorInfo()));
        $pdata = array("color"=>"", "theme"=>"", "introit"=>"",
            "note"=>"", "lesson1"=>"", "lesson2"=>"", "gospel"=>"",
            "psalm"=>"", "s2lesson"=>"", "s2gospel"=>"", "s3lesson"=>"",
            "s3gospel"=>"", "id"=>0, "lectionary"=>"",
            "hymnabc"=>"", "hymn"=>"");
    } else {
        $pdata = ($q->fetchAll(PDO::FETCH_ASSOC));
    }
    $q = $dbh->prepare("SELECT i.lectionary, c.class, c.collect, c.id
        FROM `{$dbp}churchyear_collect_index` AS i
        JOIN `{$dbp}churchyear_collects` AS c
            ON (i.id = c.id)
        WHERE i.dayname = ?
        ORDER BY i.lectionary, c.class");
    if (! $q->execute(array($dayname]))) {
        die(array_pop($q->errorInfo()));
        $cdata = array();
    } else {
        $cdata = ($q->fetchAll(PDO::FETCH_ASSOC));
    }
    ob_start();
?>
    <form id="propersform" method="post">
    <input type="hidden" name="propers" id="propers" value="<?=$dayname]?>">
    <div class="formblock"><label for="color">Color</label><br>
    <input type="text" value="<?=$pdata[0]['color']?>" name="color"></div>
    <div class="formblock"><label for="theme">Theme</label><br>
    <input type="text" value="<?=$pdata[0]['theme']?>" name="theme"></div>
    <div class="formblock fullwidth"><label for="note">Note</label><br>
    <textarea name="note"><?=$pdata[0]['note']?></textarea><br></div>
    <div class="formblock fullwidth"><label for="introit">Introit</label><br>
    <textarea name="introit"><?=$pdata[0]['introit']?></textarea></div>
    <div id="accordion">
    <? $i = 1;
    foreach ($pdata as $lset) {
        $id = $lset['id'];
        if (! $lset['lectionary'] == "historic") {
    ?>
    <h3 class="propers-<?=$id?>">
        <a href="#"><?=strtoupper($lset['lectionary'])?></a></h3>
    <div class="propers-<?=$id?>">
    <a href="#" class="delete-these-propers" data-id="<?=$id?>"
        data-lect="<?=$lset['lectionary']?>">Delete these propers</a>
    <div class="propersbox">
    <input type="hidden" name="lessons-<?=$i?>" value="<?=$id?>">
    <div class="formblock"><label for="l1-<?=$id?>">Lesson 1</label><br>
    <input type="text" value="<?=$lset['lesson1']?>" name="l1-<?=$id?>"></div>
    <div class="formblock"><label for="l2-<?=$id?>">Lesson 2</label><br>
    <input type="text" value="<?=$lset['lesson2']?>" name="l2-<?=$id?>"></div>
    <div class="formblock"><label for="go-<?=$id?>">Gospel</label><br>
    <input type="text" value="<?=$lset['gospel']?>" name="go-<?=$id?>"></div>
    <div class="formblock"><label for="ps-<?=$id?>">Psalm</label><br>
    <input type="text" value="<?=$lset['psalm']?>" name="ps-<?=$id?>"></div>
    <div class="formblock"><label for="s2l-<?=$id?>">Series 2 Lesson</label><br>
    <input type="text" value="<?=$lset['s2lesson']?>" name="s2l-<?=$id?>"></div>
    <div class="formblock"><label for="s2go-<?=$id?>">Series 2 Gospel</label><br>
    <input type="text" value="<?=$lset['s2gospel']?>" name="s2go-<?=$id?>"></div>
    <div class="formblock"><label for="s3l-<?=$id?>">Series 3 Lesson</label><br>
    <input type="text" value="<?=$lset['s3lesson']?>" name="s3l-<?=$id?>"></div>
    <div class="formblock"><label for="s3go-<?=$id?>">Series 3 Gospel</label><br>
    <input type="text" value="<?=$lset['s3gospel']?>" name="s3go-<?=$id?>"></div>
    </div>
    <div class="propersbox"> <?
    foreach ($cdata as $cset) {
        $cid = $cset['id'];
        if ($cset['lectionary'] = $lset['lectionary']) { ?>
            <div class="formblock fullwidth">
            <label for="collect-<?=$cid?>"><?=$cset['class']?></label>
            <a href="#" class="delete-collect" data-id="<?=$cid?>">Delete</a>
            <br>
            <textarea name="collect-<?=$cid?>"><?=$cset['collect']?></textarea>
            </div> <?
        }
    } ?>
    <a href="#" class="add-collect"
        data-lectionary="<?=$lset['lectionary']?>">New Collect</a>
    </div>
    </div>
    <? } else { ?>
    <h3 class="propers-<?=$id?>">
        <a href="#"><?=strtoupper($lset['lectionary'])?></a></h3>
    <div class="propers-<?=$id?>">
    <a href="#" class="delete-these-propers" data-id="<?=$id?>"
        data-lect="<?=$lset['lectionary']?>">Delete these propers</a>
    <div class="propersbox">
    <input type="hidden" name="lessons-<?=$i?>" value="<?=$id?>">
    <div class="formblock"><label for="l1-<?=$id?>">Lesson 1</label><br>
    <input type="text" value="<?=$lset['lesson1']?>" name="l1-<?=$id?>"></div>
    <div class="formblock"><label for="l2-<?=$id?>">Lesson 2</label><br>
    <input type="text" value="<?=$lset['lesson2']?>" name="l2-<?=$id?>"></div>
    <div class="formblock"><label for="go-<?=$id?>">Gospel</label><br>
    <input type="text" value="<?=$lset['gospel']?>" name="go-<?=$id?>"></div>
    <div class="formblock"><label for="ps-<?=$id?>">Psalm</label><br>
    <input type="text" value="<?=$lset['psalm']?>" name="ps-<?=$id?>"></div>
    <div class="formblock"><label for="habc-<?=$id?>">General Hymn</label><br>
    <input type="text" value="<?=$lset['hymnabc']?>" name="habc-<?=$id?>"></div>
    <div class="formblock"><label for="hymn-<?=$id?>">Series Hymn</label><br>
    <input type="text" value="<?=$lset['hymn']?>" name="hymn-<?=$id?>"></div>
    </div>
    <div class="propersbox"> <?
    foreach ($cdata as $cset) {
        $cid = $cset['id'];
        if ($cset['lectionary'] = $lset['lectionary']) { ?>
            <div class="formblock fullwidth">
            <label for="collect-<?=$cid?>"><?=$cset['class']?></label>
            <a href="#" class="delete-collect" data-id="<?=$cid?>">Delete</a>
            <br>
            <textarea name="collect-<?=$cid?>"><?=$cset['collect']?></textarea>
            </div> <?
        }
    }?>
    <a href="#" class="add-collect"
        data-lectionary="<?=$lset['lectionary']?>">New Collect</a>
    </div>
    </div> <?
    }
    $i++;
    } $i++; ?>
    </div>
    <div class="hiddentemplate" id="propers-template" data-identifier="<?=$i?>">
    <h3 class="new-propers-{{id}}"><a href="#">New Propers</a></h3>
    <div class="new-propers-{{id}}">
    <a href="#" class="abort-new-propers"
        data-id="{{id}}">Abort New Propers</a>
    <div class="propersbox">
    <input type="hidden" name="lessons-<?=$i?>" value="{{id}}">
    <div class="formblock"><label for="lectionary-{{id}}">Lectionary</label><br>
    <input type="text" value="" name="lectionary-{{id}}" required></div>
    <div class="formblock"><label for="l1-{{id}}">Lesson 1</label><br>
    <input type="text" value="" name="l1-{{id}}"></div>
    <div class="formblock"><label for="l2-{{id}}">Lesson 2</label><br>
    <input type="text" value="" name="l2-{{id}}"></div>
    <div class="formblock"><label for="go-{{id}}">Gospel</label><br>
    <input type="text" value="" name="go-{{id}}"></div>
    <div class="formblock"><label for="ps-{{id}}">Psalm</label><br>
    <input type="text" value="" name="ps-{{id}}"></div>
    <div class="formblock"><label for="habc-{{id}}">General Hymn</label><br>
    <input type="text" value="" name="habc-{{id}}"></div>
    <div class="formblock"><label for="hymn-{{id}}">Series Hymn</label><br>
    <input type="text" value="" name="hymn-{{id}}"></div>
    </div>
    <div class="propersbox">
    <a href="#" class="add-collect"
        data-lectionary="">New Collect</a>
    </div>
    </div>
    </div>
    <button type="submit" id="submit">Submit</button>
    <button type="reset">Reset</button>
    <button id="addpropers">Add Propers</button>
    </form>
    <script type="text/javascript">
        $("#accordion").accordion();
        $("#addpropers").click(function() {
            var template = $("#propers-template").html();
            var identifier = $("#propers-template").attr("data-identifier");
            $("#propers-template").attr("data-identifier", identifier+1);
            template = template.replace("{{id}}", identifier);
            $('#lectionary-'+identifier).update(function() {
                $('#addcollect-'+identifier)
                    .attr("data-lectionary", $(this).val());
            });
            $("#accordion").append(template);
            $("a.abort-new-propers").click(function() {
                if (confirm("Remove new propers?  (Changes will be lost!)")) {
                    var id=$(this).attr("data-id");
                    $(".new-propers-"+id).remove();
                }
            });
        });
        $(".delete-these-propers").click(function() {
            if (confirm("Delete propers? (Listed collects will still exist.)")) {
                var id = $(this).attr("data-id");
                // TODO: implement this deletion code on the server side
                $.get("churchyear.php", {
                    delpropers: $("#propers").val(),
                    lectionary: $(this).attr("data-lect")},
                    function(rv) {
                        if (rv[0]) {
                            $(".propers-"+id).remove();
                        } else {
                            setMessage(rv[1]);
                        }
                    });
            }
        });
        $(".add-collect").click(function() {
            $.get("churchyear.php", {
                collect: 'form',
                lectionary: $(this).attr("data-lectionary"),
                dayname: $("#propers").val()},
                function(rv) {
                    if (! ($("#dialog2"))) {
                        $("#dialog").after('<div id="dialog2"></div>');
                    }
                    $("#dialog2").html(rv);
                    $("#dialog2").dialog({modal: true,
                        position: "center",
                        title: "New Collect",
                        width: $(window).width()*0.6,
                        maxHeight: $(window).height()*0.7,
                        create: function() {
                            setupCollectDialog(this);
                        },
                        open: function() {
                            setupCollectDialog(this);
                        },
                        close: function() {
                            $("#dialog2").html("");
                        }});
            });
        });
        $("#delete-collect").click(function(){
            // TODO: Display new dialog showing collect and which lectionaries
            // use it when to confirm deletion.
            return;
        });
        // TODO: handle deletion of propers sets
    </script>
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
<?=html_head("Church Year")?>
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

    function getDateFor(year) {
        // With the current settings of the form, calculate the date
        // in the given year
        var offset = new Number($("#offset").val());
        if ($("#base").val() == "None") {
            if ($("#observed-month").val()) {
                if (Number($("#observed-sunday").val())>0) {
                    var odate = new Date(year, $("#observed-month").val()-1, 1);
                    odate.setDate(odate.getDate() + (7-odate.getDay()));
                    odate.setDate(odate.getDate() +
                        ($("#observed-sunday").val()-1));
                    return odate;
                } else {
                    var odate = new Date(year, $("#observed-month").val(), 0);
                    odate.setDate(odate.getDate() - odate.getDay());
                    odate.setDate(odate.getDate() +
                        (Number($("#observed-sunday").val())+1));
                    return odate;
                }
            } else {
                return new Date(year, $("#month").val()-1, $("#day").val());
            }
        } else if ("Easter" == $("#base").val()) {
            var base = calcEaster(year);
            base.setDate(base.getDate()+offset);
            return base;
        } else if ("Christmas 1" == $("#base").val()) {
            var base = calcChristmas1(year);
            base.setDate(base.getDate()+offset);
            return base;
        } else if ("Michaelmas 1" == $("#base").val()) {
            var base = calcMichaelmas1(year);
            base.setDate(base.getDate()+offset);
            return base;
        } else if ("Epiphany 1" == $("#base").val()) {
            var base = calcEpiphany1(year);
            base.setDate(base.getDate()+offset);
            return base;
        }
    }

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
                                setupEditDialog();
                            },
                            open: function() {
                                setupEditDialog();
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

    function setupCollectDialog(addlink) {
        $("#collect-form").submit(function() {
            // TODO: Set up submit on server side
            $.post("churchyear.php", $(this).serialize(), function(rv) {
                if (rv[0]) {
                    $("#dialog2").dialog("close");
                    var cid = rv[1]["cid"];
                    var html = Array('<div class="formblock fullwidth">',
                     '<label for="collect-'+cid+'">'+rv[1]["class"]+'</label> ',
                     '<a href="#" class="delete-collect" data-id="'+cid+'">Delete</a><br>',
                     '<textarea name="collect-'+cid+'">'+rv[1]["collect"]+'</textarea></div>')
                     .join("\n");
                    $("#addlink2").before(html);
                }
            });
        });
        $("#collect-dropdown").change(function() {
            var choice = $(this).val();
            if (choice != "new") {
                $.get("churchyear.php", { collect: "get", id: choice },
                    function(rv) {
                        $("#collect-text").val(rv);
                });
        });
    }

    function setupEditDialog() {
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
