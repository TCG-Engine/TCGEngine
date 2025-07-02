<?php

require_once "../../Core/NetworkingLibraries.php";
require_once "../../Core/HTTPLibraries.php";
require_once "./Classes/Player.php";

$response = new stdClass();

if (!isset($_POST['lobbyID'])) {
  $response->success = false;
  $response->message = "Lobby ID is required.";
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

if (!isset($_POST['lobbyID']) || !isset($_POST['rootName']) || !isset($_POST['playerID']) || !isset($_POST['authKey'])) {
  $response->success = false;
  $response->message = "Missing required parameters.";
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$lobbyID = $_POST['lobbyID'];
$rootName = $_POST['rootName'];
$playerID = $_POST['playerID'];
$authKey = $_POST['authKey'];

$timeout = 30; // Maximum time to wait in seconds
$startTime = time();

while (true) {
  // Fetch the lobby data from the cache
  $lobby = apcu_fetch($lobbyID);
  if ($lobby) {
    // Check if the lobby is ready
    if (isset($lobby->ready) && $lobby->ready) {
      $response->success = true;
      $response->ready = true;

      if (isset($lobby->gameName)) {
        $response->gameName = $lobby->gameName;
      }

      // Identify the player based on playerID and authKey
      if (isset($lobby->players) && is_array($lobby->players)) {
        foreach ($lobby->players as $player) {
          if ($player->getPlayerID() == $playerID && $player->getAuthKey() == $authKey) {
            $response->playerID = $player->getPlayerID();
            $response->authKey = $player->getAuthKey();
            break;
          }
        }
      }

      header('Content-Type: application/json');
      echo json_encode($response);
      exit;
    }
  }

  // Break the loop if the timeout is reached
  if (time() - $startTime >= $timeout) {
    $response->success = false;
    $response->message = "Timeout reached. No updates available.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  // Sleep for a short interval before checking again
  usleep(100000); // 100ms
}


?>
