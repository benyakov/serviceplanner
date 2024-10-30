<?php /* PHP function library

    Copyright (C) 2023 Jesse Jacobsen

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
    Lakewood Lutheran Church
    10202 112th St. SW
    Lakewood, WA 98498
    USA
 */

require_once("authfunctions.php");
require_once("classes.php");

/**
 * Factory function for a Configfile object.
 * Note that it blocks on the config file as long as it exists,
 * so unset the object when it's no longer needed.
 */
function getDBState($writelock=false) {
    $dbstate = new Configfile("./dbstate.ini", false, $writelock);
    return $dbstate;
}

/**
 * Factory functions for Configfile objects.
 * Note that it blocks on the config file as long as it exists,
 * so unset the object when it's no longer needed.
 */
function getConfig($writelock=false) {
    $config = new Configfile("./config.ini", true, true, $writelock);
    return $config;
}
function getOptions($writelock=false) {
    $options = new Configfile("./options.ini", true, true, $writelock);
    return $options;
}
function makePathAbsolute($path) {
    if (strpos($path, '/') != 0) {
        $inpath = __DIR__ . DIRECTORY_SEPARATOR . $inpath;
    }
    return $path;
}

function getLectionaryNames() {
    $db = new DBConnection();
    $result = $db->query("SELECT DISTINCT `lectionary` FROM
        `{$db->getPrefix()}churchyear_lessons`");
    return $result->fetchAll(PDO::FETCH_COLUMN, 0);
}

function getCollectClasses() {
    $db = new DBConnection();
    $result = $db->query("SELECT DISTINCT `class` FROM
        `{$db->getPrefix()}churchyear_collects`");
    return $result->fetchAll(PDO::FETCH_COLUMN, 0);
}

function checkJsonpReq() {
    return getGET('jsonpreq');
}

function checkContentReq() {
    return getGET('contentonly');
}

function queryLectionary($id) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT
            l.lesson1, l.lesson2, l.gospel, l.psalm, l.s2lesson, l.s2gospel,
            l.s3lesson, l.s3gospel, l.hymnabc, l.hymn, l.note
        FROM `{$dbp}days` AS d
        JOIN `{$dbp}blocks` AS b ON (d.block = b.id)
        JOIN `{$dbp}churchyear_lessons` AS l
            ON (l.lectionary=b.golect AND l.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)))
        WHERE d.pkey = :id");
    if ($id) $q->bindParam(":id", $id);
    $start_time = microtime(true);
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    $end_time = microtime(true);
    $GLOBALS['query_elapsed_time'] = $end_time - $start_time;
    return $q;
}

function queryService($id) {
    $where = array("d.pkey = :id");
    $options = getOptions();
    $combine_occ_option = $options->get("combineoccurrences");
    unset($options);
    $q = rawQuery($where, "", "", (1 == $combine_occ_option));
    if ($id) $q->bindParam(":id", $id);
    $start_time = microtime(true);
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    $end_time = microtime(true);
    $GLOBALS['query_elapsed_time'] = $end_time - $start_time;
    return $q;
}

function queryFutureHymns() {
    $where = array("d.caldate >= CURDATE()");
    $options = getOptions();
    $combine_occ_option = $options->get("combineoccurrences");
    unset($options);
    if (1 == $combine_occ_option) {
        $q = rawQuery($where, "", "", true);
    } else {
        $q = rawQuery($where, "", "", false);
    }
    $start_time = microtime(true);
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    $end_time = microtime(true);
    $GLOBALS['query_elapsed_time'] = $end_time - $start_time;
    return $q;
}

function querySomeHymns($limit) {
    $q = rawQuery(array(), "DESC", $limit);
    $start_time = microtime(true);
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    $end_time = microtime(true);
    $GLOBALS['query_elapsed_time'] = $end_time - $start_time;
    return $q;
}

/* This seems to work, but something else breaks when you use it. The code is
   therefore duplicated in records-table.php and modify.php.
function setupDateRange($options) {
    # Determine the sort direction of the listing
    if ("All" == getGET('submit'))
        $_SESSION[$sprefix]['modifyorder'] = "All";
    elseif ("Future" == getGET('submit'))
        $_SESSION[$sprefix]['modifyorder'] = "Future";
    if (! array_key_exists('modifyorder', $_SESSION[$sprefix]))
        $_SESSION[$sprefix]['modifyorder'] =
            $options->getDefault('All', 'modifyorder');
    else
        $options->set('modifyorder', $_SESSION[$sprefix]['modifyorder']);
    if ("Apply" == getGET('submit')) {
        if (getGET('allfuture')) {
            $allfuture = $_SESSION[$sprefix]["allfuture"] = true;
        } else {
            $allfuture = $_SESSION[$sprefix]["allfuture"] = false;
        }
    } elseif (isset($_SESSION[$sprefix]["allfuture"]))
        $allfuture = $_SESSION[$sprefix]["allfuture"];
    else $allfuture = false;
    # Determine the date range to be used in the current view
    if (getGET('lowdate')) {
        $lowdate = new DateTime(getGET('lowdate'));
        $_SESSION[$sprefix]["lowdate"] = $lowdate;
    } elseif (!$_SESSION[$sprefix]["lowdate"]) {
        $lowdate = new DateTime();
        $lowdate->sub(new DateInterval("P".
            $options->getDefault('1', 'past-range')."W"));
        $_SESSION[$sprefix]["lowdate"] = $lowdate;
    } else $lowdate = $_SESSION[$sprefix]['lowdate'];

    if (getGET('highdate')) {
        $highdate = new DateTime(getGET('highdate'));
        $_SESSION[$sprefix]["highdate"] = $highdate;
    } elseif (!getIndexOr($_SESSION[$sprefix],"highdate")) {
        $highdate = new DateTime();
        $highdate->add(new DateInterval("P".
            $options->getDefault('1', 'future-range')."W"));
        $_SESSION[$sprefix]["highdate"] = $highdate;
    } else $highdate = $_SESSION[$sprefix]['highdate'];

    return [ $lowdate, $highdate, $allfuture ];
}
*/

function queryServiceDateRange($lowdate, $highdate, $allfuture, $order="DESC") {
    $limited = ! $allfuture;
    $ld = $lowdate->format("Y-m-d");
    $hd = $highdate->format("Y-m-d");
    if ($limited) {
        $where[] = "d.caldate BETWEEN :lowdate AND :highdate" ;
    } else {
        $where[] = "d.caldate >= :lowdate";
        $highdate = "";
    }
    $options = getOptions();
    $combine_occ_option = $options->get("combineoccurrences");
    unset($options);
    if (1 == $combine_occ_option) {
        $q = rawQuery($where, $order, "", true);
    } else {
        $q = rawQuery($where, $order, "", false);
    }
    $q->bindValue(":lowdate", $ld);
    if ($limited) $q->bindValue(":highdate", $hd);
    $start_time = microtime(true);
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    $end_time = microtime(true);
    $GLOBALS['query_elapsed_time'] = $end_time - $start_time;
    return $q;
}

