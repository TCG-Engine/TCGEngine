<?php

require_once __DIR__ . '/../Core/EngineActionRunner.php';

function BridgeOut($payload, $exitCode = 0) {
  echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  exit($exitCode);
}

function BridgeFail($message, $details = null, $exitCode = 1) {
  $payload = ['success' => false, 'message' => $message];
  if ($details !== null) $payload['details'] = $details;
  BridgeOut($payload, $exitCode);
}

function BridgeParseArgs($argv) {
  $args = [];
  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
      $parts = explode('=', substr($arg, 2), 2);
      $args[$parts[0]] = $parts[1] ?? '1';
    }
  }
  return $args;
}

function BridgeDraftGameDir($root, $gameName) {
  return RegressionRepoRoot() . DIRECTORY_SEPARATOR . $root . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName;
}

function BridgeEnsureDraftGame($root, $gameName) {
  $gameDir = BridgeDraftGameDir($root, $gameName);
  if (!is_dir($gameDir)) BridgeFail('Draft game not found.', ['gameName' => $gameName]);
  $gameStatePath = $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt';
  if (!is_file($gameStatePath)) BridgeFail('Draft game Gamestate.txt is missing.', ['gameName' => $gameName]);
  return $gameDir;
}

function BridgeLoadRuntimeGame($root, $gameName) {
  BridgeEnsureDraftGame($root, $gameName);
  EngineLoadRootRuntime($root);
  $GLOBALS['gameName'] = strval($gameName);
  ParseGamestate('./' . $root . '/');
}

function BridgeParseTemplateSpec($specBase64) {
  $json = base64_decode($specBase64, true);
  if ($json === false) BridgeFail('Scenario spec is not valid base64.');
  $spec = json_decode($json, true);
  if (!is_array($spec)) BridgeFail('Scenario spec is not valid JSON.');
  return $spec;
}

function BridgeApplyScenarioMutations($spec) {
  $mutations = $spec['mutations'] ?? [];
  foreach ($mutations as $mutation) {
    $zoneName = strval($mutation['zone'] ?? '');
    $operation = strval($mutation['operation'] ?? 'set');
    $index = intval($mutation['index'] ?? -1);
    $property = strval($mutation['property'] ?? '');
    $value = $mutation['value'] ?? null;
    $perspectivePlayer = intval($mutation['perspectivePlayer'] ?? 0);
    if ($zoneName === '') {
      BridgeFail('Scenario mutation is missing required fields.', $mutation);
    }

    $originalPlayerID = $GLOBALS['playerID'] ?? null;
    if ($perspectivePlayer > 0) {
      $GLOBALS['playerID'] = $perspectivePlayer;
    }

    if ($operation === 'addCard') {
      $newObj = MZAddZone($perspectivePlayer > 0 ? $perspectivePlayer : intval($GLOBALS['playerID'] ?? 1), $zoneName, strval($value));
      if ($newObj === null) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('Scenario addCard mutation could not create zone object.', $mutation);
      }

      if (preg_match('/Field$/', $zoneName)) {
        $controller = ($zoneName === 'theirField' && $perspectivePlayer > 0) ? ($perspectivePlayer == 1 ? 2 : 1) : ($perspectivePlayer > 0 ? $perspectivePlayer : intval($GLOBALS['playerID'] ?? 1));
        if (!isset($newObj->Status) || $newObj->Status === '-') $newObj->Status = 2;
        if (!isset($newObj->Owner) || $newObj->Owner === '-') $newObj->Owner = $controller;
        if (!isset($newObj->Controller) || $newObj->Controller === '-') $newObj->Controller = $controller;
        if (!isset($newObj->Damage) || $newObj->Damage === '-') $newObj->Damage = 0;
        if (!is_array($newObj->TurnEffects)) $newObj->TurnEffects = [];
        if (!is_array($newObj->Counters)) $newObj->Counters = [];
        if (!is_array($newObj->Subcards)) $newObj->Subcards = [];
      }
    } else {
      if ($index < 0 || $property === '') {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('Scenario set mutation is missing required fields.', $mutation);
      }
      $zone = &GetZone($zoneName);
      if (!is_array($zone) || !isset($zone[$index])) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('Scenario mutation points to an invalid zone entry.', $mutation);
      }
      if (is_object($zone[$index])) {
        $zone[$index]->$property = $value;
      } else {
        $zone[$index] = $value;
      }
    }

    if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
  }
}

