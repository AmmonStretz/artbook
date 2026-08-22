<?php
require_once __DIR__ . '/../../bootstrap.php';

$vid = (int)($_GET['veranstaltung_id'] ?? 0);
if (!$vid) { header('Location: /admin/veranstaltungen/'); exit; }

$event = db()->prepare("SELECT id, name FROM veranstaltung WHERE id = ?");
$event->execute([$vid]);
$event = $event->fetch();
if (!$event) { header('Location: /admin/veranstaltungen/'); exit; }

$stmt = db()->prepare("
    SELECT pp.id, pp.datum, pp.uhrzeit, pp.titel, pp.ort_name,
           COUNT(pt.teilnehmer_id) AS tn_count
    FROM veranstaltung_programm pp
    LEFT JOIN programm_teilnehmer pt ON pt.programm_id = pp.id
    WHERE pp.veranstaltung_id = ?
    GROUP BY pp.id
    ORDER BY pp.datum, pp.uhrzeit
");
$stmt->execute([$vid]);

$by_day = [];
foreach ($stmt->fetchAll() as $r) {
    $by_day[$r['datum']][] = $r;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

echo adminTwig()->render('veranstaltungen/programm/index.twig', [
    'page_title' => 'Programm – ' . $event['name'],
    'nav_active' => 'veranstaltungen',
    'flash'      => $flash,
    'by_day'     => $by_day,
    'event'      => $event,
    'vid'        => $vid,
]);
