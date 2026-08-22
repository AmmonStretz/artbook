<?php
require_once __DIR__ . '/../bootstrap.php';

$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE_ADMIN;

$count_stmt = db()->prepare("SELECT COUNT(*) FROM veranstaltung" . ($q ? " WHERE name LIKE ?" : ""));
$count_stmt->execute($q ? ["%$q%"] : []);
$total = (int) $count_stmt->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE_ADMIN));
$page  = min($page, $pages);

$stmt = db()->prepare("
    SELECT v.id, v.name, v.sichtbar, v.plz, v.ort, v.ort_name, v.created_at,
           COUNT(t.id)  AS tag_anzahl,
           MIN(t.datum) AS erster_tag,
           MAX(t.datum) AS letzter_tag
    FROM veranstaltung v
    LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
    " . ($q ? "WHERE v.name LIKE :q" : "") . "
    GROUP BY v.id
    ORDER BY v.created_at DESC
    LIMIT :limit OFFSET :offset
");
if ($q) $stmt->bindValue(':q', "%$q%");
$stmt->bindValue(':limit',  PER_PAGE_ADMIN, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,        PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

echo adminTwig()->render('veranstaltungen/index.twig', [
    'page_title' => 'Veranstaltungen',
    'nav_active' => 'veranstaltungen',
    'flash'      => $flash,
    'rows'       => $rows,
    'total'      => $total,
    'pages'      => $pages,
    'page'       => $page,
    'offset'     => $offset,
    'per_page'   => PER_PAGE_ADMIN,
    'q'          => $q,
    'today'      => date('Y-m-d'),
]);