function BridgeDecisionTooltip($decision) {
  $tooltip = strval($decision->Tooltip ?? '');
  if ($tooltip === '' || $tooltip === '-') return '';
  return str_replace('_', ' ', $tooltip);
}

function BridgeCompileScenario($root, $spec) {
  $baseFixture = strval($spec['baseFixtureSlug'] ?? '');
  if ($baseFixture === '') BridgeFail('Scenario spec must include baseFixtureSlug.');

  $fixtureDir = RegressionFixtureDir($root, $baseFixture);
  $initialPath = $fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt';
  if (!is_file($initialPath)) BridgeFail('Base fixture initial_gamestate.txt not found.', ['fixture' => $baseFixture]);

  $tempGameName = 'scenario_compile_' . uniqid();
  $tempGameDir = BridgeDraftGameDir($root, $tempGameName);
  RegressionEnsureDir($tempGameDir);
  copy($initialPath, $tempGameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt');

  try {
    BridgeLoadRuntimeGame($root, $tempGameName);
    BridgeApplyScenarioMutations($spec);
    WriteGamestate('./' . $root . '/');
    $gamestate = RegressionCurrentGamestateText($root, $tempGameName);
    RegressionDeleteDirRecursive($tempGameDir);
    return $gamestate;
  } catch (Throwable $throwable) {
    RegressionDeleteDirRecursive($tempGameDir);
    BridgeFail('Scenario compilation failed.', $throwable->getMessage());
  }
}

function BridgeDecisionChoiceMatchesFilters($zoneObject, $filters) {
  if (!is_object($zoneObject)) return false;
  foreach ($filters as $filter) {
    $field = strval($filter['field'] ?? '');
    $op = strval($filter['op'] ?? '=');
    $expected = $filter['value'] ?? '';
    if ($field === '') continue;

    $actual = null;
    if (property_exists($zoneObject, $field)) {
      $actual = $zoneObject->$field;
    } else if ($field === 'CardType' && property_exists($zoneObject, 'CardID') && function_exists('CardType')) {
      $actual = CardType($zoneObject->CardID);
    } else if ($field === 'CardSubtypes' && property_exists($zoneObject, 'CardID') && function_exists('CardSubtypes')) {
      $actual = CardSubtypes($zoneObject->CardID);
    } else if ($field === 'CardElement' && property_exists($zoneObject, 'CardID') && function_exists('CardElement')) {
      $actual = CardElement($zoneObject->CardID);
    } else if ($field === 'CardClasses' && property_exists($zoneObject, 'CardID') && function_exists('CardClasses')) {
      $actual = CardClasses($zoneObject->CardID);
    }

    $actualString = is_array($actual) ? implode(',', $actual) : strval($actual ?? '');
    $expectedString = strval($expected);

    switch ($op) {
      case '!=':
        if ($actualString === $expectedString) return false;
        break;
      case '=':
      case '==':
      default:
        if ($actualString !== $expectedString) return false;
        break;
    }
  }

  return true;
}

function BridgeExpandDecisionSpecChoices($rawSpec) {
  $rawSpec = trim(strval($rawSpec));
  if ($rawSpec === '') return [];

  $parts = explode(':', $rawSpec);
  $zoneOrCard = trim(array_shift($parts));
  $filters = [];
  if (!empty($parts)) {
    $filterString = implode(':', $parts);
    $clauses = array_values(array_filter(array_map('trim', explode(',', $filterString)), fn($value) => $value !== ''));
    foreach ($clauses as $clause) {
      if (preg_match('/^(\w+)(==|!=|<=|>=|=|<|>)(.*)$/', $clause, $matches)) {
        $filters[] = ['field' => $matches[1], 'op' => $matches[2], 'value' => $matches[3]];
      } else {
        $filters[] = ['field' => $clause, 'op' => '=', 'value' => 'true'];
      }
    }
  }

  if (preg_match('/^(.+)-(\d+)$/', $zoneOrCard, $matches)) {
    $zoneName = $matches[1];
    $index = intval($matches[2]);
    $zone = GetZone($zoneName);
    if (!is_array($zone) || !isset($zone[$index])) return [];
    $zoneObject = $zone[$index];
    if (!is_object($zoneObject) || (!empty($zoneObject->removed))) return [];
    if (!BridgeDecisionChoiceMatchesFilters($zoneObject, $filters)) return [];
    return [$zoneOrCard];
  }

  $zone = GetZone($zoneOrCard);
  if (!is_array($zone)) return [$zoneOrCard];

  $expanded = [];
  for ($index = 0; $index < count($zone); ++$index) {
    $zoneObject = $zone[$index];
    if (!is_object($zoneObject) || (!empty($zoneObject->removed))) continue;
    if (!BridgeDecisionChoiceMatchesFilters($zoneObject, $filters)) continue;
    $expanded[] = $zoneOrCard . '-' . $index;
  }
  return $expanded;
}

function BridgeActionCardMetadata($mzID) {
  if (!preg_match('/^(.+)-(\d+)$/', strval($mzID), $matches)) return [];
  $zoneName = $matches[1];
  $index = intval($matches[2]);
  $zone = GetZone($zoneName);
  if (!is_array($zone) || !isset($zone[$index]) || !is_object($zone[$index])) return [];
  $zoneObject = $zone[$index];
  if (!property_exists($zoneObject, 'CardID')) return [];
  return ['resolvedCardID' => strval($zoneObject->CardID)];
}

function BridgeWithPlayerPerspective($player, $callback) {
  $originalPlayerID = $GLOBALS['playerID'] ?? null;
  $GLOBALS['playerID'] = intval($player);
  try {
    return $callback();
  } finally {
    if ($originalPlayerID !== null) {
      $GLOBALS['playerID'] = $originalPlayerID;
    }
  }
}

function BridgeEnumerateDecisionActions($decision, $player) {
  return BridgeWithPlayerPerspective($player, function() use ($decision, $player) {
    $actions = [];
    if ($decision === null || !is_object($decision)) return $actions;

    switch ($decision->Type) {
      case 'YESNO':
        $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''];
        $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''];
        break;
      case 'MZCHOOSE':
      case 'MZMAYCHOOSE':
        $rawChoices = array_values(array_filter(explode('&', strval($decision->Param ?? '')), fn($value) => $value !== ''));
        $choices = [];
        foreach ($rawChoices as $rawChoice) {
          foreach (BridgeExpandDecisionSpecChoices($rawChoice) as $expandedChoice) {
            $choices[] = $expandedChoice;
          }
        }
        $choices = array_values(array_unique($choices));
        foreach ($choices as $choice) {
          $actions[] = array_merge(
            ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $choice, 'chkInput' => [], 'inputText' => ''],
            BridgeActionCardMetadata($choice)
          );
        }
        if ($decision->Type === 'MZMAYCHOOSE') {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''];
        }
        break;
      default:
        break;
    }

    return $actions;
  });
}

