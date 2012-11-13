<? // Create views needed to process day synonyms

$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}synlessons`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}synlessons` AS
    SELECT s.synonym AS dayname, l.lectionary, l.lesson1, l.lesson2,
    l.gospel, l.psalm, l.s2lesson, l.s2gospel, l.s3lesson, l.s3gospel,
    l.hymnabc, l.hymn
    FROM `{$dbp}churchyear_lessons` AS l
    RIGHT JOIN `{$dbp}churchyear_synonyms` AS s ON (l.dayname = s.canonical)");
$q->execute();
$q = $dbh->exec("DROP VIEW IF EXISTS `{$dbp}synpropers`");
$q = $dbh->prepare("CREATE VIEW `{$dbp}synpropers` AS
    SELECT s.synonym AS dayname, p.color, p.theme, p.introit, p.note
    FROM `{$dbp}churchyear_propers` AS p
    RIGHT JOIN `{$dbp}churchyear_synonyms` AS s ON (p.dayname = s.canonical)");
$q->execute();
?>
