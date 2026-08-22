<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$tid = (int)($_POST['teilnehmer_id'] ?? 0);
if (!$tid) { echo json_encode(['error' => 'Fehlende ID']); exit; }

$stmt = db()->prepare("SELECT id FROM teilnehmer WHERE id = ? AND typ IN ('kuenstler','gruppe')");
$stmt->execute([$tid]);
if (!$stmt->fetch()) { echo json_encode(['error' => 'Ungültige ID']); exit; }

$cntStmt = db()->prepare("SELECT COUNT(*) FROM teilnehmer_bild WHERE teilnehmer_id = ?");
$cntStmt->execute([$tid]);
if ((int)$cntStmt->fetchColumn() >= IMG_MAX_COUNT) {
    echo json_encode(['error' => 'Maximal ' . IMG_MAX_COUNT . ' Bilder pro Teilnehmer erlaubt.']);
    exit;
}

if (empty($_FILES['bild']) || $_FILES['bild']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload fehlgeschlagen (Code ' . ($_FILES['bild']['error'] ?? '?') . ')']);
    exit;
}

$file    = $_FILES['bild'];
$mime    = mime_content_type($file['tmp_name']);
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed)) {
    echo json_encode(['error' => 'Nur JPEG, PNG und WebP erlaubt']); exit;
}

[$width, $height] = getimagesize($file['tmp_name']);
if ($width < IMG_MIN_WIDTH) {
    echo json_encode(['error' => "Bild zu schmal – mindestens " . IMG_MIN_WIDTH . " px Breite erforderlich (dieses: {$width} px)"]); exit;
}

$src = match($mime) {
    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
    'image/png'  => imagecreatefrompng($file['tmp_name']),
    'image/webp' => imagecreatefromwebp($file['tmp_name']),
};

if (!is_dir(IMG_UPLOAD_DIR)) mkdir(IMG_UPLOAD_DIR, 0755, true);

$ms       = floor(microtime(true) * 1000);
$filename = 'IMG_' . $ms . '.jpg';
$size     = 0;

foreach (IMG_BILD_WIDTHS as $w) {
    $path = IMG_UPLOAD_DIR . 'IMG_' . $ms . '_' . $w . '.jpg';
    if ($width > $w) {
        $newH = (int) round($height * ($w / $width));
        $dst  = imagecreatetruecolor($w, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $newH, $width, $height);
        imagejpeg($dst, $path, IMG_QUALITY);
        imagedestroy($dst);
    } else {
        imagejpeg($src, $path, IMG_QUALITY);
    }
    if ($w === 1024) $size = filesize($path);
}
imagedestroy($src);

db()->prepare("
    INSERT INTO teilnehmer_bild (teilnehmer_id, dateiname, dateiname_original, mime_type, groesse_bytes)
    VALUES (?, ?, ?, 'image/jpeg', ?)
")->execute([$tid, $filename, $file['name'], $size]);

echo json_encode([
    'ok'     => true,
    'id'     => (int) db()->lastInsertId(),
    'url'    => IMG_UPLOAD_URL . 'IMG_' . $ms . '_1024.jpg',
    'width'  => $width,
    'height' => $height,
]);
