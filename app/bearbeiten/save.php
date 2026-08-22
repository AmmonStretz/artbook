<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$token = trim($_POST['token'] ?? '');
if (!$token) { echo json_encode(['ok' => false, 'error' => 'missing token']); exit; }

$stmt = db()->prepare("
    SELECT tet.id, tet.teilnehmer_id, tet.used_at
    FROM teilnehmer_edit_token tet
    WHERE tet.token = ?
");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || $row['used_at'] !== null) {
    echo json_encode(['ok' => false, 'error' => 'Ungültiger oder bereits verwendeter Link.']);
    exit;
}

$name  = trim($_POST['name']  ?? '');
$link  = trim($_POST['link']  ?? '');
$beschr = $_POST['beschreibung'] ?? '';

if (!$name) {
    echo json_encode(['ok' => false, 'error' => 'Name ist ein Pflichtfeld.']);
    exit;
}

$db = db();
$db->prepare("UPDATE teilnehmer SET name = ?, beschreibung = ?, link = ? WHERE id = ?")
   ->execute([$name, $beschr ?: null, $link ?: null, (int)$row['teilnehmer_id']]);

$db->prepare("UPDATE teilnehmer_edit_token SET used_at = NOW() WHERE id = ?")
   ->execute([(int)$row['id']]);

echo json_encode(['ok' => true]);
