<?php
require_once __DIR__ . '/../bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/veranstaltungen/'); exit; }

$stmt = db()->prepare('SELECT id, name, ort, plz, muster, logo FROM veranstaltung WHERE id = ?');
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) { header('Location: /admin/veranstaltungen/'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    if ($v['muster']) {
        $stem = pathinfo($v['muster'], PATHINFO_FILENAME);
        $ext  = strtolower(pathinfo($v['muster'], PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            $f = IMG_MUSTER_UPLOAD_DIR . $v['muster'];
            if (file_exists($f)) unlink($f);
        } else {
            foreach (array_keys(IMG_MUSTER_FULLPAGE_SIZES) as $key) {
                $f = IMG_MUSTER_UPLOAD_DIR . $stem . '_fp_' . $key . '.webp';
                if (file_exists($f)) unlink($f);
            }
            foreach (array_keys(IMG_MUSTER_BANNER_SIZES) as $key) {
                $f = IMG_MUSTER_UPLOAD_DIR . $stem . '_bn_' . $key . '.webp';
                if (file_exists($f)) unlink($f);
            }
        }
    }
    if ($v['logo']) {
        $f = IMG_LOGO_UPLOAD_DIR . $v['logo'];
        if (file_exists($f)) unlink($f);
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
