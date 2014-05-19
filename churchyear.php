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
$dbp = $db->getPrefix();

/* churchyear.php?dropfunctions=1
 * Drops all the churchyear functions and sets a message about
 * creating them again.
 */
if ($_GET['request'] == 'dropfunctions') {
    $db->beginTransaction();
    $db->exec("DROP FUNCTION IF EXISTS `{$dbp}easter_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}christmas1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}michaelmas1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}epiphany1_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}calc_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}calc_observed_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}observed_date_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}calendar_date_in_year`;
    DROP PROCEDURE IF EXISTS `{$dbp}get_days_for_date;
    DROP FUNCTION IF EXISTS `{$dbp}next_in_year`;
    DROP FUNCTION IF EXISTS `{$dbp}get_selected_lesson`");
    setMessage("Church year functions dropped. "
        ." they will be re-created automatically.");
    $db->commit();
    $dbstate = getDBState(true);
    $dbstate->set("has-churchyear-functions", 0);
    $dbstate->save() or die("Problem saving dbstate file.");
    unset($dbstate);
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
    } elseif (! 'password' == $_SESSION[$sprefix]['authdata']['authtype']) {
        authcookie(False);
        session_destroy();
        require("./setup-session.php");
        setMessage("Data loss possible.  Please authenticate your identity and try again.");
        header("location: index.php");
        exit(0);
    }
    $db->beginTransaction();
    $db->exec("DELETE FROM `{$dbp}churchyear_graduals`");
    $db->exec("DELETE FROM `{$dbp}churchyear_collect_index`");
    $db->exec("DELETE FROM `{$dbp}churchyear_collects`");
    $db->exec("DELETE FROM `{$dbp}churchyear_synonyms`");
    $db->exec("DELETE FROM `{$dbp}churchyear_lessons`");
    $db->exec("DELETE FROM `{$dbp}churchyear_propers`");
    $db->exec("DELETE FROM `{$dbp}churchyear_order`");
    $db->exec("DELETE FROM `{$dbp}churchyear`");
    $dbstate = getDBState(true);
    setMessage("Church year tables purged.  Repopulating...");
    $db->commit();
    $dbstate->set("churchyear-filled", 0);
    $dbstate->save();
    unset($dbstate);
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
    $q = $db->prepare("SELECT `season`, `base`, `offset`, `month`, `day`,
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
    $q = $db->prepare("DELETE FROM `{$dbp}churchyear`
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
    if ("None" == $_POST['base']) {
        $_POST['base'] = "";
        $_POST['offset'] = 0;
    }
    $q = $db->prepare("INSERT INTO `{$dbp}churchyear`
        (season, base, offset, month, day,
        observed_month, observed_sunday, dayname)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $bound = array($_POST['season'], $_POST['base'],
        $_POST['offset'], $_POST['month'], $_POST['day'],
        $_POST['observed-month'], $_POST['observed-sunday'],
        $_POST['dayname']);
    if (! $q->execute($bound)) {
        $q = $db->prepare("UPDATE `{$dbp}churchyear`
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

/* Do the work of updating existing synonyms from a new list.
 * Called by the next two options.
 */
function updateSynonyms($oldlist, $newlist, $canonical, $confirmed=array()) {
    $db = new DBConnection();
    $dbp = $db->getPrefix();
    $extra = array();
    for ($i=0, $len=count($newlist); $i<=$len; $i++) {
        if (! array_key_exists($i, $oldlist)) { // Insert a new synonym
            if ("" == $newlist[$i]) continue;       // filter out unintended blanks
            $q = $db->prepare("INSERT INTO `{$dbp}churchyear_synonyms`
                (canonical, synonym) VALUES (?, ?)");
            $q->bindValue(1, $canonical);
            $q->bindValue(2, $newlist[$i]);
            $q->execute();
        } else { // Update an existing synonym
            if ("" == $newlist[$i]) { // Delete this one
                $extra[] = $oldlist[$i];
                continue;
            }
            $q = $db->prepare("UPDATE `{$dbp}churchyear_synonyms`
                SET `synonym` = ?
                WHERE `canonical` = ?
                AND `synonym` = ?");
            $q->bindValue(1, $newlist[$i]);
            $q->bindValue(2, $canonical);
            $q->bindValue(3, $oldlist[$i]);
            $q->execute();
        }
    }
    if ($extra) {
        if ($extra != $confirmed) {  // Check the extra are still extra
            // Abort
            return false;
        }
        $placeholders = implode(',', array_fill(0, count($extra), '?'));
        $q = $db->prepare("DELETE FROM `{$dbp}churchyear_propers`
            WHERE `dayname` IN({$placeholders})");
        $q->execute($extra);
        $q = $db->prepare("DELETE FROM `{$dbp}churchyear_lessons`
            WHERE `dayname` IN({$placeholders})");
        $q->execute($extra);
        $q = $db->prepare("DELETE FROM `{$dbp}churchyear_collect_index`
            WHERE `dayname` IN({$placeholders})");
        $q->execute($extra);
        $q = $db->prepare("DELETE FROM `{$dbp}churchyear_synonyms`
            WHERE `synonym` IN({$placeholders})");
        $q->execute($extra);
    }
    return true;
}

/* churchyear.php with $_POST of commitsynonyms (canonical dayname)
 * Pulls the list of items for comfirmed deletion from the $_SESSION.
 */
if ($_POST['commitsynonyms']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    list($new, $del) = $_SESSION[$sprefix]['commitsynonyms'];
    $canonical = $_POST['commitsynonyms'];
    $db->beginTransaction();
    $q = $db->prepare("SELECT `synonym` FROM `{$dbp}churchyear_synonyms`
        WHERE `canonical` = ? ORDER BY `synonym` ASC");
    $q->bindValue(1, $canonical);
    $q->execute();
    $old = array_map(array_pop, $q->fetchAll(PDO::FETCH_NUM));
    if (updateSynonyms($old, $new, $canonical, $del)) {
        $db->commit();
        echo json_encode(array(true, "Synonyms successfully changed."));
    } else {
        $db->rollback();
        echo json_encode(array(false, "A problem occurred. ".
            "Someone else may have changed the database ".
            "between your submission and your confirmation of deletions. ".
            "You can try again or try to diagnose the problem."));
    }
    unset($_SESSION[$sprefix]['commitsynonyms']);
    exit(0);
}

/* churchyear.php with $_POST of synonyms (lines) and canonical (dayname)
 * Update synonyms for canonical.
 */
if ($_POST['submitsynonyms']) {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $synonyms = explode("\n", $_POST['synonyms']);
    $canonical = $_POST['canonical'];
    $delsynonyms = array();
    $db->beginTransaction();
    $q = $db->prepare("SELECT `synonym` FROM `{$dbp}churchyear_synonyms`
        WHERE `canonical` = ? ORDER BY `synonym` ASC");
    $q->bindValue(1, $canonical);
    $q->execute();
    $olddblist = array_map(array_pop, $q->fetchAll(PDO::FETCH_NUM));
    if (count($olddblist) > count($synonyms)) {
        echo json_encode(array(false, "If you wish to delete synonyms, ".
            "change each one into ".
            "a blank line when editing the synonym list.<br>"));
        exit(0);
    }
    for ($i=0, $len=count($olddblist); $i<$len; $i++) {
        // Directed deletes
        if ("" == $synonyms[$i]) {
            $delsynonyms[] = $olddblist[$i];
        }
    }
    if ($delsynonyms) {
        // Verify the desire to lose data.
        $db->rollback();
        $_SESSION[$sprefix]['commitsynonyms'] = array($synonyms, $delsynonyms);
        $confirm = "<p>Please confirm deletion of propers, lessons, ".
            "and collect assignments ".
            "for the following existing daynames: ".
            implode(", ", $delsynonyms).".</p>".
            "<form method=\"post\" id=\"commitsynonyms\">".
            "<input type=\"hidden\" name=\"cs\" id=\"commitsynonymsfield\" value=\"{$canonical}\">".
            "<button type=\"submit\">I Confirm</button></form>".
            "<script type=\"text/javascript\">".
            "setupCommitSynonymsForm();".
            "</script>";
        echo json_encode(array('confirm', $confirm));
        exit();
    } else {
        updateSynonyms($olddblist, $synonyms, $canonical);
        $db->commit();
        echo json_encode(array('true', "Synonyms updated."));
        exit(0);
    }
}

/* churchyear.php?synonyms=dayname
 * Get synonyms for dayname.
 */
if ($_GET['request'] == "synonyms") {
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $q = $db->prepare("SELECT `synonym` FROM `{$dbp}churchyear_synonyms`
        WHERE `canonical` = ? ORDER BY `synonym` ASC");
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
    $q = $db->prepare("SELECT collect, class FROM `{$dbp}churchyear_collects`
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
    $db->beginTransaction();
    if ($_POST['existing-collect'] == "new") {
        $q = $db->prepare("INSERT INTO `{$dbp}churchyear_collects`
            (class, collect) VALUES (?, ?)");
        if (!$q->execute(array($_POST['collect-class'],
            $_POST['collect-text'])))
        {
            $rv = array(false, "Problem inserting new collect text: ".
                array_pop($q->errorInfo()));
        } else {
            $qid = $db->query("SELECT LAST_INSERT_ID()");
            $qid = $qid->fetchColumn(0);
            $q = $db->prepare("INSERT INTO `{$dbp}churchyear_collect_index`
                (`dayname`, `lectionary`, `id`) VALUES (?, ?, ?)");
            if (! $q->execute(array($_POST['dayname'],
                $_POST['lectionary'], $qid)))
            {
                $rv = array(false, "Problem inserting new collect: ".
                    array_pop($q->errorInfo()));
            } else {
                $db->commit();
                $rv = array(true,
                    "New collect inserted for {$_POST['dayname']}");
            }
        }
    } else {
        $q = $db->prepare("INSERT INTO `{$dbp}churchyear_collect_index`
            (`dayname`, `lectionary`, `id`) VALUES (?, ?, ?)");
        if (! $q->execute(array($_POST['dayname'], $_POST['lectionary'],
            $_POST['existing-collect'])))
        {
            $rv = array(false, "Problem inserting collect: ".
                array_pop($q->errorInfo()));
        } else {
            $db->commit();
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
    $q = $db->prepare("SELECT c.class, i.dayname, i.lectionary, i.id
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
    $q = $db->prepare("SELECT
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
    $q = $db->prepare("DELETE i, c FROM `{$dbp}churchyear_collect_index` AS i
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
    $q = $db->prepare("DELETE FROM `{$dbp}churchyear_collect_index`
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
    $q = $db->prepare("UPDATE `{$dbp}churchyear_propers` SET
        color=?, theme=?, introit=?, gradual=?, note=? WHERE dayname = ?");
    $q->bindValue(1, $_POST['color']);
    $q->bindValue(2, $_POST['theme']);
    $q->bindValue(3, $_POST['introit']);
    if ($_POST['gradual'] === "") // To avoid trumping the seasonal gradual
        $q->bindValue(4, null, PDO::PARAM_NULL);
    else
        $q->bindValue(4, $_POST['gradual']);
    $q->bindValue(5, $_POST['note']);
    $q->bindValue(6, $_POST['propers']);
    if (! $q->execute()) {
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
    $q = $db->prepare("UPDATE `{$dbp}churchyear_lessons` SET
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
    $q = $db->prepare("UPDATE `{$dbp}churchyear_lessons` SET
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
    $q = $db->prepare("INSERT INTO `{$dbp}churchyear_lessons`
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
    $db->beginTransaction();
    $q = $db->prepare("SELECT dayname FROM `{$dbp}churchyear_lessons` AS l
        WHERE l.id = ?");
    if (! $q->execute(array($_GET['delpropers']))) {
        echo json_encode(array(false,
            "Could not get dayname for the lessons."));
        exit(0);
    } else {
        $dayname = $q->fetchColumn(0);
    }
    $q = $db->prepare("DELETE i, l
        FROM `{$dbp}churchyear_lessons` AS l
        LEFT OUTER JOIN `{$dbp}churchyear_collect_index` AS i
        ON (i.dayname = l.dayname AND  i.lectionary = l.lectionary)
        WHERE l.id = ?");
    if (! $q->execute(array($_GET['delpropers']))) {
        $rv = array(false,
            "Problem deleting propers: ".array_pop($q->errorInfo()));
    } else {
        $db->commit();
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

if ($_GET['request'] == 'purgetables') fillServiceTables();

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

<p class="explanation">This page allows adjustments and customization of the
Church Year (when the various days should fall) and the propers assigned to
those days.  The system determines when the days fall by calculating three base
days for a given year.  All other days are either set in relation to one of
them, or set in the secular calendar.  Some days may be observed on a certain
Sunday of a certain month.  To facilitate finding the desired propers, the days
of the church year may be assigned synonyms in addition to their primary names.
Finally, the propers for each day may be set, including collects and the texts
in any number of lectionaries.  When the gradual is not set for a day, a
seasonal gradual will be used.  Everything here can be restored to default
settings on the Housekeeping tab.  Customizations may also be exported from
there for off-site storage or transfer, and also re-imported.</p>

<?=churchyear_listing(query_churchyear())?>
</div>
<div id="dialog"></div>
</body>
</html>
<?
// vim: set foldmethod=indent :
?>
