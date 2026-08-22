<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$vid = (int)($_POST['veranstaltung_id'] ?? 0);
if (!$vid) { echo json_encode(['error' => 'Fehlende ID']); exit; }

$stmt = db()->prepare("SELECT id, titelbild FROM veranstaltung WHERE id = ?");
$stmt->execute([$vid]);
$row = $stmt->fetch();
if (!$row) { echo json_encode(['error' => 'Ungültige ID']); exit; }

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

[$origW, $origH] = getimagesize($file['tmp_name']);
if ($origW < IMG_TITELBILD_MIN_WIDTH || $origH !== IMG_TITELBILD_HEIGHT) {
    echo json_encode(['error' =>
        'Bild muss mindestens ' . IMG_TITELBILD_MIN_WIDTH . ' px breit und genau ' . IMG_TITELBILD_HEIGHT . ' px hoch sein ' .
        "(dieses: {$origW}×{$origH} px)"
    ]); exit;
}

$src = match($mime) {
    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
    'image/png'  => imagecreatefrompng($file['tmp_name']),
    'image/webp' => imagecreatefromwebp($file['tmp_name']),
};

if (!is_dir(IMG_UPLOAD_DIR)) mkdir(IMG_UPLOAD_DIR, 0755, true);

// Delete old titelbild files
if ($row['titelbild']) {
    $oldStem = pathinfo($row['titelbild'], PATHINFO_FILENAME);
    foreach (IMG_TITELBILD_WIDTHS as $i => $w) {
        $suf = $i === 0 ? '.jpg' : "_{$w}.jpg";
        $f   = IMG_UPLOAD_DIR . $oldStem . $suf;
        if (file_exists($f)) unlink($f);
    }
}

$stem = 'IMG_' . floor(microtime(true) * 1000);
$H    = IMG_TITELBILD_HEIGHT; // 540

// Full: center-crop to 1920×540
$img1920 = imagecreatetruecolor(IMG_TITELBILD_MIN_WIDTH, $H);
$srcX = (int)(($origW - IMG_TITELBILD_MIN_WIDTH) / 2);
imagecopyresampled($img1920, $src, 0, 0, $srcX, 0, IMG_TITELBILD_MIN_WIDTH, $H, IMG_TITELBILD_MIN_WIDTH, $H);
imagejpeg($img1920, IMG_UPLOAD_DIR . $stem . '.jpg', IMG_QUALITY);
imagedestroy($img1920);

// _1024: center-crop to 1024×540
$img1024 = imagecreatetruecolor(1024, $H);
$srcX = (int)(($origW - 1024) / 2);
imagecopyresampled($img1024, $src, 0, 0, $srcX, 0, 1024, $H, 1024, $H);
imagejpeg($img1024, IMG_UPLOAD_DIR . $stem . '_1024.jpg', IMG_QUALITY);

// _768: scale _1024 down to 768×405
$img768 = imagecreatetruecolor(768, 405);
imagecopyresampled($img768, $img1024, 0, 0, 0, 0, 768, 405, 1024, $H);
imagejpeg($img768, IMG_UPLOAD_DIR . $stem . '_768.jpg', IMG_QUALITY);
imagedestroy($img768);
imagedestroy($img1024);
imagedestroy($src);

$baseFilename = $stem . '.jpg';
db()->prepare("UPDATE veranstaltung SET titelbild = ? WHERE id = ?")->execute([$baseFilename, $vid]);

echo json_encode([
    'ok'       => true,
    'url'      => IMG_UPLOAD_URL . $stem . '_1024.jpg',
    'url_full' => IMG_UPLOAD_URL . $baseFilename,
]);
