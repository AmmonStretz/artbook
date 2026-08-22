<?php

// On shared hosting: create db.local.php with $db_host, $db_name, $db_user, $db_pass
if (file_exists(__DIR__ . '/db.local.php')) {
    require __DIR__ . '/db.local.php';
} else {
    $db_host = getenv('DB_HOST') ?: 'db';
    $db_name = getenv('DB_NAME') ?: 'artbook';
    $db_user = getenv('DB_USER')     ?: '';
    $db_pass = getenv('DB_PASSWORD') ?: '';
}

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        global $db_host, $db_name, $db_user, $db_pass;
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db_host, $db_name);
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
