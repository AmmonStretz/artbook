<?php

function detectLang(): string {
    return $_SERVER['REDIRECT_SITE_LANG'] ?? 'de';
}

function t(string $key): string {
    static $strings;
    if ($strings === null) {
        $strings = require __DIR__ . '/../lang/' . detectLang() . '.php';
    }
    return $strings[$key] ?? $key;
}

function langUrl(string $path): string {
    $path = '/' . ltrim($path, '/');
    return '/' . detectLang() . $path;
}
