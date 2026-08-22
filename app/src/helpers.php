<?php

function fmt(string $d): string {
    if (!$d) return '';
    return detectLang() === 'en'
        ? date('j M Y', strtotime($d))
        : date('d.m.Y', strtotime($d));
}

function fmtTime(string $t): string {
    return $t ? substr($t, 0, 5) : '';
}

function fmtWeekday(string $d): string {
    if (!$d) return '';
    $en = date('l', strtotime($d));
    if (detectLang() === 'en') return $en;
    $map = [
        'Monday' => 'Montag', 'Tuesday' => 'Dienstag', 'Wednesday' => 'Mittwoch',
        'Thursday' => 'Donnerstag', 'Friday' => 'Freitag',
        'Saturday' => 'Samstag', 'Sunday' => 'Sonntag',
    ];
    return $map[$en] ?? $en;
}

function fmtWeekdayShort(string $d): string {
    if (!$d) return '';
    $en = date('l', strtotime($d));
    if (detectLang() === 'en') return substr($en, 0, 3);
    $map = [
        'Monday' => 'Mo', 'Tuesday' => 'Di', 'Wednesday' => 'Mi',
        'Thursday' => 'Do', 'Friday' => 'Fr', 'Saturday' => 'Sa', 'Sunday' => 'So',
    ];
    return $map[$en] ?? substr($en, 0, 3);
}

function typeLabel(string $typ, ?string $gruppe_typ = null): string {
    if ($typ === 'kuenstler') return t('type.artist');
    if ($gruppe_typ === 'verlag')  return t('type.verlag');
    if ($gruppe_typ === 'edition') return t('type.edition');
    return t('type.group');
}

function bildUrl(?string $dateiname): ?string {
    if (!$dateiname) return null;
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    $p    = IMG_UPLOAD_DIR . $stem . '_1024.jpg';
    return file_exists($p) ? IMG_UPLOAD_URL . $stem . '_1024.jpg' : IMG_UPLOAD_URL . $dateiname;
}

function thumbUrl(?string $dateiname): ?string {
    if (!$dateiname) return null;
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    $p    = IMG_UPLOAD_DIR . $stem . '_768.jpg';
    return file_exists($p) ? IMG_UPLOAD_URL . $stem . '_768.jpg' : IMG_UPLOAD_URL . $dateiname;
}

function titelbildHeroUrl(?string $dateiname): ?string {
    if (!$dateiname) return null;
    $stem  = pathinfo($dateiname, PATHINFO_FILENAME);
    $p1024 = IMG_UPLOAD_DIR . $stem . '_1024.jpg';
    return file_exists($p1024) ? IMG_UPLOAD_URL . $stem . '_1024.jpg' : IMG_UPLOAD_URL . $dateiname;
}

function titelbildCardUrl(?string $dateiname): ?string {
    if (!$dateiname) return null;
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    $p768 = IMG_UPLOAD_DIR . $stem . '_768.jpg';
    return file_exists($p768) ? IMG_UPLOAD_URL . $stem . '_768.jpg' : IMG_UPLOAD_URL . $dateiname;
}

function buildQuery(array $params): string {
    $filtered = array_filter($params, fn($v) => $v !== null && $v !== '' && $v !== 0);
    return $filtered ? '?' . http_build_query($filtered) : '?';
}
