<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$bid = (int)($_POST['bild_id'] ?? 0);
if (!$bid) { echo json_encode(['ok' => false, 'error' => 'missing']); exit; }

db()->prepare("UPDATE teilnehmer_bild SET teilnehmer_id = NULL WHERE id = ?")
    ->execute([$bid]);

echo json_encode(['ok' => true]);