function rawQuery($where=array(), $order="", $limit="", $blend_occurrences=false) {
    if ($where) $wherestr = "WHERE ".implode(" AND ", $where);
    else $wherestr = "";
    if ($limit) $limitstr = "LIMIT {$limit}";
    else $limitstr = "";
    if ($blend_occurrences) {
        $occ_seq = "h.sequence, h.occurrence";
    } else {
        $occ_seq = "h.occurrence, h.sequence";
    }
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT d.pkey AS serviceid,
    DATE_FORMAT(d.caldate, '%c/%e/%Y') AS date,
    DATE_FORMAT(d.caldate, '%Y-%m-%d') AS browserdate,
    h.book, h.number, h.note, h.occurrence, d.name AS dayname, d.rite,
    d.servicenotes, n.title, d.block,
    b.label AS blabel, b.notes AS bnotes,
    cyp.color AS color, cyp.theme AS theme, cyp.introit AS introit,
    (CASE b.weeklygradual
        WHEN '1' THEN cyp.weeklygradual
        ELSE cyp.seasonalgradual
        END)
        AS gradual,
    cyp.note AS propersnote,
    (smr.bibletext IS NOT NULL) AS has_sermon,
    COALESCE(l1s.lesson1, l1s.l1series) AS blesson1,
    COALESCE(l2s.lesson2, l2s.l2series) AS blesson2,
    COALESCE(gos.gospel, gos.goseries) AS bgospel,
    COALESCE(gos.hymnabc, l2s.hymnabc, l1s.hymnabc, synl.hymnabc) as hymnabc,
    COALESCE(gos.hymn, l2s.hymn, l1s.hymn, synl.hymn) as hymn,
    COALESCE(smr.bibletext,
             (CASE b.smtype
              WHEN 'gospel' THEN sms.gospel
              WHEN 'lesson1' THEN sms.lesson1
              WHEN 'lesson2' THEN sms.lesson2
             END), sms.smseries) AS bsermon,
    (CASE b.pslect
        WHEN 'custom' THEN b.psseries
        ELSE
            (SELECT psalm FROM `{$dbp}synlessons` AS cl
            WHERE cl.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)) AND cl.lectionary=b.pslect
            LIMIT 1)
        END)
        AS bpsalm,
    synl.note AS sermonlessonnote,
    b.l1lect != 'custom' AS l1link,
    b.l2lect != 'custom' AS l2link,
    b.golect != 'custom' AS golink,
    b.pslect != 'custom' AS pslink,
    b.smlect != 'custom' AS smlink,
    b.coclass AS bcollectclass,
    (SELECT collect FROM `{$dbp}churchyear_collects` AS cyc
    JOIN `{$dbp}churchyear_collect_index` AS cci
    ON (cyc.id = cci.id)
    WHERE cci.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)) AND cci.lectionary=b.colect
    AND cyc.class=b.coclass
    LIMIT 1) AS bcollect
    FROM `{$dbp}days` AS d
    LEFT OUTER JOIN `{$dbp}hymns` AS h ON (h.service = d.pkey)
    LEFT OUTER JOIN `{$dbp}sermons` AS smr ON (d.pkey = smr.service)
    LEFT OUTER JOIN `{$dbp}names` AS n ON (h.number=n.number AND h.book=n.book)
    LEFT OUTER JOIN `{$dbp}blocks` AS b ON (b.id = d.block)
    LEFT OUTER JOIN `{$dbp}synpropers` AS cyp ON
        (cyp.dayname = TRIM(SUBSTRING_INDEX(d.name, '|', 1)))
    LEFT JOIN `{$dbp}lesson1selections` AS l1s
    ON (l1s.l1lect=b.l1lect AND l1s.l1series<=>b.l1series AND
        l1s.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)))
    LEFT JOIN `{$dbp}lesson2selections` AS l2s
    ON (l2s.l2lect=b.l2lect AND l2s.l2series<=>b.l2series AND
        l2s.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)))
    LEFT JOIN `{$dbp}gospelselections` AS gos
    ON (gos.golect=b.golect AND gos.goseries<=>b.goseries AND
        gos.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)))
    LEFT JOIN `{$dbp}sermonselections` AS sms
    ON (sms.smlect=b.smlect AND sms.smseries<=>b.smseries AND
        sms.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)))
    LEFT JOIN `{$dbp}synlessons` AS synl
    ON (synl.dayname=TRIM(SUBSTRING_INDEX(d.name, '|', 1)) AND synl.lectionary=b.smlect)
    {$wherestr}
    ORDER BY d.caldate {$order}, d.pkey {$order},
        {$occ_seq} {$limitstr}");
    return $q;
}

