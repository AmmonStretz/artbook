<?php
require_once __DIR__ . '/../bootstrap.php';

$id      = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$copy_id = isset($_GET['copy']) ? (int)$_GET['copy']  : null;
$is_edit = $id !== null;
$is_copy = $copy_id !== null && !$is_edit;
$errors  = [];

$v = ['name' => '', 'beschreibung' => '', 'beschreibung_en' => '', 'link' => '', 'gruppe_typ' => ''];
$fetched_name = '';

$load_id = $is_edit ? $id : ($is_copy ? $copy_id : null);
if ($load_id) {
    $stmt = db()->prepare("SELECT * FROM teilnehmer WHERE id = ? AND typ = 'gruppe'");
    $stmt->execute([$load_id]);
    $fetched = $stmt->fetch();
    if (!$fetched) { header('Location: /admin/gruppen/'); exit; }
    $v = array_map(fn($val) => $val ?? '', $fetched);
    $fetched_name = $fetched['name'];
    if ($is_copy) { $v['name'] .= ' (Kopie)'; $v['id'] = ''; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit) {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'add_kuenstler') {
        $kid = (int)($_POST['kuenstler_id'] ?? 0);
        if ($kid) {
            db()->prepare("INSERT IGNORE INTO gruppe_kuenstler (gruppe_id, kuenstler_id) VALUES (?, ?)")
                ->execute([$id, $kid]);
        }
        header("Location: /admin/gruppen/form.php?id=$id");
        exit;
    }

    if ($action === 'remove_kuenstler') {
        $kid = (int)($_POST['kuenstler_id'] ?? 0);
        if ($kid) {
            db()->prepare("DELETE FROM gruppe_kuenstler WHERE gruppe_id = ? AND kuenstler_id = ?")
                ->execute([$id, $kid]);
        }
        header("Location: /admin/gruppen/form.php?id=$id");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['name']            = trim($_POST['name']           ?? '');
    $v['beschreibung']    = $_POST['beschreibung']        ?? '';
    $v['beschreibung_en'] = $_POST['beschreibung_en']     ?? '';
    $v['link']            = trim($_POST['link']            ?? '');
    $v['gruppe_typ']      = trim($_POST['gruppe_typ']      ?? '');

    if (!$v['name'])       $errors[] = 'Name ist ein Pflichtfeld.';
    if (!$v['gruppe_typ']) $errors[] = 'Bitte einen Typ auswählen.';
    if ($v['gruppe_typ'] && !array_key_exists($v['gruppe_typ'], GRUPPE_TYPEN))
        $errors[] = 'Ungültiger Typ.';

    if (!$errors) {
        $pdo = db();
        if ($is_edit) {
            $pdo->prepare("UPDATE teilnehmer SET name=?, beschreibung=?, beschreibung_en=?, link=?, gruppe_typ=? WHERE id=? AND typ='gruppe'")
                ->execute([$v['name'], $v['beschreibung'], $v['beschreibung_en'] ?: null, $v['link'] ?: null, $v['gruppe_typ'], $id]);
        } else {
            $pdo->prepare("INSERT INTO teilnehmer (typ, name, beschreibung, beschreibung_en, link, gruppe_typ) VALUES ('gruppe',?,?,?,?,?)")
                ->execute([$v['name'], $v['beschreibung'], $v['beschreibung_en'] ?: null, $v['link'] ?: null, $v['gruppe_typ']]);
            $new_id = (int) $pdo->lastInsertId();
            $kids   = array_map('intval', $_POST['kuenstler_ids'] ?? []);
            if ($kids) {
                $ins = $pdo->prepare("INSERT IGNORE INTO gruppe_kuenstler (gruppe_id, kuenstler_id) VALUES (?, ?)");
                foreach ($kids as $kid) { if ($kid) $ins->execute([$new_id, $kid]); }
            }
        }
        $_SESSION['flash'] = ['type' => 'success', 'msg' => $is_edit ? 'Änderungen gespeichert.' : ($is_copy ? 'Kopie erstellt.' : 'Gruppe erstellt.')];
        header('Location: /admin/gruppen/');
        exit;
    }
}

$members   = [];
$available = [];
if ($is_edit) {
    $stmt = db()->prepare("
        SELECT k.id, k.name FROM teilnehmer k
        JOIN gruppe_kuenstler gk ON gk.kuenstler_id = k.id
        WHERE gk.gruppe_id = ? AND k.typ = 'kuenstler' ORDER BY k.name
    ");
    $stmt->execute([$id]);
    $members = $stmt->fetchAll();

    $stmt = db()->prepare("
        SELECT k.id, k.name FROM teilnehmer k
        WHERE k.typ = 'kuenstler'
          AND k.id NOT IN (SELECT kuenstler_id FROM gruppe_kuenstler WHERE gruppe_id = ?)
        ORDER BY k.name
    ");
    $stmt->execute([$id]);
    $available = $stmt->fetchAll();
}

$all_kuenstler = [];
$preselected   = [];
if (!$is_edit) {
    $all_kuenstler = db()->query("SELECT id, name FROM teilnehmer WHERE typ='kuenstler' ORDER BY name")->fetchAll();
    if ($is_copy && $copy_id) {
        $stmt = db()->prepare("SELECT kuenstler_id FROM gruppe_kuenstler WHERE gruppe_id = ?");
        $stmt->execute([$copy_id]);
        $preselected = array_column($stmt->fetchAll(), 'kuenstler_id');
    }
}

$bilder = [];
if ($is_edit) {
    $stmt = db()->prepare("SELECT * FROM teilnehmer_bild WHERE teilnehmer_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $bilder = $stmt->fetchAll();
}

$page_title = $is_copy ? 'Gruppe kopieren' : ($is_edit ? 'Gruppe bearbeiten' : 'Neue Gruppe');

echo adminTwig()->render('gruppen/form.twig', [
    'page_title'    => $page_title,
    'nav_active'    => 'gruppen',
    'errors'        => $errors,
    'v'             => $v,
    'is_edit'       => $is_edit,
    'is_copy'       => $is_copy,
    'id'            => $id,
    'fetched_name'  => $fetched_name,
    'members'       => $members,
    'available'     => $available,
    'all_kuenstler' => $all_kuenstler,
    'preselected'   => $preselected,
    'bilder'        => $bilder,
]);
