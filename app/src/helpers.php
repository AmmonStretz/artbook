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

function typeLabel(?string $kategorie): string {
    return TEILNEHMER_KATEGORIEN[$kategorie ?? 'kuenstler'] ?? ucfirst($kategorie ?? 'Künstler');
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

function musterIsSvg(?string $dateiname): bool {
    return $dateiname !== null && strtolower(pathinfo($dateiname, PATHINFO_EXTENSION)) === 'svg';
}

function musterFullpageUrl(?string $dateiname, string $size = 'lg'): ?string {
    if (!$dateiname) return null;
    if (musterIsSvg($dateiname)) return IMG_MUSTER_UPLOAD_URL . $dateiname;
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    return IMG_MUSTER_UPLOAD_URL . $stem . '_fp_' . $size . '.webp';
}

function musterFullpageSrcset(?string $dateiname): string {
    if (!$dateiname || musterIsSvg($dateiname)) return '';
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    return implode(', ', array_map(
        fn($key, $wh) => IMG_MUSTER_UPLOAD_URL . $stem . '_fp_' . $key . '.webp ' . $wh[0] . 'w',
        array_keys(IMG_MUSTER_FULLPAGE_SIZES),
        IMG_MUSTER_FULLPAGE_SIZES
    ));
}

function musterBannerUrl(?string $dateiname, string $size = 'lg'): ?string {
    if (!$dateiname) return null;
    if (musterIsSvg($dateiname)) return IMG_MUSTER_UPLOAD_URL . $dateiname;
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    return IMG_MUSTER_UPLOAD_URL . $stem . '_bn_' . $size . '.webp';
}

function musterBannerSrcset(?string $dateiname): string {
    if (!$dateiname || musterIsSvg($dateiname)) return '';
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    return implode(', ', array_map(
        fn($key, $wh) => IMG_MUSTER_UPLOAD_URL . $stem . '_bn_' . $key . '.webp ' . $wh[0] . 'w',
        array_keys(IMG_MUSTER_BANNER_SIZES),
        IMG_MUSTER_BANNER_SIZES
    ));
}

function logoUrl(?string $dateiname): ?string {
    if (!$dateiname) return null;
    return IMG_LOGO_UPLOAD_URL . $dateiname;
}

function buildQuery(array $params): string {
    $filtered = array_filter($params, fn($v) => $v !== null && $v !== '' && $v !== 0);
    return $filtered ? '?' . http_build_query($filtered) : '?';
}
