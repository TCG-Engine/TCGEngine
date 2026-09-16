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
      if ($cached !== false && function_exists('SimGameNormalizeCacheRecord')) {
        $cached = SimGameNormalizeCacheRecord($cached)['gamestate'];
      }
      $hasMemoryGamestate = (is_string($cached) && $cached !== '');
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
  if (!function_exists('SimGameWriteGamestateCache')) return;

  $gameStatePath = $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt';
  if (!is_file($gameStatePath)) return;

  $gamestateText = file_get_contents($gameStatePath);
  if ($gamestateText === false || $gamestateText === '') return;
  SimGameWriteGamestateCache($root, $gameName, $gamestateText);
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
    case 'SWUSim':
      // SWU is a two-arena game: ground and space are separate attack/target spaces and must
      // never be conflated. Resources and Discard are here because SWU has real play-from-zone
      // permissions (Plot, and per-card discard plays), not just play-from-hand.
      return ['myHand', 'myGroundArena', 'mySpaceArena', 'myResources', 'myLeader', 'myBase', 'myDiscard'];
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

// Bound on how many TOPDECKSEARCH pick-combinations are enumerated. A ten-card peek with an
// "any number" constraint has 1,023 subsets; the bot only needs a legal, representative set, and an
// unbounded list would dominate every other candidate in the action space.
function BridgeTopDeckSearchActionCap() { return 40; }

// Bound on how many card NAMES a NAMECARD decision offers. Same reasoning as the search cap: a SWU
// deck holds 50 cards, so an uncapped list would swamp every other candidate in the action space.
function BridgeNameCardActionCap() { return 40; }

// Distinct card TITLES in ONE SEAT'S OWN hand, discard and deck — sorted, then capped at $limit.
//
// NAMECARD's Param carries no candidate pool (see the enumerator's case), so the candidate list has to
// be synthesised. This is the set a real player unambiguously knows — their own decklist — and it is
// the strategically relevant one for the denial cards that raise the decision.
//
// ⚠ THE SEAT IS THE WHOLE POINT. Reading any zone but $player's would hand the bot information the
// player does not have — a cheating bot, which is a worse failure than the stall this replaced and an
// invisible one, since a peeked title validates and resolves exactly like a legitimate one. The
// accessors are seat-indexed rather than perspective-relative for precisely that reason; there is no
// `my`/`their` framing here to get wrong.
//
// ⚠ SORTED BEFORE CAPPING, deliberately. Collected in zone order the list is in DECK ORDER, which the
// player has not seen — so once the cap binds, *which* titles get offered would depend on hidden
// information. You know your decklist; you do not know how it is shuffled. Sorting makes the offered
// set a pure function of the decklist, and deterministic across runs.
//
// Titles keep their SPACES: the answer is compared with CardTitle()/SWUObjectTitle() downstream and
// each consumer underscores the name itself when it needs to stash it in a space-delimited flag.
// Casing is CardTitle()'s exactly, because those comparisons are case-sensitive.
//
// SWUSim-only by construction: it needs CardTitle() plus the SWU hand/discard/deck accessors, and is
// only ever called from `case 'NAMECARD':`, which no other root that loads this file can reach.
function BridgeSWUOwnCardTitles($player, $limit) {
  if (!function_exists('CardTitle')) return [];
  $titles = [];
  foreach (['GetHand', 'GetDiscard', 'GetDeck'] as $accessor) {
    if (!function_exists($accessor)) continue;
    // The generated accessors return the raw zone global by reference, which is NULL until a gamestate
    // has been parsed — so guard rather than assume a loaded game (this file is also loaded library-only).
    $zone = $accessor(intval($player));
    if (!is_array($zone)) continue;
    foreach ($zone as $card) {
      if (!is_object($card) || !empty($card->removed)) continue;
      $title = trim(strval(CardTitle(strval($card->CardID ?? '')) ?? ''));
      if ($title === '' || isset($titles[$title])) continue;
      $titles[$title] = true;
    }
  }
  $titles = array_keys($titles);
  sort($titles, SORT_STRING);
  return array_slice($titles, 0, max(0, intval($limit)));
}

// Bound on how many SCRY top/bottom arrangements are enumerated. Both live DoScry() callers peek 1
// (SOR_236 R2-D2) or 2 (SOR_031 Inferno Four) cards, which is 2 and 6 arrangements — verified by
// repo-wide grep, so this cap does NOT bind today and the enumeration is exhaustive as the contract
// requires. It exists so a future DoScry($n) with a larger $n degrades to a truncated-but-legal list
// instead of a factorial explosion: the space is n!*(n+1), so n=4 is 120 and n=5 would be 720.
function BridgeScryActionCap() { return 120; }

