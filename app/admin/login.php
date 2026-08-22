<?php
$app_root = dirname(__DIR__);
require_once $app_root . '/vendor/autoload.php';
require_once $app_root . '/config.php';
require_once $app_root . '/src/helpers.php';
require_once $app_root . '/src/twig.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['logged_in'])) {
    header('Location: /admin/');
    exit;
}

$error    = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    if ($username === AUTH_USER && ($_POST['password'] ?? '') === AUTH_PASS) {
        $_SESSION['logged_in'] = true;
        header('Location: /admin/');
        exit;
    }
    $error = 'Benutzername oder Passwort falsch.';
}

echo adminTwig()->render('login.twig', [
    'error'    => $error,
    'username' => $username,
]);
