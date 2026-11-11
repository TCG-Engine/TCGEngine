<?php
// Training-only adapter. No live bot or public API changes.
function GaRlLegalActions($gameName, $learnerSeat) {
  global $playerID;
  $seat = GABotPendingDecisionPlayer() ?: intval(GetTurnPlayer());
  $playerID = $seat;
  $legal = GABotLegalActions($gameName, $seat);
  return GaRlExpandActions($legal);
}

function GaRlStateKey($seat, $legal) {
  // Explicit public projection: never serialize the bridge's full snapshot, deck order,
  // opposing hand, memory identities, or face-down cards into the learner's state.
  $board = [];
  foreach ([$seat, 3 - $seat] as $p) {
    $units = [];
    foreach (GetField($p) as $obj) {
      if ($obj === null || !empty($obj->removed)) continue;
      $units[] = [$obj->CardID, intval($obj->Status ?? 0), GABotRemainingHP($obj), intval(ObjectCurrentPower($obj))];
    }
    sort($units);
    $board[] = $units;
  }
  $hand = [];
  foreach (GetHand($seat) as $obj) if ($obj !== null && empty($obj->removed)) $hand[] = $obj->CardID;
  sort($hand);
  $knowledge = GABotDeckKnowledge($seat);
  $memory = [];
  foreach (GetMemory($seat) as $obj) if ($obj !== null && empty($obj->removed)) $memory[] = $obj->CardID;
  sort($memory);
  $stack = array_map(fn($obj) => [$obj->CardID, $obj->Controller], GetLiveEffectStackEntries());
  return json_encode([$knowledge, $memory, $stack, GetCurrentPhase(), $seat === intval(GetTurnPlayer()), $legal['decisionType'] ?? '',
    $legal['handler'] ?? '', $board, $hand, GABotLiveCount(GetMemory($seat)),
    GABotLiveCount(GetMemory(3 - $seat)), GABotLiveCount(GetHand(3 - $seat))]);
}

function GaRlActionKey($action, $legal) {
  $raw = strval($action['cardID'] ?? '');
  // Replace zone positions with stable identities only in zones visible to this player.
  $key = preg_replace_callback('/(myHand|myField|theirField|myMaterial|myMemory|myDiscard|theirDiscard)-(\d+)/', function($m) {
    $obj = GetZoneObject($m[0]);
    if ($obj === null || !empty($obj->removed)) return $m[1] . ':missing';
    return $m[1] . ':' . $obj->CardID . ':' . intval($obj->Status ?? 0) . ':' . intval($obj->Damage ?? 0);
  }, $raw);
  return json_encode([$legal['decisionType'] ?? 'main', intval($action['mode'] ?? 0), $key,
    $action['buttonInput'] ?? '', $action['inputText'] ?? '', $action['chkInput'] ?? []]);
}

// Review-only metadata; deliberately separate from observation and checkpoint keys.
// Only public graveyards are resolved, even if a malformed action names hidden zones.
function GaRlPublicGraveyardChoices($actions) {
  $cards = [];
  foreach ($actions as $action) {
    preg_match_all('/(?:my|their)Graveyard-\d+/', strval($action['cardID'] ?? ''), $matches);
    foreach ($matches[0] as $ref) {
      $obj = GetZoneObject($ref);
      if ($obj !== null && empty($obj->removed)) $cards[$ref] = $obj->CardID;
    }
  }
  return $cards;
}

function GaRlResumeStack() {
  if (GABotPendingDecisionPlayer() === 0 && !empty(GetLiveEffectStackEntries())) {
    $before = GABotIdleEffectStackSignature();
    if (!ResumeIdleEffectStackIfNeeded() || $before === GABotIdleEffectStackSignature()) {
      throw new RuntimeException('idle_effect_stack_no_progress');
    }
    WriteGamestate(RegressionRepoRoot() . '/GrandArchiveSim/');
  }
}

// Count attempted learner decisions, including rejected attempts, without touching RNG or policy.
function GaRlRecordPolicyCoverage(&$counts, $policy, $stateKey, $legalIndices, $actionKeys) {
  ++$counts['decisions'];
  if (count($legalIndices) < 2) return;
  ++$counts['choices'];
  if (!empty($policy->logits[$stateKey]) || !empty($policy->logits['__linear__'])) ++$counts['knownStateChoices'];
  $values = [];
  foreach ($legalIndices as $idx) {
    $key = $policy->storageKeyForAction($idx, $actionKeys);
    $values[] = $policy->actionValue($stateKey, $key);
  }
  if (max($values) - min($values) > 1.0e-12) ++$counts['learnedPreferenceChoices'];
}

// Coarse public situations let different hands and board identities share experience.
// Action keys still identify the actual card/ability: unrelated effects are not merged.
function GaRlBucket($value, $thresholds) {
  foreach ($thresholds as $i => $threshold) if ($value <= $threshold) return $i;
  return count($thresholds);
}

