<?php
// Crop-window check for localized card art: puts the English and localized concat (tile) and crop images
// side by side, one row per card, so a person can see whether the fixed crop windows in zzImageConverter.php
// (tuned to English layouts) still frame a localized card correctly. Imagick only (never GD for WebP).
//
//   php DevTools/i18n-crop-compare.php locale=es cards=SOR_001,SOR_005 [out=/tmp/i18n-crop-es.png]
// CLI-only dev tool: it reads $argv directly and has no auth check of its own, so it must never be
// reachable over HTTP (it would otherwise run with attacker-controlled args and no login gate).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$args = [];
foreach (array_slice($argv, 1) as $a) { if (strpos($a, '=') !== false) { [$k, $v] = explode('=', $a, 2); $args[$k] = $v; } }
$locale = $args['locale'] ?? '';
$cards  = array_filter(explode(',', $args['cards'] ?? ''));
$out    = $args['out'] ?? "/tmp/i18n-crop-$locale.png";
if (!in_array($locale, ['es', 'it', 'fr'], true) || !$cards) { fwrite(STDERR, "usage: locale=es|it|fr cards=ID,ID\n"); exit(1); }

$root = __DIR__ . '/../AppCore/SWU/Images';
$cell = function (string $path, int $w, int $h): Imagick {
    $im = new Imagick();
    if (is_file($path)) { $im->readImage($path); $im->thumbnailImage($w, $h, true, true); }
    else { $im->newImage($w, $h, new ImagickPixel('#442222')); }
    $im->setImageFormat('png');
    return $im;
};
$sheet = new Imagick();
foreach ($cards as $id) {
    $row = new Imagick();
    foreach ([
        "$root/concat/$id.webp", "$root/i18n/$locale/concat/$id.webp",
        "$root/crops/{$id}_cropped.png", "$root/i18n/$locale/crops/{$id}_cropped.png",
    ] as $i => $p) {
        // Crop cells match the tile height so the (up to 560x175 / 350x270) crops stay large enough to judge.
        $row->addImage($i < 2 ? $cell($p, 300, 300) : $cell($p, 400, 300));
    }
    $row->resetIterator();
    $line = $row->appendImages(false);
    // The web container has no system fonts (ImageMagick's default `helvetica` fatals), so label with the
    // repo's own Montserrat and skip the label if even that is missing.
    $font = __DIR__ . '/../Assets/Montserrat.ttf';
    if (is_file($font)) {
        $label = new ImagickDraw(); $label->setFont($font); $label->setFillColor('yellow'); $label->setFontSize(22);
        $line->annotateImage($label, 8, 26, 0, $id);
    }
    $sheet->addImage($line);
}
$sheet->resetIterator();
$final = $sheet->appendImages(true);
$final->setImageFormat('png');
$final->writeImage($out);
echo "wrote $out\n";
