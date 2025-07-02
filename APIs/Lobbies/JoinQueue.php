<?php

  require_once "../../Core/NetworkingLibraries.php";
  require_once "../../Core/HTTPLibraries.php";
  require_once "./Classes/Player.php";

  $response = new stdClass();
  
  if(!isset($_POST['rootName'])) {
    $response->success = false;
    $response->message = "Root name is required.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }
  $rootName = $_POST['rootName'];

  if(!isset($_POST['deckLink']) || empty($_POST['deckLink'])) {
    $response->success = false;
    $response->message = "Deck link is required.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }
  $deckLink = $_POST['deckLink'];

  $response->success = false;
  $response->message = "Failed to join queue.";

  // First check if there's already someone in the queue
  $cacheInfo = apcu_cache_info();
  $matchFound = false;
  $ttl = 600; // 10 minutes in seconds

  if (isset($cacheInfo['cache_list'])) {
      foreach ($cacheInfo['cache_list'] as $entry) {
          // Fetch the actual lobby data using the cache key
          $lobby = apcu_fetch($entry['info']);
          // Check if the lobby exists and meets the join criteria
          if ($lobby && isset($lobby->numPlayers) && isset($lobby->maxPlayers) && intval($lobby->numPlayers) < intval($lobby->maxPlayers)) {
              $lobby->numPlayers++;
              if($lobby->numPlayers == $lobby->maxPlayers) {
                  $lobby->ready = true;
              }
              $playerID = $lobby->numPlayers;
              $newPlayer = new Player($playerID, $deckLink);
              $lobby->players[] = $newPlayer;
              if($lobby->ready) {
                include_once '../../' . $rootName . '/CreateGame.php';
              }
              apcu_store($entry['info'], $lobby, $ttl); // Update the lobby in the cache
              
              $response->success = true;
              $response->message = "Successfully joined queue.";
              $response->ready = $lobby->ready;
              $response->playerID = $playerID;
              $response->authKey = $newPlayer->getAuthKey();
              $response->lobbyID = $lobby->id;
              if($lobby->gameName) $response->gameName = $lobby->gameName;
              $matchFound = true;
              header('Content-Type: application/json');
              echo json_encode($response);
              exit;
          }
      }
  }

  if (!$matchFound) {
      // If no match was found, create a new lobby
      $lobbyId = uniqid();
      $lobby = new stdClass();
      $lobby->numPlayers = 1;
      $lobby->maxPlayers = 2;
      $lobby->ready = false;
      $lobby->id = $lobbyId;
      $newPlayer = new Player(1, $deckLink);
      $lobby->players = array($newPlayer);

      apcu_store($lobbyId, $lobby, $ttl);

      $response->success = true;
      $response->message = "Successfully created lobby.";
      $response->ready = false;
      $response->playerID = 1;
      $response->authKey = $newPlayer->getAuthKey();
      $response->lobbyID = $lobby->id;
  }


  header('Content-Type: application/json');
  echo json_encode($response);

?>
