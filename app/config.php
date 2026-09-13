<?php
define('AUTH_USER', 'admin');
define('AUTH_PASS',  'artbook2024');
define('APP_NAME',   'artbook.berlin Admin');
define('SITE_NAME',  'artbook.berlin');
define('SITE_URL',   'https://artbookberlin.de');
define('SITE_DESCRIPTION', 'Die Buchmesse für Künstlerbücher, limitierte Editionen und unabhängige Publikationen. 20.–22. November 2026, Kunstquartier Bethanien, Berlin-Kreuzberg.');

// Bildupload (Künstler / Gruppen)
define('IMG_MIN_WIDTH',   768);
define('IMG_BILD_WIDTHS', [1024, 768]);
define('IMG_MAX_WIDTH',   1024);

// Meta / Einstellungen – Dateiuploads (Bewerbungsformular etc.)
define('META_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/meta/');
define('META_UPLOAD_URL', '/uploads/meta/');

define('IMG_QUALITY',    88);
define('IMG_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/teilnehmer/');
define('IMG_UPLOAD_URL', '/uploads/teilnehmer/');

// Muster (Veranstaltungen) – Hintergrundbild in 8 WebP-Varianten oder als SVG
// Raster-Upload muss mindestens IMG_MUSTER_MIN_WIDTH px breit sein
define('IMG_MUSTER_MIN_WIDTH',  2560);
define('IMG_MUSTER_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/veranstaltungen/muster/');
define('IMG_MUSTER_UPLOAD_URL', '/uploads/veranstaltungen/muster/');
define('IMG_MUSTER_FULLPAGE_SIZES', [
    'xl' => [2560, 1440],
    'lg' => [1920, 1080],
    'md' => [1280,  720],
    'sm' => [ 640,  360],
]);
define('IMG_MUSTER_BANNER_SIZES', [
    'xl' => [2560, 800],
    'lg' => [1920, 800],
    'md' => [1280, 400],
    'sm' => [ 640, 400],
]);

// Logo (Veranstaltungen) – wird unverändert gespeichert (SVG, PNG, JPG)
define('IMG_LOGO_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/veranstaltungen/logo/');
define('IMG_LOGO_UPLOAD_URL', '/uploads/veranstaltungen/logo/');

// Pagination – Einträge pro Seite
define('PER_PAGE_ADMIN',    10);
define('PER_PAGE_IMAGES',   24);
define('PER_PAGE_ARTISTS',  24);
define('PER_PAGE_EVENT_TN',  24);

// Teilnehmer-Kategorien – hier neue Kategorien ergänzen
const TEILNEHMER_KATEGORIEN = [
    'kuenstler' => 'Künstler',
    'verlag'    => 'Verlag',
    'edition'   => 'Edition',
    'archiv'    => 'Archiv',
    'sonstiges' => 'Sonstiges',
];