function getFlagsFor($serviceid, $occurrence, $raw=false) {
    // Return cached flag list if it exists. Otherwise, pull it from DB, cache and return
    $lw = new LogWriter('./cache/log');
    $md5occ = md5($occurrence);
    $cachename = "./cache/flags/{$serviceid}";
    if (! file_exists($cachename)) {
        mkdir($cachename, 0750, true);
    }
    if (file_exists("{$cachename}/{$md5occ}")) {
        $lw->write("HIT: {$serviceid}/{$md5occ} ({$occurrence})\n");
        $package = file_get_contents("{$cachename}/{$md5occ}");
    } else {
        $lw->write("MISS: {$serviceid}/{$md5occ} ({$occurrence})\n");
        $db = new DBConnection();
        $q = $db->prepare("SELECT f.flag, f.value, f.pkey AS flagid,
            CONCAT(u.fname, ' ', u.lname) AS user
            FROM `{$db->getPrefix()}service_flags` AS f
            JOIN `{$db->getPrefix()}users` AS u ON (u.`uid` = f.`uid`)
            WHERE f.service = :service
            AND f.occurrence = :occurrence
            ORDER BY flagid ");
        $q->bindParam(":service", $serviceid);
        $q->bindParam(":occurrence", $occurrence);
        $q->execute() or die("Couldn't get flag for {$serviceid}/{$occurrence}");
        $package = json_encode($q->fetchAll(PDO::FETCH_ASSOC));
        file_put_contents("{$cachename}/{$md5occ}", $package);
        $lw->write("ADD: {$serviceid}/{$md5occ} ({$occurrence})\n");
    }
    if ($raw) { return $package; }
    $results = json_decode($package, true);
    $rv = array();
    foreach ($results as $flag) {
        $flag = array_map(function($v) {return htmlspecialchars($v);}, $flag);
        if (2 <= authLevel()) {
            $deletelink = "<a class=\"delete-flag\" href=\"#\" data-flagid=\"{$flag['flagid']}\" data-userid=\"".authUid()."\"></a>";
        } else { $deletelink = ""; }
        $rv[] = "<div class=\"flag-repr\">
            <div class=\"flag-name\">{$deletelink}{$flag['flag']}<br><span class=\"flag-creator\">{$flag['user']}</span></div>
            <div class=\"flag-value\">{$flag['value']}</div>
            </div>";
    }
    $formatted = implode("\n", $rv);
    return json_encode(array(count($results), $formatted));
}

function getFlagestalt($serviceid, $occurrence) {
    /* flagestalt is saved in the installation options to indicate the default service/occurrence
     * to use as a template for new services, which will receive identical flags. */
    $options = getOptions(False);
    $auth = authLevel();
    $flagestalt = $options->getDefault(0, "flagestalt");
    unset($options);
    if ("0" == $flagestalt) { return ""; }
    if ($auth >= 3 && $flagestalt["service"] == $serviceid && $flagestalt["occurrence"] == $occurrence) {
        return "flagestalt";
    } else {
        return "";
    }
}

function flagestaltLink($serviceid, $occurrence, $text="<b>F</b>") {
    $occurrence=urlencode($occurrence);
    $auth = authLevel();
    if ($auth < 3) { return ""; }
    return "<a title=\"Default flags template for new services\" href=\"{$_SERVER["PHP_SELF"]}?flagestalt={$serviceid}&occurrence={$occurrence}&flag=savesettings\">{$text}</a>";
}

function findFlagsUpcoming($flag_text, $days_upcoming) {
    $db = new DBConnection();
    $days_upcoming = (int)$days_upcoming;
    $q = $db->prepare("SELECT
        f.flag, f.value, f.service, f.occurrence,
        f.pkey AS flag_key,
        u.email, CONCAT(u.fname, ' ', u.lname) AS user,
        DATE_FORMAT(d.caldate, '%c/%e/%Y') AS date
        FROM `{$db->getPrefix()}service_flags` AS f
        JOIN `{$db->getPrefix()}users` AS u ON (u.`username` = f.`value`)
        JOIN `{$db->getPrefix()}days` AS d ON (f.`service` = d.`pkey`)
        WHERE DATEDIFF(d.caldate, CURDATE()) <= {$days_upcoming}
            AND DATEDIFF(d.caldate, CURDATE()) > 0
            AND f.flag = :flagText
        GROUP BY f.pkey");
    $q->bindParam(":flagText", $flag_text);
    $q->execute() or die("<p>In findFlagsUpcoming ".array_pop($q->errorInfo())."</p>");
    return $q;
}

function listthesehymns(&$thesehymns, $rowcount, $showocc=false) {
    // Display the hymns in $thesehymns, if any.
    $rows = 0;
    if (! $thesehymns) return;
    if (! $showocc) {
        $hymnblock_occ = " data-occ=\"{$thesehymns[0]['occurrence']}\"";
    } else {
        $hymnblock_occ = "";
    }
    echo "<tr{$hymnblock_occ} data-service=\"".getIndexOr($thesehymns[0],'serviceid')."\"><td colspan=3>\n";
    echo "<table class=\"hymn-listing\">";
    foreach ($thesehymns as $ahymn) {
        $occurrence = " data-occ=\"{$ahymn['occurrence']}\"";
        // Display this hymn
        if (0 == ($rowcount+$rows) % 2) {
            $oddness = " class=\"even\"";
        } else {
            $oddness = "";
        }
        echo "<tr{$oddness}{$occurrence}>";
        if (intval($ahymn['number'])) {
            echo "<td class=\"hymn-number\">{$ahymn['book']} {$ahymn['number']}</td>";
        } else echo "<td></td>";
        echo "<td class=\"note\">{$ahymn['note']}</td><td class=\"title\">{$ahymn['title']}</td>";
        if ($showocc) {
            echo "<td class=\"hymn-occurrence\">{$ahymn['occurrence']}</td>";
        }
        //echo "<td>{$ahymn['date']}</td>"; // For debugging our looping
        echo "</tr>";
        $rows += 1;
    }
    echo "</table></td></tr>\n";
    $thesehymns=array();
    return $rows;
}

function display_records_table($q) {
    $options = getOptions(false);
    if (0 == $options->getDefault(0, "combineoccurrences")) {
        display_occurrences_separately($q);
    } else {
        display_occurrences_together($q);
    }
    unset($options);
}

/***********
 * Display the block info in a standard format
 */
function display_block_section($row, $cfg) { ?>
    <td colspan=3 class="blockdisplay">
    <h4>Block: <?=$row['blabel']?></h4>
    <div class="blocknotes maxcolumn">
        <?=translate_markup($row['bnotes'])?>
    </div>
<?
if (! ($row['blesson1'] || $row['blesson2'] || $row['bgospel']
|| $row['bpsalm'] || $row['bsermon'] || $row['bcollect']) )
{
echo "No block data found. "
."Is the liturgical day name set to a single day?";
}
?>
    <dl class="blocklessons">
    <dt>Lesson 1</dt><dd><?=linkbgw($cfg, $row['blesson1'], $row['l1link'])?></dd>
    <dt>Lesson 2</dt><dd><?=linkbgw($cfg, $row['blesson2'], $row['l2link'])?></dd>
    <dt>Gospel</dt><dd><?=linkbgw($cfg, $row['bgospel'], $row['golink'])?></dd>
    <dt>Psalm</dt><dd><?=linkbgw($cfg, $row['bpsalm']?"Ps ".$row['bpsalm']:'', $row['pslink'])?></dd>
    <dt>Sermon<?=$row['has_sermon']?'*':''?></dt><dd><?=linkbgw($cfg, $row['bsermon'], $row['has_sermon'] || $row['smlink'])?></dd>
    <dt>Weekly Hymn</dt><dd><?=$row['hymnabc']?></dd>
    <dt>Hymn</dt><dd><?=$row['hymn']?></dd>
    </dl>
    <h5>Collect (<?=$row['bcollectclass']?>)</h5>
    <div class="collecttext maxcolumn">
        <?=$row['bcollect']?>
    </div>
    <? if ($row['sermonlessonnote']) { ?>
    <h5>Sermon Lesson Note</h5>
    <div class="sermonlessonnote maxcolumn">
        <?=translate_markup($row['sermonlessonnote'])?>
    </div>
    <? }
}

/*****
 * A form for selecting which extra block info to display
 */
function extra_daynames_selection_form($day, $identifier) {
    // Get a list of lectionaries and a list of alternative daynames
    $daynames = array_map('trim', explode("|", $day));
    array_shift($daynames); // Remove first (default) dayname
    ob_start();
    ?>
    <form id="<?=$identifier?>_extra-dayname-form" class="extra-dayname-form">
    <label for="alt_lectionary">Choose Lectionary</label>
    <select name="alt_lectionary" id="<?=$identifier?>_chosen-lect">
    <? foreach (getLectionaryNames() as $lname) { ?>
        <option name="<?=$lname?>"><?=$lname?></option>
    <? } ?>
    </select>
    <label for="alt_dayname">Choose Dayname</label>
    <select name="alt_dayname" id="<?=$identifier?>_chosen-day">
    <? foreach ($daynames as $day) { ?>
        <option name="<?=$day?>"><?=$day?></option>
    <? } ?>
    </select>
    <button type="submit">Show</button>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Show a table of the data in the query $q,
 * grouping hymns into separate sections for each service occurrence.
 **/
function display_occurrences_separately($q) {
    $auth = authLevel();
    // Show a table of the data in the query $result
    ?><table id="records-listing" data-combined="false"><?
    $serviceid = "";
    $occurrence = "";
    $rowcount = 1;
    $servicecount = 0;
    $thesehymns = array();
    $hymnoccurrence = "";
    $cfg = getConfig(false);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if (! ($row['serviceid'] == $serviceid
            && $row['occurrence'] == $occurrence))
        {
            $rowcount += listthesehymns($thesehymns, $rowcount);
            $servicecount++;
            // Display the heading line
            if (is_within_week($row['date'])) {
                $datetext = "<a name=\"now\">{$row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            $urloccurrence = urlencode($row['occurrence']);
            $flagestalt = getFlagestalt($row['serviceid'], $row['occurrence']);
            echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\" class=\"heading servicehead {$flagestalt}\">".
                "<td class=\"heavy\"><a href=\"#\" class=\"expandservice\">+</a> {$datetext} {$row['occurrence']}</td>
                <td colspan=2><a name=\"service_{$row['serviceid']}\">{$row['dayname']}</a>: {$row['rite']}".
            ((3==$auth)?
            "<a class=\"menulink\" href=\"sermon.php?id={$row['serviceid']}\">Sermon</a>\n"
            :"").
            (($auth)?
            " <a class=\"menulink flagbutton\" title=\"Edit flags for this service.\" href=\"flags.php?id={$row['serviceid']}&occurrence={$urloccurrence}\">Flags</a>"
            :"").
            "<a class=\"menulink\" href=\"export.php?service={$row['serviceid']}\">CSV Data</a>\n".
            " <a class=\"menulink\" href=\"print.php?id={$row['serviceid']}\" title=\"print\">Print</a> ".
            "</td><td>({$servicecount})</td></tr>\n";
            echo "<tr class=\"service-flags\" data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3></td>"
                ."<td>".flagestaltLink($row['serviceid'], $row['occurrence'])."</td></tr>\n";
            // Propers
            ?>
            <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>">
            <td class="heavy smaller"><?=$row['theme']?></td> <td><?=$row['color']?></td></tr>
            <?
            if (getIndexOr($row,'introit') || getIndexOr($row,'gradual')) {
                ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"> <?
                if (getIndexOr($row,'introit')) {
                    ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['introit'])?></div></td> <?
                }
                if (getIndexOr($row,'gradual')) {
                    ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['gradual'])?></div></td> <?
                }
            ?> </tr> <?
            }
            if (getIndexOr($row,'propersnote')) {
                ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"><td>
                    <div class="maxcolumn"><?=translate_markup($row['propersnote'])?></div></td></tr> <?
            }
            // Block
            if ($row['block'])
            { ?>
                <tr data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>">
                <?php display_block_section($row, $cfg); ?>
                </tr>
            <? }
            if ($row['servicenotes']) {
                echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3 class=\"servicenote\">".
                     translate_markup($row['servicenotes'])."</td></tr>\n";
            }
            if (false !== strpos($row['dayname'], '|')) {
                echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3 class=\"extradaynamesctrl\"><h4>Propers for Extra Daynames</h4>".
                    extra_daynames_selection_form($row['dayname'],
                        "{$row['serviceid']}-{$row['occurrence']}")."</td></tr>\n";
                echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3><table class=\"extrablockinfo\"></table></td></tr>\n";
            }
            $serviceid = $row['serviceid'];
            $occurrence = $row['occurrence'];
        }
        // Collect hymns
        $thesehymns[] = $row;
        $hymnoccurrence = $row['occurrence'];
    }
    if ($thesehymns) listthesehymns($thesehymns, $rowcount);
    echo "</article>\n";
    echo "</table>\n";
    unset($cfg);
}

/**
 * Show a table of the data in the query $q,
 * grouping hymns into one section for all occurrances of each service.
 **/
function display_occurrences_together($q) {
    // Show a table of the data in the query $result
    ?><table id="records-listing" data-combined="true"><?
    $cfg = getConfig(false);
    $thesehymns = array();
    $rowcount = 1;
    $servicecount = 0;
    $serviceid = "";
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if ($serviceid && $row['serviceid'] != $serviceid)
        {
            $servicecount++;
            displayServiceHeaderCombined($thesehymns, $servicecount);
            $rowcount += listthesehymns($thesehymns, $rowcount, true);
        }
        // Collect hymns
        $thesehymns[] = $row;
        $serviceid = getIndexOr($row,'serviceid');
    }
    if ($thesehymns) {
        displayServiceHeaderCombined($thesehymns, $servicecount);
        listthesehymns($thesehymns, $rowcount, true);
    }
    echo "</article>\n";
    echo "</table>\n";
}

