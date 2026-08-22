<?php
require_once __DIR__ . '/../bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/gruppen/'); exit; }

$stmt = db()->prepare("SELECT id, name FROM teilnehmer WHERE id = ? AND typ = 'gruppe'");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { header('Location: /admin/gruppen/'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $bilder = db()->prepare("SELECT dateiname FROM teilnehmer_bild WHERE teilnehmer_id = ?");
    $bilder->execute([$id]);
    foreach ($bilder->fetchAll() as $b) {
        $stem = pathinfo($b['dateiname'], PATHINFO_FILENAME);
        foreach (IMG_BILD_WIDTHS as $w) {
            $f = IMG_UPLOAD_DIR . $stem . '_' . $w . '.jpg';
            if (file_exists($f)) unlink($f);
        }
    }
    db()->prepare('DELETE FROM teilnehmer WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => '«' . $r['name'] . '» wurde gelöscht.'];
    header('Location: /admin/gruppen/');
    exit;
}

$stmt = db()->prepare("SELECT COUNT(*) FROM gruppe_kuenstler WHERE gruppe_id = ?");
$stmt->execute([$id]);
$kuenstler_count = (int) $stmt->fetchColumn();

echo adminTwig()->render('gruppen/delete.twig', [
    'page_title'     => 'Gruppe löschen',
    'nav_active'     => 'gruppen',
    'name'           => $r['name'],
    'kuenstler_count' => $kuenstler_count,
]);
