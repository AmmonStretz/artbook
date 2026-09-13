<?php
require_once __DIR__ . '/../bootstrap.php';

$q        = trim($_GET['q'] ?? '');
$kat      = $_GET['kat'] ?? '';
$vid      = (int)($_GET['vid'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * PER_PAGE_ADMIN;

if ($kat && !array_key_exists($kat, TEILNEHMER_KATEGORIEN)) $kat = '';

$where  = ['1=1'];
$params = [];
if ($q)   { $where[] = 't.name LIKE ?';   $params[] = "%$q%"; }
if ($kat) { $where[] = 't.kategorie = ?'; $params[] = $kat; }
if ($vid) {
    $where[] = 'EXISTS (SELECT 1 FROM veranstaltung_teilnahme vx WHERE vx.teilnehmer_id = t.id AND vx.veranstaltung_id = ?)';
    $params[] = $vid;
}
$where_sql = implode(' AND ', $where);

$count_stmt = db()->prepare("SELECT COUNT(*) FROM teilnehmer t WHERE $where_sql");
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE_ADMIN));
$page  = min($page, $pages);

$stmt = db()->prepare("
    SELECT t.id, t.name, t.kategorie,
           COUNT(DISTINCT tm.mitglied_id) AS anzahl_mitglieder,
           COUNT(DISTINCT vt.veranstaltung_id) AS anzahl_veranstaltungen,
           COUNT(DISTINCT b.id) AS anzahl_bilder,
           t.created_at
    FROM teilnehmer t
    LEFT JOIN teilnehmer_mitglied tm ON tm.gruppe_id = t.id
    LEFT JOIN veranstaltung_teilnahme vt ON vt.teilnehmer_id = t.id
    LEFT JOIN teilnehmer_bild b ON b.teilnehmer_id = t.id
    WHERE $where_sql
    GROUP BY t.id
    ORDER BY t.name ASC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, PER_PAGE_ADMIN, $offset]);
$rows = $stmt->fetchAll();

$vstmt = db()->query("
    SELECT v.id, v.name, MIN(t.datum) AS erster_tag
    FROM veranstaltung v
    LEFT JOIN veranstaltung_tag t ON t.veranstaltung_id = v.id
    GROUP BY v.id
    ORDER BY erster_tag DESC, v.name
");
$veranstaltungen = $vstmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

echo adminTwig()->render('teilnehmer/index.twig', [
    'page_title' => 'Teilnehmer',
    'nav_active' => 'teilnehmer',
    'flash'      => $flash,
    'rows'       => $rows,
    'total'      => $total,
    'pages'      => $pages,
    'page'       => $page,
    'offset'     => $offset,
    'per_page'   => PER_PAGE_ADMIN,
    'q'               => $q,
    'kat'             => $kat,
    'vid'             => $vid,
    'veranstaltungen' => $veranstaltungen,
]);