function BridgeEnumerateHandPlayActions($player) {
  $actions = [];
  $GLOBALS['playerID'] = $player;
  $hand = GetZone('myHand');
  if (!is_array($hand)) return $actions;

  for ($index = 0; $index < count($hand); ++$index) {
    $mzId = 'myHand-' . $index;
    if (function_exists('CanActivateCard') && !CanActivateCard($player, $mzId, false)) continue;
    $actions[] = array_merge(
      ['playerID' => $player, 'mode' => 10002, 'buttonInput' => '', 'cardID' => $mzId . '!FSM!', 'chkInput' => [], 'inputText' => ''],
      BridgeActionCardMetadata($mzId)
    );
  }

  return $actions;
}

function BridgeEnumerateLegalActions($root, $gameName) {
  BridgeLoadRuntimeGame($root, $gameName);
  $dqController = new DecisionQueueController();
  for ($player = 1; $player <= 2; ++$player) {
    $decision = $dqController->NextDecision($player);
    if ($decision !== null) {
      return [
        'success' => true,
        'kind' => 'decision',
        'playerID' => $player,
        'decisionType' => $decision->Type,
        'decisionTooltip' => BridgeDecisionTooltip($decision),
        'decisionTooltipRaw' => strval($decision->Tooltip ?? ''),
        'actions' => BridgeEnumerateDecisionActions($decision, $player),
      ];
    }
  }

  $turnPlayer = intval(GetTurnPlayer());
  $phase = strval(GetCurrentPhase());
  if ($phase !== 'MAIN') {
    return ['success' => true, 'kind' => 'phase-unsupported', 'phase' => $phase, 'actions' => []];
  }

  return [
    'success' => true,
    'kind' => 'main-phase-hand-play',
    'playerID' => $turnPlayer,
    'phase' => $phase,
    'actions' => BridgeEnumerateHandPlayActions($turnPlayer),
  ];
}

