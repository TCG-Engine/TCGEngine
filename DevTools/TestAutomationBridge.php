<?php

require_once __DIR__ . '/../Core/EngineActionRunner.php';

class BridgeDaemonResponse extends Exception {
  public $payload;
  public $bridgeExitCode;

  public function __construct($payload, $bridgeExitCode = 0) {
    parent::__construct('Bridge daemon response');
    $this->payload = $payload;
    $this->bridgeExitCode = $bridgeExitCode;
  }
}

$GLOBALS['bridgeDaemonMode'] = false;

function BridgeOut($payload, $exitCode = 0) {
  if (!empty($GLOBALS['bridgeDaemonMode'])) {
    throw new BridgeDaemonResponse($payload, $exitCode);
  }
  // Keep bridge responses compact for RL throughput.
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
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
  $hasMemoryGamestate = false;
  if (function_exists('GamestateUsesMemoryStorage') && GamestateUsesMemoryStorage()) {
    if (function_exists('GetGamestateStorageKey') && function_exists('apcu_fetch')) {
      $cached = apcu_fetch(GetGamestateStorageKey($gameName));
      $hasMemoryGamestate = ($cached !== false);
    }
  }

  $hasGameDir = is_dir($gameDir);
  if (!$hasGameDir && !$hasMemoryGamestate) BridgeFail('Draft game not found.', ['gameName' => $gameName]);

  $hasFileGamestate = false;
  if ($hasGameDir) {
    $gameStatePath = $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt';
    $hasFileGamestate = is_file($gameStatePath);
  }

  if (!$hasFileGamestate && !$hasMemoryGamestate) {
    BridgeFail('Draft game gamestate is missing (neither file nor memory storage found).', ['gameName' => $gameName]);
  }
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

function BridgeMutationPlayer($perspectivePlayer) {
  return $perspectivePlayer > 0 ? $perspectivePlayer : intval($GLOBALS['playerID'] ?? 1);
}

function BridgeApplyPropertiesToZoneEntry($zoneEntry, $properties) {
  if (!is_object($zoneEntry) || !is_array($properties)) return;

  if (method_exists($zoneEntry, 'ClearIndex')) {
    $zoneEntry->ClearIndex();
  }

  foreach ($properties as $property => $value) {
    if ($property === 'CardID' || $property === 'cardID') continue;
    $zoneEntry->$property = $value;
  }

  if (method_exists($zoneEntry, 'BuildIndex')) {
    $zoneEntry->BuildIndex();
  }
}

function BridgeExtractAddCardSpec($mutation, $value) {
  $cardID = is_array($value) ? strval($value['CardID'] ?? $value['cardID'] ?? '') : strval($value ?? '');
  $properties = is_array($mutation['properties'] ?? null) ? $mutation['properties'] : [];

  if (is_array($value)) {
    foreach ($value as $property => $propertyValue) {
      if ($property === 'CardID' || $property === 'cardID') continue;
      $properties[$property] = $propertyValue;
    }
  }

  return [$cardID, $properties];
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

    $mutationPlayer = BridgeMutationPlayer($perspectivePlayer);

    if ($operation === 'clearZone') {
      MZClearZone($mutationPlayer, $zoneName);
    } else if ($operation === 'addCard') {
      [$cardID, $properties] = BridgeExtractAddCardSpec($mutation, $value);
      if ($cardID === '') {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('Scenario addCard mutation is missing a CardID.', $mutation);
      }

      $newObj = MZAddZone($mutationPlayer, $zoneName, $cardID);
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

      BridgeApplyPropertiesToZoneEntry($newObj, $properties);
    } else if ($operation === 'setProperties') {
      if ($index < 0) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('Scenario setProperties mutation is missing a valid index.', $mutation);
      }
      $zone = &GetZone($zoneName);
      if (!is_array($zone) || !isset($zone[$index])) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('Scenario mutation points to an invalid zone entry.', $mutation);
      }
      BridgeApplyPropertiesToZoneEntry($zone[$index], is_array($mutation['properties'] ?? null) ? $mutation['properties'] : []);
    } else if ($operation === 'replaceChampion') {
      // Find the first CHAMPION card in the zone and replace its CardID.
      $zone = &GetZone($zoneName);
      if (!is_array($zone)) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('replaceChampion: zone not found.', $mutation);
      }
      $found = false;
      foreach ($zone as &$champObj) {
        if (!is_object($champObj) || !empty($champObj->removed)) continue;
        if (PropertyContains(CardType($champObj->CardID), 'CHAMPION')) {
          if (method_exists($champObj, 'ClearIndex')) $champObj->ClearIndex();
          $champObj->CardID = strval($value);
          if (method_exists($champObj, 'BuildIndex')) $champObj->BuildIndex();
          $found = true;
          break;
        }
      }
      unset($champObj);
      if (!$found) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('replaceChampion: no CHAMPION found in zone.', $mutation);
      }
    } else if ($operation === 'setElementSpirit') {
      // Find the champion in the zone and set its Subcards to the spirit for the given element.
      $spiritMap = [
        'FIRE'  => 'da2ha4dk88', // Spirit of Serene Fire
        'WATER' => 'zq9ox7u6wz', // Spirit of Serene Water
        'WIND'  => 'h973fdt8pt', // Spirit of Serene Wind
      ];
      $elementKey = strtoupper(strval($value));
      if (!isset($spiritMap[$elementKey])) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('setElementSpirit: unknown element. Valid: fire, water, wind.', ['element' => $value]);
      }
      $zone = &GetZone($zoneName);
      if (!is_array($zone)) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('setElementSpirit: zone not found.', $mutation);
      }
      $found = false;
      foreach ($zone as &$champObj) {
        if (!is_object($champObj) || !empty($champObj->removed)) continue;
        if (PropertyContains(CardType($champObj->CardID), 'CHAMPION')) {
          $champObj->Subcards = [$spiritMap[$elementKey]];
          $found = true;
          break;
        }
      }
      unset($champObj);
      if (!$found) {
        if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
        BridgeFail('setElementSpirit: no CHAMPION found in zone.', $mutation);
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

function BridgeBuildMzId($zoneName, $index, $perspectivePlayer) {
  if ($index < 0) return '';
  $prefix = str_starts_with($zoneName, 'their') ? 'their' : 'my';
  if (!preg_match('/^(my|their)(.+)$/', $zoneName, $matches)) {
    return $zoneName . '-' . $index;
  }
  return $prefix . $matches[2] . '-' . $index;
}

function BridgeAddToZone($root, $gameName, $zoneName, $cardID, $perspectivePlayer = 1) {
  BridgeLoadRuntimeGame($root, $gameName);
  $originalPlayerID = $GLOBALS['playerID'] ?? null;
  $GLOBALS['playerID'] = intval($perspectivePlayer);
  try {
    $newObj = MZAddZone(intval($perspectivePlayer), $zoneName, $cardID);
    if ($newObj === null) BridgeFail('Could not add card to zone.', compact('zoneName', 'cardID', 'perspectivePlayer'));

    if (preg_match('/Field$/', $zoneName)) {
      $controller = ($zoneName === 'theirField') ? ($perspectivePlayer == 1 ? 2 : 1) : $perspectivePlayer;
      if (!isset($newObj->Status) || $newObj->Status === '-') $newObj->Status = 2;
      if (!isset($newObj->Owner) || $newObj->Owner === '-') $newObj->Owner = $controller;
      if (!isset($newObj->Controller) || $newObj->Controller === '-') $newObj->Controller = $controller;
      if (!isset($newObj->Damage) || $newObj->Damage === '-') $newObj->Damage = 0;
      if (!is_array($newObj->TurnEffects)) $newObj->TurnEffects = [];
      if (!is_array($newObj->Counters)) $newObj->Counters = [];
      if (!is_array($newObj->Subcards)) $newObj->Subcards = [];
    }

    WriteGamestate('./' . $root . '/');
    return [
      'success' => true,
      'gameName' => $gameName,
      'zone' => $zoneName,
      'cardID' => $cardID,
      'mzID' => BridgeBuildMzId($zoneName, intval($newObj->mzIndex ?? -1), intval($perspectivePlayer)),
      'gamestateHash' => RegressionCurrentGamestateHash($root, $gameName),
    ];
  } finally {
    if ($originalPlayerID !== null) $GLOBALS['playerID'] = $originalPlayerID;
  }
}

function BridgeAddCounters($root, $gameName, $mzID, $counterType, $amount, $perspectivePlayer = 1) {
  BridgeLoadRuntimeGame($root, $gameName);
  $mzID = strval($mzID);
  $counterType = strval($counterType);
  $amount = intval($amount);
  $perspectivePlayer = intval($perspectivePlayer);
  if ($counterType === '') BridgeFail('Counter type is required.');
  if ($amount === 0) BridgeFail('Counter amount cannot be zero.');

  return BridgeWithPlayerPerspective($perspectivePlayer, function() use ($root, $gameName, $mzID, $counterType, $amount, $perspectivePlayer) {
    $zoneObject = &GetZoneObject($mzID);
    if (!is_object($zoneObject)) BridgeFail('Invalid mzID for counter edit.', ['mzID' => $mzID, 'perspectivePlayer' => $perspectivePlayer]);
    if (!property_exists($zoneObject, 'Counters')) BridgeFail('Zone object does not support counters.', ['mzID' => $mzID, 'perspectivePlayer' => $perspectivePlayer]);
    if (!is_array($zoneObject->Counters)) $zoneObject->Counters = [];

    $current = intval($zoneObject->Counters[$counterType] ?? 0);
    $newValue = $current + $amount;
    if ($newValue <= 0) {
      unset($zoneObject->Counters[$counterType]);
      $newValue = 0;
    } else {
      $zoneObject->Counters[$counterType] = $newValue;
    }

    WriteGamestate('./' . $root . '/');
    return [
      'success' => true,
      'gameName' => $gameName,
      'mzID' => $mzID,
      'counterType' => $counterType,
      'counterValue' => $newValue,
      'perspectivePlayer' => $perspectivePlayer,
      'gamestateHash' => RegressionCurrentGamestateHash($root, $gameName),
    ];
  });
}

function BridgeActivePlayer() {
  return intval($GLOBALS['currentPlayer'] ?? 0);
}

function BridgeMasterySummary($playerID) {
  $zone = GetMastery($playerID);
  $summary = [];
  if (!is_array($zone)) return $summary;
  foreach ($zone as $obj) {
    if (!is_object($obj)) continue;
    if (method_exists($obj, 'Removed') && $obj->Removed()) continue;
    $summary[] = [
      'cardID' => strval($obj->CardID ?? ''),
      'direction' => strval($obj->Direction ?? ''),
      'counters' => is_array($obj->Counters ?? null) ? $obj->Counters : [],
    ];
  }
  return $summary;
}

function BridgeDecisionQueueSummary($playerID) {
  $queue = GetDecisionQueue($playerID);
  $summary = [
    'count' => 0,
    'next' => null,
  ];
  if (!is_array($queue)) return $summary;
  foreach ($queue as $decision) {
    if (!is_object($decision)) continue;
    if (method_exists($decision, 'Removed') && $decision->Removed()) continue;
    $summary['count']++;
    if ($summary['next'] === null) {
      $summary['next'] = [
        'type' => strval($decision->Type ?? ''),
        'tooltip' => BridgeDecisionTooltip($decision),
        'param' => strval($decision->Param ?? ''),
      ];
    }
  }
  return $summary;
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

function BridgeEnumerateMultiChoiceResults($choices, $min, $max, $limit = 256) {
  $choices = array_values(array_unique(array_filter($choices, fn($value) => $value !== '')));
  $count = count($choices);
  $max = min(max(0, intval($max)), $count);
  $min = min(max(0, intval($min)), $max);

  $results = [];
  if ($min === 0) {
    $results[] = '-';
  }
  if ($max === 0) return $results;

  $combo = [];
  $stopped = false;
  $builder = null;
  $builder = function($startIndex, $remaining) use (&$builder, &$choices, &$results, &$combo, &$stopped, $limit) {
    if ($stopped) return;
    if ($remaining === 0) {
      $results[] = implode('&', $combo);
      if (count($results) >= $limit) $stopped = true;
      return;
    }

    $lastIndex = count($choices) - $remaining;
    for ($index = $startIndex; $index <= $lastIndex; ++$index) {
      $combo[] = $choices[$index];
      $builder($index + 1, $remaining - 1);
      array_pop($combo);
      if ($stopped) return;
    }
  };

  for ($size = max(1, $min); $size <= $max; ++$size) {
    $builder(0, $size);
    if ($stopped) break;
  }

  return $results;
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
      case 'MZMULTICHOOSE':
        $paramParts = explode('|', strval($decision->Param ?? ''), 3);
        $min = intval($paramParts[0] ?? 0);
        $max = intval($paramParts[1] ?? 0);
        $rawChoices = array_values(array_filter(explode('&', strval($paramParts[2] ?? '')), fn($value) => $value !== ''));
        $choices = [];
        foreach ($rawChoices as $rawChoice) {
          foreach (BridgeExpandDecisionSpecChoices($rawChoice) as $expandedChoice) {
            $choices[] = $expandedChoice;
          }
        }
        $choices = array_values(array_unique($choices));
        foreach (BridgeEnumerateMultiChoiceResults($choices, $min, $max) as $choiceSet) {
          $resolvedCardIDs = [];
          if ($choiceSet !== '-') {
            foreach (explode('&', $choiceSet) as $choice) {
              $metadata = BridgeActionCardMetadata($choice);
              if (isset($metadata['resolvedCardID'])) {
                $resolvedCardIDs[] = $metadata['resolvedCardID'];
              }
            }
          }
          $actions[] = [
            'playerID' => $player,
            'mode' => 100,
            'buttonInput' => '',
            'cardID' => $choiceSet,
            'chkInput' => [],
            'inputText' => '',
            'resolvedCardIDs' => $resolvedCardIDs,
          ];
        }
        break;
      case 'NUMBERCHOOSE':
        $parts = explode('|', strval($decision->Param ?? ''), 2);
        $min = intval($parts[0] ?? 0);
        $max = intval($parts[1] ?? $min);
        if ($max < $min) {
          $tmp = $min;
          $min = $max;
          $max = $tmp;
        }
        for ($n = $min; $n <= $max; ++$n) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => strval($n), 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'TWOSIDEDSLIDER':
        foreach (BridgeEnumerateTwoSidedSliderResults(strval($decision->Param ?? '')) as $resultStr) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $resultStr, 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'MZSPLITASSIGN':
        $paramParts = explode('|', strval($decision->Param ?? ''), 2);
        $amount = max(0, intval($paramParts[0] ?? 0));
        $rawChoices = array_values(array_filter(explode('&', strval($paramParts[1] ?? '')), fn($value) => $value !== ''));
        $choices = [];
        foreach ($rawChoices as $rawChoice) {
          foreach (BridgeExpandDecisionSpecChoices($rawChoice) as $expandedChoice) {
            $choices[] = $expandedChoice;
          }
        }
        $choices = array_values(array_unique($choices));
        foreach (BridgeEnumerateSplitAssignResults($choices, $amount) as $assignmentStr) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $assignmentStr, 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'MZREARRANGE':
        foreach (BridgeEnumerateRearrangeResults(strval($decision->Param ?? '')) as $resultStr) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $resultStr, 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'MZMODAL':
        foreach (BridgeEnumerateModalResults(strval($decision->Param ?? '')) as $resultStr) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $resultStr, 'chkInput' => [], 'inputText' => ''];
        }
        break;
      default:
        // Fallback to allow engine-side pass/skip handlers when a decision type isn't enumerated yet.
        $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''];
        break;
    }

    return $actions;
  });
}

