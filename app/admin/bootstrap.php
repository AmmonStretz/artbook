<?php
$app_root = dirname(__DIR__);
require_once $app_root . '/vendor/autoload.php';
require_once $app_root . '/config.php';
require_once $app_root . '/db.php';
require_once $app_root . '/src/lang.php';
require_once $app_root . '/src/helpers.php';
require_once $app_root . '/src/twig.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once $app_root . '/auth.php';
