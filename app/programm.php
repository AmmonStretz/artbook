<?php
require_once __DIR__ . '/src/bootstrap.php';

$vid = (int)($_GET['id'] ?? 0);
if (!$vid) { header('Location: /'); exit; }

$stmt = db()->prepare("
    SELECT v.*, MIN(t.datum) AS erster_tag, MAX(t.datum) AS letzter_tag
    FROM veranstaltung v
    LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
    WHERE v.id = ? AND v.sichtbar = 1
    GROUP BY v.id
");
$stmt->execute([$vid]);
$event = $stmt->fetch();
if (!$event) { header('Location: /'); exit; }

$prog_stmt = db()->prepare("
    SELECT pp.id, pp.datum, pp.uhrzeit, pp.titel, pp.beschreibung, pp.ort_name,
           GROUP_CONCAT(t.id   ORDER BY t.name SEPARATOR ',')    AS tn_ids,
           GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR '\x01') AS tn_namen
    FROM veranstaltung_programm pp
    LEFT JOIN programm_teilnehmer pt ON pt.programm_id = pp.id
    LEFT JOIN teilnehmer t ON t.id = pt.teilnehmer_id
    WHERE pp.veranstaltung_id = ?
    GROUP BY pp.id
    ORDER BY pp.datum, pp.uhrzeit
");
$prog_stmt->execute([$vid]);

$program_by_day = [];
foreach ($prog_stmt->fetchAll() as $e) {
    $program_by_day[$e['datum']][] = $e;
}

$today    = date('Y-m-d');
$days     = array_keys($program_by_day);
$active_tab = $days[0] ?? null;
foreach ($days as $d) {
    if ($d >= $today) { $active_tab = $d; break; }
}
if (in_array($today, $days)) $active_tab = $today;

echo twig()->render('programm.twig', [
    'page_title'     => 'Programm – ' . $event['name'],
    'nav_active'     => 'veranstaltungen',
    'vid'            => $vid,
    'event'          => $event,
    'program_by_day' => $program_by_day,
    'active_tab'     => $active_tab,
]);
