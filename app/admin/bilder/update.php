<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$bid        = (int)($_POST['bild_id']      ?? 0);
$titel      = trim($_POST['titel']        ?? '') ?: null;
$titel_en   = trim($_POST['titel_en']     ?? '') ?: null;
$alt_text   = trim($_POST['alt_text']     ?? '') ?: null;
$alt_text_en = trim($_POST['alt_text_en'] ?? '') ?: null;
$startdatum = trim($_POST['startdatum']   ?? '') ?: null;

if (!$bid) { echo json_encode(['error' => 'missing']); exit; }

db()->prepare("UPDATE teilnehmer_bild SET titel = ?, titel_en = ?, alt_text = ?, alt_text_en = ?, startdatum = ? WHERE id = ?")
    ->execute([$titel, $titel_en, $alt_text, $alt_text_en, $startdatum, $bid]);

echo json_encode(['ok' => true]);
