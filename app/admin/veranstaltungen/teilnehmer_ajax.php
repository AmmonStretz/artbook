<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$vid = (int)($_REQUEST['veranstaltung_id'] ?? 0);
if (!$vid) { echo json_encode(['error' => 'missing']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $q    = trim($_GET['q'] ?? '');
    $typ  = $_GET['typ'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = 12;
    $off  = ($page - 1) * $per;

    $typ_cond = match($typ) {
        'kuenstler' => "AND t.typ = 'kuenstler'",
        'gruppe'    => "AND t.typ = 'gruppe'",
        default     => "AND t.typ IN ('kuenstler', 'gruppe')"
    };
    $q_cond = $q ? "AND t.name LIKE ?" : "";

    $base   = "FROM teilnehmer t
               WHERE t.id NOT IN (
                   SELECT teilnehmer_id FROM veranstaltung_teilnahme WHERE veranstaltung_id = ?
               ) $typ_cond $q_cond";
    $params = [$vid];
    if ($q) $params[] = "%$q%";

    $cnt = db()->prepare("SELECT COUNT(*) $base");
    $cnt->execute($params);
    $total = (int) $cnt->fetchColumn();

    $list = db()->prepare("SELECT t.id, t.name, t.typ $base ORDER BY t.typ, t.name LIMIT ? OFFSET ?");
    $i = 1;
    foreach ($params as $p) { $list->bindValue($i++, $p); }
    $list->bindValue($i++, $per, PDO::PARAM_INT);
    $list->bindValue($i,   $off, PDO::PARAM_INT);
    $list->execute();

    echo json_encode([
        'rows'  => $list->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pages' => max(1, (int) ceil($total / $per)),
        'page'  => $page,
        'per'   => $per,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = (int)($_POST['teilnehmer_id'] ?? 0);
    $nr  = trim($_POST['tischnummer'] ?? '') ?: null;
    if ($tid) {
        db()->prepare("INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id, tischnummer) VALUES (?,?,?)")
            ->execute([$vid, $tid, $nr]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['error' => 'invalid']);
    }
    exit;
}
