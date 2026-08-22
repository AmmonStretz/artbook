<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (empty($_FILES['datei']) || $_FILES['datei']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload fehlgeschlagen (Code ' . ($_FILES['datei']['error'] ?? '?') . ')']);
    exit;
}

$file     = $_FILES['datei'];
$origName = $file['name'];
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowed  = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['error' => 'Nur PDF, JPEG, PNG und WebP erlaubt']); exit;
}

if (!is_dir(META_UPLOAD_DIR)) mkdir(META_UPLOAD_DIR, 0755, true);

$meta = db()->query("SELECT bewerbung_formular FROM meta WHERE id = 1")->fetch();
if ($meta && $meta['bewerbung_formular']) {
    $old = META_UPLOAD_DIR . $meta['bewerbung_formular'];
    if (file_exists($old)) unlink($old);
}

$filename = 'FORM_' . floor(microtime(true) * 1000) . '.' . $ext;
move_uploaded_file($file['tmp_name'], META_UPLOAD_DIR . $filename);

db()->prepare("UPDATE meta SET bewerbung_formular = ?, bewerbung_formular_name = ? WHERE id = 1")
    ->execute([$filename, $origName]);

echo json_encode(['ok' => true, 'url' => META_UPLOAD_URL . $filename, 'name' => htmlspecialchars($origName)]);
