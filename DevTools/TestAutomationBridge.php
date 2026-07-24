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

function BridgeHydrateDiskGamestateIntoMemory($root, $gameName, $gameDir) {
  if (!function_exists('GamestateUsesMemoryStorage') || !GamestateUsesMemoryStorage()) return;
  if (!function_exists('GetGamestateStorageKey') || !function_exists('apcu_store')) return;

  $gameStatePath = $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt';
  if (!is_file($gameStatePath)) return;

  $gamestateText = file_get_contents($gameStatePath);
  if ($gamestateText === false || $gamestateText === '') return;
  apcu_store(GetGamestateStorageKey($gameName), $gamestateText, 600);
}

function BridgeExportMemoryGamestateToDiskIfBacked($root, $gameName) {
  if (!function_exists('GamestateUsesMemoryStorage') || !GamestateUsesMemoryStorage()) return;
  if (!function_exists('RegressionCurrentGamestateFromMemory')) return;

  $memoryOnlyGames = $GLOBALS['bridgeMemoryOnlyGames'] ?? [];
  if (!empty($memoryOnlyGames[strval($gameName)])) return;

  $gameStatePath = RegressionCurrentGamestatePath($root, $gameName);
  if (!is_file($gameStatePath)) return;

  $gamestateText = RegressionCurrentGamestateFromMemory($gameName);
  if ($gamestateText === null) return;
  file_put_contents($gameStatePath, $gamestateText);
}

function BridgeLoadRuntimeGame($root, $gameName) {
  EngineLoadRootRuntime($root);
  $gameDir = BridgeEnsureDraftGame($root, $gameName);
  $GLOBALS['gameName'] = strval($gameName);
  BridgeHydrateDiskGamestateIntoMemory($root, strval($gameName), $gameDir);
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
    BridgeExportMemoryGamestateToDiskIfBacked($root, $gameName);
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
    BridgeExportMemoryGamestateToDiskIfBacked($root, $gameName);
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
  if (!function_exists('GetMastery')) return [];
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

function BridgePlayableZonesForRoot($root) {
  switch ($root) {
    case 'AzukiSim':
      return ['myHand', 'myGarden', 'myAlley', 'myGate'];
    case 'GrandArchiveSim':
    default:
      return ['myHand', 'myField', 'myMemory', 'myMaterial', 'myGraveyard', 'myBanish'];
  }
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
    return $gamestate;
  } catch (Throwable $throwable) {
    BridgeFail('Scenario compilation failed.', $throwable->getMessage());
  } finally {
    RegressionDeleteDirRecursive($tempGameDir);
    if (function_exists('RegressionClearGamestateMemory')) {
      RegressionClearGamestateMemory($tempGameName);
    }
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
        // Runtime DecisionQueueController auto-passes a mandatory MZCHOOSE when
        // all queued candidates have disappeared. Mirror that behavior for
        // self-play so a stale post-cleanup choice cannot strand an episode.
        if ($decision->Type === 'MZCHOOSE' && empty($actions)) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''];
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
      case 'CHOOSEZONE':
        $choices = array_values(array_filter(explode('&', strval($decision->Param ?? '')), fn($value) => $value !== ''));
        foreach ($choices as $choice) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $choice, 'chkInput' => [], 'inputText' => ''];
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
        // Unknown interactive decisions should not be serialized as PASS; that creates illegal/no-op training data.
        break;
    }

    return $actions;
  });
}

function BridgeAzukiActivationAbilityCount($obj) {
  if (!is_object($obj) || !function_exists('GetObjectMacroCardIDCandidates') || !function_exists('CardActivateAbilityCount')) return 0;
  $abilityCount = 0;
  foreach (GetObjectMacroCardIDCandidates($obj) as $candidateCardID) {
    $abilityCount = max($abilityCount, intval(CardActivateAbilityCount($candidateCardID)));
  }
  return $abilityCount;
}

function BridgeEnumerateAzukiCustomInputActions($player, $zoneName, $index, $obj) {
  $actions = [];
  $mzId = $zoneName . '-' . $index;

  if ($zoneName === 'myGate') {
    try {
      if (function_exists('CanUseGateRuntime') && function_exists('GetPortalCandidates') && CanUseGateRuntime($player, $mzId, '') && !empty(GetPortalCandidates($player))) {
        $actions[] = array_merge(
          ['playerID' => $player, 'mode' => 10001, 'buttonInput' => '', 'cardID' => $mzId . '!CustomInput!Activate', 'chkInput' => [], 'inputText' => ''],
          BridgeActionCardMetadata($mzId)
        );
      }
    } catch (Throwable $throwable) {
      return [];
    }
    return $actions;
  }

  if ($zoneName !== 'myGarden' && $zoneName !== 'myAlley') return $actions;

  if ($zoneName === 'myGarden') {
    try {
      if (
        function_exists('CanAttackWith')
        && (!function_exists('HasPendingAttackResponse') || !HasPendingAttackResponse())
        && CanAttackWith($player, $mzId)
      ) {
        $actions[] = array_merge(
          ['playerID' => $player, 'mode' => 10001, 'buttonInput' => '', 'cardID' => $mzId . '!CustomInput!Attack', 'chkInput' => [], 'inputText' => ''],
          BridgeActionCardMetadata($mzId)
        );
      }
    } catch (Throwable $throwable) {
      // Keep ability enumeration below tolerant of transient object state.
    }
  }

  if (!function_exists('CanActivateAbilityRuntime') || !function_exists('CanActivateAbilityWithCopiedText')) return $actions;

  $abilityCount = BridgeAzukiActivationAbilityCount($obj);
  for ($abilityIndex = 0; $abilityIndex < $abilityCount; ++$abilityIndex) {
    try {
      if (!CanActivateAbilityRuntime($player, $mzId, $abilityIndex)) continue;
      if (!CanActivateAbilityWithCopiedText($player, $mzId, $abilityIndex)) continue;
    } catch (Throwable $throwable) {
      continue;
    }
    $actionValue = ($abilityCount === 1 && $abilityIndex === 0) ? 'Activate' : ('Activate:' . $abilityIndex);
    $actions[] = array_merge(
      ['playerID' => $player, 'mode' => 10001, 'buttonInput' => '', 'cardID' => $mzId . '!CustomInput!' . $actionValue, 'chkInput' => [], 'inputText' => ''],
      BridgeActionCardMetadata($mzId)
    );
  }

  return $actions;
}

function BridgeAzukiShouldEmitGenericFsmClick($zoneName) {
  if ($zoneName !== 'myGarden') return true;
  return function_exists('HasPendingAttackResponse') && HasPendingAttackResponse();
}

