<?php
// RUN VIA CLI, from either box (the corpus is shared, so one run covers both apps):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swu_card_art_corpus.php
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1   php DevTools/tdd-regression/test_swu_card_art_corpus.php
//
// Sweeps AppCore/SWU/Images/WebpImages/ and asserts every file sits on its class's deterministic
// canvas (628x450 landscape for Base/Leader, 450x628 portrait for everything else). This FAILS
// until the art is regenerated with overwriteImages=1 — that is the point: it is the acceptance
// gate for the regeneration, not a unit test.
//
// Before 2026-08-05 this took an app= argument and swept each app's own tree; there is now one
// corpus, and every file in it is named by SET_NNN (backs as "<id>_back", previews as "mock_<id>").
//
// Design: docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md §1, §5
header('Content-Type: text/plain');
require_once __DIR__ . '/../../zzImageConverter.php';
require_once __DIR__ . '/../../AppCore/SWU/CardImagePath.php';

// Either app's dictionary describes the same SET_NNN universe; take whichever this box has.
$dict = null;
foreach (['SWUDeck', 'SWUSim'] as $app) {
    $p = __DIR__ . '/../../' . $app . '/GeneratedCode/GeneratedCardDictionaries.php';
    if (file_exists($p)) { $dict = $p; break; }
}
if ($dict === null) { echo "FAIL: no generated card dictionary on this box\n"; exit; }
require_once $dict;

$mocks = _SWUCardImageMocks();

// A Leader with no unit-side stats is a flip leader: its back is another LEADER face.
function _isFlipLeader($cardID) {
    if (CardType($cardID) !== 'Leader') return false;
    $p = CardPower($cardID); $h = CardHp($cardID);
    return ($p === null || $p === '') && ($h === null || $h === '');
}

function _expectedTypeFor($stem, $mocks) {
    $isBack = false;
    if (substr($stem, -5) === '_back') { $isBack = true; $stem = substr($stem, 0, -5); }
    if (strpos($stem, 'mock_') === 0) {
        $mid = substr($stem, 5);
        $t = $mocks[$mid]['type'] ?? '';
        if (!$isBack) return $t;
        // Mirrors the generator's mock back-type rule (zzCardCodeGenerator.php, mock art block).
        if ($t !== 'Leader') return $t;
        $flip = empty($mocks[$mid]['power']) && empty($mocks[$mid]['hp']);
        return $flip ? 'Leader' : 'LeaderUnit';
    }
    $t = CardType($stem);
    if ($t === null) return null;                 // not in the dictionary — skipped, reported
    if (!$isBack) return $t;
    // The generator's official back-type rule is UNCONDITIONAL (zzCardCodeGenerator.php:337-339):
    //   $backType = $isFlipLeader ? "Leader" : "LeaderUnit";
    // so EVERY back side is a portrait LeaderUnit unless it is a double-leader-face flip card —
    // including the handful of Bases that ship an artBack. This test asserts what the generator
    // does; whether a Base's back ought to be landscape is a separate product question.
    return _isFlipLeader($stem) ? 'Leader' : 'LeaderUnit';
}

$files = glob(SWU_IMAGE_FS_ROOT . '/WebpImages/*.webp');
$checked = 0; $skipped = []; $bad = [];
foreach ($files as $f) {
    $stem = basename($f, '.webp');
    $type = _expectedTypeFor($stem, $mocks);
    if ($type === null) { $skipped[] = $stem; continue; }
    list($ew, $eh) = _cardCanvas($type);
    $im = new Imagick($f);
    $w = $im->getImageWidth(); $h = $im->getImageHeight();
    $im->clear();
    $checked++;
    if ($w !== $ew || $h !== $eh) $bad[] = "$stem($type) {$w}x{$h} != {$ew}x{$eh}";
}

$checks = [];
$checks['found card art'] = $checked > 0;
$checks['every file is on its canonical canvas'] = count($bad) === 0;
// The old per-app trees are gone; a file reappearing there means something still writes to them.
$stale = [];
foreach (['SWUDeck/concat', 'SWUDeck/crops', 'SWUSim/concat', 'SWUSim/crops', 'SWUSim/WebpImages'] as $d) {
    if (is_dir(__DIR__ . '/../../' . $d)) $stale[] = $d;
}
$checks['no per-app art tree has reappeared'] = count($stale) === 0;

echo "checked $checked file(s), skipped " . count($skipped) . " (not in dictionary)\n";
if ($bad) {
    echo "OFF-CANVAS (" . count($bad) . "):\n";
    foreach (array_slice($bad, 0, 25) as $b) echo "  $b\n";
    if (count($bad) > 25) echo "  ... and " . (count($bad) - 25) . " more\n";
}
if ($skipped) echo "SKIPPED sample: " . implode(', ', array_slice($skipped, 0, 8)) . "\n";
if ($stale) echo "STALE TREES: " . implode(', ', $stale) . "\n";

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
