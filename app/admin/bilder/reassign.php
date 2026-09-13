<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$bild_id   = (int)($_POST['bild_id']   ?? 0);
$target_id = (int)($_POST['target_id'] ?? 0);

if (!$bild_id || !$target_id) {
    echo json_encode(['ok' => false, 'error' => 'Fehlende Parameter']);
    exit;
}

$chk = db()->prepare("SELECT id FROM teilnehmer WHERE id = ?");
$chk->execute([$target_id]);
if (!$chk->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'Teilnehmer nicht gefunden']);
    exit;
}

db()->prepare("UPDATE teilnehmer_bild SET teilnehmer_id = ? WHERE id = ?")
    ->execute([$target_id, $bild_id]);

echo json_encode(['ok' => true]);