function GaRlCompactStateKey($seat, $legal) {
  $power = []; $health = [];
  foreach ([$seat, 3 - $seat] as $p) {
    $total = 0;
    foreach (GetField($p) as $obj) {
      if ($obj === null || !empty($obj->removed)) continue;
      $total += max(0, intval(ObjectCurrentPower($obj)));
    }
    $power[] = $total;
    $champion = GABotOwnChampion($p);
    $health[] = $champion === null ? -1 : GaRlBucket(GABotRemainingHP($champion), [5, 12]);
  }
  return json_encode([
    'phase' => GetCurrentPhase(), 'ownTurn' => $seat === intval(GetTurnPlayer()),
    'decision' => $legal['decisionType'] ?? '', 'handler' => $legal['handler'] ?? '',
    'health' => $health, 'powerBalance' => GaRlBucket($power[0] - $power[1], [-4, 3]),
    'hand' => GaRlBucket(GABotLiveCount(GetHand($seat)), [0, 3]),
    'memory' => GaRlBucket(GABotLiveCount(GetMemory($seat)), [0, 3]),
    'stack' => !empty(GetLiveEffectStackEntries()),
  ]);
}

// Bounded, deterministic subsets: identical catalog in imitation and inference.
// Large prompts retain a coverage flag instead of claiming exhaustive coverage.
function GaRlExpandActions($legal) {
  if (($legal['decisionType'] ?? '') !== 'MZMULTICHOOSE') return $legal;
  if (!empty($legal['constrainedMultiChoice'])) return $legal;
  $pool = array_values(array_unique($legal['candidates'] ?? []));
  $min = intval($legal['minimum'] ?? 0);
  $max = min(count($pool), intval($legal['maximum'] ?? 0));
  $answers = [];
  $visit = function($offset, $chosen) use (&$visit, &$answers, $pool, $min, $max) {
    if (count($answers) >= 2048) return;
    if (count($chosen) >= $min) $answers[] = $chosen ? implode('&', $chosen) : '-';
    if (count($chosen) >= $max) return;
    for ($i = $offset; $i < count($pool); ++$i) {
      $visit($i + 1, array_merge($chosen, [$pool[$i]]));
      if (count($answers) >= 2048) break;
    }
  };
  $visit(0, []);
  $legal['catalogTruncated'] = count($answers) >= 2048;
  foreach ($answers as $answer) $legal['actions'][] = ['playerID' => $legal['playerID'], 'mode' => 100, 'cardID' => $answer];
  $unique = [];
  foreach ($legal['actions'] as $action) $unique[json_encode($action)] = $action;
  $legal['actions'] = array_values($unique);
  return $legal;
}

// Public field facts only. Payload identities never enter the observation; use the
// engine's load predicate so unrelated attached cards do not count as ammunition.
function GaRlFieldTactics($obj) {
  $weapon = PropertyContains(EffectiveCardType($obj), 'WEAPON');
  return [
    'distant' => intval(IsDistant($obj)),
    'weapon' => intval($weapon),
    'loadedWeapon' => intval($weapon && FunctionalWeaponIsLoaded($obj)),
    'weaponDurability' => $weapon ? min(1, max(0, GetCounterCount($obj, 'durability')) / 5) : 0,
  ];
}

