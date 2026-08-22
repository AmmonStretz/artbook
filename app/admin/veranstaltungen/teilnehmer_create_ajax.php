<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$typ        = $_POST['typ']        ?? '';
$name       = trim($_POST['name'] ?? '');
$gruppe_typ = trim($_POST['gruppe_typ'] ?? '');
$vid        = (int)($_POST['veranstaltung_id'] ?? 0);
$add        = ($_POST['add_to_event'] ?? '0') === '1';
$nr         = trim($_POST['tischnummer'] ?? '') ?: null;

if (!in_array($typ, ['kuenstler', 'gruppe'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Ungültiger Typ.']);
    exit;
}
if (!$name) {
    echo json_encode(['ok' => false, 'error' => 'Name ist ein Pflichtfeld.']);
    exit;
}
if ($typ === 'gruppe') {
    if (!$gruppe_typ) {
        echo json_encode(['ok' => false, 'error' => 'Gruppentyp ist ein Pflichtfeld.']);
        exit;
    }
    if (!array_key_exists($gruppe_typ, GRUPPE_TYPEN)) {
        echo json_encode(['ok' => false, 'error' => 'Ungültiger Gruppentyp.']);
        exit;
    }
}

$db = db();

if ($typ === 'kuenstler') {
    $db->prepare("INSERT INTO teilnehmer (typ, name) VALUES ('kuenstler', ?)")
       ->execute([$name]);
} else {
    $db->prepare("INSERT INTO teilnehmer (typ, name, gruppe_typ) VALUES ('gruppe', ?, ?)")
       ->execute([$name, $gruppe_typ]);
}
$new_id = (int)$db->lastInsertId();

if ($add && $vid) {
    $db->prepare("INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id, tischnummer) VALUES (?,?,?)")
       ->execute([$vid, $new_id, $nr]);
}

echo json_encode(['ok' => true, 'id' => $new_id, 'name' => $name, 'typ' => $typ]);
