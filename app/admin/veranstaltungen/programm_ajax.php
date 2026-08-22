<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$vid = (int)($_REQUEST['veranstaltung_id'] ?? 0);
if (!$vid) { echo json_encode(['ok' => false, 'error' => 'missing vid']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $pid = (int)($_POST['id'] ?? 0);
        if ($pid) {
            db()->prepare("DELETE FROM programm_teilnehmer WHERE programm_id = ?")->execute([$pid]);
            db()->prepare("DELETE FROM veranstaltung_programm WHERE id = ? AND veranstaltung_id = ?")->execute([$pid, $vid]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    $pid       = (int)($_POST['id'] ?? 0);
    $datum     = trim($_POST['datum']        ?? '');
    $zeit      = trim($_POST['uhrzeit']      ?? '');
    $titel     = $_POST['titel']             ?? '';
    $titel_en  = $_POST['titel_en']          ?? '';
    $beschr    = $_POST['beschreibung']      ?? '';
    $beschr_en = $_POST['beschreibung_en']   ?? '';
    $ort       = trim($_POST['ort_name']     ?? '');
    $ort_en    = trim($_POST['ort_name_en']  ?? '');
    $tnIds     = array_map('intval', $_POST['teilnehmer'] ?? []);

    $errors = [];
    if (!$datum)             $errors[] = 'Bitte einen Tag auswählen.';
    if (!$zeit)              $errors[] = 'Uhrzeit ist ein Pflichtfeld.';
    if (!strip_tags($titel)) $errors[] = 'Titel ist ein Pflichtfeld.';

    if ($errors) { echo json_encode(['ok' => false, 'errors' => $errors]); exit; }

    $chk = db()->prepare("SELECT COUNT(*) FROM veranstaltung_tag WHERE veranstaltung_id = ? AND datum = ?");
    $chk->execute([$vid, $datum]);
    if (!(int)$chk->fetchColumn()) {
        echo json_encode(['ok' => false, 'errors' => ['Ungültiger Tag.']]);
        exit;
    }

    $db = db();
    if ($pid) {
        $db->prepare("UPDATE veranstaltung_programm
                         SET datum=?, uhrzeit=?, titel=?, titel_en=?, beschreibung=?, beschreibung_en=?, ort_name=?, ort_name_en=?
                       WHERE id=? AND veranstaltung_id=?")
           ->execute([$datum, $zeit, $titel, $titel_en ?: null, $beschr ?: null, $beschr_en ?: null, $ort ?: null, $ort_en ?: null, $pid, $vid]);
    } else {
        $db->prepare("INSERT INTO veranstaltung_programm
                          (veranstaltung_id, datum, uhrzeit, titel, titel_en, beschreibung, beschreibung_en, ort_name, ort_name_en)
                      VALUES (?,?,?,?,?,?,?,?,?)")
           ->execute([$vid, $datum, $zeit, $titel, $titel_en ?: null, $beschr ?: null, $beschr_en ?: null, $ort ?: null, $ort_en ?: null]);
        $pid = (int)$db->lastInsertId();
    }

    $db->prepare("DELETE FROM programm_teilnehmer WHERE programm_id = ?")->execute([$pid]);
    if ($tnIds) {
        $ins = $db->prepare("INSERT IGNORE INTO programm_teilnehmer (programm_id, teilnehmer_id) VALUES (?,?)");
        foreach ($tnIds as $tid) { if ($tid) $ins->execute([$pid, $tid]); }
    }

    echo json_encode(['ok' => true, 'id' => $pid, 'datum' => $datum]);
    exit;
}

// GET
$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $datum = trim($_GET['datum'] ?? '');
    $stmt = db()->prepare("
        SELECT id, uhrzeit, titel, titel_en, ort_name, ort_name_en
        FROM veranstaltung_programm
        WHERE veranstaltung_id = ? AND datum = ?
        ORDER BY uhrzeit
    ");
    $stmt->execute([$vid, $datum]);
    echo json_encode(['ok' => true, 'entries' => $stmt->fetchAll()]);
    exit;
}

$pid = (int)($_GET['id'] ?? 0);

$ep = db()->prepare("
    SELECT t.id, t.name, t.typ
    FROM veranstaltung_teilnahme vt
    JOIN teilnehmer t ON t.id = vt.teilnehmer_id
    WHERE vt.veranstaltung_id = ?
    ORDER BY t.typ, t.name
");
$ep->execute([$vid]);
$participants = $ep->fetchAll();

if (!$pid) {
    echo json_encode(['ok' => true, 'entry' => null, 'participants' => $participants, 'assigned' => []]);
    exit;
}

$es = db()->prepare("SELECT * FROM veranstaltung_programm WHERE id = ? AND veranstaltung_id = ?");
$es->execute([$pid, $vid]);
$entry = $es->fetch();
if (!$entry) { echo json_encode(['ok' => false, 'error' => 'Nicht gefunden.']); exit; }

$ai = db()->prepare("SELECT teilnehmer_id FROM programm_teilnehmer WHERE programm_id = ?");
$ai->execute([$pid]);
$assigned = $ai->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['ok' => true, 'entry' => $entry, 'participants' => $participants, 'assigned' => array_map('intval', $assigned)]);
