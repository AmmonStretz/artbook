<?php
// Fatal Errors (OOM etc.) als JSON zurückgeben statt als leere 500-Seite
ob_start();
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        ob_end_clean();
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['error' => 'Fatal: ' . $e['message']]);
    }
});

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../db.php';

ini_set('display_errors', '0');
ini_set('memory_limit', '512M');
set_time_limit(300);

header('Content-Type: application/json');

$vid = (int)($_POST['veranstaltung_id'] ?? 0);
if (!$vid) { ob_end_clean(); echo json_encode(['error' => 'Fehlende ID']); exit; }

$stmt = db()->prepare("SELECT id, muster FROM veranstaltung WHERE id = ?");
$stmt->execute([$vid]);
$row = $stmt->fetch();
if (!$row) { ob_end_clean(); echo json_encode(['error' => 'Ungültige ID']); exit; }

if (empty($_FILES['bild']) || $_FILES['bild']['error'] !== UPLOAD_ERR_OK) {
    ob_end_clean();
    echo json_encode(['error' => 'Upload fehlgeschlagen (Code ' . ($_FILES['bild']['error'] ?? '?') . ')']);
    exit;
}

$file    = $_FILES['bild'];
$mime    = mime_content_type($file['tmp_name']);
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
if (!in_array($mime, $allowed)) {
    ob_end_clean(); echo json_encode(['error' => 'Nur JPEG, PNG, WebP und SVG erlaubt']); exit;
}

$isSvg = ($mime === 'image/svg+xml');

if (!$isSvg) {
    if (!function_exists('imagewebp')) {
        ob_end_clean(); echo json_encode(['error' => 'WebP-Unterstützung fehlt (PHP GD ohne WebP kompiliert)']); exit;
    }
    $size = @getimagesize($file['tmp_name']);
    if (!$size) {
        ob_end_clean(); echo json_encode(['error' => 'Bild konnte nicht gelesen werden']); exit;
    }
    [$origW, $origH] = $size;
    if ($origW < IMG_MUSTER_MIN_WIDTH) {
        ob_end_clean();
        echo json_encode(['error' =>
            'Bild muss mindestens ' . IMG_MUSTER_MIN_WIDTH . ' px breit sein (dieses: ' . $origW . ' px)'
        ]); exit;
    }
}

if (!is_dir(IMG_MUSTER_UPLOAD_DIR) && !@mkdir(IMG_MUSTER_UPLOAD_DIR, 0755, true)) {
    ob_end_clean(); echo json_encode(['error' => 'Upload-Verzeichnis konnte nicht erstellt werden']); exit;
}

if ($row['muster']) {
    deleteMusterFiles($row['muster']);
}

$stem = 'IMG_' . floor(microtime(true) * 1000);

if ($isSvg) {
    $filename = $stem . '.svg';
    if (!move_uploaded_file($file['tmp_name'], IMG_MUSTER_UPLOAD_DIR . $filename)) {
        ob_end_clean(); echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']); exit;
    }
    $previewUrl = IMG_MUSTER_UPLOAD_URL . $filename;
} else {
    try {
        $src = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if (!$src) throw new \RuntimeException('GD konnte das Bild nicht laden');

        imagealphablending($src, false);
        imagesavealpha($src, true);

        foreach (IMG_MUSTER_FULLPAGE_SIZES as $key => [$w, $h]) {
            $img = cropCenter($src, $origW, $origH, $w, $h);
            if (!imagewebp($img, IMG_MUSTER_UPLOAD_DIR . $stem . '_fp_' . $key . '.webp', IMG_QUALITY)) {
                throw new \RuntimeException("Konnte _fp_{$key}.webp nicht schreiben");
            }
            imagedestroy($img);
        }

        foreach (IMG_MUSTER_BANNER_SIZES as $key => [$w, $h]) {
            $img = cropCenter($src, $origW, $origH, $w, $h);
            if (!imagewebp($img, IMG_MUSTER_UPLOAD_DIR . $stem . '_bn_' . $key . '.webp', IMG_QUALITY)) {
                throw new \RuntimeException("Konnte _bn_{$key}.webp nicht schreiben");
            }
            imagedestroy($img);
        }

        imagedestroy($src);
    } catch (\Throwable $e) {
        ob_end_clean();
        echo json_encode(['error' => 'Bildverarbeitung fehlgeschlagen: ' . $e->getMessage()]); exit;
    }

    $filename   = $stem . '.webp';
    $previewUrl = IMG_MUSTER_UPLOAD_URL . $stem . '_fp_lg.webp';
}

db()->prepare("UPDATE veranstaltung SET muster = ? WHERE id = ?")->execute([$filename, $vid]);

ob_end_clean();
echo json_encode(['ok' => true, 'url' => $previewUrl, 'is_svg' => $isSvg]);

function cropCenter($src, int $origW, int $origH, int $dstW, int $dstH) {
    $srcAr = $origW / $origH;
    $dstAr = $dstW  / $dstH;

    if ($srcAr > $dstAr) {
        $srcH = $origH;
        $srcW = (int)round($origH * $dstAr);
    } else {
        $srcW = $origW;
        $srcH = (int)round($origW / $dstAr);
    }
    $srcX = (int)(($origW - $srcW) / 2);
    $srcY = (int)(($origH - $srcH) / 2);

    $img = imagecreatetruecolor($dstW, $dstH);
    if (!$img) throw new \RuntimeException("imagecreatetruecolor({$dstW}x{$dstH}) fehlgeschlagen");
    imagealphablending($img, false);
    imagesavealpha($img, true);
    imagecopyresampled($img, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
    return $img;
}

function deleteMusterFiles(string $dateiname): void {
    $stem = pathinfo($dateiname, PATHINFO_FILENAME);
    $ext  = strtolower(pathinfo($dateiname, PATHINFO_EXTENSION));

    if ($ext === 'svg') {
        $f = IMG_MUSTER_UPLOAD_DIR . $dateiname;
        if (file_exists($f)) unlink($f);
        return;
    }

    foreach (array_keys(IMG_MUSTER_FULLPAGE_SIZES) as $key) {
        $f = IMG_MUSTER_UPLOAD_DIR . $stem . '_fp_' . $key . '.webp';
        if (file_exists($f)) unlink($f);
    }
    foreach (array_keys(IMG_MUSTER_BANNER_SIZES) as $key) {
        $f = IMG_MUSTER_UPLOAD_DIR . $stem . '_bn_' . $key . '.webp';
        if (file_exists($f)) unlink($f);
    }
}
