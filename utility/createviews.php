<? // Create views needed to process day synonyms

$q = $dbh->exec("DROP VIEW IF EXISTS `synlessons`");
$q = $dbh->prepare("CREATE VIEW `synlessons` AS
    SELECT s.synonym AS dayname, l.lectionary, l.lesson1, l.lesson2,
    l.gospel, l.psalm, l.s2lesson, l.s2gospel, l.s3lesson, l.s3gospel,
    l.hymnabc, l.hymn
    FROM `{$dbp}churchyear_lessons` AS s
    RIGHT JOIN `{$dbp}churchyear_synonyms` AS l ON (l.dayname = s.canonical)");
$q->execute();

?>
