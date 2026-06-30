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

$pass = ($valid['success'] === true)
     && ($noBase['success'] === false)
     && ($tooSmall['success'] === false);

echo $pass ? "PASS\n"
   : "FAIL valid=" . json_encode($valid) . " noBase=" . json_encode($noBase) . " tooSmall=" . json_encode($tooSmall) . "\n";
