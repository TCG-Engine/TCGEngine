<?php
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';

include_once __DIR__ . '/RegressionTestFramework.php';
include_once __DIR__ . '/MatchReplay.php';
include_once __DIR__ . '/BotController.php';
include_once __DIR__ . '/Versioning/AssetVersioningCapability.php';

function ConvertMzIDToAbsolute($mzID, $playerPerspective) {
  if (!$mzID || strpos($mzID, "-") === false) return $mzID;
  
  list($zone, $index) = explode("-", $mzID, 2);
  
  // Already absolute (p1 or p2 format)
  if (strpos($zone, "p1") === 0 || strpos($zone, "p2") === 0) {
    return $mzID;
  }
  
  // Relative perspective - convert to absolute
  if (strpos($zone, "their") === 0) {
    // "their" zone belongs to the opponent
    $opponentPlayer = ($playerPerspective == 1) ? 2 : 1;
    $zone = str_replace("their", "p" . $opponentPlayer, $zone);
  } else if (strpos($zone, "my") === 0) {
    // "my" zone belongs to the current player
    $zone = str_replace("my", "p" . $playerPerspective, $zone);
  }
  
  return $zone . "-" . $index;
}

function QueueFrameEvent($event) {
  global $frameAnimations;
  if (!isset($frameAnimations) || !is_array($frameAnimations)) {
    $frameAnimations = [];
  }
  if (!is_array($event)) return;
  if (!isset($event['type']) || trim(strval($event['type'])) === '') return;

  $frameAnimations[] = $event;
}

function QueueFrameAnimation($animation) {
  if (!is_array($animation)) return;
  if (!isset($animation['target']) || $animation['target'] === '') return;

  if (!isset($animation['durationMs'])) $animation['durationMs'] = 0;
  if (!isset($animation['blocking'])) $animation['blocking'] = true;
  QueueFrameEvent($animation);
}

/**
 * Queue a short, non-blocking semantic sound for the current authoritative update.
 *
 * The cue is an app-owned identifier resolved by the browser's registered sound manifest. Never
 * put asset paths or private card data in this payload. Supported options are deliberately small:
 * delayMs, volume, intensity, onlySeat, actorSeat, variantSeed, and perspectiveCues.
 */
function QueueSoundEvent($cue, $options = []) {
  $cue = trim(strval($cue));
  if ($cue === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $cue)) return;
  if (!is_array($options)) $options = [];

  $event = [
    'type' => 'SOUND',
    'cue' => $cue,
    'delayMs' => max(0, intval($options['delayMs'] ?? 0)),
    // Sound never participates in the ordered render queue's blocking window.
    'blocking' => false,
  ];

  if (array_key_exists('volume', $options)) {
    $event['volume'] = max(0.0, min(2.0, floatval($options['volume'])));
  }
  if (array_key_exists('intensity', $options)) {
    $event['intensity'] = max(0.0, min(1.0, floatval($options['intensity'])));
  }
  if (intval($options['onlySeat'] ?? 0) > 0) {
    $event['onlySeat'] = intval($options['onlySeat']);
  }
  if (intval($options['actorSeat'] ?? 0) > 0) {
    $event['actorSeat'] = intval($options['actorSeat']);
  }
  if (isset($options['variantSeed']) && strval($options['variantSeed']) !== '') {
    $event['variantSeed'] = substr(strval($options['variantSeed']), 0, 80);
  }
  if (isset($options['perspectiveCues']) && is_array($options['perspectiveCues'])) {
    $perspectiveCues = [];
    foreach (['self', 'other', 'spectator'] as $perspectiveKey) {
      $perspectiveCue = trim(strval($options['perspectiveCues'][$perspectiveKey] ?? ''));
      if ($perspectiveCue !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $perspectiveCue)) {
        $perspectiveCues[$perspectiveKey] = $perspectiveCue;
      }
    }
    if (!empty($perspectiveCues)) $event['perspectiveCues'] = $perspectiveCues;
  }

  QueueFrameEvent($event);
}

function QueueCardAnimation($targetMzID, $name, $durationMs = 400, $blocking = true, $params = []) {
  QueueFrameAnimation([
    'type' => 'css',
    'target' => strval($targetMzID),
    'name' => strval($name),
    'durationMs' => intval($durationMs),
    'blocking' => $blocking ? true : false,
    'params' => is_array($params) ? $params : [],
  ]);
}

function QueueCardLungeAnimation($sourceMzID, $targetMzID, $durationMs = 360, $blocking = true, $sourceUniqueID = null, $targetUniqueID = null, $distanceRatio = 0.7) {
  $animation = [
    'type' => 'CARD_LUNGE',
    'target' => strval($sourceMzID),
    'source' => strval($sourceMzID),
    'destination' => strval($targetMzID),
    'durationMs' => intval($durationMs),
    'blocking' => $blocking ? true : false,
    'distanceRatio' => max(0.1, min(1.0, floatval($distanceRatio))),
  ];
  if ($sourceUniqueID !== null && intval($sourceUniqueID) > 0) {
    $animation['sourceUniqueID'] = intval($sourceUniqueID);
  }
  if ($targetUniqueID !== null && intval($targetUniqueID) > 0) {
    $animation['destinationUniqueID'] = intval($targetUniqueID);
  }
  QueueFrameAnimation($animation);
}

