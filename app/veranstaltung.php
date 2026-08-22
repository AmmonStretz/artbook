<?php
require_once __DIR__ . '/src/bootstrap.php';

$vid = (int)($_GET['id'] ?? 0);
if (!$vid) { header('Location: /'); exit; }

$meta     = db()->query("SELECT bewerbung_veranstaltung_id, bewerbung_deadline FROM meta WHERE id = 1")->fetch() ?: [];
$bew_vid  = (int)($meta['bewerbung_veranstaltung_id'] ?? 0) ?: null;
$deadline = $meta['bewerbung_deadline'] ?? null;
$in_bewerbung = $bew_vid === $vid && $deadline && date('Y-m-d') <= $deadline;

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

$days = db()->prepare("SELECT datum, startzeit, endzeit FROM veranstaltung_tag WHERE veranstaltung_id = ? ORDER BY datum");
$days->execute([$vid]);
$days = $days->fetchAll();

$program_by_day = [];
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
    foreach ($prog_stmt->fetchAll() as $pr) {
        $program_by_day[$pr['datum']][] = $pr;
    }
}

// Pick up to 3 upcoming program entries for preview
$today_v    = date('Y-m-d');
$now_time_v = date('H:i:s');
$preview3   = [];
foreach ($program_by_day as $datum => $entries) {
    foreach ($entries as $e) {
        if ($datum > $today_v || ($datum === $today_v && $e['uhrzeit'] > $now_time_v)) {
            $preview3[] = ['datum' => $datum] + $e;
            if (count($preview3) >= 3) break 2;
        }
    }
}
if (!$preview3) {
    foreach ($program_by_day as $datum => $entries) {
        foreach ($entries as $e) {
            $preview3[] = ['datum' => $datum] + $e;
            if (count($preview3) >= 3) break 2;
        }
    }
}
$total_prog = array_sum(array_map('count', $program_by_day));

$total        = 0;
$pages        = 1;
$page         = 1;
$participants = [];
$q            = trim($_GET['q'] ?? '');

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
        SELECT t.id, t.name, t.typ, t.gruppe_typ, vt.tischnummer,
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

$titelbild_url = null;
if ($event['titelbild']) {
    $stem = pathinfo($event['titelbild'], PATHINFO_FILENAME);
    $path_1024 = IMG_UPLOAD_DIR . $stem . '_1024.jpg';
    $titelbild_url = file_exists($path_1024)
        ? IMG_UPLOAD_URL . $stem . '_1024.jpg'
        : IMG_UPLOAD_URL . $event['titelbild'];
}

$gmaps_query = urlencode(
    ($event['ort_name'] ? $event['ort_name'] . ', ' : '') .
    ($event['strasse']  ? $event['strasse']  . ', ' : '') .
    ($event['plz']      ? $event['plz']      . ' '  : '') .
    ($event['ort']      ?: '')
);

echo twig()->render('veranstaltung.twig', [
    'page_title'     => $event['name'],
    'nav_active'     => 'veranstaltungen',
    'vid'            => $vid,
    'event'          => $event,
    'in_bewerbung'   => $in_bewerbung,
    'days'           => $days,
    'program_by_day' => $program_by_day,
    'preview3'       => $preview3,
    'total_prog'     => $total_prog,
    'participants'   => $participants,
    'total'          => $total,
    'pages'          => $pages,
    'page'           => $page,
    'q'              => $q,
    'has_map'        => $has_map,
    'has_addr'       => $has_addr,
    'titelbild_url'  => $titelbild_url,
    'gmaps_query'    => $gmaps_query,
]);
