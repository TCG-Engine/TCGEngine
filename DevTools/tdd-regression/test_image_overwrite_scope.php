<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_image_overwrite_scope.php
//
// overwriteImages scoping, shared by zzCardCodeGenerator.php and zzCardI18nImageGenerator.php:
//   overwriteImages=1 (or true) -> replace every image (unchanged behaviour)
//   overwriteImages=HMW        -> replace only that set's images: HMW_001, HMW_001_back, HMW_T01, mock_HMW_004
//   anything else              -> an error, so a typo never silently replaces nothing (or everything)
header('Content-Type: text/plain');
require_once __DIR__ . '/../../zzImageConverter.php';

$fails = 0;
function check($name, $cond) { global $fails; echo ($cond ? "  ok: " : "  FAIL: ") . $name . "\n"; if (!$cond) $fails++; }

// ── parsing ────────────────────────────────────────────────────────────────
$none = ImageOverwriteSpec('');
check('empty value replaces nothing', $none['mode'] === 'none' && $none['error'] === null);
check('missing value (null) replaces nothing', ImageOverwriteSpec(null)['mode'] === 'none');
check('0 replaces nothing', ImageOverwriteSpec('0')['mode'] === 'none');
check('1 replaces everything', ImageOverwriteSpec('1')['mode'] === 'all');
check('true replaces everything', ImageOverwriteSpec('true')['mode'] === 'all');

$hmw = ImageOverwriteSpec('HMW');
check('HMW is a set scope', $hmw['mode'] === 'set' && $hmw['set'] === 'HMW' && $hmw['error'] === null);
check('lowercase is uppercased', ImageOverwriteSpec('hmw')['set'] === 'HMW');
check('surrounding spaces are trimmed', ImageOverwriteSpec(' TS26 ')['set'] === 'TS26');

foreach (['HMW,IC27', '../x', 'H', 'TOOLONG', 'HM W', 'HMW_', 'HMW;SOR'] as $bad) {
    $s = ImageOverwriteSpec($bad);
    check('rejects ' . json_encode($bad), $s['error'] !== null && $s['mode'] === 'none');
}

// ── matching ───────────────────────────────────────────────────────────────
check('all: any card', ImageOverwriteRequested(ImageOverwriteSpec('1'), 'SOR_001'));
check('all: non-SWU ids too', ImageOverwriteRequested(ImageOverwriteSpec('1'), 'bEXmm4rKOs'));
check('none: nothing', !ImageOverwriteRequested($none, 'HMW_001'));
foreach (['HMW_001', 'HMW_001_back', 'HMW_T01', 'mock_HMW_004', 'mock_HMW_004_back'] as $id) {
    check("HMW matches $id", ImageOverwriteRequested($hmw, $id));
}
foreach (['SOR_001', 'HMWX_001', 'XHMW_001', 'mock_SOR_004', 'HMW', 'bEXmm4rKOs', ''] as $id) {
    check("HMW does not match " . json_encode($id), !ImageOverwriteRequested($hmw, $id));
}
check('TS26 (two-digit set) matches TS26_34', ImageOverwriteRequested(ImageOverwriteSpec('TS26'), 'TS26_34'));
check('an errored spec replaces nothing', !ImageOverwriteRequested(ImageOverwriteSpec('HMW,IC27'), 'HMW_001'));

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
