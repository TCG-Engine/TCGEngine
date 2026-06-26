<?php

function MatchReplayModuleConfig() {
  if (!function_exists('GetModuleConfig')) return null;

  $raw = GetModuleConfig('MatchReplay');
  if ($raw === null || trim(strval($raw)) === '') return null;

  $parts = array_map('trim', explode(',', strval($raw)));
  if (count($parts) < 2) return null;

  $initialField = $parts[0];
  $commandsField = $parts[1];
  if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $initialField)) return null;
  if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $commandsField)) return null;

  return [
    'initialField' => $initialField,
    'commandsField' => $commandsField,
  ];
}

function MatchReplayIsEnabled() {
  return MatchReplayModuleConfig() !== null;
}

function MatchReplayGetterName($fieldName) {
  return 'Get' . strval($fieldName);
}

function MatchReplaySetterName($fieldName) {
  return 'Set' . strval($fieldName);
}

function MatchReplayGetFieldValue($fieldName) {
  $getter = MatchReplayGetterName($fieldName);
  if (!function_exists($getter)) return '-';
  $value = $getter();
  return is_string($value) ? $value : strval($value);
}

function MatchReplaySetFieldValue($fieldName, $value) {
  $setter = MatchReplaySetterName($fieldName);
  if (!function_exists($setter)) return false;
  $setter(strval($value));
  return true;
}

function MatchReplayIsEmptyStoredValue($value) {
  $value = trim(strval($value));
  return $value === '' || $value === '-';
}

function MatchReplayEncodeData($data) {
  $json = json_encode($data, JSON_UNESCAPED_SLASHES);
  if ($json === false) return '-';

  $payload = function_exists('gzencode') ? gzencode($json, 6) : $json;
  if ($payload === false) return '-';
  return 'MR1:' . base64_encode($payload);
}

