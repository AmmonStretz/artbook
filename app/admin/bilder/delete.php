<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$bid = (int)($_POST['bild_id'] ?? 0);
if (!$bid) { echo json_encode(['error' => 'missing']); exit; }

$stmt = db()->prepare("SELECT dateiname FROM teilnehmer_bild WHERE id = ?");
$stmt->execute([$bid]);
$bild = $stmt->fetch();

if (!$bild) { echo json_encode(['error' => 'not found']); exit; }

$stem = pathinfo($bild['dateiname'], PATHINFO_FILENAME);
foreach (IMG_BILD_WIDTHS as $w) {
    $f = IMG_UPLOAD_DIR . $stem . '_' . $w . '.jpg';
    if (file_exists($f)) unlink($f);
}

db()->prepare("DELETE FROM teilnehmer_bild WHERE id = ?")->execute([$bid]);

echo json_encode(['ok' => true]);