function displayServiceHeaderCombined($thesehymns, $servicecount) {
    $auth = authLevel();
    $cfg = getConfig(false);
    $occurrences = array();
    foreach ($thesehymns as $row) {
        if ($row['occurrence'])
            $occurrences[$row['occurrence']] = 1;
    }
    $occurrences = array_keys($occurrences);
    $urloccurrences=array_map(function($o) {return rawurlencode($o);}, $occurrences);
    $row = $thesehymns[0];
    if (is_within_week($row['date'])) {
        $datetext = "<a name=\"now\">{$row['date']}</a>";
    } else {
        $datetext = $row['date'];
    }
    // Heading line
    $sid = getIndexOr($row, "serviceid");
    $flagestalt = getFlagestalt($row['serviceid'], $row['occurrence']);
    echo "<tr class=\"heading servicehead {$flagestalt}\" data-service=\"{$sid}\"><td class=\"heavy\"><a href=\"#\" class=\"expandservice\">+</a> {$datetext}</td>
        <td><a name=\"service_{$sid}\">{$row['dayname']}</a>: {$row['rite']}".
    ((3==$auth)?
    "<a class=\"menulink\" href=\"sermon.php?id={$sid}\">Sermon</a>\n"
    :"").
    "<a class=\"menulink\" href=\"export.php?service={$sid}\">CSV Data</a>\n".
    " <a class=\"menulink\" href=\"print.php?id={$sid}\" title=\"print\">Print</a> ".
    "</td><td>({$servicecount})</td></tr>\n";
    for ($i=0, $limit=count($occurrences); $i<$limit; $i++) {
    echo "<tr class=\"service-flags\" data-occ=\"{$occurrences[$i]}\" data-service=\"{$sid}\"><td colspan=2></td><td>".
    (($auth)? flagestaltLink($row['serviceid'], $row['occurrence'])
    ." <a class=\"menulink flagbutton\" title=\"Edit flags for this service.\" href=\"flags.php?id={$sid}&occurrence={$urloccurrences[$i]}\">Flags</a> "
    :"").
        "{$occurrences[$i]}</td></tr>\n";
    }
    // Propers
    ?>
    <tr class="heading" data-service="<?=$row['serviceid']?>">
    <td class="propers heavy smaller"><?=$row['theme']?></td><td><?=$row['color']?></td></tr>
    <?
    if ($row['introit'] || $row['gradual']) {
        ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"> <?
        if ($row['introit']) {
            ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['introit'])?></div></td> <?
        }
        if ($row['gradual']) {
            ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['gradual'])?></div></td> <?
        }
    ?> </tr> <?
    }
    if ($row['propersnote']) {
        ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"><td>
            <div class="maxcolumn"><?=translate_markup($row['propersnote'])?></div></td></tr> <?
    }
    if (getIndexOr($row,'block'))
    { ?>
        <tr data-service="<?=getIndexOr($row,'serviceid')?>">
        <?php display_block_section($row, $cfg);?>
        </tr>
    <? }
    if ($row['servicenotes']) {
        echo "<tr data-service=\"{$sid}\"><td colspan=3 class=\"servicenote\">".
             translate_markup($row['servicenotes'])."</td></tr>\n";
    }
    if (false !== strpos($row['dayname'], '|')) {
        echo "<tr data-service=\"{$row['serviceid']}\"><td colspan=3 class=\"extradaynamesctrl\"><h4>Propers for Extra Daynames</h4>".
                 extra_daynames_selection_form($row['dayname'],
                        "{$row['serviceid']}")."</td></tr>\n";
        echo "<tr data-service=\"{$row['serviceid']}\"><td colspan=3><table class=\"extrablockinfo\"></table></td></tr>\n";
    }
    unset($cfg);
}

