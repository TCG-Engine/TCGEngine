<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swu_card_art_canvas.php
//
// The card-art canvas must be DETERMINISTIC per card class. Imagick's resizeImage(..., bestfit:true)
// is CSS "contain": it guarantees the image FITS the box, not that it EQUALS it, so per-card source
// aspect variance produced 450x628 / 449x628 / 450x627 across one pool. concat/ and crops/ cut fixed
// pixel windows out of that source, so every derivative inherited the drift.
//
// Design: docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md §5
header('Content-Type: text/plain');
require_once __DIR__ . '/../../zzImageConverter.php';

$checks = [];

// ── _cardCanvas: Leader and Base are the only landscape classes ──────────────
$checks['Leader is landscape']      = _cardCanvas('Leader') === [628, 450];
$checks['Base is landscape']        = _cardCanvas('Base')   === [628, 450];

// A leader's deployed unit side is portrait like any other unit.
$checks['LeaderUnit is portrait']   = _cardCanvas('LeaderUnit')     === [450, 628];
$checks['Unit is portrait']         = _cardCanvas('Unit')           === [450, 628];
$checks['Event is portrait']        = _cardCanvas('Event')          === [450, 628];
$checks['Upgrade is portrait']      = _cardCanvas('Upgrade')        === [450, 628];
$checks['Token Unit is portrait']   = _cardCanvas('Token Unit')     === [450, 628];
$checks['Token Upgrade is portrait']= _cardCanvas('Token Upgrade')  === [450, 628];

// An unknown or absent type must still get a deterministic canvas — this is the mock-art bug:
// zzCardCodeGenerator passed "" for mocks, which fell through to the non-deterministic branch.
$checks['empty type is portrait']   = _cardCanvas('')      === [450, 628];
$checks['unknown type is portrait'] = _cardCanvas('Nonsense') === [450, 628];

// ── _normalizeCardCanvas: exact output regardless of source aspect ───────────
// Synthetic sources only — no download, no filesystem, fully deterministic.
function _mkImage($w, $h) {
    $im = new Imagick();
    $im->newImage($w, $h, new ImagickPixel('red'));
    $im->setImageFormat('png');
    return $im;
}
function _canvasOf($w, $h, $type) {
    $im = _mkImage($w, $h);
    _normalizeCardCanvas($im, $type);
    $out = [$im->getImageWidth(), $im->getImageHeight()];
    $im->clear();
    return $out;
}

// The three real-world drift shapes from the 2026-08-04 census must all land on 450x628.
$checks['unit 450x627 -> 450x628'] = _canvasOf(450, 627, 'Unit') === [450, 628];
$checks['unit 449x628 -> 450x628'] = _canvasOf(449, 628, 'Unit') === [450, 628];
$checks['unit 450x628 stays']      = _canvasOf(450, 628, 'Unit') === [450, 628];
// A wildly off-aspect source still lands exactly on the canvas (cover-crops the overflow).
$checks['unit 1000x1000 -> 450x628'] = _canvasOf(1000, 1000, 'Unit') === [450, 628];

// Leader/Base land on 628x450, and a portrait source is rotated first.
$checks['leader 627x450 -> 628x450']  = _canvasOf(627, 450, 'Leader') === [628, 450];
$checks['leader 628x449 -> 628x450']  = _canvasOf(628, 449, 'Leader') === [628, 450];
$checks['base portrait is rotated']   = _canvasOf(450, 628, 'Base')   === [628, 450];

// A LeaderUnit source arrives landscape and must be rotated to portrait.
$checks['leaderunit landscape rotated'] = _canvasOf(628, 450, 'LeaderUnit') === [450, 628];

// The mock-art case: an empty type must still be deterministic.
$checks['empty type 450x320 -> 450x628'] = _canvasOf(450, 320, '') === [450, 628];

// ── The mock-art call site must pass a real type ─────────────────────────────
// Guard against regressing to CheckImage("mock_...", $front, "", ...), which skipped the Leader
// rotation entirely and produced 450x320 mock leaders against 628x450 real ones.
$gen = file_get_contents(__DIR__ . '/../../zzCardCodeGenerator.php');
$checks['generator loads mock defs'] = strpos($gen, '$mockDefs = SWULoadMockCards();') !== false;
$checks['mock front art passes a type'] =
    preg_match('/CheckImage\("mock_" \. \$mockID, \$front, \$def\[.type.\]/', $gen) === 1;
$checks['mock back art passes a type'] =
    preg_match('/CheckImage\("mock_" \. \$mockID \. "_back", \$back, \$/', $gen) === 1;
$checks['no empty-string type left at the mock call sites'] =
    preg_match('/CheckImage\("mock_[^)]*?, ""\s*,\s*""/', $gen) !== 1;

// Every mock in the tracked source must carry a type _cardCanvas can act on.
$mockDefsForTest = require __DIR__ . '/../../AppCore/SWU/CardMocks.php';
$typedOK = true;
foreach ($mockDefsForTest as $id => $d) { if (!isset($d['type']) || $d['type'] === '') $typedOK = false; }
$checks['every mock card has a type'] = $typedOK;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
