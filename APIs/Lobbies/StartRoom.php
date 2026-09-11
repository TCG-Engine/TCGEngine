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
if (($_POST['rootName'] ?? '') === 'SWUSim' && is_file($swuMatchFlowPath)) require_once $swuMatchFlowPath;

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
if (($snapshot->rootName ?? '') !== $rootName) _startRoomFail($response, 'Room does not belong to this game.');

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
  if (!empty($lobby->gameName) || ($lobby->state ?? '') === 'starting') { $startErr = 'Already starting or started.'; return false; }
  // Pass the cached leader sets, or the team leader-conflict check never runs in the live path.
  $adapter = LobbyAdapterFor(strval($lobby->rootName ?? ''));
  $blockers = $adapter ? $adapter->startBlockers($lobby) : ['Waiting room unavailable.'];
  if (!empty($blockers)) { $startErr = implode(' ', array_slice($blockers, 0, 3)); return false; }

  // Compact seats to 1..N in TABLE order so a mid-room leave doesn't leave a gap.
  // Team rooms carry an explicit $seat (red 1,3 / blue 2,4) — sort by it first so the array reads
  // Red, Blue, Red, Blue. SWUResolveLobbyDecks derives game seats from ARRAY POSITION, so this is
  // the entire handoff; MatchHooks.php needs no change. Twin Suns sets no $seat, so the sort is a
  // no-op there and the original compaction order is preserved byte-for-byte.
  $lobby->players = array_values($lobby->players);
  LobbyEnsureFixedSeats($lobby);
  if (SWURoomIsTeamLobby($lobby) || LobbyUsesFixedSeats($lobby)) {
    usort($lobby->players, fn($a, $b) => intval($a->getSeat() ?? 99) <=> intval($b->getSeat() ?? 99));
  }
  $seat = 1;
  $host = null;
  foreach ($lobby->players as $p) if (intval($p->getPlayerID()) === intval($lobby->hostPlayerID ?? 0)) $host = $p;
  foreach ($lobby->players as $p) { $p->setPlayerID($seat); ++$seat; }
  if ($host !== null) $lobby->hostPlayerID = $host->getPlayerID();
  $lobby->numPlayers = count($lobby->players);
  $lobby->ready = true;
  $lobby->state = 'starting';
  return true;
});
if ($startErr !== null)  _startRoomFail($response, $startErr);
if ($prepared === null)  _startRoomFail($response, 'Room is busy — try again.');

try {
  if ($rootName === 'FaBSim') {
    $lobby = $prepared;
    include __DIR__ . '/../../FaBSim/CreateGame.php';
  } else {
    if (!function_exists('SWUCreateMatchFromLobby')) throw new RuntimeException('Match framework unavailable.');
    SWUCreateMatchFromLobby($prepared);
  }
  if (empty($prepared->gameName)) throw new RuntimeException('Game creation returned no game.');
} catch (Throwable $e) {
  LobbyMutate($lobbyID, function ($l) { $l->state = 'open'; $l->ready = false; return true; });
  error_log('StartRoom failed: ' . $e->getMessage());
  _startRoomFail($response, 'Unable to create game. Check decks and try again.');
}
if (function_exists('RegisterActiveGame')) RegisterActiveGame($rootName, strval($prepared->gameName), false);

$gameName = $prepared->gameName;
// Keep the room alive for rematch (long TTL) — LobbyMutate's default is exactly that 900s.
LobbyCommitGameCreation($lobbyID, $prepared, 'started');

$response->success = true;
$response->gameName = $gameName;
$response->started = true;
header('Content-Type: application/json');
echo json_encode($response);
