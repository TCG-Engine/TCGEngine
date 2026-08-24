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
    $azukiRlBotProfilesPath = __DIR__ . '/../../AzukiSim/Custom/RlBotProfiles.php';
    if(is_file($azukiRlBotProfilesPath)) {
      include_once $azukiRlBotProfilesPath;
    }
    require_once __DIR__ . '/../../AzukiSim/CreateGame.php';
    require_once __DIR__ . '/../../Core/Match/MatchFlow.php';
    require_once __DIR__ . '/../../AzukiSim/MatchHooks.php';
  } else if($rootName === 'SWUSim') {
    $swuDeckImportPath = __DIR__ . '/../../SWUSim/Custom/DeckImport.php';
    if(is_file($swuDeckImportPath)) {
      include_once $swuDeckImportPath;
    }
    $swuMatchFlowPath = __DIR__ . '/../../SWUSim/MatchFlow.php';
    if(is_file($swuMatchFlowPath)) {
      include_once $swuMatchFlowPath;
    }
  } else if($rootName === 'HellbreakSim') {
    include_once __DIR__ . '/../../HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
    include_once __DIR__ . '/../../HellbreakSim/Custom/DeckImport.php';
  } else if($rootName === 'FaBSim') {
    include_once __DIR__ . '/../../FaBSim/GeneratedCode/GeneratedCardDictionaries.php';
    include_once __DIR__ . '/../../FaBSim/Custom/DeckImport.php';
    include_once __DIR__ . '/../../FaBDeck/DeckService.php';
  }

  $deckLink = isset($_POST['deckLink']) ? $_POST['deckLink'] : '';
  $deckLink2 = isset($_POST['deckLink2']) ? $_POST['deckLink2'] : '';
  $preconstructedDeck = isset($_POST['preconstructedDeck']) ? $_POST['preconstructedDeck'] : '';
  $createPrivate = isset($_POST['createPrivate']) && ($_POST['createPrivate'] === '1' || strtolower($_POST['createPrivate']) === 'true');
  $createGoldfish = isset($_POST['createGoldfish']) && ($_POST['createGoldfish'] === '1' || strtolower($_POST['createGoldfish']) === 'true');
  $createRlBot = isset($_POST['createRlBot']) && ($_POST['createRlBot'] === '1' || strtolower($_POST['createRlBot']) === 'true');
  $azukiRlBotProfile = function_exists('NormalizeAzukiRlBotProfile')
    ? NormalizeAzukiRlBotProfile($_POST['rlBotOpponent'] ?? 'raizan')
    : 'raizan';
  // GA heuristic-bot self-play (see GrandArchiveSim/BotController.php). botPlayers defaults to
  // both seats (full self-play, e.g. for automated regression matches); pass a single seat for a
  // human-vs-bot game instead.
  $createBot = isset($_POST['createBot']) && ($_POST['createBot'] === '1' || strtolower($_POST['createBot']) === 'true');
  $gaBotPlayers = [];
  if (isset($_POST['botPlayers'])) {
    foreach (explode(',', strval($_POST['botPlayers'])) as $seatStr) {
      $seat = intval(trim($seatStr));
      if ($seat === 1 || $seat === 2) $gaBotPlayers[] = $seat;
    }
  }
  if (empty($gaBotPlayers)) $gaBotPlayers = [1, 2];
  $createTutorial = isset($_POST['createTutorial']) && ($_POST['createTutorial'] === '1' || strtolower($_POST['createTutorial']) === 'true');
  $casterMode = isset($_POST['casterMode']) && ($_POST['casterMode'] === '1' || strtolower($_POST['casterMode']) === 'true');
  $privateInviteCode = isset($_POST['privateInviteCode']) ? trim($_POST['privateInviteCode']) : '';

  $format = isset($_POST['format']) ? strtolower(trim($_POST['format'])) : 'premier';
  if ($createRlBot && $rootName === 'AzukiSim') {
    $format = 'rlbot';
  } else if ($createTutorial && in_array($rootName, ['AzukiSim', 'HellbreakSim'], true)) {
    $format = 'tutorial';
  }
  $queueType = isset($_POST['queueType']) ? strtolower(trim($_POST['queueType'])) : 'bo1';

  // ── Private invite: ADOPT the host lobby's format + match type ────────────────────────────────
  // An invite link carries only the code, never the settings the host chose. The joiner's own
  // format/queueType dropdowns are therefore meaningless here — they are whatever their menu happened
  // to be showing. Previously the lobby lookup further down REQUIRED them to match the host's
  // ($lobby->format !== $format → continue), so a Twin Suns Bo3 invite opened by someone sitting on
  // Premier Bo1 silently skipped the correct lobby and failed as "invalid or expired invite".
  // Resolve the invite HERE, before anything reads $format — in particular before the deck is
  // validated below, which must check the deck against the format the game will actually be played in.
  // Read-only pre-pass: it only learns the settings; the authoritative join (capacity, blocks, caster
  // mode, seat assignment) still happens in the invite branch further down.
  if ($privateInviteCode !== '' && function_exists('apcu_cache_info')) {
    $inviteInfo = apcu_cache_info();
    if (isset($inviteInfo['cache_list'])) {
      foreach ($inviteInfo['cache_list'] as $inviteEntry) {
        if (!isset($inviteEntry['info'])) continue;
        $inviteLobby = apcu_fetch($inviteEntry['info']);
        if ($inviteLobby === false || !is_object($inviteLobby)) continue;
        if (($inviteLobby->rootName ?? '') !== $rootName) continue;
        if (empty($inviteLobby->isPrivate)) continue;
        if (!isset($inviteLobby->inviteCode) || strval($inviteLobby->inviteCode) !== $privateInviteCode) continue;
        $format    = strtolower(strval($inviteLobby->format ?? $format));
        $queueType = strtolower(strval($inviteLobby->queueType ?? $queueType));
        break;
      }
    }
  }

  // Solo/local modes are created immediately (no matchmaking). 'goldfish' = 1 deck (empty P2);
  // 'hotseat' = 2 decks, shared authKey.
  $isModeFormat =
      ($rootName === 'SWUSim'         && ($format === 'goldfish' || $format === 'hotseat')) ||
      ($rootName === 'GrandArchiveSim' && ($format === 'goldfish' || $format === 'hotseat' || $format === 'bot')) ||
      ($rootName === 'AzukiSim'        && ($format === 'rlbot' || $format === 'tutorial')) ||
      ($rootName === 'HellbreakSim'    && $format === 'tutorial');
  // Guard: for SWUSim, fall back to safe defaults on unknown/garbage. (Other roots ignore these.)
  if ($rootName === 'SWUSim') {
    if (!function_exists('SWUGetFormat') || SWUGetFormat($format) === null) $format = 'premier';
    if (!function_exists('SWUGetQueueType') || SWUGetQueueType($queueType) === null) $queueType = 'bo1';
    // Login is required to START a non-Open game — both the public queue and HOSTING a private one.
    // Open is the anonymous-friendly format; Goldfish/Hotseat are local-only.
    // ⚠ JOINING by invite code is deliberately EXEMPT: a logged-in host already created the lobby and
    // vouched for the format, so an anonymous friend following the link may join a Premier/Twin Suns
    // game they could not have started themselves.
    $swuNeedsAccount = !$createGoldfish && !$isModeFormat && $privateInviteCode === '';
    if ($format !== 'open' && $swuNeedsAccount && !$joiningUserId) {
      $response->success = false;
      $response->message = $createPrivate
        ? "You must be logged in to host a private game in this format."
        : "You must be logged in to join this queue.";
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
  if ($rootName === 'AzukiSim') {
    // Azuki currently supports mutual Bo1 quick rematches, not Bo3/sideboarding.
    $queueType = 'bo1';
  }

  // Authored tutorial games supply their own deterministic decks below.
  $isAzukiTutorialRequest = ($rootName === 'AzukiSim' && $format === 'tutorial');
  $isHellbreakTutorialRequest = ($rootName === 'HellbreakSim' && $format === 'tutorial');
  $isTutorialRequest = $isAzukiTutorialRequest || $isHellbreakTutorialRequest;

  // Require either deckLink or preconstructedDeck for player-authored games.
  if(!$isTutorialRequest && empty($deckLink) && empty($preconstructedDeck)) {
    $response->success = false;
    $response->message = "Either deck link or preconstructed deck is required.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  $deckValidation = $isTutorialRequest
    ? ['success' => true, 'message' => '']
    : ValidateDeckSubmissionForQueue($rootName, $deckLink, $preconstructedDeck, $format, $joiningUserId);
  if(!$deckValidation['success']) {
    $response->success = false;
    $response->message = $deckValidation['message'];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }

  $response->success = false;
  $response->message = "Failed to join queue.";

  if ($createGoldfish || $createRlBot || $createBot || $createTutorial || $isModeFormat) {
    // Normalize: the legacy createGoldfish param maps to the goldfish mode format.
    if ($createGoldfish && !$isModeFormat) $format = 'goldfish';
    if ($createRlBot && $rootName === 'AzukiSim') $format = 'rlbot';
    if ($createBot && $rootName === 'GrandArchiveSim' && !$isModeFormat) $format = 'bot';
    if ($createTutorial && in_array($rootName, ['AzukiSim', 'HellbreakSim'], true)) $format = 'tutorial';
    $isHotseat = ($format === 'hotseat');
    $isGABot = ($rootName === 'GrandArchiveSim' && $format === 'bot');
    $isAzukiRlBot = ($rootName === 'AzukiSim' && $format === 'rlbot');
    $isAzukiTutorial = ($rootName === 'AzukiSim' && $format === 'tutorial');
    $isHellbreakTutorial = ($rootName === 'HellbreakSim' && $format === 'tutorial');
    $isTutorial = $isAzukiTutorial || $isHellbreakTutorial;
    // Goldfish/Hotseat/Bot are Bo1-only for now (leave Bo3 open for later): force Bo1 regardless of input.
    $queueType = 'bo1';

    // Tutorials always use the authored starter scenario, independent of the queue form's deck choice.
    $hostPlayer = $isAzukiTutorial
      ? new Player(1, '', 'Raizan', $joiningUserId)
      : ($isHellbreakTutorial
        ? new Player(1, '', 'HellbreakFixture', $joiningUserId)
        : new Player(1, $deckLink, $preconstructedDeck, $joiningUserId));
    if ($isAzukiRlBot) {
      $botProfile = function_exists('GetAzukiRlBotProfile')
        ? GetAzukiRlBotProfile($azukiRlBotProfile)
        : ['deck' => 'Raizan'];
      $secondPlayer = new Player(2, '', strval($botProfile['deck'] ?? 'Raizan'));
    } else if ($isAzukiTutorial) {
      $secondPlayer = new Player(2, '', 'Raizan');
    } else if ($isHellbreakTutorial) {
      $secondPlayer = new Player(2, '', 'HellbreakFixture');
    } else if ($isHotseat) {
      // Hotseat: a real second deck; one person plays both seats.
      $secondPlayer = new Player(2, $deckLink2, '', $joiningUserId);
    } else if ($isGABot) {
      // Bot self-play: a real second deck too (a bot-controlled seat still needs an actual deck to
      // pilot, unlike goldfish's empty dummy) — default to the same deck as P1 if none was supplied,
      // so a single decklist can be tested against itself with one request.
      $secondPlayer = new Player(2, $deckLink2 !== '' ? $deckLink2 : $deckLink, '', $joiningUserId);
    } else {
      // Goldfish: P2 is an empty passive seat (SWUSetupGame no longer gates pregame on it).
      $secondPlayer = new Player(2, '', '');
    }

    $lobby = new stdClass();
    $lobby->numPlayers = 2;
    $lobby->maxPlayers = 2;
    $lobby->ready = true;
    $lobby->id = uniqid($isTutorial ? 'tutorial_' : ($isAzukiRlBot ? 'rlbot_' : ($isHotseat ? 'hotseat_' : ($isGABot ? 'bot_' : 'goldfish_'))), true);
    $lobby->rootName = $rootName;
    $lobby->format = $format;
    $lobby->queueType = $queueType;
    $lobby->isPrivate = true;
    $lobby->casterMode = $casterMode;
    $lobby->isGoldfish = true;            // reuse the "skip matchmaking / skip Bo3 match" plumbing
    $lobby->goldfishPlayers = ($isHotseat || $isGABot) ? [] : [2];
    $lobby->botPlayers = $isGABot ? $gaBotPlayers : [];
    $lobby->azukiRlBotPlayers = $isAzukiRlBot ? [2] : [];
    $lobby->azukiRlBotProfile = $isAzukiRlBot ? $azukiRlBotProfile : '';
    $lobby->players = [$hostPlayer, $secondPlayer];

    // CreateGame is pre-included via MatchFlow (SWU + GA), so call the setup function directly rather
    // than re-`include` (which would redeclare its functions and fatal).
    if ($rootName === 'SWUSim' && function_exists('SWUSetupGame')) {
      SWUSetupGame($lobby);
    } else if ($rootName === 'GrandArchiveSim' && function_exists('GASetupGame')) {
      GASetupGame($lobby);
    } else if ($rootName === 'AzukiSim' && function_exists('AzukiSetupGame')) {
      AzukiSetupGame($lobby);
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
        // NOTE: deliberately NOT filtered by format/queueType. The invite code alone identifies the
        // lobby, and the pre-pass above already adopted this lobby's settings into $format/$queueType.
        // Re-filtering on them here would reintroduce the original bug the moment the two disagree
        // (e.g. the pre-pass found nothing because the lobby expired between the two scans) — the join
        // would fail with a confusing "invalid or expired invite" instead of the real reason.
        if (!isset($lobby->isPrivate) || !$lobby->isPrivate) continue;
        if (!isset($lobby->inviteCode) || strval($lobby->inviteCode) !== $privateInviteCode) continue;
        if (!empty($lobby->casterMode) !== $casterMode) continue;
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
          } else if ($rootName === 'AzukiSim' && empty($lobby->isGoldfish) && function_exists('MatchCreateFromLobby')) {
            MatchCreateFromLobby('AzukiSim', $lobby); // creates the Match + game 1, sets $lobby->gameName
          } else if ($rootName === 'AzukiSim' && function_exists('AzukiSetupGame')) {
            AzukiSetupGame($lobby);
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
        $response->casterMode = !empty($lobby->casterMode);
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
    $lobby->casterMode = $casterMode;
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
    $response->casterMode = !empty($lobby->casterMode);
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
            (!empty($lobby->casterMode) === $casterMode) &&
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
                } else if ($rootName === 'AzukiSim' && empty($lobby->isGoldfish) && function_exists('MatchCreateFromLobby')) {
                  MatchCreateFromLobby('AzukiSim', $lobby); // creates the Match + game 1, sets $lobby->gameName
                } else if ($rootName === 'AzukiSim' && function_exists('AzukiSetupGame')) {
                  AzukiSetupGame($lobby);
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
      $lobby->casterMode = $casterMode;
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

    if($rootName === 'HellbreakSim') {
      if(!function_exists('HellbreakValidateDeckForQueue')) {
        return [
          'success' => false,
          'message' => 'Hellbreak deck validation is temporarily unavailable.'
        ];
      }
      try {
        return HellbreakValidateDeckForQueue($deckLink, $preconstructedDeck, $joiningUserId);
      } catch (Throwable $e) {
        error_log('HellbreakSim queue deck validation failed: ' . $e->getMessage());
        return [
          'success' => false,
          'message' => 'Could not validate the Hellbreak deck. Please try again.'
        ];
      }
    }

    if($rootName === 'FaBSim') {
      if(!function_exists('FaBValidateDeckForQueue')) return ['success'=>false,'message'=>'FaB deck validation is temporarily unavailable.'];
      try {
        return FaBValidateDeckForQueue($deckLink, $preconstructedDeck, $joiningUserId);
      } catch (Throwable $e) {
        error_log('FaBSim queue deck validation failed: ' . $e->getMessage());
        return ['success'=>false,'message'=>'Could not validate the FaB deck. Please try again.'];
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
