<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_queue_validation.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../SWUSim/Custom/DeckImport.php';

// Free-text format: section header and card IDs on SEPARATE lines (the header
// regex requires a line containing only "leader"/"base"/"deck"). A "<n> <id>"
// line expands to n copies. Card TYPE is irrelevant to structural validation —
// the parser assigns by section, and these IDs are all valid printings.
$leader = 'JTL_001';
$base   = 'JTL_023';
$card   = 'JTL_100';

function _deck($leader, $base, $count, $card) {
    $lines = ["Leader", $leader];
    if ($base !== null) { $lines[] = "Base"; $lines[] = $base; }
    $lines[] = "Deck";
    $lines[] = "$count $card";
    return implode("\n", $lines);
}

$valid    = SWUValidateDeckForQueue(_deck($leader, $base, 50, $card));
$noBase   = SWUValidateDeckForQueue(_deck($leader, null, 50, $card));
$tooSmall = SWUValidateDeckForQueue(_deck($leader, $base, 10, $card));

// The deck-size floor comes from the FORMAT. It used to be a hardcoded 50 that ignored the format
// entirely, so Open / Goldfish / Hotseat / Arenabot — which enforce no deck size at all — refused
// any list under 50 before a game could even be created.
$tooSmallPremier = SWUValidateDeckForQueue(_deck($leader, $base, 10, $card), '', 'premier');
$unrestricted = [];
foreach (['open', 'goldfish', 'hotseat', 'botpractice'] as $fmt) {
    $unrestricted[$fmt] = SWUValidateDeckForQueue(_deck($leader, $base, 10, $card), '', $fmt);
}
// Twin Suns' floor is 80, not 50.
$twinSuns60 = SWUValidateDeckForQueue(_deck($leader, $base, 60, $card), '', 'twinsuns');
// A leader and a base are still required everywhere — the game cannot start without them.
$openNoBase = SWUValidateDeckForQueue(_deck($leader, null, 10, $card), '', 'open');

$fails = [];
if ($valid['success'] !== true)             $fails[] = 'valid=' . json_encode($valid);
if ($noBase['success'] !== false)           $fails[] = 'noBase=' . json_encode($noBase);
if ($tooSmall['success'] !== false)         $fails[] = 'tooSmall=' . json_encode($tooSmall);
if ($tooSmallPremier['success'] !== false)  $fails[] = 'tooSmallPremier=' . json_encode($tooSmallPremier);
foreach ($unrestricted as $fmt => $r) {
    if ($r['success'] !== true)             $fails[] = "$fmt=" . json_encode($r);
}
if ($twinSuns60['success'] !== false)       $fails[] = 'twinSuns60=' . json_encode($twinSuns60);
if ($openNoBase['success'] !== false)       $fails[] = 'openNoBase=' . json_encode($openNoBase);

echo empty($fails) ? "PASS\n" : "FAIL " . implode(' ', $fails) . "\n";
