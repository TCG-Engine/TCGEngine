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

  $deckLink = isset($_POST['deckLink']) ? $_POST['deckLink'] : '';
  $preconstructedDeck = isset($_POST['preconstructedDeck']) ? $_POST['preconstructedDeck'] : '';
  $createPrivate = isset($_POST['createPrivate']) && ($_POST['createPrivate'] === '1' || strtolower($_POST['createPrivate']) === 'true');
  $privateInviteCode = isset($_POST['privateInviteCode']) ? trim($_POST['privateInviteCode']) : '';

  // Require either deckLink or preconstructedDeck
  if(empty($deckLink) && empty($preconstructedDeck)) {
    $response->success = false;
    $response->message = "Either deck link or preconstructed deck is required.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  $response->success = false;
  $response->message = "Failed to join queue.";

  // First check if there's already someone in the queue
  $cacheInfo = apcu_cache_info();
  $matchFound = false;
  $ttl = 600; // 10 minutes in seconds

  // Join a specific private lobby by invite code.
  if ($privateInviteCode !== '') {
    if (isset($cacheInfo['cache_list'])) {
      foreach ($cacheInfo['cache_list'] as $entry) {
        if (!isset($entry['info'])) continue;
        $lobby = apcu_fetch($entry['info']);
        if ($lobby === false || !is_object($lobby)) continue;
        if (!isset($lobby->id, $lobby->numPlayers, $lobby->maxPlayers, $lobby->rootName)) continue;
        if ($lobby->rootName !== $rootName) continue;
        if (!isset($lobby->isPrivate) || !$lobby->isPrivate) continue;
        if (!isset($lobby->inviteCode) || strval($lobby->inviteCode) !== $privateInviteCode) continue;
        if (intval($lobby->numPlayers) >= intval($lobby->maxPlayers)) continue;

        $lobby->numPlayers++;
        if ($lobby->numPlayers == $lobby->maxPlayers) {
          $lobby->ready = true;
        }
        $playerID = $lobby->numPlayers;
        $newPlayer = new Player($playerID, $deckLink, $preconstructedDeck);
        $lobby->players[] = $newPlayer;

        if ($lobby->ready) {
          include_once '../../' . $rootName . '/CreateGame.php';
        }

        apcu_store($entry['info'], $lobby, $ttl);

        $response->success = true;
        $response->message = "Successfully joined private game.";
        $response->ready = $lobby->ready;
        $response->playerID = $playerID;
        $response->authKey = $newPlayer->getAuthKey();
        $response->lobbyID = $lobby->id;
        if (isset($lobby->gameName) && $lobby->gameName) $response->gameName = $lobby->gameName;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
      }
    }

    $response->success = false;
    $response->message = "Private game invite is invalid, expired, or already full.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  // Create a private lobby that can only be joined via invite code.
  if ($createPrivate) {
    $lobbyId = uniqid();
    $lobby = new stdClass();
    $lobby->numPlayers = 1;
    $lobby->maxPlayers = 2;
    $lobby->ready = false;
    $lobby->id = $lobbyId;
    $lobby->rootName = $rootName;
    $lobby->isPrivate = true;
    $lobby->inviteCode = bin2hex(random_bytes(12));
    $newPlayer = new Player(1, $deckLink, $preconstructedDeck);
    $lobby->players = array($newPlayer);

    apcu_store($lobbyId, $lobby, $ttl);

    $response->success = true;
    $response->message = "Successfully created private lobby.";
    $response->ready = false;
    $response->playerID = 1;
    $response->authKey = $newPlayer->getAuthKey();
    $response->lobbyID = $lobby->id;
    $response->inviteCode = $lobby->inviteCode;

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  if (isset($cacheInfo['cache_list'])) {
      foreach ($cacheInfo['cache_list'] as $entry) {
          if (!isset($entry['info'])) continue;
          // Fetch the actual lobby data using the cache key
          $lobby = apcu_fetch($entry['info']);
          // Check if the lobby exists and meets the join criteria
          if (
            $lobby &&
            isset($lobby->numPlayers, $lobby->maxPlayers, $lobby->rootName) &&
            $lobby->rootName === $rootName &&
            (!isset($lobby->isPrivate) || !$lobby->isPrivate) &&
            intval($lobby->numPlayers) < intval($lobby->maxPlayers)
          ) {
              $lobby->numPlayers++;
              if($lobby->numPlayers == $lobby->maxPlayers) {
                  $lobby->ready = true;
              }
              $playerID = $lobby->numPlayers;
              $newPlayer = new Player($playerID, $deckLink, $preconstructedDeck);
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
              if(isset($lobby->gameName) && $lobby->gameName) $response->gameName = $lobby->gameName;
              $matchFound = true;
              header('Content-Type: application/json');
              echo json_encode($response);
              exit;
          }
      }
  }

  if (!$matchFound) {
      // If no match was found, create a new public lobby
      $lobbyId = uniqid();
      $lobby = new stdClass();
      $lobby->numPlayers = 1;
      $lobby->maxPlayers = 2;
      $lobby->ready = false;
      $lobby->id = $lobbyId;
      $lobby->rootName = $rootName;
      $lobby->isPrivate = false;
      $newPlayer = new Player(1, $deckLink, $preconstructedDeck);
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
