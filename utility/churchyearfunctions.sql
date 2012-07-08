# Church year functions to be installed on Mysql server
#   Copyright (C) 2012 Jesse Jacobsen

#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.

#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.

#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

#   Send feedback or donations to: Jesse Jacobsen <jmatjac@gmail.com>

#   Mailed donation may be sent to:
#   Bethany Lutheran Church
#   2323 E. 12th St.
#   The Dalles, OR 97058
#   USA


#DELIMITER $$
DROP FUNCTION IF EXISTS `{{DBP}}easter_in_year`;
CREATE FUNCTION `{{DBP}}easter_in_year`(p_year INTEGER) RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE century, shiftedEpact, adjustedEpact INTEGER;
    DECLARE paschalMoon, apr19 DATE;
    SET century = 1 + p_year/100;
     # "Age of moon for April 5";
    SET shiftedEpact = (14 + (11 * (p_year % 19)) # Nicean rule
        - ((3 * century) / 4)                # Gregorian Century rule
        + (((8 * century) + 5) / 25)         # Metonic cycle correction
        + (30 * century)) % 30;              # To keep the value positive
     # "Adjust for 29.5 day month.";
    IF shiftedEpact = 0 OR (shiftedEpact = 1 AND 10 < (p_year % 19)) THEN
        SET adjustedEpact = shiftedEpact + 1;
    ELSE
        SET adjustedEpact = shiftedEpact;
    END IF;
    SET apr19 = CONCAT_WS('-', p_year, 4, 19) ;
    SET paschalMoon = apr19 - INTERVAL adjustedEpact DAY;
    RETURN paschalMoon + INTERVAL (8 - DAYOFWEEK(paschalMoon)) DAY;
END;

DROP FUNCTION IF EXISTS `{{DBP}}christmas1_in_year`;
CREATE FUNCTION `{{DBP}}christmas1_in_year`(p_year INTEGER) RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE wdchristmas INTEGER;
    SET wdchristmas = DAYOFWEEK(CONCAT_WS('-', p_year, 12, 25));
    IF wdchristmas = 1 THEN
        RETURN CONCAT_WS('-', p_year, 12, 25);
    ELSE
        RETURN CONCAT_WS('-', p_year, 12, 25) + INTERVAL (8-wdchristmas) DAY;
    END IF;
END;

DROP FUNCTION IF EXISTS `{{DBP}}michaelmas1_in_year`;
CREATE FUNCTION `{{DBP}}michaelmas1_in_year`(p_year INTEGER) RETURNS DATE
READS SQL DATA
BEGIN
    DECLARE mike_observed, wdmichaelmas, oct1wd INTEGER;
    DECLARE michaelmas, oct1 DATE;
    SELECT observed_sunday FROM `{{DBP}}churchyear`
        WHERE dayname="Michaelmas" INTO mike_observed;
    SET michaelmas = CONCAT_WS('-', p_year, 9, 29);
    SET wdmichaelmas = DAYOFWEEK(michaelmas);
    IF mike_observed != -1 && wdmichaelmas = 7 THEN
        RETURN CONCAT_WS('-', p_year, 9, 30);
    END IF;
    SET oct1 = CONCAT_WS('-', p_year, 10, 1);
    SET oct1wd = DAYOFWEEK(oct1);
    RETURN oct1 + INTERVAL 8-oct1wd DAY;
END;

DROP FUNCTION IF EXISTS `{{DBP}}epiphany1_in_year`;
CREATE FUNCTION `{{DBP}}epiphany1_in_year`(p_year INTEGER) RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE wdepiphany INTEGER;
    SET wdepiphany = DAYOFWEEK(CONCAT_WS('-', p_year, 1, 6));
    IF wdepiphany = 1 THEN
        RETURN CONCAT_WS('-', p_year, 1, 6);
    ELSE
        RETURN CONCAT_WS('-', p_year, 1, 6) + INTERVAL (8-wdepiphany) DAY;
    END IF;
END;

DROP FUNCTION IF EXISTS `{{DBP}}calc_date_in_year`;
CREATE FUNCTION `{{DBP}}calc_date_in_year`(p_year INTEGER,
    base VARCHAR(255), offset INTEGER, month INTEGER, day INTEGER)
    RETURNS DATE
DETERMINISTIC
BEGIN
    IF base IS NULL THEN
        RETURN CONCAT_WS('-', p_year, month, day);
    END IF;
    IF base = "Easter" THEN
        RETURN `{{DBP}}easter_in_year`(p_year) + INTERVAL offset DAY;
    ELSEIF base = "Christmas 1" THEN
        RETURN `{{DBP}}christmas1_in_year`(p_year) + INTERVAL offset DAY;
    ELSEIF base = "Michaelmas 1" THEN
        RETURN `{{DBP}}michaelmas1_in_year`(p_year) + INTERVAL offset DAY;
    ELSEIF base = "Epiphany 1" THEN
        RETURN `{{DBP}}epiphany1_in_year`(p_year) + INTERVAL offset DAY;
    ELSE
        RETURN 0;
    END IF;
END;

