<?php
require_once __DIR__ . '/../bootstrap.php';

$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE_ADMIN;

$where  = ['1=1'];
$params = [];
if ($q) { $where[] = 'name LIKE ?'; $params[] = "%$q%"; }
$where_sql = implode(' AND ', $where);

$count_stmt = db()->prepare("SELECT COUNT(*) FROM veranstaltungsort WHERE $where_sql");
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE_ADMIN));
$page  = min($page, $pages);

$stmt = db()->prepare("
    SELECT o.id, o.name, o.strasse, o.plz, o.ort,
           COUNT(v.id) AS anzahl_veranstaltungen
    FROM veranstaltungsort o
    LEFT JOIN veranstaltung v ON v.veranstaltungsort_id = o.id
    WHERE $where_sql
    GROUP BY o.id
    ORDER BY o.name ASC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, PER_PAGE_ADMIN, $offset]);
$rows = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

echo adminTwig()->render('orte/index.twig', [
    'page_title' => 'Veranstaltungsorte',
    'nav_active' => 'orte',
    'flash'      => $flash,
    'rows'       => $rows,
    'total'      => $total,
    'pages'      => $pages,
    'page'       => $page,
    'offset'     => $offset,
    'per_page'   => PER_PAGE_ADMIN,
    'q'          => $q,
]);
