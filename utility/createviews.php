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
        g.gradual
    FROM `{$dbp}churchyear_propers` AS p
    RIGHT JOIN `{$dbp}churchyear_synonyms` AS s ON (p.dayname = s.canonical)
    LEFT JOIN `{$dbp}churchyear` AS cy ON (p.dayname = cy.dayname)
    LEFT JOIN `{$dbp}churchyear_graduals` AS g ON (g.season = cy.season)");
$q->execute() or die(array_pop($q->errorInfo()));;
?>
