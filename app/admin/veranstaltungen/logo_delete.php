<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$vid = (int)($_POST['veranstaltung_id'] ?? 0);
if (!$vid) { echo json_encode(['error' => 'missing']); exit; }

$stmt = db()->prepare("SELECT logo FROM veranstaltung WHERE id = ?");
$stmt->execute([$vid]);
$row = $stmt->fetch();
if (!$row) { echo json_encode(['error' => 'not found']); exit; }

if ($row['logo']) {
    $f = IMG_LOGO_UPLOAD_DIR . $row['logo'];
    if (file_exists($f)) unlink($f);
    db()->prepare("UPDATE veranstaltung SET logo = NULL WHERE id = ?")->execute([$vid]);
}

echo json_encode(['ok' => true]);
