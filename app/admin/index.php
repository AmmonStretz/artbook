<?php
require_once __DIR__ . '/bootstrap.php';

$count_veranstaltungen = (int) db()->query('SELECT COUNT(*) FROM veranstaltung')->fetchColumn();
$count_kuenstler       = (int) db()->query("SELECT COUNT(*) FROM teilnehmer WHERE typ='kuenstler'")->fetchColumn();
$count_gruppen         = (int) db()->query("SELECT COUNT(*) FROM teilnehmer WHERE typ='gruppe'")->fetchColumn();
$count_bilder          = (int) db()->query('SELECT COUNT(*) FROM teilnehmer_bild')->fetchColumn();

echo adminTwig()->render('dashboard.twig', [
    'page_title'            => 'Dashboard',
    'nav_active'            => 'dashboard',
    'count_veranstaltungen' => $count_veranstaltungen,
    'count_kuenstler'       => $count_kuenstler,
    'count_gruppen'         => $count_gruppen,
    'count_bilder'          => $count_bilder,
]);
