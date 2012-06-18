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
require("./init.php");
require("./churchyear/functions.php");
$auth = auth();

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
if ($_GET['request'] == 'dropfunctions') {
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
if ($_GET['request'] == 'purgetables') {
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
if ($_GET['request'] == "params") {
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

/* churchyear.php?requestform=dayname
 * Returns a form for modifying the db parameters of dayname.
 */
if ($_GET['requestform'] == 'dayname') {
    require("./churchyear/get_dayform.php");
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
if ($_GET['request'] == "synonyms") {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $q = $dbh->prepare("SELECT `synonym` FROM `{$dbp}churchyear_synonyms`
        WHERE `canonical` = ?");
    if ($q->execute(array($_GET['name']))) {
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
if ($_GET['request'] == "collect") {
    $q = $dbh->query("SELECT collect FROM `{$dbp}churchyear_collects`
        WHERE id = ?");
    $q->execute(array($_GET['id']));
    return json_encode($q->fetchColumn(0));
}

/* churchyear.php with _POST from the collect form below
 * Process the collect form (below) & create/update the collect.
 */
if ($_POST['existing-collect']) {
    if (! $auth) {
        echo json_encode(array(false, "Access denied. Please log in first."));
        exit(0);
    }
    if ($_POST['existing-collect'] == "new") {
        $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_collects`
            (class, collect) VALUES (?, ?)");
        if (!$q->execute()) {
            echo json_encode(false, array_pop($q->errorInfo()));
            $dbh->rollback();
            exit(0);
        }
        $qid = $dbh->query("SELECT LAST_INSERT_ID()");
        $qid = $qid->fetchColumn(0);
        $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_collect_index`
            (`dayname`, `lectionary`, `id`)
            VALUES (?, ?, ?)");
        if (! $q->execute(array($_POST['dayname'], $_POST['lectionary'], $qid))){
            echo json_encode(false, array_pop($q->errorInfo()));
            $dbh->rollback();
            exit(0);
        }
        $dbh->commit();
        echo json_encode(true);
        exit(0);
    }
    echo json_encode(array(true, array("class"=>"", "cid"=>"", "collect"=>"")));
    exit(0);
}

/* churchyear.php?requestform=collect&lectionary=[lect]&dayname=[name]
 * Return a form for the new collect dialog.
 */
if ($_GET['requestform'] == "collect") {
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

/* churchyear.php?requestform=delete-collect&cid=id
 * Supply a form for confirming the deletion of collect with given id
 */
if ($_POST['requestform'] = "delete-collect") {
    if (! $auth) {
        echo "Access denied.  Please log in.";
        exit(0);
    }
    // Show collect, lectionaries using it, and daynames when used
    $q = $dbh->prepare("SELECT
        c.collect, c.class, i.lectionary, i.dayname, i.id
        FROM `{$dbp}churchyear_collect_index` AS i
        JOIN `{$dbp}churchyear_collect` AS c ON (c.id = i.id)
        WHERE i.id = ?");
    if (! $q->execute(array($_POST['cid']))) {
        echo array_pop($q->errorInfo());
    } else {
        $row = $q->fetch(PDO::FETCH_ASSOC);
?>
    <h4><?=$row['class']?></h4>
    <p><?=$row['collect']?></p>
    <h4>Used:</h4>
    <ul>
    <li><?=$row['dayname']?> (<?=$row['lectionary']?>)</li>
    <? while ($q->fetch(PDO::FETCH_ASSOC)) {?>
    <li><?=$row['dayname']?> (<?=$row['lectionary']?>)</li>
    <?}?>
    </ul>
    <form id="delete-collect-confirm" method="post"
    action="churchyear.php?deletecollect=<?=$_POST['cid']?>">
    <button type="submit" name="submit">Confirm Collect</button>
    <button id="cancel-delete">Cancel Deletion</button>
    </form>
<?  }

}

/* churchyear.php with $_POST data from propers form
 * Update propers for the dayname.
 */
if ($_POST['propers']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $qi = $dbh->prepare("INSERT INTO `{$dbp}churchyear_propers`
        (color, theme, note, introit, dayname)
        VALUES (?, ?, ?, ?)");
    $qu = $dbh->prepare("UPDATE `{$dbp}churchyear_propers` SET
        color=?, theme=?, note=?, introit=? WHERE dayname=?");
    $valarray = array($_POST['color'], $_POST['theme'], $_POST['note'],
        $_POST['propers'], $_POST['introit']);
    if (!($qi->execute($valarray) || $qu->execute($valarray))) {
        echo json_encode(array_pop($q->errorInfo()));
        exit(0);
    }
    $i=1;
    $qhi = $dbh->prepare("INSERT INTO `{$dbp}churchyear_lessons`
        (lesson1, lesson2, gospel, psalm, s2lesson, s2gospel,
        s3lesson, s3gospel, lectionary, dayname)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $qhu = $dbh->prepare("UPDATE `{$dbp}churchyear_lessons`
        SET lesson1=?, lesson2=?, gospel=?, psalm=?, s2lesson=?,
        s2gospel=?, s3lesson=?, s3gospel=?,lectionary=?, dayname=? WHERE id=?");
    $qii = $dbh->prepare("INSERT INTO `{$dbp}churchyear_lessons`
        (lesson1, lesson2, gospel, psalm, hymnabc, hymn)
        VALUES (?, ?, ?, ?, ? ,?)");
    $qiu = $dbh->prepare("UPDATE `{$dbp}churchyear_lessons`
        SET lesson1=?, lesson2=?, gospel=?, psalm=?, hymnabc=?, hymn=?
        WHERE id=?");
    while (array_key_exists("lessons-{$i}", $_POST)) {
        $id = $_POST["lessons-{$i}"];
        if ($_POST['lessontype'] == "ilcw") {
            $query = array($_POST["l1-{$id}"], $_POST["l2-{$id}"],
                $_POST["go-{$id}"], $_POST["ps-{$id}"],
                $_POST["habc-{$id}"], $_POST["hymn-{$id}"], $_POST['propers']);
            $qi = $qu = $query;
            array_unshift($qi, $qii);
            array_push($qu, $_POST["id-{$id}"]);
            array_unshift($qu, $qiu);
        } else {
            $query = array($_POST["l1-{$id}"], $_POST["l2-{$id}"],
                $_POST["go-{$id}"], $_POST["ps-{$id}"],
                $_POST["s2l-{$id}"], $_POST["s2go-{$id}"],
                $_POST["s3l-{$id}"], $_POST["s3go-{$id}"], $_POST['propers']);
            $qi = $qu = $query;
            array_unshift($qi, $qhi);
            array_push($qu, $_POST["id-{$id}"]);
            array_unshift($qu, $qhu);
        }
        if (! ($qi[-1]->execute(array_slice($qi, 1)) ||
               $qu[-1]->execute(array_slice($qu, 1)))) {
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
    require("./churchyear/get_propersform.php");
}

/* churchyear.php?delpropers=id
 * Delete the lessons with the given id
 */
if ($_GET['delpropers']) {
    if (! $auth) {
        echo json_encode(array(false, "Access denied. Please log in"));
        exit(0);
    }
    $q = $dbh->prepare("DELETE FROM `{$dbh}churchyear_lessons` WHERE id = ?");
    if ($q->execute(array($_GET['delpropers']))) {
        echo json_encode(array(true));
    } else {
        echo json_encode(array(false, array_pop($q->errorInfo())));
    }
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
    <? require("./churchyear/ecmascript.js"); ?>
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
