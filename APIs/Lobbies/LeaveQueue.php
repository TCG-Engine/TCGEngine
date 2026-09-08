<?php

require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";
require_once "./Classes/TeamRooms.php";   // SWUMigrateHostIfNeeded
require_once "./Classes/LobbyStore.php";  // LobbyMutate

$response = new stdClass();
if (!isset($_POST['rootName']) || !isset($_POST['playerID']) || !isset($_POST['lobbyID']) || !isset($_POST['authKey'])) {
  $response->success = false;
  $response->message = "Root name, PlayerID, and authKey are required.";
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$rootName = $_POST['rootName'];
$playerID = intval($_POST['playerID']);
$lobbyID = $_POST['lobbyID'];
$authKey = $_POST['authKey'];

$response->success = false;
$response->message = "Failed to leave queue.";

// The cache key and $lobby->id are always the same value (both creation paths do
// `$lobbyId = uniqid(); ... $lobby->id = $lobbyId; apcu_store($lobbyId, ...)`), so the full-cache
// walk this used to do was pure waste over a cache that also holds gamestates.
$left = false;
$lobby = LobbyMutate($lobbyID, function ($lobby) use ($authKey, &$left) {
  if (!isset($lobby->players) || !is_array($lobby->players)) return false;
  // ⚠ AUTHENTICATE ON authKey, NOT playerID.
  // This used to match `getPlayerID() === $playerID` and never check the authKey it demands —
  // two bugs in one line. (1) SECURITY: anyone holding a lobbyID could evict any seat, because
  // playerID is a public seat number and the only secret was ignored. (2) CORRECTNESS: playerID
  // is a SEAT, not an identity — StartRoom compacts seats to 1..N and team rooms sort by picked
  // seat — so a caller whose seat had shifted silently matched nobody and Leave did nothing at
  // all, which is exactly how it was reported. authKey is a 128-bit per-player secret, so
  // matching it alone is both correct and strictly stronger.
  foreach ($lobby->players as $index => $player) {
    if (($player instanceof Player) && hash_equals(strval($player->getAuthKey()), strval($authKey))) {
      array_splice($lobby->players, $index, 1);
      $lobby->numPlayers--;
      $left = true;
      if ($lobby->numPlayers <= 0) return 'delete';   // last seat gone — nothing left to come back to
      // The host may be the one who just left. Migrate BEFORE the store, or the lobby is written
      // back with a hostPlayerID naming a seat nobody holds and StartRoom rejects every attempt to
      // start it.
      if (function_exists('SWUMigrateHostIfNeeded')) SWUMigrateHostIfNeeded($lobby);
      return true;
    }
  }
  return false;
});

if ($left) {
  $response->success = true;
  $response->message = ($lobby !== null && intval($lobby->numPlayers) <= 0)
    ? "Lobby deleted as it became empty." : "Successfully left the queue.";
}
$lobbyFound = $left;

if (!$lobbyFound) {
  $response->message = "Player not found in any lobby.";
}

header('Content-Type: application/json');
echo json_encode($response);

?>
