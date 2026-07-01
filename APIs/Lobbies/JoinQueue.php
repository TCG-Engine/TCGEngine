<?php

  require_once "../../Core/NetworkingLibraries.php";
  require_once "../../Core/HTTPLibraries.php";
  require_once "./Classes/Player.php";
  require_once __DIR__ . '/JoinQueue_blocklib.php';

  // Personal deck stats (Feature B): remember who created each seat so the match can attribute W/L.
  if (session_status() === PHP_SESSION_NONE) { @session_start(); }
  $joiningUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : null;

  $response = new stdClass();
  
  if(!isset($_POST['rootName'])) {
    $response->success = false;
    $response->message = "Root name is required.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }
  $rootName = $_POST['rootName'];

  if($rootName === 'GrandArchiveSim') {
    $grandArchiveDeckImportPath = __DIR__ . '/../../GrandArchiveSim/Custom/DeckImport.php';
    if(is_file($grandArchiveDeckImportPath)) {
      include_once $grandArchiveDeckImportPath;
    }
  } else if($rootName === 'AzukiSim') {
    $azukiDeckImportPath = __DIR__ . '/../../AzukiSim/Custom/DeckImport.php';
    if(is_file($azukiDeckImportPath)) {
      include_once $azukiDeckImportPath;
    }
  } else if($rootName === 'SWUSim') {
    $swuDeckImportPath = __DIR__ . '/../../SWUSim/Custom/DeckImport.php';
    if(is_file($swuDeckImportPath)) {
      include_once $swuDeckImportPath;
    }
    $swuMatchFlowPath = __DIR__ . '/../../SWUSim/MatchFlow.php';
    if(is_file($swuMatchFlowPath)) {
      include_once $swuMatchFlowPath;
    }
  }

  $deckLink = isset($_POST['deckLink']) ? $_POST['deckLink'] : '';
  $preconstructedDeck = isset($_POST['preconstructedDeck']) ? $_POST['preconstructedDeck'] : '';
  $createPrivate = isset($_POST['createPrivate']) && ($_POST['createPrivate'] === '1' || strtolower($_POST['createPrivate']) === 'true');
  $createGoldfish = isset($_POST['createGoldfish']) && ($_POST['createGoldfish'] === '1' || strtolower($_POST['createGoldfish']) === 'true');
  $privateInviteCode = isset($_POST['privateInviteCode']) ? trim($_POST['privateInviteCode']) : '';

  $format = isset($_POST['format']) ? strtolower(trim($_POST['format'])) : 'premier';
  $queueType = isset($_POST['queueType']) ? strtolower(trim($_POST['queueType'])) : 'bo1';
  // Guard: for SWUSim, fall back to safe defaults on unknown/garbage. (Other roots ignore these.)
  if ($rootName === 'SWUSim') {
    if (!function_exists('SWUGetFormat') || SWUGetFormat($format) === null) $format = 'premier';
    if (!function_exists('SWUGetQueueType') || SWUGetQueueType($queueType) === null) $queueType = 'bo1';
    // Only logged-in users may join the public queue for non-Open formats (Open is the
    // anonymous-friendly format). Goldfish (solo) and private games (invite link) are exempt.
    $swuPublicQueue = !$createGoldfish && !$createPrivate && $privateInviteCode === '';
    if ($format !== 'open' && $swuPublicQueue && !$joiningUserId) {
      $response->success = false;
      $response->message = "You must be logged in to join this queue.";
      header('Content-Type: application/json');
      echo json_encode($response);
      exit;
    }
  }

  // Require either deckLink or preconstructedDeck
  if(empty($deckLink) && empty($preconstructedDeck)) {
    $response->success = false;
    $response->message = "Either deck link or preconstructed deck is required.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  $deckValidation = ValidateDeckSubmissionForQueue($rootName, $deckLink, $preconstructedDeck);
  if(!$deckValidation['success']) {
    $response->success = false;
    $response->message = $deckValidation['message'];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  $response->success = false;
  $response->message = "Failed to join queue.";

  if ($createGoldfish) {
    $hostPlayer = new Player(1, $deckLink, $preconstructedDeck, $joiningUserId);
    $goldfishPlayer = new Player(2, '', '');

    $lobby = new stdClass();
    $lobby->numPlayers = 2;
    $lobby->maxPlayers = 2;
    $lobby->ready = true;
    $lobby->id = uniqid('goldfish_', true);
    $lobby->rootName = $rootName;
    $lobby->format = $format;
    $lobby->queueType = $queueType;
    $lobby->isPrivate = true;
    $lobby->isGoldfish = true;
    $lobby->goldfishPlayers = [2];
    $lobby->players = [$hostPlayer, $goldfishPlayer];

    // SWUSim's CreateGame is pre-included via MatchFlow (functions already defined),
    // so call SWUSetupGame directly rather than re-`include` (which would redeclare).
    if ($rootName === 'SWUSim' && function_exists('SWUSetupGame')) {
      SWUSetupGame($lobby);
    } else {
      include '../../' . $rootName . '/CreateGame.php';
    }

    $response->success = true;
    $response->message = "Successfully created goldfish game.";
    $response->ready = true;
    $response->playerID = 1;
    $response->authKey = $hostPlayer->getAuthKey();
    $response->gameName = $lobby->gameName ?? '';

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  // First check if there's already someone in the queue
  $cacheInfo = apcu_cache_info();
  $matchFound = false;
  $ttl = 600; // 10 minutes in seconds
  $matchedTtl = 90; // keep matched lobbies briefly so existing pollers can receive the ready state

  // Join a specific private lobby by invite code.
  if ($privateInviteCode !== '') {
    if (isset($cacheInfo['cache_list'])) {
      foreach ($cacheInfo['cache_list'] as $entry) {
        if (!isset($entry['info'])) continue;
        $lobby = apcu_fetch($entry['info']);
        if ($lobby === false || !is_object($lobby)) continue;
        if (!isset($lobby->id, $lobby->numPlayers, $lobby->maxPlayers, $lobby->rootName)) continue;
        if ($lobby->rootName !== $rootName) continue;
        if (($lobby->format ?? 'premier') !== $format) continue;
        if (($lobby->queueType ?? 'bo1') !== $queueType) continue;
        if (!isset($lobby->isPrivate) || !$lobby->isPrivate) continue;
        if (!isset($lobby->inviteCode) || strval($lobby->inviteCode) !== $privateInviteCode) continue;
        if (SWUJoinBlocked($joiningUserId, SWULobbyHostUserId($lobby))) continue; // blocked: fall through to generic "invalid/expired/full"
        if (intval($lobby->numPlayers) >= intval($lobby->maxPlayers)) continue;

        $lobby->numPlayers++;
        if ($lobby->numPlayers == $lobby->maxPlayers) {
          $lobby->ready = true;
        }
        $playerID = $lobby->numPlayers;
        $newPlayer = new Player($playerID, $deckLink, $preconstructedDeck, $joiningUserId);
        $lobby->players[] = $newPlayer;

        if ($lobby->ready) {
          if ($rootName === 'SWUSim' && empty($lobby->isGoldfish) && function_exists('SWUCreateMatchFromLobby')) {
            SWUCreateMatchFromLobby($lobby); // sets $lobby->gameName to game 1
          } else {
            include_once '../../' . $rootName . '/CreateGame.php';
          }
        }
        if ($lobby->ready && isset($lobby->gameName) && $lobby->gameName !== '') {
          RegisterActiveGame($rootName, strval($lobby->gameName), true);
          $lobby->state = 'matched';
          apcu_store($entry['info'], $lobby, $matchedTtl);
        } else {
          apcu_store($entry['info'], $lobby, $ttl);
        }

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
    $lobby->format = $format;
    $lobby->queueType = $queueType;
    $lobby->isPrivate = true;
    $lobby->inviteCode = bin2hex(random_bytes(12));
    $newPlayer = new Player(1, $deckLink, $preconstructedDeck, $joiningUserId);
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
            (($lobby->format ?? 'premier') === $format) &&
            (($lobby->queueType ?? 'bo1') === $queueType) &&
            (!isset($lobby->isPrivate) || !$lobby->isPrivate) &&
            intval($lobby->numPlayers) < intval($lobby->maxPlayers)
          ) {
              if (SWUJoinBlocked($joiningUserId, SWULobbyHostUserId($lobby))) continue; // skip blocked host, keep scanning
              $lobby->numPlayers++;
              if($lobby->numPlayers == $lobby->maxPlayers) {
                  $lobby->ready = true;
              }
              $playerID = $lobby->numPlayers;
              $newPlayer = new Player($playerID, $deckLink, $preconstructedDeck, $joiningUserId);
              $lobby->players[] = $newPlayer;
              if($lobby->ready) {
                if ($rootName === 'SWUSim' && empty($lobby->isGoldfish) && function_exists('SWUCreateMatchFromLobby')) {
                  SWUCreateMatchFromLobby($lobby); // sets $lobby->gameName to game 1
                } else {
                  include_once '../../' . $rootName . '/CreateGame.php';
                }
              }
              if ($lobby->ready && isset($lobby->gameName) && $lobby->gameName !== '') {
                RegisterActiveGame($rootName, strval($lobby->gameName), false);
                $lobby->state = 'matched';
                apcu_store($entry['info'], $lobby, $matchedTtl);
              } else {
                apcu_store($entry['info'], $lobby, $ttl); // Update the lobby in the cache
              }

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
      $lobby->format = $format;
      $lobby->queueType = $queueType;
      $lobby->isPrivate = false;
      $newPlayer = new Player(1, $deckLink, $preconstructedDeck, $joiningUserId);
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

  function ValidateDeckSubmissionForQueue($rootName, $deckLink, $preconstructedDeck) {
    if($rootName === 'GrandArchiveSim') {
      if(!function_exists('GrandArchiveValidateDeckForQueue')) {
        return [
          'success' => false,
          'message' => 'Deck validation is temporarily unavailable.'
        ];
      }

      try {
        return GrandArchiveValidateDeckForQueue($deckLink, $preconstructedDeck);
      } catch (Throwable $e) {
        error_log('GrandArchive queue deck validation failed: ' . $e->getMessage());
        return [
          'success' => false,
          'message' => 'Could not validate deck input. Please try again.'
        ];
      }
    }

    if($rootName === 'AzukiSim') {
      if(!function_exists('AzukiValidateDeckForQueue')) {
        return [
          'success' => false,
          'message' => 'Deck validation is temporarily unavailable.'
        ];
      }

      try {
        return AzukiValidateDeckForQueue($deckLink, $preconstructedDeck);
      } catch (Throwable $e) {
        error_log('AzukiSim queue deck validation failed: ' . $e->getMessage());
        return [
          'success' => false,
          'message' => 'Could not validate deck input. Please try again.'
        ];
      }
    }

    if($rootName === 'SWUSim') {
      if(!function_exists('SWUValidateDeckForQueue')) {
        return [
          'success' => false,
          'message' => 'Deck validation is temporarily unavailable.'
        ];
      }

      try {
        return SWUValidateDeckForQueue($deckLink, $preconstructedDeck);
      } catch (Throwable $e) {
        error_log('SWUSim queue deck validation failed: ' . $e->getMessage());
        return [
          'success' => false,
          'message' => 'Could not validate deck input. Please try again.'
        ];
      }
    }

    if(!empty($preconstructedDeck)) {
      return [
        'success' => true,
        'message' => ''
      ];
    }

    if(trim($deckLink) === '') {
      return [
        'success' => false,
        'message' => 'Either deck link or preconstructed deck is required.'
      ];
    }

    return [
      'success' => true,
      'message' => ''
    ];
  }

?>
