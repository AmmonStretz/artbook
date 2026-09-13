<?php
require_once __DIR__ . '/../bootstrap.php';

$id      = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$copy_id = isset($_GET['copy']) ? (int)$_GET['copy']  : null;
$is_edit = $id !== null;
$is_copy = $copy_id !== null && !$is_edit;
$errors  = [];

$v = ['name' => '', 'beschreibung' => '', 'beschreibung_en' => '', 'link' => '', 'kategorie' => 'kuenstler'];
$fetched_name = '';

$load_id = $is_edit ? $id : ($is_copy ? $copy_id : null);
if ($load_id) {
    $stmt = db()->prepare("SELECT * FROM teilnehmer WHERE id = ?");
    $stmt->execute([$load_id]);
    $fetched = $stmt->fetch();
    if (!$fetched) { header('Location: /admin/teilnehmer/'); exit; }
    $v = array_map(fn($val) => $val ?? '', $fetched);
    $fetched_name = $fetched['name'];
    if ($is_copy) { $v['name'] .= ' (Kopie)'; $v['id'] = ''; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit) {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'add_mitglied') {
        $mid = (int)($_POST['mitglied_id'] ?? 0);
        if ($mid && $mid !== $id) {
            db()->prepare("INSERT IGNORE INTO teilnehmer_mitglied (gruppe_id, mitglied_id) VALUES (?, ?)")
                ->execute([$id, $mid]);
        }
        header("Location: /admin/teilnehmer/form.php?id=$id");
        exit;
    }

    if ($action === 'create_mitglied') {
        $name = trim($_POST['new_mitglied_name'] ?? '');
        if ($name) {
            $pdo = db();
            $pdo->prepare("INSERT INTO teilnehmer (name, kategorie) VALUES (?, 'kuenstler')")->execute([$name]);
            $new_mid = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT IGNORE INTO teilnehmer_mitglied (gruppe_id, mitglied_id) VALUES (?, ?)")->execute([$id, $new_mid]);
        }
        header("Location: /admin/teilnehmer/form.php?id=$id");
        exit;
    }

    if ($action === 'remove_mitglied') {
        $mid = (int)($_POST['mitglied_id'] ?? 0);
        if ($mid) {
            db()->prepare("DELETE FROM teilnehmer_mitglied WHERE gruppe_id = ? AND mitglied_id = ?")
                ->execute([$id, $mid]);
        }
        header("Location: /admin/teilnehmer/form.php?id=$id");
        exit;
    }

    if ($action === 'add_gruppe') {
        $gid = (int)($_POST['gruppe_id'] ?? 0);
        if ($gid && $gid !== $id) {
            db()->prepare("INSERT IGNORE INTO teilnehmer_mitglied (gruppe_id, mitglied_id) VALUES (?, ?)")
                ->execute([$gid, $id]);
        }
        header("Location: /admin/teilnehmer/form.php?id=$id");
        exit;
    }

    if ($action === 'remove_gruppe') {
        $gid = (int)($_POST['gruppe_id'] ?? 0);
        if ($gid) {
            db()->prepare("DELETE FROM teilnehmer_mitglied WHERE gruppe_id = ? AND mitglied_id = ?")
                ->execute([$gid, $id]);
        }
        header("Location: /admin/teilnehmer/form.php?id=$id");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'save') === 'save') {
    $v['name']            = trim($_POST['name']           ?? '');
    $v['beschreibung']    = $_POST['beschreibung']        ?? '';
    $v['beschreibung_en'] = $_POST['beschreibung_en']     ?? '';
    $v['link']            = trim($_POST['link']            ?? '');
    $v['kategorie']       = trim($_POST['kategorie']       ?? 'kuenstler');

    if (!$v['name']) $errors[] = 'Name ist ein Pflichtfeld.';
    if (!array_key_exists($v['kategorie'], TEILNEHMER_KATEGORIEN)) $errors[] = 'Ungültige Kategorie.';

    if (!$errors) {
        $pdo = db();
        if ($is_edit) {
            $old = $pdo->prepare("SELECT kategorie FROM teilnehmer WHERE id = ?");
            $old->execute([$id]);
            $old_kat = $old->fetchColumn();
            $new_kat = $v['kategorie'];

            $removed_relations = 0;
            if ($old_kat !== $new_kat) {
                if ($old_kat !== 'kuenstler' && $new_kat === 'kuenstler') {
                    // Gruppe → Künstler: Mitglieder-Relationen entfernen
                    $del = $pdo->prepare("DELETE FROM teilnehmer_mitglied WHERE gruppe_id = ?");
                    $del->execute([$id]);
                    $removed_relations = $del->rowCount();
                } elseif ($old_kat === 'kuenstler' && $new_kat !== 'kuenstler') {
                    // Künstler → Gruppe: Gruppen-Zuordnungen entfernen
                    $del = $pdo->prepare("DELETE FROM teilnehmer_mitglied WHERE mitglied_id = ?");
                    $del->execute([$id]);
                    $removed_relations = $del->rowCount();
                }
            }

            $pdo->prepare("UPDATE teilnehmer SET name=?, beschreibung=?, beschreibung_en=?, link=?, kategorie=? WHERE id=?")
                ->execute([$v['name'], $v['beschreibung'] ?: null, $v['beschreibung_en'] ?: null, $v['link'] ?: null, $new_kat, $id]);
        } else {
            $removed_relations = 0;
            $pdo->prepare("INSERT INTO teilnehmer (name, beschreibung, beschreibung_en, link, kategorie) VALUES (?,?,?,?,?)")
                ->execute([$v['name'], $v['beschreibung'] ?: null, $v['beschreibung_en'] ?: null, $v['link'] ?: null, $v['kategorie']]);
            $new_id = (int) $pdo->lastInsertId();
            if ($is_copy && $copy_id) {
                $kids = db()->prepare("SELECT mitglied_id FROM teilnehmer_mitglied WHERE gruppe_id = ?");
                $kids->execute([$copy_id]);
                $ins = $pdo->prepare("INSERT IGNORE INTO teilnehmer_mitglied (gruppe_id, mitglied_id) VALUES (?, ?)");
                foreach ($kids->fetchAll() as $k) { $ins->execute([$new_id, $k['mitglied_id']]); }
            }
            $id = $new_id;
        }
        $msg = $is_edit ? 'Änderungen gespeichert.' : ($is_copy ? 'Kopie erstellt.' : 'Teilnehmer erstellt.');
        if ($removed_relations > 0) {
            $msg .= ' ' . $removed_relations . ' Relation' . ($removed_relations === 1 ? '' : 'en') . ' wurden durch den Kategorie-Wechsel entfernt.';
        }
        $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
        header('Location: /admin/teilnehmer/');
        exit;
    }
}

