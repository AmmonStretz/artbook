<?php
require_once __DIR__ . '/../bootstrap.php';

$per  = PER_PAGE_IMAGES;
$page = max(1, (int)($_GET['page'] ?? 1));

$total = (int) db()->query("
    SELECT
      (SELECT COUNT(*) FROM teilnehmer_bild) +
      (SELECT COUNT(*) FROM veranstaltung WHERE titelbild IS NOT NULL)
")->fetchColumn();

$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$stmt = db()->prepare("
    SELECT quelle, quelle_id, dateiname, titel, alt_text, startdatum, owner_name, owner_typ, owner_id
    FROM (
        SELECT 'teilnehmer'   AS quelle, b.id    AS quelle_id, b.dateiname,
               b.titel, b.alt_text, b.startdatum,
               t.name AS owner_name, t.typ  AS owner_typ, t.id AS owner_id
        FROM teilnehmer_bild b
        JOIN teilnehmer t ON t.id = b.teilnehmer_id
        UNION ALL
        SELECT 'veranstaltung' AS quelle, v.id   AS quelle_id, v.titelbild AS dateiname,
               NULL AS titel, NULL AS alt_text, NULL AS startdatum,
               v.name AS owner_name, 'veranstaltung' AS owner_typ, v.id AS owner_id
        FROM veranstaltung v
        WHERE v.titelbild IS NOT NULL
    ) AS alle
    ORDER BY dateiname DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$per, $off]);
$bilder = $stmt->fetchAll();

foreach ($bilder as &$b) {
    $qid = (int)$b['quelle_id'];
    if ($b['quelle'] === 'veranstaltung') {
        $stem = pathinfo($b['dateiname'], PATHINFO_FILENAME);
        $b['url']       = IMG_UPLOAD_URL . $stem . '_1024.jpg';
        $b['url_full']  = IMG_UPLOAD_URL . $b['dateiname'];
        $b['badge_cls'] = 'badge-future';
        $b['badge_label'] = 'Veranstaltung';
        $b['owner_url'] = '/admin/veranstaltungen/form.php?id=' . $qid;
    } elseif ($b['owner_typ'] === 'kuenstler') {
        $stem = pathinfo($b['dateiname'], PATHINFO_FILENAME);
        $b['url']       = IMG_UPLOAD_URL . $stem . '_768.jpg';
        $b['url_full']  = IMG_UPLOAD_URL . $stem . '_1024.jpg';
        $b['badge_cls'] = 'badge-kuenstler';
        $b['badge_label'] = 'Künstler';
        $b['owner_url'] = '/admin/kuenstler/form.php?id=' . (int)$b['owner_id'];
    } else {
        $stem = pathinfo($b['dateiname'], PATHINFO_FILENAME);
        $b['url']       = IMG_UPLOAD_URL . $stem . '_768.jpg';
        $b['url_full']  = IMG_UPLOAD_URL . $stem . '_1024.jpg';
        $b['badge_cls'] = 'badge-type';
        $b['badge_label'] = 'Gruppe';
        $b['owner_url'] = '/admin/gruppen/form.php?id=' . (int)$b['owner_id'];
    }
}
unset($b);

echo adminTwig()->render('bilder/index.twig', [
    'page_title' => 'Bilder',
    'nav_active' => 'bilder',
    'bilder'     => $bilder,
    'total'      => $total,
    'page'       => $page,
    'pages'      => $pages,
    'offset'     => $off,
    'per_page'   => $per,
]);
