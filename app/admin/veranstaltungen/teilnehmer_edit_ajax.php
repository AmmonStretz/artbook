<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false]); exit; }

    $stmt = db()->prepare("SELECT id, name, beschreibung, beschreibung_en, link, kategorie FROM teilnehmer WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { echo json_encode(['ok' => false, 'error' => 'Nicht gefunden.']); exit; }

    echo json_encode(['ok' => true, 'teilnehmer' => $row]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = (int)($_POST['id'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $beschr     = $_POST['beschreibung']    ?? '';
    $beschr_en  = $_POST['beschreibung_en'] ?? '';
    $link       = trim($_POST['link'] ?? '');
    $kategorie = trim($_POST['kategorie'] ?? '');

    if (!$id || !$name) {
        echo json_encode(['ok' => false, 'error' => 'Name ist ein Pflichtfeld.']);
        exit;
    }

    if ($kategorie && !array_key_exists($kategorie, TEILNEHMER_KATEGORIEN)) {
        echo json_encode(['ok' => false, 'error' => 'Ungültiger Gruppentyp.']);
        exit;
    }

    $exists = db()->prepare("SELECT id FROM teilnehmer WHERE id = ?");
    $exists->execute([$id]);
    if (!$exists->fetch()) { echo json_encode(['ok' => false, 'error' => 'Nicht gefunden.']); exit; }

    db()->prepare("UPDATE teilnehmer SET name=?, beschreibung=?, beschreibung_en=?, link=?, kategorie=? WHERE id=?")
       ->execute([$name, $beschr ?: null, $beschr_en ?: null, $link ?: null, $kategorie ?: null, $id]);

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false]);
