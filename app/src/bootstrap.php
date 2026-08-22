<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/twig.php';

if (session_status() === PHP_SESSION_NONE) session_start();
