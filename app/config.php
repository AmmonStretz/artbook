<?php
define('AUTH_USER', 'admin');
define('AUTH_PASS',  'artbook2024');
define('APP_NAME',   'artbook.berlin Admin');
define('SITE_NAME',  'artbook.berlin');
define('SITE_URL',   'https://artbookberlin.de');
define('SITE_DESCRIPTION', 'Die Buchmesse für Künstlerbücher, limitierte Editionen und unabhängige Publikationen. 20.–22. November 2026, Kunstquartier Bethanien, Berlin-Kreuzberg.');

// Bildupload (Künstler / Gruppen)
define('IMG_MIN_WIDTH',   768);
define('IMG_MAX_COUNT',   5);
define('IMG_BILD_WIDTHS', [1024, 768]);
define('IMG_MAX_WIDTH',   1024);

// Meta / Einstellungen – Dateiuploads (Bewerbungsformular etc.)
define('META_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/meta/');
define('META_UPLOAD_URL', '/uploads/meta/');

// Titelbild (Veranstaltungen) – wird in 3 Größen gespeichert
// Erwartet genau 1920×540 px; breiter ist ok (wird mittig beschnitten)
define('IMG_TITELBILD_MIN_WIDTH', 1920);
define('IMG_TITELBILD_HEIGHT',    540);
define('IMG_TITELBILD_WIDTHS',    [1920, 1024, 768]);
define('IMG_QUALITY',    88);
define('IMG_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/teilnehmer/');
define('IMG_UPLOAD_URL', '/uploads/teilnehmer/');

// Pagination – Einträge pro Seite
define('PER_PAGE_ADMIN',    10);
define('PER_PAGE_IMAGES',   24);
define('PER_PAGE_ARTISTS',  24);
define('PER_PAGE_EVENT_TN',  24);

// Gruppen-Typen – hier neue Typen ergänzen
const GRUPPE_TYPEN = [
    'verlag'  => 'Verlag',
    'edition' => 'Edition',
];
