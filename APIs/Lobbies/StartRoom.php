<?php
require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/TeamRooms.php";
require_once "./Classes/LobbyAdapter.php";
require_once "./Classes/LobbyStore.php";
$swuFormatsPath = __DIR__ . '/../../AppCore/SWU/Formats.php';
if (is_file($swuFormatsPath)) require_once $swuFormatsPath;
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

$snapshot = $lobbyID ? apcu_fetch($lobbyID) : null;
if (!$snapshot) _startRoomFail($response, 'Room not found.');

// Only the HOST may start, authenticated by authKey. Host is an IDENTITY (hostPlayerID), not a
// seat — Team Suns reassigns seats on every team pick and host must not migrate with them.
$hostPlayerID = intval($snapshot->hostPlayerID ?? 1);
$host = null;
foreach (($snapshot->players ?? []) as $p) {
  if (($p instanceof Player) && $p->getPlayerID() == $hostPlayerID) { $host = $p; break; }
}
if (!$host || $playerID !== $hostPlayerID || $host->getAuthKey() !== $authKey) _startRoomFail($response, 'Only the host can start.');
$startAdapter = LobbyAdapterFor(strval($snapshot->rootName ?? ''));
if ($startAdapter === null || !$startAdapter->wantsWaitingRoom($snapshot)) {
  _startRoomFail($response, 'Not a multiplayer room.');
}
if (!empty($snapshot->gameName)) _startRoomFail($response, 'Already started.');

$startErr = null;
$prepared = LobbyMutate($lobbyID, function ($lobby) use (&$startErr) {
  if (!empty($lobby->gameName)) { $startErr = 'Already started.'; return false; }
  // Pass the cached leader sets, or the team leader-conflict check never runs in the live path.
  $blockers = SWURoomStartBlockers($lobby, SWURoomLeaderSets($lobby));
  if (!empty($blockers)) { $startErr = implode(' ', array_slice($blockers, 0, 3)); return false; }

  // Compact seats to 1..N in TABLE order so a mid-room leave doesn't leave a gap.
  // Team rooms carry an explicit $seat (red 1,3 / blue 2,4) — sort by it first so the array reads
  // Red, Blue, Red, Blue. SWUResolveLobbyDecks derives game seats from ARRAY POSITION, so this is
  // the entire handoff; MatchHooks.php needs no change. Twin Suns sets no $seat, so the sort is a
  // no-op there and the original compaction order is preserved byte-for-byte.
  $lobby->players = array_values($lobby->players);
  if (SWURoomIsTeamLobby($lobby)) {
    usort($lobby->players, fn($a, $b) => intval($a->getSeat() ?? 99) <=> intval($b->getSeat() ?? 99));
  }
  $seat = 1;
  foreach ($lobby->players as $p) { $p->setPlayerID($seat); ++$seat; }
  $lobby->numPlayers = count($lobby->players);
  $lobby->ready = true;
  return true;
});
if ($startErr !== null)  _startRoomFail($response, $startErr);
if ($prepared === null)  _startRoomFail($response, 'Room is busy — try again.');

if (!function_exists('SWUCreateMatchFromLobby')) _startRoomFail($response, 'Match framework unavailable.');
SWUCreateMatchFromLobby($prepared);   // writes the game — I/O, deliberately outside the lock
if (empty($prepared->gameName)) _startRoomFail($response, 'Failed to create game — check deck legality.');
if (function_exists('RegisterActiveGame')) RegisterActiveGame($rootName, strval($prepared->gameName), false);

$gameName = $prepared->gameName;
// Keep the room alive for rematch (long TTL) — LobbyMutate's default is exactly that 900s.
LobbyCommitGameCreation($lobbyID, $prepared, 'started');

$response->success = true;
$response->gameName = $gameName;
$response->started = true;
header('Content-Type: application/json');
echo json_encode($response);
