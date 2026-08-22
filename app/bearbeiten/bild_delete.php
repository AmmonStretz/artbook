<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$token = trim($_POST['token'] ?? '');
$bid   = (int)($_POST['bild_id'] ?? 0);

if (!$token || !$bid) { echo json_encode(['error' => 'missing params']); exit; }

$stmt = db()->prepare("
    SELECT tet.teilnehmer_id, tet.used_at
    FROM teilnehmer_edit_token tet
    WHERE tet.token = ?
");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || $row['used_at'] !== null) {
    echo json_encode(['error' => 'Ungültiger Link.']);
    exit;
}

// Ensure the image belongs to this token's artist
$bStmt = db()->prepare("SELECT dateiname FROM teilnehmer_bild WHERE id = ? AND teilnehmer_id = ?");
$bStmt->execute([$bid, (int)$row['teilnehmer_id']]);
$bild = $bStmt->fetch(PDO::FETCH_ASSOC);

if (!$bild) { echo json_encode(['error' => 'Bild nicht gefunden.']); exit; }

$stem = pathinfo($bild['dateiname'], PATHINFO_FILENAME);
foreach (IMG_BILD_WIDTHS as $w) {
    $f = IMG_UPLOAD_DIR . $stem . '_' . $w . '.jpg';
    if (file_exists($f)) unlink($f);
}

db()->prepare("DELETE FROM teilnehmer_bild WHERE id = ?")->execute([$bid]);

echo json_encode(['ok' => true]);
