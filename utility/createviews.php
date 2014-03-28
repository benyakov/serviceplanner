<? /* Create views needed to process day synonyms
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
$dbh = new DBConnection();
$dbp = $dbh->getPrefix();
$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}synlessons`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}synlessons` AS
    SELECT s.synonym AS dayname, l.lectionary, l.lesson1, l.lesson2,
    l.gospel, l.psalm, l.s2lesson, l.s2gospel, l.s3lesson, l.s3gospel,
    l.hymnabc, l.hymn
    FROM `{$dbp}churchyear_lessons` AS l
    RIGHT JOIN `{$dbp}churchyear_synonyms` AS s ON (l.dayname = s.canonical)");
$q->execute() or die(array_pop($q->errorInfo()));
$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}synpropers`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}synpropers` AS
    SELECT s.synonym AS dayname, p.color, p.theme, p.introit, p.note,
    COALESCE(p.gradual, g.gradual) AS gradual
    FROM `{$dbp}churchyear_propers` AS p
    RIGHT JOIN `{$dbp}churchyear_synonyms` AS s ON (p.dayname = s.canonical)
    LEFT JOIN `{$dbp}churchyear` AS cy ON (p.dayname = cy.dayname)
    LEFT JOIN `{$dbp}churchyear_graduals` AS g ON (g.season = cy.season)");
$q->execute() or die(array_pop($q->errorInfo()));;
$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}lesson1selections`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}lesson1selections` AS
    SELECT DISTINCT b.l1lect, b.l1series, d.name AS dayname, s.lesson1
    FROM `{$dbp}days` AS d
    JOIN `{$dbp}blocks` AS b ON (b.id = d.block)
    JOIN `{$dbp}synlessons` AS s
        ON (d.name = s.dayname AND b.l1lect=s.lectionary)");
$q->execute() or die(array_pop($q->errorInfo()));;
$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}lesson2selections`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}lesson2selections` AS
    SELECT DISTINCT b.l2lect, b.l2series, d.name AS dayname, s.lesson2
    FROM `{$dbp}days` AS d
    JOIN `{$dbp}blocks` AS b ON (b.id = d.block)
    JOIN `{$dbp}synlessons` AS s
        ON (d.name = s.dayname AND b.l2lect=s.lectionary)");
$q->execute() or die(array_pop($q->errorInfo()));;
$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}gospelselections`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}gospelselections` AS
    SELECT DISTINCT b.golect, b.goseries, d.name AS dayname, s.gospel
    FROM `{$dbp}days` AS d
    JOIN `{$dbp}blocks` AS b ON (b.id = d.block)
    JOIN `{$dbp}synlessons` AS s
        ON (d.name = s.dayname AND b.golect=s.lectionary)");
$q->execute() or die(array_pop($q->errorInfo()));;
$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}sermonselections`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}sermonselections` AS
    SELECT DISTINCT b.smlect, b.smseries, d.name AS dayname, s.gospel,
    s.lesson1, s.lesson2
    FROM `{$dbp}days` AS d
    JOIN `{$dbp}blocks` AS b ON (b.id = d.block)
    JOIN `{$dbp}synlessons` AS s
        ON (d.name = s.dayname AND b.smlect=s.lectionary)");
$q->execute() or die(array_pop($q->errorInfo()));;
?>