/**
 * Show a table of the data in the query $q
 * with links to edit each record, and checkboxes to delete records.
 */
function modify_records_table($q, $action) {
    ?><form id="delete-service" action="<?=$action?>" method="post">
      <button class="deletesubmit" type="submit" value="Delete">Delete</button>
      <button type="reset" value="Clear">Clear</button>
      </form>
    <?
    $options = getOptions(false);
    if (0 == $options->getDefault(0, "combineoccurrences")) {
        modify_occurrences_separately($q);
    } else {
        modify_occurrences_together($q);
    }
    unset($options);
}

function modify_occurrences_separately($q) {
    $auth = authLevel();
    $cfg = getConfig(false);
    $serviceid = "";
    $occurrence = "";
    $rowcount = 1;
    $servicecount = 0;
    $thesehymns = array();
    $hymnoccurrence = "";
    ?> <table id="modify-listing" data-combined="false"> <?
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if (! ($row['serviceid'] == $serviceid
            && $row['occurrence'] == $occurrence))
        { // Create Service Block
            $rowcount += listthesehymns($thesehymns, $rowcount);
            $servicecount++;
            if (is_within_week($row['date'])) {
                $datetext = "<a name=\"now\">{$row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            $urldate=urlencode($row['browserdate']);
            $urloccurrence=urlencode($row['occurrence']);
            $flagestalt = getFlagestalt($row['serviceid'], $row['occurrence']);
            echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\" class=\"heading servicehead {$flagestalt}\"><td><a href=\"#\" class=\"expandservice\">+</a>
            <input form=\"delete-service\" type=\"checkbox\" name=\"{$row['serviceid']}_{$row['occurrence']}\" id=\"check_{$row['serviceid']}_{$row['occurrence']}\">
            <span class=\"heavy\">{$datetext} {$row['occurrence']}</span>
            <div class=\"menublock\">";
            if (3 == $auth) {
                echo "
            <a class=\"menulink\" href=\"enter.php?date={$urldate}\" title=\"Add another service or hymns on {$row['date']}.\">Add</a>
            <a class=\"menulink\" title=\"See or edit sermon plans for this service.\" href=\"sermon.php?id={$row['serviceid']}\">Sermon</a>
            <a class=\"menulink copy-service\" data-id=\"{$row['serviceid']}\" href=\"#\" title=\"Copy this to another date.\">Copy</a>
            <a href=\"#\" class=\"edit-service menulink\" title=\"Edit this service.\" data-id=\"{$row['serviceid']}\">Edit</a>";
            }
            echo "
            <a class=\"menulink\" href=\"print.php?id={$row['serviceid']}\" title=\"Show a printable format of this service.\">Print</a>
            <a class=\"menulink flagbutton\" title=\"Edit flags for this service.\" href=\"flags.php?id={$row['serviceid']}&occurrence={$urloccurrence}\">Flags</a>
            </div>
            </td>
            <td colspan=2>
            <a name=\"service_{$row['serviceid']}\">{$row['dayname']}</a>: {$row['rite']}
            </td><td>({$servicecount})</td></tr>\n";
            // Flags Row
            echo "<tr class=\"service-flags\" data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3></td><td>"
                .flagestaltLink($row['serviceid'], $row['occurrence'])."</td></tr>\n";
            // Propers Row
            ?>
            <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>">
            <td class="heavy smaller"><?=$row['theme']?></td> <td><?=$row['color']?></td></tr>
            <?
            if (getIndexOr($row,'introit') || getIndexOr($row,'gradual')) {
                ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"> <?
                if (getIndexOr($row,'introit')) {
                    ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['introit'])?></div></td> <?
                }
                if (getIndexOr($row,'gradual')) {
                    ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['gradual'])?></div></td> <?
                }
            ?> </tr> <?
            }
            if (getIndexOr($row,'propersnote')) {
                ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"><td>
                    <div class="maxcolumn"><?=translate_markup($row['propersnote'])?></div></td></tr> <?
            }
            // Block Row
            if ($row['block'])
            { ?>
                <tr data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>">
                <?php display_block_section($row, $cfg); ?>
                </tr>
            <? }
            // Notes Row
            if ($row['servicenotes']) {
                echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3 class=\"servicenote\">".
                     translate_markup($row['servicenotes'])."</td></tr>\n";
            }
            if (false !== strpos($row['dayname'], '|')) {
                echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3 class=\"extradaynamesctrl\"><h4>Propers for Extra Daynames</h4>".
                         extra_daynames_selection_form($row['dayname'],
                        "{$row['serviceid']}-{$row['occurrence']}")."</td></tr>\n";
                echo "<tr data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3><table class=\"extrablockinfo\"></table></td></tr>\n";
            }
            $serviceid = $row['serviceid'];
            $occurrence = $row['occurrence'];
        }
        // Collect hymns
        $thesehymns[] = $row;
        $hymnoccurrence = $row['occurrence'];
    }
    if ($thesehymns) listthesehymns($thesehymns, $rowcount);
    ?>
    </article>
    </table>
    <button class="deletesubmit" form="delete-service" type="submit" value="Delete">Delete</button>
    <button form="delete-service" type="reset" value="Clear">Clear</button>
    </form>
    <?
    unset($cfg);
}

