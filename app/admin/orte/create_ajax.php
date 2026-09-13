<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$strasse = trim($_POST['strasse'] ?? '');
$plz     = trim($_POST['plz']     ?? '');
$ort     = trim($_POST['ort']     ?? '');
$land    = trim($_POST['land']    ?? 'Deutschland') ?: 'Deutschland';
$ort_url = trim($_POST['ort_url'] ?? '');

if (!$name) {
    echo json_encode(['ok' => false, 'error' => 'Name ist ein Pflichtfeld.']);
    exit;
}

db()->prepare("
    INSERT INTO veranstaltungsort (name, strasse, plz, ort, land, ort_url)
    VALUES (?,?,?,?,?,?)
")->execute([$name, $strasse ?: null, $plz ?: null, $ort ?: null, $land, $ort_url ?: null]);

$id = (int) db()->lastInsertId();

echo json_encode([
    'ok'  => true,
    'ort' => [
        'id'      => $id,
        'name'    => $name,
        'strasse' => $strasse,
        'plz'     => $plz,
        'ort'     => $ort,
        'land'    => $land,
        'ort_url' => $ort_url,
        'lat'     => null,
        'lng'     => null,
    ],
]);
