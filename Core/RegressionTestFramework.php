<?php

function RegressionRepoRoot() {
  return dirname(__DIR__);
}

function RegressionNormalizeNewlines($text) {
  $text = str_replace(["\r\n", "\r"], "\n", $text);
  $lines = explode("\n", $text);
  foreach ($lines as &$line) {
    $line = rtrim($line);
  }
  unset($line);
  return implode("\n", $lines);
}

function RegressionDiffNormalizedTexts($expectedText, $actualText, $contextLines = 2) {
  $expectedLines = explode("\n", RegressionNormalizeNewlines($expectedText));
  $actualLines = explode("\n", RegressionNormalizeNewlines($actualText));

  $maxLines = max(count($expectedLines), count($actualLines));
  for ($index = 0; $index < $maxLines; ++$index) {
    $expectedLine = $expectedLines[$index] ?? null;
    $actualLine = $actualLines[$index] ?? null;
    if ($expectedLine === $actualLine) continue;

    $start = max(0, $index - $contextLines);
    $end = min($maxLines - 1, $index + $contextLines);
    $context = [];
    for ($lineIndex = $start; $lineIndex <= $end; ++$lineIndex) {
      $marker = $lineIndex === $index ? '>' : ' ';
      $context[] = [
        'marker' => $marker,
        'lineNumber' => $lineIndex + 1,
        'expected' => $expectedLines[$lineIndex] ?? '<missing>',
        'actual' => $actualLines[$lineIndex] ?? '<missing>',
      ];
    }

    return [
      'lineNumber' => $index + 1,
      'expected' => $expectedLine ?? '<missing>',
      'actual' => $actualLine ?? '<missing>',
      'expectedLineCount' => count($expectedLines),
      'actualLineCount' => count($actualLines),
      'context' => $context,
    ];
  }

  return null;
}

function RegressionFormatSnapshotDiff($expectedText, $actualText, $contextLines = 2) {
  $diff = RegressionDiffNormalizedTexts($expectedText, $actualText, $contextLines);
  if ($diff === null) return 'Snapshots match.';

  $lines = [];
  $lines[] = "Final snapshot mismatch at line {$diff['lineNumber']} (expected {$diff['expectedLineCount']} lines, actual {$diff['actualLineCount']} lines).";
  $lines[] = "Expected: {$diff['expected']}";
  $lines[] = "Actual:   {$diff['actual']}";
  $lines[] = 'Context:';
  foreach ($diff['context'] as $row) {
    $lines[] = sprintf(
      "%s L%-4d expected: %s | actual: %s",
      $row['marker'],
      $row['lineNumber'],
      $row['expected'],
      $row['actual']
    );
  }

  return implode("\n", $lines);
}

function RegressionNormalizeAction($action) {
  return [
    'playerID' => intval($action['playerID'] ?? 0),
    'mode' => intval($action['mode'] ?? 0),
    'buttonInput' => strval($action['buttonInput'] ?? ''),
    'cardID' => strval($action['cardID'] ?? ''),
    'chkInput' => array_values(array_map('strval', $action['chkInput'] ?? [])),
    'inputText' => strval($action['inputText'] ?? ''),
  ];
}

function RegressionTestsRoot($rootName) {
  return RegressionRepoRoot() . DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'Integration' . DIRECTORY_SEPARATOR . $rootName;
}

function RegressionFixtureDir($rootName, $slug) {
  return RegressionTestsRoot($rootName) . DIRECTORY_SEPARATOR . $slug;
}

function RegressionRecordingDir($rootName, $gameName) {
  return RegressionRepoRoot() . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName . DIRECTORY_SEPARATOR . 'RegressionRecording';
}

function RegressionRecordingStatePath($rootName, $gameName) {
  return RegressionRecordingDir($rootName, $gameName) . DIRECTORY_SEPARATOR . 'recording.json';
}

function RegressionRecordingInitialStatePath($rootName, $gameName) {
  return RegressionRecordingDir($rootName, $gameName) . DIRECTORY_SEPARATOR . 'initial_gamestate.txt';
}

function RegressionReplayStatePath($rootName, $gameName) {
  return RegressionRepoRoot() . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName . DIRECTORY_SEPARATOR . 'RegressionReplayState.json';
}

function RegressionEnsureDir($path) {
  if (!is_dir($path)) {
    mkdir($path, 0777, true);
  }
}

function RegressionCurrentGamestatePath($rootName, $gameName) {
  return RegressionRepoRoot() . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName . DIRECTORY_SEPARATOR . 'Gamestate.txt';
}

