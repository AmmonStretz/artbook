<?php
require_once __DIR__ . '/src/bootstrap.php';

$meta     = db()->query("SELECT * FROM meta WHERE id = 1")->fetch() ?: [];
$bew_vid  = (int)($meta['bewerbung_veranstaltung_id'] ?? 0) ?: null;
$deadline = $meta['bewerbung_deadline'] ?? null;
$today    = date('Y-m-d');
$now_time = date('H:i:s');
// TODO: TEST-MODUS – vor Go-Live entfernen
$today    = '2026-11-21';
$now_time = '15:30:00';

// Current/upcoming events first, fallback to most recent past event
$stmt = db()->prepare("
    SELECT v.*, o.anfahrtsbeschreibung,
           MIN(t.datum) AS erster_tag, MAX(t.datum) AS letzter_tag
    FROM veranstaltung v
    LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
    LEFT JOIN veranstaltungsort o ON o.id = v.veranstaltungsort_id
    WHERE v.sichtbar = 1
    GROUP BY v.id
    HAVING letzter_tag >= ? OR letzter_tag IS NULL
    ORDER BY erster_tag ASC
    LIMIT 1
");
$stmt->execute([$today]);
$event = $stmt->fetch() ?: null;

if (!$event) {
    $stmt = db()->prepare("
        SELECT v.*, o.anfahrtsbeschreibung,
               MIN(t.datum) AS erster_tag, MAX(t.datum) AS letzter_tag
        FROM veranstaltung v
        LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
        LEFT JOIN veranstaltungsort o ON o.id = v.veranstaltungsort_id
        WHERE v.sichtbar = 1
        GROUP BY v.id
        HAVING letzter_tag IS NOT NULL
        ORDER BY letzter_tag DESC
        LIMIT 1
    ");
    $stmt->execute([]);
    $event = $stmt->fetch() ?: null;
}

$vid          = $event ? (int)$event['id'] : null;
$in_bewerbung = $vid && $bew_vid === $vid && $deadline && $today <= $deadline;

// Count past events for the archive banner
$past_stmt = db()->prepare("
    SELECT COUNT(*) FROM (
        SELECT v.id FROM veranstaltung v
        LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
        WHERE v.sichtbar = 1
        GROUP BY v.id
        HAVING MAX(t.datum) < ?
    ) past
");
$past_stmt->execute([$today]);
$past_events_count = (int)$past_stmt->fetchColumn();

// Event detail data
$days           = [];
$program_by_day = [];
$preview_by_day = [];
$total_prog     = 0;
$participants   = [];
$total          = 0;
$pages          = 1;
$page           = 1;
$q              = '';
$has_map        = false;
$has_addr       = false;
$muster_banner_url    = null;
$muster_banner_srcset = null;
$muster_is_svg  = false;
$logo_url       = null;
$gmaps_query    = '';
$json_ld        = '';

if ($event) {
    $days_stmt = db()->prepare("SELECT datum, startzeit, endzeit FROM veranstaltung_tag WHERE veranstaltung_id = ? ORDER BY datum");
    $days_stmt->execute([$vid]);
    $days = $days_stmt->fetchAll();

    if ($event['programm_sichtbar'] ?? 1) {
        $prog_stmt = db()->prepare("
            SELECT pp.id, pp.datum, pp.uhrzeit, pp.titel, pp.beschreibung, pp.ort_name,
                   GROUP_CONCAT(t.id   ORDER BY t.name SEPARATOR ',')    AS tn_ids,
                   GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR '\x01') AS tn_namen
            FROM veranstaltung_programm pp
            LEFT JOIN programm_teilnehmer pt ON pt.programm_id = pp.id
            LEFT JOIN teilnehmer t ON t.id = pt.teilnehmer_id
            WHERE pp.veranstaltung_id = ?
            GROUP BY pp.id ORDER BY pp.datum, pp.uhrzeit
        ");
        $prog_stmt->execute([$vid]);
        $one_hour_ago = date('H:i:s', strtotime($now_time) - 3600);
        foreach ($prog_stmt->fetchAll() as $pr) {
            if ($pr['datum'] < $today || ($pr['datum'] === $today && $pr['uhrzeit'] < $one_hour_ago)) {
                $pr['prog_status'] = 'past';
            } elseif ($pr['datum'] === $today && $pr['uhrzeit'] <= $now_time) {
                $pr['prog_status'] = 'live';
            } else {
                $pr['prog_status'] = 'future';
            }
            $program_by_day[$pr['datum']][] = $pr;
        }
    }

    $preview_live   = null;
    $preview_future = [];
    foreach ($program_by_day as $entries) {
        foreach ($entries as $e) {
            if (!$preview_live && $e['prog_status'] === 'live') {
                $preview_live = $e;
            } elseif ($e['prog_status'] === 'future' && count($preview_future) < 2) {
                $preview_future[] = $e;
            }
        }
        if (count($preview_future) >= 2) break;
    }
    foreach (array_merge($preview_live ? [$preview_live] : [], $preview_future) as $e) {
        $preview_by_day[$e['datum']][] = $e;
    }
    $total_prog = array_sum(array_map('count', $program_by_day));

    $q = trim($_GET['q'] ?? '');

    if ($event['teilnehmer_sichtbar'] ?? 1) {
        $per    = PER_PAGE_EVENT_TN;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $q_cond = $q ? 'AND t.name LIKE :q' : '';

        $count_stmt = db()->prepare("
            SELECT COUNT(*) FROM veranstaltung_teilnahme vt
            JOIN teilnehmer t ON t.id = vt.teilnehmer_id
            WHERE vt.veranstaltung_id = :vid $q_cond
        ");
        $count_params = [':vid' => $vid];
        if ($q) $count_params[':q'] = "%$q%";
        $count_stmt->execute($count_params);
        $total  = (int)$count_stmt->fetchColumn();
        $pages  = max(1, (int)ceil($total / $per));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $per;

        $pstmt = db()->prepare("
            SELECT t.id, t.name, t.kategorie, vt.tischnummer,
                   (SELECT b.dateiname FROM teilnehmer_bild b WHERE b.teilnehmer_id = t.id ORDER BY b.id ASC LIMIT 1) AS first_image
            FROM veranstaltung_teilnahme vt
            JOIN teilnehmer t ON t.id = vt.teilnehmer_id
            WHERE vt.veranstaltung_id = :vid $q_cond
            ORDER BY t.name ASC LIMIT :lim OFFSET :off
        ");
        $pparams = [':vid' => $vid];
        if ($q) $pparams[':q'] = "%$q%";
        $pparams[':lim'] = $per;
        $pparams[':off'] = $offset;
        $pstmt->execute($pparams);
        $participants = $pstmt->fetchAll();
    }

    $has_map  = $event['lat'] !== null && $event['lng'] !== null;
    $has_addr = $event['strasse'] || $event['ort'];

    $muster_banner_url    = musterBannerUrl($event['muster'] ?? null);
    $muster_banner_srcset = musterBannerSrcset($event['muster'] ?? null);
    $muster_is_svg        = musterIsSvg($event['muster'] ?? null);
    $logo_url             = logoUrl($event['logo'] ?? null);

    $gmaps_query = urlencode(
        ($event['ort_name'] ? $event['ort_name'] . ', ' : '') .
        ($event['strasse']  ? $event['strasse']  . ', ' : '') .
        ($event['plz']      ? $event['plz']      . ' '  : '') .
        ($event['ort']      ?: '')
    );

    if ($event['erster_tag']) {
        $json_ld = json_encode([
            '@context' => 'https://schema.org',
            '@type'    => 'Event',
            'name'     => $event['name'],
            'startDate' => $event['erster_tag'],
            'endDate'   => $event['letzter_tag'] ?? $event['erster_tag'],
            'location'  => [
                '@type'   => 'Place',
                'name'    => $event['ort_name'] ?: ($event['ort'] ?? ''),
                'address' => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => $event['strasse'] ?? '',
                    'postalCode'      => $event['plz'] ?? '',
                    'addressLocality' => $event['ort'] ?? '',
                    'addressCountry'  => 'DE',
                ],
            ],
            'url'       => SITE_URL,
            'organizer' => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

echo twig()->render('index.twig', [
    'page_title'           => $event ? $event['name'] : '',
    'nav_active'           => 'home',
    'meta_description'     => $event ? (strip_tags($event['beschreibung'] ?? '') ?: null) : null,
    'event'                => $event,
    'vid'                  => $vid,
    'in_bewerbung'         => $in_bewerbung,
    'days'                 => $days,
    'program_by_day'       => $program_by_day,
    'preview_by_day'       => $preview_by_day,
    'date_today'           => $today,
    'date_tomorrow'        => date('Y-m-d', strtotime('+1 day')),
    'total_prog'           => $total_prog,
    'participants'         => $participants,
    'total'                => $total,
    'pages'                => $pages,
    'page'                 => $page,
    'q'                    => $q,
    'has_map'              => $has_map,
    'has_addr'             => $has_addr,
    'muster_banner_url'    => $muster_banner_url,
    'muster_banner_srcset' => $muster_banner_srcset,
    'muster_is_svg'        => $muster_is_svg,
    'logo_url'             => $logo_url,
    'gmaps_query'          => $gmaps_query,
    'json_ld'              => $json_ld,
    'past_events_count'    => $past_events_count,
]);
