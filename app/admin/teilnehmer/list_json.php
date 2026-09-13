<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$stmt = db()->query("SELECT id, name, kategorie FROM teilnehmer ORDER BY name");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
