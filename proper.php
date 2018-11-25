<?php /* Get a single proper for a service
    Copyright (C) 2018 Jesse Jacobsen

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
$uid = authUid();
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
$db = new DBConnection();
if (! (isset($_POST['step']) || isset($_POST['json']))) {
    if ($type = getGET('type', "") &&
        is_numeric(getGET('serviceid')))
    {
        if (! in_array($type,
            ['blesson1', 'blesson2', 'bgospel', 'bpsalm', 'introit', 'gradual'])) {
            echo json_encode([false, "Unrecognized type of lesson."]);
            exit(0);
        $db->beginTransaction();
        if ("deselect" == getGET('action')) {
            }
            $q = $db->prepare("UPDATE `{$db->getPrefix()}days`
                SET `{$type}` = NULL
                WHERE `d.pkey` = :serviceid");
            $q->bindValue(":serviceid", getGET('serviceid'));
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        // Return a json-formatted [ success, value ] for the proper.
        $dbp = $dbh->getPrefix();
        $q = $db->prepare("SELECT
            COALESCE(d.multichoice_introit, cyp.introit) AS introit,
            COALESCE(d.multichoice_gradual,
                (CASE b.weeklygradual
                    WHEN '1' THEN cyp.weeklygradual
                    ELSE cyp.seasonalgradual
                    END))
                AS gradual,
            COALESCE(d.multichoice_lesson1, l1s.lesson1, l1s.l1series) AS blesson1,
            COALESCE(d.multichoice_lesson2, l2s.lesson2, l2s.l2series) AS blesson2,
            COALESCE(d.multichoice_gospel, gos.gospel, gos.goseries) AS bgospel,
            COALESCE(d.multichoice_psalm, (
                CASE b.pslect
                    WHEN 'custom' THEN b.psseries
                    ELSE
                        (SELECT psalm FROM `{$dbp}synlessons` AS cl
                        WHERE cl.dayname=d.name AND cl.lectionary=b.pslect
                        LIMIT 1)
                    END))
                AS bpsalm

            FROM `{$dbp}days` AS d
            LEFT OUTER JOIN `{$dbp}hymns` AS h ON (h.service = d.pkey)
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
            LEFT JOIN `{$dbp}synlessons` AS synl
            ON (synl.dayname=d.name AND synl.lectionary=b.smlect)

            WHERE d.pkey = :serviceid";

        $q->execute() or die(array_pop($q->errorInfo()));
        $proper = $q->fetch(PDO::FETCH_ASSOC)[$type];
        echo json_encode([true, $proper]);
        exit(0);
    } else {
        echo json_encode([false, "Improper serviceid or no proper type given."]);
        exit(0);
    }
} else {

    // TODO: return multichoice selection dialog (below is old flags code)
    if (! (is_numeric(getGET('id')) and getGET('occurrence')) ) {
        setMessage("Need both a service and occurrence to see service flags. ".
            "Have you chosen a occurrence by adding hymns?");
        header("Location: modify.php");
        exit(0);
    } else {
        $id = getGET('id');
        $occurrence = getGET('occurrence');
        $urloccurrence = urlencode($occurrence);
        $htmloccurrence = htmlspecialchars($occurrence);
    }
    ?><!DOCTYPE html>
    <html lang="en">
    <?=html_head("Edit Service Flags")?>
    <body>
    <script type="text/javascript">
        $(document).ready(function() {
            setupFlags();
        });
    </script>
    <? pageHeader();
    siteTabs("modify"); ?>
        <div id="content-container">
        <div class="quicklinks"><a href="modify.php">Back to Service Listing</a>
        </div>
        <h1>Service Flags</h1>
        <p class="explanation">This page allows you to see the flags on a
service and either add to them or change them.</p>

<?  echo generateFlagsForm($id, $occurrence);
    $q = queryService($id);
    display_records_table($q, "delete.php");
    ?>
    </div>
    </body>
    </html>
<?

}
