<?php
require_once __DIR__ . '/src/bootstrap.php';

$meta = db()->query("SELECT impressum FROM meta WHERE id = 1")->fetch() ?: [];

echo twig()->render('impressum.twig', [
    'page_title' => 'Impressum',
    'nav_active' => '',
    'meta'       => $meta,
]);
