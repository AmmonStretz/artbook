<?php
require_once __DIR__ . '/src/bootstrap.php';

$today = date('Y-m-d');

$stmt = db()->prepare("
    SELECT v.id, v.name, v.ort_name, v.plz, v.ort, v.titelbild,
           MIN(t.datum) AS erster_tag, MAX(t.datum) AS letzter_tag,
           COUNT(DISTINCT vt.teilnehmer_id) AS tn_count
    FROM veranstaltung v
    LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
    LEFT JOIN veranstaltung_teilnahme vt ON vt.veranstaltung_id = v.id
    WHERE v.sichtbar = 1
    GROUP BY v.id
    HAVING letzter_tag < ?
    ORDER BY erster_tag DESC
");
$stmt->execute([$today]);
$veranstaltungen = $stmt->fetchAll();

echo twig()->render('archiv.twig', [
    'page_title'      => 'Archiv',
    'nav_active'      => '',
    'veranstaltungen' => $veranstaltungen,
]);