function BridgeEnumerateFSMActionsForZone($player, $zoneName) {
  $actions = [];
  $zone = GetZone($zoneName);
  if (!is_array($zone)) return $actions;

  $isHighlightedFromMeta = function($metaJson) {
    $meta = json_decode(strval($metaJson), true);
    if (!is_array($meta)) return false;
    if (isset($meta['highlight'])) return boolval($meta['highlight']);
    return isset($meta['color']) && strval($meta['color']) !== '';
  };

  for ($index = 0; $index < count($zone); ++$index) {
    $obj = $zone[$index];
    if (!is_object($obj) || !empty($obj->removed)) continue;
    $mzId = $zoneName . '-' . $index;

    if (function_exists('CanActivateCard')) {
      if (!CanActivateCard($player, $mzId, false)) continue;
    }
    // RL legality tightening: for hand-origin actions, require reserve affordability.
    // In GA UI this is often shown as yellow (advisory), but for RL we treat unaffordable
    // hand cards as illegal to avoid repeated dead-end attempts.
    if ($zoneName === 'myHand' && function_exists('CanAffordActivationReserve')) {
      if (!CanAffordActivationReserve($player, $obj)) continue;
    }
    // First-player turn 1 cannot activate ATTACK cards.
    if ($zoneName === 'myField' && function_exists('IsFirstTurnAttackLocked') && IsFirstTurnAttackLocked($player)) {
      if (PropertyContains(EffectiveCardType($obj), 'ATTACK')) continue;
    }
    // Align RL legal actions with engine/UI legality for tricky zones:
    // - MaterialSelectionMetadata mirrors ActionMap myMaterial conditions.
    // - EphemerateMeta captures graveyard/memory-cast legality (ephemerate/glimmer/opportunity).
    // - BanishSelectionMetadata captures banish-cast legality.
    if ($zoneName === 'myMaterial' && function_exists('MaterialSelectionMetadata')) {
      try {
        if (!$isHighlightedFromMeta(MaterialSelectionMetadata($obj))) continue;
      } catch (Throwable $throwable) {
        continue;
      }
    }
    if (($zoneName === 'myMemory' || $zoneName === 'myGraveyard') && function_exists('EphemerateMeta')) {
      try {
        if (!$isHighlightedFromMeta(EphemerateMeta($obj))) continue;
      } catch (Throwable $throwable) {
        continue;
      }
    }
    if ($zoneName === 'myBanish' && function_exists('BanishSelectionMetadata')) {
      try {
        if (!$isHighlightedFromMeta(BanishSelectionMetadata($obj))) continue;
      } catch (Throwable $throwable) {
        continue;
      }
    }
    $actions[] = array_merge(
      ['playerID' => $player, 'mode' => 10002, 'buttonInput' => '', 'cardID' => $mzId . '!FSM!', 'chkInput' => [], 'inputText' => ''],
      BridgeActionCardMetadata($mzId)
    );
  }
  return $actions;
}

