<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$meta = db()->query("SELECT bewerbung_formular FROM meta WHERE id = 1")->fetch();
if ($meta && $meta['bewerbung_formular']) {
    $f = META_UPLOAD_DIR . $meta['bewerbung_formular'];
    if (file_exists($f)) unlink($f);
    db()->prepare("UPDATE meta SET bewerbung_formular = NULL, bewerbung_formular_name = NULL WHERE id = 1")
        ->execute();
}

echo json_encode(['ok' => true]);