function BridgeAzukiAllowsFsmClick($player, $zoneName, $index, $obj) {
  $mzId = $zoneName . '-' . $index;

  if ($zoneName === 'myHand') {
    try {
      $cardID = strval($obj->CardID ?? '');
      if ($cardID === '') return false;
      if (function_exists('CanPlayCardNow')) {
        if (!CanPlayCardNow($player, $cardID)) return false;
      } else if (function_exists('CanPlayCardByTiming') && !CanPlayCardByTiming($player, $cardID)) {
        return false;
      }
      if (function_exists('CardType') && function_exists('ResolveWeaponEquipTargets') && CardType($cardID) === 'WEAPON' && empty(ResolveWeaponEquipTargets($player))) return false;
      if (function_exists('CanPayIKZCost')) {
        $cost = function_exists('EffectivePlayCost') ? EffectivePlayCost($player, $cardID, $obj) : (function_exists('CardCost') ? intval(CardCost($cardID)) : 0);
        if (!CanPayIKZCost($player, $cost)) return false;
      }
    } catch (Throwable $throwable) {
      return false;
    }
    return true;
  }

  if ($zoneName === 'myGarden') {
    try {
      if (function_exists('HasPendingAttackResponse') && HasPendingAttackResponse()) {
        return function_exists('CanRedirectPendingAttack') && CanRedirectPendingAttack($player, $mzId);
      }
      return function_exists('CanAttackWith') && CanAttackWith($player, $mzId);
    } catch (Throwable $throwable) {
      return false;
    }
  }

  return false;
}

