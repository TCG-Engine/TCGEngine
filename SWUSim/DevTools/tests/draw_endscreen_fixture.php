<?php
// REGRESSION-SKIP: a fixture endpoint for the browser harness, not a test (it builds a drawn match and prints its ids).
// Test fixture for the drawn-game end screen (DevTools/ui-harness/swusim-draw-endscreen-xbrowser.mjs). Builds a STARTED
// public 1v1 premier match the way the real paths do (see persistent_lobby_fixture.php), then ends its game 1 as a DRAW
// through the engine's own commit point (SWUDeclareGameDraw — what two bases falling together reaches) and lets the
// match layer record it (MatchAfterActionHook), exactly as the end of a real action would.
//   &queue=bo3   a Best of 3 (game 1 drawn: the series goes on); default Best of 1 (a drawn match)
//   &scenario=lastwon   a Bo3 that went Draw, P1, then THIS game won by P2: a drawn MATCH whose last game was decided
//   &scenario=unrecorded   the game is drawn but the match layer has not recorded it yet (the gap between the
//                          gamestate write and the after-action hook) — the board alone knows it is a draw
// Moderators only. Prints {gameName, matchId, keys:{1,2}}.
header('Content-Type: application/json');
chdir(__DIR__ . '/../../..');
include_once './AccountFiles/AccountSessionAPI.php';
$authError = CheckLoggedInUserMod();
if ($authError !== null && $authError !== '' && $authError !== false) { echo json_encode(['error' => 'moderators only']); exit; }
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
include_once './Core/NetworkingLibraries.php';
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/Rooms.php';   // the lobby Player class
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/TurnController.php';
include_once './SWUSim/Custom/GameLogic.php';
include_once './SWUSim/Custom/CombatLogic.php';

$deck = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));
$lobby = new stdClass();
$lobby->id = 'drfx' . bin2hex(random_bytes(5));
$lobby->rootName = 'SWUSim'; $lobby->format = 'premier'; $lobby->queueType = ($_GET['queue'] ?? '') === 'bo3' ? 'bo3' : 'bo1';
$lobby->isPrivate = false; $lobby->maxPlayers = 2; $lobby->numPlayers = 2; $lobby->hostPlayerID = 1;
$lobby->casterMode = false; $lobby->ready = true; $lobby->state = 'starting'; $lobby->inviteCode = '';
$a = new Player(1, $deck, '', null); $b = new Player(2, $deck, '', null);
foreach ([$a, $b] as $p) { $p->setDeckOk(true); $p->setReady(true); $p->touch(); }
$lobby->players = [$a, $b];
apcu_store($lobby->id, $lobby, LOBBY_TTL_SECONDS);
$matchId = SWUCreateMatchFromLobby($lobby);
$game = strval($lobby->gameName ?? '');
if (!$matchId || $game === '') { echo json_encode(['error' => 'could not create the match']); exit; }
if (function_exists('RegisterActiveGame')) RegisterActiveGame('SWUSim', $game, false);
LobbyCommitGameCreation($lobby->id, $lobby, 'started');

$scenario = strval($_GET['scenario'] ?? '');
if ($scenario === 'lastwon') {
    // Two decided games before this one: game 1 drawn, game 2 won by P1. This game is game 3.
    MatchWithLock('SWUSim', strval($matchId), function (&$m) {
        $m['bestOf'] = 3; $m['winsNeeded'] = 2; $m['queueType'] = 'bo3';
        array_unshift($m['games'], ['gameName' => 'drfxg1', 'gameNumber' => 1, 'winner' => 0, 'winners' => [], 'draw' => true, 'statsRecorded' => true],
                                   ['gameName' => 'drfxg2', 'gameNumber' => 2, 'winner' => 1, 'winners' => [1], 'statsRecorded' => true]);
        $m['games'][2]['gameNumber'] = 3;
        $m['wins']['1'] = 1;
    });
}
// End the game through the engine — a draw, or (lastwon) a P2 win — and let the match layer see it.
global $gameName, $playerID;
$gameName = $game; $playerID = 1;
ParseGamestate('./SWUSim/');
if ($scenario === 'lastwon') SWUDeclareGameWinner(2, "GAMEOVER:Player 1's base has been defeated! Player 2 wins!", "P1's base was defeated");
else SWUDeclareGameDraw("GAMEOVER:Both bases have been defeated! The game is a draw!", 'each base was defeated');
WriteGamestate('./SWUSim/');
if ($scenario !== 'unrecorded') MatchAfterActionHook('SWUSim', $game);

echo json_encode(['gameName' => $game, 'matchId' => strval($matchId), 'keys' => ['1' => $a->getAuthKey(), '2' => $b->getAuthKey()],
                  'match' => SWUReadMatch(strval($matchId))['state'] ?? null]);
