<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_match.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../AppCore/SWU/Formats.php';
include_once __DIR__ . '/../../SWUSim/Match.php';

$checks = [];

// Create a Bo3 match.
$matchId = SWUCreateMatch('SWUSim', 'premier', 'bo3', [
    1 => ['originalDeck' => ['JTL_100'], 'authKey' => 'a1'],
    2 => ['originalDeck' => ['LOF_100'], 'authKey' => 'a2'],
]);
$checks['match id returned'] = is_string($matchId) && $matchId !== '';
$m = SWUReadMatch($matchId);
$checks['match persisted']   = is_array($m) && is_file(SWUMatchPath($matchId));
$checks['bestOf 3']          = ($m['bestOf'] ?? null) === 3;
$checks['winsNeeded 2']      = ($m['winsNeeded'] ?? null) === 2;
$checks['starts in_progress']= ($m['state'] ?? null) === 'in_progress';

// Record game 1 won by seat 2.
$m = SWURecordGameResult($matchId, '101', 2);
$checks['wins after g1'] = ($m['wins']['2'] ?? 0) === 1 && ($m['wins']['1'] ?? 0) === 0;
$checks['not over after g1'] = SWUMatchIsOver($m) === false;

// IDEMPOTENT: recording the same gameName again must NOT double-count.
$m = SWURecordGameResult($matchId, '101', 2);
$checks['idempotent same game'] = ($m['wins']['2'] ?? 0) === 1;

// Record game 2 won by seat 2 → match over, winner 2.
$m = SWURecordGameResult($matchId, '102', 2);
$checks['over after g2'] = SWUMatchIsOver($m) === true;
$checks['winner is 2']   = SWUMatchWinner($m) === 2;
$checks['state complete']= ($m['state'] ?? null) === 'complete';

// A Bo1 match needs only 1 win.
$bo1 = SWUCreateMatch('SWUSim', 'premier', 'bo1', [
    1 => ['originalDeck' => [], 'authKey' => 'b1'],
    2 => ['originalDeck' => [], 'authKey' => 'b2'],
]);
$m1 = SWURecordGameResult($bo1, '201', 1);
$checks['bo1 over in one'] = SWUMatchIsOver($m1) === true && SWUMatchWinner($m1) === 1;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
