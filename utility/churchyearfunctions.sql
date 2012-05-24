DELIMITER $$;
CREATE FUNCTION easter_in_year(p_year INTEGER) RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE century, shiftedEpact, adjustedEpact INTEGER;
    DECLARE paschalMoon DATE;
    SET century = 1 + p_year/100;
     # "Age of moon for April 5";
    SET shiftedEpact = (14 + 11 * (p_year % 19)) # Nicean rule
        - (3 * century) / 4                # Gregorian Century rule
        + ((8 * century) + 5) / 25         # Metonic cycle correction
        + (30 * century) % 30;              # To keep the value positive
     # "Adjust for 29.5 day month.";
    IF shiftedEpact = 0 OR (shiftedEpact = 1 AND 10 < (p_year % 19)) THEN
        SET adjustedEpact = shiftedEpact + 1;
    ELSE
        SET adjustedEpact = shiftedEpact;
    END IF;
    SET paschalMoon = DATE_SUB(CONCAT_WS('-', p_year, 4, 19),
        INTERVAL adjustedEpact DAYS);
    RETURN DATE_ADD(paschalMoon, INTERVAL (8 - DAYOFWEEK(paschalMoon)) DAYS);
END$$

CREATE FUNCTION christmas1_in_year(p_year INTEGER) RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE wdchristmas INTEGER;
    SET wdchristmas = DAYOFWEEK(CONCAT_WS('-', p_year, 12, 25));
    IF wdchristmas = 1 THEN
        RETURN CONCAT_WS('-', p_year, 12, 25);
    ELSE
        RETURN DATE_ADD(CONCAT_WS('-', p_year, 12, 25), 8-wdchristmas);
    END IF;
END$$

CREATE FUNCTION michaelmas1_in_year(p_year INTEGER) RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE mike_observed, wdmichaelmas, oct1wd INTEGER
    DECLARE michaelmas, oct1 DATE;
    SELECT observed_sunday FROM {$dbp}churchyear
        WHERE dayname="Michaelmas" INTO @mike_observed;
    SET @michaelmas = STR_TO_DATE(CONCAT_WS('-', p_year, 9, 29));
    SET @wdmichaelmas = DAYOFWEEK(@michaelmas);
    IF @mike_observed != -1 && @wdmichaelmas = 7 THEN
        RETURN CONCAT_WS('-', p_year, 9, 30);
    END IF;
    SET @oct1 = CONCAT_WS('-', p_year, 10, 1);
    SET @oct1wd = DAYOFWEEK(@oct1);
    RETURN DATE_ADD(@oct1, 8-@oct1wd DAYS);
END$$

CREATE FUNCTION calc_date_in_year (p_year INTEGER, p_dayname STRING,
    base STRING, offset INTEGER, month INTEGER, day INTEGER)
DETERMINISTIC
RETURNS DATE
BEGIN
    IF base IS NULL THEN
        RETURN CONCAT_WS('-', p_year, month, day);
    END IF;
    IF base = "Easter" THEN
        RETURN DATE_ADD(easter_in_year(p_year), offset DAYS);
    ELSEIF base = "Christmas 1" THEN
        RETURN DATE_ADD(christmas1_in_year(p_year), offset DAYS);
    ELSEIF base = "Michaelmas 1" THEN
        RETURN DATE_ADD(michaelmas1_in_year(p_year), offset DAYS);
    END IF;
END$$

CREATE FUNCTION date_in_year (p_year INTEGER, p_dayname STRING)
READS SQL DATA
RETURNS DATE
BEGIN
    DECLARE base, offset, month, day INTEGER;
    SELECT base, offset, month, day
        FROM {$dbp}churchyear
        WHERE dayname=p_dayname
        INTO base, offset, month, day;
    RETURN calc_date_in_year(p_year, p_dayname, base, offset, month, day)
END$$

CREATE FUNCTION calc_observed_date_in_year (p_year INTEGER, p_dayname STRING,
    base STRING, observed_month INTEGER, observed_sunday INTEGER)
DETERMINISTIC
RETURNS DATE
BEGIN
    DECLARE actual, firstofmonth, lastofmonth DATE;
    IF base IS NULL THEN
        IF observed_month = 0 THEN
            SET actual = date_in_year(p_year, p_dayname)
            IF DAYOFWEEK(actual) > 1 THEN
                RETURN DATE_ADD(actual, 8-DAYOFWEEK(actual) DAYS);
            END IF;
        END IF;
        IF observed_sunday > 0 THEN
            SET firstofmonth =
                CONCAT_WS('-', p_year, observed_month, 1);
            IF DAYOFWEEK(firstofmonth) > 1 THEN
                SET firstofmonth = DATE_ADD(firstofmonth,
                    8-DAYOFWEEK(firstofmonth) DAYS);
            END IF;
            RETURN DATE_ADD(firstofmonth, (observed_sunday-1)*7 DAYS);
        ELSE
            SET lastofmonth =
                DATE_SUB(DATE_ADD(
                    CONCAT_WS('-', p_year, observed_month)
                    , 1 MONTH), 1 DAY);
            IF DAYOFWEEK(lastofmonth > 1) THEN
                SET lastofmonth = DATE_SUB(lastofmonth,
                    DAYOFWEEK(lastofmonth)-1);
            END IF;
            RETURN DATE_ADD(lastofmonth, (observed_sunday+1)*7 DAYS);
        END IF;
    END IF;
END$$

CREATE FUNCTION observed_date_in_year (p_year INTEGER, p_dayname STRING)
READS SQL DATA
RETURNS DATE
BEGIN
    DECLARE base, observed_month, observed_sunday INTEGER
SELECT base, observed_month, observed_sunday
    FROM {$dbp}churchyear
    WHERE dayname=p_dayname
    INTO base, observed_month, observed_sunday;
RETURN calc_observed_date_in_year(p_year, p_dayname, base, observed_month,
    observed_sunday);
END$$

DELIMITER ;
