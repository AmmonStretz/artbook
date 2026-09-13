<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$vid = (int)($_GET['veranstaltung_id'] ?? $_POST['veranstaltung_id'] ?? 0);
if (!$vid) { echo json_encode(['error' => 'missing veranstaltung_id']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tid    = (int)($_POST['teilnehmer_id'] ?? 0);

    if ($action === 'remove' && $tid) {
        db()->prepare("DELETE FROM veranstaltung_teilnahme WHERE veranstaltung_id = ? AND teilnehmer_id = ?")
            ->execute([$vid, $tid]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'update_tischnummer' && $tid) {
        $nr = trim($_POST['tischnummer'] ?? '') ?: null;
        db()->prepare("UPDATE veranstaltung_teilnahme SET tischnummer = ? WHERE veranstaltung_id = ? AND teilnehmer_id = ?")
            ->execute([$nr, $vid, $tid]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false]);
    exit;
}

$per  = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$q    = trim($_GET['q'] ?? '');

$where  = 'WHERE vt.veranstaltung_id = ?';
$params = [$vid];
if ($q) { $where .= ' AND t.name LIKE ?'; $params[] = "%$q%"; }

$cs = db()->prepare("SELECT COUNT(*) FROM veranstaltung_teilnahme vt JOIN teilnehmer t ON t.id = vt.teilnehmer_id $where");
$cs->execute($params);
$total = (int)$cs->fetchColumn();

$pages = max(1, (int)ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$stmt = db()->prepare("
    SELECT t.id, t.name, t.kategorie, vt.tischnummer
    FROM veranstaltung_teilnahme vt
    JOIN teilnehmer t ON t.id = vt.teilnehmer_id
    $where
    ORDER BY t.kategorie, t.name
    LIMIT ? OFFSET ?
");
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $per, PDO::PARAM_INT);
$stmt->bindValue($i,   $off, PDO::PARAM_INT);
$stmt->execute();

echo json_encode([
    'rows'  => $stmt->fetchAll(),
    'total' => $total,
    'pages' => $pages,
    'page'  => $page,
    'per'   => $per,
]);
