<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 8;
$offset = ($page - 1) * $per;

$where  = ['1=1'];
$params = [];
if ($q) { $where[] = 'name LIKE ?'; $params[] = "%$q%"; }
$where_sql = implode(' AND ', $where);

$count_stmt = db()->prepare("SELECT COUNT(*) FROM veranstaltungsort WHERE $where_sql");
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $per));

$stmt = db()->prepare("
    SELECT id, name, strasse, plz, ort, land, ort_url, lat, lng
    FROM veranstaltungsort
    WHERE $where_sql
    ORDER BY name ASC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, $per, $offset]);
$rows = $stmt->fetchAll();

echo json_encode([
    'ok'    => true,
    'rows'  => $rows,
    'total' => $total,
    'pages' => $pages,
    'page'  => $page,
    'per'   => $per,
]);
