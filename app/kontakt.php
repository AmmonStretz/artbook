<?php
require_once __DIR__ . '/src/bootstrap.php';

$meta         = db()->query("SELECT * FROM meta WHERE id = 1")->fetch() ?: [];
$active_vid   = (int)($meta['bewerbung_veranstaltung_id'] ?? 0) ?: null;
$deadline     = $meta['bewerbung_deadline'] ?? null;
$today        = date('Y-m-d');
$in_bewerbung = $active_vid && $deadline && $today <= $deadline;

$active_event = null;
if ($active_vid) {
    $stmt = db()->prepare("SELECT id, name FROM veranstaltung WHERE id = ? AND sichtbar = 1");
    $stmt->execute([$active_vid]);
    $active_event = $stmt->fetch() ?: null;
}

$sent   = false;
$errors = [];
$post   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post['name']      = trim($_POST['name']      ?? '');
    $post['email']     = trim($_POST['email']      ?? '');
    $post['betreff']   = trim($_POST['betreff']    ?? '');
    $post['nachricht'] = trim($_POST['nachricht']  ?? '');
    $post['bewerbung_veranstaltung'] = trim($_POST['bewerbung_veranstaltung'] ?? '');
    $post['bewerbung_medium']        = trim($_POST['bewerbung_medium']        ?? '');

    if (!$post['name'])    $errors[] = 'Bitte geben Sie Ihren Namen ein.';
    if (!$post['email'] || !filter_var($post['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    if (!$post['nachricht']) $errors[] = 'Bitte geben Sie eine Nachricht ein.';

    if (!$errors) {
        $to      = $meta['kontakt_email'] ?? '';
        $subject = '[Kontaktformular] ' . ($post['betreff'] ?: 'Neue Nachricht');
        $body    = "Name: {$post['name']}\nE-Mail: {$post['email']}\n";

        if ($in_bewerbung) {
            if ($post['bewerbung_veranstaltung']) $body .= "Veranstaltung: {$post['bewerbung_veranstaltung']}\n";
            if ($post['bewerbung_medium'])        $body .= "Medium/Technik: {$post['bewerbung_medium']}\n";
            $subject = '[Bewerbung] ' . ($post['betreff'] ?: ($post['name'] . ' – ' . ($active_event['name'] ?? '')));
        }

        $body   .= "\nNachricht:\n{$post['nachricht']}";
        $headers = "From: noreply@artbook.local\r\nReply-To: {$post['email']}\r\nContent-Type: text/plain; charset=UTF-8";
        if ($to) @mail($to, $subject, $body, $headers);
        $sent = true;
    }
}

echo twig()->render('kontakt.twig', [
    'page_title'   => 'Kontakt',
    'nav_active'   => 'kontakt',
    'meta'         => $meta,
    'in_bewerbung' => $in_bewerbung,
    'active_event' => $active_event,
    'deadline'     => $deadline,
    'sent'         => $sent,
    'errors'       => $errors,
    'post'         => $post,
]);
