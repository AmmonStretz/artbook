<?php
require_once __DIR__ . '/../bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/veranstaltungen/'); exit; }

$stmt = db()->prepare('SELECT id, name, ort, plz, titelbild FROM veranstaltung WHERE id = ?');
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) { header('Location: /admin/veranstaltungen/'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    if ($v['titelbild']) {
        $stem = pathinfo($v['titelbild'], PATHINFO_FILENAME);
        foreach (IMG_TITELBILD_WIDTHS as $i => $w) {
            $suf = $i === 0 ? '.jpg' : "_{$w}.jpg";
            $f   = IMG_UPLOAD_DIR . $stem . $suf;
            if (file_exists($f)) unlink($f);
        }
    }
    db()->prepare('DELETE FROM veranstaltung WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = [
        'type' => 'success',
        'msg'  => '«' . $v['name'] . '» wurde gelöscht.',
    ];
    header('Location: /admin/veranstaltungen/');
    exit;
}

echo adminTwig()->render('veranstaltungen/delete.twig', [
    'page_title' => 'Veranstaltung löschen',
    'nav_active' => 'veranstaltungen',
    'name'       => $v['name'],
]);
