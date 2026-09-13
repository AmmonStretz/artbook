<?php
require_once __DIR__ . '/../bootstrap.php';

$id      = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$copy_id = isset($_GET['copy']) ? (int)$_GET['copy']  : null;
$is_edit = $id !== null;
$is_copy = $copy_id !== null && !$is_edit;
$errors  = [];

$v = [
    'name' => '', 'name_en' => '',
    'beschreibung' => '', 'beschreibung_en' => '',
    'kurzbeschreibung' => '', 'kurzbeschreibung_en' => '',
    'sichtbar' => '1', 'programm_sichtbar' => '1', 'teilnehmer_sichtbar' => '1',
    'strasse' => '', 'plz' => '', 'ort' => '',
    'ort_name' => '', 'ort_url' => '',
    'lat' => '', 'lng' => '',
    'muster' => '', 'logo' => '',
    'veranstaltungsort_id' => '',
];
$tage = [['datum' => '', 'startzeit' => '', 'endzeit' => '']];
$fetched_name = '';

$load_id = $is_edit ? $id : ($is_copy ? $copy_id : null);
if ($load_id) {
    $row = db()->prepare('SELECT * FROM veranstaltung WHERE id = ?');
    $row->execute([$load_id]);
    $fetched = $row->fetch();
    if (!$fetched) { header('Location: /admin/veranstaltungen/'); exit; }

    $v = array_map(fn($val) => $val ?? '', $fetched);
    $fetched_name = $fetched['name'];

    if ($is_copy) {
        $v['name']     = $v['name'] . ' (Kopie)';
        $v['id']       = '';
        $v['sichtbar'] = '0';
    }

    $st = db()->prepare('SELECT datum, startzeit, endzeit FROM veranstaltung_tag WHERE veranstaltung_id = ? ORDER BY datum');
    $st->execute([$load_id]);
    $fetched_tage = $st->fetchAll();
    if ($fetched_tage) $tage = $fetched_tage;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit) {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove_teilnehmer') {
        $tid = (int)($_POST['teilnehmer_id'] ?? 0);
        if ($tid) {
            db()->prepare("DELETE FROM veranstaltung_teilnahme WHERE veranstaltung_id = ? AND teilnehmer_id = ?")
                ->execute([$id, $tid]);
        }
        header("Location: /admin/veranstaltungen/form.php?id=$id");
        exit;
    }

    if ($action === 'update_tischnummer') {
        $tid = (int)($_POST['teilnehmer_id'] ?? 0);
        $nr  = trim($_POST['tischnummer'] ?? '') ?: null;
        if ($tid) {
            db()->prepare("UPDATE veranstaltung_teilnahme SET tischnummer = ? WHERE veranstaltung_id = ? AND teilnehmer_id = ?")
                ->execute([$nr, $id, $tid]);
        }
        header("Location: /admin/veranstaltungen/form.php?id=$id");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['name']                = trim($_POST['name']           ?? '');
    $v['name_en']             = trim($_POST['name_en']        ?? '');
    $v['beschreibung']        = $_POST['beschreibung']        ?? '';
    $v['beschreibung_en']     = $_POST['beschreibung_en']     ?? '';
    $v['kurzbeschreibung']    = $_POST['kurzbeschreibung']    ?? '';
    $v['kurzbeschreibung_en'] = $_POST['kurzbeschreibung_en'] ?? '';
    $v['sichtbar']             = isset($_POST['sichtbar'])            ? 1 : 0;
    $v['programm_sichtbar']    = isset($_POST['programm_sichtbar'])   ? 1 : 0;
    $v['teilnehmer_sichtbar']  = isset($_POST['teilnehmer_sichtbar']) ? 1 : 0;
    $v['veranstaltungsort_id'] = (int)($_POST['veranstaltungsort_id'] ?? 0) ?: null;

    $tage = [];
    foreach ($_POST['tag_datum'] ?? [] as $i => $datum) {
        $tage[] = [
            'datum'     => trim($datum),
            'startzeit' => trim($_POST['tag_startzeit'][$i] ?? ''),
            'endzeit'   => trim($_POST['tag_endzeit'][$i]   ?? ''),
        ];
    }

    if (!$v['name'])
        $errors[] = 'Name ist ein Pflichtfeld.';
    if (!array_filter($tage, fn($t) => $t['datum'] !== ''))
        $errors[] = 'Mindestens ein Veranstaltungstag mit Datum ist erforderlich.';
    foreach ($tage as $i => $t) {
        if ($t['datum'] && (!$t['startzeit'] || !$t['endzeit']))
            $errors[] = 'Tag ' . ($i + 1) . ': Bitte Start- und Endzeit angeben.';
    }
    if (!$errors) {
        $pdo = db();

        $ort_fields = ['strasse' => null, 'plz' => null, 'ort' => null,
                       'ort_name' => null, 'ort_url' => null, 'lat' => null, 'lng' => null];
        if ($v['veranstaltungsort_id']) {
            $os = $pdo->prepare('SELECT * FROM veranstaltungsort WHERE id = ?');
            $os->execute([$v['veranstaltungsort_id']]);
            $the_ort = $os->fetch();
            if ($the_ort) {
                $ort_fields['strasse']  = $the_ort['strasse'] ?: null;
                $ort_fields['plz']      = $the_ort['plz'] ?: null;
                $ort_fields['ort']      = $the_ort['ort'] ?: null;
                $ort_fields['ort_name'] = $the_ort['name'] ?: null;
                $ort_fields['ort_url']  = $the_ort['ort_url'] ?: null;
                $ort_fields['lat']      = $the_ort['lat'];
                $ort_fields['lng']      = $the_ort['lng'];
            }
        }
        $lat = $ort_fields['lat'];
        $lng = $ort_fields['lng'];

        if ($is_edit) {
            $pdo->prepare("
                UPDATE veranstaltung
                   SET name=?, name_en=?,
                       beschreibung=?, beschreibung_en=?,
                       kurzbeschreibung=?, kurzbeschreibung_en=?,
                       sichtbar=?, programm_sichtbar=?, teilnehmer_sichtbar=?,
                       strasse=?, plz=?, ort=?, ort_name=?, ort_url=?, lat=?, lng=?,
                       veranstaltungsort_id=?
                 WHERE id=?
            ")->execute([
                $v['name'], $v['name_en'] ?: null,
                $v['beschreibung'], $v['beschreibung_en'] ?: null,
                $v['kurzbeschreibung'] ?: null, $v['kurzbeschreibung_en'] ?: null,
                $v['sichtbar'], $v['programm_sichtbar'], $v['teilnehmer_sichtbar'],
                $ort_fields['strasse'], $ort_fields['plz'], $ort_fields['ort'],
                $ort_fields['ort_name'], $ort_fields['ort_url'], $lat, $lng,
                $v['veranstaltungsort_id'], $id,
            ]);
            // Only delete days that were removed; keeps program items for surviving days
            $newDates = array_values(array_filter(array_column($tage, 'datum')));
            if ($newDates) {
                $ph = implode(',', array_fill(0, count($newDates), '?'));
                $pdo->prepare("DELETE FROM veranstaltung_tag WHERE veranstaltung_id = ? AND datum NOT IN ($ph)")
                    ->execute(array_merge([$id], $newDates));
            } else {
                $pdo->prepare('DELETE FROM veranstaltung_tag WHERE veranstaltung_id = ?')->execute([$id]);
            }
        } else {
            $pdo->prepare("
                INSERT INTO veranstaltung
                    (name, name_en, beschreibung, beschreibung_en,
                     kurzbeschreibung, kurzbeschreibung_en,
                     sichtbar, programm_sichtbar, teilnehmer_sichtbar,
                     strasse, plz, ort, ort_name, ort_url, lat, lng,
                     veranstaltungsort_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $v['name'], $v['name_en'] ?: null,
                $v['beschreibung'], $v['beschreibung_en'] ?: null,
                $v['kurzbeschreibung'] ?: null, $v['kurzbeschreibung_en'] ?: null,
                $v['sichtbar'], $v['programm_sichtbar'], $v['teilnehmer_sichtbar'],
                $ort_fields['strasse'], $ort_fields['plz'], $ort_fields['ort'],
                $ort_fields['ort_name'], $ort_fields['ort_url'], $lat, $lng,
                $v['veranstaltungsort_id'],
            ]);
            $id = (int) $pdo->lastInsertId();
        }

        $ins = $pdo->prepare('INSERT INTO veranstaltung_tag (veranstaltung_id, datum, startzeit, endzeit) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE startzeit = VALUES(startzeit), endzeit = VALUES(endzeit)');
        foreach ($tage as $t) {
            if ($t['datum']) $ins->execute([$id, $t['datum'], $t['startzeit'], $t['endzeit']]);
        }

        if ($is_copy && $copy_id) {
            $pdo->prepare("
                INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id, tischnummer)
                SELECT ?, teilnehmer_id, tischnummer FROM veranstaltung_teilnahme WHERE veranstaltung_id = ?
            ")->execute([$id, $copy_id]);
        }

        if ($is_edit && ($_POST['_ajax'] ?? '') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'msg' => 'Änderungen gespeichert.']);
            exit;
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'msg'  => $is_edit ? 'Änderungen gespeichert.' : ($is_copy ? 'Kopie erstellt.' : 'Veranstaltung erstellt.'),
        ];
        header($is_edit ? "Location: /admin/veranstaltungen/form.php?id=$id" : "Location: /admin/veranstaltungen/form.php?id=$id");
        exit;
    }
}

