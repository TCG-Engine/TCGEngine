<?php

  require_once "../../Core/NetworkingLibraries.php";
  require_once "../../Core/HTTPLibraries.php";
  require_once "./Classes/Player.php";
  require_once __DIR__ . '/JoinQueue_blocklib.php';
  require_once __DIR__ . "/Classes/TeamRooms.php";   // SWURoomAutoTeamOnJoin / SWURoomAssignTeam
  require_once __DIR__ . "/Classes/LobbyAdapter.php"; // LobbyAdapterFor — the per-sim lobby seam
  require_once __DIR__ . "/Classes/LobbyStore.php";  // LobbyMutate — the ONE locked lobby write

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
  // The RAW request, before any per-mode default is layered on. GA self-play defaults to BOTH seats;
  // SWUSim Bot Practice defaults to seat 2 ONLY (seat 1 is the human). Keeping the unmodified parse
  // here is what lets the two defaults differ without either mode reading the other's fallback.
  $requestedBotPlayers = [];
  if (isset($_POST['botPlayers'])) {
    foreach (explode(',', strval($_POST['botPlayers'])) as $seatStr) {
      $seat = intval(trim($seatStr));
      if ($seat === 1 || $seat === 2) $requestedBotPlayers[] = $seat;
    }
  }
  $gaBotPlayers = $requestedBotPlayers;
  if (empty($gaBotPlayers)) $gaBotPlayers = [1, 2];
  $createTutorial = isset($_POST['createTutorial']) && ($_POST['createTutorial'] === '1' || strtolower($_POST['createTutorial']) === 'true');
  $casterMode = isset($_POST['casterMode']) && ($_POST['casterMode'] === '1' || strtolower($_POST['casterMode']) === 'true');
  $privateInviteCode = isset($_POST['privateInviteCode']) ? trim($_POST['privateInviteCode']) : '';
  // Grand Archive analytics sharing is opt-out. Legacy clients that omit the field retain the
  // default-on behavior; a match shares only when every participant leaves it enabled.
  $shareAnonymizedGameplayData = !isset($_POST['shareAnonymizedGameplayData'])
    || in_array(strtolower(trim(strval($_POST['shareAnonymizedGameplayData']))), ['1', 'true', 'yes', 'on'], true);

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
  if ($privateInviteCode !== '') {
    $inviteKey   = LobbyKeyForInvite($privateInviteCode, $rootName);
    $inviteLobby = $inviteKey !== null ? apcu_fetch($inviteKey) : false;
    if (is_object($inviteLobby) && !empty($inviteLobby->isPrivate)) {
      $format    = strtolower(strval($inviteLobby->format ?? $format));
      $queueType = strtolower(strval($inviteLobby->queueType ?? $queueType));
    }
  }

  // Solo/local modes are created immediately (no matchmaking). 'goldfish' = 1 deck (empty P2);
  // 'hotseat' = 2 decks, shared authKey.
  $isModeFormat =
      ($rootName === 'FaBSim' && $format === 'bot') ||
      ($rootName === 'SWUSim'         && ($format === 'goldfish' || $format === 'hotseat' || $format === 'botpractice')) ||
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
    $isFaBBot = ($rootName === 'FaBSim' && $format === 'bot');
    // SWUSim Bot Practice: ONE human at seat 1, a bot at seat 2. Unlike goldfish, seat 2 is a REAL
    // seat — it needs a real deck, takes its own mulligan, and can lose its base.
    $isBotPractice = ($rootName === 'SWUSim' && $format === 'botpractice');
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
    if ($isFaBBot) {
      $secondPlayer = new Player(2, '', '');
      $faBBotProfile=strval($_POST['botProfile']??'fai');
      if(!in_array($faBBotProfile,['fai','professor','ira','boltyn','levia','prism','lexi','dromai','arakni','uzuri'],true))throw new InvalidArgumentException('Unknown FaB bot profile.');
      $secondPlayer->setBotProfile($faBBotProfile);
      $secondPlayer->setDeckOk(true);
      $secondPlayer->setReady(true);
    } else if ($isAzukiRlBot) {
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
    } else if ($isBotPractice) {
      // Bot Practice: seat 2 is the BOT's seat and it plays a real list, exactly like hotseat's
      // second seat — NOT goldfish's empty sponge. Falling through to the goldfish `else` below is
      // what made every game created through this endpoint dead on arrival: seat 2's deck load
      // failed, SWUSim/CreateGame.php's $deckLoadOk went false (there is no goldfish exemption for
      // this mode, correctly), QueuePregameSetup() never ran, and the game sat in phase APS with no
      // hands, no mulligan and no base while the bot polled "no action pending" forever.
      //
      // deckLink2 is the bot's list. It falls back to the host's own list (as GA self-play above
      // does) rather than to '' so a request that omits it produces a playable mirror match instead
      // of re-creating that dead seat — the menu that would enforce a second deck is Phase 5.
      $secondPlayer = new Player(2, $deckLink2 !== '' ? $deckLink2 : $deckLink, '', $joiningUserId);
    } else {
      // Goldfish: P2 is an empty passive seat (SWUSetupGame no longer gates pregame on it).
      $secondPlayer = new Player(2, '', '');
    }

    $lobby = new stdClass();
    $lobby->numPlayers = 2;
    $lobby->maxPlayers = 2;
    $lobby->ready = true;
    $lobby->id = uniqid($isTutorial ? 'tutorial_' : ($isAzukiRlBot ? 'rlbot_' : ($isHotseat ? 'hotseat_' : ($isGABot ? 'bot_' : ($isBotPractice ? 'botpractice_' : 'goldfish_')))), true);
    $lobby->rootName = $rootName;
    $lobby->format = $format;
    $lobby->queueType = $queueType;
    $lobby->isPrivate = true;
    if ($rootName === 'GrandArchiveSim') $lobby->shareAnonymizedGameplayData = $shareAnonymizedGameplayData;
    $lobby->casterMode = $casterMode;
    $lobby->isGoldfish = true;            // reuse the "skip matchmaking / skip Bo3 match" plumbing
    // Seat 2 is a passive empty sponge ONLY in goldfish. Hotseat, GA self-play and Bot Practice all
    // give it a real deck, so none of them may declare it goldfish. (Inert for SWUSim's CreateGame,
    // which never reads this field — but it is the recorded INTENT, and FaB/Hellbreak/GA do read it.)
    $lobby->goldfishPlayers = ($isHotseat || $isGABot || $isBotPractice || $isFaBBot) ? [] : [2];
    // Which seats a bot drives. GA self-play defaults to both seats; Bot Practice defaults to seat 2
    // (seat 1 is the human) and honours an explicit botPlayers request — until now this line dropped
    // that field for SWUSim outright and only worked via SWUSim/CreateGame.php's own [2] fallback.
    $lobby->botPlayers = $isGABot
      ? $gaBotPlayers
      : ($isBotPractice ? (empty($requestedBotPlayers) ? [2] : $requestedBotPlayers) : []);
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
    // O(1) invite lookup (LobbyKeyForInvite keeps the full-cache scan as a fallback for lobbies
    // created before the index existed). Deliberately still a LOOP: every eligibility `continue`
    // below falls through to the shared "invalid, expired, or already full" response, and rewriting
    // them as a flat condition would change which failure each one reports.
    $inviteKey  = LobbyKeyForInvite($privateInviteCode, $rootName);
    $candidates = $inviteKey !== null ? [['info' => $inviteKey]] : [];
    foreach ($candidates as $entry) {
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
      // A lobby whose match has begun cannot be joined. Was gated on SWUSim + a seat-count
      // predicate; now on the adapter, so it holds for every sim and every private format.
      $joinAdapter = LobbyAdapterFor(strval($lobby->rootName));
      if ($joinAdapter !== null && $joinAdapter->wantsWaitingRoom($lobby) && !empty($lobby->gameName)) continue;

      // The scan above is a LOOKUP, not a claim: it tells us which key to mutate. Every eligibility
      // condition is re-checked inside the mutation, because the room can fill or start while the
      // deck below is resolving.
      $targetKey = $entry['info'];
      $snapshot  = $lobby;

      // ⚠ NETWORK I/O, DELIBERATELY BEFORE THE LOCK. This used to run inside the fetch→store
      // window as _SWURoomApplyDeck(): swudb takes 0.4s cold and 3.2-4.2s once it throttles, and
      // the whole lobby was written back afterwards from a snapshot that old — reverting every
      // heartbeat committed meanwhile, which is what made "the 4th joined and someone else
      // dropped" a real causal chain. It also collapses the SECOND resolution of the same deck
      // link: ValidateDeckSubmissionForQueue at the top of this file already fetched it once.
      $isRoom   = $joinAdapter !== null && $joinAdapter->wantsWaitingRoom($snapshot);
      $resolved = $isRoom ? _SWURoomResolveDeck($snapshot, $deckLink, $preconstructedDeck)
                          : ['ok' => true, 'leaders' => [], 'base' => '', 'cards' => []];

      $joinErr = null; $newPlayer = null; $playerID = 0;
      $stored = LobbyMutate($targetKey, function ($lobby) use (
          $isRoom, $resolved, $deckLink, $preconstructedDeck, $joiningUserId, $rootName,
          $shareAnonymizedGameplayData, &$joinErr, &$newPlayer, &$playerID) {
        if (intval($lobby->numPlayers) >= intval($lobby->maxPlayers)) { $joinErr = 'full'; return false; }
        if ($isRoom && !empty($lobby->gameName))                      { $joinErr = 'started'; return false; }
        $lobby->numPlayers++;
        if ($rootName === 'GrandArchiveSim') {
          $lobby->shareAnonymizedGameplayData = !empty($lobby->shareAnonymizedGameplayData) && $shareAnonymizedGameplayData;
        }
        // ⚠ THE BEHAVIOUR CHANGE. Every PRIVATE lobby waits for an explicit host Start instead of
        // auto-readying when it fills — that is the whole point of the waiting room: players agree
        // on decks first. Public queues are untouched, so fill = ready still holds for quick match.
        if (!$isRoom && $lobby->numPlayers == $lobby->maxPlayers) $lobby->ready = true;
        $playerID  = _SWUNextPlayerID($lobby);
        $newPlayer = new Player($playerID, $deckLink, $preconstructedDeck, $joiningUserId);
        if ($isRoom) _SWURoomApplyResolvedDeck($newPlayer, $resolved);
        $lobby->players[] = $newPlayer;
        LobbyEnsureFixedSeats($lobby);
        // Team rooms: force the joiner onto the only team with room; otherwise they pick (spec §4.3).
        // Must run AFTER the player is appended so the counts include them.
        $autoTeam = SWURoomAutoTeamOnJoin($lobby);
        if ($autoTeam !== null) SWURoomAssignTeam($lobby, $newPlayer, $autoTeam);
        return true;
      });

      if ($joinErr !== null) continue;   // full or started: keep the generic "invalid/expired/full"
      if ($stored === null) {
        $response->success = false;
        $response->message = "That room is busy right now — try again.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
      }
      $lobby = $stored;

      // Game creation stays OUTSIDE the mutation: it writes files and rows, which is exactly the
      // I/O the lock must not span. Only a non-room private lobby reaches it here (a room waits
      // for the host's Start), and a second short mutation commits the gameName it produces.
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
        if (isset($lobby->gameName) && $lobby->gameName !== '') {
          RegisterActiveGame($rootName, strval($lobby->gameName), true);
          // $matchedTtl: keep a matched lobby alive just long enough for the pollers already
          // waiting on it to receive the ready state.
          $lobby = LobbyCommitGameCreation($targetKey, $lobby, 'matched', $matchedTtl) ?? $lobby;
        }
      }

      $response->success = true;
      $response->message = "Successfully joined private game.";
      $response->ready = $lobby->ready;
      $response->playerID = $playerID;
      $response->authKey = $newPlayer->getAuthKey();
      $response->lobbyID = $lobby->id;
      $response->maxPlayers = $lobby->maxPlayers;
      $response->isRoom = $isRoom;
      $response->team   = $newPlayer->getTeam();
      $response->seat   = $newPlayer->getSeat();
      $response->inviteCode = $lobby->inviteCode;
      $response->casterMode = !empty($lobby->casterMode);
      if (isset($lobby->gameName) && $lobby->gameName) $response->gameName = $lobby->gameName;
      header('Content-Type: application/json');
      echo json_encode($response);
      exit;
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
    [, $lobbyMaxPlayers] = ($rootName === 'SWUSim') ? SWUFormatSeatRange($format) : [2, 2];
    if ($rootName === 'FaBSim' && $format === 'upf') $lobbyMaxPlayers = 4;
    $lobby->maxPlayers = $lobbyMaxPlayers;
    $lobby->ready = false;
    $lobby->id = $lobbyId;
    $lobby->rootName = $rootName;
    $lobby->format = $format;
    $lobby->queueType = $queueType;
    $lobby->isPrivate = true;
    if ($rootName === 'GrandArchiveSim') $lobby->shareAnonymizedGameplayData = $shareAnonymizedGameplayData;
    $lobby->casterMode = $casterMode;
    $lobby->hostUserId = $joiningUserId;
    $lobby->hostPlayerID = 1;   // Identity of the room creator. Read this, never "playerID === 1" —
                                // Team Suns seats move, and host must not move with them.
    $lobby->inviteCode = bin2hex(random_bytes(12));
    $newPlayer = new Player(1, $deckLink, $preconstructedDeck, $joiningUserId);
    if (LobbyUsesFixedSeats($lobby)) $newPlayer->setSeat(1);
    // $lobby->isPrivate is already true here, so this is simply "is this sim opted in, and is the
    // format not solo/local" — which for a private lobby is every format a human plays against another.
    $createAdapter = LobbyAdapterFor($rootName);
    $createIsRoom  = $createAdapter !== null && $createAdapter->wantsWaitingRoom($lobby);
    if ($createIsRoom) _SWURoomApplyResolvedDeck($newPlayer, _SWURoomResolveDeck($lobby, $deckLink, $preconstructedDeck));
    $lobby->players = array($newPlayer);

    apcu_store($lobbyId, $lobby, $ttl);
    // O(1) invite lookup. Three call sites used to walk the WHOLE apcu cache — which also holds
    // gamestates — and apcu_fetch every entry just to match one code. Same TTL as the lobby, so the
    // index cannot outlive what it points at.
    apcu_store('invite:' . $lobby->inviteCode, $lobbyId, $ttl);

    $response->success = true;
    $response->message = "Successfully created private lobby.";
    $response->ready = false;
    $response->playerID = 1;
    $response->authKey = $newPlayer->getAuthKey();
    $response->lobbyID = $lobby->id;
    $response->inviteCode = $lobby->inviteCode;
    $response->casterMode = !empty($lobby->casterMode);
    $response->maxPlayers = $lobby->maxPlayers;
    $response->isRoom = $createIsRoom;

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
              // No deck resolution happens here, so nothing needs hoisting — but the seat-ID
              // allocation and the capacity re-check are the same bugs as the private path.
              $targetKey = $entry['info'];
              $joinErr = null; $newPlayer = null; $playerID = 0;
              $stored = LobbyMutate($targetKey, function ($lobby) use (
                  $deckLink, $preconstructedDeck, $joiningUserId, $rootName,
                  $shareAnonymizedGameplayData, &$joinErr, &$newPlayer, &$playerID) {
                // Re-checked under the lock: two people can reach a one-seat queue at once, and the
                // loser must fall through to the next lobby rather than overfill this one.
                if (intval($lobby->numPlayers) >= intval($lobby->maxPlayers)) { $joinErr = 'full'; return false; }
                $lobby->numPlayers++;
                if ($rootName === 'GrandArchiveSim') {
                  $lobby->shareAnonymizedGameplayData = !empty($lobby->shareAnonymizedGameplayData) && $shareAnonymizedGameplayData;
                }
                if ($lobby->numPlayers == $lobby->maxPlayers) $lobby->ready = true;
                $playerID  = _SWUNextPlayerID($lobby);
                $newPlayer = new Player($playerID, $deckLink, $preconstructedDeck, $joiningUserId);
                $lobby->players[] = $newPlayer;
                LobbyEnsureFixedSeats($lobby);
                return true;
              });
              if ($joinErr !== null) continue;   // full: keep scanning for another lobby
              if ($stored === null) continue;    // busy or vanished: same treatment
              $lobby = $stored;

              // Game creation writes files and rows — I/O, deliberately outside the lock. A second
              // short mutation commits the gameName it produces.
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
                if (isset($lobby->gameName) && $lobby->gameName !== '') {
                  RegisterActiveGame($rootName, strval($lobby->gameName), false);
                  $lobby = LobbyCommitGameCreation($targetKey, $lobby, 'matched', $matchedTtl) ?? $lobby;
                }
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
      if ($rootName === 'GrandArchiveSim') $lobby->shareAnonymizedGameplayData = $shareAnonymizedGameplayData;
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

  // Room seats: resolve the deck, check it against the ROOM'S OWN format (twinsuns or teamsuns)
  // rather than a hardcoded one, and cache the resolved LEADERS on the seat so the room roster
  // shows an accurate deckOk at create/join time — mirrors UpdateLobbyDeck.php's check. The leader
  // cache is what makes the team-wide leader-conflict check possible without re-resolving four
  // decks on every roster poll. Never fatal. Returns the deckOk value it just wrote.
  // Resolve a seat's deck and build the roster's identity strip. DOES NETWORK I/O — a swudb link is
  // a remote fetch. Call it BEFORE entering LobbyMutate, never inside: this is the multi-second
  // critical section that used to revert other players' heartbeats.
  //
  // $lobby is only read for its format, which is fixed for the life of the room, so an unlocked
  // snapshot is a safe thing to validate against.
  function _SWURoomResolveDeck($lobby, $deckLink, $preconstructedDeck) {
    $empty = ['ok' => false, 'leaders' => [], 'base' => '', 'cards' => []];
    $adapter = LobbyAdapterFor(strval($lobby->rootName ?? ''));
    if ($adapter === null) return $empty;
    $input = trim($deckLink) !== '' ? $deckLink : $preconstructedDeck;
    $v = $adapter->validateDeck($lobby, (string)$input);
    if (empty($v['ok'])) return $empty;
    $leaders = []; $base = '';
    foreach ($v['identity']['cards'] as $c) {
      if ($c['kind'] === 'leader')                   $leaders[] = $c['id'];
      elseif ($c['kind'] === 'base' && $base === '') $base      = $c['id'];
    }
    return ['ok' => true, 'leaders' => $leaders, 'base' => $base, 'cards' => $v['identity']['cards']];
  }

  // Stamp a resolved deck onto a seat. PURE — safe inside LobbyMutate.
  // ⚠ Every failure path writes EMPTIES through setDeckIdentity(), which is one call so leaders, base
  // and cards can never drift: a seat showing last deck's base under this deck's leaders is worse
  // than showing nothing.
  function _SWURoomApplyResolvedDeck($player, array $resolved) {
    if (!($player instanceof Player)) return false;
    $player->setDeckIdentity($resolved['leaders'], $resolved['base'], $resolved['cards']);
    $player->setDeckOk($resolved['ok']);
    // Joining WITH a legal deck auto-readies, exactly as loading one later does (UpdateLobbyDeck).
    // Otherwise every seat would arrive un-ready and have to press a button to confirm the deck they
    // just chose. Unready remains available for "hold on".
    $player->setReady($resolved['ok']);
    // Start the presence clock now: a seat that has not polled yet must not look absent.
    $player->touch();
    return $resolved['ok'];
  }

  // The next free seat id: one above the highest in use, never the seat COUNT.
  //
  // ⚠ This used to be `$lobby->numPlayers`, which collides the moment anybody has left — LeaveQueue
  // splices without renumbering, so a 3-seat room that lost seat 1 hands the next joiner id 3, which
  // seat 3 is still holding. Two tiles then render with the same id and StartRoom's host lookup
  // matches only the first of them.
  function _SWUNextPlayerID($lobby): int {
    $max = 0;
    foreach (($lobby->players ?? []) as $p) {
      if ($p instanceof Player) $max = max($max, intval($p->getPlayerID()));
    }
    return $max + 1;
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
        return FaBValidateDeckForQueue($deckLink, $preconstructedDeck, $joiningUserId, $format);
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
