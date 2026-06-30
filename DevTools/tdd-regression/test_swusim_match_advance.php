<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_match_advance.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php'; // transitively include_once's GamestateParser + GameLogic

class _MAPlayer {
    private $seat; private $key; private $link;
    public function __construct($seat, $link) { $this->seat = $seat; $this->link = $link; $this->key = 'ak' . $seat . uniqid(); }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return $this->key; }
    public function getDeckLink() { return $this->link; }
    public function getPreconstructedDeck() { return ''; }
}

$cards = ['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010',
          'JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011',
          'JTL_102','LOF_102','SEC_102','LAW_102'];
$deckLines = ["Leader","JTL_001","Base","JTL_023","Deck"];
foreach ($cards as $c) $deckLines[] = "3 $c";
$deckLines[] = "1 JTL_103"; $deckLines[] = "1 LOF_103";
$deck = implode("\n", $deckLines);

$lobby = new stdClass(); $lobby->isPrivate = false; $lobby->format = 'premier'; $lobby->queueType = 'bo3';
$lobby->players = [ new _MAPlayer(1, $deck), new _MAPlayer(2, $deck) ];
$matchId = SWUCreateMatchFromLobby($lobby);
$g1 = (SWUReadMatch($matchId))['games'][0]['gameName'];

$checks = [];

// Simulate game 1 ending with winner 2: load g1's state, declare, run the hook.
global $gameName; $gameName = $g1;
ParseGamestate(__DIR__ . '/../../SWUSim/');
SWUDeclareGameWinner(2);
SWUAfterActionMatchHook('SWUSim', $g1);

$m = SWUReadMatch($matchId);
$checks['g1 recorded'] = (($m['games'][0]['winner'] ?? null) === 2);
// C: hook now PAUSES into sideboarding instead of spawning game 2 immediately.
$checks['sideboarding state'] = ($m['state'] ?? '') === 'sideboarding';
$checks['sideboard pointer on g1'] = is_file(SWUSideboardPointerPath($g1));
$checks['no game2 yet'] = count($m['games']) === 1;
$checks['not over yet'] = SWUMatchIsOver($m) === false;

// Both players submit (no changes); game 2 spawns.
SWUSubmitSideboardDeck($matchId, 1, $m['players']['1']['originalDeck']);
SWUSubmitSideboardDeck($matchId, 2, $m['players']['2']['originalDeck']);
SWUMaybeSpawnAfterSideboard($matchId);
$m = SWUReadMatch($matchId);
$checks['game2 spawned'] = count($m['games']) === 2 && !empty($m['games'][1]['gameName']);
$g2 = $m['games'][1]['gameName'] ?? '';
$checks['game2 matchref'] = is_array(SWUReadMatchRef($g2)) && SWUReadMatchRef($g2)['matchId'] === $matchId;
$checks['back in_progress'] = ($m['state'] ?? '') === 'in_progress';

// Simulate game 2 also won by 2 → match over.
$gameName = $g2;
ParseGamestate(__DIR__ . '/../../SWUSim/');
SWUDeclareGameWinner(2);
SWUAfterActionMatchHook('SWUSim', $g2);
$m = SWUReadMatch($matchId);
$checks['match over'] = SWUMatchIsOver($m) && SWUMatchWinner($m) === 2;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', $fails) . " m=" . json_encode($m ?? null) . "\n";
