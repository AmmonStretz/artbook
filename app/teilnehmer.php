<?php
require_once __DIR__ . '/src/bootstrap.php';

$tid = (int)($_GET['id']  ?? 0);
$vid = (int)($_GET['vid'] ?? 0) ?: null;
if (!$tid) { header('Location: /'); exit; }

$stmt = db()->prepare("SELECT * FROM teilnehmer WHERE id = ?");
$stmt->execute([$tid]);
$t = $stmt->fetch();
if (!$t) { header('Location: /'); exit; }

$is_gruppe = $t['kategorie'] !== 'kuenstler';

if ($is_gruppe && $vid) {
    // Active members for this specific event
    $ms = db()->prepare("
        SELECT k.id, k.name, k.link,
               (SELECT b.dateiname FROM teilnehmer_bild b WHERE b.teilnehmer_id = k.id ORDER BY b.id ASC LIMIT 1) AS first_image
        FROM teilnehmer k
        JOIN veranstaltung_gruppe_mitglied vgm ON vgm.mitglied_id = k.id
        WHERE vgm.gruppe_id = ? AND vgm.veranstaltung_id = ?
        ORDER BY k.name
    ");
    $ms->execute([$tid, $vid]);
    $members = $ms->fetchAll();

    // Past members: in the group globally but not active for this event
    $pm = db()->prepare("
        SELECT k.id, k.name, k.link,
               (SELECT b.dateiname FROM teilnehmer_bild b WHERE b.teilnehmer_id = k.id ORDER BY b.id ASC LIMIT 1) AS first_image
        FROM teilnehmer k
        JOIN teilnehmer_mitglied tm ON tm.mitglied_id = k.id
        WHERE tm.gruppe_id = ?
        AND k.id NOT IN (
            SELECT mitglied_id FROM veranstaltung_gruppe_mitglied
            WHERE gruppe_id = ? AND veranstaltung_id = ?
        )
        ORDER BY k.name
    ");
    $pm->execute([$tid, $tid, $vid]);
    $past_members = $pm->fetchAll();

    // Images: active member images first, then group's own images
    $bild_active = db()->prepare("
        SELECT b.* FROM teilnehmer_bild b
        JOIN veranstaltung_gruppe_mitglied vgm ON vgm.mitglied_id = b.teilnehmer_id
        WHERE vgm.gruppe_id = ? AND vgm.veranstaltung_id = ?
        ORDER BY b.id
    ");
    $bild_active->execute([$tid, $vid]);
    $bild_own = db()->prepare("SELECT * FROM teilnehmer_bild WHERE teilnehmer_id = ? ORDER BY id");
    $bild_own->execute([$tid]);
    $bilder = array_merge($bild_active->fetchAll(), $bild_own->fetchAll());
} else {
    // No event context: show all global members
    $ms = db()->prepare("
        SELECT k.id, k.name, k.link,
               (SELECT b.dateiname FROM teilnehmer_bild b WHERE b.teilnehmer_id = k.id ORDER BY b.id ASC LIMIT 1) AS first_image
        FROM teilnehmer k
        JOIN teilnehmer_mitglied tm ON tm.mitglied_id = k.id
        WHERE tm.gruppe_id = ? ORDER BY k.name
    ");
    $ms->execute([$tid]);
    $members = $ms->fetchAll();
    $past_members = [];

    $bild_stmt = db()->prepare("SELECT * FROM teilnehmer_bild WHERE teilnehmer_id = ? ORDER BY id");
    $bild_stmt->execute([$tid]);
    $bilder = $bild_stmt->fetchAll();
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

$type_label = typeLabel($t['kategorie'] ?? null);

echo twig()->render('teilnehmer.twig', [
    'page_title'           => $t['name'],
    'nav_active'           => $vid ? 'teilnehmer' : 'kuenstler',
    'tid'                  => $tid,
    'vid'                  => $vid,
    't'                    => $t,
    'type_label'           => $type_label,
    'bilder'               => $bilder,
    'members'              => $members,
    'past_members'         => $past_members,
    'attended_events'      => $attended_events,
    'meine_programmpunkte' => $meine_programmpunkte,
]);