function RegressionCurrentGamestateText($rootName, $gameName) {
  $path = RegressionCurrentGamestatePath($rootName, $gameName);
  if (!is_file($path)) return '';
  return file_get_contents($path);
}

function RegressionCurrentGamestateHash($rootName, $gameName) {
  return hash('sha256', RegressionNormalizeNewlines(RegressionCurrentGamestateText($rootName, $gameName)));
}

function RegressionIsRecordingActive($rootName, $gameName) {
  $recording = RegressionReadRecording($rootName, $gameName);
  return is_array($recording) && !empty($recording['active']);
}

function RegressionReadRecording($rootName, $gameName) {
  $path = RegressionRecordingStatePath($rootName, $gameName);
  if (!is_file($path)) return null;
  $json = file_get_contents($path);
  $data = json_decode($json, true);
  return is_array($data) ? $data : null;
}

function RegressionWriteRecording($rootName, $gameName, $recording) {
  RegressionEnsureDir(RegressionRecordingDir($rootName, $gameName));
  file_put_contents(
    RegressionRecordingStatePath($rootName, $gameName),
    json_encode($recording, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
  );
}

function RegressionReadReplayState($rootName, $gameName) {
  $path = RegressionReplayStatePath($rootName, $gameName);
  if (!is_file($path)) return null;
  $json = file_get_contents($path);
  $data = json_decode($json, true);
  return is_array($data) ? $data : null;
}

function RegressionWriteReplayState($rootName, $gameName, $state) {
  $gameDir = dirname(RegressionReplayStatePath($rootName, $gameName));
  RegressionEnsureDir($gameDir);
  file_put_contents(
    RegressionReplayStatePath($rootName, $gameName),
    json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
  );
}

function RegressionClearReplayState($rootName, $gameName) {
  $path = RegressionReplayStatePath($rootName, $gameName);
  if (is_file($path)) {
    @unlink($path);
  }
}

function RegressionSanitizeSlug($slug) {
  $slug = strtolower(trim($slug));
  $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
  $slug = preg_replace('/-+/', '-', $slug);
  return trim($slug, '-');
}

function RegressionStartRecording($rootName, $gameName, $viewerPlayerID, $createdBy = 'anonymous') {
  if (RegressionIsRecordingActive($rootName, $gameName)) {
    return ['success' => false, 'message' => 'A regression recording is already active for this game.'];
  }

  RegressionClearReplayState($rootName, $gameName);

  $initialGamestate = RegressionCurrentGamestateText($rootName, $gameName);
  if ($initialGamestate === '') {
    return ['success' => false, 'message' => 'Unable to capture the current gamestate.'];
  }

  RegressionEnsureDir(RegressionRecordingDir($rootName, $gameName));
  file_put_contents(RegressionRecordingInitialStatePath($rootName, $gameName), $initialGamestate);

  $recording = [
    'active' => true,
    'rootName' => $rootName,
    'gameName' => strval($gameName),
    'viewerPlayerID' => intval($viewerPlayerID),
    'createdBy' => strval($createdBy),
    'createdAt' => date('c'),
    'actions' => [],
    'assertions' => [],
  ];
  RegressionWriteRecording($rootName, $gameName, $recording);

  return ['success' => true, 'message' => 'Regression recording started.'];
}

function RegressionStopRecording($rootName, $gameName) {
  $recording = RegressionReadRecording($rootName, $gameName);
  if ($recording === null || empty($recording['active'])) {
    return ['success' => false, 'message' => 'No active regression recording was found for this game.'];
  }

  $recording['active'] = false;
  $recording['stoppedAt'] = date('c');
  RegressionWriteRecording($rootName, $gameName, $recording);

  return ['success' => true, 'message' => 'Regression recording stopped.'];
}

function RegressionRecordAction($rootName, $gameName, $action) {
  $recording = RegressionReadRecording($rootName, $gameName);
  if ($recording === null || empty($recording['active'])) return;

  $recording['actions'][] = RegressionNormalizeAction($action);
  RegressionWriteRecording($rootName, $gameName, $recording);
}

function RegressionBuildAssertionFromInput($viewerPlayerID, $payload) {
  $type = strval($payload['type'] ?? '');
  $assertion = [
    'step' => intval($payload['step'] ?? 0),
    'type' => $type,
    'viewerPlayerID' => intval($viewerPlayerID),
  ];

  switch ($type) {
    case 'phase_is':
    case 'turn_player_is':
    case 'flash_message_contains':
      $assertion['value'] = strval($payload['value'] ?? '');
      break;
    case 'zone_count':
      $assertion['zone'] = strval($payload['zone'] ?? '');
      $assertion['value'] = intval($payload['value'] ?? 0);
      break;
    case 'card_exists':
      $assertion['zone'] = strval($payload['zone'] ?? '');
      $assertion['cardID'] = strval($payload['cardID'] ?? '');
      break;
    case 'card_property_equals':
      $assertion['mzId'] = strval($payload['mzId'] ?? '');
      $assertion['property'] = strval($payload['property'] ?? '');
      $assertion['value'] = strval($payload['value'] ?? '');
      break;
    case 'decision_queue_empty':
      $assertion['player'] = strval($payload['player'] ?? 'all');
      break;
    default:
      return [null, 'Unsupported assertion type.'];
  }

  return [$assertion, null];
}

function RegressionAddAssertion($rootName, $gameName, $viewerPlayerID, $payloadJson) {
  $recording = RegressionReadRecording($rootName, $gameName);
  if ($recording === null) {
    return ['success' => false, 'message' => 'No regression recording exists for this game.'];
  }

  $payload = json_decode($payloadJson, true);
  if (!is_array($payload)) {
    return ['success' => false, 'message' => 'Assertion payload must be valid JSON.'];
  }

  $payload['step'] = count($recording['actions']);
  [$assertion, $error] = RegressionBuildAssertionFromInput($viewerPlayerID, $payload);
  if ($error !== null) {
    return ['success' => false, 'message' => $error];
  }

  $recording['assertions'][] = $assertion;
  RegressionWriteRecording($rootName, $gameName, $recording);
  return ['success' => true, 'message' => 'Assertion added at step ' . $payload['step'] . '.'];
}

function RegressionSaveFixture($rootName, $gameName, $slug, $name = '', $notes = '') {
  $recording = RegressionReadRecording($rootName, $gameName);
  if ($recording === null) {
    return ['success' => false, 'message' => 'No regression recording exists for this game.'];
  }
  if (!empty($recording['active'])) {
    return ['success' => false, 'message' => 'Stop the recording before saving the fixture.'];
  }

  $slug = RegressionSanitizeSlug($slug);
  if ($slug === '') {
    return ['success' => false, 'message' => 'Fixture slug cannot be empty.'];
  }

  $fixtureDir = RegressionFixtureDir($rootName, $slug);
  RegressionEnsureDir($fixtureDir);

  $fixtureName = trim($name) !== '' ? trim($name) : $slug;
  $meta = [
    'name' => $fixtureName,
    'rootName' => $rootName,
    'createdAt' => date('c'),
    'createdBy' => $recording['createdBy'] ?? 'anonymous',
  ];
  if (trim($notes) !== '') {
    $meta['notes'] = trim($notes);
  }

  file_put_contents(
    $fixtureDir . DIRECTORY_SEPARATOR . 'meta.json',
    json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
  );
  copy(RegressionRecordingInitialStatePath($rootName, $gameName), $fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt');
  file_put_contents(
    $fixtureDir . DIRECTORY_SEPARATOR . 'actions.json',
    json_encode($recording['actions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
  );
  file_put_contents(
    $fixtureDir . DIRECTORY_SEPARATOR . 'assertions.json',
    json_encode($recording['assertions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
  );
  file_put_contents(
    $fixtureDir . DIRECTORY_SEPARATOR . 'expected_final_gamestate.txt',
    RegressionCurrentGamestateText($rootName, $gameName)
  );

  return ['success' => true, 'message' => 'Saved regression fixture to ' . $fixtureDir . '.'];
}

function RegressionLoadAssertionsForFixture($fixtureDir) {
  $path = $fixtureDir . DIRECTORY_SEPARATOR . 'assertions.json';
  if (!is_file($path)) return [];
  $data = json_decode(file_get_contents($path), true);
  return is_array($data) ? $data : [];
}

function RegressionLoadActionsForFixture($fixtureDir) {
  $path = $fixtureDir . DIRECTORY_SEPARATOR . 'actions.json';
  if (!is_file($path)) return [];
  $data = json_decode(file_get_contents($path), true);
  return is_array($data) ? $data : [];
}

function RegressionListFixtures($rootName) {
  $root = RegressionTestsRoot($rootName);
  if (!is_dir($root)) return [];
  $fixtures = [];
  foreach (scandir($root) as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    if (is_dir($root . DIRECTORY_SEPARATOR . $entry)) {
      $fixtures[] = $entry;
    }
  }
  sort($fixtures);
  return $fixtures;
}

function RegressionListFixtureOptions($rootName) {
  $options = [];
  foreach (RegressionListFixtures($rootName) as $slug) {
    $meta = [];
    $metaPath = RegressionFixtureDir($rootName, $slug) . DIRECTORY_SEPARATOR . 'meta.json';
    if (is_file($metaPath)) {
      $decoded = json_decode(file_get_contents($metaPath), true);
      if (is_array($decoded)) $meta = $decoded;
    }
    $options[] = [
      'slug' => $slug,
      'name' => trim(strval($meta['name'] ?? '')) !== '' ? strval($meta['name']) : $slug,
    ];
  }
  return $options;
}

function RegressionFixtureExpectedFinalPath($rootName, $slug) {
  return RegressionFixtureDir($rootName, $slug) . DIRECTORY_SEPARATOR . 'expected_final_gamestate.txt';
}

function RegressionReplayStateMatchesExpectedFinal($rootName, $gameName, $slug) {
  $expectedPath = RegressionFixtureExpectedFinalPath($rootName, $slug);
  if (!is_file($expectedPath)) return null;
  $expected = file_get_contents($expectedPath);
  $actual = RegressionCurrentGamestateText($rootName, $gameName);
  return RegressionNormalizeNewlines($expected) === RegressionNormalizeNewlines($actual);
}

function RegressionBuildReplayState($rootName, $gameName, $slug, $actions, $nextActionIndex, $lastOperation, $lastMessage = '') {
  $actionCount = count($actions);
  $matchesExpectedFinal = RegressionReplayStateMatchesExpectedFinal($rootName, $gameName, $slug);
  return [
    'slug' => $slug,
    'actionCount' => $actionCount,
    'nextActionIndex' => max(0, min(intval($nextActionIndex), $actionCount)),
    'completed' => intval($nextActionIndex) >= $actionCount,
    'lastOperation' => strval($lastOperation),
    'lastMessage' => strval($lastMessage),
    'updatedAt' => date('c'),
    'currentGamestateHash' => RegressionCurrentGamestateHash($rootName, $gameName),
    'matchesExpectedFinal' => $matchesExpectedFinal,
  ];
}

function RegressionPersistReplayState($rootName, $gameName, $slug, $actions, $nextActionIndex, $lastOperation, $lastMessage = '') {
  $state = RegressionBuildReplayState($rootName, $gameName, $slug, $actions, $nextActionIndex, $lastOperation, $lastMessage);
  RegressionWriteReplayState($rootName, $gameName, $state);
  return $state;
}

function RegressionLoadFixtureIntoCurrentGame($rootName, $gameName, $slug) {
  $slug = RegressionSanitizeSlug($slug);
  if ($slug === '') {
    return ['success' => false, 'message' => 'Fixture slug cannot be empty.'];
  }

  $fixtureDir = RegressionFixtureDir($rootName, $slug);
  if (!is_dir($fixtureDir)) {
    return ['success' => false, 'message' => 'Fixture directory was not found.'];
  }

  $initialPath = $fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt';
  if (!is_file($initialPath)) {
    return ['success' => false, 'message' => 'Fixture initial_gamestate.txt is missing.'];
  }

  $gameStatePath = RegressionCurrentGamestatePath($rootName, $gameName);
  if ($gameStatePath === '') {
    return ['success' => false, 'message' => 'Unable to resolve the current game state path.'];
  }

  copy($initialPath, $gameStatePath);

  if (function_exists('ParseGamestate')) {
    ParseGamestate('./' . $rootName . '/');
  }

  return [
    'success' => true,
    'slug' => $slug,
    'fixtureDir' => $fixtureDir,
    'actions' => RegressionLoadActionsForFixture($fixtureDir),
  ];
}

function RegressionReplayFixture($rootName, $gameName, $slug, $replayActions = false) {
  $loadResult = RegressionLoadFixtureIntoCurrentGame($rootName, $gameName, $slug);
  if (empty($loadResult['success'])) {
    return $loadResult;
  }

  $slug = $loadResult['slug'];
  $actions = $loadResult['actions'];
  $lastMessage = ($replayActions ? 'Replayed' : 'Loaded initial state for') . ' regression fixture ' . $slug . '.';
  $replayState = RegressionPersistReplayState(
    $rootName,
    $gameName,
    $slug,
    $actions,
    0,
    'load_initial',
    $lastMessage
  );

  if ($replayActions) {
    foreach ($actions as $stepIndex => $action) {
      $result = EngineRunAction($action, $rootName, $gameName, [
        'updateCache' => false,
        'disableRecording' => true,
      ]);
      if (empty($result['success'])) {
        RegressionPersistReplayState(
          $rootName,
          $gameName,
          $slug,
          $actions,
          $stepIndex,
          'replay_failed',
          'Fixture replay failed at step ' . ($stepIndex + 1) . ': ' . ($result['message'] ?: 'engine action failed')
        );
        return [
          'success' => false,
          'message' => 'Fixture replay failed at step ' . ($stepIndex + 1) . ': ' . ($result['message'] ?: 'engine action failed'),
        ];
      }
    }

    $lastMessage = 'Replayed regression fixture ' . $slug . ' (' . count($actions) . ' actions).';
    $replayState = RegressionPersistReplayState(
      $rootName,
      $gameName,
      $slug,
      $actions,
      count($actions),
      'replay_all',
      $lastMessage
    );
  }

  if (function_exists('GamestateUpdated')) {
    GamestateUpdated($gameName);
  }
  if (function_exists('SetFlashMessage')) {
    SetFlashMessage($lastMessage);
  }

  return [
    'success' => true,
    'message' => $lastMessage . ' Loaded into game ' . $gameName . '.',
    'actionCount' => count($actions),
    'replayActions' => $replayActions,
    'replayState' => $replayState,
  ];
}

function RegressionReplayFixtureNextAction($rootName, $gameName, $slug = '') {
  $slug = RegressionSanitizeSlug($slug);
  $state = RegressionReadReplayState($rootName, $gameName);

  if ($slug === '' && is_array($state)) {
    $slug = RegressionSanitizeSlug(strval($state['slug'] ?? ''));
  }
  if ($slug === '') {
    return ['success' => false, 'message' => 'Fixture slug cannot be empty.'];
  }

  $shouldReloadInitialState = !is_array($state) || strval($state['slug'] ?? '') !== $slug;
  if ($shouldReloadInitialState) {
    $loadResult = RegressionLoadFixtureIntoCurrentGame($rootName, $gameName, $slug);
    if (empty($loadResult['success'])) {
      return $loadResult;
    }
    $actions = $loadResult['actions'];
    $state = RegressionPersistReplayState(
      $rootName,
      $gameName,
      $slug,
      $actions,
      0,
      'load_initial',
      'Loaded initial state for regression fixture ' . $slug . '.'
    );
  } else {
    $actions = RegressionLoadActionsForFixture(RegressionFixtureDir($rootName, $slug));
  }

  $nextActionIndex = intval($state['nextActionIndex'] ?? 0);
  if ($nextActionIndex >= count($actions)) {
    $message = 'Regression fixture ' . $slug . ' is already at the final action (' . count($actions) . '/' . count($actions) . ').';
    $state = RegressionPersistReplayState($rootName, $gameName, $slug, $actions, count($actions), 'step_complete', $message);
    if (function_exists('SetFlashMessage')) {
      SetFlashMessage($message);
    }
    if (function_exists('GamestateUpdated')) {
      GamestateUpdated($gameName);
    }
    return [
      'success' => true,
      'message' => $message,
      'actionCount' => count($actions),
      'nextActionIndex' => count($actions),
      'replayState' => $state,
    ];
  }

  $result = EngineRunAction($actions[$nextActionIndex], $rootName, $gameName, [
    'updateCache' => false,
    'disableRecording' => true,
  ]);
  if (empty($result['success'])) {
    $message = 'Fixture replay failed at step ' . ($nextActionIndex + 1) . ': ' . ($result['message'] ?: 'engine action failed');
    RegressionPersistReplayState($rootName, $gameName, $slug, $actions, $nextActionIndex, 'step_failed', $message);
    return ['success' => false, 'message' => $message];
  }

  $newNextActionIndex = $nextActionIndex + 1;
  $message = 'Replayed action ' . $newNextActionIndex . ' of ' . count($actions) . ' for regression fixture ' . $slug . '.';
  $state = RegressionPersistReplayState($rootName, $gameName, $slug, $actions, $newNextActionIndex, 'step', $message);

  if (function_exists('GamestateUpdated')) {
    GamestateUpdated($gameName);
  }
  if (function_exists('SetFlashMessage')) {
    SetFlashMessage($message);
  }

  return [
    'success' => true,
    'message' => $message,
    'actionCount' => count($actions),
    'nextActionIndex' => $newNextActionIndex,
    'replayState' => $state,
  ];
}

function RegressionAssertionMatchesStep($assertion, $step) {
  return intval($assertion['step'] ?? -1) === intval($step);
}

function RegressionZoneCountForAssertion($zoneName, $viewerPlayerID) {
  global $playerID;
  $playerID = intval($viewerPlayerID);
  $zone = GetZone($zoneName);
  if (!is_array($zone)) return intval($zone);
  $count = 0;
  foreach ($zone as $obj) {
    if (is_object($obj) && method_exists($obj, 'Removed') && $obj->Removed()) continue;
    $count++;
  }
  return $count;
}

function RegressionEvaluateAssertion($assertion) {
  global $playerID;
  $playerID = intval($assertion['viewerPlayerID'] ?? 1);
  $type = $assertion['type'] ?? '';

  switch ($type) {
    case 'phase_is':
      $actual = strval(GetCurrentPhase());
      $expected = strval($assertion['value'] ?? '');
      return [$actual === $expected, "Expected phase '{$expected}', got '{$actual}'."];
    case 'turn_player_is':
      $actual = intval(GetTurnPlayer());
      $expected = intval($assertion['value'] ?? 0);
      return [$actual === $expected, "Expected turn player {$expected}, got {$actual}."];
    case 'zone_count':
      $zone = strval($assertion['zone'] ?? '');
      $actual = RegressionZoneCountForAssertion($zone, $playerID);
      $expected = intval($assertion['value'] ?? 0);
      return [$actual === $expected, "Expected {$zone} count {$expected}, got {$actual}."];
    case 'card_exists':
      $zoneName = strval($assertion['zone'] ?? '');
      $cardId = strval($assertion['cardID'] ?? '');
      $zone = GetZone($zoneName);
      $found = false;
      if (is_array($zone)) {
        foreach ($zone as $obj) {
          if (!is_object($obj)) continue;
          if (method_exists($obj, 'Removed') && $obj->Removed()) continue;
          if (($obj->CardID ?? null) === $cardId) {
            $found = true;
            break;
          }
        }
      }
      return [$found, "Expected card {$cardId} to exist in {$zoneName}."];
    case 'card_property_equals':
      $mzId = strval($assertion['mzId'] ?? '');
      $property = strval($assertion['property'] ?? '');
      $expected = strval($assertion['value'] ?? '');
      $obj = GetZoneObject($mzId);
      $actual = '';
      if (is_object($obj) && property_exists($obj, $property)) {
        $value = $obj->$property;
        $actual = is_scalar($value) ? strval($value) : json_encode($value);
      }
      return [$actual === $expected, "Expected {$mzId}.{$property} to equal '{$expected}', got '{$actual}'."];
    case 'decision_queue_empty':
      $target = strtolower(strval($assertion['player'] ?? 'all'));
      $players = $target === 'all' ? [1, 2] : [intval($target)];
      foreach ($players as $decisionPlayer) {
        $zone = GetDecisionQueue($decisionPlayer);
        if (is_array($zone)) {
          foreach ($zone as $obj) {
            if (is_object($obj) && method_exists($obj, 'Removed') && $obj->Removed()) continue;
            return [false, "Expected decision queue for player {$decisionPlayer} to be empty."];
          }
        }
      }
      return [true, ''];
    case 'flash_message_contains':
      $actual = strval(GetFlashMessage());
      $expected = strval($assertion['value'] ?? '');
      return [str_contains($actual, $expected), "Expected flash message to contain '{$expected}', got '{$actual}'."];
    default:
      return [false, "Unsupported assertion type '{$type}'."];
  }
}

function RegressionEvaluateAssertionsForStep($assertions, $step) {
  foreach ($assertions as $assertion) {
    if (!RegressionAssertionMatchesStep($assertion, $step)) continue;
    [$passed, $message] = RegressionEvaluateAssertion($assertion);
    if (!$passed) {
      return [false, $message];
    }
  }
  return [true, ''];
}

function RegressionDeleteDirRecursive($path) {
  if (!is_dir($path)) return;
  foreach (scandir($path) as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $child = $path . DIRECTORY_SEPARATOR . $entry;
    if (is_dir($child)) RegressionDeleteDirRecursive($child);
    else @unlink($child);
  }
  @rmdir($path);
}
