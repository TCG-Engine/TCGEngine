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
  $opportunityState = BridgeGetOpportunityState();
  if ($phase !== 'MAIN') {
    return [
      'success' => true,
      'kind' => 'phase-unsupported',
      'phase' => $phase,
      'opportunityState' => $opportunityState,
      'actions' => [],
    ];
  }

  $kind = 'main-phase-hand-play';
  if ($opportunityState['effectStackCount'] > 0) {
    $kind = 'effect-stack-response';
  } else if ($opportunityState['pendingOpportunityHandler'] !== '') {
    $kind = 'opportunity-window-hand-play';
  }

  $handActions = BridgeEnumerateHandPlayActions($turnPlayer);
  $passAction = ['playerID' => $turnPlayer, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''];

  return [
    'success' => true,
    'kind' => $kind,
    'playerID' => $turnPlayer,
    'phase' => $phase,
    'opportunityState' => $opportunityState,
    'actions' => array_merge($handActions, [$passAction]),
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
    'activePlayer' => BridgeActivePlayer(),
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
    $payload['players'] = [
      'player1' => [
        'mastery' => BridgeMasterySummary(1),
        'decisionQueue' => BridgeDecisionQueueSummary(1),
      ],
      'player2' => [
        'mastery' => BridgeMasterySummary(2),
        'decisionQueue' => BridgeDecisionQueueSummary(2),
      ],
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
  case 'add-to-zone':
    BridgeOut(BridgeAddToZone(
      $root,
      $args['gameName'] ?? '',
      strval($args['zone'] ?? ''),
      strval($args['cardID'] ?? ''),
      intval($args['perspectivePlayer'] ?? 1)
    ));
    break;
  case 'add-counters':
    BridgeOut(BridgeAddCounters(
      $root,
      $args['gameName'] ?? '',
      strval($args['mzID'] ?? ''),
      strval($args['counterType'] ?? ''),
      intval($args['amount'] ?? 0),
      intval($args['perspectivePlayer'] ?? 1)
    ));
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