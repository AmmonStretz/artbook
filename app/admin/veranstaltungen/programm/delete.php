<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../db.php';

$vid = (int)($_GET['veranstaltung_id'] ?? 0);
$id  = (int)($_GET['id']              ?? 0);

if ($vid && $id) {
    db()->prepare("DELETE FROM veranstaltung_programm WHERE id = ? AND veranstaltung_id = ?")
        ->execute([$id, $vid]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Programmpunkt gelöscht.'];
}

$return = ($_GET['return'] ?? '') === 'form';
header($return ? "Location: /admin/veranstaltungen/form.php?id=$vid" : "Location: /admin/veranstaltungen/programm/?veranstaltung_id=$vid");
exit;
