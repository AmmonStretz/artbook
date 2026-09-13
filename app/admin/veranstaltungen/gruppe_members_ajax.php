<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $gid  = (int)($_GET['gruppe_id'] ?? 0);
    $mode = $_GET['mode'] ?? 'members';
    $q    = trim($_GET['q'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = 12;

    if (!$gid) { echo json_encode(['ok' => false]); exit; }

    $like = $q ? "%$q%" : null;

    if ($mode === 'available') {
        $where  = "k.id != ? AND k.id NOT IN (SELECT mitglied_id FROM teilnehmer_mitglied WHERE gruppe_id = ?)";
        $params = [$gid, $gid];
        if ($like) { $where .= ' AND k.name LIKE ?'; $params[] = $like; }

        $cnt = db()->prepare("SELECT COUNT(*) FROM teilnehmer k WHERE $where");
        $cnt->execute($params);
        $total = (int)$cnt->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page  = min($page, $pages);
        $off   = ($page - 1) * $per;

        $stmt = db()->prepare("SELECT k.id, k.name FROM teilnehmer k WHERE $where ORDER BY k.name LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $per, $off]);
    } else {
        $where  = 'tm.gruppe_id = ?';
        $params = [$gid];
        if ($like) { $where .= ' AND k.name LIKE ?'; $params[] = $like; }

        $cnt = db()->prepare("SELECT COUNT(*) FROM teilnehmer_mitglied tm JOIN teilnehmer k ON k.id = tm.mitglied_id WHERE $where");
        $cnt->execute($params);
        $total = (int)$cnt->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page  = min($page, $pages);
        $off   = ($page - 1) * $per;

        $stmt = db()->prepare("SELECT k.id, k.name FROM teilnehmer k JOIN teilnehmer_mitglied tm ON tm.mitglied_id = k.id WHERE $where ORDER BY k.name LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $per, $off]);
    }

    $rows = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page, 'per' => $per]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $gid    = (int)($_POST['gruppe_id']    ?? 0);
    $kid    = (int)($_POST['kuenstler_id'] ?? 0);

    if (!$gid || !$kid) { echo json_encode(['ok' => false]); exit; }

    if ($action === 'add') {
        db()->prepare("INSERT IGNORE INTO teilnehmer_mitglied (gruppe_id, mitglied_id) VALUES (?, ?)")
           ->execute([$gid, $kid]);
        echo json_encode(['ok' => true]);
    } elseif ($action === 'remove') {
        db()->prepare("DELETE FROM teilnehmer_mitglied WHERE gruppe_id = ? AND mitglied_id = ?")
           ->execute([$gid, $kid]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

echo json_encode(['ok' => false]);