function GaRlFeatureState($seat, $legal) {
  $features = ['ownTurn' => intval($seat === intval(GetTurnPlayer())),
    'phase:' . GetCurrentPhase() => 1, 'decision:' . ($legal['decisionType'] ?? 'main') => 1,
    'handler:' . ($legal['handler'] ?? '') => 1];
  foreach ([$seat, 3 - $seat] as $i => $p) {
    $prefix = $i === 0 ? 'own' : 'enemy';
    $champion = GABotOwnChampion($p);
    $features[$prefix . 'HP'] = $champion ? max(-1, min(1, GABotRemainingHP($champion) / 30)) : 0;
    $features[$prefix . 'ChampionDistant'] = intval($champion !== null && IsDistant($champion));
    $tactics = ['distant' => 0, 'weapon' => 0, 'loadedWeapon' => 0, 'weaponDurability' => 0];
    $readyPower = 0; $rested = 0;
    foreach (GetField($p) as $obj) {
      if ($obj === null || !empty($obj->removed)) continue;
      foreach (GaRlFieldTactics($obj) as $name => $value) $tactics[$name] += $value;
      if (intval($obj->Status ?? 0) === 2) $readyPower += max(0, ObjectCurrentPower($obj));
      else ++$rested;
    }
    foreach ($tactics as $name => $value) $features[$prefix . ':' . $name] = min(1, $value / 5);
    $features[$prefix . 'ReadyPower'] = min(1, $readyPower / 30);
    $features[$prefix . 'Rested'] = min(1, $rested / 10);
    $features[$prefix . 'Hand'] = min(1, GABotLiveCount(GetHand($p)) / 10);
    $features[$prefix . 'Memory'] = min(1, GABotLiveCount(GetMemory($p)) / 10);
  }
  $features['stack'] = min(1, count(GetLiveEffectStackEntries()) / 5);
  $features['reserve'] = min(1, CountAvailableReservePayments($seat) / 10);
  $combat = GABotCombatContext($seat);
  if ($combat !== null) {
    $features['combatLethal'] = intval($combat['threat'] >= $combat['hp']);
    $features['combatDefending'] = intval($combat['targetOwner'] === $seat);
    $features['combatMargin'] = max(-1, min(1, ($combat['threat'] - $combat['hp']) / 10));
  }
  $knowledge = GABotDeckKnowledge($seat);
  $features['deckExact'] = intval($knowledge['exact']);
  $roles = [];
  foreach (array_keys($knowledge['unseen'] ?? []) as $id) {
    foreach (['ALLY', 'ACTION', 'ATTACK'] as $role) if (PropertyContains(CardType($id), $role)) $roles[$role][] = $id;
  }
  foreach ($roles as $role => $ids) {
    $odds = GABotDrawOdds($seat, $ids);
    $features['drawLower:' . $role] = $odds['lower'];
    $features['drawUpper:' . $role] = $odds['upper'];
  }
  $features['_actions'] = [];
  foreach ($legal['actions'] ?? [] as $action) {
    $descriptor = [];
    if (preg_match_all('/(?:myHand|myField|theirField|myMemory|myMaterial)-(\d+)/', $action['cardID'] ?? '', $matches)) {
      foreach ($matches[0] as $mz) {
        $obj = GetZoneObject($mz);
        if (!$obj || !empty($obj->removed)) continue;
        if (str_contains($mz, 'Field-')) {
        $descriptor = array_merge($descriptor, GaRlFieldTactics($obj));
        $descriptor['power'] = min(1, max(0, ObjectCurrentPower($obj)) / 15);
        $descriptor['hp'] = max(-1, min(1, GABotRemainingHP($obj) / 30));
        $descriptor['ready'] = intval(intval($obj->Status ?? 0) === 2);
        }
        $descriptor['enemyTarget'] = intval(str_starts_with($mz, 'their'));
        if (str_starts_with($mz, 'myHand')) {
          $cost = max(0, intval(CardCost_reserve($obj->CardID)));
          $descriptor['reserveCost'] = min(1, $cost / 10);
          $descriptor['reserveMargin'] = max(-1, min(1, (CountAvailableReservePayments($seat) - $cost) / 10));
        }
      }
    }
    $features['_actions'][GaRlActionKey($action, $legal)] = $descriptor;
  }
  return json_encode($features);
}

// Shared action-role and state/action interactions; no runtime reads when replaying a gradient.
function GaRlFeatures($stateKey, $actionKey) {
  $state = json_decode($stateKey, true) ?: [];
  $action = json_decode($actionKey, true) ?: [];
  $raw = strval($action[2] ?? $actionKey);
  $role = preg_match('/PASS|ENDTURN/i', $raw) ? 'pass' : (str_contains($raw, 'Activate') ? 'activate' : (str_contains($raw, 'myHand') ? 'play' : (str_contains($raw, '!') ? 'attack' : 'choose')));
  $tokens = ['role:' . $role, 'mode:' . ($action[1] ?? ''), 'decision:' . ($action[0] ?? '')];
  // Strip changing damage/status while preserving visible card identity and ability.
  $tokens[] = 'card:' . preg_replace('/:(\d+):(-?\d+)/', '', $raw);
  $features = [];
  foreach (($state['_actions'][$actionKey] ?? []) as $name => $value) $features['action:' . $name] = $value;
  foreach ($tokens as $token) {
    $features[$token] = 1.0;
    foreach ($state as $name => $value) if (is_numeric($value)) $features[$token . '/' . $name] = max(-1, min(1, floatval($value)));
  }
  return $features;
}

// Diagnostic only: absence of an optional scorer is not missing engine card code.
function GaRlDecisionSupport($legal, $actions, $seat) {
  $result = ['status' => 'unknown', 'kind' => 'unclassified', 'unsupportedActions' => []];
  if (!empty($legal['catalogTruncated'])) return ['status' => 'missing', 'kind' => 'action_catalog_truncated', 'unsupportedActions' => []];
  if (!in_array($legal['decisionType'] ?? '', ['MZMAYCHOOSE', 'YESNO'], true)) return $result;
  foreach ($actions as $action) {
    $choice = strval($action['cardID'] ?? '');
    if (in_array($choice, ['PASS', 'NO'], true)) continue;
    if (GABotOptionalScore($seat, $choice, $legal) === null) $result['unsupportedActions'][] = GaRlActionKey($action, $legal);
  }
  $result['status'] = $result['unsupportedActions'] ? 'missing' : 'supported';
  $result['kind'] = 'heuristic_optional_scoring';
  return $result;
}