function QueueZoneMoveAnimation($sourceMzID, $destinationMzID, $durationMs = 420, $blocking = true, $sourceUniqueID = null, $destinationUniqueID = null, $delayMs = 0, $onlySeat = null) {
  $animation = [
    'type' => 'ZONE_MOVE',
    // QueueFrameAnimation requires a target. For movement events it is the old-board source.
    'target' => strval($sourceMzID),
    'source' => strval($sourceMzID),
    'destination' => strval($destinationMzID),
    'durationMs' => intval($durationMs),
    'delayMs' => max(0, intval($delayMs)),
    'blocking' => $blocking ? true : false,
  ];
  if ($sourceUniqueID !== null && intval($sourceUniqueID) > 0) {
    $animation['sourceUniqueID'] = intval($sourceUniqueID);
  }
  if ($destinationUniqueID !== null && intval($destinationUniqueID) > 0) {
    $animation['destinationUniqueID'] = intval($destinationUniqueID);
  }
  // Optional owner scoping: only the named seat plays this move. Used for zones the opponent cannot
  // see anyway (SWU resources are Display: Visibility=Self). Omitted => every viewer plays it.
  if ($onlySeat !== null && intval($onlySeat) > 0) {
    $animation['onlySeat'] = intval($onlySeat);
  }
  QueueFrameAnimation($animation);
}

function QueueDamageAnimation($targetMzID, $amount, $durationMs = 500, $blocking = true, $uniqueID = null, $delayMs = 0) {
  $animation = [
    'type' => 'DAMAGE',
    'target' => strval($targetMzID),
    'amount' => intval($amount),
    'durationMs' => intval($durationMs),
    'delayMs' => max(0, intval($delayMs)),
    'blocking' => $blocking ? true : false,
  ];
  if ($uniqueID !== null && intval($uniqueID) > 0) {
    $animation['uniqueID'] = intval($uniqueID);
  }
  QueueFrameAnimation($animation);
}

function QueuePreventedDamageAnimation($targetMzID, $durationMs = 500, $blocking = true, $uniqueID = null) {
  $animation = [
    'type' => 'PREVENTED_DAMAGE',
    'target' => strval($targetMzID),
    'durationMs' => intval($durationMs),
    'blocking' => $blocking ? true : false,
  ];
  if ($uniqueID !== null && intval($uniqueID) > 0) {
    $animation['uniqueID'] = intval($uniqueID);
  }
  QueueFrameAnimation($animation);
}

function QueueBlockedRecoveryAnimation($targetMzID, $durationMs = 500, $blocking = true, $uniqueID = null) {
  $animation = [
    'type' => 'BLOCKED_RECOVERY',
    'target' => strval($targetMzID),
    'durationMs' => intval($durationMs),
    'blocking' => $blocking ? true : false,
  ];
  if ($uniqueID !== null && intval($uniqueID) > 0) {
    $animation['uniqueID'] = intval($uniqueID);
  }
  QueueFrameAnimation($animation);
}

// Tilt a card to its exhausted angle (the app's RotationRules visual) at the FRONT of the animation
// window, rather than waiting for the board re-render. Exhausting is a cost — paid at declaration —
// so it must be visible before the effect it paid for; the re-render is deferred until every blocking
// animation finishes, which is too late when an action declares and resolves in one update.
// NON-blocking by default: this is a pre-render catch-up, it must not extend the render delay.
// $dim: also darken the card, for an app whose "exhausted" look is a tilt PLUS a shade (SWU renders
// both; UILibraries draws the shade as .exhausted-status-overlay-layer once the board re-renders).
// Opt-in per app rather than assumed, because a sim that only tilts must not start dimming.
function QueueExhaustAnimation($targetMzID, $durationMs = 120, $blocking = false, $uniqueID = null, $dim = false) {
  $animation = [
    'type' => 'EXHAUST',
    'target' => strval($targetMzID),
    'durationMs' => intval($durationMs),
    'blocking' => $blocking ? true : false,
  ];
  if ($uniqueID !== null && intval($uniqueID) > 0) {
    $animation['uniqueID'] = intval($uniqueID);
  }
  if ($dim) {
    $animation['dim'] = true;
  }
  QueueFrameAnimation($animation);
}

function QueueRestoreAnimation($targetMzID, $amount, $durationMs = 500, $blocking = true) {
  QueueFrameAnimation([
    'type' => 'RESTORE',
    'target' => strval($targetMzID),
    'amount' => intval($amount),
    'durationMs' => intval($durationMs),
    'blocking' => $blocking ? true : false,
  ]);
}

// Shield-break: a 5-frame SVG shatter that fades in over its duration, played at the broken
// shield's own top-right slot ($slot = 0 is the rightmost orb, each +20px to the left).
// Blocking by default because these overlays are injected into the live DOM and wiped by the
// next board re-render, so the block is what keeps the animation on screen for its duration.
function QueueShieldBreakAnimation($targetMzID, $slot = 0, $durationMs = 600, $blocking = true) {
  QueueFrameAnimation([
    'type' => 'SHIELD_BREAK',
    'target' => strval($targetMzID),
    'slot' => intval($slot),
    'durationMs' => intval($durationMs),
    'blocking' => $blocking ? true : false,
  ]);
}

function SetFrameAnimationCache($gameName, $animations) {
  if (!is_array($animations)) $animations = [];
  $encoded = json_encode($animations);
  if ($encoded === false) $encoded = '[]';
  // Store frame animations under a DEDICATED cache key, not as a piece of the shared multi-piece
  // game-state blob. SetCachePiece/GamestateUpdated do unlocked read-modify-write of the whole
  // blob; a concurrent stale long-poll could read the blob before the anims were added and write
  // its copy back, clobbering the animation piece before the poll reads it (the "animations only
  // show after 2 retries" bug). A dedicated key is overwritten atomically and never RMW-contended.
  WriteCache($gameName . '_anim', $encoded);
}