$is_gruppe      = isset($v['kategorie']) && $v['kategorie'] !== 'kuenstler';
$members        = [];
$mitglied_bei   = [];
$available      = [];
$bilder         = [];
$veranstaltungen = [];

if ($is_edit) {
    if ($is_gruppe) {
        // Gruppe: zeige Mitglieder
        $stmt = db()->prepare("
            SELECT k.id, k.name, k.kategorie FROM teilnehmer k
            JOIN teilnehmer_mitglied tm ON tm.mitglied_id = k.id
            WHERE tm.gruppe_id = ? ORDER BY k.name
        ");
        $stmt->execute([$id]);
        $members = $stmt->fetchAll();

        $stmt = db()->prepare("
            SELECT id, name, kategorie FROM teilnehmer
            WHERE kategorie = 'kuenstler'
              AND id NOT IN (SELECT mitglied_id FROM teilnehmer_mitglied WHERE gruppe_id = ?)
            ORDER BY name
        ");
        $stmt->execute([$id]);
        $available = $stmt->fetchAll();
    } else {
        // Künstler: zeige Gruppen, bei denen er Mitglied ist
        $stmt = db()->prepare("
            SELECT g.id, g.name, g.kategorie FROM teilnehmer g
            JOIN teilnehmer_mitglied tm ON tm.gruppe_id = g.id
            WHERE tm.mitglied_id = ? ORDER BY g.name
        ");
        $stmt->execute([$id]);
        $mitglied_bei = $stmt->fetchAll();

        $stmt = db()->prepare("
            SELECT id, name, kategorie FROM teilnehmer
            WHERE kategorie != 'kuenstler'
              AND id NOT IN (SELECT gruppe_id FROM teilnehmer_mitglied WHERE mitglied_id = ?)
            ORDER BY name
        ");
        $stmt->execute([$id]);
        $available = $stmt->fetchAll();
    }

    $stmt = db()->prepare("SELECT * FROM teilnehmer_bild WHERE teilnehmer_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $bilder = $stmt->fetchAll();

    $stmt = db()->prepare("
        SELECT v.id, v.name, MIN(t.datum) AS erster_tag
        FROM veranstaltung v
        JOIN veranstaltung_teilnahme vt ON vt.veranstaltung_id = v.id
        LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
        WHERE vt.teilnehmer_id = ?
        GROUP BY v.id
        ORDER BY erster_tag DESC
    ");
    $stmt->execute([$id]);
    $veranstaltungen = $stmt->fetchAll();
}

$page_title = $is_copy ? 'Teilnehmer kopieren' : ($is_edit ? 'Teilnehmer bearbeiten' : 'Neuer Teilnehmer');

echo adminTwig()->render('teilnehmer/form.twig', [
    'page_title'    => $page_title,
    'nav_active'    => 'teilnehmer',
    'errors'        => $errors,
    'v'             => $v,
    'is_edit'       => $is_edit,
    'is_copy'       => $is_copy,
    'is_gruppe'     => $is_gruppe,
    'id'            => $id,
    'fetched_name'  => $fetched_name,
    'members'       => $members,
    'mitglied_bei'  => $mitglied_bei,
    'available'     => $available,
    'bilder'          => $bilder,
    'veranstaltungen' => $veranstaltungen,
]);