function BridgeEnumeratePlayableActions($player) {
  $actions = [];
  $GLOBALS['playerID'] = $player;
  $zones = ['myHand', 'myField', 'myMemory', 'myMaterial', 'myGraveyard', 'myBanish'];
  foreach ($zones as $zoneName) {
    $actions = array_merge($actions, BridgeEnumerateFSMActionsForZone($player, $zoneName));
  }

  // Dedupe by action payload identity.
  $seen = [];
  $deduped = [];
  foreach ($actions as $action) {
    $key = ($action['mode'] ?? '') . '|' . ($action['playerID'] ?? '') . '|' . ($action['cardID'] ?? '') . '|' . ($action['buttonInput'] ?? '') . '|' . ($action['inputText'] ?? '');
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $deduped[] = $action;
  }
  return $deduped;
}

function BridgeFilterActionsByPlayer($actions, $expectedPlayer) {
  $filtered = [];
  foreach ($actions as $action) {
    if (!is_array($action)) continue;
    $actionPlayer = intval($action['playerID'] ?? 0);
    if ($actionPlayer !== intval($expectedPlayer)) continue;
    $filtered[] = $action;
  }
  return $filtered;
}

function BridgeCountActiveZoneObjects($zoneName) {
  $zone = GetZone($zoneName);
  if (!is_array($zone)) return 0;
  $count = 0;
  foreach ($zone as $zoneObject) {
    if (is_object($zoneObject) && empty($zoneObject->removed)) ++$count;
  }
  return $count;
}

