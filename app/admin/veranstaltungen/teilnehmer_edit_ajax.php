<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false]); exit; }

    $stmt = db()->prepare("SELECT id, typ, name, beschreibung, beschreibung_en, link, gruppe_typ FROM teilnehmer WHERE id = ?");
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
    $gruppe_typ = trim($_POST['gruppe_typ'] ?? '');

    if (!$id || !$name) {
        echo json_encode(['ok' => false, 'error' => 'Name ist ein Pflichtfeld.']);
        exit;
    }

    $stmt = db()->prepare("SELECT typ FROM teilnehmer WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { echo json_encode(['ok' => false, 'error' => 'Nicht gefunden.']); exit; }

    if ($row['typ'] === 'gruppe') {
        if (!$gruppe_typ || !array_key_exists($gruppe_typ, GRUPPE_TYPEN)) {
            echo json_encode(['ok' => false, 'error' => 'Gruppentyp ist ein Pflichtfeld.']);
            exit;
        }
        db()->prepare("UPDATE teilnehmer SET name=?, beschreibung=?, beschreibung_en=?, link=?, gruppe_typ=? WHERE id=?")
           ->execute([$name, $beschr ?: null, $beschr_en ?: null, $link ?: null, $gruppe_typ, $id]);
    } else {
        db()->prepare("UPDATE teilnehmer SET name=?, beschreibung=?, beschreibung_en=?, link=? WHERE id=?")
           ->execute([$name, $beschr ?: null, $beschr_en ?: null, $link ?: null, $id]);
    }

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false]);
