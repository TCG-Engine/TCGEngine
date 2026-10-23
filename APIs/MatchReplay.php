<?php
// Maintenance gate — must precede any write.
// Writes replay state under the game tree the rewrite walks.
require_once __DIR__ . '/../AppCore/SWU/Maintenance.php';
SWUMaintenanceRequire('SWUDeck', 'deck');


error_reporting(E_ALL);

header('Content-Type: application/json');

include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/ViewerIdentity.php';
include_once __DIR__ . '/../Core/EngineActionRunner.php';
include_once __DIR__ . '/../Core/NetworkingLibraries.php';

function MatchReplayApiRespond($statusCode, $payload) {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

function MatchReplayApiRootIsValid($rootName) {
  return preg_match('/^[A-Za-z0-9_]+$/', strval($rootName)) === 1;
}

function MatchReplayApiLoadGame($rootName, $gameName) {
  if (!MatchReplayApiRootIsValid($rootName)) {
    MatchReplayApiRespond(400, ['success' => false, 'message' => 'Invalid root name.']);
  }
  if (!IsGameNameValid($gameName)) {
    MatchReplayApiRespond(400, ['success' => false, 'message' => 'Invalid game name.']);
  }

  if (!SimGameExists($rootName, $gameName)) {
    MatchReplayApiRespond(404, ['success' => false, 'message' => 'Game not found.']);
  }

  EngineLoadRootRuntime($rootName);
  $GLOBALS['gameName'] = strval($gameName);
  ParseGamestate(dirname(__DIR__) . '/' . $rootName . '/');
}

function MatchReplayApiCreateTempGameName($rootName) {
  $gameRoot = __DIR__ . '/../' . $rootName . '/Games';
  if (!is_dir($gameRoot)) mkdir($gameRoot, 0777, true);

  for ($attempt = 0; $attempt < 20; ++$attempt) {
    $candidate = date('ymdHis') . strval(random_int(1000, 9999));
    if (!is_dir($gameRoot . '/' . $candidate)) return $candidate;
  }
  MatchReplayApiRespond(500, ['success' => false, 'message' => 'Unable to allocate replay game name.']);
}

$action = strtolower(trim(strval($_GET['action'] ?? '')));

if ($action === 'download') {
  $rootName = trim(strval($_GET['folderPath'] ?? $_GET['rootName'] ?? ''));
  $gameName = trim(strval($_GET['gameName'] ?? ''));
  $authKey = trim(strval($_GET['authKey'] ?? ''));
  // Same seat-count omission as SubmitBugReport had: without it P3 normalises to spectator and P4
  // falls out of range, so a 3-4 seat player cannot download their own replay.
  $viewerInfo = NormalizeViewerIdentity($_GET['playerID'] ?? '', SimGameMaxSeats($rootName));
  if ($viewerInfo['viewerID'] === '') {
    MatchReplayApiRespond(400, ['success' => false, 'message' => 'Invalid player ID.']);
  }

  MatchReplayApiLoadGame($rootName, $gameName);

  if (!$viewerInfo['isSpectator'] && $viewerInfo['viewerSeat'] !== null && $authKey === '' && isset($_COOKIE['lastAuthKey'])) {
    $authKey = trim(strval($_COOKIE['lastAuthKey']));
  }

  if (!SimGameValidateViewerAuth($rootName, $gameName, $viewerInfo, $authKey)) {
    MatchReplayApiRespond(403, ['success' => false, 'message' => 'Invalid auth key.']);
  }
  if (!MatchReplayCanDownload()) {
    MatchReplayApiRespond(409, ['success' => false, 'message' => 'Replay can only be saved after the match is over.']);
  }

  $payload = MatchReplayBuildDownloadPayload($rootName, $gameName);
  // A root that seals (MatchReplayShouldSeal): only a seated player gets one, sealed to their seat; never plain.
  if (MatchReplayShouldSeal()) {
    if ($viewerInfo['isSpectator'] || intval($viewerInfo['viewerSeat'] ?? 0) < 1) {
      MatchReplayApiRespond(403, ['success' => false, 'message' => 'Only a player of this game can save its replay.']);
    }
    $payload = MatchReplaySeal($payload, intval($viewerInfo['viewerSeat']));
    if ($payload === null) {
      MatchReplayApiRespond(503, ['success' => false, 'message' => 'Replays cannot be saved on this server right now.']);
    }
  }

  MatchReplayApiRespond(200, [
    'success' => true,
    'replay' => $payload,
  ]);
}

if ($action === 'import') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    MatchReplayApiRespond(405, ['success' => false, 'message' => 'Use POST to import a replay.']);
  }

  $body = json_decode(file_get_contents('php://input'), true);
  if (!is_array($body)) {
    MatchReplayApiRespond(400, ['success' => false, 'message' => 'Invalid JSON body.']);
  }

  $replay = $body['replay'] ?? $body;
  // A sealed record: open it with the root's key (loaded below) into the plain payload + the seat it plays from.
  $sealedSeat = 0;
  if (is_array($replay) && strval($replay['format'] ?? '') === MATCH_REPLAY_SEALED_FORMAT) {
    $sealedRoot = strval($replay['rootName'] ?? '');
    if (!MatchReplayApiRootIsValid($sealedRoot) || !is_dir(__DIR__ . '/../' . $sealedRoot)) {
      MatchReplayApiRespond(400, ['success' => false, 'message' => 'Replay root is not available on this server.']);
    }
    EngineLoadRootRuntime($sealedRoot);
    $opened = MatchReplayUnseal($replay);
    if ($opened === null) MatchReplayApiRespond(400, ['success' => false, 'message' => 'This replay could not be opened.']);
    $replay = $opened['payload'];
    $sealedSeat = intval($opened['seat']);
  }
  $validationError = MatchReplayValidateReplayPayload($replay);
  if ($validationError !== '') {
    MatchReplayApiRespond(400, ['success' => false, 'message' => $validationError]);
  }

  $rootName = strval($replay['rootName']);
  if (!MatchReplayApiRootIsValid($rootName) || !is_dir(__DIR__ . '/../' . $rootName)) {
    MatchReplayApiRespond(400, ['success' => false, 'message' => 'Replay root is not available on this server.']);
  }

  EngineLoadRootRuntime($rootName);
  if (!MatchReplayIsEnabled()) {
    MatchReplayApiRespond(400, ['success' => false, 'message' => 'This game root does not support match replay playback.']);
  }

  $gameName = MatchReplayApiCreateTempGameName($rootName);
  $replayAuthKey = bin2hex(random_bytes(16));
  $replayAuthKeys = MatchReplayImportAuthKeys($replayAuthKey, $sealedSeat);
  $playSeat = $sealedSeat > 0 ? $sealedSeat : 1;
  if (!SimGameWriteAuthKeys($rootName, $gameName, $replayAuthKeys)) {
    MatchReplayApiRespond(500, ['success' => false, 'message' => 'Unable to create replay authentication.']);
  }

  $gameDir = __DIR__ . '/../' . $rootName . '/Games/' . $gameName;
  mkdir($gameDir, 0777, true);
  file_put_contents($gameDir . '/Gamestate.txt', strval($replay['initialGamestate']));

  $GLOBALS['gameName'] = strval($gameName);
  ParseGamestate(dirname(__DIR__) . '/' . $rootName . '/');
  MatchReplaySetInitialGamestateText(strval($replay['initialGamestate']));

  $commandState = MatchReplayEmptyCommandState();
  $commandState['actions'] = array_values(array_map('MatchReplayNormalizeAction', $replay['actions']));
  $commandState['nextActionIndex'] = 0;
  $commandState['playback'] = true;
  $commandState['sourceGameName'] = strval($replay['gameName'] ?? '');
  $commandState['sourceSavedAt'] = strval($replay['savedAt'] ?? '');
  if ($sealedSeat > 0) { $commandState['sealed'] = true; $commandState['sealedSeat'] = $sealedSeat; }
  MatchReplaySetCommandState($commandState);
  MatchReplayMarkPlaybackGame($gameName);
  if (function_exists('SetFlashMessage')) {
    SetFlashMessage('Loaded replay with ' . count($commandState['actions']) . ' actions.');
  }
  WriteGamestate(dirname(__DIR__) . '/' . $rootName . '/');
  if (function_exists('GamestateUpdated')) {
    GamestateUpdated($gameName);
  }

  MatchReplayApiRespond(200, [
    'success' => true,
    'rootName' => $rootName,
    'gameName' => $gameName,
    'authKey' => $replayAuthKey,
    'nextTurnUrl' => './NextTurn.php?gameName=' . rawurlencode($gameName) . '&playerID=' . $playSeat . '&folderPath=' . rawurlencode($rootName) . '&authKey=' . rawurlencode($replayAuthKey) . '&replay=1',
  ] + ($sealedSeat > 0 ? ['playerID' => $sealedSeat] : []));
}

MatchReplayApiRespond(400, ['success' => false, 'message' => 'Unsupported match replay action.']);

?>