function BridgeGetOpportunityState() {
  $pendingHandler = DecisionQueueController::GetVariable('PendingOpportunityHandler');
  $pendingFirstPlayer = DecisionQueueController::GetVariable('PendingOpportunityFirstPlayer');
  $pendingNextPlayer = DecisionQueueController::GetVariable('PendingOpportunityNextPlayer');
  return [
    'pendingOpportunityHandler' => ($pendingHandler === null || $pendingHandler === '') ? '' : strval($pendingHandler),
    'pendingOpportunityFirstPlayer' => ($pendingFirstPlayer === null || $pendingFirstPlayer === '') ? null : intval($pendingFirstPlayer),
    'pendingOpportunityNextPlayer' => ($pendingNextPlayer === null || $pendingNextPlayer === '') ? null : intval($pendingNextPlayer),
    'effectStackCount' => BridgeCountActiveZoneObjects('EffectStack'),
  ];
}

function BridgeEnumerateSplitAssignResults($choices, $amount) {
  $choices = array_values(array_filter($choices, fn($c) => is_string($c) && $c !== ''));
  $amount = max(0, intval($amount));
  if ($amount === 0 || count($choices) === 0) return ['-'];

  $results = [];
  $seen = [];
  $maxResults = 200;

  $emit = function($assignmentMap) use (&$results, &$seen, $choices, $maxResults) {
    if (count($results) >= $maxResults) return;
    $parts = [];
    foreach ($choices as $mzID) {
      $amt = intval($assignmentMap[$mzID] ?? 0);
      if ($amt <= 0) continue;
      $parts[] = $mzID . ':' . $amt;
    }
    $str = empty($parts) ? '-' : implode(',', $parts);
    if (isset($seen[$str])) return;
    $seen[$str] = true;
    $results[] = $str;
  };

  // Always include "all to one target" options.
  foreach ($choices as $mzID) {
    $map = [];
    $map[$mzID] = $amount;
    $emit($map);
  }

  // Include a balanced baseline split.
  $n = count($choices);
  $base = intdiv($amount, $n);
  $rem = $amount % $n;
  $map = [];
  for ($i = 0; $i < $n; ++$i) {
    $map[$choices[$i]] = $base + ($i < $rem ? 1 : 0);
  }
  $emit($map);

  // Enumerate exact compositions for small pools.
  if ($amount <= 8 && $n <= 5) {
    $dist = array_fill(0, $n, 0);
    $recurse = function($idx, $remaining) use (&$recurse, &$dist, $n, $choices, $emit) {
      if ($idx === $n - 1) {
        $dist[$idx] = $remaining;
        $map = [];
        for ($i = 0; $i < $n; ++$i) $map[$choices[$i]] = $dist[$i];
        $emit($map);
        return;
      }
      for ($v = 0; $v <= $remaining; ++$v) {
        $dist[$idx] = $v;
        $recurse($idx + 1, $remaining - $v);
      }
    };
    $recurse(0, $amount);
  }

  return empty($results) ? ['-'] : $results;
}

