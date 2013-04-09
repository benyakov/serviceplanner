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

/* churchyear.php?dropfunctions=1
 * Drops all the churchyear functions and sets a message about
 * creating them again.
 */
if ($_GET['request'] == 'dropfunctions') {
    $dbh->beginTransaction();
    $dbh->exec("DROP FUNCTION `{$dbp}easter_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}christmas1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}michaelmas1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}epiphany1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}calc_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}calc_observed_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}observed_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}`get_lesson`;
    DROP PROCEDURE IF EXISTS `{$dbp}get_days_for_date`");
    setMessage("Church year functions dropped.  To re-create them, visit"
        ." the Church Year tab.  They will be created automatically.");
    $dbh->commit();
    $dbstate->store("has-churchyear-functions", 0);
    $dbstate->save() or die("Problem saving dbstate file.");
    header("location: index.php");
    exit(0);
}

/* churchyear.php?purgetables=1
 * Purge the churchyear tables and set a message about populating
 * them again.
 */
if ($_GET['request'] == 'purgetables') {
    if (! $auth) {
        setMessage("Access denied.  Please log in.");
        header("location: index.php");
        exit(0);
    }
    $dbh->beginTransaction();
    $dbh->exec("DELETE FROM `{$dbp}churchyear_graduals`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_collects_index`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_collects`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_synonyms`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_lessons`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_propers`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear_order`");
    $dbh->exec("DELETE FROM `{$dbp}churchyear`");
    if ($dbstate->save()) {
        setMessage("Church year tables purged.  They should be re-populated "
            ."by the time you see this message.");
        $dbh->commit();
        $dbstate->store("churchyear-filled", 0);
    } else {
        setMessage("Problem saving dbstate config file.  Tables not purged.");
        $dbh->rollback();
    }
    header("location: index.php");
    exit(0);
}

/* churchyear.php?daysfordate=date
 * Returns a comma-separated list of daynames that match the date given.
 */
if ($_GET['daysfordate']) {
    echo json_encode(daysForDate($_GET['daysfordate']));
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
        echo json_encode(array(false, "Access denied. Please log in."));
        exit(0);
    }
    // Update/save supplied values for the given day
    unset($_POST['submit_day']);
    $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear`
        (season, base, offset, month, day,
        observed_month, observed_sunday, dayname)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $bound = array($_POST['season'], $_POST['base'],
        $_POST['offset'], $_POST['month'], $_POST['day'],
        $_POST['observed_month'], $_POST['observed_sunday'],
        $_POST['dayname']);
    if (! $q->execute($bound)) {
        $q = $dbh->prepare("UPDATE `{$dbp}churchyear`
            SET season=?, base=?, offset=?, month=?, day=?,
            observed_month=?, observed_sunday=?
            WHERE dayname=?");
        if (! $q->execute($bound)) {
            $rv = array(false, "Problem saving: ". array_pop($q->errorInfo()));
        } elseif ($q->rowCount() > 0) {
            $rv = array(true,
                "Saved parameters for existing day {$_POST['dayname']}");
        } else {
            $rv = array(false, "No changes made.");
        }
    } else {
        $rv = array(true, "Saved parameters for new day {$_POST['dayname']}.");
    }
    if ($rv[0]) {
        array_push($rv, churchyear_listing(query_churchyear()));
    }
    echo json_encode($rv);
    exit(0);
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
    $dbh->beginTransaction();
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
    $q = $dbh->prepare("SELECT collect, class FROM `{$dbp}churchyear_collects`
        WHERE id = ?");
    $q->execute(array($_GET['id']));
    echo json_encode($q->fetch(PDO::FETCH_NUM));
    exit(0);
}

/* churchyear.php with _POST from the collect form below
 * Process the collect form (below) & create/update the collect.
 */
if ($_POST['existing-collect']) {
    if (! $auth) {
        echo json_encode(false, "Access denied. Please log in first.");
        exit(0);
    }
    $dbh->beginTransaction();
    if ($_POST['existing-collect'] == "new") {
        $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_collects`
            (class, collect) VALUES (?, ?)");
        if (!$q->execute(array($_POST['collect-class'],
            $_POST['collect-text'])))
        {
            $rv = array(false, "Problem inserting new collect text: ".
                array_pop($q->errorInfo()));
        } else {
            $qid = $dbh->query("SELECT LAST_INSERT_ID()");
            $qid = $qid->fetchColumn(0);
            $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_collect_index`
                (`dayname`, `lectionary`, `id`) VALUES (?, ?, ?)");
            if (! $q->execute(array($_POST['dayname'],
                $_POST['lectionary'], $qid)))
            {
                $rv = array(false, "Problem inserting new collect: ".
                    array_pop($q->errorInfo()));
            } else {
                $dbh->commit();
                $rv = array(true,
                    "New collect inserted for {$_POST['dayname']}");
            }
        }
    } else {
        $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_collect_index`
            (`dayname`, `lectionary`, `id`) VALUES (?, ?, ?)");
        if (! $q->execute(array($_POST['dayname'], $_POST['lectionary'],
            $_POST['existing-collect'])))
        {
            $rv = array(false, "Problem inserting collect: ".
                array_pop($q->errorInfo()));
        } else {
            $dbh->commit();
            $rv = array(true,
                "Existing collect attached to {$_POST['dayname']}");
        }
    }
    if ($rv[0]) {
        require("./churchyear/get_propersform.php");
        array_push($rv, propersForm($_POST['dayname']));
    }
    echo json_encode($rv);
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
    $q = $dbh->prepare("SELECT c.class, i.dayname, i.lectionary, i.id
        FROM `{$dbp}churchyear_collect_index` AS i
        JOIN `{$dbp}churchyear_collects` AS c ON (c.id = i.id)
        WHERE i.dayname != ? OR i.lectionary != ?
        ORDER BY i.dayname, i.lectionary, c.class");
    if (! $q->execute(array($_GET['dayname'], $_GET['lectionary']))) {
        echo "Problem getting available collects: " .
            array_pop($q->errorInfo());
        exit(0);
    }
    ?>
    <form action="churchyear.php" id="collect-form" method="post">
        <h3>Collect used by "<?=$_GET['lectionary']?>" for
        "<?=$_GET['dayname']?>"</h3>
        <div class="fullwidth">
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
        <input type="text" required id="collect-class" name="collect-class"
            placeholder="Type or Series"></input><br>
        <textarea name="collect-text" id="collect-text" required></textarea><br>
        <button type="submit">Submit</button>
        <button type="reset">Reset</button>
        </div>
    </form>
    <?
    exit(0);
}

/* churchyear.php?requestform=delete-collect&cid=id
 * Supply a form for confirming the deletion of collect with given id
 */
if ($_GET['requestform'] == "delete-collect") {
    if (! $auth) {
        echo "Access denied.  Please log in.";
        exit(0);
    }
    // Show collect, lectionaries using it, and daynames when used
    $q = $dbh->prepare("SELECT
        c.collect, c.class, i.lectionary, i.dayname, i.id
        FROM `{$dbp}churchyear_collect_index` AS i
        RIGHT OUTER JOIN `{$dbp}churchyear_collects` AS c ON (c.id = i.id)
        WHERE c.id = ?
        GROUP BY i.lectionary, i.dayname");
    if (! $q->execute(array($_GET['cid']))) {
        echo array_pop($q->errorInfo());
    } else {
        $row = $q->fetch(PDO::FETCH_ASSOC);
?>
    <h4><?=$row['class']?></h4>
    <p><?=$row['collect']?></p>
    <h4>Used:</h4>
    <ul>
    <li><?=$row['dayname']?> (<?=$row['lectionary']?>) <a href="#" class="detach-collect" data-cid="<?=$_GET['cid']?>" data-lectionary="<?=$row['lectionary']?>" data-dayname="<?=$row['dayname']?>">Detach from this day and lectionary</a></li>
    <? while ($row = $q->fetch(PDO::FETCH_ASSOC)) {?>
    <li><?=$row['dayname']?> (<?=$row['lectionary']?>) <a href="#" class="detach-collect" data-cid="<?=$_GET['cid']?>" data-lectionary="<?=$row['lectionary']?>" data-dayname="<?=$row['dayname']?>">Detach from this day and lectionary</a></li>
    <?}?>
    </ul>
    <form id="delete-collect-confirm" method="post" action="churchyear.php">
    <input type="hidden" name="deletecollect" value="<?=$_GET['cid']?>">
    <input type="hidden" name="dayname" value="<?=$_GET['dayname']?>">
    <button type="submit" name="submit">Delete Collect Entirely</button>
    </form>
<?  }
    exit(0);
}

/* churchyear.php with $_POST of deletecollect=collectid
 * Delete the collect with the given id
 */
if ($_POST['deletecollect']) {
    if (! $auth) {
        echo json_encode("Access denied.  Please log in.");
        exit(0);
    }
    $q = $dbh->prepare("DELETE i, c FROM `{$dbp}churchyear_collect_index` AS i
        JOIN `{$dbp}churchyear_collects` AS c
        ON (i.id = c.id)
        WHERE i.id = :index");
    if (! $q->execute(array('index'=>$_POST['deletecollect']))) {
        $rv = array(false,
            "Problem deleting collect: ".array_pop($q->errorInfo()));
    } else {
        require("./churchyear/get_propersform.php");
        $rv = array(true, "Collect deleted.", propersForm($_POST['dayname']));
    }
    echo json_encode($rv);
    exit(0);
}

/* churchyear.php?detachcollect=id&lectionary=name&dayname=day
 * Detach the collect from the given day in the given lectionary
 */
if ($_GET['detachcollect']) {
    if (! $auth) {
        echo json_encode("Access denied.  Please log in.");
        exit(0);
    }
    $q = $dbh->prepare("DELETE FROM `{$dbp}churchyear_collect_index`
        WHERE dayname = ? AND lectionary = ? AND id = ?");
    if (! $q->execute(array($_GET['dayname'], $_GET['lectionary'],
        $_GET['detachcollect'])))
    {
        $rv = array(false, "Problem detaching collect: ".
            array_pop($q->errorInfo()));
    } else {
        require("./churchyear/get_propersform.php");
        $rv = array(true, "Collect detached from lectionary ".
            "'{$_GET['lectionary']}' on {$_GET['dayname']}.",
            propersForm($_GET['dayname']));
    }
    echo json_encode($rv);
    exit(0);
}

/* churchyear.php?propers=dayname
 * Show populated form for the propers of the given dayname
 */
if ($_GET['propers']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    require("./churchyear/get_propersform.php");
    echo json_encode(array(true, propersForm($_GET['propers'])));
    exit(0);
}

/* churchyear.php with $_POST containing propers = dayname
 * Submit provided changes to propers.
 */
if ($_POST['propers']) {
    if (! $auth) {
        echo json_encode(array(false, "Access denied.  Please log in."));
        exit(0);
    }
    $q = $dbh->prepare("UPDATE `{$dbp}churchyear_propers` SET
        color=?, theme=?, introit=?, note=? WHERE dayname = ?");
    if (! $q->execute(array($_POST['color'], $_POST['theme'],
        $_POST['introit'], $_POST['note'], $_POST['propers'])))
    {
        $rv = array(false, "Problem updating propers: ".
            array_pop($q->errorInfo()));
    } else {
        $rv = array(true, "Basic propers saved.");
    }
    echo json_encode($rv);
    exit(0);
}

/* churchyear.php with $_POST of "lessontype" = "historic"
 * Update lessons for the day/lectionary with the provided lessons.
 */
if ($_POST['lessontype'] == "historic") {
    if (! $auth) {
        echo json_encode(array(false, "Access denied.  Please log in."));
        exit(0);
    }
    $q = $dbh->prepare("UPDATE `{$dbp}churchyear_lessons` SET
       lectionary='historic', lesson1=?, lesson2=?, gospel=?, psalm=?,
       s2lesson=?, s2gospel=?, s3lesson=?, s3gospel=?, hymnabc='', hymn=''
       WHERE id=?");
    if (! $q->execute(array($_POST['l1'], $_POST['l2'], $_POST['go'],
        $_POST['ps'], $_POST['s2l'], $_POST['s2g'], $_POST['s3l'],
        $_POST['s3g'], $_POST['lessons'])))
    {
        $rv = array(false, "Problem updating propers: ".
            array_pop($q->errorInfo()));
    } else {
        $rv = array(true, "Historic lessons saved.");
    }
    echo json_encode($rv);
    exit(0);
}

/* churchyear.php with $_POST of "lessontype" = "ilcw"
 * Update lessons for the day/lectionary with the provided lessons.
 */
if ($_POST['lessontype'] == "ilcw") {
    if (! $auth) {
        echo json_encode(array(false, "Access denied.  Please log in."));
        exit(0);
    }
    $q = $dbh->prepare("UPDATE `{$dbp}churchyear_lessons` SET
       lesson1=?, lesson2=?, gospel=?, psalm=?,
       s2lesson='', s2gospel='', s3lesson='', s3gospel='',
       hymnabc=?, hymn=?  WHERE id=?");
    if (! $q->execute(array($_POST['l1'], $_POST['l2'], $_POST['go'],
        $_POST['ps'], $_POST['hymnabc'], $_POST['hymn'], $_POST['lessons'])))
    {
        $rv = array(false, "Problem updating propers: ".
            array_pop($q->errorInfo()));
    } else {
        $rv = array(true, "Lessons saved.");
    }
    echo json_encode($rv);
    exit(0);
}

/* churchyear.php with $_POST of "lessons" = "New"
 * Save the propers in the indicated lectionary
 */
if ($_POST['lessons'] == "New") {
    if (! $auth) {
        echo json_encode(array(false, "Access denied.  Please log in."));
        exit(0);
    }
    $q = $dbh->prepare("INSERT INTO `{$dbp}churchyear_lessons`
        (dayname, lectionary, lesson1, lesson2, gospel, psalm, hymnabc, hymn)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (! $q->execute(array($_POST['dayname'], $_POST['lectionary'],
        $_POST['l1'], $_POST['l2'], $_POST['go'], $_POST['ps'], $_POST['habc'],
        $_POST['hymn'])))
    {
        $rv = array(false,
            "Problem saving new lessons: ".array_pop($q->errorInfo()));
    } else {
        require("./churchyear/get_propersform.php");
        $rv = array(true,
            "New lessons saved in lectionary '{$_POST['lectionary']}'.",
            propersForm($_POST['dayname']));
    }
    echo json_encode($rv);
    exit(0);
}

/* churchyear.php?delpropers=id
 * Delete the lessons with the given id
 */
if ($_GET['delpropers']) {
    if (! $auth) {
        echo json_encode(array(false, "Access denied. Please log in."));
        exit(0);
    }
    $dbh->beginTransaction();
    $q = $dbh->prepare("SELECT dayname FROM `{$dbp}churchyear_lessons` AS l
        WHERE l.id = ?");
    if (! $q->execute(array($_GET['delpropers']))) {
        echo json_encode(array(false,
            "Could not get dayname for the lessons."));
        exit(0);
    } else {
        $dayname = $q->fetchColumn(0);
    }
    $q = $dbh->prepare("DELETE i, l
        FROM `{$dbp}churchyear_lessons` AS l
        LEFT OUTER JOIN `{$dbp}churchyear_collect_index` AS i
        ON (i.dayname = l.dayname AND  i.lectionary = l.lectionary)
        WHERE l.id = ?");
    if (! $q->execute(array($_GET['delpropers']))) {
        $rv = array(false,
            "Problem deleting propers: ".array_pop($q->errorInfo()));
    } else {
        $dbh->commit();
        require("./churchyear/get_propersform.php");
        $rv = array(true, "Propers deleted.", propersForm($dayname));
    }
    echo json_encode($rv);
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
<?
pageHeader();
siteTabs($auth);?>
<div id="content-container">
<h1>Church Year Configuration</h1>
<?=churchyear_listing(query_churchyear())?>
</div>
<div id="dialog"></div>
</body>
</html>
