<?php
require_once __DIR__ . '/../bootstrap.php';

$raw_ids = $_REQUEST['ids'] ?? [];
$ids = array_values(array_unique(array_filter(array_map('intval', (array)$raw_ids))));

if (count($ids) < 2) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Mindestens 2 Teilnehmer auswählen.'];
    header('Location: /admin/teilnehmer/');
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT id, name, kategorie FROM teilnehmer WHERE id IN ($placeholders) ORDER BY name");
$stmt->execute($ids);
$teilnehmer = $stmt->fetchAll();

if (count($teilnehmer) !== count($ids)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Ungültige Teilnehmer-IDs.'];
    header('Location: /admin/teilnehmer/');
    exit;
}

$kategorien     = array_values(array_unique(array_column($teilnehmer, 'kategorie')));
$same_kategorie = count($kategorien) === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$same_kategorie) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Zusammenführen nur bei gleicher Kategorie möglich.'];
        header('Location: /admin/teilnehmer/');
        exit;
    }

    $keep_id = (int)($_POST['keep_id'] ?? 0);
    if (!in_array($keep_id, array_column($teilnehmer, 'id'))) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Ungültiger verbleibender Teilnehmer.'];
        header('Location: /admin/teilnehmer/');
        exit;
    }

    $src_ids = array_filter($ids, fn($i) => $i !== $keep_id);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($src_ids as $src) {
            // Veranstaltungs-Teilnahmen übertragen (Duplikate ignorieren)
            $pdo->prepare("
                INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id, tischnummer, tisch_id)
                SELECT veranstaltung_id, ?, tischnummer, tisch_id
                FROM veranstaltung_teilnahme WHERE teilnehmer_id = ?
            ")->execute([$keep_id, $src]);

            // Bilder übertragen
            $pdo->prepare("UPDATE teilnehmer_bild SET teilnehmer_id = ? WHERE teilnehmer_id = ?")
                ->execute([$keep_id, $src]);

            // Programmpunkte übertragen (Duplikate ignorieren)
            $pdo->prepare("
                INSERT IGNORE INTO programm_teilnehmer (programm_id, teilnehmer_id)
                SELECT programm_id, ? FROM programm_teilnehmer WHERE teilnehmer_id = ?
            ")->execute([$keep_id, $src]);

            // Mitgliedschaften übertragen: src war Mitglied einer Gruppe → keep wird Mitglied
            $pdo->prepare("
                INSERT IGNORE INTO teilnehmer_mitglied (gruppe_id, mitglied_id)
                SELECT gruppe_id, ? FROM teilnehmer_mitglied WHERE mitglied_id = ? AND gruppe_id != ?
            ")->execute([$keep_id, $src, $keep_id]);

            // Mitgliedschaften übertragen: src war Gruppe → keep wird Gruppe
            $pdo->prepare("
                INSERT IGNORE INTO teilnehmer_mitglied (gruppe_id, mitglied_id)
                SELECT ?, mitglied_id FROM teilnehmer_mitglied WHERE gruppe_id = ? AND mitglied_id != ?
            ")->execute([$keep_id, $src, $keep_id]);

            // Quelle löschen (Cascade räumt verbleibende FKs auf)
            $pdo->prepare("DELETE FROM teilnehmer WHERE id = ?")->execute([$src]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Fehler: ' . $e->getMessage()];
        header('Location: /admin/teilnehmer/');
        exit;
    }

    $n = count($src_ids);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => $n . ' ' . ($n === 1 ? 'Eintrag' : 'Einträge') . ' zusammengeführt.'];
    header("Location: /admin/teilnehmer/form.php?id=$keep_id");
    exit;
}

echo adminTwig()->render('teilnehmer/merge.twig', [
    'page_title'     => 'Teilnehmer zusammenführen',
    'nav_active'     => 'teilnehmer',
    'teilnehmer'     => $teilnehmer,
    'ids'            => $ids,
    'same_kategorie' => $same_kategorie,
    'kategorien'     => $kategorien,
]);
