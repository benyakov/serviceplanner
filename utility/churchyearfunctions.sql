DELIMITER $$;
CREATE FUNCTION easter_in_year(p_year INT) RETURNS DATE
DETERMINISTIC
BEGIN
SET @iD=0,@iE=0,@iQ=0,@iMonth=0,@iDay=0;
SET @iD = 255 - 11 * (p_year % 19);
SET @iD = IF (@iD > 50,(@iD-21) % 30 + 21,@iD);
SET @iD = @iD - IF(@iD > 48, 1 ,0);
SET @iE = (p_year + FLOOR(p_year/4) + @iD + 1) % 7;
SET @iQ = @iD + 7 - @iE;
IF @iQ < 32 THEN
    SET @iMonth = 3;
    SET @iDay = @iQ;
ELSE
    SET @iMonth = 4;
    SET @iDay = @iQ - 31;
END IF;
RETURN STR_TO_DATE(CONCAT(p_year,'-',@iMonth,'-',@iDay),'%Y-%m-%d');
END$$

CREATE FUNCTION christmas1_in_year(p_year INT) RETURNS DATE
DETERMINISTIC
BEGIN
SET @wdchristmas = DAYOFWEEK(CONCAT_WS('-', p_year, 12, 25));
IF @wdchristmas = 1 THEN
    RETURN CONCAT_WS('-', p_year, 12, 25);
ELSE
    RETURN DATE_ADD(CONCAT_WS('-', p_year, 12, 25), 8-@wdchristmas);
END$$

CREATE FUNCTION michaelmas1_in_year(p_year INT) RETURNS DATE
DETERMINISTIC
BEGIN
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

CREATE FUNCTION date_in_year (p_year INT, p_dayname STRING)
RETURNS DATE
BEGIN
SELECT base, offset, month, day
    FROM {$dbp}churchyear
    WHERE dayname=p_dayname
    INTO base, offset, month, day;
IF **IS_NULL(base) THEN RETURN CONCAT_WS('-', p_year, month, day);
IF base = "Easter" THEN
    RETURN DATE_ADD(easter_in_year(p_year), offset DAYS);
ELSEIF base = "Christmas 1" THEN
    RETURN DATE_ADD(christmas1_in_year(p_year), offset DAYS);
ELSEIF base = "Michaelmas 1" THEN
    RETURN DATE_ADD(michaelmas1_in_year(p_year), offset DAYS);
END IF;
END$$

CREATE FUNCTION observed_date_in_year (p_year INT, p_dayname STRING)
RETURNS DATE
BEGIN
SELECT base, offset, observed_month, observed_sunday
    FROM {$dbp}churchyear
    WHERE dayname=p_dayname
    INTO base, offset, observed_month, observed_sunday;
IF **IS_NULL(base) THEN
    IF **IS_NULL(observed_month) THEN
        SET @actual = date_in_year(p_year, p_dayname)
        IF DAYOFWEEK(@actual) > 1 THEN
            RETURN DATE_ADD(@actual, 8-DAYOFWEEK(@actual) DAYS);
    END IF;
    IF observed_sunday > 0 THEN
        SET @firstofmonth =
            CONCAT_WS('-', p_year, observed_month, 1);
        IF DAYOFWEEK(@firstofmonth) > 1 THEN
            SET @firstofmonth = DATE_ADD(@firstofmonth, 8-DAYOFWEEK(@firstofmonth) DAYS);
        END IF;
        RETURN DATE_ADD(@firstofmonth, (observed_sunday-1)*7 DAYS);
    ELSE
        SET @lastofmonth =
        DATE_SUB(DATE_ADD(
            CONCAT_WS('-', p_year, observed_month)
            , 1 MONTH), 1 DAY);
        IF DAYOFWEEK(@lastofmonth > 1) THEN
            SET @lastofmonth = DATE_SUB(@lastofmonth,
                DAYOFWEEK(@lastofmonth)-1);
        END IF;
        RETURN DATE_ADD(@lastofmonth, (observed_sunday+1)*7 DAYS);
    END IF;
    END IF;
END IF;
END$$

DELIMITER ;