// Bound on how many TRAITS a NAMETRAIT decision offers. The candidate set is already narrow (the
// traits actually on enemy cards in play — a full board rarely shows more than a dozen of the 117
// printed traits), so like the NAMECARD cap this is a ceiling, not a working limit.
function BridgeNameTraitActionCap() { return 40; }

// Every permutation of the indices 0..$count-1, IDENTITY FIRST, stopping after $limit of them
// (0 = no limit). Used by the SCRY arm, whose answer space is "some ordering of the peeked cards,
// cut at some point into a top half and a bottom half".
//
// ⚠ $limit BOUNDS THE BUILD, not just the result. n is 1 or 2 at both live DoScry() callers, but this
// returns a materialised list of n! arrays, so truncating downstream would still have MATERIALISED
// 3.6M arrays for a hypothetical n=10 caller before the cap could bite. The caller passes the number
// of permutations its action cap can actually consume.
function BridgeIndexPermutations($count, $limit = 0) {
  if ($count <= 0) return [[]];
  $limit   = max(0, intval($limit));
  $results = [];
  $walk = function(array $prefix, array $remaining) use (&$walk, &$results, $limit) {
    if ($limit > 0 && count($results) >= $limit) return;
    if (!$remaining) { $results[] = $prefix; return; }
    foreach ($remaining as $position => $value) {
      $rest = $remaining;
      unset($rest[$position]);
      $walk(array_merge($prefix, [$value]), array_values($rest));
      if ($limit > 0 && count($results) >= $limit) return;
    }
  };
  $walk([], range(0, $count - 1));
  return $results;
}

// Distinct TRAITS carried by the ENEMY cards in play, from $player's seat — sorted, then capped.
//
// NAMETRAIT's Param is the empty string (HMW_108 The First Legion is the only emitter repo-wide and
// queues it with ''), so like NAMECARD the candidate list has to be synthesised. The trait UNIVERSE
// (SWUAllTraits(), 117 entries) is the validator's pool but a terrible action list: naming a trait
// nobody has is legal and does exactly nothing, so 100+ of those candidates are pure noise.
//
// ⚠ THE INFORMATION BOUNDARY HERE IS "IN PLAY", not "the seat's own zones" — the opposite framing to
// BridgeSWUOwnCardTitles(). HMW_108 strips the named trait from enemy cards *including those not in
// play*, so it is tempting to derive candidates from the enemy hand/deck/discard as well. That would
// be a CHEATING bot: a real player cannot see an opponent's hand or deck, and an answer sourced from
// one validates and resolves exactly like a legitimate one, so the cheat is invisible. Only the
// arenas, leader and base — the public board — are read.
//
// ⚠ ENEMY is by TEAM and by CONTROLLER, not by "the other seat". Twin Suns has 3-4 seats and Team
// Suns pairs them, and HMW_108's own read side (_SWUHmw108TraitSuppressed) spares a teammate through
// SWUTeamOf, so the candidate list has to agree with it or the bot names traits its own effect will
// not strip. A unit an enemy OWNS but you now CONTROL is your card and is skipped; the reverse is
// included. Objects are classified by ->Controller, defaulting to the seat whose zone holds them.
//
// TraitContains() is the object-aware chokepoint (per-instance NO_TRAIT_ markers, upgrade grants,
// deployed-leader trait overrides, and HMW_108's own suppression), so the set is what the board
// ACTUALLY shows rather than what the cards print. That is why this loops the universe against each
// object instead of reading $traitData: a granted trait (LOF_073's Mandalorian, SEC_156's Rebel)
// appears on no printed trait line.
//
// SWUSim-only by construction — it needs SWUAllTraits()/TraitContains() and is only ever called from
// `case 'NAMETRAIT':`, which no other root that loads this file can reach.
function BridgeSWUEnemyInPlayTraits($player, $limit) {
  if (!function_exists('SWUAllTraits') || !function_exists('TraitContains')) return [];
  $me    = intval($player);
  $seats = function_exists('GetLiveSeatsArray') ? GetLiveSeatsArray() : [1, 2];
  $team  = function_exists('SWUTeamOf') ? fn($seat) => SWUTeamOf(intval($seat)) : fn($seat) => intval($seat);
  $enemyObjects = [];
  foreach ($seats as $seat) {
    $seat = intval($seat);
    foreach (['GetGroundArena', 'GetSpaceArena', 'GetLeader', 'GetBase'] as $accessor) {
      if (!function_exists($accessor)) continue;
      // The generated accessors return the raw zone global by reference, which is NULL until a
      // gamestate has been parsed — so guard rather than assume a loaded game.
      $zone = $accessor($seat);
      if (!is_array($zone)) continue;
      foreach ($zone as $card) {
        if (!is_object($card) || !empty($card->removed)) continue;
        // ENEMY is by TEAM, resolved on the CONTROLLER rather than on whose zone holds the card.
        // One test covers both cases: SWUTeamOf() returns the seat itself outside a team game, so
        // this rejects your own cards (same seat) and, in Team Suns, your teammate's as well.
        if ($team(intval($card->Controller ?? $seat)) === $team($me)) continue;
        $enemyObjects[] = $card;
      }
    }
  }
  if (!$enemyObjects) return [];
  $traits = [];
  foreach (SWUAllTraits() as $trait) {
    foreach ($enemyObjects as $object) {
      if (TraitContains($object, $trait)) { $traits[] = $trait; break; }
    }
  }
  // Traits keep their SPACES ('Bounty Hunter', 'Capital Ship' — 14 of the 117 are multi-word), for
  // the same reason NAMECARD titles do: the answer travels in $lastDecision, and HMW_108's handler
  // runs the str_replace(' ','_') itself when it stashes the name in a space-delimited flag.
  sort($traits, SORT_STRING);
  return array_slice($traits, 0, max(0, intval($limit)));
}

