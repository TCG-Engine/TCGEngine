<?php

require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";

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

$cacheInfo = apcu_cache_info();
$lobbyFound = false;

if (is_array($cacheInfo) && isset($cacheInfo['cache_list']) && is_array($cacheInfo['cache_list'])) {
  foreach ($cacheInfo['cache_list'] as $entry) {
    if (!isset($entry['info']) || !is_string($entry['info']) || $entry['info'] === '') continue;

    // Fetch the actual lobby data using the cache key
    $lobby = apcu_fetch($entry['info']);
    if ($lobby === false || !is_object($lobby)) continue;

    if (isset($lobby->id, $lobby->players, $lobby->numPlayers) && $lobby->id == $lobbyID && is_array($lobby->players)) {
      // Check if the player exists in the lobby
      foreach ($lobby->players as $index => $player) {
        if (($player instanceof Player) && $player->getPlayerID() === $playerID) {
          // Remove the player from the lobby
          array_splice($lobby->players, $index, 1);
          $lobby->numPlayers--;

          // If the lobby is empty, delete it
          if ($lobby->numPlayers <= 0) {
            apcu_delete($entry['info']);
            $response->message = "Lobby deleted as it became empty.";
          } else {
            // Update the lobby in the cache
            apcu_store($entry['info'], $lobby);
            $response->message = "Successfully left the queue.";
          }

          $response->success = true;
          $lobbyFound = true;
          break 2;
        }
      }
    }
  }
}

if (!$lobbyFound) {
  $response->message = "Player not found in any lobby.";
}

header('Content-Type: application/json');
echo json_encode($response);

?>