function BridgeEnumerateFSMActionsForZone($player, $zoneName, $root = '') {
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

    if ($root === 'AzukiSim') {
      $actions = array_merge($actions, BridgeEnumerateAzukiCustomInputActions($player, $zoneName, $index, $obj));
      if (!BridgeAzukiShouldEmitGenericFsmClick($zoneName)) continue;
      if (!BridgeAzukiAllowsFsmClick($player, $zoneName, $index, $obj)) continue;
    }

    if (function_exists('CanActivateCard')) {
      if (!CanActivateCard($player, $mzId, false)) continue;
    }
    if ($root !== 'AzukiSim' && function_exists('CardHasAbility') && in_array($zoneName, ['myGarden', 'myAlley', 'myGate'], true)) {
      try {
        if (!CardHasAbility($obj)) continue;
      } catch (Throwable $throwable) {
        continue;
      }
    }
    if ($zoneName === 'myHand' && (function_exists('CanPlayCardNow') || function_exists('CanPlayCardByTiming')) && function_exists('CanPayIKZCost')) {
      try {
        $cardID = strval($obj->CardID ?? '');
        if ($cardID === '') continue;
        if (function_exists('CanPlayCardNow')) {
          if (!CanPlayCardNow($player, $cardID)) continue;
        } else if (!CanPlayCardByTiming($player, $cardID)) {
          continue;
        }
        if (!CanPayIKZCost($player, function_exists('EffectivePlayCost') ? EffectivePlayCost($player, $cardID, $obj) : intval(CardCost($cardID)))) continue;
      } catch (Throwable $throwable) {
        continue;
      }
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

function BridgeEnumeratePlayableActions($player, $root = '') {
  $actions = [];
  $GLOBALS['playerID'] = $player;
  $zones = BridgePlayableZonesForRoot($root);
  foreach ($zones as $zoneName) {
    $actions = array_merge($actions, BridgeEnumerateFSMActionsForZone($player, $zoneName, $root));
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

function BridgePassActionForRoot($root, $player) {
  $cardID = ($root === 'AzukiSim') ? 'myLeaderHealthSlot!CustomInput!Pass' : 'myHealth-0!CustomInput!PASS';
  return [
    'playerID' => intval($player),
    'mode' => 10001,
    'buttonInput' => '',
    'cardID' => $cardID,
    'chkInput' => [],
    'inputText' => '',
  ];
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

function BridgeBucketAzukiLife($life) {
  $life = intval($life);
  if ($life <= 5) return 'critical';
  if ($life <= 10) return 'low';
  if ($life <= 15) return 'medium';
  return 'high';
}

function BridgeBucketAzukiStat($value) {
  $value = intval($value);
  if ($value <= 0) return '0';
  if ($value <= 2) return '1-2';
  if ($value <= 4) return '3-4';
  return '5+';
}

function BridgeAzukiFieldCardSummary($player, $obj, $exactCardID) {
  $cardID = strval($obj->CardID ?? '');
  $type = function_exists('CardType') ? strtoupper(strval(CardType($cardID))) : '';
  $attack = function_exists('ResolveEntityAttackValue') ? ResolveEntityAttackValue($player, $obj) : (function_exists('CardAttack') ? CardAttack($cardID) : 0);
  $health = function_exists('ResolveEntityHealthValue') ? ResolveEntityHealthValue($player, $obj) : (function_exists('CardHealth') ? CardHealth($cardID) : 0);
  $damage = intval($obj->Damage ?? 0);
  $remaining = max(0, intval($health) - $damage);
  $parts = [
    'type' => $type !== '' ? $type : 'UNKNOWN',
    'status' => intval($obj->Status ?? 2) === 1 ? 'tapped' : 'ready',
    'atk' => BridgeBucketAzukiStat($attack),
    'hp' => BridgeBucketAzukiStat($remaining),
    'dmg' => BridgeBucketAzukiStat($damage),
  ];
  if ($exactCardID) $parts = ['cardID' => $cardID] + $parts;
  if (function_exists('IsDefenderEntity') && IsDefenderEntity($obj)) $parts['def'] = 1;
  if (function_exists('IsTauntEntity') && IsTauntEntity($obj)) $parts['taunt'] = 1;
  ksort($parts);
  return $parts;
}

function BridgeAzukiZoneCards($player, $zoneName, $exactCardID) {
  if ($zoneName === 'hand') $zone = function_exists('GetHand') ? GetHand($player) : [];
  else if ($zoneName === 'garden') $zone = function_exists('GetGarden') ? GetGarden($player) : [];
  else if ($zoneName === 'alley') $zone = function_exists('GetAlley') ? GetAlley($player) : [];
  else if ($zoneName === 'gate') $zone = function_exists('GetGate') ? GetGate($player) : [];
  else $zone = [];

  $items = [];
  if (!is_array($zone)) return $items;
  foreach ($zone as $obj) {
    if (!is_object($obj) || !empty($obj->removed)) continue;
    $cardID = strval($obj->CardID ?? '');
    if ($cardID === '') continue;
    if ($zoneName === 'hand' || $zoneName === 'gate') {
      $items[] = $exactCardID ? $cardID : (function_exists('CardType') ? strtoupper(strval(CardType($cardID))) : 'UNKNOWN');
    } else {
      $items[] = BridgeAzukiFieldCardSummary($player, $obj, $exactCardID);
    }
  }
  usort($items, function($a, $b) {
    return strcmp(json_encode($a, JSON_UNESCAPED_SLASHES), json_encode($b, JSON_UNESCAPED_SLASHES));
  });
  return $items;
}

function BridgeAzukiRlStateSummary() {
  $players = [];
  for ($player = 1; $player <= 2; ++$player) {
    $leader = BridgeAzukiLeaderSummary($player);
    $players['p' . $player] = [
      'lifeBucket' => BridgeBucketAzukiLife(intval($leader['remainingLife'] ?? 0)),
      'hand' => BridgeAzukiZoneCards($player, 'hand', true),
      'gardenExact' => BridgeAzukiZoneCards($player, 'garden', true),
      'alleyExact' => BridgeAzukiZoneCards($player, 'alley', true),
      'gardenAbstract' => BridgeAzukiZoneCards($player, 'garden', false),
      'alleyAbstract' => BridgeAzukiZoneCards($player, 'alley', false),
      'gate' => BridgeAzukiZoneCards($player, 'gate', true),
      'ikzAreaCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1IKZArea' : 'p2IKZArea'),
      'ikzPileCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1IKZPile' : 'p2IKZPile'),
      'ikzToken' => function_exists('GetIKZToken') ? intval(GetIKZToken($player)) : 0,
    ];
  }
  return $players;
}

function BridgeAzukiReadyAttackTotal($player) {
  $total = 0;
  foreach (['garden', 'alley'] as $zoneName) {
    if ($zoneName === 'garden') $zone = function_exists('GetGarden') ? GetGarden($player) : [];
    else $zone = function_exists('GetAlley') ? GetAlley($player) : [];
    if (!is_array($zone)) continue;
    foreach ($zone as $obj) {
      if (!is_object($obj) || !empty($obj->removed)) continue;
      if (intval($obj->Status ?? 2) !== 2) continue;
      $cardID = strval($obj->CardID ?? '');
      if ($cardID === '') continue;
      if (function_exists('CardType') && CardType($cardID) !== 'LEADER' && CardType($cardID) !== 'ENTITY') continue;
      $total += function_exists('ResolveEntityAttackValue') ? intval(ResolveEntityAttackValue($player, $obj)) : (function_exists('CardAttack') ? intval(CardAttack($cardID)) : 0);
    }
  }
  return $total;
}

function BridgeAzukiBoardAttackTotal($player) {
  $total = 0;
  foreach (['garden', 'alley'] as $zoneName) {
    if ($zoneName === 'garden') $zone = function_exists('GetGarden') ? GetGarden($player) : [];
    else $zone = function_exists('GetAlley') ? GetAlley($player) : [];
    if (!is_array($zone)) continue;
    foreach ($zone as $obj) {
      if (!is_object($obj) || !empty($obj->removed)) continue;
      $cardID = strval($obj->CardID ?? '');
      if ($cardID === '') continue;
      if (function_exists('CardType') && CardType($cardID) !== 'LEADER' && CardType($cardID) !== 'ENTITY') continue;
      $total += function_exists('ResolveEntityAttackValue') ? intval(ResolveEntityAttackValue($player, $obj)) : (function_exists('CardAttack') ? intval(CardAttack($cardID)) : 0);
    }
  }
  return $total;
}

function BridgeAzukiStrategyStateSummary() {
  $players = [];
  for ($player = 1; $player <= 2; ++$player) {
    $leader = BridgeAzukiLeaderSummary($player);
    $players['p' . $player] = [
      'lifeBucket' => BridgeBucketAzukiLife(intval($leader['remainingLife'] ?? 0)),
      'remainingLife' => intval($leader['remainingLife'] ?? 0),
      'readyAttack' => BridgeAzukiReadyAttackTotal($player),
      'boardAttack' => BridgeAzukiBoardAttackTotal($player),
    ];
  }
  return $players;
}

function BridgeAzukiAvailableIKZ($player) {
  $count = function_exists('CountAvailableIKZ') ? intval(CountAvailableIKZ($player)) : 0;
  $garden = function_exists('GetGarden') ? GetGarden($player) : [];
  if (is_array($garden)) {
    foreach ($garden as $entity) {
      if (!is_object($entity) || !empty($entity->removed)) continue;
      if (intval($entity->Status ?? 2) !== 2) continue;
      if (strval($entity->CardID ?? '') !== 'S1-STT03-007_Koyama-Farm-Caretaker_E_R_die') continue;
      ++$count;
    }
  }
  return $count;
}

function BridgeAzukiCompactStateSummary() {
  $players = [];
  for ($player = 1; $player <= 2; ++$player) {
    $leader = BridgeAzukiLeaderSummary($player);
    $players['p' . $player] = [
      'lifeBucket' => BridgeBucketAzukiLife(intval($leader['remainingLife'] ?? 0)),
      'remainingLife' => intval($leader['remainingLife'] ?? 0),
      'handCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1Hand' : 'p2Hand'),
      'availableIKZ' => BridgeAzukiAvailableIKZ($player),
      'ikzAreaCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1IKZArea' : 'p2IKZArea'),
      'ikzPileCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1IKZPile' : 'p2IKZPile'),
      'ikzToken' => function_exists('GetAccessibleIKZTokenCount') ? intval(GetAccessibleIKZTokenCount($player)) : 0,
      'readyAttack' => BridgeAzukiReadyAttackTotal($player),
      'boardAttack' => BridgeAzukiBoardAttackTotal($player),
      'gardenCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1Garden' : 'p2Garden'),
      'alleyCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1Alley' : 'p2Alley'),
      'gateCount' => BridgeCountActiveZoneObjects($player === 1 ? 'p1Gate' : 'p2Gate'),
    ];
  }
  return $players;
}

function BridgeRlActionTargetRole($action) {
  if (!is_array($action)) return 'other';
  $raw = strval($action['cardID'] ?? '');
  $resolved = strval($action['resolvedCardID'] ?? '');
  if ($resolved !== '' && function_exists('CardType') && strtoupper(strval(CardType($resolved))) === 'LEADER') return 'leader';
  if (str_starts_with($raw, 'theirGarden-')) return 'enemy-garden';
  if (str_starts_with($raw, 'theirAlley-')) return 'enemy-alley';
  if (str_starts_with($raw, 'myGarden-')) return 'own-garden';
  if (str_starts_with($raw, 'myAlley-')) return 'own-alley';
  if (str_starts_with($raw, 'myHand-')) return 'own-hand';
  if (str_starts_with($raw, 'theirHand-')) return 'enemy-hand';
  return 'other';
}