function BridgeEnumerateRearrangeResults($param) {
  $param = strval($param);
  if ($param === '') return [''];
  $segments = array_values(array_filter(array_map('trim', explode(';', $param)), fn($s) => $s !== ''));
  if (empty($segments)) return [''];
  $piles = [];
  foreach ($segments as $seg) {
    $eq = strpos($seg, '=');
    if ($eq === false) {
      $piles[] = ['name' => $seg, 'cards' => []];
      continue;
    }
    $name = trim(substr($seg, 0, $eq));
    $cardsStr = trim(substr($seg, $eq + 1));
    $cards = $cardsStr === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $cardsStr)), fn($c) => $c !== ''));
    $piles[] = ['name' => $name, 'cards' => $cards];
  }
  if (empty($piles)) return [$param];

  $serialize = function($pileData) {
    $parts = [];
    foreach ($pileData as $pile) {
      $parts[] = $pile['name'] . '=' . implode(',', $pile['cards']);
    }
    return implode(';', $parts);
  };

  $results = [];
  // deterministic baseline: leave as-is
  $results[] = $serialize($piles);
  // alternate: reverse order within each pile
  $reversed = [];
  foreach ($piles as $pile) {
    $cards = $pile['cards'];
    $reversed[] = ['name' => $pile['name'], 'cards' => array_reverse($cards)];
  }
  $results[] = $serialize($reversed);
  // alternate: move all cards to the first pile in current order
  $all = [];
  foreach ($piles as $pile) $all = array_merge($all, $pile['cards']);
  $allFirst = [];
  foreach ($piles as $idx => $pile) {
    $allFirst[] = ['name' => $pile['name'], 'cards' => $idx === 0 ? $all : []];
  }
  $results[] = $serialize($allFirst);

  return array_values(array_unique($results));
}

function BridgeEnumerateModalResults($param) {
  $parts = explode('|', strval($param), 3);
  $min = intval($parts[0] ?? 0);
  $max = intval($parts[1] ?? $min);
  $labels = [];
  if (isset($parts[2])) {
    $labels = array_values(array_filter(array_map('trim', explode('&', $parts[2])), fn($s) => $s !== ''));
  }
  if ($max < $min) {
    $tmp = $min;
    $min = $max;
    $max = $tmp;
  }
  if (empty($labels)) return ['-'];

  $results = [];
  $n = count($labels);
  for ($k = $min; $k <= min($max, $n); ++$k) {
    if ($k <= 0) {
      $results[] = '-';
      continue;
    }
    // first-k deterministic selection
    $results[] = implode('&', array_slice($labels, 0, $k));
    // last-k deterministic selection
    $results[] = implode('&', array_slice($labels, $n - $k, $k));
  }
  return array_values(array_unique($results));
}

function BridgeEnumerateTwoSidedSliderResults($param) {
  $parts = explode('|', strval($param), 3);
  $min = intval($parts[0] ?? 0);
  $max = intval($parts[1] ?? $min);
  if ($max < $min) {
    $tmp = $min;
    $min = $max;
    $max = $tmp;
  }

  $results = [strval($max), strval($min)];
  $mid = intval(floor(($min + $max) / 2));
  $results[] = strval($mid);
  return array_values(array_unique($results));
}

function BridgeEnumerateLegalActions($root, $gameName) {
  BridgeLoadRuntimeGame($root, $gameName);
  return BridgeEnumerateLegalActionsLoaded($root, $gameName);
}

function BridgeEnumerateLegalActionsLoaded($root, $gameName) {
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
  $currentPlayer = BridgeActivePlayer();
  $phase = strval(GetCurrentPhase());
  $opportunityState = BridgeGetOpportunityState();
  $kind = 'free-play-fsm';
  if ($opportunityState['effectStackCount'] > 0) {
    $kind = 'effect-stack-fsm';
  } else if ($opportunityState['pendingOpportunityHandler'] !== '') {
    $kind = 'opportunity-window-fsm';
  }

  // In normal free-play/main-phase contexts, ownership should follow turn player.
  // currentPlayer can be stale across phase transitions and cause wrong-perspective
  // legal actions (e.g. p2 main showing p1 my* actions).
  $actingPlayer = $turnPlayer;
  if ($kind !== 'free-play-fsm') {
    $actingPlayer = $currentPlayer > 0 ? $currentPlayer : $turnPlayer;
  }
  $fsmActions = BridgeEnumeratePlayableActions($actingPlayer);
  // Real end-turn pass uses the health-zone CustomInput widget path (mode 10001),
  // not decision mode 100/PASS.
  $passAction = [
    'playerID' => $actingPlayer,
    'mode' => 10001,
    'buttonInput' => '',
    'cardID' => 'myHealth-0!CustomInput!PASS',
    'chkInput' => [],
    'inputText' => '',
  ];
  $canPhasePass = ($phase === 'MAIN' && $actingPlayer === $turnPlayer);
  $actions = $fsmActions;
  if ($canPhasePass) {
    $actions[] = $passAction;
  }
  // Safety guard: never surface cross-player actions to RL in free-play/FSM
  // contexts. This prevents stale-perspective loops where turnPlayer=2 but
  // the legal list still contains player 1 "my*" actions.
  $actions = BridgeFilterActionsByPlayer($actions, $actingPlayer);

  return [
    'success' => true,
    'kind' => $kind,
    'playerID' => $actingPlayer,
    'turnPlayer' => $turnPlayer,
    'currentPlayer' => $currentPlayer,
    'phase' => $phase,
    'canPhasePass' => $canPhasePass,
    'opportunityState' => $opportunityState,
    'actions' => $actions,
  ];
}

