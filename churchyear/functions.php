<? /* Church year interface
    Copyright (C) 2021 Jesse Jacobsen

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
    //echo "<pre> "; print_r($rows); echo "</pre>";
    ob_start();
?>
<table id="churchyear-listing">
<tr><td></td><th>Name</th><th>Next<br>Observed</th><th>Season</th><th>Base Day</th>
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
    <td class="next"><?$next=next_in_year($row['dayname'], $rows); if ($next) {echo $next->format("d M Y");} else { print_r($next); }?></td>
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
    if (2 == strlen("{$year}")) $year = "20{$year}";
    $dtobject = new DateTime();
    $dtobject->setTimestamp(easter_date($year));
    return $dtobject;
    /******* Manual calculation (date arithmetic untested in PHP)
    * $century = $shiftedEpact = $adjustedEpact = 0;
    * $apr19 = new DateTimeImmutable("4/19/{$year}");
    * $century = 1 + intdiv($year, 100);
    * // Age of moon for April 5
    * $shiftedEpact = (14 + (11 * ($year % 19))       // Nicean rule
    *     - intdiv((3 * $century), 4)                 // Gregory Century rule
    *     + intdiv(((8 * $century) + 5), 25)           // Metonic cycle correction
    *     + (30 * $century) % 30);                     // To keep the value positive
    * // Adjust for 29.5 day month
    * if ($shiftedEpact == 0 or ($shiftedEpact == 1 and 10 < ($year % 19))) {
    *     $adjustedEpact = $shiftedEpact + 1;
    * } else {
    *     $adjustedEpact = $shiftedEpact;
    * }
    * $paschalMoon = $apr19->sub(makeInterval("P{$adjustedEpact}D"));
    * return $paschalMoon->add(makeInterval("P". (8 - $paschalMoon->format("w")."D")));
    */
}

function get_advent4_in_year($year) {
    $christmas = new DateTimeImmutable("12/25/{$year}");
    $wdchristmas = $christmas->format("w");
    if (1 == $wdchristmas) { // Christmas is a Sunday
        return new DateTimeImmutable("12/18/{$year}");
    } else {
        return $christmas->sub(makeInterval("P".($wdchristmas)."D"));
    }
}

function get_christmas1_in_year($year) {
    $christmas = new DateTimeImmutable("12/25/{$year}");
    $wdchristmas = $christmas->format("w");
    return $christmas->add(makeInterval("P".(7-$wdchristmas)."D"));
}

function get_michaelmas1_in_year($year, $table) {
    $day_params = churchyear_table_rec($table, "Michaelmas");
    $sep30 = new DateTimeImmutable("9/30/{$year}");
    $sep30wd = $sep30->format("w"); // One=Sunday
    if (0 == $sep30wd) {
        return $sep30;
    } else {
        return $sep30->sub(makeInterval("P{$sep30wd}D"));
    }
}

function get_epiphany1_in_year($year) {
    $epiphany = new DateTimeImmutable("1/6/{$year}");
    $wdepiphany = $epiphany->format("w");
    return $epiphany->add(makeInterval("P".(7-$wdepiphany)."D"));
}

function churchyear_table_rec($table, $dayname) {
    // Get the table row where dayname is $dayname
    // Put the row into $day_params
    $day_params = "Not Found";
    foreach ($table as $index=>$table_row) {
        if ($table_row["dayname"] == $dayname) {
            $day_params = $table_row;
            break;
        }
    }
    return $day_params;
}

function makeInterval($initstring) {
    if (strpos($initstring, "-") !== false) {
        $initstring = str_replace('-', '', $initstring);
        $invert = 1;
    } else {
        $invert = 0;
    }
    $interval = new DateInterval($initstring);
    $interval->invert = $invert;
    return $interval;
}

function date_in_year($year, $dayname, $table) {
    $day_params = churchyear_table_rec($table, $dayname);
    $base = $day_params['base'];
    $offset = $day_params['offset'];
    //echo "<pre>"; print_r($day_params); echo "</pre>";
    if ((! $base) // Base is null, 0, or ""
        and ($day_params['month'] and $day_params['day'])) {
        return new DateTimeImmutable("{$day_params['month']}/{$day_params['day']}/{$year}");
    } else {
        $interval = makeInterval("P{$offset}D");
    }
    if ("Easter" == $base) {
        return get_easter_in_year($year)->add($interval);
    } elseif ("Christmas 1" == $base) {
        if ($offset > 0) {
            $year = $year - 1;
        }
        return get_christmas1_in_year($year)->add($interval);
    } elseif ("Advent 4" == $base) {
        return get_advent4_in_year($year)->add($interval);
    } elseif ("Michaelmas 1" == $base) {
        return get_michaelmas1_in_year($year, $table)->add($interval);
    } elseif ("Epiphany 1" == $base) {
        return get_epiphany1_in_year($year)->add($interval);
    } else {
        return 0;
    }
}

