<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$vid = (int)($_POST['veranstaltung_id'] ?? 0);
if (!$vid) { echo json_encode(['error' => 'Fehlende ID']); exit; }

$stmt = db()->prepare("SELECT id, logo FROM veranstaltung WHERE id = ?");
$stmt->execute([$vid]);
$row = $stmt->fetch();
if (!$row) { echo json_encode(['error' => 'Ungültige ID']); exit; }

if (empty($_FILES['bild']) || $_FILES['bild']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload fehlgeschlagen (Code ' . ($_FILES['bild']['error'] ?? '?') . ')']);
    exit;
}

$file   = $_FILES['bild'];
$mime   = mime_content_type($file['tmp_name']);
$extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/svg+xml' => 'svg'];
if (!isset($extMap[$mime])) {
    echo json_encode(['error' => 'Nur JPEG, PNG und SVG erlaubt']); exit;
}

if (!is_dir(IMG_LOGO_UPLOAD_DIR)) mkdir(IMG_LOGO_UPLOAD_DIR, 0755, true);

if ($row['logo']) {
    $old = IMG_LOGO_UPLOAD_DIR . $row['logo'];
    if (file_exists($old)) unlink($old);
}

$filename = 'IMG_' . floor(microtime(true) * 1000) . '.' . $extMap[$mime];
move_uploaded_file($file['tmp_name'], IMG_LOGO_UPLOAD_DIR . $filename);

db()->prepare("UPDATE veranstaltung SET logo = ? WHERE id = ?")->execute([$filename, $vid]);

echo json_encode(['ok' => true, 'url' => IMG_LOGO_UPLOAD_URL . $filename]);