function modify_occurrences_together($q) {
    ?> <table id="modify-listing" data-combined="true"> <?
    $serviceid = "";
    $rowcount = 1;
    $servicecount = 0;
    $thesehymns = array();
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if ($serviceid && $row['serviceid'] != $serviceid)
        {
            $servicecount++;
            modifyServiceHeaderCombined($thesehymns,$servicecount);
            listthesehymns($thesehymns, $rowcount, true);
        }
        // Collect hymns
        $thesehymns[] = $row;
        $serviceid = $row['serviceid'];
    }
    if ($thesehymns) {
        modifyServiceHeaderCombined($thesehymns,$servicecount);
        listthesehymns($thesehymns, $rowcount, true);
    }
    ?>
    </article>
    </table>
    <button class="deletesubmit" form="delete-service" type="submit" value="Delete">Delete</button>
    <button form="delete-service" type="reset" value="Clear">Clear</button>
    </form>
    <?
}

function modifyServiceHeaderCombined($thesehymns,$servicecount) {
    $auth = authLevel();
    $cfg = getConfig(false);
    $occurrences = array();
    foreach ($thesehymns as $row) {
        if ($row['occurrence'])
            $occurrences[$row['occurrence']] = 1;
    }
    $occurrences = array_keys($occurrences);
    $urloccurrences=array_map(function($o) {return rawurlencode($o);}, $occurrences);
    $row = $thesehymns[0];
    if (is_within_week($row['date'])) {
        $datetext = "<a name=\"now\">{$row['date']}</a>";
    } else {
        $datetext = $row['date'];
    }
    $urldate=urlencode($row['browserdate']);
    $flagestalt = getFlagestalt($row['serviceid'], $row['occurrence']);
    echo "<tr class=\"heading servicehead {$flagestalt}\" data-service=\"{$row['serviceid']}\"><td>";
    echo "<a href=\"#\" class=\"expandservice\">+</a> ";
    echo "<div class=\"deletion-block\">";
    foreach ($occurrences as $occ) {
        echo "<input form=\"delete-service\" type=\"checkbox\" name=\"{$row['serviceid']}_{$occ}\" id=\"check_{$row['serviceid']}_{$occ}\"> <label for=\"check_{$row['serviceid']}_{$occ}\">{$occ}</label><br>";
    }
    echo "</div>";
    echo "<span class=\"heavy\">{$datetext}</span>
    <div class=\"menublock\">";
    if (3 == $auth) {
        echo "
    <a class=\"menulink\" href=\"enter.php?date={$urldate}\" title=\"Add another occurrence or hymns on {$row['date']}.\">Add</a>
    <a class=\"menulink\" title=\"See or edit sermon plans for this service.\" href=\"sermon.php?id={$row['serviceid']}\">Sermon</a>
    <a class=\"menulink copy-service\" data-id=\"{$row['serviceid']}\" href=\"#\" title=\"Copy this to another date.\">Copy</a>
    <a href=\"#\" class=\"edit-service menulink\" title=\"Edit this service.\" data-id=\"{$row['serviceid']}\">Edit</a>";
    }
    echo "
    <a class=\"menulink\" href=\"print.php?id={$row['serviceid']}\" title=\"Show a printable format of this service.\">Print</a>
    </div>
    </td>
    <td>
    <a name=\"service_{$row['serviceid']}\">{$row['dayname']}</a>: {$row['rite']}
    </td><td>({$servicecount})</td></tr>\n";
    for ($i=0, $limit=count($occurrences); $i<$limit; $i++) {
        echo "<tr class=\"service-flags\" data-occ=\"{$occurrences[$i]}\" data-service=\"{$row['serviceid']}\"><td colspan=2></td>
            <td>".flagestaltLink($row['serviceid'], $row['occurrence'])." <a class=\"menulink flagbutton\" title=\"Edit flags for this service.\" href=\"flags.php?id={$row['serviceid']}&occurrence={$urloccurrences[$i]}\">Flags</a> {$occurrences[$i]}</td>
        </tr>\n";
    }
    // Propers
    ?>
    <tr class="heading" data-service="<?=$row['serviceid']?>">
    <td class="propers heavy smaller"><?=$row['theme']?></td><td><?=$row['color']?></td></tr>
    <?
    if (getIndexOr($row,'introit') || getIndexOr($row,'gradual')) {
        ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"> <?
        if (getIndexOr($row,'introit')) {
            ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['introit'])?></div></td> <?
        }
        if (getIndexOr($row,'gradual')) {
            ?> <td><div class="sbspar maxcolumn smaller"><?=translate_markup($row['gradual'])?></div></td> <?
        }
    ?> </tr> <?
    }
    if (getIndexOr($row,'propersnote')) {
        ?> <tr class="propers heading" data-occ="<?=$row['occurrence']?>" data-service="<?=$row['serviceid']?>"><td>
            <div class="maxcolumn"><?=translate_markup($row['propersnote'])?></div></td></tr> <?
    }
    if (getIndexOr($row,'block'))
    { ?>
        <tr data-service="<?=$row['serviceid']?>">
        <?php display_block_section($row, $cfg); ?>
        </tr>
    <? }
    if (getIndexOr($row,'servicenotes')) {
        echo "<tr data-service=\"{$row['serviceid']}\"><td colspan=3 class=\"servicenote\">".
             translate_markup($row['servicenotes'])."</td></tr>\n";
    }
    if (false !== strpos($row['dayname'], '|')) {
        echo "<tr data-service=\"{$row['serviceid']}\"><td colspan=3 class=\"extradaynamesctrl\"><h4>Propers for Extra Daynames</h4>".
                 extra_daynames_selection_form($row['dayname'],
                        "{$row['serviceid']}")."</td></tr>\n";
        echo "<tr data-service=\"{$row['serviceid']}\"><td colspan=3><table class=\"extrablockinfo\"></table></td></tr>\n";
    }
    unset($cfg);
}

function html_head($title, $xstylesheets=Array()) {
    global $AddToHeader;
    $rv[] = '<meta charset="utf-8">';
    $rv[] = "<head><title>{$title}</title>";
    $jqf = fopen("jquery/locations.json", "r");
    $jquery_locations = json_decode(fread($jqf, 1024));
    fclose($jqf);

    if (is_link($_SERVER['SCRIPT_FILENAME']))
    {   // Find the installation for css and other links
        $here = dirname(__FILE__);
        $rv[] = "<style type=\"text/css\">";
        $rv[] = get_style("{$here}/style");
        $rv[] = "</style>";
        $rv[] = "<style type=\"text/css\" media=\"print\">";
        $rv[] = get_style("{$here}/print");
        $rv[] = "</style>";
    } else {
        $here = dirname($_SERVER['SCRIPT_NAME']);
        $rv[] = "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$here}/styles/style.css\">";
        if ($xstylesheets) {
            foreach ($xstylesheets as $xstyle) {
                $rv[] = "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$here}/styles/{$xstyle}\">";
            }
        }
        $rv[] = "<script type=\"text/javascript\" src=\"{$jquery_locations->jquery}\"></script>";
        $rv[] = "<link href=\"{$jquery_locations->style}\" rel=\"stylesheet\" type=\"text/css\"/>
        <script type=\"text/javascript\" src=\"modernizr/modernizr.js\"></script>
        <script type=\"text/javascript\" src=\"{$jquery_locations->ui}\"></script>
        <script type=\"text/javascript\" src=\"{$jquery_locations->appear}\"></script>
        <script type=\"text/javascript\" src=\"jquery/jquery.ba-dotimeout.min.js\"></script>";
        $rv[] = "<script type=\"text/javascript\" src=\"{$here}/ecmascript.js\"></script>";
    }
    echo "<!-- AddToHeader Section -->";
    if ($AddToHeader) {
        foreach ($AddToHeader as $content) $rv[] = $content;
    }
    $rv[] = "</head>";
    return implode("\n", $rv);
}