function EngineLoadRootRuntime($folderPath) {
  $repoRoot = RegressionRepoRoot();
  $localVarNames = array_keys(get_defined_vars());

  include_once $repoRoot . '/Core/CoreZoneModifiers.php';
  include_once $repoRoot . '/Core/NetworkingLibraries.php';
  include_once $repoRoot . '/Core/HTTPLibraries.php';
  include_once $repoRoot . '/AccountFiles/AccountSessionAPI.php';
  include_once $repoRoot . '/AccountFiles/AccountDatabaseAPI.php';
  include_once $repoRoot . '/Database/ConnectionManager.php';

  $gamestateParserPath = $repoRoot . '/' . $folderPath . '/GamestateParser.php';
  $dictionaryPath = $repoRoot . '/' . $folderPath . '/GeneratedCode/GeneratedCardDictionaries.php';
  $parserLoadedForReflection = false;
  if (!is_file($dictionaryPath)) {
    include_once $gamestateParserPath;
    $parserLoadedForReflection = true;
    if (function_exists('GetAssetReflectionPath')) {
      $reflectionRoot = trim((string)GetAssetReflectionPath());
      if ($reflectionRoot !== '') {
        $dictionaryPath = $repoRoot . '/' . $reflectionRoot . '/GeneratedCode/GeneratedCardDictionaries.php';
      }
    }
  }
  include_once $dictionaryPath;
  if (!$parserLoadedForReflection) include_once $gamestateParserPath;
  include_once $repoRoot . '/' . $folderPath . '/ZoneAccessors.php';
  include_once $repoRoot . '/' . $folderPath . '/ZoneClasses.php';

  // SWUSim Bo3 match orchestration — load on the action path so the after-action
  // hook + concede/convert handlers exist during real play (not just in tests).
  if ($folderPath === 'SWUSim') {
    $swuMatchFlow = $repoRoot . '/SWUSim/MatchFlow.php';
    if (is_file($swuMatchFlow)) include_once $swuMatchFlow;
  }
  // GrandArchiveSim Bo3 match orchestration — shared Core/Match framework + GA adapter,
  // loaded on the action path so the after-action hook + hooks exist during real play.
  if ($folderPath === 'GrandArchiveSim') {
    require_once $repoRoot . '/Core/Match/MatchFlow.php';
    require_once $repoRoot . '/GrandArchiveSim/MatchHooks.php';
  }
  if ($folderPath === 'AzukiSim') {
    require_once $repoRoot . '/Core/Match/MatchFlow.php';
    require_once $repoRoot . '/AzukiSim/MatchHooks.php';
  }

  // Root runtime files define important registries at top level. When they are
  // included from inside this function, those variables land in local scope
  // unless we explicitly promote them back into the global runtime.
  foreach (get_defined_vars() as $name => $value) {
    if ($name === 'GLOBALS' || in_array($name, $localVarNames, true)) continue;
    $GLOBALS[$name] = $value;
  }
}

function EngineNormalizeActionPayload($action) {
  return RegressionNormalizeAction($action);
}

function EngineActionCardExists($mzid) {
  $mzArr = explode('-', $mzid);
  if (count($mzArr) < 2) return false;
  $zone = GetZone($mzArr[0]);
  if (!is_array($zone)) return false;
  return intval($mzArr[1]) < count($zone);
}

function EngineAddCardToTopOfDeck($player, $cardID, $sourceObject = null) {
  if (function_exists('DeckAddReplacement')) {
    $replaceResult = DeckAddReplacement($player, $cardID, $sourceObject);
    if ($replaceResult) return $replaceResult;
  }
  if (function_exists('TokenCeaseBeforeAdd') && !TokenCeaseBeforeAdd($player, $cardID, $sourceObject)) return null;

  $deckObj = new Deck($cardID, 'Deck', $player);
  $deck = &GetDeck($player);
  array_unshift($deck, $deckObj);

  if ($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach ($properties as $prop => $value) {
      if ($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $deckObj->$prop = $value;
      }
    }
  }

  for ($i = 0; $i < count($deck); ++$i) {
    $deck[$i]->mzIndex = $i;
  }

  return $deckObj;
}

