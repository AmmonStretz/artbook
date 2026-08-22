<?php
require_once __DIR__ . '/src/bootstrap.php';

$meta = db()->query("SELECT * FROM meta WHERE id = 1")->fetch() ?: [];

$bew_vid  = (int)($meta['bewerbung_veranstaltung_id'] ?? 0) ?: null;
$deadline = $meta['bewerbung_deadline'] ?? null;
$today    = date('Y-m-d');
$now_time = date('H:i:s');

$stmt = db()->prepare("
    SELECT v.*, MIN(t.datum) AS erster_tag, MAX(t.datum) AS letzter_tag
    FROM veranstaltung v
    LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
    WHERE v.sichtbar = 1
    GROUP BY v.id
    HAVING letzter_tag >= DATE_SUB(?, INTERVAL 7 DAY) OR letzter_tag IS NULL
    ORDER BY
      CASE
        WHEN erster_tag <= ? AND letzter_tag >= ? THEN 0
        WHEN erster_tag  > ?                       THEN 1
        ELSE                                            2
      END ASC,
      ABS(DATEDIFF(COALESCE(erster_tag, ?), ?)) ASC
    LIMIT 1
");
$stmt->execute([$today, $today, $today, $today, $today, $today]);
$active_event = $stmt->fetch() ?: null;
$active_vid   = $active_event ? (int)$active_event['id'] : null;

$in_bewerbung = $active_vid && $bew_vid === $active_vid && $deadline && $today <= $deadline;

$event_state = 'normal';
if ($active_event && $active_event['erster_tag']) {
    $start = $active_event['erster_tag'];
    $end   = $active_event['letzter_tag'] ?? $start;
    if ($today > $end) {
        $days_since  = (int)floor((strtotime($today) - strtotime($end)) / 86400);
        $event_state = $days_since <= 7 ? 'nachher' : 'vorbei';
    } elseif ($today >= $start) {
        $event_state = 'laeuft';
    } else {
        $days_until  = (int)floor((strtotime($start) - strtotime($today)) / 86400);
        $event_state = $days_until <= 7 ? 'bald' : 'normal';
    }
}

if ($event_state === 'vorbei') $active_event = null;

$preview_tn = [];
if ($active_vid && $active_event && $event_state !== 'nachher'
    && ($active_event['teilnehmer_sichtbar'] ?? 1)) {
    $ptn = db()->prepare("
        SELECT t.id, t.name, t.typ, t.gruppe_typ,
               (SELECT b.dateiname FROM teilnehmer_bild b
                WHERE b.teilnehmer_id = t.id ORDER BY b.id ASC LIMIT 1) AS first_image
        FROM teilnehmer t
        JOIN veranstaltung_teilnahme vt ON vt.teilnehmer_id = t.id
        WHERE vt.veranstaltung_id = ?
        ORDER BY RAND() LIMIT 8
    ");
    $ptn->execute([$active_vid]);
    $preview_tn = $ptn->fetchAll();
}

$json_ld = '';
if ($active_event && $active_event['erster_tag']) {
    $json_ld = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'Event',
        'name'     => $active_event['name'],
        'startDate' => $active_event['erster_tag'],
        'endDate'   => $active_event['letzter_tag'] ?? $active_event['erster_tag'],
        'location'  => [
            '@type'   => 'Place',
            'name'    => $active_event['ort_name'] ?: ($active_event['ort'] ?? ''),
            'address' => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $active_event['strasse'] ?? '',
                'postalCode'      => $active_event['plz'] ?? '',
                'addressLocality' => $active_event['ort'] ?? '',
                'addressCountry'  => 'DE',
            ],
        ],
        'url'       => SITE_URL,
        'organizer' => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

echo twig()->render('index.twig', [
    'page_title'       => '',
    'nav_active'       => 'home',
    'meta_description' => strip_tags($meta['einladung'] ?? '') ?: null,
    'meta'             => $meta,
    'active_event'     => $active_event,
    'active_vid'       => $active_vid,
    'in_bewerbung'     => $in_bewerbung,
    'event_state'      => $event_state,
    'preview_tn'       => $preview_tn,
    'json_ld'          => $json_ld,
]);