function BridgeRlEnemyTargetProfile($action) {
  if (!is_array($action)) return null;
  $role = BridgeRlActionTargetRole($action);
  if ($role !== 'leader' && !str_starts_with($role, 'enemy-')) return null;

  $raw = strval($action['cardID'] ?? '');
  $cardID = strval($action['resolvedCardID'] ?? '');
  $actingPlayer = intval($action['playerID'] ?? 0);
  $targetPlayer = $actingPlayer === 1 ? 2 : ($actingPlayer === 2 ? 1 : 0);
  $obj = function_exists('GetZoneObject') && $raw !== '' ? GetZoneObject($raw) : null;
  $attack = 0;
  $remainingHP = 0;

  if ($role === 'leader' && $targetPlayer !== 0) {
    $leader = BridgeAzukiLeaderSummary($targetPlayer);
    $remainingHP = max(0, intval($leader['remainingLife'] ?? 0));
  }
  if (is_object($obj)) {
    $attack = function_exists('ResolveEntityAttackValue') && $targetPlayer !== 0
      ? intval(ResolveEntityAttackValue($targetPlayer, $obj))
      : (function_exists('CardAttack') ? intval(CardAttack($cardID)) : 0);
    if ($role !== 'leader') {
      $health = function_exists('ResolveEntityHealthValue') && $targetPlayer !== 0
        ? intval(ResolveEntityHealthValue($targetPlayer, $obj))
        : (function_exists('CardHealth') ? intval(CardHealth($cardID)) : 0);
      $remainingHP = max(0, $health - intval($obj->Damage ?? 0));
    }
  } else {
    if (function_exists('CardAttack')) $attack = intval(CardAttack($cardID));
    if ($role !== 'leader' && function_exists('CardHealth')) $remainingHP = max(0, intval(CardHealth($cardID)));
  }

  $threat = function_exists('AzukiRlBotCardThreatValue') ? intval(AzukiRlBotCardThreatValue($cardID)) : 1;
  return [
    'attack' => max(0, $attack),
    'hp' => max(0, $remainingHP),
    'threat' => max(0, $threat),
  ];
}

function BridgeRlSemanticActionKey($action, $legal = [], $actionKeyVersion = 'semantic-v2') {
  if (!is_array($action)) return 'invalid';
  $raw = strval($action['cardID'] ?? '');
  $rawUpper = strtoupper($raw);
  $resolved = strval($action['resolvedCardID'] ?? '');
  $kind = is_array($legal) ? strval($legal['kind'] ?? '') : '';
  $decisionType = is_array($legal) ? strtoupper(strval($legal['decisionType'] ?? '')) : '';

  if ($rawUpper === 'PASS' || str_ends_with($rawUpper, '!CUSTOMINPUT!PASS')) {
    if ($kind === 'azuki-attack-response-fsm') return 'pass:response';
    if ($kind === 'opportunity-window-fsm' || $kind === 'effect-stack-fsm') return 'pass:opportunity';
    return 'pass:main';
  }

  if (str_contains($raw, '!CustomInput!')) {
    [, $operation] = array_pad(explode('!CustomInput!', $raw, 2), 2, '');
    $operationKey = strtolower(str_replace(':', '-', $operation));
    if (strcasecmp($operation, 'Attack') === 0) return 'attack:' . ($resolved !== '' ? $resolved : BridgeRlActionTargetRole($action));
    return 'activate:' . ($resolved !== '' ? $resolved : BridgeRlActionTargetRole($action)) . ':' . $operationKey;
  }

  if (str_ends_with($raw, '!FSM!')) {
    if (str_starts_with($raw, 'myHand-')) return 'play:' . ($resolved !== '' ? $resolved : 'unknown');
    return 'interact:' . BridgeRlActionTargetRole($action) . ':' . ($resolved !== '' ? $resolved : 'unknown');
  }

  if ($resolved !== '') {
    if (strval($actionKeyVersion) === 'semantic-v2') {
      $profile = BridgeRlEnemyTargetProfile($action);
      if (is_array($profile)) {
        return 'target:' . ($decisionType !== '' ? strtolower($decisionType) : 'card')
          . ':' . BridgeRlActionTargetRole($action)
          . ':atk=' . intval($profile['attack'] ?? 0)
          . ':hp=' . intval($profile['hp'] ?? 0)
          . ':threat=' . intval($profile['threat'] ?? 1);
      }
    }
    return 'target:' . ($decisionType !== '' ? strtolower($decisionType) : 'card') . ':' . BridgeRlActionTargetRole($action) . ':' . $resolved;
  }

  $choice = $raw === '' ? strval($action['buttonInput'] ?? '') : $raw;
  return 'choice:' . ($decisionType !== '' ? strtolower($decisionType) : strval($action['mode'] ?? 'action')) . ':' . $choice;
}

function BridgeAzukiPressureBucket($value) {
  $value = intval($value);
  if ($value <= 0) return '0';
  if ($value <= 2) return '1-2';
  if ($value <= 5) return '3-5';
  if ($value <= 9) return '6-9';
  return '10+';
}

function BridgeAzukiCompactStateKey($snapshot, $actingPlayer, $legal = []) {
  $actingPlayer = intval($actingPlayer);
  if ($actingPlayer !== 1 && $actingPlayer !== 2) $actingPlayer = 1;
  $opp = $actingPlayer === 1 ? 2 : 1;
  $compact = is_array($snapshot['azukiCompactState'] ?? null) ? $snapshot['azukiCompactState'] : [];
  $me = is_array($compact['p' . $actingPlayer] ?? null) ? $compact['p' . $actingPlayer] : [];
  $them = is_array($compact['p' . $opp] ?? null) ? $compact['p' . $opp] : [];
  $actions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
  $playCosts = [];
  $playCount = 0;
  $attackCount = 0;
  $activateCount = 0;
  foreach ($actions as $action) {
    $actionKey = BridgeRlSemanticActionKey($action, $legal);
    if (str_starts_with($actionKey, 'play:')) {
      ++$playCount;
      $resolved = strval($action['resolvedCardID'] ?? '');
      if ($resolved !== '' && function_exists('CardCost')) $playCosts[] = intval(CardCost($resolved));
    } else if (str_starts_with($actionKey, 'attack:')) {
      ++$attackCount;
    } else if (str_starts_with($actionKey, 'activate:')) {
      ++$activateCount;
    }
  }
  sort($playCosts, SORT_NUMERIC);
  $key = [
    'version' => 'AzukiSim:compact-v2',
    'context' => strval($legal['kind'] ?? ''),
    'decision' => strtoupper(strval($legal['decisionType'] ?? '')),
    'phase' => strval($snapshot['phase'] ?? ''),
    'isTurnPlayer' => intval($snapshot['turnPlayer'] ?? 0) === $actingPlayer ? 1 : 0,
    'myLife' => strval($me['lifeBucket'] ?? 'high'),
    'theirLife' => strval($them['lifeBucket'] ?? 'high'),
    'myHand' => min(10, intval($me['handCount'] ?? 0)),
    'myAvailableIKZ' => min(10, intval($me['availableIKZ'] ?? 0)),
    'myIKZArea' => min(10, intval($me['ikzAreaCount'] ?? 0)),
    'myIKZToken' => min(3, intval($me['ikzToken'] ?? 0)),
    'myReadyAttack' => BridgeAzukiPressureBucket($me['readyAttack'] ?? 0),
    'theirReadyAttack' => BridgeAzukiPressureBucket($them['readyAttack'] ?? 0),
    'myBoardAttack' => BridgeAzukiPressureBucket($me['boardAttack'] ?? 0),
    'theirBoardAttack' => BridgeAzukiPressureBucket($them['boardAttack'] ?? 0),
    'myBoardCount' => min(10, intval($me['gardenCount'] ?? 0) + intval($me['alleyCount'] ?? 0)),
    'theirBoardCount' => min(10, intval($them['gardenCount'] ?? 0) + intval($them['alleyCount'] ?? 0)),
    'myGate' => min(3, intval($me['gateCount'] ?? 0)),
    'theirGate' => min(3, intval($them['gateCount'] ?? 0)),
    'legalPlays' => min(10, $playCount),
    'minPlayCost' => empty($playCosts) ? -1 : min(9, $playCosts[0]),
    'maxPlayCost' => empty($playCosts) ? -1 : min(9, $playCosts[count($playCosts) - 1]),
    'legalAttacks' => min(10, $attackCount),
    'legalActivations' => min(10, $activateCount),
  ];
  ksort($key);
  return json_encode($key, JSON_UNESCAPED_SLASHES);
}

