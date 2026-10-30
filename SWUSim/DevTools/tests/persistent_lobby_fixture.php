<?php
// REGRESSION-SKIP: a fixture endpoint for the browser harness, not a test (it builds a match and prints its ids).
// Test fixture for the persistent-lobby browser checks (DevTools/ui-harness/swusim-persistent-lobby-xbrowser.mjs).
// Builds a STARTED 1v1 premier match the way the real paths do and prints its ids as JSON:
//   ?origin=room    a private waiting room started it (the match carries roomId; the room is saved to disk)
//   ?origin=public  a public-queue pairing (no room)
//   &queue=bo3      a Best of 3 (default Best of 1)
//   &finish=1       also record game 1 as won by seat 1 on the MATCH (no gamestate game-over — for room-page checks);
//                   leave it off and concede in the browser (input 10006) for a real end screen.
// Moderators only, like SWUSim/TestSchemaSetup.php. Prints {lobbyID, inviteCode, gameName, matchId, keys:{1,2}}.
header('Content-Type: application/json');
chdir(__DIR__ . '/../../..');
include_once './AccountFiles/AccountSessionAPI.php';
$authError = CheckLoggedInUserMod();
if ($authError !== null && $authError !== '' && $authError !== false) { echo json_encode(['error' => 'moderators only']); exit; }
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/Rooms.php';

$origin = ($_GET['origin'] ?? 'room') === 'public' ? 'public' : 'room';
$deck = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));

$lobby = new stdClass();
$lobby->id = 'plfx' . bin2hex(random_bytes(5));
$lobby->rootName = 'SWUSim'; $lobby->format = 'premier'; $lobby->queueType = ($_GET['queue'] ?? '') === 'bo3' ? 'bo3' : 'bo1';
$lobby->isPrivate = ($origin === 'room'); $lobby->maxPlayers = 2; $lobby->numPlayers = 2; $lobby->hostPlayerID = 1;
$lobby->casterMode = false; $lobby->ready = true; $lobby->state = 'starting';
$lobby->inviteCode = $origin === 'room' ? bin2hex(random_bytes(12)) : '';
$a = new Player(1, $deck, '', null); $b = new Player(2, $deck, '', null);
foreach ([$a, $b] as $p) { $p->setDeckOk(true); $p->setReady(true); $p->touch(); }
$lobby->players = [$a, $b];
apcu_store($lobby->id, $lobby, LOBBY_TTL_SECONDS);
if ($lobby->inviteCode !== '') apcu_store('invite:' . $lobby->inviteCode, $lobby->id, LOBBY_TTL_SECONDS);

$matchId = SWUCreateMatchFromLobby($lobby);
$game = strval($lobby->gameName ?? '');
if (!$matchId || $game === '') { echo json_encode(['error' => 'could not create the match']); exit; }
if (function_exists('RegisterActiveGame')) RegisterActiveGame('SWUSim', $game, $lobby->isPrivate);
LobbyCommitGameCreation($lobby->id, $lobby, 'started');
if ($origin === 'room') SWURoomOnMatchStarted($lobby->id, strval($matchId));
if (!empty($_GET['finish'])) MatchRecordGameResult('SWUSim', $matchId, $game, 1, 5, [1]);

echo json_encode(['lobbyID' => $lobby->id, 'inviteCode' => $lobby->inviteCode, 'gameName' => $game, 'matchId' => strval($matchId),
                  'keys' => ['1' => $a->getAuthKey(), '2' => $b->getAuthKey()]]);