/******
* Return a DateTimeImmutable for the observed (Nth Sunday of month) or 0 if not specified
*/
function get_observed($day_params, $year) {
        if ($day_params['observed_sunday'] > 0) {  // By Sunday from beginning
            $firstofmonth = new DateTimeImmutable("{$day_params['observed_month']}/1/{$year}");
            $firstofmonthwd = $firstofmonth->format("w");
            if (1 < $firstofmonthwd) { // Not Sunday; adjust to Sunday
                $firstofmonth = $firstofmonth->add(
                    makeInterval("P".(7-$firstofmonthwd)."D"));
            }
            return $firstofmonth->add(
                makeInterval("P".(($day_params['observed_sunday']-1)*7)."D"));
        } elseif ($day_params['observed_sunday'] < 0) {  // By Sunday from end of month (negative)
            $first_of_month = new DateTimeImmutable("{$day_params['observed_month']}/1/{$year}");
            $next_month = $first_of_month->add(makeInterval("P1M"));
            $last_day_of_month = $next_month->sub(makeInterval("P1D"));
            $last_day_of_monthwd = $last_day_of_month->format("w");
            $last_sunday_of_month = $last_day_of_month->sub(
                makeInterval("P{$last_day_of_monthwd}D"));
            $rv = $last_sunday_of_month->add(
                makeInterval("P".(($day_params['observed_sunday']+1)*7)."D"));
            return $rv;
        } else {
            return 0;
        }
}

function observed_date_in_year($year, $dayname, $table) {
    $day_params = churchyear_table_rec($table, $dayname);
    $base = $day_params['base'];
    if (! $base) { // $base is null or ""
        // Check for actual date
        $actual = date_in_year($year, $dayname, $table);
        if (! $actual)  {  // No date specified. 
            if ($observed = get_observed($day_params, $year)) {
                return $observed;
            } else { // No actual or observed, just return today's date.
                return new DateTimeImmutable("now");
            } 
        } else {
            $actualwd = $actual->format("w");            // Check if actual is a Sunday
            if ($actualwd == 1) { 
                return $actual;
            } else {
                if ($day_params['observed_sunday'] != 0) { // If not, check if there is an observed Sunday
                    //echo "Getting observed month.";
                    return get_observed($day_params, $year);
                } else {                                 
                    if ($day_params['season']) {        // If it has a season, use actual
                        return $actual;
                    } else {                            // Otherwise, rewind to Sunday
                        //echo "Rewinding.";
                        return $actual->sub(makeInterval("P{$actualwd}D"));
                    }
                }
            } 
        }
    } else {
        return date_in_year($year, $dayname, $table);
    }
}

function calendar_date_in_year($year, $dayname, $table) {
    $day_params = churchyear_table_rec($table, $dayname);
    return new DateTime("{$day_params['month']}/{$day_params['day']}/{$year}");
}

function next_in_year($dayname, $table) {
    $now = new DateTime();
    $year = $now->format("Y");
    $date = observed_date_in_year($year, $dayname, $table);
    if ($date < $now) {
        $date = observed_date_in_year(($year+1), $dayname, $table);
    }
    //echo "<pre>".print_r($date)."</pre>"; 
    return $date;
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
        $cy_table = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cy_table as $cy_row) {
            if (date_in_year($date->format("Y"), $cy_row['dayname'], $cy_table) == $date) {
                $rv[]=$cy_row['dayname'];
            } elseif (observed_date_in_year($date->format("Y"), $cy_row['dayname'], $cy_table) == $date) {
                $rv[]=$cy_row['dayname'];
            } elseif (calendar_date_in_year($date->format("Y"), $cy_row['dayname'], $cy_table) == $date) {
                $rv[]=$cy_row['dayame'];
            }
        }
        return $rv;
    }
}


?>
