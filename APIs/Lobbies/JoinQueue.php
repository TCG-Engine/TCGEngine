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
    // Shared Core/Match framework + GA adapter. CreateGame defines GASetupGame (the setupGame hook
    // + the goldfish direct-call path); no ambient $lobby here, so its auto-run guard stays quiet.
    require_once __DIR__ . '/../../GrandArchiveSim/CreateGame.php';
    require_once __DIR__ . '/../../Core/Match/MatchFlow.php';
    require_once __DIR__ . '/../../GrandArchiveSim/MatchHooks.php';
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
  $deckLink2 = isset($_POST['deckLink2']) ? $_POST['deckLink2'] : '';
  $preconstructedDeck = isset($_POST['preconstructedDeck']) ? $_POST['preconstructedDeck'] : '';
  $createPrivate = isset($_POST['createPrivate']) && ($_POST['createPrivate'] === '1' || strtolower($_POST['createPrivate']) === 'true');
  $createGoldfish = isset($_POST['createGoldfish']) && ($_POST['createGoldfish'] === '1' || strtolower($_POST['createGoldfish']) === 'true');
  $createRlBot = isset($_POST['createRlBot']) && ($_POST['createRlBot'] === '1' || strtolower($_POST['createRlBot']) === 'true');
  $privateInviteCode = isset($_POST['privateInviteCode']) ? trim($_POST['privateInviteCode']) : '';

  $format = isset($_POST['format']) ? strtolower(trim($_POST['format'])) : 'premier';
  if ($createRlBot && $rootName === 'AzukiSim') {
    $format = 'rlbot';
    $deckLink = '';
    $preconstructedDeck = 'Raizan';
  }
  $queueType = isset($_POST['queueType']) ? strtolower(trim($_POST['queueType'])) : 'bo1';
  // Solo/local modes are created immediately (no matchmaking). 'goldfish' = 1 deck (empty P2);
  // 'hotseat' = 2 decks, shared authKey.
  $isModeFormat =
      ($rootName === 'SWUSim'         && ($format === 'goldfish' || $format === 'hotseat')) ||
      ($rootName === 'GrandArchiveSim' && ($format === 'goldfish' || $format === 'hotseat')) ||
      ($rootName === 'AzukiSim'        && $format === 'rlbot');
  // Guard: for SWUSim, fall back to safe defaults on unknown/garbage. (Other roots ignore these.)
  if ($rootName === 'SWUSim') {
    if (!function_exists('SWUGetFormat') || SWUGetFormat($format) === null) $format = 'premier';
    if (!function_exists('SWUGetQueueType') || SWUGetQueueType($queueType) === null) $queueType = 'bo1';
    // Only logged-in users may join the public queue for non-Open formats (Open is the
    // anonymous-friendly format). Goldfish (solo) and private games (invite link) are exempt.
    $swuPublicQueue = !$createGoldfish && !$isModeFormat && !$createPrivate && $privateInviteCode === '';
    if ($format !== 'open' && $swuPublicQueue && !$joiningUserId) {
      $response->success = false;
      $response->message = "You must be logged in to join this queue.";
      header('Content-Type: application/json');
      echo json_encode($response);
      exit;
    }
  }
  if ($rootName === 'GrandArchiveSim') {
    // GA has no DB-backed login, so no logged-in gate. Just normalize unknown values.
    if (!function_exists('GAGetFormat') || GAGetFormat($format) === null) $format = 'standard';
    if (!function_exists('GAGetQueueType') || GAGetQueueType($queueType) === null) $queueType = 'bo1';
  }

  // Require either deckLink or preconstructedDeck
  if(empty($deckLink) && empty($preconstructedDeck)) {
    $response->success = false;
    $response->message = "Either deck link or preconstructed deck is required.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  $deckValidation = ValidateDeckSubmissionForQueue($rootName, $deckLink, $preconstructedDeck, $format, $joiningUserId);
  if(!$deckValidation['success']) {
    $response->success = false;
    $response->message = $deckValidation['message'];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  $response->success = false;
  $response->message = "Failed to join queue.";

  if ($createGoldfish || $createRlBot || $isModeFormat) {
    // Normalize: the legacy createGoldfish param maps to the goldfish mode format.
    if ($createGoldfish && !$isModeFormat) $format = 'goldfish';
    if ($createRlBot && $rootName === 'AzukiSim') $format = 'rlbot';
    $isHotseat = ($format === 'hotseat');
    $isAzukiRlBot = ($rootName === 'AzukiSim' && $format === 'rlbot');
    // Goldfish/Hotseat are Bo1-only for now (leave Bo3 open for later): force Bo1 regardless of input.
    $queueType = 'bo1';

    $hostPlayer = new Player(1, $deckLink, $preconstructedDeck, $joiningUserId);
    if ($isAzukiRlBot) {
      $hostPlayer = new Player(1, '', 'Raizan', $joiningUserId);
      $secondPlayer = new Player(2, '', 'Raizan');
    } else if ($isHotseat) {
      // Hotseat: a real second deck; one person plays both seats.
      $secondPlayer = new Player(2, $deckLink2, '', $joiningUserId);
    } else {
      // Goldfish: P2 is an empty passive seat (SWUSetupGame no longer gates pregame on it).
      $secondPlayer = new Player(2, '', '');
    }

    $lobby = new stdClass();
    $lobby->numPlayers = 2;
    $lobby->maxPlayers = 2;
    $lobby->ready = true;
    $lobby->id = uniqid($isAzukiRlBot ? 'rlbot_' : ($isHotseat ? 'hotseat_' : 'goldfish_'), true);
    $lobby->rootName = $rootName;
    $lobby->format = $format;
    $lobby->queueType = $queueType;
    $lobby->isPrivate = true;
    $lobby->isGoldfish = true;            // reuse the "skip matchmaking / skip Bo3 match" plumbing
    $lobby->goldfishPlayers = $isHotseat ? [] : [2];
    $lobby->azukiRlBotPlayers = $isAzukiRlBot ? [2] : [];
    $lobby->players = [$hostPlayer, $secondPlayer];

    // CreateGame is pre-included via MatchFlow (SWU + GA), so call the setup function directly rather
    // than re-`include` (which would redeclare its functions and fatal).
    if ($rootName === 'SWUSim' && function_exists('SWUSetupGame')) {
      SWUSetupGame($lobby);
    } else if ($rootName === 'GrandArchiveSim' && function_exists('GASetupGame')) {
      GASetupGame($lobby);
    } else {
      include '../../' . $rootName . '/CreateGame.php';
    }

    $response->success = true;
    $response->message = "Successfully created $format game.";
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
        if (($lobby->rootName === 'SWUSim') && (($lobby->format ?? '') === 'twinsuns') && !empty($lobby->gameName)) continue; // already started

        $lobby->numPlayers++;
        $isTwinSunsRoom = ($lobby->rootName === 'SWUSim' && ($lobby->format ?? '') === 'twinsuns');
        if (!$isTwinSunsRoom && $lobby->numPlayers == $lobby->maxPlayers) {
          $lobby->ready = true;   // 2-seat: fill = ready (unchanged)
        }
        $playerID = $lobby->numPlayers;
        $newPlayer = new Player($playerID, $deckLink, $preconstructedDeck, $joiningUserId);
        if ($isTwinSunsRoom) $newPlayer->setDeckOk(_SWUTwinSunsDeckOk($deckLink, $preconstructedDeck));
        $lobby->players[] = $newPlayer;

        if ($lobby->ready) {
          if ($rootName === 'SWUSim' && empty($lobby->isGoldfish) && function_exists('SWUCreateMatchFromLobby')) {
            SWUCreateMatchFromLobby($lobby); // sets $lobby->gameName to game 1
          } else if ($rootName === 'GrandArchiveSim' && empty($lobby->isGoldfish) && function_exists('MatchCreateFromLobby')) {
            MatchCreateFromLobby('GrandArchiveSim', $lobby); // creates the Match + game 1, sets $lobby->gameName
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
        $response->maxPlayers = $lobby->maxPlayers;
        $response->isRoom = $isTwinSunsRoom;
        $response->inviteCode = $lobby->inviteCode;
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
    $lobby->maxPlayers = ($rootName === 'SWUSim' && $format === 'twinsuns') ? 4 : 2;
    $lobby->ready = false;
    $lobby->id = $lobbyId;
    $lobby->rootName = $rootName;
    $lobby->format = $format;
    $lobby->queueType = $queueType;
    $lobby->isPrivate = true;
    $lobby->hostUserId = $joiningUserId;
    $lobby->inviteCode = bin2hex(random_bytes(12));
    $newPlayer = new Player(1, $deckLink, $preconstructedDeck, $joiningUserId);
    if ($rootName === 'SWUSim' && $format === 'twinsuns') $newPlayer->setDeckOk(_SWUTwinSunsDeckOk($deckLink, $preconstructedDeck));
    $lobby->players = array($newPlayer);

    apcu_store($lobbyId, $lobby, $ttl);

    $response->success = true;
    $response->message = "Successfully created private lobby.";
    $response->ready = false;
    $response->playerID = 1;
    $response->authKey = $newPlayer->getAuthKey();
    $response->lobbyID = $lobby->id;
    $response->inviteCode = $lobby->inviteCode;
    $response->maxPlayers = $lobby->maxPlayers;
    $response->isRoom = ($rootName === 'SWUSim' && $format === 'twinsuns');

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  // Public matchmaking kill-switch (SWUSim only). Every non-public path (mode formats,
  // private-invite-by-code, createPrivate) has already exited above by this point.
  if ($rootName === 'SWUSim' && function_exists('SWUPublicQueueEnabled') && !SWUPublicQueueEnabled()) {
    $response->success = false;
    $response->message = "Public matchmaking isn't open yet — use a private invite.";
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
                } else if ($rootName === 'GrandArchiveSim' && empty($lobby->isGoldfish) && function_exists('MatchCreateFromLobby')) {
                  MatchCreateFromLobby('GrandArchiveSim', $lobby); // creates the Match + game 1, sets $lobby->gameName
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

  // Twin Suns room seats: resolve + check the deck against full 'twinsuns' format legality (2
  // leaders, 80+ cards, highlander, alignment) so the room roster shows an accurate deckOk at
  // create/join time — mirrors UpdateLobbyDeck.php's check. Never fatal; false on any failure.
  function _SWUTwinSunsDeckOk($deckLink, $preconstructedDeck) {
    if (!function_exists('SWUResolveDeckInput') || !function_exists('SWUCheckFormat')) return false;
    $input = trim($deckLink) !== '' ? $deckLink : $preconstructedDeck;
    if (trim((string)$input) === '') return false;
    $resolved = SWUResolveDeckInput($input);
    if (empty($resolved['success'])) return false;
    $errs = SWUCheckFormat('twinsuns', $resolved['leader'] ?? '', $resolved['base'] ?? '', $resolved['mainDeck'] ?? [], $resolved['sideboard'] ?? []);
    return empty($errs);
  }

  function ValidateDeckSubmissionForQueue($rootName, $deckLink, $preconstructedDeck, $format = 'standard', $joiningUserId = null) {
    if($rootName === 'GrandArchiveSim') {
      if(!function_exists('GrandArchiveValidateDeckForQueue')) {
        return [
          'success' => false,
          'message' => 'Deck validation is temporarily unavailable.'
        ];
      }

      try {
        return GrandArchiveValidateDeckForQueue($deckLink, $preconstructedDeck, $format);
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
        return AzukiValidateDeckForQueue($deckLink, $preconstructedDeck, $joiningUserId);
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