function BridgeApplyEngineAction($root, $gameName, $actionBase64) {
  $json = base64_decode($actionBase64, true);
  if ($json === false) BridgeFail('Action payload is not valid base64.');
  $action = json_decode($json, true);
  if (!is_array($action)) BridgeFail('Action payload is not valid JSON.');
  $result = EngineRunAction($action, $root, $gameName, ['updateCache' => false, 'disableRecording' => true]);
  $result['gamestateHash'] = RegressionCurrentGamestateHash($root, $gameName);
  return $result;
}

function BridgeSnapshot($root, $gameName, $view) {
  BridgeLoadRuntimeGame($root, $gameName);
  $payload = [
    'success' => true,
    'view' => $view,
    'phase' => strval(GetCurrentPhase()),
    'turnPlayer' => intval(GetTurnPlayer()),
    'flashMessage' => function_exists('GetFlashMessage') ? strval(GetFlashMessage()) : '',
    'gamestateHash' => RegressionCurrentGamestateHash($root, $gameName),
  ];

  if ($view === 'summary') {
    $payload['zones'] = [
      'myHandCount' => is_array(GetZone('myHand')) ? count(GetZone('myHand')) : 0,
      'theirHandCount' => is_array(GetZone('theirHand')) ? count(GetZone('theirHand')) : 0,
      'myFieldCount' => is_array(GetZone('myField')) ? count(GetZone('myField')) : 0,
      'theirFieldCount' => is_array(GetZone('theirField')) ? count(GetZone('theirField')) : 0,
    ];
  } else {
    $payload['gamestateText'] = RegressionCurrentGamestateText($root, $gameName);
  }

  return $payload;
}

$args = BridgeParseArgs($argv);
$command = $args['command'] ?? '';
$root = $args['root'] ?? '';

if ($command === '') BridgeFail('Missing --command argument.');
if ($root === '') BridgeFail('Missing --root argument.');

switch ($command) {
  case 'compile-scenario':
    $spec = BridgeParseTemplateSpec($args['spec'] ?? '');
    BridgeOut(['success' => true, 'gamestateText' => BridgeCompileScenario($root, $spec)]);
    break;
  case 'enumerate-legal-actions':
    BridgeOut(BridgeEnumerateLegalActions($root, $args['gameName'] ?? ''));
    break;
  case 'apply-engine-action':
    BridgeOut(BridgeApplyEngineAction($root, $args['gameName'] ?? '', $args['action'] ?? ''));
    break;
  case 'get-game-snapshot':
    BridgeOut(BridgeSnapshot($root, $args['gameName'] ?? '', $args['view'] ?? 'summary'));
    break;
  default:
    BridgeFail('Unsupported command.', ['command' => $command]);
}