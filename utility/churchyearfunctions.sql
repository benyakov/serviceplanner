DELIMITER $$
DROP FUNCTION IF EXISTS easter_in_year;
CREATE FUNCTION easter_in_year(p_year INTEGER) RETURNS DATE
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
END$$

DROP FUNCTION IF EXISTS christmas1_in_year;
CREATE FUNCTION christmas1_in_year(p_year INTEGER) RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE wdchristmas INTEGER;
    SET wdchristmas = DAYOFWEEK(CONCAT_WS('-', p_year, 12, 25));
    IF wdchristmas = 1 THEN
        RETURN CONCAT_WS('-', p_year, 12, 25);
    ELSE
        RETURN CONCAT_WS('-', p_year, 12, 25) + INTERVAL (8-wdchristmas) DAY;
    END IF;
END$$

DROP FUNCTION IF EXISTS michaelmas1_in_year;
CREATE FUNCTION michaelmas1_in_year(p_year INTEGER) RETURNS DATE
READS SQL DATA
BEGIN
    DECLARE mike_observed, wdmichaelmas, oct1wd INTEGER;
    DECLARE michaelmas, oct1 DATE;
    SELECT observed_sunday FROM `{$dbp}churchyear`
        WHERE dayname="Michaelmas" INTO mike_observed;
    SET michaelmas = CONCAT_WS('-', p_year, 9, 29);
    SET wdmichaelmas = DAYOFWEEK(michaelmas);
    IF mike_observed != -1 && wdmichaelmas = 7 THEN
        RETURN CONCAT_WS('-', p_year, 9, 30);
    END IF;
    SET oct1 = CONCAT_WS('-', p_year, 10, 1);
    SET oct1wd = DAYOFWEEK(oct1);
    RETURN oct1 + INTERVAL 8-oct1wd DAY;
END$$

DROP FUNCTION IF EXISTS calc_date_in_year;
CREATE FUNCTION calc_date_in_year (p_year INTEGER, p_dayname VARCHAR(255),
    base VARCHAR(255), offset INTEGER, month INTEGER, day INTEGER)
    RETURNS DATE
DETERMINISTIC
BEGIN
    IF base IS NULL THEN
        RETURN CONCAT_WS('-', p_year, month, day);
    END IF;
    IF base = "Easter" THEN
        RETURN easter_in_year(p_year) + INTERVAL offset DAY;
    ELSEIF base = "Christmas 1" THEN
        RETURN christmas1_in_year(p_year) + INTERVAL offset DAY;
    ELSEIF base = "Michaelmas 1" THEN
        RETURN michaelmas1_in_year(p_year) + INTERVAL offset DAY;
    END IF;
END$$

DROP FUNCTION IF EXISTS date_in_year;
CREATE FUNCTION date_in_year (p_year INTEGER, p_dayname VARCHAR(255))
RETURNS DATE
READS SQL DATA
BEGIN
    DECLARE base, offset, month, day INTEGER;
    SELECT base, offset, month, day
        FROM `{$dbp}churchyear`
        WHERE dayname=p_dayname
        INTO base, offset, month, day;
    RETURN calc_date_in_year(p_year, p_dayname, base, offset, month, day);
END$$

DROP FUNCTION IF EXISTS calc_observed_date_in_year;
CREATE FUNCTION calc_observed_date_in_year (p_year INTEGER,
    p_dayname VARCHAR(255), base VARCHAR(255),
    observed_month INTEGER, observed_sunday INTEGER)
RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE actual, firstofmonth, lastofmonth DATE;
    IF base IS NULL THEN
        IF observed_month = 0 THEN
            SET actual = date_in_year(p_year, p_dayname);
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
    END IF;
END$$

DROP FUNCTION IF EXISTS observed_date_in_year;
CREATE FUNCTION observed_date_in_year (p_year INTEGER, p_dayname VARCHAR(255))
RETURNS DATE
READS SQL DATA
BEGIN
    DECLARE base, observed_month, observed_sunday INTEGER;
    SELECT base, observed_month, observed_sunday
        FROM `{$dbp}churchyear`
        WHERE dayname=p_dayname
        INTO base, observed_month, observed_sunday;
RETURN calc_observed_date_in_year(p_year, p_dayname, base, observed_month,
    observed_sunday);
END$$

DELIMITER ;
