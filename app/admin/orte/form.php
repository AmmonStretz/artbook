<?php
require_once __DIR__ . '/../bootstrap.php';

$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_edit = $id !== null;
$errors  = [];

$v = [
    'name' => '', 'strasse' => '', 'plz' => '', 'ort' => '',
    'land' => 'Deutschland', 'ort_url' => '', 'anfahrtsbeschreibung' => '',
    'lat' => '', 'lng' => '',
];
$fetched_name = '';

if ($is_edit) {
    $stmt = db()->prepare('SELECT * FROM veranstaltungsort WHERE id = ?');
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if (!$fetched) { header('Location: /admin/orte/'); exit; }
    $v = array_map(fn($val) => $val ?? '', $fetched);
    $fetched_name = $fetched['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['name']    = trim($_POST['name']    ?? '');
    $v['strasse'] = trim($_POST['strasse'] ?? '');
    $v['plz']     = trim($_POST['plz']     ?? '');
    $v['ort']     = trim($_POST['ort']     ?? '');
    $v['land']    = trim($_POST['land']    ?? 'Deutschland');
    $v['ort_url']              = trim($_POST['ort_url'] ?? '');
    $v['anfahrtsbeschreibung'] = $_POST['anfahrtsbeschreibung'] ?? '';
    $v['lat']                  = trim($_POST['lat'] ?? '');
    $v['lng']                  = trim($_POST['lng'] ?? '');

    if (!$v['name'])
        $errors[] = 'Name ist ein Pflichtfeld.';
    if ($v['lat'] !== '' && ((float)$v['lat'] < -90 || (float)$v['lat'] > 90))
        $errors[] = 'Breitengrad muss zwischen −90 und 90 liegen.';
    if ($v['lng'] !== '' && ((float)$v['lng'] < -180 || (float)$v['lng'] > 180))
        $errors[] = 'Längengrad muss zwischen −180 und 180 liegen.';

    if (!$errors) {
        $lat = $v['lat'] !== '' ? (float)$v['lat'] : null;
        $lng = $v['lng'] !== '' ? (float)$v['lng'] : null;

        if ($is_edit) {
            db()->prepare("
                UPDATE veranstaltungsort
                   SET name=?, strasse=?, plz=?, ort=?, land=?, ort_url=?, anfahrtsbeschreibung=?, lat=?, lng=?
                 WHERE id=?
            ")->execute([
                $v['name'], $v['strasse'], $v['plz'], $v['ort'],
                $v['land'], $v['ort_url'] ?: null, $v['anfahrtsbeschreibung'] ?: null, $lat, $lng, $id,
            ]);
        } else {
            db()->prepare("
                INSERT INTO veranstaltungsort (name, strasse, plz, ort, land, ort_url, anfahrtsbeschreibung, lat, lng)
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([
                $v['name'], $v['strasse'], $v['plz'], $v['ort'],
                $v['land'], $v['ort_url'] ?: null, $v['anfahrtsbeschreibung'] ?: null, $lat, $lng,
            ]);
            $id = (int) db()->lastInsertId();
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'msg'  => $is_edit ? 'Änderungen gespeichert.' : 'Veranstaltungsort erstellt.',
        ];
        header("Location: /admin/orte/form.php?id=$id");
        exit;
    }
}

$veranstaltungen = [];
if ($is_edit) {
    $stmt = db()->prepare("
        SELECT v.id, v.name, MIN(t.datum) AS erster_tag
        FROM veranstaltung v
        LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
        WHERE v.veranstaltungsort_id = ?
        GROUP BY v.id
        ORDER BY erster_tag DESC, v.name
    ");
    $stmt->execute([$id]);
    $veranstaltungen = $stmt->fetchAll();
}

$page_title = $is_edit ? 'Veranstaltungsort bearbeiten' : 'Neuer Veranstaltungsort';

echo adminTwig()->render('orte/form.twig', [
    'page_title'      => $page_title,
    'nav_active'      => 'orte',
    'errors'          => $errors,
    'v'               => $v,
    'is_edit'         => $is_edit,
    'id'              => $id,
    'fetched_name'    => $fetched_name,
    'veranstaltungen' => $veranstaltungen,
]);