// All ascending index combinations of $size drawn from 0..$count-1.
function BridgeIndexCombinations($count, $size) {
  $results = [];
  if ($size <= 0 || $size > $count) return $results;
  $indices = range(0, $size - 1);
  while (true) {
    $results[] = $indices;
    $position = $size - 1;
    while ($position >= 0 && $indices[$position] === $count - $size + $position) --$position;
    if ($position < 0) break;
    ++$indices[$position];
    for ($next = $position + 1; $next < $size; ++$next) $indices[$next] = $indices[$next - 1] + 1;
  }
  return $results;
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
        // Zero AddDecision emitters anywhere in the current tree (verified Phase 2a Task 6, not
        // just "untested here" — no root, including GrandArchiveSim/AzukiSim, actually raises this
        // type today). It survives only as a generic decision-type primitive the schema generator
        // and a couple of goldfish/bot resolvers know how to answer if something ever emits it.
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
        // Zero AddDecision emitters in SWUSim (verified Phase 2a Task 6). Live emitter is AzukiSim
        // only — GrandArchiveSim's Custom/GameLogic.php has a generic dispatch/goldfish-resolve arm
        // for this type but no actual AddDecision call raising it, so it does not emit it either.
        $choices = array_values(array_filter(explode('&', strval($decision->Param ?? '')), fn($value) => $value !== ''));
        foreach ($choices as $choice) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $choice, 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'TOPDECKSEARCH':
        // SWUSim's peek-and-take picker (SOR_123 Recruit, SOR_042 Search Your Feelings, SOR_104
        // U-Wing Reinforcement, …). Param is "allIDs|matchingIDs|constraint|costMap" and the answer
        // is a COMMA-separated list of chosen CardIDs drawn from the peeked cards — see
        // SWUSim/Custom/GameLogic.php's _topDeckSearchBegin() ("Finalize answer format:
        // comma-separated chosen CardIDs (empty = choose none)") and _topDeckResolveFromIDs().
        //
        // Constraint forms: "count:N" (at most N picks), "cost:N" (any number, combined cost <= N),
        // "cost:N:M" (at most M picks AND combined cost <= N). The engine ENFORCES all of these
        // server-side and silently drops overflow picks, so an over-budget answer would be a partial
        // no-op rather than an error — enumerate only answers that satisfy the constraint, so the
        // action the bot picks is the action that happens.
        //
        // Found by DevTools/SWUSimBotSelfPlayTest.php: with no case here every deck-search card
        // stalled the game outright.
        {
          $parts = explode('|', strval($decision->Param ?? ''));
          $matchIDs = array_values(array_filter(explode(',', strval($parts[1] ?? '')), fn($v) => $v !== ''));
          $constraint = strval($parts[2] ?? '');
          $costs = [];
          foreach (explode(',', strval($parts[3] ?? '')) as $pair) {
            $bits = explode(':', $pair);
            if (count($bits) === 2 && $bits[0] !== '') $costs[$bits[0]] = intval($bits[1]);
          }
          $maxPicks = count($matchIDs);
          $maxCost = null;
          if (str_starts_with($constraint, 'count:')) {
            $maxPicks = max(0, intval(substr($constraint, 6)));
          } else if (str_starts_with($constraint, 'cost:')) {
            $costBits = explode(':', $constraint);
            $maxCost = intval($costBits[1] ?? 0);
            if (isset($costBits[2]) && $costBits[2] !== '') $maxPicks = max(0, intval($costBits[2]));
          }
          $maxPicks = min($maxPicks, count($matchIDs));

          // Subsets by INDEX, because the peeked cards can legitimately contain duplicates and the
          // resolver de-duplicates positionally. Bounded by BridgeTopDeckSearchActionCap() so a wide
          // search (10 peeked, "any number") cannot produce a combinatorial action list.
          $emitted = 0;
          $cap = BridgeTopDeckSearchActionCap();
          for ($size = 1; $size <= $maxPicks && $emitted < $cap; ++$size) {
            foreach (BridgeIndexCombinations(count($matchIDs), $size) as $combo) {
              if ($emitted >= $cap) break;
              $picked = [];
              $total = 0;
              foreach ($combo as $index) {
                $picked[] = $matchIDs[$index];
                $total += intval($costs[$matchIDs[$index]] ?? 0);
              }
              if ($maxCost !== null && $total > $maxCost) continue;
              $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '',
                            'cardID' => implode(',', $picked), 'chkInput' => [], 'inputText' => ''];
              ++$emitted;
            }
          }
          // Taking nothing is always legal (the validator accepts '' for every non-MZCHOOSE type),
          // and it is the answer of last resort — listed last so a chooser that scans in order only
          // reaches it when no real pick is available.
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => '', 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'OPTIONCHOOSE':
        // SWUSim's fixed-label picker: Param is an '&'-joined label list ("You&Opponent",
        // "Ready&Exhaust", "P2&P3", "@-&Your_deck&P2_deck") and the answer is ONE of those labels
        // verbatim — see SWUSim/Custom/GameLogic.php's SWUValidateDecisionAnswer(), whose
        // OPTIONCHOOSE arm is exactly `in_array($answer, $labels, true)`. The labels are the whole
        // candidate set, so no expansion or de-duplication applies: an underscore inside a label
        // ("Your_deck") is TRANSPORT, not display text, and must be submitted as-is.
        //
        // Found by DevTools/SWUSimBotSelfPlayTest.php: with no case here the decision fell through
        // to `default`, the enumerator returned zero actions, SWUBotLegalActions recorded an
        // unrecognized-decision gap, and the game stalled the moment any card asked "choose a
        // player" (SOR_167 Force Throw was the first). GA/Azuki never produce this type, which is
        // why the bridge had no case for it.
        //
        // ⚠ A segment beginning with '@' is a UI DIRECTIVE, NOT AN OPTION. "@{$topID}" renders that
        // card's art above the buttons and "@-" renders a placeholder; Core/OptionChooseUI.js splits
        // the Param exactly this way and never offers an '@' segment as a button. The Param always
        // leads with it when present ("@SOR_246&Play&Leave", "@-&Your_deck&P2_deck").
        //
        // It must be filtered HERE because SWUValidateDecisionAnswer's OPTIONCHOOSE arm is
        // `in_array($answer, $labels, true)` against the UN-stripped list, so it ACCEPTS the
        // directive — the answer passes validation and then no downstream handler has a matching
        // label. With 'first-legal' the bot picks $actions[0], which is precisely that token, on
        // every one of the 9+ producers that use the prefix (SOR_246 You're My Only Hope,
        // SOR_051 Ezra Bridger, SOR_147 C-3PO, SOR_236 Reinforcement Walker, LAW AllianceOutpost,
        // LAW Watchful, TS26 Ahsoka, and SWUSim/Custom/GameLogic.php's multi-Action base picker).
        foreach (array_map('trim', explode('&', strval($decision->Param ?? ''))) as $label) {
          if ($label === '') continue;
          if ($label[0] === '@') continue;   // UI directive (card art), never a legal answer
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $label, 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'NAMECARD':
        // SWUSim's "Name a card" free-text picker (SOR_062 Regional Governor, SOR_185 Chimaera,
        // ASH_077 Ryder Azadi, LAW_243 Transmission Jamming, LOF_204 Zuckuss, SEC_046 Galen Erso,
        // SEC_186 Garindan, SEC_210 Stolen Starpath Unit, SEC_260 Inspector's Shuttle, plus the
        // Foresight regroup grant). The answer is a card TITLE — the consumers all resolve it with
        // `SWUObjectTitle($c) === $named` / `CardTitle($topCid) === $named`.
        //
        // ⚠ SPACES STAY. This is the one decision type where the repo's "underscores are transport"
        // rule does NOT apply to the answer: the name travels in $lastDecision, not in a
        // space-delimited Param, and the consumers that do need it in a flag
        // (SWU_NAMEBLOCK / SWU_NAMEBLOCK_PHASE / SWU_GALEN) run the str_replace(' ','_') themselves on
        // arrival. Pre-underscoring here would pass validation-by-accident nowhere and whiff everywhere
        // — SWUValidateDecisionAnswer's NAMECARD arm now refuses the underscored form outright.
        //
        // ⚠ THE PARAM IS EMPTY at all ten emitters, so unlike every other case here there is nothing to
        // enumerate. The candidate list is synthesised from the deciding seat's OWN hand/discard/deck —
        // information a real player has, bounded, always legal, and the set that matters for the denial
        // cards (ASH_077, LAW_243) that raise the decision most often.
        //
        // Found by DevTools/SWUSimBotSelfPlayTest.php: with no case here NAMECARD fell through to
        // `default`, the enumerator returned zero actions, SWUBotLegalActions recorded a gap, and the
        // game STALLED — 20 of 24 modern-Premier games died on it. GA also emits NAMECARD (with a
        // non-empty Param, unlike SWUSim's ten emitters) but no GA source file requires this bridge —
        // the ONLY path that loads it under root=GrandArchiveSim is DevTools/rl/train_selfplay_php.php,
        // and there this arm is inert rather than absent: BridgeSWUOwnCardTitles() opens with
        // `if (!function_exists('CardTitle')) return [];`, GA has no CardTitle(), so it yields only the
        // decline. That is an accident of a missing function, not a guarantee — do not rely on it for
        // the next arm added here. AzukiSim, which does load this file at runtime, never queues the type.
        {
          foreach (BridgeSWUOwnCardTitles($player, BridgeNameCardActionCap()) as $title) {
            $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '',
                          'cardID' => $title, 'chkInput' => [], 'inputText' => ''];
          }
          // Declining is genuinely legal — every consumer opens with
          // `if (SWUDecisionDeclined($lastDecision)) return;` — and '-' is the token this decision's
          // validator accepts ('NO' is the YESNO button's literal and is refused). Listed LAST so the
          // bot's 'first-legal' chooser only reaches it when the seat has no card to name at all, which
          // also makes it the guarantee that this case never returns zero actions (zero = the stall).
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '',
                        'cardID' => '-', 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'NAMETRAIT':
        // SWUSim's "Name a Trait" free-text picker. ONE emitter repo-wide: HMW_108 The First Legion
        // ("On Attack: Name a Trait. Enemy cards, including those not in play, lose that Trait for
        // this phase"), which is in the HMW preview set — so this arm is dormant in Premier and goes
        // live the moment HMW rotates in. The answer is the trait STRING, and HMW_108's own handler
        // resolves it with strcasecmp against SWUAllTraits().
        //
        // ⚠ SPACES STAY, exactly as in NAMECARD: 14 of the 117 printed traits are multi-word
        // ('Bounty Hunter', 'Capital Ship'), the answer travels in $lastDecision rather than in a
        // space-delimited Param, and the handler does its own str_replace(' ','_') on arrival when it
        // arms the SWU_HMW108 flag. Pre-underscoring here would be refused by the validator and would
        // match nothing downstream even if it were not.
        //
        // ⚠ THE PARAM IS EMPTY, so there is nothing to enumerate — see BridgeSWUEnemyInPlayTraits()
        // for where the candidate list comes from and why it is the PUBLIC BOARD rather than the full
        // trait universe or the enemy's hidden zones.
        //
        // Found by DevTools/SWUSimBotSelfPlayTest.php: with no case here NAMETRAIT fell through to
        // `default`, the enumerator returned zero actions, SWUBotLegalActions recorded a gap and the
        // game STALLED — 3 of 12 games in the discovery report's NAMETRAIT isolation probe. No other
        // root that loads this file queues the type.
        {
          foreach (BridgeSWUEnemyInPlayTraits($player, BridgeNameTraitActionCap()) as $trait) {
            $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '',
                          'cardID' => $trait, 'chkInput' => [], 'inputText' => ''];
          }
          // Declining is legal at the transport level (NAMETRAIT is not MZCHOOSE, so the validator's
          // decline gate passes '-' through, and HMW_108#0 opens by returning on '-'/''/'PASS').
          // Listed LAST so the 'first-legal' chooser only reaches it when the enemy board is empty —
          // which is also the guarantee that this case never returns zero actions, i.e. never stalls.
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '',
                        'cardID' => '-', 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'SCRY':
        // SWUSim's look-at-top-N picker. Param is the comma-joined list of PEEKED CardIDs, topmost
        // first, and the answer is "topIDs|bottomIDs" — each half a comma-list drawn from that set,
        // top listed topmost-first (SCRY_FINALIZE, SWUSim/Custom/GameLogic.php). The answer space is
        // therefore "some ordering of the peeked cards, cut into a top run and a bottom run":
        // n!*(n+1) arrangements.
        //
        // EXHAUSTIVE, and correctly so: DoScry() has exactly two callers repo-wide — SOR_236 R2-D2
        // (n=1, 2 answers) and SOR_031 Inferno Four (n=2, 6 answers) — verified by grep, not assumed.
        // BridgeScryActionCap() is a ceiling for a hypothetical third caller, not a working limit.
        //
        // ⚠ SCRY_FINALIZE IS DELIBERATELY FORGIVING: "any peeked card the answer fails to account for
        // goes back on top rather than vanishing". So a malformed answer is a SILENT NO-OP, not an
        // error — which is exactly why an unverified encoder here would be worse than the stall it
        // replaces, and why every answer this arm emits is round-tripped through
        // SWUValidateDecisionAnswer's SCRY arm in DevTools/tdd-regression/test_swusim_decision_validators.php.
        //
        // Found by DevTools/SWUSimBotSelfPlayTest.php: 22 of 24 probe-deck games stalled here.
        {
          $peeked = array_values(array_filter(explode(',', strval($decision->Param ?? '')), fn($v) => $v !== ''));
          $count  = count($peeked);
          if ($count === 0) {
            // Unreachable in practice (DoScry returns early on an empty deck) but a case that can
            // return zero actions is a stall, so keep the floor: '' is the forgiving all-back-on-top.
            $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => '', 'chkInput' => [], 'inputText' => ''];
            break;
          }
          // Keyed by the answer STRING, which also de-duplicates: when the same CardID is peeked
          // twice, distinct index arrangements collapse to the same answer and the engine's
          // multiplicity check treats them as interchangeable.
          $answers = [];
          $addAnswer = function(array $topIdx, array $bottomIdx) use (&$answers, $peeked) {
            $answers[implode(',', array_map(fn($i) => $peeked[$i], $topIdx))
                     . '|' . implode(',', array_map(fn($i) => $peeked[$i], $bottomIdx))] = true;
          };
          // The two EXTREMES first, in the peeked order, so they survive any truncation: keep the
          // whole peek on top (the no-change answer) and bury the whole peek.
          $all = range(0, $count - 1);
          $addAnswer($all, []);
          $addAnswer([], $all);
          // Each permutation yields ($count + 1) answers (one per cut point), so this is the most
          // permutations the cap can consume — see BridgeIndexPermutations' note on why the LIMIT has
          // to reach the generator rather than be applied to its result.
          $cap = BridgeScryActionCap();
          foreach (BridgeIndexPermutations($count, intdiv($cap, $count + 1) + 1) as $perm) {
            for ($split = 0; $split <= $count; ++$split) {
              if (count($answers) >= $cap) break 2;
              $addAnswer(array_slice($perm, 0, $split), array_slice($perm, $split));
            }
          }
          foreach (array_keys($answers) as $answer) {
            $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $answer, 'chkInput' => [], 'inputText' => ''];
          }
        }
        break;
      case 'REVEALARRANGE':
        // SWUSim's reveal-N / discard-any / reorder-the-rest picker. ONE emitter repo-wide: SOR_152
        // For a Cause I Believe In. Param is the comma-joined list of revealed CardIDs and the answer
        // is "keptIDs|discardIDs" (REVEALARRANGE_FINALIZE, SWUSim/Custom/GameLogic.php) — kept go back
        // on top with the FIRST LISTED ENDING UP ON TOP, discarded go to the discard pile From='DECK'.
        //
        // ⚠ THE GRAMMAR RESEMBLES SCRY'S BUT THE SECOND HALF MEANS SOMETHING ELSE. "top|bottom" there,
        // "kept|DISCARDED" here: an answer copied from the SCRY arm would mill the cards it meant to
        // bury. Read the finalizer, not the sibling case.
        //
        // ⚠ Same forgiving tail as SCRY — an unaccounted revealed card goes back on top — so a
        // malformed answer is a silent no-op. That is what SWUValidateDecisionAnswer's REVEALARRANGE
        // arm (added alongside this case) exists to refuse.
        //
        // BOUNDED BY SUBSET, NOT BY PERMUTATION. The kept half's order is a real choice, but with 4
        // revealed the ordered space is Σ C(4,k)·k! = 65 and a cap-truncated slice of a factorial list
        // would make *which* orders are offered an artifact of the enumeration order. So this
        // enumerates the 2^n keep/discard SPLITS with the kept half left in the revealed order, capped
        // by BridgeTopDeckSearchActionCap() (40) — a different, smaller cap than SCRY's 120; the two
        // are not interchangeable and neither is the 65 above equal to either cap. Every answer it
        // emits is legal and distinct; re-ordering the kept half is deliberately left to a later
        // chooser that can actually evaluate one order against another.
        //
        // Found by DevTools/SWUSimBotSelfPlayTest.php: 2 of 24 probe-deck games stalled here.
        {
          $revealed = array_values(array_filter(explode(',', strval($decision->Param ?? '')), fn($v) => $v !== ''));
          $count    = count($revealed);
          if ($count === 0) {
            $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => '', 'chkInput' => [], 'inputText' => ''];
            break;
          }
          $answers = [];
          $addAnswer = function(array $kept, array $discarded) use (&$answers) {
            // Keyed by string, so duplicate revealed CardIDs collapse to one answer.
            $answers[implode(',', $kept) . '|' . implode(',', $discarded)] = true;
          };
          // Bit i set = revealed[i] is DISCARDED.
          $cap   = BridgeTopDeckSearchActionCap();
          $total = 1 << min($count, 20);   // guard the shift; n is 4 at the only emitter
          // The two EXTREMES first, exactly like SCRY, so each survives any truncation: mask 0 (keep
          // everything, order unchanged) is the no-op answer, and mask (total-1) (discard everything)
          // is its counterpart. Before this fix the loop below walked masks ascending from 0, so
          // discard-all was always the LAST mask generated — inert at SOR_152's own n=4 (16 < the cap
          // of 40) but at n>=6 the cap binds before reaching it, silently dropping discard-all along
          // with every other high-discard answer. That is exactly the failure SCRY's two-extremes-first
          // ordering exists to prevent.
          $order = [0];
          if ($total > 1) $order[] = $total - 1;
          for ($m = 1; $m < $total - 1; ++$m) $order[] = $m;
          foreach ($order as $mask) {
            if (count($answers) >= $cap) break;
            $kept = $discarded = [];
            for ($i = 0; $i < $count; ++$i) {
              if ($mask & (1 << $i)) $discarded[] = $revealed[$i];
              else                   $kept[] = $revealed[$i];
            }
            $addAnswer($kept, $discarded);
          }
          foreach (array_keys($answers) as $answer) {
            $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $answer, 'chkInput' => [], 'inputText' => ''];
          }
        }
        break;
      case 'MZREARRANGE':
        // Zero AddDecision emitters in SWUSim (verified Phase 2a Task 6). Live emitters: GrandArchiveSim
        // (Custom/GameLogic.php + Custom/CardDQHandlers.php), AzukiSim (Custom/GameLogic.php), AND
        // HellbreakSim (Custom/CombatLogic.php) — three roots, not just the two GA/Azuki usually cited.
        foreach (BridgeEnumerateRearrangeResults(strval($decision->Param ?? '')) as $resultStr) {
          $actions[] = ['playerID' => $player, 'mode' => 100, 'buttonInput' => '', 'cardID' => $resultStr, 'chkInput' => [], 'inputText' => ''];
        }
        break;
      case 'MZMODAL':
        // Zero AddDecision emitters in SWUSim (verified Phase 2a Task 6). Live emitters: GrandArchiveSim,
        // AzukiSim, HellbreakSim, AND FaBSim — four roots use this type, not just GA/Azuki.
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
  // Verified against the real client: SWUSim's Pass button submits 'myHealth-0!CustomInput!Pass'
  // (see SWUSim/Custom/GameLayoutShared.php window.swuPassAction, dispatched to CustomWidgetInput's
  // "myHealth" case in SWUSim/Custom/CustomInput.php). Same mzID as the GA default, different verb case.
  if ($root === 'AzukiSim')      $cardID = 'myLeaderHealthSlot!CustomInput!Pass';
  else if ($root === 'SWUSim')   $cardID = 'myHealth-0!CustomInput!Pass';
  else                           $cardID = 'myHealth-0!CustomInput!PASS';
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

  // Answers are INDICES, matching what Core/MZModalUI.js submits (`indices.join(',')`). Labels look
  // friendlier but handlers read the answer as an index (HellbreakSim validates `^\d+$`), so a
  // label is silently rejected and the prompt is consumed with nothing done.
  $results = [];
  $n = count($labels);
  for ($k = $min; $k <= min($max, $n); ++$k) {
    if ($k <= 0) {
      $results[] = '-';
      continue;
    }
    if ($k === 1) {
      // Single-select: offer every option. Sampling first/last hides the middle ones, and those are
      // real choices (a Hellbreak horror prompt lists Play Card, Attack, Scheme, Ability, Pass...).
      for ($i = 0; $i < $n; ++$i) $results[] = (string)$i;
      continue;
    }
    // Multi-select: keep the deterministic first-k / last-k sample; every combination explodes.
    $results[] = implode(',', range(0, $k - 1));
    $results[] = implode(',', range($n - $k, $n - 1));
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

function BridgeIsStaticDecision($decision) {
  if ($decision === null || !is_object($decision)) return false;
  return in_array(strtoupper(strval($decision->Type ?? '')), ['PASSPARAMETER', 'MZMOVE', 'CUSTOM', 'SYSTEM'], true);
}

/**
 * An action can enqueue static work for the other player while the acting
 * player's queue is already executing. DecisionQueueController intentionally
 * refuses same-player recursion, and no caller automatically enters that other
 * queue. Drain those cross-player continuations before self-play asks for its
 * next interactive action.
 */
function BridgeDrainStaticDecisionQueuesLoaded($maxPasses = 32) {
  $dqController = new DecisionQueueController();
  $drainedPasses = 0;
  for ($pass = 0; $pass < max(1, intval($maxPasses)); ++$pass) {
    $progressed = false;
    for ($player = 1; $player <= 2; ++$player) {
      $decision = $dqController->NextDecision($player);
      if (!BridgeIsStaticDecision($decision)) continue;
      $dqController->ExecuteStaticMethods($player, '-');
      ++$drainedPasses;
      $progressed = true;
    }
    if (!$progressed) return $drainedPasses;
  }
  throw new RuntimeException('Static decision queues did not settle after ' . intval($maxPasses) . ' bridge drain passes.');
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
    $drainedStaticPasses = !empty($result['success']) ? BridgeDrainStaticDecisionQueuesLoaded() : 0;
    if ($drainedStaticPasses > 0 && function_exists('WriteGamestate')) WriteGamestate('./' . $root . '/');
    $result['staticDecisionDrainPasses'] = $drainedStaticPasses;
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
    $drainedStaticPasses = !empty($result['success']) ? BridgeDrainStaticDecisionQueuesLoaded() : 0;
    if ($drainedStaticPasses > 0 && function_exists('WriteGamestate')) WriteGamestate('./' . $root . '/');
    $result['staticDecisionDrainPasses'] = $drainedStaticPasses;
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

// GA has a champion, Azuki has a leader. SWU seats carry TWO persistent objects — a leader and a
// base — and the base is the loss condition, so a single-avatar summary loses the thing that
// decides the game.
function BridgeSWUAvatarSummary($playerID) {
  $summary = ['leader' => null, 'base' => null];
  if (function_exists('GetLeader')) {
    $leaders = GetLeader(intval($playerID));
    if (is_array($leaders) && isset($leaders[0]) && $leaders[0] !== null) {
      $summary['leader'] = [
        'cardID' => strval($leaders[0]->CardID ?? ''),
        'damage' => intval($leaders[0]->Damage ?? 0),
      ];
    }
  }
  if (function_exists('GetBase')) {
    $bases = GetBase(intval($playerID));
    if (is_array($bases) && isset($bases[0]) && $bases[0] !== null) {
      $summary['base'] = [
        'cardID' => strval($bases[0]->CardID ?? ''),
        'damage' => intval($bases[0]->Damage ?? 0),
      ];
    }
  }
  return $summary;
}

function BridgePrimaryAvatarSummary($root, $playerID) {
  if ($root === 'SWUSim') return BridgeSWUAvatarSummary($playerID);
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
  $isTerminal = $winner >= 1 && $winner <= 4;   // seats 3-4 exist in multiplayer formats (Twin Suns)
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
