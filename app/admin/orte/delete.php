<?php
require_once __DIR__ . '/../bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/orte/'); exit; }

$stmt = db()->prepare('SELECT id, name FROM veranstaltungsort WHERE id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { header('Location: /admin/orte/'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    db()->prepare('DELETE FROM veranstaltungsort WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => '«' . $r['name'] . '» wurde gelöscht.'];
    header('Location: /admin/orte/');
    exit;
}

$vc = db()->prepare('SELECT COUNT(*) FROM veranstaltung WHERE veranstaltungsort_id = ?');
$vc->execute([$id]);
$veranstaltungen_count = (int) $vc->fetchColumn();

echo adminTwig()->render('orte/delete.twig', [
    'page_title'           => 'Veranstaltungsort löschen',
    'nav_active'           => 'orte',
    'id'                   => $id,
    'name'                 => $r['name'],
    'veranstaltungen_count' => $veranstaltungen_count,
]);
