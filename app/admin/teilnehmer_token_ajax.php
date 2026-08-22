<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$id = (int)($_REQUEST['id'] ?? 0);
if (!$id) { echo json_encode(['ok' => false, 'error' => 'missing id']); exit; }

$tn = db()->prepare("SELECT id, name FROM teilnehmer WHERE id = ? AND typ IN ('kuenstler','gruppe')");
$tn->execute([$id]);
if (!$tn->fetch()) { echo json_encode(['ok' => false, 'error' => 'Nicht gefunden.']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $row = db()->prepare("SELECT token, created_at, used_at FROM teilnehmer_edit_token WHERE teilnehmer_id = ?");
    $row->execute([$id]);
    $t = $row->fetch();
    echo json_encode([
        'ok'         => true,
        'has_token'  => (bool) $t,
        'used'       => $t ? ($t['used_at'] !== null) : false,
        'created_at' => $t['created_at'] ?? null,
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'revoke') {
    db()->prepare("DELETE FROM teilnehmer_edit_token WHERE teilnehmer_id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'generate') {
    $token = bin2hex(random_bytes(48));
    db()->prepare("
        INSERT INTO teilnehmer_edit_token (teilnehmer_id, token)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = NOW(), used_at = NULL
    ")->execute([$id, $token]);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/bearbeiten/?token=' . $token;
    echo json_encode(['ok' => true, 'url' => $url]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid action']);
