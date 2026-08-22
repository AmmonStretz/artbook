<?php
require_once __DIR__ . '/../bootstrap.php';

$id      = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$copy_id = isset($_GET['copy']) ? (int)$_GET['copy']  : null;
$is_edit = $id !== null;
$is_copy = $copy_id !== null && !$is_edit;
$errors  = [];

$v = ['name' => '', 'beschreibung' => '', 'beschreibung_en' => '', 'link' => ''];
$fetched_name = '';

$load_id = $is_edit ? $id : ($is_copy ? $copy_id : null);
if ($load_id) {
    $stmt = db()->prepare("SELECT * FROM teilnehmer WHERE id = ? AND typ = 'kuenstler'");
    $stmt->execute([$load_id]);
    $fetched = $stmt->fetch();
    if (!$fetched) { header('Location: /admin/kuenstler/'); exit; }
    $v = array_map(fn($val) => $val ?? '', $fetched);
    $fetched_name = $fetched['name'];
    if ($is_copy) { $v['name'] .= ' (Kopie)'; $v['id'] = ''; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['name']            = trim($_POST['name']           ?? '');
    $v['beschreibung']    = $_POST['beschreibung']        ?? '';
    $v['beschreibung_en'] = $_POST['beschreibung_en']     ?? '';
    $v['link']            = trim($_POST['link']            ?? '');

    if (!$v['name']) $errors[] = 'Name ist ein Pflichtfeld.';

    if (!$errors) {
        $pdo = db();
        if ($is_edit) {
            $pdo->prepare("UPDATE teilnehmer SET name=?, beschreibung=?, beschreibung_en=?, link=? WHERE id=? AND typ='kuenstler'")
                ->execute([$v['name'], $v['beschreibung'], $v['beschreibung_en'] ?: null, $v['link'] ?: null, $id]);
        } else {
            $pdo->prepare("INSERT INTO teilnehmer (typ, name, beschreibung, beschreibung_en, link) VALUES ('kuenstler',?,?,?,?)")
                ->execute([$v['name'], $v['beschreibung'], $v['beschreibung_en'] ?: null, $v['link'] ?: null]);
        }
        $_SESSION['flash'] = ['type' => 'success', 'msg' => $is_edit ? 'Änderungen gespeichert.' : ($is_copy ? 'Kopie erstellt.' : 'Künstler erstellt.')];
        header('Location: /admin/kuenstler/');
        exit;
    }
}

$bilder = [];
if ($is_edit) {
    $stmt = db()->prepare("SELECT * FROM teilnehmer_bild WHERE teilnehmer_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $bilder = $stmt->fetchAll();
}

$page_title = $is_copy ? 'Künstler kopieren' : ($is_edit ? 'Künstler bearbeiten' : 'Neuer Künstler');

echo adminTwig()->render('kuenstler/form.twig', [
    'page_title'   => $page_title,
    'nav_active'   => 'kuenstler',
    'errors'       => $errors,
    'v'            => $v,
    'is_edit'      => $is_edit,
    'is_copy'      => $is_copy,
    'id'           => $id,
    'bilder'       => $bilder,
    'fetched_name' => $fetched_name,
]);