function BridgeAzukiCompactCountBucket($value) {
  $value = intval($value);
  if ($value <= 0) return '0';
  if ($value <= 2) return '1-2';
  if ($value <= 4) return '3-4';
  if ($value <= 7) return '5-7';
  return '8+';
}

function BridgeAzukiCompactStateKeyForVersion($snapshot, $actingPlayer, $legal, $version) {
  $actingPlayer = intval($actingPlayer);
  if ($actingPlayer !== 1 && $actingPlayer !== 2) $actingPlayer = 1;
  $opp = $actingPlayer === 1 ? 2 : 1;
  $compact = is_array($snapshot['azukiCompactState'] ?? null) ? $snapshot['azukiCompactState'] : [];
  $me = is_array($compact['p' . $actingPlayer] ?? null) ? $compact['p' . $actingPlayer] : [];
  $them = is_array($compact['p' . $opp] ?? null) ? $compact['p' . $opp] : [];
  $actions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
  $kind = strtolower(strval($legal['kind'] ?? ''));
  $decision = strtoupper(strval($legal['decisionType'] ?? ''));
  $playCount = 0;
  $attackCount = 0;
  $activateCount = 0;
  $nonPassCount = 0;
  foreach ($actions as $action) {
    $actionKey = BridgeRlSemanticActionKey($action, $legal);
    if (!str_starts_with($actionKey, 'pass:')) ++$nonPassCount;
    if (str_starts_with($actionKey, 'play:')) ++$playCount;
    else if (str_starts_with($actionKey, 'attack:')) ++$attackCount;
    else if (str_starts_with($actionKey, 'activate:')) ++$activateCount;
  }

  if ($kind === 'free-play-fsm') $context = 'main';
  else if ($kind === 'attack-response-fsm' || ($version === 'AzukiSim:compact-v4' && $kind === 'azuki-attack-response-fsm')) $context = 'response';
  else if ($kind === 'opportunity-fsm') $context = 'opportunity';
  else if ($decision !== '') $context = 'decision';
  else $context = $kind === '' ? 'other' : $kind;

  $key = [
    'version' => strval($version),
    'context' => $context,
    'decision' => $decision,
    'isTurnPlayer' => intval($snapshot['turnPlayer'] ?? 0) === $actingPlayer ? 1 : 0,
    'myLife' => strval($me['lifeBucket'] ?? 'high'),
    'theirLife' => strval($them['lifeBucket'] ?? 'high'),
    'myHand' => BridgeAzukiCompactCountBucket($me['handCount'] ?? 0),
    'myAvailableIKZ' => BridgeAzukiCompactCountBucket($me['availableIKZ'] ?? 0),
  ];

  if ($context === 'main') {
    $key['myReadyAttack'] = BridgeAzukiPressureBucket($me['readyAttack'] ?? 0);
    $key['theirReadyAttack'] = BridgeAzukiPressureBucket($them['readyAttack'] ?? 0);
    $key['myBoardCount'] = BridgeAzukiCompactCountBucket(intval($me['gardenCount'] ?? 0) + intval($me['alleyCount'] ?? 0));
    $key['theirBoardCount'] = BridgeAzukiCompactCountBucket(intval($them['gardenCount'] ?? 0) + intval($them['alleyCount'] ?? 0));
    $key['legalPlays'] = BridgeAzukiCompactCountBucket($playCount);
    $key['legalAttacks'] = BridgeAzukiCompactCountBucket($attackCount);
    $key['legalActivations'] = BridgeAzukiCompactCountBucket($activateCount);
  } else if ($context === 'response') {
    $key['incomingPressure'] = BridgeAzukiPressureBucket($them['boardAttack'] ?? 0);
    $key['myBoardCount'] = BridgeAzukiCompactCountBucket(intval($me['gardenCount'] ?? 0) + intval($me['alleyCount'] ?? 0));
    $key['legalResponses'] = BridgeAzukiCompactCountBucket($nonPassCount);
  } else if ($context === 'decision') {
    $key['legalChoices'] = BridgeAzukiCompactCountBucket(count($actions));
    if (in_array($decision, ['CHOOSEZONE', 'MZCHOOSE', 'MZMULTICHOOSE'], true)) {
      $key['myBoardCount'] = BridgeAzukiCompactCountBucket(intval($me['gardenCount'] ?? 0) + intval($me['alleyCount'] ?? 0));
      $key['theirBoardCount'] = BridgeAzukiCompactCountBucket(intval($them['gardenCount'] ?? 0) + intval($them['alleyCount'] ?? 0));
    }
  } else {
    $key['legalChoices'] = BridgeAzukiCompactCountBucket(count($actions));
  }

  ksort($key);
  return json_encode($key, JSON_UNESCAPED_SLASHES);
}

function BridgeAzukiCompactV3StateKey($snapshot, $actingPlayer, $legal = []) {
  return BridgeAzukiCompactStateKeyForVersion($snapshot, $actingPlayer, $legal, 'AzukiSim:compact-v3');
}

