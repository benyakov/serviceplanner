<? /* PHP function library

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

require_once("authfunctions.php");

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
    return $_GET['jsonpreq'];
}

function checkContentReq() {
    return $_GET['contentonly'];
}

function queryService($id) {
    $q = rawQuery(array("d.pkey = :id"));
    if ($id) $q->bindParam(":id", $id);
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    return $q;
}

function queryFutureHymns() {
    $q = rawQuery(array("d.caldate >= CURDATE()"));
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    return $q;
}

function querySomeHymns($limit) {
    $q = rawQuery(array(), "DESC", $limit);
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    return $q;
}

function queryServiceDateRange($lowdate, $highdate, $allfuture=false, $order="DESC") {
    $where = array("d.caldate >= :lowdate");
    if (! $allfuture) $where[] = "d.caldate <= :highdate";
    $q = rawQuery($where, $order);
    $q->bindParam(":lowdate", $lowdate->format("Y-m-d"));
    if (! $allfuture) $q->bindParam(":highdate", $highdate->format("Y-m-d"));
    if (! $q->execute())
        die("<p>".array_pop($q->errorInfo()).'</p><p style="white-space: pre;">'.$q->queryString."</p>");
    return $q;
}

function rawQuery($where=array(), $order="", $limit="") {
    if ($where) $wherestr = "WHERE ".implode(" AND ", $where);
    if ($limit) $limitstr = "LIMIT {$limit}";
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
            WHERE cl.dayname=d.name AND cl.lectionary=b.pslect
            LIMIT 1)
        END)
        AS bpsalm,
    b.l1lect != 'custom' AS l1link,
    b.l2lect != 'custom' AS l2link,
    b.golect != 'custom' AS golink,
    b.pslect != 'custom' AS pslink,
    b.smlect != 'custom' AS smlink,
    b.coclass AS bcollectclass,
    (SELECT collect FROM `{$dbp}churchyear_collects` AS cyc
    JOIN `{$dbp}churchyear_collect_index` AS cci
    ON (cyc.id = cci.id)
    WHERE cci.dayname=d.name AND cci.lectionary=b.colect
    AND cyc.class=b.coclass
    LIMIT 1) AS bcollect
    FROM `{$dbp}hymns` AS h
    RIGHT OUTER JOIN `{$dbp}days` AS d ON (h.service = d.pkey)
    LEFT OUTER JOIN `{$dbp}sermons` AS smr ON (h.service = smr.service)
    LEFT OUTER JOIN `{$dbp}names` AS n ON (h.number = n.number)
        AND (h.book = n.book)
    LEFT OUTER JOIN `{$dbp}blocks` AS b ON (b.id = d.block)
    LEFT OUTER JOIN `{$dbp}synpropers` AS cyp ON (cyp.dayname = d.name)
    LEFT JOIN `{$dbp}lesson1selections` AS l1s
    ON (l1s.l1lect=b.l1lect AND l1s.l1series<=>b.l1series AND l1s.dayname=d.name)
    LEFT JOIN `{$dbp}lesson2selections` AS l2s
    ON (l2s.l2lect=b.l2lect AND l2s.l2series<=>b.l2series AND l2s.dayname=d.name)
    LEFT JOIN `{$dbp}gospelselections` AS gos
    ON (gos.golect=b.golect AND gos.goseries<=>b.goseries AND gos.dayname=d.name)
    LEFT JOIN `{$dbp}sermonselections` AS sms
    ON (sms.smlect=b.smlect AND sms.smseries<=>b.smseries AND sms.dayname=d.name)
    {$wherestr}
    ORDER BY d.caldate {$order}, h.service {$order},
        h.occurrence, h.sequence {$limitstr}");
    return $q;
}

function listthesehymns(&$thesehymns, $rowcount, $showocc=false) {
    // Display the hymns in $thesehymns, if any.
    $rows = 0;
    if (! $thesehymns) return;
    echo "<tr data-occ=\"{$thesehymns[0]['occurrence']}\"><td colspan=3>\n";
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

/**
 * Show a table of the data in the query $q,
 * grouping hymns into separate sections for each service occurrence.
 **/
