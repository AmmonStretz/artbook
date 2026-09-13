<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$name       = trim($_POST['name'] ?? '');
$kategorie = trim($_POST['kategorie'] ?? '') ?: null;
$vid        = (int)($_POST['veranstaltung_id'] ?? 0);
$add        = ($_POST['add_to_event'] ?? '0') === '1';
$nr         = trim($_POST['tischnummer'] ?? '') ?: null;

if (!$name) {
    echo json_encode(['ok' => false, 'error' => 'Name ist ein Pflichtfeld.']);
    exit;
}
if ($kategorie && !array_key_exists($kategorie, TEILNEHMER_KATEGORIEN)) {
    echo json_encode(['ok' => false, 'error' => 'Ungültiger Gruppentyp.']);
    exit;
}

$db = db();

$db->prepare("INSERT INTO teilnehmer (name, kategorie) VALUES (?, ?)")
   ->execute([$name, $kategorie]);
$new_id = (int)$db->lastInsertId();

if ($add && $vid) {
    $db->prepare("INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id, tischnummer) VALUES (?,?,?)")
       ->execute([$vid, $new_id, $nr]);
}

echo json_encode(['ok' => true, 'id' => $new_id, 'name' => $name, 'kategorie' => $kategorie]);
