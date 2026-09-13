<?php
require_once __DIR__ . '/../../bootstrap.php';

$vid = (int)($_GET['veranstaltung_id'] ?? $_POST['veranstaltung_id'] ?? 0);
if (!$vid) { header('Location: /admin/veranstaltungen/'); exit; }

$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_edit = $id !== null;
$errors  = [];

$event = db()->prepare("SELECT id, name FROM veranstaltung WHERE id = ?");
$event->execute([$vid]);
$event = $event->fetch();
if (!$event) { header('Location: /admin/veranstaltungen/'); exit; }

$days_stmt = db()->prepare("SELECT datum FROM veranstaltung_tag WHERE veranstaltung_id = ? ORDER BY datum");
$days_stmt->execute([$vid]);
$days = $days_stmt->fetchAll(PDO::FETCH_COLUMN);

$ep_stmt = db()->prepare("
    SELECT t.id, t.name, t.kategorie
    FROM veranstaltung_teilnahme vt
    JOIN teilnehmer t ON t.id = vt.teilnehmer_id
    WHERE vt.veranstaltung_id = ?
    ORDER BY t.kategorie, t.name
");
$ep_stmt->execute([$vid]);
$event_participants = $ep_stmt->fetchAll();

$has_datum_param = isset($_GET['datum']) && in_array($_GET['datum'], $days);
$preselect_datum = $has_datum_param ? $_GET['datum'] : ($days[0] ?? '');
$v = ['datum' => $preselect_datum, 'uhrzeit' => '', 'titel' => '', 'beschreibung' => '', 'ort_name' => ''];
$assigned_ids = [];

if ($is_edit) {
    $row = db()->prepare("SELECT * FROM veranstaltung_programm WHERE id = ? AND veranstaltung_id = ?");
    $row->execute([$id, $vid]);
    $fetched = $row->fetch();
    if (!$fetched) { header("Location: /admin/veranstaltungen/programm/?veranstaltung_id=$vid"); exit; }
    $v = array_merge($v, array_map(fn($val) => $val ?? '', $fetched));

    $ai = db()->prepare("SELECT teilnehmer_id FROM programm_teilnehmer WHERE programm_id = ?");
    $ai->execute([$id]);
    $assigned_ids = $ai->fetchAll(PDO::FETCH_COLUMN);
}

$return_val = $_GET['return'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['datum']        = trim($_POST['datum']        ?? '');
    $v['uhrzeit']      = trim($_POST['uhrzeit']      ?? '');
    $v['titel']        = $_POST['titel']              ?? '';
    $v['beschreibung'] = $_POST['beschreibung']       ?? '';
    $v['ort_name']     = trim($_POST['ort_name']      ?? '');
    $assigned_ids      = array_map('intval', $_POST['teilnehmer'] ?? []);
    $return_val        = $_POST['return'] ?? '';

    if (!$v['datum'])                          $errors[] = 'Bitte einen Tag auswählen.';
    elseif (!in_array($v['datum'], $days))     $errors[] = 'Ungültiger Tag.';
    if (!$v['uhrzeit'])                        $errors[] = 'Uhrzeit ist ein Pflichtfeld.';
    if (!strip_tags($v['titel']))              $errors[] = 'Titel ist ein Pflichtfeld.';

    if (!$errors) {
        $db = db();
        if ($is_edit) {
            $db->prepare("
                UPDATE veranstaltung_programm
                   SET datum=?, uhrzeit=?, titel=?, beschreibung=?, ort_name=?
                 WHERE id=? AND veranstaltung_id=?
            ")->execute([$v['datum'], $v['uhrzeit'], $v['titel'],
                         $v['beschreibung'] ?: null, $v['ort_name'] ?: null,
                         $id, $vid]);
        } else {
            $db->prepare("
                INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
                VALUES (?,?,?,?,?,?)
            ")->execute([$vid, $v['datum'], $v['uhrzeit'], $v['titel'],
                         $v['beschreibung'] ?: null, $v['ort_name'] ?: null]);
            $id = (int)$db->lastInsertId();
        }

        $db->prepare("DELETE FROM programm_teilnehmer WHERE programm_id = ?")->execute([$id]);
        $ins = $db->prepare("INSERT IGNORE INTO programm_teilnehmer (programm_id, teilnehmer_id) VALUES (?,?)");
        foreach ($assigned_ids as $tid) {
            if ($tid) $ins->execute([$id, $tid]);
        }

        $_SESSION['flash'] = ['type' => 'success', 'msg' => $is_edit ? 'Programmpunkt gespeichert.' : 'Programmpunkt hinzugefügt.'];
        $back = $return_val === 'form'
            ? "Location: /admin/veranstaltungen/form.php?id=$vid"
            : "Location: /admin/veranstaltungen/programm/?veranstaltung_id=$vid";
        header($back);
        exit;
    }
}

$back_url = ($return_val === 'form')
    ? "/admin/veranstaltungen/form.php?id=$vid"
    : "/admin/veranstaltungen/programm/?veranstaltung_id=$vid";

echo adminTwig()->render('veranstaltungen/programm/form.twig', [
    'page_title'         => $is_edit ? 'Programmpunkt bearbeiten' : 'Programmpunkt hinzufügen',
    'nav_active'         => 'veranstaltungen',
    'errors'             => $errors,
    'v'                  => $v,
    'is_edit'            => $is_edit,
    'vid'                => $vid,
    'event'              => $event,
    'days'               => $days,
    'event_participants' => $event_participants,
    'assigned_ids'       => $assigned_ids,
    'preselect_datum'    => $preselect_datum,
    'has_datum_param'    => $has_datum_param,
    'back_url'           => $back_url,
    'return_val'         => $return_val,
]);
