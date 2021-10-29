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

function query_churchyear($json=false) {
    /* Return an executed query for all rows of the churchyear db
     */
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT cy.`dayname`, cy.`season`, cy.`base`,
        cy.`offset`, cy.`month`, cy.`day`,
        cy.`observed_month`, cy.`observed_sunday`,
        `{$dbp}next_in_year`(cy.`dayname`) AS next
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
<tr><td></td><th>Name</th><th>Next</th><th>Season</th><th>Base Day</th>
    <th>Days Offset</th><th>Month</th>
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
            class="propersname"><?=$row['dayname']?></a></td>
    <td class="next"><?=$row['next']?></td>
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

function reconfigureNonfestival($type) {
    /* Given either "Historic", "ILCW", or "RCL", reconfigure the settings for
     * the affected days in the Church Year to skip Sundays at the appropriate
     * times.
     */
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $dbh->beginTransaction();
    $q = $dbh->prepare("UPDATE `{$dbp}churchyear` SET
        `base` = :base,
        `offset` = :offset
        WHERE `dayname` = :dayname");
    $qbap = $dbh->prepare("UPDATE `{$dbp}churchyear` SET
        `base` = :base,
        `offset` = :offset,
        `season` = :season
        WHERE `dayname` = :dayname");
    $base = $offset = $dayname = $season = "";
    $q->bindParam(":base", $base);
    $q->bindParam(":offset", $offset);
    $q->bindParam(":dayname", $dayname);
    $qbap->bindParam(":base", $base);
    $qbap->bindParam(":offset", $offset);
    $qbap->bindParam(":dayname", $dayname);
    $qbap->bindParam(":season", $season);
    if ("Historic" == $type) {
        $base = "Easter";
        for ($i = 1; $i <= 24; $i++) {
            $offset = 56 + $i * 7;
            $dayname = "Trinity {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $base = "Christmas 1";
        $offset = -49;
        $dayname = "Third Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -42;
        $dayname = "Second Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -35;
        $dayname = "Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $base = "Michaelmas 1";
        for ($i = 1; $i <= 7; $i++) {
            $offset = ($i-1) * 7;
            $dayname = "Michaelmas {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $season = "Pre-lent";
        $base = "Easter";
        $offset = -49;
        $dayname = "Baptism of Jesus";
        $qbap->execute() or die(array_pop($q->errorInfo()));
    } elseif ("ILCW" == $type) {
        $base = "Easter";
        for ($i = 1; $i < 25; $i++) {
            $offset = 56 + $i * 7;
            $dayname = "Trinity {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $offset = 56 + ++$i * 7;
        $dayname = "Third Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = 56 + ++$i * 7;
        $dayname = "Second Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -35;
        $base = "Christmas 1";
        $dayname = "Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $season = "Epiphany";
        $base = "Epiphany 1";
        $dayname = "Baptism of Jesus";
        $offset = 0;
        $qbap->execute() or die(array_pop($q->errorInfo()));
    } elseif ("RCL" == $type) {
        $base = "Christmas 1";
        $offset = -35;
        $dayname = "Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -42;
        $dayname = "Second Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        $offset = -49;
        $dayname = "Third Last";
        $q->execute() or die(array_pop($q->errorInfo()));
        for ($i = 24, $offset = -56; $i >= 1; $i--, $offset-=7) {
            $dayname = "Trinity {$i}";
            $q->execute() or die(array_pop($q->errorInfo()));
        }
        $season = "Epiphany";
        $base = "Epiphany 1";
        $dayname = "Baptism of Jesus";
        $offset = 0;
        $qbap->execute() or die(array_pop($q->errorInfo()));
    }
    $dbh->commit();
}


function get_easter_in_year($year) {

    $century = $shiftedEpact = $adjustedEpact = 0;
    $apr19 = new DateTimeImmutable('4/19/{$year}');
    $century = 1 + intdiv($year, 100);
    // Age of moon for April 5
    $shiftedEpact = (14 + (11 * ($year % 19))       // Nicean rule
        - intdiv(((3 * century), 4)                 // Gregory Century rule
        + intdiv(((8 * century) + 5), 25)           // Metonic cycle correction
        + (30 * century)) % 30;                     // To keep the value positive
    // Adjust for 29.5 day month
    if (shiftedEpact == 0 or (shiftedEpact == 1 and 10 < ($year % 19))) {
        $adjustedEpact = $shiftedEpact + 1;
    } else {
        $adjustedEpact = $shiftedEpact;
    }
    $paschalMoon = $apr19->sub(new DateInterval("p{$adjustedEpact}D");
    return $paschalMoon->add(new DateInterval("p". (8 - $paschalMoon.format("w"));

    // Check with easter_date() ?
}

function get_advent4_in_year($year) {
    $christmas = new DateTimeImmutable("12/25/{$year}");
    $wdchristmas = $christmas->format("w");
    if (1 == $wdchristmas) {
        return new DateTimeImmutable("12/18/{$year}");
    } else {
        return $christmas->sub($wdchristmas - new DateInterval("P1D"));
    }
    return $christmas->add(8-new DateInterval("P".$wdchristmas."D"));
}

function get_christmas1_in_year($year) {
    $christmas = new DateTimeImmutable("12/25/{$year}");
    $wdchristmas = $christmas->format("w");
    return $christmas->add(8-new DateInterval("P".$wdchristmas."D"));
}

function get_michaelmas1_in_year($year) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT cy.`observed_sunday` FROM `{$dbp}churchyear`
        WHERE cy.`dayname` = 'Michaelmas'");
    if (! $q->execute()) {
        die("Problem getting Michaelmas observed Sunday: ".array_pop($q->errorInfo()));
    } else {
        $mike_observed = $q->fetchColumn(0);
    }
    $michaelmas = new DateTimeImmutable("9/29/{$year}");
    $wdmichaelmas = $michaelmas->format("w");
    if (1 != $mike_observed and 7 == $wdmichaelmas) {
        return new DateTimeImmutable("9/30{$year}");
    }
    $oct1 = new DateTimeImmutable("10/1/{$year}");
    $oct1wd = $oct1->format("w");
    if (1 == $oct1wd) {
        return oct1;
    } else {
        return $oct1->add(new DateInterval("p".(8-oct1wd)."D"));
    }
}

function get_epiphany1_in_year($year) {
    $epiphany = new DateTimeImmutable("1/6/{$year}");
    $wdepiphany = $epiphany->format("w");
    return $epiphany->add(new DateInterval("p".(8-$wdepiphany)."D"));
}

function calc_date_in_year($year, $base, $offset, $month, $day) {
    $interval = new DateInterval("p{$offset}D");
    if (is_null($base)) {
        return new DateTimeImmutable("{$month}/{$day},{$year}");
    } elseif ("Easter" == $base) {
        return get_easter_in_year($year).add($interval);
    } elseif ("Christmas 1" == $base) {
        if ($offset > 0) {
            $year = $year - 1;
        }
        return get_christmas1_in_year($year).add($interval);
    } elseif ("Advent 4" == $base) {
        return get_advent4_in_year($year).add($interval);
    } elseif ("Michaelmas 1" == $base) {
        return get_michaelmas1_in_year($year).add($interval);
    } elseif ("Epiphany 1" == $base) {
        return get_epiphany1_in_year($year).add($interval);
    } else {
        return 0;
    }
}

function date_in_year($year, $dayname) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT base, offset, month, day
        FROM `{$dbp}churchyear`
        WHERE `dayname`=:dayname");
    $q->bindValue(":dayname", $dayname);
    if (! $q->execute()) {
        die("Problem getting day params: ".array_pop($q->errorInfo()));
    } else {
        $day_params = $q->fetch(PDO::FETCH_ASSOC);
    }
    return calc_date_in_year($year, $day_params['base',
        $day_params['offset'], $day_params['month'], $day_params['day']);
}

function calc_observed_date_in_year($year, $dayname, $base, $observed_month,
    $observed_sunday) {
    if (! $base) { // $base is null or ""
        if (0 == $observed_month) { // Observing by date when not Sunday
            $actual = date_in_year($year, $dayname);
            $actualwd = $actual->format("w");
            if ($actualwd > 1) {
                return $actual.add(new DateInterval("p".(8-$actualwd)."D"));
            }
        } elseif (0 < $observed_sunday) {  // Observing by Sunday of month
            $firstofmonth = new DateTimeImmutable("1/{$observed_month}/{$year}");
            $firstofmonthwd = $firstofmoth->format("w");
            if (1 < $firstofmonthwd) { // Past Sunday; adjust to Sunday
                $firstofmonth = $firstofmonth.add(
                    new DateInterval("p".(8-$firstofmonthwd));
            }
            return $firstofmonth.add(
                new DateInterval("p".(($observed_sunday-1)*7)."D"));
        } else {  // Observing by Sunday counting from end of month (negative)
            $first_of_month = new DateTimeImmutable("1/{$observed_month}/{$year}");
            $next_month = $first_of_month.add(new DateInterval("p1m"));
            $last_day_of_month = $next_month.sub(new DateInterval("p1d"));
            $last_day_of_monthwd = $last_day_of_month->format("w");
            $last_sunday_of_month = $last_day_of_month.sub(
                new DateInterval("p{$last_day_of_monthwd}D"));
            return $last_sunday_of_month.add(
                new DateInterval("p".(($observed_sunday+1)*7)."D"));
        }
    } else {
        return 0;
    }
}

function observed_date_in_year($year, $dayname) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT base, observed_month, observed_sunday
        FROM `{$dbp}churchyear`
        WHERE `dayname`=:dayname");
    $q->bindValue(":dayname", $dayname);
    if (! $q->execute()) {
        die("Problem getting day params: ".array_pop($q->errorInfo()));
    } else {
        $day_params = $q->fetch(PDO::FETCH_ASSOC);
    }
    return calc_observed_date_in_year($year, $dayname, $day_params['base'],
        $day_params['observed_month'], $day_params['observed_sunday']);
}

function calendar_date_in_year($year, $dayname) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT day, month FROM `{$dbp}churchyear`
        WHERE `dayname` = :dayname");
    $q->bindValue(":dayname", $dayname);
    if (! $q->execute()) {
        die("Problem getting day params: ".array_pop($q->errorInfo()));
    } else {
        $day_month = $q->fetch(PDO::FETCH_ASSOC);
    }
    return new DateTime("{$day_month['day']}/{$day_month['month']}/{$year}");
}

function get_days_for_date($date) {
    // To do this in PHP (rather than SQL functions, the orig. strategy), we
    // must examine the entire table in PHP and use our PHP functions
    // to test each dayname in the year of $date for a match of $date.
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT * FROM `{$dbp}churchyear`");
    if (! $q->execute()) {
        die("Problem getting churchyear table: ".array_pop($q->errorInfo()));
    } else {
        $rv = array();
        while ($cy_row = $q->fetch(PDO::FETCH_ASSOC)) {
            if (date_in_year($date->format("y"), $cy_row['dayname']) == $date) {
                $rv[]=$cy_row['dayname'];
            } elseif {
                observed_date_in_year($date->format("y"), $cy_row['dayname']) == $date) {
                $rv[]=$return $cy_row['dayname'];
            } elseif
                calendar_date_in_year($date->format("y"), $cy_row['dayname']) == $date)
                $rv[]=$cy_row['dayame'];
            }
        }
        return $rv;
    }
}

function next_in_year($dayname) {
    $now = new DateTime();
    $year = $now->format("y");
    $result = date_in_year($year, $dayname) or
        observed_date_in_year($year, $dayname);
    if ($result < $now) {
        $result = date_in_year(($year+1), $dayname) or
            observed_date_in_year(($year+1), $dayname);
    }
    return $result
}

?>
