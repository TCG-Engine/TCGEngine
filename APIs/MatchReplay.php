<?php

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

  $gameDir = __DIR__ . '/../' . $rootName . '/Games/' . $gameName;
  if (!is_dir($gameDir)) {
    MatchReplayApiRespond(404, ['success' => false, 'message' => 'Game not found.']);
  }

  EngineLoadRootRuntime($rootName);
  $GLOBALS['gameName'] = strval($gameName);
  ParseGamestate('./' . $rootName . '/');
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
  $viewerInfo = NormalizeViewerIdentity($_GET['playerID'] ?? '');
  if ($viewerInfo['viewerID'] === '') {
    MatchReplayApiRespond(400, ['success' => false, 'message' => 'Invalid player ID.']);
  }

  MatchReplayApiLoadGame($rootName, $gameName);

  if (($viewerInfo['viewerID'] === '1' || $viewerInfo['viewerID'] === '2') && $authKey === '' && isset($_COOKIE['lastAuthKey'])) {
    $authKey = trim(strval($_COOKIE['lastAuthKey']));
  }

  if (!SimGameValidateViewerAuth($rootName, $gameName, $viewerInfo, $authKey)) {
    MatchReplayApiRespond(403, ['success' => false, 'message' => 'Invalid auth key.']);
  }
  if (!MatchReplayCanDownload()) {
    MatchReplayApiRespond(409, ['success' => false, 'message' => 'Replay can only be saved after the match is over.']);
  }

  MatchReplayApiRespond(200, [
    'success' => true,
    'replay' => MatchReplayBuildDownloadPayload($rootName, $gameName),
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
  $gameDir = __DIR__ . '/../' . $rootName . '/Games/' . $gameName;
  mkdir($gameDir, 0777, true);
  file_put_contents($gameDir . '/Gamestate.txt', strval($replay['initialGamestate']));

  $GLOBALS['gameName'] = strval($gameName);
  ParseGamestate('./' . $rootName . '/');
  MatchReplaySetInitialGamestateText(strval($replay['initialGamestate']));

  $commandState = MatchReplayEmptyCommandState();
  $commandState['actions'] = array_values(array_map('MatchReplayNormalizeAction', $replay['actions']));
  $commandState['nextActionIndex'] = 0;
  $commandState['playback'] = true;
  $commandState['sourceGameName'] = strval($replay['gameName'] ?? '');
  $commandState['sourceSavedAt'] = strval($replay['savedAt'] ?? '');
  MatchReplaySetCommandState($commandState);
  MatchReplayMarkPlaybackGame($gameName);
  if (function_exists('SetFlashMessage')) {
    SetFlashMessage('Loaded replay with ' . count($commandState['actions']) . ' actions.');
  }
  WriteGamestate('./' . $rootName . '/');
  if (function_exists('GamestateUpdated')) {
    GamestateUpdated($gameName);
  }

  MatchReplayApiRespond(200, [
    'success' => true,
    'rootName' => $rootName,
    'gameName' => $gameName,
    'nextTurnUrl' => './NextTurn.php?gameName=' . rawurlencode($gameName) . '&playerID=1&folderPath=' . rawurlencode($rootName) . '&replay=1',
  ]);
}

MatchReplayApiRespond(400, ['success' => false, 'message' => 'Unsupported match replay action.']);

?>
