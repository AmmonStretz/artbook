<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

function externalUrl(string $url): string {
    if (!preg_match('#^https?://#i', $url)) {
        return 'https://' . $url;
    }
    return $url;
}

function twig(): Environment {
    static $env;
    if ($env) return $env;

    $loader = new FilesystemLoader(__DIR__ . '/../templates');
    $env = new Environment($loader, [
        'autoescape' => 'html',
        'cache'      => false,
    ]);

    $lang        = detectLang();
    $rawUri      = $_SERVER['REQUEST_URI'] ?? '/';
    $uriNoLang   = preg_replace('#^/(de|en)#', '', $rawUri) ?: '/';

    // Count of public events — used for conditional nav links
    $event_count = (int)db()->query("SELECT COUNT(*) FROM veranstaltung WHERE sichtbar = 1")->fetchColumn();
    $env->addGlobal('event_count', $event_count);

    // Constants as globals
    $env->addGlobal('SITE_NAME',            SITE_NAME);
    $env->addGlobal('SITE_URL',             SITE_URL);
    $env->addGlobal('SITE_DESCRIPTION',     SITE_DESCRIPTION);
    $env->addGlobal('TEILNEHMER_KATEGORIEN',         TEILNEHMER_KATEGORIEN);
    $env->addGlobal('IMG_UPLOAD_URL',       IMG_UPLOAD_URL);
    $env->addGlobal('META_UPLOAD_URL',      META_UPLOAD_URL);
    $env->addGlobal('request_uri',          strtok($rawUri, '?'));
    $env->addGlobal('request_uri_no_lang',  strtok($uriNoLang, '?'));
    $env->addGlobal('lang',                 $lang);

    // Helper functions
    $fns = ['fmt', 'fmtTime', 'fmtWeekday', 'fmtWeekdayShort', 'bildUrl', 'thumbUrl',
            'musterFullpageUrl', 'musterFullpageSrcset', 'musterBannerUrl', 'musterBannerSrcset',
            'logoUrl', 'buildQuery'];
    foreach ($fns as $fn) {
        $env->addFunction(new TwigFunction($fn, $fn));
    }

    $env->addFunction(new TwigFunction('t',         't'));
    $env->addFunction(new TwigFunction('langUrl',   'langUrl'));
    $env->addFunction(new TwigFunction('typeLabel', 'typeLabel'));
    $env->addFilter(new TwigFilter('externalUrl', 'externalUrl'));

    return $env;
}

function adminTwig(): Environment {
    static $env;
    if ($env) return $env;

    $loader = new FilesystemLoader(__DIR__ . '/../templates/admin');
    $env = new Environment($loader, ['autoescape' => 'html', 'cache' => false]);

    $env->addGlobal('APP_NAME',             APP_NAME);
    $env->addGlobal('TEILNEHMER_KATEGORIEN',         TEILNEHMER_KATEGORIEN);
    $env->addGlobal('IMG_UPLOAD_URL',       IMG_UPLOAD_URL);
    $env->addGlobal('META_UPLOAD_URL',      META_UPLOAD_URL);
    $env->addGlobal('IMG_MIN_WIDTH',        IMG_MIN_WIDTH);
    $env->addGlobal('IMG_MAX_WIDTH',        IMG_MAX_WIDTH);
    $env->addGlobal('IMG_MUSTER_MIN_WIDTH',  IMG_MUSTER_MIN_WIDTH);
    $env->addGlobal('IMG_MUSTER_UPLOAD_URL', IMG_MUSTER_UPLOAD_URL);
    $env->addGlobal('IMG_LOGO_UPLOAD_URL',   IMG_LOGO_UPLOAD_URL);

    foreach (['fmt', 'fmtWeekday', 'bildUrl', 'thumbUrl'] as $fn) {
        $env->addFunction(new TwigFunction($fn, $fn));
    }
    $env->addFilter(new TwigFilter('externalUrl', 'externalUrl'));

    return $env;
}
