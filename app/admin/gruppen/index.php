<?php
require_once __DIR__ . '/../bootstrap.php';

$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE_ADMIN;

$count_stmt = db()->prepare("SELECT COUNT(*) FROM teilnehmer WHERE typ='gruppe'" . ($q ? " AND name LIKE ?" : ""));
$count_stmt->execute($q ? ["%$q%"] : []);
$total = (int) $count_stmt->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE_ADMIN));
$page  = min($page, $pages);

$stmt = db()->prepare("
    SELECT g.id, g.name, g.link, g.gruppe_typ,
           COUNT(gk.kuenstler_id) AS anzahl_kuenstler
    FROM teilnehmer g
    LEFT JOIN gruppe_kuenstler gk ON gk.gruppe_id = g.id
    WHERE g.typ = 'gruppe'" . ($q ? " AND g.name LIKE :q" : "") . "
    GROUP BY g.id
    ORDER BY g.name ASC
    LIMIT :limit OFFSET :offset
");
if ($q) $stmt->bindValue(':q', "%$q%");
$stmt->bindValue(':limit',  PER_PAGE_ADMIN, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,        PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

echo adminTwig()->render('gruppen/index.twig', [
    'page_title' => 'Gruppen',
    'nav_active' => 'gruppen',
    'flash'      => $flash,
    'rows'       => $rows,
    'total'      => $total,
    'pages'      => $pages,
    'page'       => $page,
    'offset'     => $offset,
    'per_page'   => PER_PAGE_ADMIN,
    'q'          => $q,
]);
