<?php
/**
 * Einmalig ausführen: liest Maße aus den gespeicherten _1024.jpg-Varianten
 * und schreibt sie in teilnehmer_bild.breite / .hoehe.
 *
 * Ausführen im App-Container:
 *   docker compose exec app php /var/www/html/tools/backfill_dimensionen.php
 */
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$rows = db()->query("SELECT id, dateiname FROM teilnehmer_bild WHERE breite IS NULL")->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "Keine Einträge ohne Maße gefunden.\n";
    exit(0);
}

$updated = 0;
$missing = 0;

$upd = db()->prepare("UPDATE teilnehmer_bild SET breite = ?, hoehe = ? WHERE id = ?");

foreach ($rows as $row) {
    $base = preg_replace('/\.jpg$/i', '', $row['dateiname']);
    $path = IMG_UPLOAD_DIR . $base . '_1024.jpg';

    if (!file_exists($path)) {
        echo "FEHLT: {$path}\n";
        $missing++;
        continue;
    }

    [$w, $h] = getimagesize($path);
    $upd->execute([$w, $h, $row['id']]);
    $updated++;
}

echo "Fertig: {$updated} aktualisiert, {$missing} Dateien nicht gefunden.\n";
