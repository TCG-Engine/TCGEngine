<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_match_core.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/Match/Match.php';
MatchRegisterHooks('MatchTestSim', []); // no optional hooks

$checks = [];
$matchId = MatchCreate('MatchTestSim', 'premier', 'bo3', [
    1 => ['originalDeck' => ['A1'], 'authKey' => 'a1'],
    2 => ['originalDeck' => ['B1'], 'authKey' => 'a2'],
]);
$checks['id returned']        = is_string($matchId) && $matchId !== '';
$m = MatchRead('MatchTestSim', $matchId);
$checks['persisted']          = is_array($m) && is_file(MatchPath('MatchTestSim', $matchId));
$checks['bestOf 3']           = ($m['bestOf'] ?? null) === 3;
$checks['winsNeeded 2']       = ($m['winsNeeded'] ?? null) === 2;
$checks['in_progress']        = ($m['state'] ?? null) === 'in_progress';
$checks['rootName stored']    = ($m['rootName'] ?? null) === 'MatchTestSim';

MatchRecordGameResult('MatchTestSim', $matchId, 'g1', 1, 1);
MatchRecordGameResult('MatchTestSim', $matchId, 'g1', 1, 1); // idempotent
$m = MatchRead('MatchTestSim', $matchId);
$checks['idempotent one win']  = intval($m['wins']['1'] ?? 0) === 1;
$checks['not over at 1 win']    = MatchIsOver($m) === false;

MatchRecordGameResult('MatchTestSim', $matchId, 'g2', 1, 2);
$m = MatchRead('MatchTestSim', $matchId);
$checks['over at 2 wins']       = MatchIsOver($m) === true;
$checks['winner seat 1']        = MatchWinner($m) === 1;

// sideboard state machine
$matchId2 = MatchCreate('MatchTestSim', 'premier', 'bo3', [
    1 => ['originalDeck' => ['A1'], 'authKey' => 'a1'],
    2 => ['originalDeck' => ['B1'], 'authKey' => 'a2'],
]);
MatchBeginSideboarding('MatchTestSim', $matchId2, 2);
$m2 = MatchRead('MatchTestSim', $matchId2);
$checks['state sideboarding']   = ($m2['state'] ?? null) === 'sideboarding';
$checks['loser first']          = ($m2['pendingFirstPlayer'] ?? null) === 2;
$checks['not both ready']       = MatchSideboardBothReady($m2) === false;
MatchSubmitSideboardDeck('MatchTestSim', $matchId2, 1, ['A1']);
MatchSubmitSideboardDeck('MatchTestSim', $matchId2, 2, ['B1']);
$m2 = MatchRead('MatchTestSim', $matchId2);
$checks['both ready']           = MatchSideboardBothReady($m2) === true;

$fail = 0; foreach ($checks as $k=>$v){ echo ($v?'PASS ':'FAIL ').$k."\n"; if(!$v)$fail++; }
echo ($fail===0?"ALL GREEN\n":"$fail FAILED\n");