$participants = [];
$prog_by_date = [];
if ($is_edit) {
    $stmt = db()->prepare("
        SELECT t.id, t.name, t.kategorie, vt.tischnummer
        FROM veranstaltung_teilnahme vt
        JOIN teilnehmer t ON t.id = vt.teilnehmer_id
        WHERE vt.veranstaltung_id = ?
        ORDER BY t.kategorie, t.name
    ");
    $stmt->execute([$id]);
    $participants = $stmt->fetchAll();

    $ps = db()->prepare("
        SELECT id, datum, uhrzeit, titel, ort_name
        FROM veranstaltung_programm
        WHERE veranstaltung_id = ?
        ORDER BY datum, uhrzeit
    ");
    $ps->execute([$id]);
    foreach ($ps->fetchAll() as $p) {
        $prog_by_date[$p['datum']][] = $p;
    }
}

if ($is_edit && ($_POST['_ajax'] ?? '') === '1' && $errors) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

$page_title = $is_copy ? 'Veranstaltung kopieren' : ($is_edit ? 'Veranstaltung bearbeiten' : 'Neue Veranstaltung');

$current_ort = null;
if ($v['veranstaltungsort_id']) {
    $os = db()->prepare('SELECT * FROM veranstaltungsort WHERE id = ?');
    $os->execute([$v['veranstaltungsort_id']]);
    $current_ort = $os->fetch() ?: null;
}

echo adminTwig()->render('veranstaltungen/form.twig', [
    'page_title'   => $page_title,
    'nav_active'   => 'veranstaltungen',
    'errors'       => $errors,
    'v'            => $v,
    'tage'         => $tage,
    'is_edit'      => $is_edit,
    'is_copy'      => $is_copy,
    'id'           => $id,
    'fetched_name' => $fetched_name,
    'participants' => $participants,
    'prog_by_date' => $prog_by_date,
    'current_ort'  => $current_ort,
]);
