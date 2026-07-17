<?php
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
$swuMatchFlowPath = __DIR__ . '/../../SWUSim/MatchFlow.php';
if (is_file($swuMatchFlowPath)) require_once $swuMatchFlowPath;

$response = new stdClass();
function _startRoomFail($response, $m) {
  $response->success = false;
  $response->message = $m;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID  = $_POST['lobbyID'] ?? '';
$rootName = $_POST['rootName'] ?? '';
$playerID = intval($_POST['playerID'] ?? 0);
$authKey  = $_POST['authKey'] ?? '';

$lobby = $lobbyID ? apcu_fetch($lobbyID) : null;
if (!$lobby) _startRoomFail($response, 'Room not found.');

// Only the HOST (seat 1) may start, authenticated by authKey.
$host = null;
foreach (($lobby->players ?? []) as $p) {
  if (($p instanceof Player) && $p->getPlayerID() == 1) { $host = $p; break; }
}
if (!$host || $playerID !== 1 || $host->getAuthKey() !== $authKey) _startRoomFail($response, 'Only the host can start.');
if (($lobby->rootName ?? '') !== 'SWUSim' || ($lobby->format ?? '') !== 'twinsuns') _startRoomFail($response, 'Not a Twin Suns room.');
if (!empty($lobby->gameName)) _startRoomFail($response, 'Already started.');
if (intval($lobby->numPlayers) < 3) _startRoomFail($response, 'Need at least 3 players.');

// Compact seats to 1..N in current order so a mid-room leave doesn't leave a gap.
$lobby->players = array_values($lobby->players);
$seat = 1;
foreach ($lobby->players as $p) { $p->setPlayerID($seat); ++$seat; }
$lobby->numPlayers = count($lobby->players);
$lobby->ready = true;

if (!function_exists('SWUCreateMatchFromLobby')) _startRoomFail($response, 'Match framework unavailable.');
SWUCreateMatchFromLobby($lobby); // sets $lobby->gameName
if (empty($lobby->gameName)) _startRoomFail($response, 'Failed to create game — check deck legality.');

if (function_exists('RegisterActiveGame')) RegisterActiveGame($rootName, strval($lobby->gameName), false);
$lobby->state = 'started';
apcu_store($lobbyID, $lobby, 900); // keep the room alive for rematch (long TTL)

$response->success = true;
$response->gameName = $lobby->gameName;
$response->started = true;
header('Content-Type: application/json');
echo json_encode($response);