function display_occurrences_separately($q) {
    $auth = authLevel();
    // Show a table of the data in the query $result
    ?><table id="records-listing"><?
    $serviceid = "";
    $occurrence = "";
    $rowcount = 1;
    $thesehymns = array();
    $hymnoccurrence = "";
    $cfg = getConfig(false);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if (! ($row['serviceid'] == $serviceid
            && $row['occurrence'] == $occurrence))
        {
            $rowcount += listthesehymns($thesehymns, $rowcount, $hymnoccurrence);
            // Display the heading line
            if (is_within_week($row['date'])) {
                $datetext = "<a name=\"now\">{$row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            $urloccurrence = urlencode($row['occurrence']);
            echo "<tr data-occ=\"{$row['occurrence']}\" class=\"heading servicehead\"><td class=\"heavy\">{$datetext} {$row['occurrence']}</td>
                <td colspan=2><a name=\"service_{$row['serviceid']}\">{$row['dayname']}</a>: {$row['rite']}".
            ((3==$auth)?
            "<a class=\"menulink\" href=\"sermon.php?id={$row['serviceid']}\">Sermon</a>\n"
            :"").
            (($auth)?
            " <a class=\"menulink\" title=\"Edit flags for this service.\" href=\"flags.php?id={$row['serviceid']}&occurrence={$urloccurrence}\">Flags</a>"
            :"").
            "<a class=\"menulink\" href=\"export.php?service={$row['serviceid']}\">CSV Data</a>\n".
            " <a class=\"menulink\" href=\"print.php?id={$row['serviceid']}\" title=\"print\">Print</a> ".
            "</td></tr>\n";
            echo "<tr class=\"service-flags\" data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3></td></tr>\n";
            echo "<tr data-occ=\"{$row['occurrence']}\" class=\"heading\"><td class=\"propers\" colspan=3>\n";
            echo "<table><tr><td class=\"heavy smaller\">{$row['theme']}</td>";
            echo "<td colspan=2>{$row['color']}</td></tr>";
            if ($row['introit'] || $row['gradual']) {
                echo "<tr class=\"heading propers\"><td colspan=3>";
                if ($row['introit'])
                    echo "<p class=\"sbspar maxcolumn smaller\">{$row['introit']}</p>";
                if ($row['gradual'])
                    echo "<p class=\"sbspar halfcolumn smaller\">{$row['gradual']}</p>";
                echo "</td></tr>";
            }
            if ($row['propersnote']) {
                echo "<tr class=\"heading propers\"><td colspan=3>
                    <p class=\"maxcolumn\">".
                    translate_markup($row['propersnote'])."</p></td></tr>";
            }
            echo "\n</table></td></tr>\n";
            if ($row['block'])
            { ?>
                <tr data-occ="<?=$row['occurrence']?>"><td colspan=3 class="blockdisplay">
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
                    <dt>Sermon<?=$row['has_sermon']?'*':''?></dt><dd><?=linkbgw($cfg, $row['bsermon'], $row['smlink'])?></dd>
                    </dl>
                    <h5>Collect (<?=$row['bcollectclass']?>)</h5>
                    <div class="collecttext maxcolumn">
                        <?=$row['bcollect']?>
                    </div>
                </tr>
            <? }
            if ($row['servicenotes']) {
                echo "<tr data-occ=\"{$row['occurrence']}\"><td colspan=3 class=\"servicenote\">".
                     translate_markup($row['servicenotes'])."</td></tr>\n";
            }
            $serviceid = $row['serviceid'];
            $occurrence = $row['occurrence'];
        }
        // Collect hymns
        $thesehymns[] = $row;
        $hymnoccurrence = $row['occurrence'];
    }
    if ($thesehymns) listthesehymns($thesehymns, $rowcount, $hymnoccurrence);
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
    ?><table id="records-listing"><?
    $cfg = getConfig(false);
    $thesehymns = array();
    $rowcount = 1;
    $serviceid = "";
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if ($serviceid && $row['serviceid'] != $serviceid)
        {
            displayServiceHeaderCombined($thesehymns);
            $rowcount += listthesehymns($thesehymns, $rowcount, true);
        }
        // Collect hymns
        $thesehymns[] = $row;
        $serviceid = $row['serviceid'];
    }
    if ($thesehymns) {
        displayServiceHeaderCombined($thesehymns);
        listthesehymns($thesehymns, $rowcount, true);
    }
    echo "</article>\n";
    echo "</table>\n";
}