function BridgeApplyEngineAction($root, $gameName, $actionBase64) {
  $action = BridgeDecodeActionPayload($actionBase64);
  try {
    $result = EngineRunAction($action, $root, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    $result['gamestateHash'] = RegressionCurrentGamestateHash($root, $gameName);
    return $result;
  } catch (Throwable $throwable) {
    return [
      'success' => false,
      'message' => 'Engine action failed.',
      'error' => $throwable->getMessage(),
      'errorFile' => $throwable->getFile(),
      'errorLine' => $throwable->getLine(),
    ];
  }
}

function BridgeDecodeActionPayload($actionBase64) {
  $json = base64_decode($actionBase64, true);
  if ($json === false) BridgeFail('Action payload is not valid base64.');
  $action = json_decode($json, true);
  if (!is_array($action)) BridgeFail('Action payload is not valid JSON.');
  return $action;
}

function BridgeApplyEngineActionLoaded($root, $gameName, $action) {
  try {
    $result = EngineExecuteLoadedAction($action, $root, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    $result['gamestateHash'] = RegressionCurrentGamestateHash($root, $gameName);
    return $result;
  } catch (Throwable $throwable) {
    return [
      'success' => false,
      'message' => 'Engine action failed.',
      'error' => $throwable->getMessage(),
      'errorFile' => $throwable->getFile(),
      'errorLine' => $throwable->getLine(),
    ];
  }
}

function BridgeStepSelfplayGame($root, $gameName, $actionBase64) {
  BridgeLoadRuntimeGame($root, $gameName);
  $action = BridgeDecodeActionPayload($actionBase64);
  $t0 = microtime(true);
  $applyResult = BridgeApplyEngineActionLoaded($root, $gameName, $action);
  $t1 = microtime(true);
  $snapshot = BridgeSnapshotLoaded($root, $gameName, 'summary');
  $t2 = microtime(true);
  $legal = BridgeEnumerateLegalActionsLoaded($root, $gameName);
  $t3 = microtime(true);
  return [
    'success' => true,
    'applyResult' => $applyResult,
    'snapshot' => $snapshot,
    'legalActions' => $legal,
    'timingsMs' => [
      'apply' => intval(round(($t1 - $t0) * 1000)),
      'snapshot' => intval(round(($t2 - $t1) * 1000)),
      'enumerate' => intval(round(($t3 - $t2) * 1000)),
      'total' => intval(round(($t3 - $t0) * 1000)),
    ],
  ];
}

function BridgeSnapshot($root, $gameName, $view) {
  BridgeLoadRuntimeGame($root, $gameName);
  return BridgeSnapshotLoaded($root, $gameName, $view);
}

function BridgeSnapshotLoaded($root, $gameName, $view) {
  $payload = [
    'success' => true,
    'view' => $view,
    'activePlayer' => BridgeActivePlayer(),
    'phase' => strval(GetCurrentPhase()),
    'turnPlayer' => intval(GetTurnPlayer()),
    'turnNumber' => intval(GetTurnNumber()),
    'flashMessage' => function_exists('GetFlashMessage') ? strval(GetFlashMessage()) : '',
    'gamestateHash' => RegressionCurrentGamestateHash($root, $gameName),
  ];

  if ($view === 'summary') {
    $myChampion = BridgeChampionSummary(1);
    $theirChampion = BridgeChampionSummary(2);
    $terminal = BridgeTerminalStateFromDQVariables();
    $payload['zones'] = [
      'myHandCount' => BridgeCountActiveZoneObjects('myHand'),
      'theirHandCount' => BridgeCountActiveZoneObjects('theirHand'),
      'myFieldCount' => BridgeCountActiveZoneObjects('myField'),
      'theirFieldCount' => BridgeCountActiveZoneObjects('theirField'),
      'myDeckCount' => BridgeCountActiveZoneObjects('myDeck'),
      'theirDeckCount' => BridgeCountActiveZoneObjects('theirDeck'),
      'myMemoryCount' => BridgeCountActiveZoneObjects('myMemory'),
      'theirMemoryCount' => BridgeCountActiveZoneObjects('theirMemory'),
      'myMaterialCount' => BridgeCountActiveZoneObjects('myMaterial'),
      'theirMaterialCount' => BridgeCountActiveZoneObjects('theirMaterial'),
    ];
    $payload['players'] = [
      'player1' => [
        'mastery' => BridgeMasterySummary(1),
        'champion' => $myChampion,
        'decisionQueue' => BridgeDecisionQueueSummary(1),
      ],
      'player2' => [
        'mastery' => BridgeMasterySummary(2),
        'champion' => $theirChampion,
        'decisionQueue' => BridgeDecisionQueueSummary(2),
      ],
    ];
    $payload['terminal'] = $terminal;
  } else {
    $payload['gamestateText'] = RegressionCurrentGamestateText($root, $gameName);
  }

  return $payload;
}

function BridgeChampionSummary($playerID) {
  $zone = GetField($playerID);
  if (!is_array($zone)) {
    return [
      'found' => false,
      'mzID' => '',
      'cardID' => '',
      'baseLife' => 0,
      'damage' => 0,
      'remainingLife' => 0,
    ];
  }

  for ($i = 0; $i < count($zone); ++$i) {
    $obj = $zone[$i];
    if (!is_object($obj) || !empty($obj->removed)) continue;
    if (!PropertyContains(CardType($obj->CardID), 'CHAMPION')) continue;
    $baseLife = intval(CardLife($obj->CardID));
    $damage = intval($obj->Damage ?? 0);
    return [
      'found' => true,
      'mzID' => 'p' . $playerID . 'Field-' . $i,
      'cardID' => strval($obj->CardID),
      'baseLife' => $baseLife,
      'damage' => $damage,
      'remainingLife' => $baseLife - $damage,
    ];
  }

  return [
    'found' => false,
    'mzID' => '',
    'cardID' => '',
    'baseLife' => 0,
    'damage' => 0,
    'remainingLife' => 0,
  ];
}

function BridgeTerminalStateFromDQVariables() {
  $raw = GetDecisionQueueVariables();
  $vars = json_decode(strval($raw), true);
  $winner = 0;
  if (is_array($vars) && isset($vars['GAMEOVER_WINNER'])) {
    $winner = intval($vars['GAMEOVER_WINNER']);
  }
  $isTerminal = $winner === 1 || $winner === 2;
  return [
    'isTerminal' => $isTerminal,
    'winner' => $winner,
    'reason' => $isTerminal ? 'engine-gameover-variable' : '',
    'source' => 'DecisionQueueVariables.GAMEOVER_WINNER',
  ];
}

function BridgeLoadDeckForPlayer($root, $playerID, $deckText, &$summary) {
  $deckImportPath = RegressionRepoRoot() . DIRECTORY_SEPARATOR . $root . DIRECTORY_SEPARATOR . 'Custom' . DIRECTORY_SEPARATOR . 'DeckImport.php';
  if (!is_file($deckImportPath)) {
    BridgeFail('Deck import helper not found for root.', ['root' => $root, 'path' => $deckImportPath]);
  }
  include_once $deckImportPath;

  if (!function_exists('GrandArchiveResolveDeckInput')) {
    BridgeFail('GrandArchiveResolveDeckInput is not available for this root.', ['root' => $root]);
  }

  $resolved = GrandArchiveResolveDeckInput($deckText);
  if (!is_array($resolved) || empty($resolved['success'])) {
    return [
      'success' => false,
      'playerID' => $playerID,
      'message' => strval($resolved['message'] ?? 'Deck parse failed.'),
      'materialCount' => 0,
      'mainDeckCount' => 0,
      'unresolved' => is_array($resolved['unresolved'] ?? null) ? $resolved['unresolved'] : [],
    ];
  }

  $gameDeck = &GetDeck($playerID);
  $material = &GetMaterial($playerID);
  $mainCards = is_array($resolved['mainDeck'] ?? null) ? $resolved['mainDeck'] : [];
  $materialCards = is_array($resolved['material'] ?? null) ? $resolved['material'] : [];

  foreach ($materialCards as $cardID) {
    $material[] = new Material($cardID);
  }
  foreach ($mainCards as $cardID) {
    $gameDeck[] = new Deck($cardID);
  }

  // Deterministic shuffle for RL start reproducibility.
  EngineShuffle($gameDeck, false);

  $playerSummary = [
    'success' => true,
    'playerID' => $playerID,
    'message' => '',
    'materialCount' => count($materialCards),
    'mainDeckCount' => count($mainCards),
    'unresolved' => is_array($resolved['unresolved'] ?? null) ? array_values($resolved['unresolved']) : [],
  ];
  $summary[] = $playerSummary;
  return $playerSummary;
}

function BridgeStartSelfplayGame($root, $gameName, $seed, $deckTextP1, $deckTextP2, $memoryOnly = 'auto') {
  $gameName = trim(strval($gameName));
  if ($gameName === '') BridgeFail('gameName is required for start-selfplay-game.');

  $deckTextP1 = strval($deckTextP1);
  $deckTextP2 = strval($deckTextP2);
  if (trim($deckTextP1) === '') BridgeFail('deckTextP1 is required for start-selfplay-game.');
  if (trim($deckTextP2) === '') $deckTextP2 = $deckTextP1;

  EngineLoadRootRuntime($root);
  $GLOBALS['gameName'] = $gameName;

  $memoryOnlyRaw = strtolower(trim(strval($memoryOnly)));
  if ($memoryOnlyRaw === '' || $memoryOnlyRaw === 'auto') {
    $memoryOnlyResolved = (function_exists('GamestateUsesMemoryStorage') && GamestateUsesMemoryStorage());
  } else {
    $memoryOnlyResolved = in_array($memoryOnlyRaw, ['1', 'true', 'yes', 'on'], true);
  }

  if (!$memoryOnlyResolved) {
    $gameDir = BridgeDraftGameDir($root, $gameName);
    RegressionEnsureDir($gameDir);
  }

  InitializeGamestate();
  SetDeterministicRandomCounter(intval($seed));
  WriteGamestate('./' . $root . '/');
  ParseGamestate('./' . $root . '/');
  SetDeterministicRandomCounter(intval($seed));

  $deckSummary = [];
  $p1Result = BridgeLoadDeckForPlayer($root, 1, $deckTextP1, $deckSummary);
  if (empty($p1Result['success'])) {
    BridgeOut([
      'success' => false,
      'message' => 'Player 1 deck parse failed.',
      'playerResult' => $p1Result,
      'deckParseSummary' => $deckSummary,
      'gameName' => $gameName,
      'seed' => intval($seed),
    ]);
  }

  $p2Result = BridgeLoadDeckForPlayer($root, 2, $deckTextP2, $deckSummary);
  if (empty($p2Result['success'])) {
    BridgeOut([
      'success' => false,
      'message' => 'Player 2 deck parse failed.',
      'playerResult' => $p2Result,
      'deckParseSummary' => $deckSummary,
      'gameName' => $gameName,
      'seed' => intval($seed),
    ]);
  }

  $firstPlayer = &GetFirstPlayer();
  $firstPlayer = 1;
  $turnPlayer = &GetTurnPlayer();
  $turnPlayer = $firstPlayer;
  $currentTurn = &GetTurnNumber();
  $currentTurn = 1;

  $currentPhase = &GetCurrentPhase();
  $currentPhase = 'WU';
  SetPhaseParameters("-");
  QueuePregameStartingChampionSetup();
  AdvanceAndExecute("PASS");
  AutoAdvanceAndExecute();
  SaveUndoVersion($firstPlayer, "Pregame Starting Champion");

  WriteGamestate('./' . $root . '/');
  $legalActions = BridgeEnumerateLegalActions($root, $gameName);

  return [
    'success' => true,
    'gameName' => $gameName,
    'seed' => intval($seed),
    'memoryOnlyResolved' => $memoryOnlyResolved,
    'deckParseSummary' => $deckSummary,
    'gamestateHash' => RegressionCurrentGamestateHash($root, $gameName),
    'snapshot' => BridgeSnapshot($root, $gameName, 'summary'),
    'legalActions' => $legalActions,
  ];
}

function BridgeDecodeDeckTextArg($value) {
  $raw = strval($value);
  if ($raw === '') return '';
  $decoded = base64_decode($raw, true);
  if ($decoded === false) return $raw;
  if ($decoded === '') return '';
  return $decoded;
}

$args = BridgeParseArgs($argv);
function BridgeDispatchCommand($command, $root, $args) {
  if ($command === '') BridgeFail('Missing --command argument.');
  if ($root === '') BridgeFail('Missing --root argument.');

  switch ($command) {
    case 'compile-scenario':
      $spec = BridgeParseTemplateSpec($args['spec'] ?? '');
      return ['success' => true, 'gamestateText' => BridgeCompileScenario($root, $spec)];
    case 'add-to-zone':
      return BridgeAddToZone(
        $root,
        $args['gameName'] ?? '',
        strval($args['zone'] ?? ''),
        strval($args['cardID'] ?? ''),
        intval($args['perspectivePlayer'] ?? 1)
      );
    case 'add-counters':
      return BridgeAddCounters(
        $root,
        $args['gameName'] ?? '',
        strval($args['mzID'] ?? ''),
        strval($args['counterType'] ?? ''),
        intval($args['amount'] ?? 0),
        intval($args['perspectivePlayer'] ?? 1)
      );
    case 'enumerate-legal-actions':
      return BridgeEnumerateLegalActions($root, $args['gameName'] ?? '');
    case 'apply-engine-action':
      return BridgeApplyEngineAction($root, $args['gameName'] ?? '', $args['action'] ?? '');
    case 'step-selfplay-game':
      return BridgeStepSelfplayGame($root, $args['gameName'] ?? '', $args['action'] ?? '');
    case 'get-game-snapshot':
      return BridgeSnapshot($root, $args['gameName'] ?? '', $args['view'] ?? 'summary');
    case 'start-selfplay-game':
      return BridgeStartSelfplayGame(
        $root,
        strval($args['gameName'] ?? ''),
        intval($args['seed'] ?? 0),
        BridgeDecodeDeckTextArg($args['deckTextP1'] ?? ''),
        BridgeDecodeDeckTextArg($args['deckTextP2'] ?? ''),
        strval($args['memoryOnly'] ?? 'auto')
      );
    default:
      BridgeFail('Unsupported command.', ['command' => $command]);
  }
}

$daemon = strval($args['daemon'] ?? '') === '1';
if ($daemon) {
  $GLOBALS['bridgeDaemonMode'] = true;
  while (($line = fgets(STDIN)) !== false) {
    $line = trim($line);
    if ($line === '') continue;
    ob_start();
    try {
      $request = json_decode($line, true);
      if (!is_array($request)) {
        throw new Exception('Invalid daemon request JSON.');
      }
      $command = strval($request['command'] ?? '');
      $root = strval($request['root'] ?? '');
      $requestArgs = is_array($request['args'] ?? null) ? $request['args'] : [];
      $result = BridgeDispatchCommand($command, $root, $requestArgs);
      ob_end_clean();
      echo json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    } catch (BridgeDaemonResponse $response) {
      ob_end_clean();
      echo json_encode($response->payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    } catch (Throwable $throwable) {
      ob_end_clean();
      echo json_encode([
        'success' => false,
        'message' => 'Bridge daemon request failed.',
        'error' => $throwable->getMessage(),
        'errorFile' => $throwable->getFile(),
        'errorLine' => $throwable->getLine(),
      ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
    flush();
  }
  exit(0);
}

$command = $args['command'] ?? '';
$root = $args['root'] ?? '';
BridgeOut(BridgeDispatchCommand($command, $root, $args));
