<?php
require_once __DIR__ . '/src/bootstrap.php';

$tid = (int)($_GET['id']  ?? 0);
$vid = (int)($_GET['vid'] ?? 0) ?: null;
if (!$tid) { header('Location: /'); exit; }

$stmt = db()->prepare("SELECT * FROM teilnehmer WHERE id = ?");
$stmt->execute([$tid]);
$t = $stmt->fetch();
if (!$t) { header('Location: /'); exit; }

$bilder = db()->prepare("SELECT * FROM teilnehmer_bild WHERE teilnehmer_id = ? ORDER BY id");
$bilder->execute([$tid]);
$bilder = $bilder->fetchAll();

$members = [];
if ($t['typ'] === 'gruppe') {
    $ms = db()->prepare("
        SELECT k.id, k.name FROM teilnehmer k
        JOIN gruppe_kuenstler gk ON gk.kuenstler_id = k.id
        WHERE gk.gruppe_id = ? ORDER BY k.name
    ");
    $ms->execute([$tid]);
    $members = $ms->fetchAll();
}

$ev_stmt = db()->prepare("
    SELECT v.id, v.name, MIN(t.datum) AS erster_tag
    FROM veranstaltung v
    JOIN veranstaltung_teilnahme vt ON vt.veranstaltung_id = v.id
    LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
    WHERE vt.teilnehmer_id = ? AND v.sichtbar = 1 AND v.teilnehmer_sichtbar = 1
    GROUP BY v.id ORDER BY erster_tag DESC
");
$ev_stmt->execute([$tid]);
$attended_events = $ev_stmt->fetchAll();

$prog_stmt = db()->prepare("
    SELECT vp.id, vp.datum, vp.uhrzeit, vp.titel, vp.beschreibung, vp.ort_name,
           v.id AS veranstaltung_id, v.name AS veranstaltung_name
    FROM programm_teilnehmer pt
    JOIN veranstaltung_programm vp ON vp.id = pt.programm_id
    JOIN veranstaltung v ON v.id = vp.veranstaltung_id
    WHERE pt.teilnehmer_id = ? AND v.sichtbar = 1 AND v.programm_sichtbar = 1
    ORDER BY vp.datum ASC, vp.uhrzeit ASC
");
$prog_stmt->execute([$tid]);
$meine_programmpunkte = $prog_stmt->fetchAll();

$type_label = typeLabel($t['typ'], $t['gruppe_typ'] ?? null);

echo twig()->render('teilnehmer.twig', [
    'page_title'           => $t['name'],
    'nav_active'           => $vid ? 'teilnehmer' : 'kuenstler',
    'tid'                  => $tid,
    'vid'                  => $vid,
    't'                    => $t,
    'type_label'           => $type_label,
    'bilder'               => $bilder,
    'members'              => $members,
    'attended_events'      => $attended_events,
    'meine_programmpunkte' => $meine_programmpunkte,
    'back_url'             => $vid ? langUrl("/veranstaltung.php?id=$vid") : langUrl('/kuenstler.php'),
    'back_label'           => $vid ? t('participant.back_list') : t('participant.back_artists'),
]);
