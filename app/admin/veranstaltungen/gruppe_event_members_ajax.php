<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $gid  = (int)($_GET['gruppe_id']       ?? 0);
    $vid  = (int)($_GET['veranstaltung_id'] ?? 0);
    $mode = $_GET['mode'] ?? 'members';
    $q    = trim($_GET['q'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = 12;

    if (!$gid || !$vid) { echo json_encode(['ok' => false]); exit; }

    $like = $q ? "%$q%" : null;

    if ($mode === 'available') {
        // Global members of the group NOT yet active for this event
        $where  = "tm.gruppe_id = ? AND k.id NOT IN (SELECT mitglied_id FROM veranstaltung_gruppe_mitglied WHERE gruppe_id = ? AND veranstaltung_id = ?)";
        $params = [$gid, $gid, $vid];
        if ($like) { $where .= ' AND k.name LIKE ?'; $params[] = $like; }

        $cnt = db()->prepare("SELECT COUNT(*) FROM teilnehmer k JOIN teilnehmer_mitglied tm ON tm.mitglied_id = k.id WHERE $where");
        $cnt->execute($params);
        $total = (int)$cnt->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page  = min($page, $pages);
        $off   = ($page - 1) * $per;

        $stmt = db()->prepare("SELECT k.id, k.name FROM teilnehmer k JOIN teilnehmer_mitglied tm ON tm.mitglied_id = k.id WHERE $where ORDER BY k.name LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $per, $off]);
    } else {
        // Active members for this event
        $where  = 'vgm.gruppe_id = ? AND vgm.veranstaltung_id = ?';
        $params = [$gid, $vid];
        if ($like) { $where .= ' AND k.name LIKE ?'; $params[] = $like; }

        $cnt = db()->prepare("SELECT COUNT(*) FROM veranstaltung_gruppe_mitglied vgm JOIN teilnehmer k ON k.id = vgm.mitglied_id WHERE $where");
        $cnt->execute($params);
        $total = (int)$cnt->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page  = min($page, $pages);
        $off   = ($page - 1) * $per;

        $stmt = db()->prepare("SELECT k.id, k.name FROM teilnehmer k JOIN veranstaltung_gruppe_mitglied vgm ON vgm.mitglied_id = k.id WHERE $where ORDER BY k.name LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $per, $off]);
    }

    $rows = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page, 'per' => $per]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']        ?? '';
    $gid    = (int)($_POST['gruppe_id']        ?? 0);
    $vid    = (int)($_POST['veranstaltung_id']  ?? 0);
    $kid    = (int)($_POST['mitglied_id']       ?? 0);

    if (!$gid || !$vid || !$kid) { echo json_encode(['ok' => false]); exit; }

    if ($action === 'add') {
        db()->prepare("INSERT IGNORE INTO veranstaltung_gruppe_mitglied (veranstaltung_id, gruppe_id, mitglied_id) VALUES (?, ?, ?)")
           ->execute([$vid, $gid, $kid]);
        echo json_encode(['ok' => true]);
    } elseif ($action === 'remove') {
        db()->prepare("DELETE FROM veranstaltung_gruppe_mitglied WHERE veranstaltung_id = ? AND gruppe_id = ? AND mitglied_id = ?")
           ->execute([$vid, $gid, $kid]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

echo json_encode(['ok' => false]);