DROP FUNCTION IF EXISTS `{{DBP}}date_in_year`;
CREATE FUNCTION `{{DBP}}date_in_year`(p_year INTEGER, p_dayname VARCHAR(255))
RETURNS TEXT
READS SQL DATA
BEGIN
    DECLARE v_base VARCHAR(255);
    DECLARE v_offset, v_month, v_day INTEGER;
    SELECT base, offset, month, day
        FROM `{{DBP}}churchyear`
        WHERE dayname=p_dayname
        INTO v_base, v_offset, v_month, v_day;
    RETURN `{{DBP}}calc_date_in_year`(p_year, v_base, v_offset, v_month, v_day);
END;

DROP FUNCTION IF EXISTS `{{DBP}}calc_observed_date_in_year`;
CREATE FUNCTION `{{DBP}}calc_observed_date_in_year`(p_year INTEGER,
    p_dayname VARCHAR(255), base VARCHAR(255),
    observed_month INTEGER, observed_sunday INTEGER)
RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE actual, firstofmonth, lastofmonth DATE;
    IF base IS NULL THEN
        IF observed_month = 0 THEN
            SET actual = `{{DBP}}date_in_year`(p_year, p_dayname);
            IF DAYOFWEEK(actual) > 1 THEN
                RETURN actual + INTERVAL 8-DAYOFWEEK(actual) DAY;
            END IF;
        END IF;
        IF observed_sunday > 0 THEN
            SET firstofmonth =
                CONCAT_WS('-', p_year, observed_month, 1);
            IF DAYOFWEEK(firstofmonth) > 1 THEN
                SET firstofmonth = firstofmonth
                    + INTERVAL 8-DAYOFWEEK(firstofmonth) DAY;
            END IF;
            RETURN firstofmonth + INTERVAL (observed_sunday-1)*7 DAY;
        ELSE
            SET lastofmonth = (CONCAT_WS('-', p_year, observed_month, 1)
                + INTERVAL 1 MONTH) - INTERVAL 1 DAY;
            IF DAYOFWEEK(lastofmonth) > 1 THEN
                SET lastofmonth = lastofmonth
                    - INTERVAL DAYOFWEEK(lastofmonth)-1 DAY;
            END IF;
            RETURN lastofmonth + INTERVAL (observed_sunday+1)*7 DAY;
        END IF;
    ELSE
        RETURN 0;
    END IF;
END;

DROP FUNCTION IF EXISTS `{{DBP}}observed_date_in_year`;
CREATE FUNCTION `{{DBP}}observed_date_in_year` (p_year INTEGER,
    p_dayname VARCHAR(255))
RETURNS DATE
READS SQL DATA
BEGIN
    DECLARE v_base VARCHAR(255);
    DECLARE v_observed_month, v_observed_sunday INTEGER;
    SELECT base, observed_month, observed_sunday
        FROM `{{DBP}}churchyear`
        WHERE dayname=p_dayname
        INTO v_base, v_observed_month, v_observed_sunday;
RETURN `{{DBP}}calc_observed_date_in_year`(p_year, p_dayname, v_base, v_observed_month,
    v_observed_sunday);
END;

DROP PROCEDURE IF EXISTS `{{DBP}}get_days_for_date`;
CREATE PROCEDURE `{{DBP}}get_days_for_date` (p_date DATE)
BEGIN
    SELECT dayname FROM `{{DBP}}churchyear`
    WHERE `{{DBP}}date_in_year`(YEAR(p_date), dayname) = p_date
    OR `{{DBP}}observed_date_in_year`(YEAR(p_date), dayname) = p_date;
END;

DROP FUNCTION IF EXISTS `{{DBP}}get_lesson_field`;
CREATE FUNCTION `{{DBP}}get_lesson_field`(lesson VARCHAR(16),
    lect VARCHAR(56), series VARCHAR(16)) RETURNS VARCHAR(64)
DETERMINISTIC
BEGIN
    IF lect = 'historic' THEN
        IF lesson = 'lesson1' OR lesson = 'lesson2' THEN
            IF series = 'first' THEN RETURN lesson;
            ELSEIF series = 'second' THEN RETURN 's2lesson';
            ELSEIF series = 'third' THEN RETURN 's3lesson';
            END IF;
        ELSEIF lesson = 'gospel' THEN
            IF series = 'first' THEN RETURN 'gospel';
            ELSEIF series = 'second' THEN RETURN 's2gospel';
            ELSEIF series = 'third' THEN RETURN 's3gospel';
            END IF;
        END IF;
    ELSE
        RETURN lesson;
    END IF;
END;

DROP PROCEDURE IF EXISTS `{{DBP}}lesson_ref`;
CREATE PROCEDURE `{{DBP}}lesson_ref`
    (lesson VARCHAR(16), lectionary VARCHAR(56), series VARCHAR(16),
        dayname VARCHAR(255))
DETERMINISTIC
BEGIN
    @q = CONCAT('SELECT `',
        {{DBP}}get_lesson_field`(lesson, lectionary, series),
        '` FROM `{{DBP}}churchyear_lessons` AS cl ',
        ' WHERE cl.dayname = ? AND cl.lectionary = ?');
    PREPARE @query FROM @q;
    EXECUTE @query USING dayname, lectionary;
    DEALLOCATE PREPARE @query;
END;

#DELIMITER ;
