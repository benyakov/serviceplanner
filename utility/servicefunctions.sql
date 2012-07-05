# Service functions to be installed on Mysql server
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
DROP FUNCTION IF EXISTS `{{DBP}}GET_LESSON`;
CREATE FUNCTION `{{DBP}}GET_LESSON`(dayname VARCHAR(255), lesson VARCHAR(16),
    lect VARCHAR(56), series VARCHAR(16)) RETURNS VARCHAR(64)
DETERMINISTIC
BEGIN
    DECLARE field VARCHAR(16);
    IF lect = 'historic' THEN
        IF lesson = 'lesson1' OR lesson = 'lesson2' THEN
            IF series = 'first' THEN
                SET field = lesson;
            ELSE IF series = 'second' THEN
                SET field = 's2lesson';
            ELSE IF series = 'third' THEN
                SET field = 's3lesson';
            END IF
        ELSE IF lesson = 'gospel' THEN
            IF series = 'first' THEN
                SET field = 'gospel';
            ELSE IF series = 'second' THEN
                SET field = 's2gospel';
            ELSE IF series = 'third' THEN
                SET field = 's3gospel';
            END IF
        END IF
        SET @sql_text = concat('SELECT ', field,
                ' FROM `{{DBP}}churchyear_lessons` WHERE dayname=''',
                dayname, '''');
        PREPARE stmt FROM @sql_text;
        EXECUTE stmt;
    ELSE
        SET @sql_text = concat('SELECT ', lesson,
            ' FROM `{{DBP}}churchyear_lessons` WHERE dayname = ''',
            dayname, '''');
        PREPARE stmt FROM @sql_text;
        EXECUTE stmt;
    END IF
END;

#DELIMITER ;