function linkbgw($config, $ref, $linked, $other=true) {
    // Return an html <a> entity using biblelinktemplate, or if blank,
    // return an html <a> to BibleGateway for the given reference,
    // or if the version is not set, just the ref.
    if (! $linked) {
        return $ref;
    }
    try { // biblelinktemplate may not be set.
        $blt = $config->get("biblelinktemplate");
        if ("" == $blt) {
            $merged_link = "";
        } else {
            $merged_link = "<a href=\"".str_replace('{{}}', rawurlencode($ref), $blt).'"';
        }
    } catch(ConfigfileUnknownKey $e) {
        $merged_link = "";
    }
    if ("" == $merged_link) {
        try { // biblegwversion may not be set.
            $bgwversion = urlencode($config->get("biblegwversion"));
        } catch(ConfigfileUnknownKey $e) {
            return $ref;
        }
        $merged_link = "<a href=\"http://biblegateway.com/passage?search=".
            urlencode($ref)."&version={$bgwversion}&interface=print\"";
    }
    if ($other) $other = " target=\"bgmain\" ";
    else $other = "";
    return  "$merged_link{$other}>".
        htmlspecialchars($ref)."</a>";
}

function quote_array($ary) {
    // reduce ugliness (Note: connect to mysql before using.)
    return str_replace("'", "''", $ary);
}

function gensitetabs($sitetabs, $action, $bare=false) {
    $tabs = array_fill_keys(array_keys($sitetabs), 0);
    $tabs[$action] = 1;
    $rv = "";
    if (!$bare) {
        $rv .= "<nav><div id=\"sitetabs-background\">";
        $rv .= "<ul id=\"sitetabs\">";
    }
    foreach ($tabs as $name => $activated) {
        if ($activated) {
            $class = ' class="activated"';
        } else {
            $class = "";
        }
        $tabtext = $sitetabs[$name];
        $rv .= "<li{$class} data-name='{$name}'><a href=\"{$name}.php\">{$tabtext}</a></li>";
    }
    if (!$bare) {
        $rv .= "</ul></div></nav>\n";
    }
    return $rv;
}

function translate_markup($text) {
    require_once('markdown/Michelf/MarkdownExtra.inc.php');
    if (is_null($text)) { return ""; }
    return \Michelf\MarkdownExtra::defaultTransform($text);
}

function is_within_week($dbdate) {
    // True if the given date is within a week *after* today.
    $db = strtotime($dbdate);
    $now = getdate();
    $weekahead = mktime(0,0,0,$now['mon'],$now['mday']+8,$now['year']);
    if ($db <= $weekahead && $db >= time()) return True; else return False;
}

function get_style($filename) {
    // Include the style file indicated, adding ".css"
    $file = "{$filename}.css";
    if (file_exists($file)) {
        return file_get_contents($file);
    }
}

function showMessage() {
    global $sprefix;
    if (array_key_exists('message', $_SESSION[$sprefix])) { ?>
        <script type="text/javascript">
            $(document).ready(function() {
            <? foreach ($_SESSION[$sprefix]['message'] as $msg) {
                $safemsg = str_replace('"', '\"', $msg); ?>
                setMessage("<?=$safemsg?>");
            <? } ?>
            });
        </script>
        <? unset($_SESSION[$sprefix]['message']);
    }
}

function setMessage($text) {
    global $sprefix;
    $_SESSION[$sprefix]['message'][] = $text;
}

function getLoginForm($bare=false) {
    global $sprefix;
    $auth = authId();
    if ($bare) {
        $rv = "";
    } else {
        $rv = '<div id="login">';
    }
    if ($auth) {
        if ("cookie" == $_SESSION[$sprefix]['authdata']['authtype'])
            $cookie = "*";
        else
            $cookie = "";
        $rv .= "{$_SESSION[$sprefix]['authdata']['login']}{$cookie} <a href=\"login.php?action=logout\" name=\"Log out\" title=\"Log out\">Log out</a>";
    } else {
        $rv .= '<form id="loginform" method="post" action="login.php">
        <label for="username">User Name</label>
        <input id="username" type="text" name="username" required>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <button type="submit" value="submit">Log In</button>
        </form>';
    }
    if ($bare) {
        return $rv;
    } else {
        return $rv .= '</div>';
    }
}

function getUserActions($bare=false) {
    $authlevel = authLevel();
    $actions = array();
    if ($authlevel) {
        if ($authlevel<3) {
            $actions[] = '<a href="useradmin.php?flag=changepw"
                title="Update Password">Update Password</a>';
        } else {
            $actions[] = '<a href="useradmin.php"
                title="User Administration">User Administration</a>';
        }
        $actions[] = flagestaltLink(0, '', "Remove Flag Default");
        $actions[] = '<a href="help.php" title="Help">Help</a>';
    } else {
        $actions[] = '<a href="resetpw.php"
        title="Reset Password">Reset Password</a>';
    }
    $actions[] = '<a href="#" id="seemessages" title="Review Messages">Review Messages</a>';
    $stactions = implode(' | ', $actions);
    if ($bare) {
        return $stactions;
    } else {
        return '<div id="useractions">'.$stactions.'</div>';
    }
}

