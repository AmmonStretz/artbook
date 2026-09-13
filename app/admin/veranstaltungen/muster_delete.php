<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$vid = (int)($_POST['veranstaltung_id'] ?? 0);
if (!$vid) { echo json_encode(['error' => 'missing']); exit; }

$stmt = db()->prepare("SELECT muster FROM veranstaltung WHERE id = ?");
$stmt->execute([$vid]);
$row = $stmt->fetch();
if (!$row) { echo json_encode(['error' => 'not found']); exit; }

if ($row['muster']) {
    $stem = pathinfo($row['muster'], PATHINFO_FILENAME);
    $ext  = strtolower(pathinfo($row['muster'], PATHINFO_EXTENSION));

    if ($ext === 'svg') {
        $f = IMG_MUSTER_UPLOAD_DIR . $row['muster'];
        if (file_exists($f)) unlink($f);
    } else {
        foreach (array_keys(IMG_MUSTER_FULLPAGE_SIZES) as $key) {
            $f = IMG_MUSTER_UPLOAD_DIR . $stem . '_fp_' . $key . '.webp';
            if (file_exists($f)) unlink($f);
        }
        foreach (array_keys(IMG_MUSTER_BANNER_SIZES) as $key) {
            $f = IMG_MUSTER_UPLOAD_DIR . $stem . '_bn_' . $key . '.webp';
            if (file_exists($f)) unlink($f);
        }
    }

    db()->prepare("UPDATE veranstaltung SET muster = NULL WHERE id = ?")->execute([$vid]);
}

echo json_encode(['ok' => true]);