function EngineExecuteLoadedAction($action, $folderPath, $gameName, $options = []) {
  global $updateNumber, $playerID, $frameAnimations;

  // Maintenance gate. This is THE engine write chokepoint — ProcessInput.php routes every sim's
  // actions through here, and the single WriteGamestate() below is what mutates a deck/game file.
  // Gating here rather than in GetNextTurn.php matters: that file is generated and gitignored, so
  // a gate added there would be silently wiped on the next regen.
  //
  // Keyed on $folderPath, not a hardcoded app: each app root has its own maintenance flag, so
  // freezing SWUDeck on swustats cannot freeze SWUSim on petranaki.
  if (function_exists('SWUMaintenanceRequire') && is_string($folderPath) && $folderPath !== '') {
    SWUMaintenanceRequire($folderPath, 'deck');
  }

  $action = EngineNormalizeActionPayload($action);
  $playerID = $action['playerID'];
  $mode = intval($action['mode']);
  $buttonInput = $action['buttonInput'];
  $cardID = $action['cardID'];
  $chkInput = $action['chkInput'];
  $inputText = $action['inputText'];

  $result = [
    'success' => true,
    'message' => '',
    'writeGamestate' => true,
    'updateCache' => $options['updateCache'] ?? true,
    'recordAction' => !($options['disableRecording'] ?? false),
  ];

  // Optional, game-owned validation seam for isolated modes such as scripted tutorials.
  // Normal games do not define the hook and retain the existing action path unchanged.
  if (function_exists('GameValidateEngineAction')) {
    $validation = GameValidateEngineAction($action);
    if (is_array($validation) && array_key_exists('allowed', $validation) && !$validation['allowed']) {
      return [
        'success' => false,
        'message' => strval($validation['message'] ?? 'That action is not available right now.'),
        'writeGamestate' => true,
        'updateCache' => $options['updateCache'] ?? true,
        'recordAction' => false,
      ];
    }
  }

  $matchReplayControlModes = [11101, 11102, 11103, 11104];
  // "Play from Here" (11104) branches a playback session into free play: the guard is lifted so normal
  // actions run, and those actions are NOT recorded into the replay (so Reset still replays the original).
  $matchReplayInterrupted = function_exists('MatchReplayIsInterrupted') && MatchReplayIsInterrupted();
  if (
    empty($options['disableRecording']) &&
    function_exists('MatchReplayIsPlaybackSession') &&
    MatchReplayIsPlaybackSession() &&
    !$matchReplayInterrupted &&
    !in_array($mode, $matchReplayControlModes, true)
  ) {
    $rejectedResult = [
      'success' => false,
      'message' => 'Replay playback sessions can only be advanced with replay controls.',
      'writeGamestate' => false,
      'updateCache' => false,
      'recordAction' => false,
    ];
    if ($mode === 10017) {
      $rejectedResult['botStepApplied'] = false;
      $rejectedResult['botStepRetryable'] = false;
      $rejectedResult['botControllerState'] = BuildBotControllerClientState($folderPath, $gameName);
    }
    return $rejectedResult;
  }

  // Mode 10017 is normally only the browser-to-controller transport. The bot's
  // chosen gameplay action is executed recursively and recorded in its own
  // right. A controller may explicitly request persistence when it recovers
  // engine-owned static queue work that has no nested gameplay action.
  $matchReplayPendingAction = ($result['recordAction'] && !$matchReplayInterrupted && $mode !== 10017)
    ? MatchReplayBeginPotentialAction($folderPath, $gameName)
    : null;

  // Optional game-owned observation seam for features that need the pre-mutation state
  // (for example semantic zone-transition animations). It cannot alter action legality.
  if (function_exists('GameBeforeEngineAction')) {
    GameBeforeEngineAction($action);
  }

  $frameAnimations = [];
  if ($result['updateCache']) {
    SetFrameAnimationCache($gameName, []);
  }
  // Bot transport mode is only a wrapper. Its nested gameplay action owns and
  // commits the semantic frame because the wrapper itself never writes state.
  if($mode !== 10017 && function_exists('GameLogBeginFrame')) {
    GameLogBeginFrame($action, $options);
  }

  if ($mode !== 10015 && function_exists('SetFlashMessage')) SetFlashMessage('');

  switch ($mode) {
    case 100:
      $dqController = new DecisionQueueController();
      // Per-app answer validation (defined by apps that opt in, e.g. SWUSim): reject an answer that
      // is not a candidate of the pending choice instead of letting continuations act on it.
      if (function_exists('SWUValidateDecisionAnswer') && !SWUValidateDecisionAnswer($playerID, strval($cardID))) {
        if (function_exists('SetFlashMessage')) SetFlashMessage("Invalid selection.");
        break;
      }
      $dqController->PopDecision($playerID);
      $dqController->ExecuteStaticMethods($playerID, $cardID);
      break;
    case 10000:
      $macro = $buttonInput;
      $zone = &GetZone($inputText);
      switch ($macro) {
        case 'Shuffle':
          EngineShuffle($zone);
          break;
        default:
          break;
      }
      break;
    case 10001:
      $inpArr = explode('!', $cardID);
      $actionCard = $inpArr[0] ?? '';
      $widgetType = $inpArr[1] ?? '';
      $actionValue = $inpArr[2] ?? '';
      if ($widgetType == 'CustomInput') {
        CustomWidgetInput($playerID, $actionCard, $actionValue);
        break;
      }
      switch ($actionValue) {
        case '-1':
        case '+1':
          $card = &GetZoneObject($actionCard);
          if (is_object($card)) $card->$widgetType += intval($actionValue);
          else $card += intval($actionValue);
          break;
        case 'Notes':
          if (!EngineActionCardExists($actionCard)) break;
          $noteText = str_replace(' ', '_', $inpArr[3] ?? '');
          $card = GetZoneObject($actionCard);
          $widgetCardId = $card->CardID;
          $card = SearchZoneForCard('myCardNotes', $card->CardID, $playerID);
          if ($card != null) {
            $card->Notes = $noteText;
          } else {
            MZAddZone($playerID, 'myCardNotes', $widgetCardId);
            $card = SearchZoneForCard('myCardNotes', $widgetCardId, $playerID);
            $card->Notes = $noteText;
          }
          break;
        default:
          $card = &GetZoneObject($actionCard);
          if (is_object($card)) {
            if ($card->$widgetType == $actionValue) $card->$widgetType = '-';
            else $card->$widgetType = $actionValue;
          } else {
            if ($card == $actionValue) $card = '-';
            else $card = $actionValue;
          }
          break;
      }
      break;
    case 10002:
      $inpArr = explode('!', $cardID);
      $actionCard = $inpArr[0] ?? '';
      $actionValue = $inpArr[1] ?? '';
      $parameterArr = explode(',', $inpArr[2] ?? '');
      // Record the source mzid (e.g. "myLeader1-3") for the duration of this action so app-level
      // AddValidation hooks can tell WHICH zone/pane a card came from — the generated Add*/Validate*
      // path only forwards the card id, losing that context. Additive and app-agnostic: only reads
      // that opt in (currently SWUDeck's per-slot leader swap) are affected.
      $GLOBALS['gEngineActionSourceMZID'] = $actionCard;
      if (!EngineActionCardExists($actionCard)) break;
      $card = GetZoneObject($actionCard);
      switch ($actionValue) {
        case 'Move':
          $card->Remove();
          $destination = $parameterArr[0] ?? '';
          MZAddZone($playerID, $destination, $card->CardID);
          break;
        case 'Add':
          $destination = $parameterArr[0] ?? '';
          MZAddZone($playerID, $destination, $card->CardID);
          break;
        case 'Remove':
          $card->Remove();
          break;
        case 'Swap':
          $destination = $parameterArr[0] ?? '';
          MZClearZone($playerID, $destination);
          MZAddZone($playerID, $destination, $card->CardID);
          break;
        case 'FSM':
          ActionMap($actionCard);
          break;
        default:
          break;
      }
      break;
    case 10003:
      $version = $cardID;
      if ($version == 'current') {
        break;
      } elseif ($version == 'new') {
        $versioningAdapter = AssetVersioningGetLoadedAdapter();
        if (AssetVersioningAdapterEnabled($versioningAdapter)) {
          $result['success'] = false;
          $result['message'] = 'Versions are created automatically when a game result is recorded.';
          break;
        }
        $versionName = $options['versionName'] ?? $inputText;
        SaveVersion($playerID, $versionName);
      } else {
        $versioningAdapter = AssetVersioningGetLoadedAdapter();
        if (str_starts_with((string)$version, 'auto:') && AssetVersioningAdapterEnabled($versioningAdapter)) {
          $loaded = AssetVersioningApplyVersion(
            $versioningAdapter,
            $gameName,
            $playerID,
            intval(substr((string)$version, 5))
          );
          if(!$loaded) {
            $result['success'] = false;
            $result['message'] = 'That version could not be loaded.';
          }
          break;
        }
        if ($folderPath == 'SoulMastersDB') {
          SoulMastersSwitchVersion($version);
          break;
        }
        LoadVersion($playerID, intval($version));
      }
      break;
    case 10004:
      if (function_exists('SWUDoUndo')) {
        // SWUSim multi-step undo. 'undoKind' selects step (default) vs phase (Undo Phase button).
        // Sourced from $_GET via ProcessInput's options array — these requests are GETs, so a
        // $_POST fallback here is dead code that silently forced every undo to 'step'.
        $undoKind = ($options['undoKind'] ?? '') === 'phase' ? 'phase' : 'step';
        // Pass root/game so SWUUndoNeedsConsent can gate on private-vs-public (private = always free).
        SWUDoUndo($playerID, $undoKind, $folderPath, $gameName);
      } else {
        // Legacy behaviour for other sims
        LoadVersion($playerID);
        if (!($options['suppressUndoFlash'] ?? false) && function_exists('SetFlashMessage')) {
          SetFlashMessage('Player ' . $playerID . ' undid their last action.');
        }
      }
      break;
    case 10008:
      // Approve undo request (called by the opponent)
      if (function_exists('SWUApproveUndo')) SWUApproveUndo();
      break;
    case 10009:
      // Deny undo request (called by the opponent)
      if (function_exists('SWUDenyUndo')) SWUDenyUndo();
      break;
    case 10010:
      // Block future undo requests permanently (called by the opponent)
      if (!function_exists('GetSWUVar')) break;
      $targetPlayer = intval(GetSWUVar('PENDING_BLOCK_PROMPT_FOR', '0'));
      if ($targetPlayer >= 1 && $targetPlayer <= 2) {
        SetSWUVar('UNDO_BLOCKED_' . $targetPlayer, 'true');
      }
      SetSWUVar('PENDING_BLOCK_PROMPT_FOR', '');
      SetFlashMessage('Future undo requests from this player are blocked.');
      break;
    case 10018:
      // SWUSim — save a gamestate bookmark. Optional label arrives in inputText.
      // Private-lobby gating is enforced inside SWUTakeBookmark, not here.
      if (function_exists('SWUTakeBookmark')) SWUTakeBookmark($playerID, strval($inputText), $folderPath, $gameName);
      break;
    case 10019:
      // SWUSim — load a gamestate bookmark. Bookmark id arrives in buttonInput.
      if (function_exists('SWULoadBookmark')) SWULoadBookmark($playerID, intval($buttonInput), $folderPath, $gameName);
      break;
    case 10011:
      // Keep allowing undo requests (dismiss block prompt)
      if (!function_exists('GetSWUVar')) break;
      SetSWUVar('PENDING_BLOCK_PROMPT_FOR', '');
      break;
    case 10015:
      if (function_exists('SetFlashMessage')) SetFlashMessage('');
      if (function_exists('SetShortcutPreferencesState')) {
        SetShortcutPreferencesState($playerID, $inputText);
      }
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 10017:
      if (function_exists('ProcessBotControllerStep')) {
        $botResult = ProcessBotControllerStep($playerID, $folderPath, $gameName);
        $result['success'] = !empty($botResult['success']);
        $result['message'] = strval($botResult['message'] ?? '');
        $result['writeGamestate'] = !empty($botResult['writeGamestate']);
        $result['updateCache'] = !empty($botResult['updateCache']);
        $result['recordAction'] = false;
        $result['botStepApplied'] = !empty($botResult['applied']);
        $result['botStepRetryable'] = !array_key_exists('retryable', $botResult) || !empty($botResult['retryable']);
      } else {
        $result['success'] = false;
        $result['message'] = 'Bot step is not available.';
        $result['writeGamestate'] = false;
        $result['updateCache'] = false;
        $result['recordAction'] = false;
        $result['botStepApplied'] = false;
        $result['botStepRetryable'] = false;
      }
      $result['botControllerState'] = BuildBotControllerClientState($folderPath, $gameName);
      break;
    case 10005:
      $versioningAdapter = AssetVersioningGetLoadedAdapter();
      if (AssetVersioningAdapterEnabled($versioningAdapter)) {
        $result['success'] = false;
        $result['message'] = 'Versions are created automatically when a game result is recorded.';
      } else {
        SaveVersion($playerID);
      }
      break;
    case 10006:
      if (($playerID === 1 || $playerID === 2) && function_exists('TriggerGameOver')) {
        if(function_exists('GameLogEvent')) {
          GameLogEvent('concede', ['by' => 'p' . intval($playerID)]);
        }
        TriggerGameOver($playerID);
        if (function_exists('SetFlashMessage')) {
          SetFlashMessage('Player ' . $playerID . ' conceded.');
        }
        if (function_exists('WriteLog')) {
          WriteLog('Player ' . $playerID . ' conceded.');
        }
      } else {
        $result['success'] = false;
        $result['message'] = 'Concede is not available for this action.';
      }
      break;
    case 10007: // concede the whole match (Bo3)
      if (($playerID === 1 || $playerID === 2) && function_exists('SWUReadMatchRef') && function_exists('SWUConcedeMatch')) {
        $ref = SWUReadMatchRef($gameName);
        if (is_array($ref)) {
          $cm = SWUConcedeMatch($ref['matchId'], $playerID);
          if (function_exists('SetFlashMessage') && is_array($cm)) {
            SetFlashMessage('MATCHOVER:Player ' . intval($cm['winner']) . ' wins the match by concession.');
          }
        } else if (function_exists('TriggerGameOver')) {
          TriggerGameOver($playerID); // not a match → fall back to game concede
        }
      } else {
        $result['success'] = false;
        $result['message'] = 'Match concede unavailable.';
      }
      break;
    case 10012: // convert a finished Bo1 into a Bo3 (mutual agreement). (10008 was taken by undo-approve.)
      if (($playerID === 1 || $playerID === 2) && function_exists('SWUReadMatchRef')
          && function_exists('SWURequestConvertToBo3') && function_exists('SWUAcceptConvertToBo3')) {
        $ref = SWUReadMatchRef($gameName);
        if (is_array($ref)) {
          SWURequestConvertToBo3($ref['matchId'], $playerID);
          SWUAcceptConvertToBo3($ref['matchId']); // promotes when both have requested; clients follow the sideboard pointer
        } else {
          $result['success'] = false;
          $result['message'] = 'Convert to Bo3 unavailable.';
        }
      } else if (($playerID === 1 || $playerID === 2) && $folderPath === 'GrandArchiveSim'
          && function_exists('MatchReadRef') && function_exists('MatchRequestConvertToBo3')) {
        $ref = MatchReadRef($folderPath, $gameName);
        if (is_array($ref)) {
          MatchRequestConvertToBo3($folderPath, $ref['matchId'], $playerID);
          MatchAcceptConvertToBo3($folderPath, $ref['matchId']); // promotes when both have requested; clients poll EndGameInfo
        } else { $result['success'] = false; $result['message'] = 'Convert to Bo3 unavailable.'; }
      } else {
        $result['success'] = false;
        $result['message'] = 'Convert to Bo3 unavailable.';
      }
      break;
    case 10013: // request a QUICK rematch (no sideboard). inputText = bestOf ('1'|'3')
    case 10016: // request a FULL rematch (sideboard).     inputText = bestOf ('1'|'3')
      if (($playerID === 1 || $playerID === 2) && function_exists('SWUReadMatchRef')
          && function_exists('SWURequestRematch') && function_exists('SWUAcceptRematch')) {
        $ref = SWUReadMatchRef($gameName);
        if (is_array($ref)) {
          $bestOf = (intval($inputText) === 3) ? 3 : 1;
          $sideboard = ($mode === 10016);
          SWURequestRematch($ref['matchId'], $playerID, $bestOf, $sideboard);
          SWUAcceptRematch($ref['matchId']); // creates the new match when both have requested
        } else { $result['success'] = false; $result['message'] = 'Rematch unavailable.'; }
      } else if (($playerID === 1 || $playerID === 2) && in_array($folderPath, ['GrandArchiveSim', 'AzukiSim'], true)
          && function_exists('MatchReadRef') && function_exists('MatchRequestRematch')) {
        if ($folderPath === 'AzukiSim' && $mode !== 10013) {
          $result['success'] = false;
          $result['message'] = 'Only quick rematches are available.';
          break;
        }
        $ref = MatchReadRef($folderPath, $gameName);
        if (is_array($ref)) {
          $bestOf = ($folderPath === 'AzukiSim') ? 1 : ((intval($inputText) === 3) ? 3 : 1);
          $sideboard = ($folderPath !== 'AzukiSim' && $mode === 10016);
          MatchRequestRematch($folderPath, $ref['matchId'], $playerID, $bestOf, $sideboard);
          MatchAcceptRematch($folderPath, $ref['matchId']); // creates the new match when both have requested
        } else { $result['success'] = false; $result['message'] = 'Rematch unavailable.'; }
      } else { $result['success'] = false; $result['message'] = 'Rematch unavailable.'; }
      break;
    case 10014:
      // Drag-to-move (a sandbox tool) REMOVES the dragged card and re-adds a FRESH copy from just its
      // CardID at the destination — wiping Damage/Subcards/Status — and can target ANY card, including an
      // opponent's. It's disabled in the SWUSim client (IsDragDropEnabled), and has no valid use in
      // competitive play, so reject it server-side too (defense in depth: a modified/replayed client
      // can't reset an opponent's damaged leader). Apps that keep the sandbox affordance (RBSim/Gudnak/…)
      // are unaffected.
      if ($folderPath === 'SWUSim') { $result['success'] = false; $result['message'] = 'Drag-to-move is disabled.'; break; }
      $inpArr = explode('!', $cardID);
      $moveCard = $inpArr[0] ?? '';
      $destination = $inpArr[1] ?? '';
      if (!EngineActionCardExists($moveCard)) break;
      $card = GetZoneObject($moveCard);
      if ($card->DragMode() != 'Clone') $card->Remove();
      MZAddZone($playerID, $destination, $card->CardID);
      break;
    case 11000:
      $createdBy = $options['createdBy'] ?? 'anonymous';
      $recordingResult = RegressionStartRecording($folderPath, $gameName, $playerID, $createdBy);
      $result['success'] = $recordingResult['success'];
      $result['message'] = $recordingResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11001:
      $recordingResult = RegressionStopRecording($folderPath, $gameName);
      $result['success'] = $recordingResult['success'];
      $result['message'] = $recordingResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11002:
      $recordingResult = RegressionAddAssertion($folderPath, $gameName, $playerID, $inputText);
      $result['success'] = $recordingResult['success'];
      $result['message'] = $recordingResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11003:
      $payload = json_decode($inputText, true);
      if (!is_array($payload)) {
        $result['success'] = false;
        $result['message'] = 'Fixture save payload must be valid JSON.';
      } else {
        $recordingResult = RegressionSaveFixture(
          $folderPath,
          $gameName,
          strval($payload['slug'] ?? ''),
          strval($payload['name'] ?? ''),
          strval($payload['notes'] ?? '')
        );
        $result['success'] = $recordingResult['success'];
        $result['message'] = $recordingResult['message'];
      }
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11007:
      $payload = json_decode($inputText, true);
      if (!is_array($payload)) {
        $result['success'] = false;
        $result['message'] = 'Fixture re-record payload must be valid JSON.';
      } else {
        $recordingResult = RegressionRerecordFixture(
          $folderPath,
          $gameName,
          strval($payload['slug'] ?? '')
        );
        $result['success'] = $recordingResult['success'];
        $result['message'] = $recordingResult['message'];
      }
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11004:
      $payload = json_decode($inputText, true);
      if (!is_array($payload)) {
        $result['success'] = false;
        $result['message'] = 'Fixture replay payload must be valid JSON.';
      } else {
        $replayResult = RegressionReplayFixture(
          $folderPath,
          $gameName,
          strval($payload['slug'] ?? ''),
          !empty($payload['replayActions'])
        );
        $result['success'] = $replayResult['success'];
        $result['message'] = $replayResult['message'];
      }
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11005:
      $payload = json_decode($inputText, true);
      if (!is_array($payload)) {
        $result['success'] = false;
        $result['message'] = 'Fixture replay-step payload must be valid JSON.';
      } else {
        $replayResult = RegressionReplayFixtureNextAction(
          $folderPath,
          $gameName,
          strval($payload['slug'] ?? '')
        );
        $result['success'] = $replayResult['success'];
        $result['message'] = $replayResult['message'];
      }
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11006:
      $payload = json_decode($inputText, true);
      if (!is_array($payload) || empty($payload['slug']) || empty($payload['cardId'])) {
        $result['success'] = false;
        $result['message'] = 'Link card payload must be valid JSON with slug and cardId fields.';
      } else {
        $linkSlug = RegressionSanitizeSlug(strval($payload['slug']));
        $linkCardId = strval($payload['cardId']);
        $conn = GetLocalMySQLConnection();
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS test_card_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            root_name VARCHAR(100) NOT NULL,
            test_slug VARCHAR(255) NOT NULL,
            card_id VARCHAR(100) NOT NULL,
            UNIQUE KEY uq_test_card (root_name, test_slug, card_id),
            KEY idx_root_card (root_name, card_id)
        )");
        $linkStmt = mysqli_prepare($conn, "INSERT IGNORE INTO test_card_links (root_name, test_slug, card_id) VALUES (?, ?, ?)");
        if ($linkStmt) {
          mysqli_stmt_bind_param($linkStmt, "sss", $folderPath, $linkSlug, $linkCardId);
          $linkOk = mysqli_stmt_execute($linkStmt);
          mysqli_stmt_close($linkStmt);
          $result['success'] = $linkOk;
          $result['message'] = $linkOk
            ? "Linked card $linkCardId to fixture $linkSlug."
            : 'Failed to link card: ' . mysqli_error($conn);
        } else {
          $result['success'] = false;
          $result['message'] = 'Prepare failed: ' . mysqli_error($conn);
        }
        mysqli_close($conn);
      }
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11008:
    case 11009:
      $cardId = trim($inputText);
      if ($cardId === '') {
        $result['success'] = false;
        $result['message'] = 'Card ID is required.';
      } else {
        $targetPlayer = ($mode === 11008) ? 1 : 2;
        MZAddZone($targetPlayer, 'myHand', $cardId);
        $result['message'] = "Added card $cardId to player $targetPlayer hand.";
      }
      break;
    case 11010:
    case 11011:
      $cardId = trim($inputText);
      if ($cardId === '') {
        $result['success'] = false;
        $result['message'] = 'Card ID is required.';
      } else {
        $targetPlayer = ($mode === 11010) ? 1 : 2;
        EngineAddCardToTopOfDeck($targetPlayer, $cardId);
        $result['message'] = "Added card $cardId to player $targetPlayer top deck.";
      }
      break;
    case 11012:
    case 11013:
      $cardId = trim($inputText);
      if ($cardId === '') {
        $result['success'] = false;
        $result['message'] = 'Card ID is required.';
      } else {
        $targetPlayer = ($mode === 11012) ? 1 : 2;
        MZAddZone($targetPlayer, 'myGraveyard', $cardId);
        $result['message'] = "Added card $cardId to player $targetPlayer graveyard.";
      }
      break;
    case 11101:
      $replayResult = MatchReplayReplayNextActionLoaded($folderPath, $gameName);
      $result['success'] = $replayResult['success'];
      $result['message'] = $replayResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = true;
      $result['recordAction'] = false;
      break;
    case 11102:
      $replayResult = MatchReplayLoadInitialForPlayback($folderPath, $gameName, 0);
      $result['success'] = $replayResult['success'];
      $result['message'] = $replayResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = true;
      $result['recordAction'] = false;
      break;
    case 11103:
      $replayResult = MatchReplayReplayAllLoaded($folderPath, $gameName);
      $result['success'] = $replayResult['success'];
      $result['message'] = $replayResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = true;
      $result['recordAction'] = false;
      break;
    case 11104:
      $replayResult = MatchReplayEnterInterrupt($folderPath, $gameName);
      $result['success'] = $replayResult['success'];
      $result['message'] = $replayResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = true;
      $result['recordAction'] = false;
      break;
  }

  if (!$result['success'] || !$result['writeGamestate'] || !$result['recordAction']) {
    MatchReplayCancelPotentialAction($matchReplayPendingAction);
  }

  if ($result['success'] && $result['writeGamestate'] && function_exists('GameAfterEngineAction')) {
    GameAfterEngineAction($action, $result);
  }

  if ($result['success'] && $result['writeGamestate'] && function_exists('ProcessGoldfishAutomation')) {
    ProcessGoldfishAutomation();
  }

  if ($result['writeGamestate']) {
    if ($result['recordAction'] && !$matchReplayInterrupted) {
      MatchReplayCommitAction($matchReplayPendingAction, $action);
    }
    // SWUSim state-based game-over: catch a base sitting at lethal damage (incl. post-undo zombie
    // states) BEFORE writing, so the GAMEOVER flash persists to the client. No-op for other sims.
    if (function_exists('SWUCheckBaseDefeatState')) SWUCheckBaseDefeatState();
    ++$updateNumber;
    if(function_exists('GameLogCommitFrame')) {
      GameLogCommitFrame($gameName, $updateNumber, $action, $result);
    }
    WriteGamestate('./' . $folderPath . '/');
    // SWUSim-only Bo3 match advance (function exists only when MatchFlow is loaded; no-op for other sims).
    if (function_exists('SWUAfterActionMatchHook')) {
      SWUAfterActionMatchHook($folderPath, $gameName);
    }
    // GrandArchiveSim Bo3 match advance via the shared Core/Match framework.
    if ($folderPath === 'GrandArchiveSim' && function_exists('MatchAfterActionHook')) {
      MatchAfterActionHook($folderPath, $gameName);
    }
    if ($folderPath === 'AzukiSim' && function_exists('MatchAfterActionHook')) {
      MatchAfterActionHook($folderPath, $gameName);
    }
    if (is_numeric($gameName)
        && function_exists('TouchOwnershipLastUpdated')
        && function_exists('GetEditAuth') && GetEditAuth() === 'AssetOwner') {
      TouchOwnershipLastUpdated(intval($gameName));
    }
    if ($result['updateCache']) {
      SetFrameAnimationCache($gameName, $frameAnimations);
      GamestateUpdated($gameName);
      if (function_exists('TouchActiveGame')) {
        TouchActiveGame($folderPath, $gameName);
      }
    }
    if ($result['recordAction'] && RegressionIsRecordingActive($folderPath, $gameName)) {
      RegressionRecordAction($folderPath, $gameName, $action);
    }
  }

  return $result;
}

function EngineRunAction($action, $folderPath, $gameId, $options = []) {
  EngineLoadRootRuntime($folderPath);
  global $gameName;
  $gameName = strval($gameId);
  ParseGamestate('./' . $folderPath . '/');
  return EngineExecuteLoadedAction($action, $folderPath, $gameName, $options);
}
