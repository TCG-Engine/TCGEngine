<?php
// Game 103 — "mocks are not showing up in the card chooser". The opponent's ASH_224 Elzar Mann search of
// the top 10 showed nine cards and one broken image: HMW_240, a PREVIEW card, and the only legal pick.
//
// Preview (mock) cards keep a plain CardID but store their art as mock_<CardID>.webp — the shared corpus
// has mock_HMW_240.webp and NO HMW_240.webp. Every client art site routes its CardID through
// resolveCardImageID() (Core/jsInclude.js), which reads window.MockCardImageIDs (emitted by
// zzCardCodeGenerator into the client dictionary) and adds the prefix. The three DECK-PEEK panels in the
// UILibraries bundle — SCRY, REVEALARRANGE and TOPDECKSEARCH — built the path by hand as
// imgBase + cardID + '.webp', so any preview card on top of a deck rendered as a broken image in all
// three. Same family as #970 (Fortify badge), #971 (zone popup) and the sideboard art test.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

// The live bundle is whichever Core/UILibraries<date>.js the page loads — take the newest, the same way
// the loader does, so a cache-bust rename never leaves this test reading a stale copy.
$bundles = glob($root . '/Core/UILibraries[0-9]*.js');
check(!empty($bundles), 'a Core/UILibraries<date>.js bundle exists');
rsort($bundles);
$js = file_get_contents($bundles[0]);
check($js !== false && $js !== '', basename($bundles[0]) . ' is readable');

foreach (['ShowScryPanel', 'ShowRevealArrangePanel', 'ShowTopDeckSearchPanel'] as $fnName) {
    $start = strpos($js, "function {$fnName}(");
    check($start !== false, "{$fnName} is present");
    // The function body runs until the next top-level function declaration.
    $end = strpos($js, "\nfunction ", $start + 10);
    $fn = substr($js, $start, ($end === false ? strlen($js) : $end) - $start);
    // Assert CODE, not prose — strip // comment bodies first (a comment naming the resolver must not
    // be able to pass for the call itself; the #971 guard was not load-bearing until it did this).
    $fn = preg_replace('~//[^\n]*~', '', $fn);

    check(preg_match('~\.src\s*=~', $fn) === 1, "{$fnName} sets an image src");
    check(strpos($fn, 'resolveCardImageID(') !== false,
          "{$fnName} resolves its art filename through resolveCardImageID (mock_ prefix for preview cards)");
    check(preg_match("~imgBase\s*\+\s*cardID\s*\+\s*['\"]\.webp~", $fn) !== 1,
          "{$fnName} no longer builds the path from the RAW CardID");
}

// The same bug in the two OTHER client art sites SWUSim reaches with a raw, preview-capable CardID:
//   • OptionChooseUI's "@<CardID>" card image — the top card shown by LAW_125 Watchful, LAW_242 Improvise,
//     SOR_246 You're My Only Hope, Ezra Bridger, C-3PO, Reinforcement Walker ("@{$topID}&Play&Leave" …);
//   • SwuCardArtSrc — the "look at the top card of your deck at any time" peek (LAW_094 Hondo Ohnaka,
//     HMW_205 Intelligence Agency).
// (IconChoiceUI / MZRearrangePopup / TwoSidedSliderUI build the same raw path but are never queued by
// SWUSim — ICONCHOICE is Grand Archive's Shifting Currents — so they are out of scope here.)
$other = [
    ['Core/OptionChooseUI.js', 'cardIDs.forEach(function(cid)', "~imgBase\s*\+\s*cid\s*\+\s*['\"]\.webp~"],
    ['Core/jsInclude.js',      'function SwuCardArtSrc(',        "~['\"]/['\"]\s*\+\s*cardID\s*\+\s*['\"]\.webp~"],
];
foreach ($other as [$file, $anchor, $rawPattern]) {
    $src = file_get_contents($root . '/' . $file);
    $start = strpos($src, $anchor);
    check($start !== false, "{$file}: '{$anchor}' is present");
    $body = preg_replace('~//[^\n]*~', '', substr($src, $start, 700));
    check(strpos($body, 'resolveCardImageID(') !== false,
          "{$file}: the art filename goes through resolveCardImageID");
    check(preg_match($rawPattern, $body) !== 1, "{$file}: no longer builds the path from the RAW CardID");
}

// The other half of the contract: the resolver still applies the prefix, and the generator still
// publishes the map it reads.
$inc = file_get_contents($root . '/Core/jsInclude.js');
check(strpos($inc, 'function resolveCardImageID') !== false, 'resolveCardImageID is defined in Core/jsInclude.js');
check(strpos($inc, "'mock_' + id") !== false, 'resolveCardImageID adds the mock_ prefix for listed cards');
$gen = file_get_contents($root . '/zzCardCodeGenerator.php');
check($gen !== false && strpos($gen, 'MockCardImageIDs') !== false, 'zzCardCodeGenerator still emits MockCardImageIDs');
echo "PASS\n";
