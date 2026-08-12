<?php
// TDD guard: once a game's result is recorded, it is SEALED — rewinding and replaying it writes nothing
// to the match. No deck W/L, no external stats submit, no re-recorded winner, no Bo3 sideboard spawn.
// The flag lives in the Match JSON, NOT the gamestate: anything in the gamestate would be rewound by
// the very undo it exists to survive.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_stats_seal.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
include_once './Core/Match/Hooks.php';
include_once './Core/Match/Match.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ── MatchGameIsSealed ────────────────────────────────────────────────────────
$m = ['games' => [
    ['gameName' => 'g1', 'winner' => 1, 'statsRecorded' => true],
    ['gameName' => 'g2', 'winner' => null],
]];
$check(MatchGameIsSealed($m, 'g1') === true,  'a recorded game reads as sealed');
$check(MatchGameIsSealed($m, 'g2') === false, 'an in-progress game is not sealed');
$check(MatchGameIsSealed($m, 'nope') === false, 'an unknown game is not sealed');
$check(MatchGameIsSealed(['games' => []], 'g1') === false, 'an empty match is not sealed');

// A legacy record written before this change has no statsRecorded key and must not read as sealed —
// otherwise an in-flight match would silently stop progressing the moment this deploys.
$legacy = ['games' => [['gameName' => 'g1', 'winner' => 1]]];
$check(MatchGameIsSealed($legacy, 'g1') === false, 'a legacy record (no flag) is NOT sealed');

// ── the stamp is set even when no stats write happens ────────────────────────
// Round-1 concedes and >2-seat games both SKIP the stats write, but their results are still final. A
// flag that tracked "a DB row was written" would leave those games re-recordable.
$src = file_get_contents('./Core/Match/Match.php');
$check(strpos($src, "['statsRecorded'] = true") !== false, 'MatchRecordGameResult stamps statsRecorded');
$stampPos = strpos($src, "['statsRecorded'] = true");
$gatePos  = strpos($src, 'if ($roundNumber === null || intval($roundNumber) >= 2)');
$check($stampPos !== false && $gatePos !== false && $stampPos < $gatePos,
    'the stamp is set BEFORE the round>=2 stats gate (a round-1 concede is still sealed)');

// ── the hook honours it ──────────────────────────────────────────────────────
$flow = file_get_contents('./Core/Match/MatchFlow.php');
$check(strpos($flow, 'MatchGameIsSealed') !== false, 'MatchAfterActionHook consults MatchGameIsSealed');
$hookPos    = strpos($flow, 'function MatchAfterActionHook');
$sealPos    = strpos($flow, 'MatchGameIsSealed', $hookPos);
$capturePos = strpos($flow, "MatchHook(\$rootName, 'captureGameDetail')", $hookPos);
$check($sealPos !== false && $capturePos !== false && $sealPos < $capturePos,
    'the seal check runs BEFORE captureGameDetail (so nothing is recomputed for a sealed game)');
$check(strpos($flow, "\$m['games'][\$i]['statsRecorded'] = true") !== false,
    'MatchConcede also stamps statsRecorded');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
