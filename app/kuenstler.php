<?php
require_once __DIR__ . '/src/bootstrap.php';

$per  = PER_PAGE_ARTISTS;
$page = max(1, (int)($_GET['page'] ?? 1));
$q    = trim($_GET['q'] ?? '');
$typ  = $_GET['typ'] ?? '';
$fvid = (int)($_GET['veranstaltung'] ?? 0) ?: null;

if (!in_array($typ, ['', 'kuenstler', 'gruppe'])) $typ = '';
if ($fvid) {
    $chk = db()->prepare("SELECT id FROM veranstaltung WHERE id = ? AND sichtbar = 1 AND teilnehmer_sichtbar = 1");
    $chk->execute([$fvid]);
    if (!$chk->fetch()) $fvid = null;
}

$where  = ['1=1'];
$params = [];
if ($q)    { $where[] = 't.name LIKE ?'; $params[] = "%$q%"; }
if ($typ)  { $where[] = 't.typ = ?';    $params[] = $typ; }
if ($fvid) { $where[] = 't.id IN (SELECT teilnehmer_id FROM veranstaltung_teilnahme WHERE veranstaltung_id = ?)'; $params[] = $fvid; }

$where_sql = implode(' AND ', $where);

$count = db()->prepare("SELECT COUNT(*) FROM teilnehmer t WHERE $where_sql");
$count->execute($params);
$total  = (int)$count->fetchColumn();
$pages  = max(1, (int)ceil($total / $per));
$page   = min($page, $pages);
$offset = ($page - 1) * $per;

$stmt = db()->prepare("
    SELECT t.id, t.name, t.typ, t.gruppe_typ,
           (SELECT b.dateiname FROM teilnehmer_bild b
            WHERE b.teilnehmer_id = t.id ORDER BY b.id ASC LIMIT 1) AS first_image
    FROM teilnehmer t
    WHERE $where_sql
    ORDER BY t.name ASC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, $per, $offset]);
$rows = $stmt->fetchAll();

$veranstaltungen = db()->query(
    "SELECT id, name FROM veranstaltung WHERE sichtbar=1 AND teilnehmer_sichtbar=1 ORDER BY name"
)->fetchAll();

echo twig()->render('kuenstler.twig', [
    'page_title'      => 'Mitwirkende',
    'nav_active'      => 'kuenstler',
    'rows'            => $rows,
    'total'           => $total,
    'pages'           => $pages,
    'page'            => $page,
    'q'               => $q,
    'typ'             => $typ,
    'fvid'            => $fvid,
    'veranstaltungen' => $veranstaltungen,
]);
