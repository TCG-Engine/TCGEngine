<?php
// Test fixture: a match whose game 1 is FINISHED, with a known log, parked in sideboarding.
//   curl 'http://localhost:3400/TCGEngine/DevTools/tdd-regression/fixtures/swusim_make_sideboard.php'
// Prints matchId=<id> and gameName=<n>. Seat authKeys are sbk1 / sbk2.
//
// The log deliberately carries ONE RESTRICTED LINE PER SEAT, because the thing worth checking on the
// Sideboard screen is that seat 1 cannot read what seat 2 drew — game 1 is over, but games 2 and 3
// are still to be played.
//
// Local dev only, and in the web SAPI: the log writer it calls needs APCu (see swusim_write_gamelog).
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
header('Content-Type: text/plain');
$root = dirname(__DIR__, 3);
chdir($root);
require_once $root . '/SWUSim/Mod/DevGate.php';
if (!SWUIsLocalDevRequest()) { http_response_code(403); echo "Local dev only.\n"; exit; }
require_once './Core/Match/Match.php';
require_once './Core/Match/MatchFlow.php';

$B = 'http://localhost/TCGEngine/';
function _sbPost($u, $f) { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>60, CURLOPT_POST=>1, CURLOPT_POSTFIELDS=>http_build_query($f)]); $r = curl_exec($c); curl_close($c); return (string)$r; }
function _sbGet($u)      { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>60]); $r = curl_exec($c); curl_close($c); return (string)$r; }

$schema = "## GIVEN\nCommonSetup: bbw/rrk/{myResources:5; theirResources:5}\nWithGamePhase: ActionPhase\nWithActivePlayer: 1\n\n## WHEN\n\n## EXPECT\nTURNPLAYER:1\n";
$gn = json_decode(_sbPost($B . 'SWUSim/TestSchemaSetup.php', ['schema' => $schema]), true)['gameName'] ?? '';
if ($gn === '') { http_response_code(500); echo "could not create a game\n"; exit; }

$log = implode('<NL>', [
    'PLAY|ALL|P1 played [[SOR_014]]',
    'ATTACK|ALL|P1 attacked with [[SOR_014]] for 3 damage',
    'DRAW|P1|You drew [[SOR_020]]',     // Capital City  — seat 1 only
    'DRAW|P2|You drew [[SOR_078]]',     // Vanquish      — seat 2 only
    'PHASE|ALL|Regroup phase',
    'WIN|ALL|P1 won game 1',
]);
_sbGet($B . 'DevTools/tdd-regression/fixtures/swusim_write_gamelog.php?' . http_build_query(['gameName' => $gn, 'log' => $log]));

$deckJson = json_decode(file_get_contents('./SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'), true);
$d = ['leader' => $deckJson['leader']['id'], 'base' => $deckJson['base']['id'], 'mainDeck' => [], 'sideboard' => []];
foreach ($deckJson['deck'] as $e) for ($i = 0; $i < intval($e['count']); ++$i) $d['mainDeck'][] = $e['id'];

$players = ['1' => ['originalDeck' => $d, 'currentDeck' => $d, 'authKey' => 'sbk1', 'userId' => 0],
            '2' => ['originalDeck' => $d, 'currentDeck' => $d, 'authKey' => 'sbk2', 'userId' => 0]];
$mid = MatchCreate('SWUSim', 'premier', 'bo3', $players, false, null);
MatchWriteRef('SWUSim', $gn, $mid, 1);
MatchWithLock('SWUSim', $mid, function (&$m) use ($gn) {
    $m['games'][] = ['gameName' => strval($gn), 'gameNumber' => 1, 'winner' => 1];
    $m['currentGameNumber'] = 1;
    $m['state'] = 'sideboarding';
});
echo "matchId=$mid\ngameName=$gn\n";
