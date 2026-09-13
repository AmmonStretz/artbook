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

$today        = date('Y-m-d');
$now_time     = date('H:i:s');
// TODO: TEST-MODUS – vor Go-Live entfernen
$today        = '2026-11-21';
$now_time     = '15:30:00';
$one_hour_ago = date('H:i:s', strtotime($now_time) - 3600);

$program_by_day = [];
foreach ($prog_stmt->fetchAll() as $e) {
    if ($e['datum'] < $today || ($e['datum'] === $today && $e['uhrzeit'] < $one_hour_ago)) {
        $e['prog_status'] = 'past';
    } elseif ($e['datum'] === $today && $e['uhrzeit'] <= $now_time) {
        $e['prog_status'] = 'live';
    } else {
        $e['prog_status'] = 'future';
    }
    $program_by_day[$e['datum']][] = $e;
}

echo twig()->render('programm.twig', [
    'page_title'     => 'Programm – ' . $event['name'],
    'nav_active'     => 'veranstaltungen',
    'vid'            => $vid,
    'event'          => $event,
    'program_by_day' => $program_by_day,
    'date_today'     => $today,
    'date_tomorrow'  => date('Y-m-d', strtotime('+1 day')),
    'total_prog'     => array_sum(array_map('count', $program_by_day)),
]);
