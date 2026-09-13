<?php
require_once __DIR__ . '/../bootstrap.php';

$per    = PER_PAGE_IMAGES;
$page   = max(1, (int)($_GET['page'] ?? 1));
$filter = in_array($_GET['filter'] ?? '', ['teilnehmer', 'veranstaltung', 'unzugeordnet'])
    ? $_GET['filter']
    : 'alle';

if ($filter === 'teilnehmer') {
    $total = (int) db()->query("SELECT COUNT(*) FROM teilnehmer_bild WHERE teilnehmer_id IS NOT NULL")->fetchColumn();
} elseif ($filter === 'veranstaltung') {
    $total = (int) db()->query("
        SELECT
          (SELECT COUNT(*) FROM veranstaltung WHERE muster IS NOT NULL) +
          (SELECT COUNT(*) FROM veranstaltung WHERE logo   IS NOT NULL)
    ")->fetchColumn();
} elseif ($filter === 'unzugeordnet') {
    $total = (int) db()->query("SELECT COUNT(*) FROM teilnehmer_bild WHERE teilnehmer_id IS NULL")->fetchColumn();
} else {
    $total = (int) db()->query("
        SELECT
          (SELECT COUNT(*) FROM teilnehmer_bild) +
          (SELECT COUNT(*) FROM veranstaltung WHERE muster IS NOT NULL) +
          (SELECT COUNT(*) FROM veranstaltung WHERE logo   IS NOT NULL)
    ")->fetchColumn();
}

$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$veranstaltungSql = "
    SELECT 'veranstaltung_muster' AS quelle, v.id AS quelle_id, v.muster AS dateiname,
           NULL AS titel, NULL AS alt_text, NULL AS startdatum,
           v.name AS owner_name, 'veranstaltung' AS owner_kategorie, v.id AS owner_id
    FROM veranstaltung v WHERE v.muster IS NOT NULL
    UNION ALL
    SELECT 'veranstaltung_logo' AS quelle, v.id AS quelle_id, v.logo AS dateiname,
           NULL AS titel, NULL AS alt_text, NULL AS startdatum,
           v.name AS owner_name, 'veranstaltung' AS owner_kategorie, v.id AS owner_id
    FROM veranstaltung v WHERE v.logo IS NOT NULL
";

if ($filter === 'teilnehmer') {
    $sql = "
        SELECT 'teilnehmer' AS quelle, b.id AS quelle_id, b.dateiname,
               b.titel, b.alt_text, b.startdatum,
               t.name AS owner_name, t.kategorie AS owner_kategorie, t.id AS owner_id
        FROM teilnehmer_bild b
        JOIN teilnehmer t ON t.id = b.teilnehmer_id
        ORDER BY b.dateiname DESC
        LIMIT ? OFFSET ?
    ";
} elseif ($filter === 'veranstaltung') {
    $sql = "
        SELECT quelle, quelle_id, dateiname, titel, alt_text, startdatum,
               owner_name, owner_kategorie, owner_id
        FROM ($veranstaltungSql) AS ve
        ORDER BY dateiname DESC
        LIMIT ? OFFSET ?
    ";
} elseif ($filter === 'unzugeordnet') {
    $sql = "
        SELECT 'teilnehmer' AS quelle, b.id AS quelle_id, b.dateiname,
               b.titel, b.alt_text, b.startdatum,
               NULL AS owner_name, NULL AS owner_kategorie, NULL AS owner_id
        FROM teilnehmer_bild b
        WHERE b.teilnehmer_id IS NULL
        ORDER BY b.dateiname DESC
        LIMIT ? OFFSET ?
    ";
} else {
    $sql = "
        SELECT quelle, quelle_id, dateiname, titel, alt_text, startdatum,
               owner_name, owner_kategorie, owner_id
        FROM (
            SELECT 'teilnehmer' AS quelle, b.id AS quelle_id, b.dateiname,
                   b.titel, b.alt_text, b.startdatum,
                   t.name AS owner_name, t.kategorie AS owner_kategorie, t.id AS owner_id
            FROM teilnehmer_bild b
            LEFT JOIN teilnehmer t ON t.id = b.teilnehmer_id
            UNION ALL
            $veranstaltungSql
        ) AS alle
        ORDER BY dateiname DESC
        LIMIT ? OFFSET ?
    ";
}

$stmt = db()->prepare($sql);
$stmt->execute([$per, $off]);
$bilder = $stmt->fetchAll();

foreach ($bilder as &$b) {
    $qid  = (int)$b['quelle_id'];
    $stem = pathinfo($b['dateiname'], PATHINFO_FILENAME);
    $ext  = strtolower(pathinfo($b['dateiname'], PATHINFO_EXTENSION));

    if ($b['quelle'] === 'veranstaltung_muster') {
        $url = $ext === 'svg'
            ? IMG_MUSTER_UPLOAD_URL . $b['dateiname']
            : IMG_MUSTER_UPLOAD_URL . $stem . '_fp_lg.webp';
        $b['url']         = $url;
        $b['url_full']    = $url;
        $b['badge_cls']   = 'badge-future';
        $b['badge_label'] = 'Muster';
        $b['owner_url']   = '/admin/veranstaltungen/form.php?id=' . $qid;
    } elseif ($b['quelle'] === 'veranstaltung_logo') {
        $b['url']         = IMG_LOGO_UPLOAD_URL . $b['dateiname'];
        $b['url_full']    = IMG_LOGO_UPLOAD_URL . $b['dateiname'];
        $b['badge_cls']   = 'badge-future';
        $b['badge_label'] = 'Logo';
        $b['owner_url']   = '/admin/veranstaltungen/form.php?id=' . $qid;
    } else {
        $b['url']      = IMG_UPLOAD_URL . $stem . '_768.jpg';
        $b['url_full'] = IMG_UPLOAD_URL . $stem . '_1024.jpg';
        if ($b['owner_id'] === null) {
            $b['badge_cls']   = 'badge-past';
            $b['badge_label'] = 'Unzugeordnet';
            $b['owner_name']  = '–';
            $b['owner_url']   = null;
        } else {
            $b['badge_cls']   = 'badge-kuenstler';
            $b['badge_label'] = TEILNEHMER_KATEGORIEN[$b['owner_kategorie']] ?? ucfirst($b['owner_kategorie']);
            $b['owner_url']   = '/admin/teilnehmer/form.php?id=' . (int)$b['owner_id'];
        }
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
    'filter'     => $filter,
]);