function BridgeAzukiCompactV4StateKey($snapshot, $actingPlayer, $legal = []) {
  return BridgeAzukiCompactStateKeyForVersion($snapshot, $actingPlayer, $legal, 'AzukiSim:compact-v4');
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
        'decisionParam' => strval($decision->Param ?? ''),
        'decisionTooltip' => BridgeDecisionTooltip($decision),
        'decisionTooltipRaw' => strval($decision->Tooltip ?? ''),
        'actions' => BridgeEnumerateDecisionActions($decision, $player),
      ];
    }
  }

  if ($root === 'AzukiSim' && function_exists('HasPendingAttackResponse') && HasPendingAttackResponse()) {
    $responderPlayer = function_exists('GetPendingAttackResponderPlayer') ? intval(GetPendingAttackResponderPlayer()) : 0;
    if ($responderPlayer === 1 || $responderPlayer === 2) {
      $actions = BridgeEnumeratePlayableActions($responderPlayer, $root);
      $actions[] = BridgePassActionForRoot($root, $responderPlayer);
      $actions = BridgeFilterActionsByPlayer($actions, $responderPlayer);
      return [
        'success' => true,
        'kind' => 'azuki-attack-response-fsm',
        'playerID' => $responderPlayer,
        'turnPlayer' => intval(GetTurnPlayer()),
        'currentPlayer' => BridgeActivePlayer(),
        'phase' => strval(GetCurrentPhase()),
        'canPhasePass' => false,
        'opportunityState' => BridgeGetOpportunityState(),
        'actions' => $actions,
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
  $fsmActions = BridgeEnumeratePlayableActions($actingPlayer, $root);
  // Real end-turn pass uses the health-zone CustomInput widget path (mode 10001),
  // not decision mode 100/PASS.
  $passAction = BridgePassActionForRoot($root, $actingPlayer);
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
    EngineLoadRootRuntime($root);
    $gameDir = BridgeEnsureDraftGame($root, $gameName);
    BridgeHydrateDiskGamestateIntoMemory($root, strval($gameName), $gameDir);
    $result = EngineRunAction($action, $root, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    BridgeExportMemoryGamestateToDiskIfBacked($root, $gameName);
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
    $myChampion = BridgePrimaryAvatarSummary($root, 1);
    $theirChampion = BridgePrimaryAvatarSummary($root, 2);
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
      'myGardenCount' => BridgeCountActiveZoneObjects('myGarden'),
      'theirGardenCount' => BridgeCountActiveZoneObjects('theirGarden'),
      'myAlleyCount' => BridgeCountActiveZoneObjects('myAlley'),
      'theirAlleyCount' => BridgeCountActiveZoneObjects('theirAlley'),
      'myGateCount' => BridgeCountActiveZoneObjects('myGate'),
      'theirGateCount' => BridgeCountActiveZoneObjects('theirGate'),
      'myIKZAreaCount' => BridgeCountActiveZoneObjects('myIKZArea'),
      'theirIKZAreaCount' => BridgeCountActiveZoneObjects('theirIKZArea'),
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
    if ($root === 'AzukiSim' && !empty($GLOBALS['bridgeIncludeAzukiRlState'])) {
      $payload['azukiRlState'] = BridgeAzukiRlStateSummary();
    }
    if ($root === 'AzukiSim' && !empty($GLOBALS['bridgeIncludeAzukiStrategyState'])) {
      $payload['azukiStrategyState'] = BridgeAzukiStrategyStateSummary();
    }
    if ($root === 'AzukiSim' && !empty($GLOBALS['bridgeIncludeAzukiCompactState'])) {
      $payload['azukiCompactState'] = BridgeAzukiCompactStateSummary();
    }
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

function BridgeAzukiLeaderSummary($playerID) {
  $empty = [
    'found' => false,
    'mzID' => '',
    'cardID' => '',
    'baseLife' => 0,
    'damage' => 0,
    'remainingLife' => 0,
  ];
  if (!function_exists('GetGarden')) return $empty;

  $zone = GetGarden($playerID);
  if (!is_array($zone)) return $empty;
  for ($i = 0; $i < count($zone); ++$i) {
    $obj = $zone[$i];
    if (!is_object($obj) || !empty($obj->removed)) continue;
    $cardID = strval($obj->CardID ?? '');
    if ($cardID === '') continue;
    $isLeader = false;
    if (function_exists('CardType')) {
      $isLeader = strtoupper(strval(CardType($cardID))) === 'LEADER';
    }
    if (!$isLeader && function_exists('CardCategory')) {
      $isLeader = strtoupper(strval(CardCategory($cardID))) === 'LEADER';
    }
    if (!$isLeader) continue;

    $baseLife = function_exists('LeaderMaxHealth') ? intval(LeaderMaxHealth($playerID)) : intval(CardHealth($cardID));
    if ($baseLife <= 0) $baseLife = 20;
    $damage = intval($obj->Damage ?? 0);
    return [
      'found' => true,
      'mzID' => 'p' . $playerID . 'Garden-' . $i,
      'cardID' => $cardID,
      'baseLife' => $baseLife,
      'damage' => $damage,
      'remainingLife' => function_exists('LeaderCurrentHealth') ? intval(LeaderCurrentHealth($playerID)) : max(0, $baseLife - $damage),
    ];
  }
  return $empty;
}

function BridgePrimaryAvatarSummary($root, $playerID) {
  if ($root === 'AzukiSim') return BridgeAzukiLeaderSummary($playerID);
  return BridgeChampionSummary($playerID);
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
  if ($root === 'AzukiSim') {
    return BridgeLoadAzukiDeckForPlayer($playerID, $deckText, $summary);
  }

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

function BridgeLoadAzukiCreateGameHelpers() {
  if (!defined('AZUKISIM_CREATEGAME_LIBRARY_ONLY')) {
    define('AZUKISIM_CREATEGAME_LIBRARY_ONLY', true);
  }
  $createGamePath = RegressionRepoRoot() . DIRECTORY_SEPARATOR . 'AzukiSim' . DIRECTORY_SEPARATOR . 'CreateGame.php';
  if (!is_file($createGamePath)) {
    BridgeFail('AzukiSim CreateGame helper not found.', ['path' => $createGamePath]);
  }
  include_once $createGamePath;
}

function BridgeAzukiDeckTextParts($deckText) {
  $trimmed = trim(strval($deckText));
  $normalized = strtolower($trimmed);
  $starterNames = ['raizan' => true, 'shao' => true, 'bobu' => true, 'zero' => true];

  if ($trimmed === '') {
    return ['preconstructed' => 'Raizan', 'deckLink' => ''];
  }
  if (isset($starterNames[$normalized])) {
    return ['preconstructed' => ucfirst($normalized), 'deckLink' => ''];
  }
  if (preg_match('/^preconstructed\s*:\s*(raizan|shao|bobu|zero)\s*$/i', $trimmed, $matches)) {
    return ['preconstructed' => ucfirst(strtolower($matches[1])), 'deckLink' => ''];
  }
  if (preg_match('/^starter\s*:\s*(raizan|shao|bobu|zero)\s*$/i', $trimmed, $matches)) {
    return ['preconstructed' => ucfirst(strtolower($matches[1])), 'deckLink' => ''];
  }
  $deckID = BridgeAzukiDeckIDFromText($trimmed);
  return ['preconstructed' => 'Raizan', 'deckLink' => $deckID === '' ? $trimmed : '', 'deckID' => $deckID];
}

function BridgeAzukiDeckIDFromText($deckText) {
  $trimmed = trim(strval($deckText));
  if (preg_match('/^\d+$/', $trimmed)) return $trimmed;
  if (preg_match('/^azukideck:(\d+)$/i', $trimmed, $matches)) return $matches[1];

  $parsed = parse_url($trimmed);
  if (!is_array($parsed) || empty($parsed['query'])) return '';
  parse_str(strval($parsed['query']), $query);
  if (strcasecmp(strval($query['folderPath'] ?? ''), 'AzukiDeck') !== 0) return '';
  $gameName = trim(strval($query['gameName'] ?? ''));
  return preg_match('/^\d+$/', $gameName) ? $gameName : '';
}

function BridgeAzukiDeckAPIURL($deckText, $deckID) {
  $configured = trim(strval(getenv('TCGENGINE_AZUKIDECK_API_URL') ?: ''));
  if ($configured !== '') {
    $separator = str_contains($configured, '?') ? '&' : '?';
    return $configured . $separator . http_build_query([
      'deckID' => $deckID,
      'format' => 'json',
      'folderPath' => 'AzukiDeck',
    ]);
  }

  $parsed = parse_url(trim(strval($deckText)));
  if (is_array($parsed) && isset($parsed['scheme'], $parsed['host'])) {
    $scheme = strtolower(strval($parsed['scheme']));
    $host = strval($parsed['host']);
    $port = isset($parsed['port']) ? ':' . intval($parsed['port']) : '';
    if (($scheme === 'https' || $scheme === 'http') && $host !== '') {
      $path = strval($parsed['path'] ?? '');
      $enginePath = preg_replace('#/NextTurn\.php$#i', '', $path);
      if ($enginePath === $path) $enginePath = '/TCGEngine';
      return $scheme . '://' . $host . $port . rtrim($enginePath, '/') . '/AzukiDeck/LoadDeck.php?' . http_build_query([
        'deckID' => $deckID,
        'format' => 'json',
      ]);
    }
  }

  return 'https://zendo.gg/TCGEngine/AzukiDeck/LoadDeck.php?' . http_build_query([
    'deckID' => $deckID,
    'format' => 'json',
  ]);
}

function BridgeNormalizeAzukiDeckAPIResponse($payload) {
  if (!is_array($payload)) {
    return ['success' => false, 'message' => 'AzukiDeck API returned invalid JSON.'];
  }
  if (isset($payload['error'])) {
    return ['success' => false, 'message' => 'AzukiDeck API error: ' . strval($payload['error'])];
  }

  $leader = trim(strval($payload['leader']['id'] ?? ''));
  $gate = trim(strval($payload['gate']['id'] ?? ($payload['base']['id'] ?? '')));
  $mainDeck = [];
  foreach (($payload['deck'] ?? []) as $entry) {
    if (!is_array($entry)) continue;
    $cardID = trim(strval($entry['id'] ?? ''));
    $quantity = max(0, intval($entry['count'] ?? 0));
    if ($cardID === '' || $quantity === 0) continue;
    for ($i = 0; $i < $quantity; ++$i) $mainDeck[] = $cardID;
  }

  $resolved = [
    'success' => $leader !== '' && $gate !== '' && count($mainDeck) > 0,
    'message' => '',
    'leader' => $leader,
    'gate' => $gate,
    'mainDeck' => $mainDeck,
    'unresolved' => [],
  ];
  if (!$resolved['success']) {
    $resolved['message'] = 'AzukiDeck API response needs a leader, gate, and at least one main-deck card.';
  }
  return function_exists('AzukiCanonicalizeResolvedDeck') ? AzukiCanonicalizeResolvedDeck($resolved) : $resolved;
}

function BridgeFetchAzukiDeckAPI($deckText, $deckID) {
  static $cache = [];
  $url = BridgeAzukiDeckAPIURL($deckText, $deckID);
  if (isset($cache[$url])) return $cache[$url];

  $body = false;
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $body = curl_exec($ch);
    $httpCode = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($body === false || $httpCode < 200 || $httpCode >= 300) {
      $detail = $curlError !== '' ? $curlError : 'HTTP ' . $httpCode;
      return ['success' => false, 'message' => 'Could not load AzukiDeck deck from API (' . $detail . ').', 'apiURL' => $url];
    }
  } else {
    $context = stream_context_create(['http' => [
      'timeout' => 15,
      'header' => "Accept: application/json\r\n",
      'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
      return ['success' => false, 'message' => 'Could not load AzukiDeck deck from API.', 'apiURL' => $url];
    }
  }

  $resolved = BridgeNormalizeAzukiDeckAPIResponse(json_decode(strval($body), true));
  $resolved['apiURL'] = $url;
  if (!empty($resolved['success'])) $cache[$url] = $resolved;
  return $resolved;
}

function BridgeResolveAzukiDeck($deckText, $deckID) {
  $apiResolved = BridgeFetchAzukiDeckAPI($deckText, $deckID);
  if (!empty($apiResolved['success'])) return $apiResolved;

  if (!function_exists('AzukiDeckReadDeckState')) {
    return $apiResolved;
  }
  $resolved = AzukiCanonicalizeResolvedDeck(AzukiDeckReadDeckState($deckID));
  if (!is_array($resolved) || empty($resolved['success'])) return $apiResolved;
  $resolved['apiURL'] = strval($apiResolved['apiURL'] ?? '');
  $resolved['source'] = 'local-fallback';
  return $resolved;
}

function BridgePopulateAzukiDeck($playerID, $resolved) {
  if (!is_array($resolved) || empty($resolved['success'])) return $resolved;

  $deck = &GetDeck($playerID);
  $garden = &GetGarden($playerID);
  $gate = &GetGate($playerID);

  $leaderCard = new Garden($resolved['leader']);
  NormalizeStartingGardenCard($leaderCard, $playerID);
  $garden[] = $leaderCard;

  $gateCard = new Gate($resolved['gate']);
  NormalizeStartingGateCard($gateCard, $playerID);
  $gate[] = $gateCard;

  foreach ($resolved['mainDeck'] as $cardID) $deck[] = new Deck($cardID);
  if (!empty($GLOBALS['bridgeDeterministicDeckShuffle'])) {
    AzukiDeterministicStartingDeckShuffle($deck, $playerID);
  } else {
    EngineShuffle($deck, true);
  }
  return $resolved;
}

function BridgeLoadAzukiDeckForPlayer($playerID, $deckText, &$summary) {
  BridgeLoadAzukiCreateGameHelpers();
  if (!function_exists('LoadPlayer')) {
    BridgeFail('AzukiSim LoadPlayer is not available.');
  }

  $parts = BridgeAzukiDeckTextParts($deckText);
  $resolved = null;
  try {
    if (($parts['deckID'] ?? '') !== '') {
      $resolved = BridgeResolveAzukiDeck($deckText, $parts['deckID']);
      if (empty($resolved['success'])) {
        throw new RuntimeException(strval($resolved['message'] ?? 'Could not load the selected AzukiDeck deck.'));
      }
      BridgePopulateAzukiDeck($playerID, $resolved);
    } else {
      LoadPlayer($playerID, $parts['preconstructed'], $parts['deckLink']);
    }
  } catch (Throwable $throwable) {
    return [
      'success' => false,
      'playerID' => $playerID,
      'message' => $throwable->getMessage(),
      'leader' => '',
      'gate' => '',
      'mainDeckCount' => 0,
      'unresolved' => [],
    ];
  }

  $garden = function_exists('GetGarden') ? GetGarden($playerID) : [];
  $gate = function_exists('GetGate') ? GetGate($playerID) : [];
  $deck = function_exists('GetDeck') ? GetDeck($playerID) : [];
  $leader = '';
  if (is_array($garden)) {
    foreach ($garden as $obj) {
      if (!is_object($obj) || !empty($obj->removed)) continue;
      $cardID = strval($obj->CardID ?? '');
      if ($cardID === '') continue;
      if ((function_exists('CardType') && strtoupper(strval(CardType($cardID))) === 'LEADER')
        || (function_exists('CardCategory') && strtoupper(strval(CardCategory($cardID))) === 'LEADER')) {
        $leader = $cardID;
        break;
      }
    }
  }
  $gateID = '';
  if (is_array($gate)) {
    foreach ($gate as $obj) {
      if (!is_object($obj) || !empty($obj->removed)) continue;
      $gateID = strval($obj->CardID ?? '');
      if ($gateID !== '') break;
    }
  }

  $playerSummary = [
    'success' => true,
    'playerID' => $playerID,
    'message' => '',
    'preconstructed' => $parts['preconstructed'],
    'deckLink' => $parts['deckLink'],
    'deckID' => strval($parts['deckID'] ?? ''),
    'deckAPI' => is_array($resolved) ? strval($resolved['apiURL'] ?? '') : '',
    'deckSource' => is_array($resolved) ? strval($resolved['source'] ?? 'api') : '',
    'leader' => $leader,
    'gate' => $gateID,
    'mainDeckCount' => is_array($deck) ? count($deck) : 0,
    'unresolved' => [],
  ];
  $summary[] = $playerSummary;
  return $playerSummary;
}

function BridgeRunRootSelfplayStartup($root) {
  if ($root === 'AzukiSim') {
    SetFlashMessage('');
    $currentPhase = &GetCurrentPhase();
    $currentPhase = 'SOT';
    SetPhaseParameters("-");

    for ($p = 1; $p <= 2; ++$p) {
      DrawOpeningHand($p);
    }
    QueueOpeningMulligans();

    GainIKZ(1, 1);
    DecisionQueueController::StoreVariable('P2_StartingIKZTokenPending', '1');

    AdvanceAndExecute("PASS");
    AutoAdvanceAndExecute();
    return;
  }

  $currentPhase = &GetCurrentPhase();
  $currentPhase = 'WU';
  SetPhaseParameters("-");
  QueuePregameStartingChampionSetup();
  AdvanceAndExecute("PASS");
  AutoAdvanceAndExecute();
  SaveUndoVersion(GetFirstPlayer(), "Pregame Starting Champion");
}

function BridgeStartSelfplayGame($root, $gameName, $seed, $deckTextP1, $deckTextP2, $memoryOnly = 'auto') {
  $gameName = trim(strval($gameName));
  if ($gameName === '') BridgeFail('gameName is required for start-selfplay-game.');

  $deckTextP1 = strval($deckTextP1);
  $deckTextP2 = strval($deckTextP2);
  if (trim($deckTextP1) === '') BridgeFail('deckTextP1 is required for start-selfplay-game.');
  if (trim($deckTextP2) === '') $deckTextP2 = $deckTextP1;

  EngineLoadRootRuntime($root);
  if ($root === 'AzukiSim') {
    BridgeLoadAzukiCreateGameHelpers();
  }
  $GLOBALS['gameName'] = $gameName;
  if (function_exists('RegressionClearGamestateMemory')) {
    RegressionClearGamestateMemory($gameName);
  }

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
  if (!isset($GLOBALS['bridgeMemoryOnlyGames']) || !is_array($GLOBALS['bridgeMemoryOnlyGames'])) {
    $GLOBALS['bridgeMemoryOnlyGames'] = [];
  }
  if ($memoryOnlyResolved) {
    $GLOBALS['bridgeMemoryOnlyGames'][$gameName] = true;
  } else {
    unset($GLOBALS['bridgeMemoryOnlyGames'][$gameName]);
  }

  InitializeGamestate();
  SetDeterministicRandomCounter(intval($seed));
  WriteGamestate('./' . $root . '/');
  ParseGamestate('./' . $root . '/');
  SetDeterministicRandomCounter(intval($seed));

  $deckSummary = [];
  $previousDeterministicShuffle = $GLOBALS['bridgeDeterministicDeckShuffle'] ?? null;
  $previousDeterministicShuffleSeed = $GLOBALS['bridgeDeterministicDeckShuffleSeed'] ?? null;
  $GLOBALS['bridgeDeterministicDeckShuffle'] = true;
  $GLOBALS['bridgeDeterministicDeckShuffleSeed'] = intval($seed);
  try {
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
  } finally {
    if ($previousDeterministicShuffle === null) {
      unset($GLOBALS['bridgeDeterministicDeckShuffle']);
    } else {
      $GLOBALS['bridgeDeterministicDeckShuffle'] = $previousDeterministicShuffle;
    }
    if ($previousDeterministicShuffleSeed === null) {
      unset($GLOBALS['bridgeDeterministicDeckShuffleSeed']);
    } else {
      $GLOBALS['bridgeDeterministicDeckShuffleSeed'] = $previousDeterministicShuffleSeed;
    }
  }

  $firstPlayer = &GetFirstPlayer();
  $firstPlayer = 1;
  $turnPlayer = &GetTurnPlayer();
  $turnPlayer = $firstPlayer;
  $currentTurn = &GetTurnNumber();
  $currentTurn = 1;

  BridgeRunRootSelfplayStartup($root);

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

if (defined('TCGENGINE_BRIDGE_LIBRARY_ONLY') && TCGENGINE_BRIDGE_LIBRARY_ONLY) {
  return;
}

$args = BridgeParseArgs($argv);
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
