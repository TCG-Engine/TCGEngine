<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_match_flow.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/Match/MatchFlow.php';

$spawned = [];
MatchRegisterHooks('MatchTestSim', [
    'resolveLobbyDecks' => function ($lobby) {
        return [1 => ['originalDeck'=>['A1'],'authKey'=>'a1'],
                2 => ['originalDeck'=>['B1'],'authKey'=>'a2']];
    },
    'validateDeck' => function ($d,$f){ return true; },
    'setupGame'    => function ($lobby,$opts) use (&$spawned) {
        $g = 'MTSgame' . (count($spawned)+1); $spawned[] = $g;
        @mkdir(__DIR__ . '/../../MatchTestSim/Games/' . $g, 0777, true);
        return $g;
    },
]);

$checks = [];

// create-from-lobby
$lobby = new stdClass(); $lobby->format='premier'; $lobby->queueType='bo3'; $lobby->players=[];
$matchId = MatchCreateFromLobby('MatchTestSim', $lobby);
$m = MatchRead('MatchTestSim', $matchId);
$checks['match created bestOf 3']  = ($m['bestOf'] ?? null) === 3;
$checks['currentGameNumber 1']     = ($m['currentGameNumber'] ?? null) === 1;
$checks['one game shell']          = count($m['games'] ?? []) === 1;
$gn = $m['games'][0]['gameName'] ?? '';
$ref = MatchReadRef('MatchTestSim', $gn);
$checks['ref written for game 1']  = ($ref['matchId'] ?? null) === $matchId && ($ref['gameNumber'] ?? null) === 1;

// concede ends the match
MatchConcede('MatchTestSim', $matchId, 2); // seat 2 concedes -> seat 1 clinches
$m = MatchRead('MatchTestSim', $matchId);
$checks['concede ends match'] = MatchIsOver($m) === true && MatchWinner($m) === 1;

// rematch handshake creates a new match
@mkdir(__DIR__ . '/../../MatchTestSim/Games/rg1', 0777, true);
$mid = MatchCreate('MatchTestSim','premier','bo1',[
    1=>['originalDeck'=>['A1'],'authKey'=>'a1'], 2=>['originalDeck'=>['B1'],'authKey'=>'a2']]);
MatchRecordGameResult('MatchTestSim', $mid, 'rg1', 1, 2); // bo1 -> completes
MatchRequestRematch('MatchTestSim', $mid, 1, 1, false);
MatchRequestRematch('MatchTestSim', $mid, 2, 1, false);
$newId = MatchAcceptRematch('MatchTestSim', $mid);
$checks['rematch new match id']   = is_string($newId) && $newId !== '' && $newId !== $mid;
$nm = MatchRead('MatchTestSim', $newId);
$checks['rematch bestOf 1']       = ($nm['bestOf'] ?? null) === 1;

$fail = 0; foreach ($checks as $k=>$v){ echo ($v?'PASS ':'FAIL ').$k."\n"; if(!$v)$fail++; }
echo ($fail===0?"ALL GREEN\n":"$fail FAILED\n");