function getCSSAdjuster() {
    $options = getOptions(false);
?>
    <form name="cssadjuster" id="cssadjuster">
    <div id="cssadjuster">
        <table>
        <tr><td><label for="basefont">Base font size (pixels)</label></td>
        <td><input name="basefont" id="basefont" type="number" min="6" max="50" step="1"></td></tr>
        <tr><td><label for="hymnfont">Hymn font size (%)</label></td>
        <td><input name="hymnfont" id="hymnfont" type="number" min="25" max="200" step="5"></td></tr>
        <tr><td><label for="notefont">Note font size (%)</label></td>
        <td><input name="notefont" id="notefont" type="number" min="25" max="200" step="5"></td></tr>
        <tr><td><label for="cssblockdisplay">Show block info?</label></td>
        <td><input name="cssblockdisplay" id="cssblockdisplay" type="checkbox"></td></tr>
        <tr><td><label for="csspropers">Show propers?</label></td>
        <td><input name="csspropers" id="csspropers" type="checkbox"></td></tr>
    <? if (0 == $options->get("combineoccurrences")) { ?>
        <tr id="adjusteroccurrencechooser" style="display: none;">
        <td><label for="occurrences">Show occurrences:</label></td>
        <td><ul id="adjusteroccurrences"></ul></td></tr>
    <? } ?>
        <tr><td></td>
        <td><button type="button" id="cssreset">Reset to Default</button></td>
        </table>
    </div>
    </form>
    <script type="text/javascript">
        $(document).ready(function() {
            setupStyleAdjusterLocs();
        });
        function setupStyleAdjusterLocs() {
            var occurrences = $("tr[data-occ]").map(function() {
                return $(this).attr("data-occ");
            });
            var locobj = {};
            for (i=1;i<occurrences.length;i++) locobj[l=occurrences[i]] = 1;
            occurrences = Array();
            for (l in locobj) occurrences.push(l);
            var stored = false;
            if (typeof(Storage) !== "undefined")
                stored = $.parseJSON(localStorage.getItem("occurrences"));
            for (index in occurrences) {
                var loc = occurrences[index];
                var init = " checked";
                if (stored && (! stored[loc])) {
                    init = '';
                }
                $("#adjusteroccurrences").append('<li>'+
                    '<input name="'+loc+'" class="cssadjusterloc" type="checkbox" '+init+'>'+
                    ' <label for="'+loc+'">'+loc+'</label></li>');
            }
            $("#adjusteroccurrencechooser").show();
            $(".cssadjusterloc").change(updateCSS);
        }
        // Initialize vars
        var basefont = 0;
        var hymnfont = 0;
        var notefont = 0;
        var blockdisplay = 0;
        var propers = 0;
        // Get from storage
        if (typeof(Storage) !== "undefined") {
            basefont = localStorage.getItem("basefont");
            hymnfont = localStorage.getItem("hymnfont");
            notefont = localStorage.getItem("notefont");
            blockdisplay = localStorage.getItem("blockdisplay");
            propers = localStorage.getItem("propers");
        }
        // Set defaults
        if (! basefont) basefont = $("body").css("font-size").replace(/[^0-9]/g, "");
        if (! hymnfont) hymnfont = 100;
        if (! notefont) notefont = 100;
        if ((blockdisplay == 0) || (blockdisplay == null)) blockdisplay = true;
        if ((propers == 0) || (propers == null)) propers = true;
        // Populate form
        $("#basefont").val(basefont).change(updateCSS);
        $("#hymnfont").val(hymnfont).change(updateCSS);
        $("#notefont").val(notefont).change(updateCSS);
        $("#cssblockdisplay").prop('checked', blockdisplay).change(updateCSS);
        $("#csspropers").prop('checked', propers).change(updateCSS);
        $("#cssreset").click(function() {
            localStorage.removeItem("basefont");
            localStorage.removeItem("hymnfont");
            localStorage.removeItem("notefont");
            localStorage.removeItem("blockdisplay");
            localStorage.removeItem("propers");
            localStorage.removeItem("occurrences");
            location.reload();
        });
    </script>
<?
    unset($options);
}

function jsString($s, $q="'") {
    return str_replace( array($q, "\n"), array("\\$q", "\\n"), $s);
}

function ordinal($n) {
    $ords = array("zeroth", "first", "second", "third", "fourth", "fifth",
        "sixth", "seventh", "eighth", "ninth", "tenth");
    if (is_numeric($n) && $n > -1 && $n < 11) {
        return $ords[$n];
    } else return $n;
}

/*
 * Unused
function getLessonField($lesson, $lect, $series) {
    // Return the field name we want in the given circumstances
    if ($lect == 'historic') {
        if ($lesson == 'lesson1' or $lesson = 'lesson2') {
            if ($series == 'first') return $lesson;
            elseif ($series == 'second') return 's2lesson';
            elseif ($series == 'third') return 's3lesson';
        } elseif ($lesson == 'gospel') {
            if ($series == 'first') return 'gospel';
            elseif ($series = 'second') return 's2gospel';
            elseif ($series = 'third') return 's3gospel';
        }
    } else return $lesson;
}
 */

/* Replace occurrences of {{DBP}} with $prefix or $dbp in text.
 */
function replaceDBP($text, $prefix=false) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    if ($prefix !== false) {
        return str_replace('{{DBP}}', $prefix, $text);
    } else {
        return str_replace('{{DBP}}', $dbp, $text);
    }
}

function pageHeader($displayonly=false) { ?>
    <header>
    <div id="pageheader">
    <? if (!$displayonly) {
        echo getLoginForm();
        echo "<div id=\"styler\"><a href=\"#\" title=\"Adjust Styles\" id=\"openstyler\">Adjust Styles</a></div>\n";
        echo getUserActions();
        echo "<div id=\"stylerdialog\">";
        getCSSAdjuster();
        echo "</div>\n";
    } ?>
    <?showMessage();?>
    </div>
    <div id="msgdialog"></div>
    </header> <?
}
function siteTabs($basename=false, $displayonly=false) {
    global $script_basename;
    $options = getOptions();
    $config = getConfig(false);
    if (! $basename) $basename=$script_basename;
    if (! $displayonly) {
        if (2 <= authLevel()) {
            echo gensitetabs(
                $config->getDefault($options->get("sitetabs"), "sitetabs"),
                $basename);
        } else {
            echo gensitetabs(
                $config->getDefault($options->get("anonymous sitetabs"),
                    "anonymous sitetabs"),
                    $basename);
        }
    }
?>  <script type="text/javascript">
    function fixhash() {
        var loc = $("html,body").scrollTop()-200;
        $("html,body").animate({scrollTop:loc}, 1000);
        console.log("Scrolled by -200 for "+location.hash);
    }
    $(document).ready(function() {
        if (location.hash) {
            location.hash=location.hash.replace('#', '');
            fixhash();
        }
    });
    $(window).bind('hashchange', fixhash);
    </script><?
}

/**
 * Set up to fill the service tables incrementally via js rpc calls.
 */
function fillServiceTables() {
    global $AddToHeader;

    $AddToHeader[] = '
<script type="text/javascript">
    $(document).ready(function() {
        if ($("#spinner").length == 0) {
            $("body").append("<div id=\"spinner\"><img class=\"spinner\" src=\"spin/spinner.gif\"></div>");
        }
        $("#spinner").css("visibility", "visible");
        churchYearTables();
    });
    function churchYearTables() {
        var msgWrapper = function(rv) {
            setMessage(rv[1]);
            if (6 == Number(rv[0])) {
                $("#spinner").hide();
                window.location="admin.php";
            } else {
                churchYearTables();
            }
        };
        $.getJSON("dbadmin.php", {action: "churchyeartables"}, msgWrapper);
    }
</script>';
}

function is_digits($item) {
    // For checking web input
    return !preg_match("/[^0-9]/", $item);
}

function getIndexOr($var, $key, $default='') {
    if (isset($var[$key])) return $var[$key];
    else return $default;
}

function getGET($index, $default='') {
    return getIndexOr($_GET, $index, $default);
}

function getPOST($index, $default='') {
    return getIndexOr($_POST, $index, $default);
}

// vim: set foldmethod=indent :
?>