function MatchReplayDecodeData($storedValue) {
  $storedValue = trim(strval($storedValue));
  if (MatchReplayIsEmptyStoredValue($storedValue)) return null;

  if (strpos($storedValue, 'MR1:') === 0) {
    $payload = base64_decode(substr($storedValue, 4), true);
    if ($payload === false) return null;
    if (function_exists('gzdecode')) {
      $decodedPayload = @gzdecode($payload);
      if ($decodedPayload !== false) $payload = $decodedPayload;
    }
    $decoded = json_decode($payload, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
  }

  $decoded = json_decode($storedValue, true);
  return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
}

function MatchReplayArrayIsList($value) {
  if (!is_array($value)) return false;
  $expected = 0;
  foreach (array_keys($value) as $key) {
    if ($key !== $expected) return false;
    ++$expected;
  }
  return true;
}

function MatchReplayNormalizeAction($action) {
  if (function_exists('RegressionNormalizeAction')) {
    return RegressionNormalizeAction($action);
  }
  return [
    'playerID' => intval($action['playerID'] ?? 0),
    'mode' => intval($action['mode'] ?? 0),
    'buttonInput' => strval($action['buttonInput'] ?? ''),
    'cardID' => strval($action['cardID'] ?? ''),
    'chkInput' => array_values(array_map('strval', $action['chkInput'] ?? [])),
    'inputText' => strval($action['inputText'] ?? ''),
  ];
}

function MatchReplayEmptyCommandState() {
  return [
    'format' => 'tcgengine-match-replay-commands-v1',
    'createdAt' => date('c'),
    'updatedAt' => date('c'),
    'actions' => [],
    'nextActionIndex' => 0,
    'playback' => false,
  ];
}

function MatchReplayNormalizeCommandState($decoded) {
  $state = MatchReplayEmptyCommandState();
  if (MatchReplayArrayIsList($decoded)) {
    $state['actions'] = array_values(array_map('MatchReplayNormalizeAction', $decoded));
    return $state;
  }
  if (!is_array($decoded)) return $state;

  $actions = $decoded['actions'] ?? [];
  if (is_array($actions)) {
    $state['actions'] = array_values(array_map('MatchReplayNormalizeAction', $actions));
  }
  $state['nextActionIndex'] = max(0, intval($decoded['nextActionIndex'] ?? 0));
  $state['nextActionIndex'] = min($state['nextActionIndex'], count($state['actions']));
  $state['playback'] = !empty($decoded['playback']);
  $state['createdAt'] = strval($decoded['createdAt'] ?? $state['createdAt']);
  $state['updatedAt'] = strval($decoded['updatedAt'] ?? $state['updatedAt']);
  if (isset($decoded['sourceGameName'])) $state['sourceGameName'] = strval($decoded['sourceGameName']);
  if (isset($decoded['sourceSavedAt'])) $state['sourceSavedAt'] = strval($decoded['sourceSavedAt']);
  return $state;
}

function MatchReplayGetCommandState() {
  $config = MatchReplayModuleConfig();
  if ($config === null) return MatchReplayEmptyCommandState();
  return MatchReplayNormalizeCommandState(MatchReplayDecodeData(MatchReplayGetFieldValue($config['commandsField'])));
}

function MatchReplaySetCommandState($state) {
  $config = MatchReplayModuleConfig();
  if ($config === null) return false;
  $state = MatchReplayNormalizeCommandState($state);
  $state['updatedAt'] = date('c');
  return MatchReplaySetFieldValue($config['commandsField'], MatchReplayEncodeData($state));
}

function MatchReplayGetInitialGamestateText() {
  $config = MatchReplayModuleConfig();
  if ($config === null) return '';
  $decoded = MatchReplayDecodeData(MatchReplayGetFieldValue($config['initialField']));
  return is_string($decoded) ? $decoded : '';
}

function MatchReplaySetInitialGamestateText($text) {
  $config = MatchReplayModuleConfig();
  if ($config === null) return false;
  return MatchReplaySetFieldValue($config['initialField'], MatchReplayEncodeData(strval($text)));
}

function MatchReplayBeginPotentialAction($rootName, $gameName) {
  $config = MatchReplayModuleConfig();
  if ($config === null) return null;

  $previousInitial = MatchReplayGetFieldValue($config['initialField']);
  $previousCommands = MatchReplayGetFieldValue($config['commandsField']);
  $changed = false;

  if (MatchReplayIsEmptyStoredValue($previousInitial)) {
    $initialText = function_exists('RegressionCurrentGamestateText')
      ? RegressionCurrentGamestateText($rootName, $gameName)
      : '';
    if ($initialText !== '') {
      MatchReplaySetFieldValue($config['initialField'], MatchReplayEncodeData($initialText));
      $changed = true;
    }
  }

  if (MatchReplayIsEmptyStoredValue($previousCommands)) {
    MatchReplaySetFieldValue($config['commandsField'], MatchReplayEncodeData(MatchReplayEmptyCommandState()));
    $changed = true;
  }

  return [
    'config' => $config,
    'changed' => $changed,
    'previousInitial' => $previousInitial,
    'previousCommands' => $previousCommands,
  ];
}

function MatchReplayCancelPotentialAction($pendingState) {
  if (!is_array($pendingState) || empty($pendingState['changed'])) return;
  $config = $pendingState['config'] ?? null;
  if (!is_array($config)) return;
  MatchReplaySetFieldValue($config['initialField'], strval($pendingState['previousInitial'] ?? '-'));
  MatchReplaySetFieldValue($config['commandsField'], strval($pendingState['previousCommands'] ?? '-'));
}

function MatchReplayCommitAction($pendingState, $action) {
  $config = is_array($pendingState) ? ($pendingState['config'] ?? MatchReplayModuleConfig()) : MatchReplayModuleConfig();
  if ($config === null) return false;

  $state = MatchReplayNormalizeCommandState(MatchReplayDecodeData(MatchReplayGetFieldValue($config['commandsField'])));
  $state['actions'][] = MatchReplayNormalizeAction($action);
  $state['updatedAt'] = date('c');
  return MatchReplaySetFieldValue($config['commandsField'], MatchReplayEncodeData($state));
}

function MatchReplayGetWinner() {
  if (class_exists('DecisionQueueController')) {
    $winner = DecisionQueueController::GetVariable('GAMEOVER_WINNER');
    if ($winner !== null && strval($winner) !== '') return intval($winner);
  }
  if (function_exists('GetDecisionQueueVariables')) {
    $vars = json_decode(GetDecisionQueueVariables(), true);
    if (is_array($vars) && isset($vars['GAMEOVER_WINNER']) && strval($vars['GAMEOVER_WINNER']) !== '') {
      return intval($vars['GAMEOVER_WINNER']);
    }
  }
  return 0;
}

function MatchReplayIsGameOver() {
  if (MatchReplayGetWinner() > 0) return true;
  if (function_exists('IsGameOver')) return !!IsGameOver();
  return false;
}

function MatchReplayCanDownload() {
  if (!MatchReplayIsEnabled()) return false;
  if (!MatchReplayIsGameOver()) return false;
  if (MatchReplayGetInitialGamestateText() === '') return false;
  return count(MatchReplayGetCommandState()['actions'] ?? []) > 0;
}

function MatchReplayBuildDownloadPayload($rootName, $gameName) {
  $initialText = MatchReplayGetInitialGamestateText();
  $commandState = MatchReplayGetCommandState();
  $payload = [
    'format' => 'tcgengine-match-replay-v1',
    'rootName' => strval($rootName),
    'gameName' => strval($gameName),
    'savedAt' => date('c'),
    'createdAt' => strval($commandState['createdAt'] ?? ''),
    'winner' => MatchReplayGetWinner(),
    'actionCount' => count($commandState['actions'] ?? []),
    'initialGamestate' => $initialText,
    'actions' => $commandState['actions'] ?? [],
  ];
  if (function_exists('RegressionCurrentGamestateHash')) {
    $payload['finalGamestateHash'] = RegressionCurrentGamestateHash($rootName, $gameName);
  }
  return $payload;
}

function MatchReplayValidateReplayPayload($replay) {
  if (!is_array($replay)) return 'Replay payload must be a JSON object.';
  if (strval($replay['format'] ?? '') !== 'tcgengine-match-replay-v1') return 'Unsupported replay format.';
  if (!preg_match('/^[A-Za-z0-9_]+$/', strval($replay['rootName'] ?? ''))) return 'Invalid replay root name.';
  if (!isset($replay['initialGamestate']) || !is_string($replay['initialGamestate']) || $replay['initialGamestate'] === '') {
    return 'Replay initial gamestate is missing.';
  }
  if (!isset($replay['actions']) || !is_array($replay['actions'])) return 'Replay actions are missing.';
  return '';
}

function MatchReplayPlaybackState() {
  if (!MatchReplayIsEnabled()) return null;
  $state = MatchReplayGetCommandState();
  if (empty($state['playback'])) return null;
  return [
    'actionCount' => count($state['actions'] ?? []),
    'nextActionIndex' => intval($state['nextActionIndex'] ?? 0),
    'completed' => intval($state['nextActionIndex'] ?? 0) >= count($state['actions'] ?? []),
    'sourceGameName' => strval($state['sourceGameName'] ?? ''),
    'sourceSavedAt' => strval($state['sourceSavedAt'] ?? ''),
  ];
}

function MatchReplayLoadInitialForPlayback($rootName, $gameName, $nextActionIndex = 0) {
  $initialText = MatchReplayGetInitialGamestateText();
  if ($initialText === '') return ['success' => false, 'message' => 'Replay initial gamestate is missing.'];

  $state = MatchReplayGetCommandState();
  $actions = $state['actions'] ?? [];
  $sourceGameName = strval($state['sourceGameName'] ?? '');
  $sourceSavedAt = strval($state['sourceSavedAt'] ?? '');

  if (function_exists('RegressionClearGamestateMemory')) {
    RegressionClearGamestateMemory($gameName);
  }

  $gameDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName;
  if (!is_dir($gameDir)) mkdir($gameDir, 0777, true);
  file_put_contents($gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt', $initialText);

  if (function_exists('ParseGamestate')) {
    ParseGamestate('./' . $rootName . '/');
  }
  MatchReplaySetInitialGamestateText($initialText);
  $newState = MatchReplayEmptyCommandState();
  $newState['actions'] = $actions;
  $newState['nextActionIndex'] = max(0, min(intval($nextActionIndex), count($actions)));
  $newState['playback'] = true;
  if ($sourceGameName !== '') $newState['sourceGameName'] = $sourceGameName;
  if ($sourceSavedAt !== '') $newState['sourceSavedAt'] = $sourceSavedAt;
  MatchReplaySetCommandState($newState);
  if (function_exists('SetFlashMessage')) {
    SetFlashMessage('Loaded replay initial state.');
  }
  if (function_exists('WriteGamestate')) {
    WriteGamestate('./' . $rootName . '/');
  }
  if (function_exists('GamestateUpdated')) {
    GamestateUpdated($gameName);
  }
  return ['success' => true, 'message' => 'Loaded replay initial state.'];
}

function MatchReplayReplayNextActionLoaded($rootName, $gameName) {
  $state = MatchReplayGetCommandState();
  if (empty($state['playback'])) return ['success' => false, 'message' => 'This game is not a replay playback session.'];

  $actions = $state['actions'] ?? [];
  $nextActionIndex = intval($state['nextActionIndex'] ?? 0);
  if ($nextActionIndex >= count($actions)) {
    return ['success' => true, 'message' => 'Replay is already complete.'];
  }

  $state['nextActionIndex'] = $nextActionIndex + 1;
  MatchReplaySetCommandState($state);

  $result = EngineExecuteLoadedAction($actions[$nextActionIndex], $rootName, $gameName, [
    'updateCache' => false,
    'disableRecording' => true,
  ]);
  if (empty($result['success'])) {
    $state['nextActionIndex'] = $nextActionIndex;
    MatchReplaySetCommandState($state);
    if (function_exists('WriteGamestate')) {
      WriteGamestate('./' . $rootName . '/');
    }
    return [
      'success' => false,
      'message' => 'Replay failed at action ' . ($nextActionIndex + 1) . ': ' . ($result['message'] ?: 'engine action failed'),
    ];
  }

  if (function_exists('SetFlashMessage')) {
    SetFlashMessage('Replayed action ' . ($nextActionIndex + 1) . ' of ' . count($actions) . '.');
  }
  if (function_exists('GamestateUpdated')) {
    GamestateUpdated($gameName);
  }
  return [
    'success' => true,
    'message' => 'Replayed action ' . ($nextActionIndex + 1) . ' of ' . count($actions) . '.',
  ];
}

function MatchReplayReplayAllLoaded($rootName, $gameName) {
  $state = MatchReplayGetCommandState();
  if (empty($state['playback'])) return ['success' => false, 'message' => 'This game is not a replay playback session.'];

  $actions = $state['actions'] ?? [];
  $start = intval($state['nextActionIndex'] ?? 0);
  for ($i = $start; $i < count($actions); ++$i) {
    $step = MatchReplayReplayNextActionLoaded($rootName, $gameName);
    if (empty($step['success'])) return $step;
  }
  return ['success' => true, 'message' => 'Replay complete (' . count($actions) . ' actions).'];
}

?>
