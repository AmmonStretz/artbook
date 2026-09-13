<?php
require_once __DIR__ . '/../src/bootstrap.php';

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

// Check whether the dimension columns exist (Migration 13 may not be applied yet)
$hasDims = false;
try {
    db()->query("SELECT breite FROM teilnehmer_bild LIMIT 0");
    $hasDims = true;
} catch (PDOException $e) {}

$dimCols = $hasDims ? ', b.breite, b.hoehe' : '';

// Fetch all participants with all their images; pick one randomly per participant in PHP
$stmt = db()->prepare("
    SELECT t.id, t.name,
           b.dateiname {$dimCols}
    FROM veranstaltung_teilnahme vt
    JOIN teilnehmer t ON t.id = vt.teilnehmer_id
    LEFT JOIN teilnehmer_bild b ON b.teilnehmer_id = t.id
    WHERE vt.veranstaltung_id = ?
    ORDER BY t.id
");
$stmt->execute([$vid]);
$rows = $stmt->fetchAll();

// Group images by participant
$participants = [];
foreach ($rows as $row) {
    $tid = $row['id'];
    if (!isset($participants[$tid])) {
        $participants[$tid] = ['id' => $tid, 'name' => $row['name'], 'images' => []];
    }
    if ($row['dateiname']) {
        $participants[$tid]['images'][] = [
            'dateiname' => $row['dateiname'],
            'breite'    => (int)($row['breite'] ?? 0),
            'hoehe'     => (int)($row['hoehe']  ?? 0),
        ];
    }
}

// Build image list: one random image per participant
$images = [];
foreach ($participants as $p) {
    if (empty($p['images'])) continue;
    $img  = $p['images'][array_rand($p['images'])];
    $stem = pathinfo($img['dateiname'], PATHINFO_FILENAME);

    $path_768 = IMG_UPLOAD_DIR . $stem . '_768.jpg';
    $has_768  = file_exists($path_768);
    $url = $has_768
        ? IMG_UPLOAD_URL . $stem . '_768.jpg'
        : IMG_UPLOAD_URL . $img['dateiname'];

    // Prefer stored dimensions; fall back to reading the actual file
    if ($img['breite'] && $img['hoehe']) {
        $orig_w = $img['breite'];
        $orig_h = $img['hoehe'];
        $disp_w = min(768, $orig_w);
        $disp_h = (int)round($orig_h / $orig_w * $disp_w);
    } elseif ($has_768 && ($size = @getimagesize($path_768)) !== false) {
        $disp_w = $size[0];
        $disp_h = $size[1];
    } else {
        $disp_w = 768;
        $disp_h = 576; // 4:3 last-resort fallback
    }
    if ($disp_h < 1) $disp_h = (int)round($disp_w * 3 / 4);

    $images[] = [
        'id'   => $p['id'],
        'name' => $p['name'],
        'url'  => $url,
        'w'    => $disp_w,
        'h'    => $disp_h,
    ];
}

shuffle($images);

$center_url = musterBannerUrl($event['muster'] ?? null, 'sm');
$center_w   = 640;
$center_h   = 400;

echo twig()->render('navigation.twig', [
    'page_title' => $event['name'],
    'nav_active' => 'veranstaltungen',
    'vid'        => $vid,
    'event'      => $event,
    'images'     => $images,
    'center_url' => $center_url,
    'center_w'   => $center_w,
    'center_h'   => $center_h,
]);