function displayServiceHeaderCombined($thesehymns) {
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
    echo "<tr class=\"heading servicehead\"><td class=\"heavy\">{$datetext}</td>
        <td><a name=\"service_{$row['serviceid']}\">{$row['dayname']}</a>: {$row['rite']}".
    ((3==$auth)?
    "<a class=\"menulink\" href=\"sermon.php?id={$row['serviceid']}\">Sermon</a>\n"
    :"").
    "<a class=\"menulink\" href=\"export.php?service={$row['serviceid']}\">CSV Data</a>\n".
    " <a class=\"menulink\" href=\"print.php?id={$row['serviceid']}\" title=\"print\">Print</a> ".
    "</td></tr>\n";
    for ($i=0, $limit=count($occurrences); $i<$limit; $i++) {
    echo "<tr class=\"service-flags\" data-occ=\"{$occurrences[$i]}\" data-service=\"{$row['serviceid']}\"><td colspan=2></td><td>".
    (($auth)?
    " <a class=\"menulink\" title=\"Edit flags for this service.\" href=\"flags.php?id={$row['serviceid']}&occurrence={$urloccurrences[$i]}\">Flags</a> "
    :"").
        "{$occurrences[$i]}</td></tr>\n";
    }
    echo "<tr class=\"heading\"><td class=\"propers\" colspan=3>\n";
    echo "<table><tr><td class=\"heavy smaller\">{$row['theme']}</td>";
    echo "<td colspan=2>{$row['color']}</td></tr>";
    if ($row['introit'] || $row['gradual']) {
        echo "<tr class=\"heading propers\"><td colspan=3>";
        if ($row['introit'])
            echo "<p class=\"sbspar maxcolumn smaller\">{$row['introit']}</p>";
        if ($row['gradual'])
            echo "<p class=\"sbspar halfcolumn smaller\">{$row['gradual']}</p>";
        echo "</td></tr>";
    }
    if ($row['propersnote']) {
        echo "<tr class=\"heading propers\"><td colspan=3>
            <p class=\"maxcolumn\">".
            translate_markup($row['propersnote'])."</p></td></tr>";
    }
    echo "\n</table></td></tr>\n";
    if ($row['block'])
    { ?>
        <tr data-occ="<?=$row['occurrence']?>"><td colspan=3 class="blockdisplay">
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
            <dt>Sermon<?=$row['has_sermon']?'*':''?></dt><dd><?=linkbgw($cfg, $row['bsermon'], $row['smlink'])?></dd>
            </dl>
            <h5>Collect (<?=$row['bcollectclass']?>)</h5>
            <div class="collecttext maxcolumn">
                <?=$row['bcollect']?>
            </div>
        </tr>
    <? }
    if ($row['servicenotes']) {
        echo "<tr data-occ=\"{$row['occurrence']}\"><td colspan=3 class=\"servicenote\">".
             translate_markup($row['servicenotes'])."</td></tr>\n";
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
    $thesehymns = array();
    $hymnoccurrence = "";
    ?> <table id="modify-listing"> <?
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if (! ($row['serviceid'] == $serviceid
            && $row['occurrence'] == $occurrence))
        { // Create Service Block
            $rowcount += listthesehymns($thesehymns, $rowcount);
            if (is_within_week($row['date'])) {
                $datetext = "<a name=\"now\">{$row['date']}</a>";
            } else {
                $datetext = $row['date'];
            }
            $urldate=urlencode($row['browserdate']);
            $urloccurrence=urlencode($row['occurrence']);
            echo "<tr data-occ=\"{$row['occurrence']}\" class=\"heading servicehead\"><td>
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
            <a class=\"menulink\" title=\"Edit flags for this service.\" href=\"flags.php?id={$row['serviceid']}&occurrence={$urloccurrence}\">Flags</a>
            </div>
            </td>
            <td colspan=2>
            <a name=\"service_{$row['serviceid']}\">{$row['dayname']}</a>: {$row['rite']}
            </td></tr>\n";
            echo "<tr class=\"service-flags\" data-occ=\"{$row['occurrence']}\" data-service=\"{$row['serviceid']}\"><td colspan=3></td></tr>\n";
            echo "<tr data-occ=\"{$row['occurrence']}\" class=\"heading\"><td colspan=3 class=\"propers\">\n";
            echo "<table><tr><td class=\"heavy smaller\">{$row['theme']}</td>";
            echo "<td colspan=2>{$row['color']}</td></tr>";
            if ($row['introit'] || $row['gradual']) {
                echo "<tr><td colspan=3>";
                if ($row['introit'])
                    echo "<p class=\"sbspar maxcolumn smaller\">{$row['introit']}</p>";
                if ($row['gradual'])
                    echo "<p class=\"sbspar halfcolumn smaller\">{$row['gradual']}</p>";
                echo "</td></tr>";
            }
            if ($row['propersnote']) {
                echo "<tr><td colspan=3>
                    <p class=\"maxcolumn\">".
                    translate_markup($row['propersnote'])."</p></td></tr>";
            }
            echo "\n</tr></table></td>\n";
            if ($row['block'])
            { ?>
                <tr data-occ="<?=$row['occurrence']?>"><td colspan=3 class="blockdisplay">
                    <h4>Block: <?=$row['blabel']?></h4>
                    <div class="blocknotes">
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
                    <dt>Sermon<?=$row['has_sermon']?'*':''?></dt><dd><?=linkbgw($cfg, $row['bsermon'], $row['smlink'])?></dd>
                    </dl>
                    <h5>Collect (<?=$row['bcollectclass']?>)</h5>
                    <div class="collecttext maxcolumn">
                        <?=$row['bcollect']?>
                    </div>
                </tr>
            <? }
            if ($row['servicenotes']) {
                echo "<tr data-occ=\"{$row['occurrence']}\"><td colspan=3 class=\"servicenote\">".
                     translate_markup($row['servicenotes'])."</td></tr>\n";
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
    ?> <table id="modify-listing"> <?
    $serviceid = "";
    $rowcount = 1;
    $thesehymns = array();
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        if ($serviceid && $row['serviceid'] != $serviceid)
        {
            modifyServiceHeaderCombined($thesehymns);
            listthesehymns($thesehymns, $rowcount, true);
        }
        // Collect hymns
        $thesehymns[] = $row;
        $serviceid = $row['serviceid'];
    }
    if ($thesehymns) {
        modifyServiceHeaderCombined($thesehymns);
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

function modifyServiceHeaderCombined($thesehymns) {
    $auth = authLevel();
    $cfg = getConfig(false);
    $occurrences = array();
    foreach ($thesehymns as $row) {
        if ($row['occurrence'])
            $occurrences[$row['occurrence']] = 1;
    }
    $occurrences = array_keys($occurrences);
    $urloccurrences=array_map(function($o) {return rawurlencode($o);}, $occurrences);
    if (is_within_week($row['date'])) {
        $datetext = "<a name=\"now\">{$row['date']}</a>";
    } else {
        $datetext = $row['date'];
    }
    $urldate=urlencode($row['browserdate']);
    echo "<tr class=\"heading servicehead\"><td>
    <input form=\"delete-service\" type=\"checkbox\" name=\"{$row['serviceid']}\" id=\"check_{$row['serviceid']}\">
    <span class=\"heavy\">{$datetext}</span>
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
    </td></tr>\n";
    for ($i=0, $limit=count($occurrences); $i<$limit; $i++) {
        echo "<tr class=\"service-flags\" data-occ=\"{$occurrences[$i]}\" data-service=\"{$row['serviceid']}\"><td colspan=2></td>
            <td><a class=\"menulink\" title=\"Edit flags for this service.\" href=\"flags.php?id={$row['serviceid']}&occurrence={$urloccurrences[$i]}\">Flags</a> {$occurrences[$i]}</td>
        </tr>\n";
    }
    echo "<tr class=\"heading\"><td colspan=3 class=\"propers\">\n";
    echo "<table><tr><td class=\"heavy smaller\">{$row['theme']}</td>";
    echo "<td colspan=2>{$row['color']}</td></tr>";
    if ($row['introit'] || $row['gradual']) {
        echo "<tr><td colspan=3>";
        if ($row['introit'])
            echo "<p class=\"sbspar maxcolumn smaller\">{$row['introit']}</p>";
        if ($row['gradual'])
            echo "<p class=\"sbspar halfcolumn smaller\">{$row['gradual']}</p>";
        echo "</td></tr>";
    }
    if ($row['propersnote']) {
        echo "<tr><td colspan=3>
            <p class=\"maxcolumn\">".
            translate_markup($row['propersnote'])."</p></td></tr>";
    }
    echo "\n</tr></table></td>\n";
    if ($row['block'])
    { ?>
        <tr><td colspan=3 class="blockdisplay">
            <h4>Block: <?=$row['blabel']?></h4>
            <div class="blocknotes">
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
            <dt>Sermon<?=$row['has_sermon']?'*':''?></dt><dd><?=linkbgw($cfg, $row['bsermon'], $row['smlink'])?></dd>
            </dl>
            <h5>Collect (<?=$row['bcollectclass']?>)</h5>
            <div class="collecttext maxcolumn">
                <?=$row['bcollect']?>
            </div>
        </tr>
    <? }
    if ($row['servicenotes']) {
        echo "<tr><td colspan=3 class=\"servicenote\">".
             translate_markup($row['servicenotes'])."</td></tr>\n";
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
        <script type=\"text/javascript\" src=\"jquery/jquery.ba-dotimeout.min.js\"></script>";
        $rv[] = "<script type=\"text/javascript\" src=\"{$here}/ecmascript.js.php\"></script>";
    }
    echo "<!-- AddToHeader Section -->";
    if ($AddToHeader) {
        foreach ($AddToHeader as $content) $rv[] = $content;
    }
    $rv[] = "</head>";
    return implode("\n", $rv);
}

function linkbgw($config, $ref, $linked, $other=true) {
    // Return a link to BibleGateway for the given reference,
    // or if the version is not set, just the ref.
    if (! $linked) {
        return $ref;
    }
    try { // The config value may not be set.
        $bgwversion = urlencode($config->get("biblegwversion"));
        if ($other) $other = " target=\"bgmain\" ";
        else $other = "";
        return "<a href=\"http://biblegateway.com/passage?search=".
            rawurlencode($ref).
            "&version={$bgwversion}&interface=print\" ${other}>{$ref}</a>";
    } catch(ConfigfileUnknownKey $e) {
        return $ref;
    }
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

function daysForDate($date) {
    // Return an array of day names matching the given English-format date.
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    if (! $date) return array();
    $found = array();
    $date = strtotime($date);
    $q = $dbh->prepare("call {$dbp}get_days_for_date(:date)");
    $q->bindValue(':date', strftime('%Y-%m-%d', $date));
    $result = $q->execute();
    while ($row = $q->fetch(PDO::FETCH_NUM)) {
        $found[] = $row[0];
    }
    return $found;
}

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

// vim: set foldmethod=indent :
?>
