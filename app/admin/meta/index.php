<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bewerbung_vid      = (int)($_POST['bewerbung_veranstaltung_id'] ?? 0) ?: null;
    $bewerbung_deadline = trim($_POST['bewerbung_deadline'] ?? '') ?: null;

    db()->prepare("
        UPDATE meta SET
            impressum                  = ?,
            impressum_en               = ?,
            einladung                  = ?,
            einladung_en               = ?,
            nachher_text               = ?,
            nachher_text_en            = ?,
            kontakt_tel1               = ?,
            kontakt_tel2               = ?,
            kontakt_fax                = ?,
            kontakt_email              = ?,
            kontakt_adresse            = ?,
            bewerbung_veranstaltung_id = ?,
            bewerbung_deadline         = ?
        WHERE id = 1
    ")->execute([
        $_POST['impressum']       ?? null,
        $_POST['impressum_en']    ?? null,
        $_POST['einladung']       ?? null,
        $_POST['einladung_en']    ?? null,
        $_POST['nachher_text']    ?? null,
        $_POST['nachher_text_en'] ?? null,
        trim($_POST['kontakt_tel1']    ?? '') ?: null,
        trim($_POST['kontakt_tel2']    ?? '') ?: null,
        trim($_POST['kontakt_fax']     ?? '') ?: null,
        trim($_POST['kontakt_email']   ?? '') ?: null,
        trim($_POST['kontakt_adresse'] ?? '') ?: null,
        $bewerbung_vid,
        $bewerbung_deadline,
    ]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Einstellungen gespeichert.'];
    header('Location: /admin/meta/');
    exit;
}

$meta = db()->query("SELECT * FROM meta WHERE id = 1")->fetch();
$meta = array_map(fn($v) => $v ?? '', $meta ?: []);

$veranstaltungen = db()->query("SELECT id, name FROM veranstaltung ORDER BY name")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

echo adminTwig()->render('meta/index.twig', [
    'page_title'      => 'Einstellungen',
    'nav_active'      => 'meta',
    'flash'           => $flash,
    'meta'            => $meta,
    'veranstaltungen' => $veranstaltungen,
]);
