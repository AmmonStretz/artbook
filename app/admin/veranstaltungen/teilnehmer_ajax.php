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
        'kuenstler' => "AND t.kategorie = 'kuenstler'",
        'gruppe'    => "AND t.kategorie != 'kuenstler'",
        default     => ""
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

    $list = db()->prepare("SELECT t.id, t.name, t.kategorie $base ORDER BY t.kategorie = 'kuenstler' DESC, t.name LIMIT ? OFFSET ?");
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

        // Auto-populate per-event members for groups
        $kat_stmt = db()->prepare("SELECT kategorie FROM teilnehmer WHERE id = ?");
        $kat_stmt->execute([$tid]);
        $kategorie = $kat_stmt->fetchColumn();

        if ($kategorie && $kategorie !== 'kuenstler') {
            // Copy member selection from most recent previous event for this group
            $prev_stmt = db()->prepare("
                SELECT veranstaltung_id FROM veranstaltung_gruppe_mitglied
                WHERE gruppe_id = ? AND veranstaltung_id != ?
                GROUP BY veranstaltung_id ORDER BY veranstaltung_id DESC LIMIT 1
            ");
            $prev_stmt->execute([$tid, $vid]);
            $prev_vid = $prev_stmt->fetchColumn();

            if ($prev_vid) {
                db()->prepare("
                    INSERT IGNORE INTO veranstaltung_gruppe_mitglied (veranstaltung_id, gruppe_id, mitglied_id)
                    SELECT ?, gruppe_id, mitglied_id FROM veranstaltung_gruppe_mitglied
                    WHERE gruppe_id = ? AND veranstaltung_id = ?
                ")->execute([$vid, $tid, $prev_vid]);
            } else {
                // First time: use all current global members as default
                db()->prepare("
                    INSERT IGNORE INTO veranstaltung_gruppe_mitglied (veranstaltung_id, gruppe_id, mitglied_id)
                    SELECT ?, gruppe_id, mitglied_id FROM teilnehmer_mitglied WHERE gruppe_id = ?
                ")->execute([$vid, $tid]);
            }
        }

        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['error' => 'invalid']);
    }
    exit;
}
