<?php

define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once __DIR__ . '/../TestAutomationBridge.php';

function RlParseArgs($argv) {
  $args = [
    'root' => 'GrandArchiveSim',
    'deck-file' => '',
    'episodes' => 100,
    'seed' => 123,
    'max-steps' => 1000,
    'max-turns' => 100,
    'max-actions' => 256,
    'learning-rate' => 0.05,
    'temperature' => 1.0,
    'epsilon' => 0.05,
    'timeout-reward' => -0.25,
    'tactical-terminal-weight' => 0.1,
    'aggro-leader-damage-reward' => 0.25,
    'control-leader-damage-reward' => 0.05,
    'control-enemy-threat-reward' => 0.15,
    'control-own-threat-penalty' => 0.1,
    'tactical-no-state-change-penalty' => 0.05,
    'checkpoint-every' => 25,
    'log-every' => 25,
    'workers' => 1,
    'worker-episodes' => 1,
    'strategy-mode' => 'none',
    'worker' => false,
    'worker-id' => 0,
    'policy-file' => '',
    'result-file' => '',
    'run-id' => '',
    'episode-number' => 0,
    'episode-seed' => 0,
    'capture-replay' => false,
    'memory-only' => null,
    'checkpoint' => '',
  ];

  $items = array_slice($argv, 1);
  for ($i = 0; $i < count($items); ++$i) {
    $arg = $items[$i];
    if ($arg === '--worker') {
      $args['worker'] = true;
      continue;
    }
    if ($arg === '--capture-replay') {
      $args['capture-replay'] = true;
      continue;
    }
    if ($arg === '--memory-only') {
      $args['memory-only'] = true;
      continue;
    }
    if ($arg === '--disk-games') {
      $args['memory-only'] = false;
      continue;
    }
    if (!str_starts_with($arg, '--')) continue;
    $arg = substr($arg, 2);
    if (str_contains($arg, '=')) {
      [$key, $value] = explode('=', $arg, 2);
    } else {
      $key = $arg;
      $value = ($i + 1 < count($items) && !str_starts_with($items[$i + 1], '--')) ? $items[++$i] : '1';
    }
    if (array_key_exists($key, $args)) {
      $args[$key] = $value;
    }
  }

  foreach (['episodes', 'seed', 'max-steps', 'max-turns', 'max-actions', 'checkpoint-every', 'log-every', 'workers', 'worker-episodes', 'worker-id', 'episode-number', 'episode-seed'] as $key) {
    $args[$key] = intval($args[$key]);
  }
  $args['workers'] = max(1, intval($args['workers']));
  $args['worker-episodes'] = max(1, intval($args['worker-episodes']));
  foreach (['learning-rate', 'temperature', 'epsilon', 'timeout-reward', 'tactical-terminal-weight', 'aggro-leader-damage-reward', 'control-leader-damage-reward', 'control-enemy-threat-reward', 'control-own-threat-penalty', 'tactical-no-state-change-penalty'] as $key) {
    $args[$key] = floatval($args[$key]);
  }
  return $args;
}

function RlFail($message) {
  fwrite(STDERR, $message . PHP_EOL);
  exit(1);
}

function RlEnsureDir($path) {
  if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
    RlFail('Unable to create directory: ' . $path);
  }
}

function RlWriteJson($path, $payload) {
  file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function RlCleanupGame($gameName) {
  if (function_exists('RegressionClearGamestateMemory')) {
    RegressionClearGamestateMemory($gameName);
  }
  if (isset($GLOBALS['bridgeMemoryOnlyGames']) && is_array($GLOBALS['bridgeMemoryOnlyGames'])) {
    unset($GLOBALS['bridgeMemoryOnlyGames'][strval($gameName)]);
  }
}

function RlRandomFloat() {
  return mt_rand() / mt_getrandmax();
}

function RlDefaultStateKeyVersion($root) {
  return 'lite-v2';
}

function RlStateKeyVersion() {
  return strval($GLOBALS['rlStateKeyVersion'] ?? 'lite-v2');
}

function RlCompatibleStateKeyVersions() {
  return ['lite-v2' => true, 'AzukiSim:azuki-v1' => true, 'strategy-v1' => true];
}

function RlStrategyEnabled($mode) {
  return strval($mode) === 'aggro-control';
}

function RlBucketPressure($value) {
  $value = intval($value);
  if ($value <= 0) return '0';
  if ($value <= 2) return '1-2';
  if ($value <= 5) return '3-5';
  if ($value <= 9) return '6-9';
  return '10+';
}

function RlStrategicStateKeyFromSnapshot($snapshot, $actingPlayer) {
  $actingPlayer = intval($actingPlayer);
  if ($actingPlayer !== 1 && $actingPlayer !== 2) $actingPlayer = 1;
  $opp = $actingPlayer === 1 ? 2 : 1;
  $strategic = is_array($snapshot['azukiStrategyState'] ?? null) ? $snapshot['azukiStrategyState'] : [];
  $me = is_array($strategic['p' . $actingPlayer] ?? null) ? $strategic['p' . $actingPlayer] : [];
  $them = is_array($strategic['p' . $opp] ?? null) ? $strategic['p' . $opp] : [];
  $players = is_array($snapshot['players'] ?? null) ? $snapshot['players'] : [];
  $player = is_array($players['player' . $actingPlayer] ?? null) ? $players['player' . $actingPlayer] : [];
  $dq = is_array($player['decisionQueue'] ?? null) ? $player['decisionQueue'] : [];
  $nextDQ = is_array($dq['next'] ?? null) ? $dq['next'] : [];
  $key = [
    'version' => 'strategy-v1',
    'phase' => strval($snapshot['phase'] ?? ''),
    'nextDQType' => strval($nextDQ['type'] ?? ''),
    'myLife' => strval($me['lifeBucket'] ?? 'high'),
    'theirLife' => strval($them['lifeBucket'] ?? 'high'),
    'myReadyAttack' => RlBucketPressure(intval($me['readyAttack'] ?? 0)),
    'theirReadyAttack' => RlBucketPressure(intval($them['readyAttack'] ?? 0)),
  ];
  ksort($key);
  return json_encode($key, JSON_UNESCAPED_SLASHES);
}

function RlLiteV2StateKeyFromSnapshot($snapshot) {
  $zones = is_array($snapshot['zones'] ?? null) ? $snapshot['zones'] : [];
  $players = is_array($snapshot['players'] ?? null) ? $snapshot['players'] : [];
  $p1 = is_array($players['player1'] ?? null) ? $players['player1'] : [];
  $p2 = is_array($players['player2'] ?? null) ? $players['player2'] : [];
  $c1 = is_array($p1['champion'] ?? null) ? $p1['champion'] : [];
  $c2 = is_array($p2['champion'] ?? null) ? $p2['champion'] : [];
  $p1DQ = is_array($p1['decisionQueue'] ?? null) ? $p1['decisionQueue'] : [];
  $p2DQ = is_array($p2['decisionQueue'] ?? null) ? $p2['decisionQueue'] : [];
  $p1NextDQ = is_array($p1DQ['next'] ?? null) ? $p1DQ['next'] : [];
  $p2NextDQ = is_array($p2DQ['next'] ?? null) ? $p2DQ['next'] : [];
  $scalars = [
    'activePlayer' => intval($snapshot['activePlayer'] ?? 0),
    'turnPlayer' => intval($snapshot['turnPlayer'] ?? 0),
    'phase' => strval($snapshot['phase'] ?? ''),
    'myHandCount' => intval($zones['myHandCount'] ?? 0),
    'myMemoryCount' => intval($zones['myMemoryCount'] ?? 0),
    'theirMemoryCount' => intval($zones['theirMemoryCount'] ?? 0),
    'myMaterialCount' => intval($zones['myMaterialCount'] ?? 0),
    'theirMaterialCount' => intval($zones['theirMaterialCount'] ?? 0),
    'p1ChampionRemainingLife' => intval($c1['remainingLife'] ?? 0),
    'p2ChampionRemainingLife' => intval($c2['remainingLife'] ?? 0),
    'p1ChampionDamage' => intval($c1['damage'] ?? 0),
    'p2ChampionDamage' => intval($c2['damage'] ?? 0),
    'p1NextDQType' => strval($p1NextDQ['type'] ?? ''),
    'p2NextDQType' => strval($p2NextDQ['type'] ?? ''),
  ];
  ksort($scalars);
  return json_encode($scalars, JSON_UNESCAPED_SLASHES);
}

function RlAzukiV1LifeBucket($life) {
  $life = intval($life);
  if ($life <= 5) return 'critical';
  if ($life <= 10) return 'low';
  if ($life <= 15) return 'medium';
  return 'high';
}

function RlAzukiV1StateKeyFromSnapshot($snapshot, $actingPlayer = 0) {
  $players = is_array($snapshot['players'] ?? null) ? $snapshot['players'] : [];
  $p1 = is_array($players['player1'] ?? null) ? $players['player1'] : [];
  $p2 = is_array($players['player2'] ?? null) ? $players['player2'] : [];
  $p1DQ = is_array($p1['decisionQueue'] ?? null) ? $p1['decisionQueue'] : [];
  $p2DQ = is_array($p2['decisionQueue'] ?? null) ? $p2['decisionQueue'] : [];
  $p1NextDQ = is_array($p1DQ['next'] ?? null) ? $p1DQ['next'] : [];
  $p2NextDQ = is_array($p2DQ['next'] ?? null) ? $p2DQ['next'] : [];
  $azuki = is_array($snapshot['azukiRlState'] ?? null) ? $snapshot['azukiRlState'] : [];
  $active = intval($actingPlayer);
  if ($active !== 1 && $active !== 2) $active = intval($snapshot['activePlayer'] ?? 0);
  if ($active !== 1 && $active !== 2) $active = intval($snapshot['turnPlayer'] ?? 1);
  if ($active !== 1 && $active !== 2) $active = 1;
  $opp = $active === 1 ? 2 : 1;
  $me = is_array($azuki['p' . $active] ?? null) ? $azuki['p' . $active] : [];
  $them = is_array($azuki['p' . $opp] ?? null) ? $azuki['p' . $opp] : [];
  $c1 = is_array($p1['champion'] ?? null) ? $p1['champion'] : [];
  $c2 = is_array($p2['champion'] ?? null) ? $p2['champion'] : [];
  $key = [
    'version' => 'AzukiSim:azuki-v1',
    'activePlayer' => $active,
    'turnPlayer' => intval($snapshot['turnPlayer'] ?? 0),
    'phase' => strval($snapshot['phase'] ?? ''),
    'p1NextDQType' => strval($p1NextDQ['type'] ?? ''),
    'p2NextDQType' => strval($p2NextDQ['type'] ?? ''),
    'p1Life' => strval($azuki['p1']['lifeBucket'] ?? RlAzukiV1LifeBucket(intval($c1['remainingLife'] ?? 0))),
    'p2Life' => strval($azuki['p2']['lifeBucket'] ?? RlAzukiV1LifeBucket(intval($c2['remainingLife'] ?? 0))),
    'myHand' => is_array($me['hand'] ?? null) ? $me['hand'] : [],
    'myGarden' => is_array($me['gardenExact'] ?? null) ? $me['gardenExact'] : [],
    'myAlley' => is_array($me['alleyExact'] ?? null) ? $me['alleyExact'] : [],
    'myGate' => is_array($me['gate'] ?? null) ? $me['gate'] : [],
    'myIkzArea' => intval($me['ikzAreaCount'] ?? 0),
    'myIkzToken' => intval($me['ikzToken'] ?? 0),
    'theirGarden' => is_array($them['gardenAbstract'] ?? null) ? $them['gardenAbstract'] : [],
    'theirAlley' => is_array($them['alleyAbstract'] ?? null) ? $them['alleyAbstract'] : [],
    'theirGateCount' => is_array($them['gate'] ?? null) ? count($them['gate']) : 0,
  ];
  ksort($key);
  return json_encode($key, JSON_UNESCAPED_SLASHES);
}

function RlStateKeyFromSnapshot($snapshot, $stateKeyVersion = null, $actingPlayer = 0) {
  $version = $stateKeyVersion === null ? RlStateKeyVersion() : strval($stateKeyVersion);
  if ($version === 'AzukiSim:azuki-v1') return RlAzukiV1StateKeyFromSnapshot($snapshot, $actingPlayer);
  return RlLiteV2StateKeyFromSnapshot($snapshot);
}

function RlActionSignature($action) {
  $chk = $action['chkInput'] ?? [];
  $chkKey = is_array($chk) ? implode(',', array_map('strval', $chk)) : strval($chk);
  return 'mode=' . intval($action['mode'] ?? -1)
    . '|card=' . strval($action['cardID'] ?? '')
    . '|button=' . strval($action['buttonInput'] ?? '')
    . '|input=' . strval($action['inputText'] ?? '')
    . '|chk=' . $chkKey;
}

function RlFilterEpisodeStepsByPlayer($episodeSteps, $player) {
  $filtered = [];
  foreach ($episodeSteps as $step) {
    if (intval($step['turn_player'] ?? 0) === intval($player)) $filtered[] = $step;
  }
  return $filtered;
}

function RlBuildStuckDiagnostics($stepTrace, $window = 200) {
  if (empty($stepTrace)) {
    return ['traceWindow' => 0, 'topActions' => [], 'topTransitions' => [], 'stateChangeRate' => 0.0];
  }
  $tail = array_slice($stepTrace, -max(1, intval($window)));
  $actionCounts = [];
  $transitionCounts = [];
  $changed = 0;
  foreach ($tail as $item) {
    $signature = strval($item['actionSignature'] ?? '');
    $actionCounts[$signature] = ($actionCounts[$signature] ?? 0) + 1;
    $pre = strval($item['preHash'] ?? '');
    $post = strval($item['postHash'] ?? '');
    if ($pre !== '' || $post !== '') {
      $transition = $pre . '->' . $post;
      $transitionCounts[$transition] = ($transitionCounts[$transition] ?? 0) + 1;
    }
    if (!empty($item['stateChanged'])) ++$changed;
  }
  arsort($actionCounts);
  arsort($transitionCounts);
  $topActions = [];
  foreach (array_slice($actionCounts, 0, 10, true) as $signature => $count) {
    $topActions[] = ['signature' => $signature, 'count' => intval($count)];
  }
  $topTransitions = [];
  foreach (array_slice($transitionCounts, 0, 10, true) as $transition => $count) {
    $topTransitions[] = ['transition' => $transition, 'count' => intval($count)];
  }
  return [
    'traceWindow' => count($tail),
    'stateChangeRate' => count($tail) > 0 ? ($changed / count($tail)) : 0.0,
    'topActions' => $topActions,
    'topTransitions' => $topTransitions,
    'tailTrace' => $tail,
  ];
}

function RlCandidateIndices($mask, $actions, $noOpKeys, $stateKey) {
  $indices = [];
  foreach ($mask as $i => $enabled) {
    if (!$enabled) continue;
    $action = $actions[$i] ?? [];
    $key = $stateKey . '|' . intval($action['mode'] ?? -1) . '|' . strval($action['cardID'] ?? '');
    if (!isset($noOpKeys[$key])) $indices[] = intval($i);
  }
  return $indices;
}

function RlActionTargetRole($action) {
  if (!is_array($action)) return '';
  $cardID = strval($action['cardID'] ?? '');
  $resolved = strval($action['resolvedCardID'] ?? '');
  if ($resolved !== '' && function_exists('CardType')) {
    $type = strval(CardType($resolved));
    if ($type === 'LEADER') return 'leader';
    if ($type === 'ENTITY') {
      if (str_starts_with($cardID, 'theirGarden-') || str_starts_with($cardID, 'theirAlley-')) return 'enemy-unit';
      if (str_starts_with($cardID, 'myGarden-') || str_starts_with($cardID, 'myAlley-')) return 'own-unit';
    }
  }
  if (str_starts_with($cardID, 'theirGarden-') || str_starts_with($cardID, 'theirAlley-')) return 'enemy-unit';
  return '';
}

function RlFilterLegalIndicesForPosture($legalIndices, $actions, $postureIdx) {
  if (empty($legalIndices)) return $legalIndices;
  $preferred = [];
  foreach ($legalIndices as $idx) {
    $role = RlActionTargetRole($actions[$idx] ?? []);
    if (intval($postureIdx) === 0 && $role === 'leader') $preferred[] = $idx;
    if (intval($postureIdx) === 1 && $role === 'enemy-unit') $preferred[] = $idx;
  }
  return !empty($preferred) ? $preferred : $legalIndices;
}

function RlAzukiStrategyMetrics($snapshot, $player) {
  $player = intval($player);
  if ($player !== 1 && $player !== 2) $player = 1;
  $opp = $player === 1 ? 2 : 1;
  $strategic = is_array($snapshot['azukiStrategyState'] ?? null) ? $snapshot['azukiStrategyState'] : [];
  $me = is_array($strategic['p' . $player] ?? null) ? $strategic['p' . $player] : [];
  $them = is_array($strategic['p' . $opp] ?? null) ? $strategic['p' . $opp] : [];
  return [
    'myRemainingLife' => intval($me['remainingLife'] ?? 0),
    'enemyRemainingLife' => intval($them['remainingLife'] ?? 0),
    'myReadyAttack' => intval($me['readyAttack'] ?? 0),
    'enemyReadyAttack' => intval($them['readyAttack'] ?? 0),
    'myBoardAttack' => intval($me['boardAttack'] ?? ($me['readyAttack'] ?? 0)),
    'enemyBoardAttack' => intval($them['boardAttack'] ?? ($them['readyAttack'] ?? 0)),
  ];
}

function RlTacticalImmediateReward($beforeSnapshot, $afterSnapshot, $player, $postureIdx, $stateChanged, $args) {
  if (intval($postureIdx) !== 0 && intval($postureIdx) !== 1) return 0.0;
  $before = RlAzukiStrategyMetrics($beforeSnapshot, $player);
  $after = RlAzukiStrategyMetrics($afterSnapshot, $player);
  $reward = 0.0;
  $leaderDamage = max(0, intval($before['enemyRemainingLife']) - intval($after['enemyRemainingLife']));
  if (intval($postureIdx) === 0) {
    $reward += $leaderDamage * floatval($args['aggro-leader-damage-reward']);
  } else {
    $enemyThreatReduced = max(0, intval($before['enemyBoardAttack']) - intval($after['enemyBoardAttack']));
    $ownThreatLost = max(0, intval($before['myBoardAttack']) - intval($after['myBoardAttack']));
    $reward += $leaderDamage * floatval($args['control-leader-damage-reward']);
    $reward += $enemyThreatReduced * floatval($args['control-enemy-threat-reward']);
    $reward -= $ownThreatLost * floatval($args['control-own-threat-penalty']);
  }
  if (!$stateChanged) $reward -= floatval($args['tactical-no-state-change-penalty']);
  return $reward;
}

class RlTabularPolicy {
  public int $maxActions;
  public float $temperature;
  public float $learningRate;
  public string $stateKeyVersion;
  public string $strategyMode = 'none';
  public ?RlTabularPolicy $strategyPolicy = null;
  // Sparse map: stateKey => actionIndex => logit. Missing entries are zero.
  public array $logits = [];

  public function __construct($maxActions, $temperature, $learningRate, $stateKeyVersion = null) {
    $this->maxActions = intval($maxActions);
    $this->temperature = max(0.000001, floatval($temperature));
    $this->learningRate = floatval($learningRate);
    $this->stateKeyVersion = $stateKeyVersion === null ? RlStateKeyVersion() : strval($stateKeyVersion);
  }

  public function ensureState($stateKey) {
    if (!isset($this->logits[$stateKey])) {
      $this->logits[$stateKey] = [];
    }
    return $this->logits[$stateKey];
  }

  public function actionProbabilities($stateKey, $legalIndices) {
    if (empty($legalIndices)) return [];
    $stateLogits = $this->logits[$stateKey] ?? [];
    $vals = [];
    $maxLogit = -1.0e30;
    foreach ($legalIndices as $idx) {
      $logit = floatval($stateLogits[strval($idx)] ?? 0.0) / $this->temperature;
      $vals[] = [$idx, $logit];
      if ($logit > $maxLogit) $maxLogit = $logit;
    }
    $denom = 0.0;
    $expVals = [];
    foreach ($vals as [$idx, $logit]) {
      $v = exp($logit - $maxLogit);
      $expVals[] = [$idx, $v];
      $denom += $v;
    }
    $result = [];
    foreach ($expVals as [$idx, $v]) {
      $result[] = [$idx, $denom > 0 ? ($v / $denom) : (1.0 / count($expVals))];
    }
    return $result;
  }

  public function selectAction($stateKey, $legalMask, $epsilon) {
    $legalIndices = [];
    $limit = min(count($legalMask), $this->maxActions);
    for ($i = 0; $i < $limit; ++$i) {
      if (!empty($legalMask[$i])) $legalIndices[] = $i;
    }
    if (empty($legalIndices)) throw new Exception('No legal actions available for policy selection.');
    if (RlRandomFloat() < $epsilon) {
      return $legalIndices[mt_rand(0, count($legalIndices) - 1)];
    }
    $probs = $this->actionProbabilities($stateKey, $legalIndices);
    $r = RlRandomFloat();
    $acc = 0.0;
    foreach ($probs as [$idx, $p]) {
      $acc += $p;
      if ($r <= $acc) return $idx;
    }
    return $probs[count($probs) - 1][0];
  }

  public function updateEpisode($episodeSteps, $terminalReward) {
    foreach ($episodeSteps as $step) {
      if (isset($step['turn_player_filter']) && intval($step['turn_player'] ?? 0) !== intval($step['turn_player_filter'])) continue;
      $stateKey = $step['state_key'];
      $actionIdx = intval($step['action_index']);
      if ($actionIdx >= $this->maxActions) continue;
      $probs = $this->actionProbabilities($stateKey, $step['legal_indices']);
      $this->ensureState($stateKey);
      foreach ($probs as [$idx, $p]) {
        $grad = ($idx === $actionIdx ? 1.0 : 0.0) - $p;
        $key = strval($idx);
        $this->logits[$stateKey][$key] = floatval($this->logits[$stateKey][$key] ?? 0.0) + $this->learningRate * $terminalReward * $grad;
        if (abs($this->logits[$stateKey][$key]) < 1.0e-12) unset($this->logits[$stateKey][$key]);
      }
      if (empty($this->logits[$stateKey])) unset($this->logits[$stateKey]);
    }
  }

  public function updateEpisodeForPlayer($episodeSteps, $player, $terminalReward) {
    $this->applyDelta($this->episodeDeltaForPlayer($episodeSteps, $player, $terminalReward));
  }

  public function episodeDeltaForPlayer($episodeSteps, $player, $terminalReward, $terminalWeight = 1.0) {
    $delta = [];
    foreach ($episodeSteps as $step) {
      if (intval($step['turn_player'] ?? 0) !== intval($player)) continue;
      $stateKey = $step['state_key'];
      $actionIdx = intval($step['action_index']);
      if ($actionIdx >= $this->maxActions) continue;
      $probs = $this->actionProbabilities($stateKey, $step['legal_indices']);
      $stepReward = array_key_exists('tactical_reward', $step)
        ? (floatval($step['tactical_reward']) + floatval($terminalWeight) * floatval($terminalReward))
        : floatval($terminalReward);
      foreach ($probs as [$idx, $p]) {
        $grad = ($idx === $actionIdx ? 1.0 : 0.0) - $p;
        $key = strval($idx);
        $delta[$stateKey][$key] = floatval($delta[$stateKey][$key] ?? 0.0) + $this->learningRate * $stepReward * $grad;
      }
    }
    return $delta;
  }

  public function applyDelta($delta) {
    if (!is_array($delta)) return;
    foreach ($delta as $stateKey => $values) {
      if (!is_array($values) && !is_object($values)) continue;
      $stateKey = strval($stateKey);
      foreach ((array)$values as $idx => $value) {
        $actionIdx = intval($idx);
        if ($actionIdx < 0 || $actionIdx >= $this->maxActions) continue;
        $v = floatval($value);
        if (abs($v) < 1.0e-12) continue;
        $key = strval($actionIdx);
        $this->logits[$stateKey][$key] = floatval($this->logits[$stateKey][$key] ?? 0.0) + $v;
        if (abs($this->logits[$stateKey][$key]) < 1.0e-12) unset($this->logits[$stateKey][$key]);
      }
      if (empty($this->logits[$stateKey] ?? [])) unset($this->logits[$stateKey]);
    }
  }

  public static function mergeDeltas($deltas) {
    $merged = [];
    foreach ($deltas as $delta) {
      if (!is_array($delta)) continue;
      foreach ($delta as $stateKey => $values) {
        if (!is_array($values) && !is_object($values)) continue;
        $stateKey = strval($stateKey);
        foreach ((array)$values as $idx => $value) {
          $key = strval(intval($idx));
          $merged[$stateKey][$key] = floatval($merged[$stateKey][$key] ?? 0.0) + floatval($value);
          if (abs($merged[$stateKey][$key]) < 1.0e-12) unset($merged[$stateKey][$key]);
        }
        if (empty($merged[$stateKey] ?? [])) unset($merged[$stateKey]);
      }
    }
    return $merged;
  }

  public function payload() {
    return $this->payloadWithStrategy(true);
  }

  public function payloadWithStrategy($includeStrategy) {
    $logits = [];
    foreach ($this->logits as $stateKey => $values) {
      if (!empty($values)) $logits[$stateKey] = (object)$values;
    }
    $payload = [
      'max_actions' => $this->maxActions,
      'temperature' => $this->temperature,
      'learning_rate' => $this->learningRate,
      'state_key_version' => $this->stateKeyVersion,
      'logits_format' => 'sparse_index_map',
      'logits' => $logits,
    ];
    if ($includeStrategy && $this->strategyPolicy !== null && RlStrategyEnabled($this->strategyMode)) {
      $payload['strategy_mode'] = $this->strategyMode;
      $payload['strategy_policy'] = $this->strategyPolicy->payloadWithStrategy(false);
    }
    return $payload;
  }

  public static function fromPayload($payload, $fallbackMaxActions, $fallbackTemperature, $fallbackLearningRate) {
    if (!is_array($payload)) {
      RlFail('Checkpoint payload must be a JSON object.');
    }
    $stateKeyVersion = strval($payload['state_key_version'] ?? 'legacy-v1');
    if (empty(RlCompatibleStateKeyVersions()[$stateKeyVersion])) {
      $obj = new RlTabularPolicy(
        intval($payload['max_actions'] ?? $fallbackMaxActions),
        floatval($payload['temperature'] ?? $fallbackTemperature),
        floatval($payload['learning_rate'] ?? $fallbackLearningRate)
      );
      fwrite(STDERR, 'Checkpoint state key version ' . $stateKeyVersion . ' is incompatible with this trainer; starting with an empty policy table.' . PHP_EOL);
      return $obj;
    }
    $obj = new RlTabularPolicy(
      intval($payload['max_actions'] ?? $fallbackMaxActions),
      floatval($payload['temperature'] ?? $fallbackTemperature),
      floatval($payload['learning_rate'] ?? $fallbackLearningRate),
      $stateKeyVersion
    );
    $format = strval($payload['logits_format'] ?? '');
    $rawLogits = is_array($payload['logits'] ?? null) ? $payload['logits'] : [];
    foreach ($rawLogits as $stateKey => $values) {
      if (!is_array($values) && !is_object($values)) continue;
      $valuesArray = (array)$values;
      foreach ($valuesArray as $idx => $value) {
        $actionIdx = intval($idx);
        if ($actionIdx < 0 || $actionIdx >= $obj->maxActions) continue;
        $floatValue = floatval($value);
        if (abs($floatValue) < 1.0e-12) continue;
        $obj->logits[strval($stateKey)][strval($actionIdx)] = $floatValue;
      }
      if (empty($obj->logits[strval($stateKey)] ?? [])) unset($obj->logits[strval($stateKey)]);
    }
    $strategyMode = strval($payload['strategy_mode'] ?? 'none');
    $strategyPayload = is_array($payload['strategy_policy'] ?? null) ? $payload['strategy_policy'] : null;
    if (RlStrategyEnabled($strategyMode) && $strategyPayload !== null) {
      $obj->strategyMode = $strategyMode;
      $obj->strategyPolicy = self::fromPayload($strategyPayload, 2, $obj->temperature, $obj->learningRate);
    }
    return $obj;
  }

  public static function load($path, $fallbackMaxActions, $fallbackTemperature, $fallbackLearningRate) {
    if (!is_file($path)) RlFail('Checkpoint file not found: ' . $path);
    $payload = json_decode(file_get_contents($path), true);
    if (!is_array($payload)) RlFail('Checkpoint is not valid JSON: ' . $path);
    return self::fromPayload($payload, $fallbackMaxActions, $fallbackTemperature, $fallbackLearningRate);
  }

  public function stateCount() {
    return count($this->logits);
  }

  public function copy() {
    $obj = new RlTabularPolicy($this->maxActions, $this->temperature, $this->learningRate, $this->stateKeyVersion);
    $obj->logits = $this->logits;
    $obj->strategyMode = $this->strategyMode;
    $obj->strategyPolicy = $this->strategyPolicy !== null ? $this->strategyPolicy->copy() : null;
    return $obj;
  }
}

function RlEnsureStrategyPolicy($policy, $strategyMode) {
  if (!$policy instanceof RlTabularPolicy) return;
  $strategyMode = strval($strategyMode);
  if (!RlStrategyEnabled($strategyMode)) return;
  $policy->strategyMode = $strategyMode;
  if (!$policy->strategyPolicy instanceof RlTabularPolicy) {
    $policy->strategyPolicy = new RlTabularPolicy(2, $policy->temperature, $policy->learningRate, 'strategy-v1');
  }
}

function RlStepLoaded($root, $gameName, $action) {
  $preHash = RegressionCurrentGamestateHash($root, $gameName);
  $apply = BridgeApplyEngineActionLoaded($root, $gameName, $action);
  BridgeLoadRuntimeGame($root, $gameName);
  $snapshot = BridgeSnapshotLoaded($root, $gameName, 'summary');
  $legal = BridgeEnumerateLegalActionsLoaded($root, $gameName);
  return [
    'applyResult' => $apply,
    'snapshot' => $snapshot,
    'legalActions' => $legal,
    'preHash' => $preHash,
    'postHash' => strval($snapshot['gamestateHash'] ?? ''),
  ];
}

function RlRunEpisode($args, $deckText, $policy, $opponent, $runId, $episodeNumber, $epSeed, $captureReplay, $updateP2) {
  $gameName = 'rl_train_php_' . $runId . '_' . sprintf('%04d', intval($episodeNumber)) . '_' . intval($epSeed);
  $memoryArg = $args['memory-only'] === null ? 'auto' : ($args['memory-only'] ? '1' : '0');
  $GLOBALS['bridgeIncludeAzukiRlState'] =
    ($policy instanceof RlTabularPolicy && $policy->stateKeyVersion === 'AzukiSim:azuki-v1')
    || ($opponent instanceof RlTabularPolicy && $opponent->stateKeyVersion === 'AzukiSim:azuki-v1');
  $GLOBALS['bridgeIncludeAzukiStrategyState'] =
    ($policy instanceof RlTabularPolicy && RlStrategyEnabled($policy->strategyMode))
    || ($opponent instanceof RlTabularPolicy && RlStrategyEnabled($opponent->strategyMode));
  $startPayload = BridgeStartSelfplayGame($args['root'], $gameName, intval($epSeed), $deckText, $deckText, $memoryArg);
  if (empty($startPayload['success'])) RlFail('start-selfplay-game failed: ' . json_encode($startPayload));

  $snapshot = is_array($startPayload['snapshot'] ?? null) ? $startPayload['snapshot'] : BridgeSnapshotLoaded($args['root'], $gameName, 'summary');
  $initialFull = BridgeSnapshotLoaded($args['root'], $gameName, 'full');
  $legal = is_array($startPayload['legalActions'] ?? null) ? $startPayload['legalActions'] : ['actions' => []];
  $lastLegalActions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
  $mask = array_fill(0, count($lastLegalActions), 1);

  $episodeSteps = [];
  $strategySteps = [];
  $replayActions = [];
  $stepTrace = [];
  $noOpKeys = [];
  $info = ['winner' => 0, 'isTerminal' => false, 'timedOut' => false, 'stepCount' => 0, 'gamestateHash' => strval($snapshot['gamestateHash'] ?? '')];
  $reward = 0.0;
  $done = false;
  $episodeStart = microtime(true);

  while (!$done) {
    BridgeLoadRuntimeGame($args['root'], $gameName);
    $legal = BridgeEnumerateLegalActionsLoaded($args['root'], $gameName);
    $lastLegalActions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
    $mask = array_fill(0, count($lastLegalActions), 1);

    if (!in_array(1, $mask, true)) {
      $done = true;
      $reward = 0.0;
      $info['timedOut'] = true;
      break;
    }
    $turnPlayer = intval($legal['playerID'] ?? ($snapshot['turnPlayer'] ?? 1));
    if ($turnPlayer !== 1 && $turnPlayer !== 2) $turnPlayer = intval($snapshot['turnPlayer'] ?? 1);
    $actingPolicy = $turnPlayer === 1 ? $policy : $opponent;
    $stateKey = RlStateKeyFromSnapshot($snapshot, $actingPolicy->stateKeyVersion, $turnPlayer);
    $boundedMask = array_slice($mask, 0, intval($args['max-actions']));
    $legalIndices = RlCandidateIndices($boundedMask, $lastLegalActions, $noOpKeys, $stateKey);
    if (empty($legalIndices)) {
      $done = true;
      $reward = 0.0;
      $info['timedOut'] = true;
      break;
    }
    $strategyPosture = null;
    if ($actingPolicy instanceof RlTabularPolicy && $actingPolicy->strategyPolicy instanceof RlTabularPolicy && RlStrategyEnabled($actingPolicy->strategyMode)) {
      $strategyKey = RlStrategicStateKeyFromSnapshot($snapshot, $turnPlayer);
      $strategyPosture = $actingPolicy->strategyPolicy->selectAction($strategyKey, [1, 1], floatval($args['epsilon']));
      $strategySteps[] = [
        'state_key' => $strategyKey,
        'action_index' => intval($strategyPosture),
        'legal_indices' => [0, 1],
        'turn_player' => $turnPlayer,
      ];
      $legalIndices = RlFilterLegalIndicesForPosture($legalIndices, $lastLegalActions, $strategyPosture);
    }
    $filteredMask = array_fill(0, count($boundedMask), 0);
    foreach ($legalIndices as $idx) $filteredMask[$idx] = 1;
    $actionIndex = $actingPolicy->selectAction($stateKey, $filteredMask, floatval($args['epsilon']));
    $action = $lastLegalActions[$actionIndex] ?? [];
    $cleanAction = [
      'playerID' => intval($action['playerID'] ?? 0),
      'mode' => intval($action['mode'] ?? 0),
      'buttonInput' => strval($action['buttonInput'] ?? ''),
      'cardID' => strval($action['cardID'] ?? ''),
      'chkInput' => is_array($action['chkInput'] ?? null) ? $action['chkInput'] : [],
      'inputText' => strval($action['inputText'] ?? ''),
      'resolvedCardID' => strval($action['resolvedCardID'] ?? ''),
    ];
    $stepIndex = count($episodeSteps);
    $episodeSteps[] = ['state_key' => $stateKey, 'action_index' => $actionIndex, 'legal_indices' => $legalIndices, 'turn_player' => $turnPlayer, 'strategy_posture' => $strategyPosture];
    $replayActions[] = ['step' => count($replayActions) + 1, 'turnPlayer' => $turnPlayer, 'actionIndex' => $actionIndex, 'legalCount' => count($legalIndices), 'strategyPosture' => $strategyPosture, 'action' => $cleanAction];

    try {
      $beforeSnapshot = $snapshot;
      $stepPayload = RlStepLoaded($args['root'], $gameName, $cleanAction);
      $applyResult = $stepPayload['applyResult'];
      if (empty($applyResult['success'])) throw new Exception(strval($applyResult['message'] ?? 'engine action failed'));
      $snapshot = $stepPayload['snapshot'];
      if ($strategyPosture !== null) {
        $episodeSteps[$stepIndex]['tactical_reward'] = RlTacticalImmediateReward($beforeSnapshot, $snapshot, $turnPlayer, $strategyPosture, $stepPayload['preHash'] !== $stepPayload['postHash'], $args);
        $replayActions[$stepIndex]['tacticalReward'] = $episodeSteps[$stepIndex]['tactical_reward'];
      }
      $legal = $stepPayload['legalActions'];
      $lastLegalActions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
      $mask = array_fill(0, count($lastLegalActions), 1);
      $terminal = is_array($snapshot['terminal'] ?? null) ? $snapshot['terminal'] : [];
      $isTerminal = !empty($terminal['isTerminal']);
      $winner = intval($terminal['winner'] ?? 0);
      $timedOut = count($replayActions) >= intval($args['max-steps']) || intval($snapshot['turnNumber'] ?? 0) > intval($args['max-turns']);
      $done = $isTerminal || $timedOut;
      $reward = 0.0;
      if ($done && $isTerminal) $reward = $winner === 1 ? 1.0 : ($winner === 2 ? -1.0 : 0.0);
      $info = [
        'winner' => $winner,
        'isTerminal' => $isTerminal,
        'timedOut' => $timedOut,
        'stepCount' => count($replayActions),
        'gamestateHash' => strval($snapshot['gamestateHash'] ?? ''),
        'legalKind' => strval($legal['kind'] ?? ''),
        'chosenAction' => $cleanAction,
        'stateChanged' => $stepPayload['preHash'] !== $stepPayload['postHash'],
        'preHash' => $stepPayload['preHash'],
        'postHash' => $stepPayload['postHash'],
        'flashMessage' => strval($snapshot['flashMessage'] ?? ''),
      ];
    } catch (Throwable $throwable) {
      $done = true;
      $reward = 0.0;
      $info = ['winner' => 0, 'isTerminal' => false, 'timedOut' => true, 'stepCount' => count($replayActions), 'legalKind' => 'engine-error', 'error' => $throwable->getMessage(), 'chosenAction' => $cleanAction];
      if ($strategyPosture !== null) {
        $episodeSteps[$stepIndex]['tactical_reward'] = -floatval($args['tactical-no-state-change-penalty']);
        $replayActions[$stepIndex]['tacticalReward'] = $episodeSteps[$stepIndex]['tactical_reward'];
      }
    }
    $mode = intval($cleanAction['mode']);
    $cardId = strval($cleanAction['cardID']);
    $stepTrace[] = [
      'step' => intval($info['stepCount'] ?? count($replayActions)),
      'turnPlayer' => $turnPlayer,
      'legalCount' => count($legalIndices),
      'actionSignature' => RlActionSignature($cleanAction),
      'stateChanged' => !empty($info['stateChanged']),
      'preHash' => strval($info['preHash'] ?? ''),
      'postHash' => strval($info['postHash'] ?? ''),
      'gamestateHash' => strval($info['gamestateHash'] ?? ''),
      'flashMessage' => strval($info['flashMessage'] ?? ''),
    ];
    if (empty($info['stateChanged']) && !$done) {
      $noOpKeys[$stateKey . '|' . $mode . '|' . $cardId] = true;
    }
  }

  $elapsedMs = intval(round((microtime(true) - $episodeStart) * 1000));
  $steps = intval($info['stepCount'] ?? count($replayActions));
  $episodeTimedOut = !empty($info['timedOut']);
  if ($episodeTimedOut && empty($info['isTerminal'])) {
    $reward = floatval($args['timeout-reward']);
  }
  $tacticalTerminalWeight = RlStrategyEnabled(strval($args['strategy-mode'])) ? floatval($args['tactical-terminal-weight']) : 1.0;
  $p1Delta = $policy->episodeDeltaForPlayer($episodeSteps, 1, floatval($reward), $tacticalTerminalWeight);
  $p1StrategyDelta = ($policy instanceof RlTabularPolicy && $policy->strategyPolicy instanceof RlTabularPolicy)
    ? $policy->strategyPolicy->episodeDeltaForPlayer($strategySteps, 1, floatval($reward))
    : [];
  $p2Reward = ($episodeTimedOut && empty($info['isTerminal'])) ? floatval($args['timeout-reward']) : -floatval($reward);
  $p2Delta = $updateP2 ? $policy->episodeDeltaForPlayer($episodeSteps, 2, $p2Reward, $tacticalTerminalWeight) : [];
  $p2StrategyDelta = ($updateP2 && $policy instanceof RlTabularPolicy && $policy->strategyPolicy instanceof RlTabularPolicy)
    ? $policy->strategyPolicy->episodeDeltaForPlayer($strategySteps, 2, $p2Reward)
    : [];
  $episodeWinner = intval($info['winner'] ?? 0);
  $replay = null;
  if ($captureReplay || $episodeTimedOut) {
    $replay = [
      'episode' => intval($episodeNumber),
      'seed' => intval($epSeed),
      'gameName' => $gameName,
      'memoryOnlyResolved' => $startPayload['memoryOnlyResolved'] ?? null,
      'initialGamestateHash' => strval($startPayload['gamestateHash'] ?? ''),
      'initialGamestateText' => strval($initialFull['gamestateText'] ?? ''),
      'deckParseSummary' => $startPayload['deckParseSummary'] ?? [],
      'result' => $info,
      'actions' => $replayActions,
    ];
    if ($episodeTimedOut) {
      $replay['stuckDiagnostics'] = RlBuildStuckDiagnostics($stepTrace);
    }
  }

  RlCleanupGame($gameName);
  unset($episodeSteps, $strategySteps, $replayActions, $stepTrace, $initialFull, $snapshot, $legal, $lastLegalActions, $mask);
  if (function_exists('gc_collect_cycles')) gc_collect_cycles();

  return [
    'episode' => intval($episodeNumber),
    'seed' => intval($epSeed),
    'winner' => $episodeWinner,
    'reward' => floatval($reward),
    'p2Reward' => floatval($p2Reward),
    'steps' => $steps,
    'timedOut' => $episodeTimedOut,
    'elapsedMs' => $elapsedMs,
    'info' => $info,
    'p1Delta' => $p1Delta,
    'p2Delta' => $p2Delta,
    'p1StrategyDelta' => $p1StrategyDelta,
    'p2StrategyDelta' => $p2StrategyDelta,
    'replay' => $replay,
  ];
}

function RlEpisodeOutcome($summary) {
  if (!empty($summary['timedOut'])) return 'timeout';
  $winner = intval($summary['winner'] ?? 0);
  return $winner > 0 ? ('winner=P' . $winner) : 'ended';
}

function RlWriteCheckpoint($ckptDir, $episodeNumber, $policy, &$frozenPool) {
  $ckptPath = $ckptDir . DIRECTORY_SEPARATOR . 'episode_' . sprintf('%04d', intval($episodeNumber)) . '.json';
  RlWriteJson($ckptPath, $policy->payload());
  RlWriteJson($ckptDir . DIRECTORY_SEPARATOR . 'latest.json', $policy->payload());
  $frozenPool[] = $policy->copy();
  if (count($frozenPool) > 5) array_shift($frozenPool);
  return $ckptPath;
}

function RlPrintProgress($args, $policy, $epsDone, $steps, $outcome, $timedOutEpisodes, $completedSteps, $trainStart) {
  $elapsedS = max(0.000001, microtime(true) - $trainStart);
  $epsPerS = intval($epsDone) / $elapsedS;
  $stepsPerS = intval($completedSteps) / $elapsedS;
  $epsRemaining = max(0, intval($args['episodes']) - intval($epsDone));
  $etaS = $epsPerS > 0 ? ($epsRemaining / $epsPerS) : 0;
  $etaText = date('Y-m-d H:i:s', time() + intval(round($etaS)));
  $pct = intval($args['episodes']) > 0 ? (100.0 * intval($epsDone) / intval($args['episodes'])) : 100.0;
  echo sprintf(
    "[progress] ep %d/%d (%.1f%%) | epSteps %d | outcome %s | timeouts %d/%d | mem %.1fMB | states %d | elapsed %.1fs | eps/s %.3f | steps/s %.1f | avgSteps/ep %.1f | ETA %s\n",
    intval($epsDone),
    intval($args['episodes']),
    $pct,
    intval($steps),
    $outcome,
    intval($timedOutEpisodes),
    intval($epsDone),
    memory_get_usage(true) / 1048576.0,
    $policy->stateCount(),
    $elapsedS,
    $epsPerS,
    $stepsPerS,
    intval($epsDone) > 0 ? (intval($completedSteps) / intval($epsDone)) : 0.0,
    $etaText
  );
}

function RlBaseRunConfig($args, $baseDir, $replayDir, $timeoutReplayPath, $completedSteps, $timedOutEpisodes, $totalElapsedMs, $episodeSummaries) {
  return [
    'root' => $args['root'],
    'deckFile' => $args['deck-file'],
    'episodes' => intval($args['episodes']),
    'seed' => intval($args['seed']),
    'maxSteps' => intval($args['max-steps']),
    'maxTurns' => intval($args['max-turns']),
    'maxActions' => intval($args['max-actions']),
    'learningRate' => floatval($args['learning-rate']),
    'temperature' => floatval($args['temperature']),
    'epsilon' => floatval($args['epsilon']),
    'timeoutReward' => floatval($args['timeout-reward']),
    'tacticalTerminalWeight' => floatval($args['tactical-terminal-weight']),
    'aggroLeaderDamageReward' => floatval($args['aggro-leader-damage-reward']),
    'controlLeaderDamageReward' => floatval($args['control-leader-damage-reward']),
    'controlEnemyThreatReward' => floatval($args['control-enemy-threat-reward']),
    'controlOwnThreatPenalty' => floatval($args['control-own-threat-penalty']),
    'tacticalNoStateChangePenalty' => floatval($args['tactical-no-state-change-penalty']),
    'memoryOnly' => $args['memory-only'],
    'trainer' => 'php',
    'stateKeyVersion' => RlStateKeyVersion(),
    'strategyMode' => strval($GLOBALS['rlStrategyMode'] ?? 'none'),
    'workers' => intval($args['workers']),
    'workerEpisodes' => intval($args['worker-episodes']),
    'checkpointEvery' => intval($args['checkpoint-every']),
    'finalReplay' => $replayDir . DIRECTORY_SEPARATOR . 'episode_' . sprintf('%04d', intval($args['episodes'])) . '.json',
    'firstTimeoutReplay' => $timeoutReplayPath,
    'summary' => [
      'totalSteps' => intval($completedSteps),
      'timedOutEpisodes' => intval($timedOutEpisodes),
      'elapsedMs' => intval($totalElapsedMs),
      'stepsPerSecond' => $totalElapsedMs > 0 ? (intval($completedSteps) / ($totalElapsedMs / 1000.0)) : 0.0,
    ],
    'episodesSummary' => $episodeSummaries,
  ];
}

function RlRunWorker($args) {
  if (trim(strval($args['deck-file'])) === '') RlFail('--deck-file is required.');
  if (!is_file($args['deck-file'])) RlFail('Deck file not found: ' . $args['deck-file']);
  if (!is_file(strval($args['policy-file']))) RlFail('Worker policy file not found: ' . strval($args['policy-file']));
  if (trim(strval($args['result-file'])) === '') RlFail('--result-file is required for worker mode.');

  mt_srand(intval($args['episode-seed']) + intval($args['worker-id']));
  $deckText = file_get_contents($args['deck-file']);
  $policy = RlTabularPolicy::load(strval($args['policy-file']), $args['max-actions'], $args['temperature'], $args['learning-rate']);
  RlEnsureStrategyPolicy($policy, $args['strategy-mode']);
  $GLOBALS['rlStrategyMode'] = $policy->strategyMode;
  $episodeCount = max(1, min(intval($args['worker-episodes']), intval($args['episodes'])));
  $allDeltas = [];
  $allStrategyDeltas = [];
  $summaries = [];
  $replays = [];
  for ($i = 0; $i < $episodeCount; ++$i) {
    $episodeNumber = intval($args['episode-number']) + $i;
    $episodeSeed = intval($args['episode-seed']) + $i;
    $captureReplay = !empty($args['capture-replay']) && ($i === $episodeCount - 1);
    $result = RlRunEpisode(
      $args,
      $deckText,
      $policy,
      $policy,
      strval($args['run-id']),
      $episodeNumber,
      $episodeSeed,
      $captureReplay,
      true
    );
    $episodeDelta = RlTabularPolicy::mergeDeltas([$result['p1Delta'] ?? [], $result['p2Delta'] ?? []]);
    $episodeStrategyDelta = RlTabularPolicy::mergeDeltas([$result['p1StrategyDelta'] ?? [], $result['p2StrategyDelta'] ?? []]);
    $allDeltas[] = $episodeDelta;
    $allStrategyDeltas[] = $episodeStrategyDelta;
    $policy->applyDelta($episodeDelta);
    if ($policy->strategyPolicy instanceof RlTabularPolicy) $policy->strategyPolicy->applyDelta($episodeStrategyDelta);
    $summaries[] = [
      'episode' => intval($result['episode']),
      'seed' => intval($result['seed']),
      'winner' => intval($result['winner']),
      'reward' => floatval($result['reward']),
      'steps' => intval($result['steps']),
      'timedOut' => !empty($result['timedOut']),
      'elapsedMs' => intval($result['elapsedMs']),
    ];
    if (is_array($result['replay'] ?? null)) {
      $replays[] = [
        'episode' => intval($result['episode']),
        'timedOut' => !empty($result['timedOut']),
        'replay' => $result['replay'],
      ];
    }
  }
  $delta = RlTabularPolicy::mergeDeltas($allDeltas);
  $strategyDelta = RlTabularPolicy::mergeDeltas($allStrategyDeltas);
  RlWriteJson(strval($args['result-file']), [
    'success' => true,
    'workerId' => intval($args['worker-id']),
    'episode' => intval($args['episode-number']),
    'episodeCount' => $episodeCount,
    'summaries' => $summaries,
    'summary' => $summaries[count($summaries) - 1] ?? [],
    'delta' => $delta,
    'strategyDelta' => $strategyDelta,
    'replays' => $replays,
    'replay' => !empty($replays) ? $replays[count($replays) - 1]['replay'] : null,
  ]);
}

function RlRunSequential($args) {
  mt_srand(intval($args['seed']));
  $deckText = file_get_contents($args['deck-file']);
  $runId = date('Ymd-His');
  $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'runs' . DIRECTORY_SEPARATOR . $runId;
  $ckptDir = $baseDir . DIRECTORY_SEPARATOR . 'checkpoints';
  $replayDir = $baseDir . DIRECTORY_SEPARATOR . 'replays';
  RlEnsureDir($baseDir);
  RlEnsureDir($ckptDir);
  RlEnsureDir($replayDir);

  $policy = trim(strval($args['checkpoint'])) !== ''
    ? RlTabularPolicy::load(strval($args['checkpoint']), $args['max-actions'], $args['temperature'], $args['learning-rate'])
    : new RlTabularPolicy($args['max-actions'], $args['temperature'], $args['learning-rate']);
  RlEnsureStrategyPolicy($policy, $args['strategy-mode']);
  $GLOBALS['rlStrategyMode'] = $policy->strategyMode;
  $GLOBALS['rlStateKeyVersion'] = $policy->stateKeyVersion;
  $frozenPool = [];
  $completedSteps = 0;
  $timedOutEpisodes = 0;
  $timeoutReplayPath = null;
  $trainStart = microtime(true);
  $episodeSummaries = [];

  for ($ep = 0; $ep < intval($args['episodes']); ++$ep) {
    $epNumber = $ep + 1;
    $epSeed = intval($args['seed']) + $ep;
    $opponent = (!empty($frozenPool) && ($ep % 2 === 1)) ? $frozenPool[mt_rand(0, count($frozenPool) - 1)] : $policy;
    $captureReplay = $epNumber === intval($args['episodes']);
    $result = RlRunEpisode($args, $deckText, $policy, $opponent, $runId, $epNumber, $epSeed, $captureReplay, $opponent === $policy);
    $policy->applyDelta($result['p1Delta'] ?? []);
    if ($policy->strategyPolicy instanceof RlTabularPolicy) $policy->strategyPolicy->applyDelta($result['p1StrategyDelta'] ?? []);
    if ($opponent === $policy) $policy->applyDelta($result['p2Delta'] ?? []);
    if ($opponent === $policy && $policy->strategyPolicy instanceof RlTabularPolicy) $policy->strategyPolicy->applyDelta($result['p2StrategyDelta'] ?? []);

    if (!empty($result['timedOut'])) ++$timedOutEpisodes;
    $completedSteps += intval($result['steps']);
    $episodeSummaries[] = [
      'episode' => $epNumber,
      'seed' => $epSeed,
      'winner' => intval($result['winner']),
      'reward' => floatval($result['reward']),
      'steps' => intval($result['steps']),
      'timedOut' => !empty($result['timedOut']),
      'elapsedMs' => intval($result['elapsedMs']),
    ];

    if (!empty($result['timedOut']) && $timeoutReplayPath === null && is_array($result['replay'] ?? null)) {
      $timeoutReplayPath = $replayDir . DIRECTORY_SEPARATOR . 'timeout_episode_' . sprintf('%04d', $epNumber) . '.json';
      RlWriteJson($timeoutReplayPath, $result['replay']);
    }
    if ($captureReplay && is_array($result['replay'] ?? null)) {
      RlWriteJson($replayDir . DIRECTORY_SEPARATOR . 'episode_' . sprintf('%04d', $epNumber) . '.json', $result['replay']);
    }
    if (($epNumber % intval($args['checkpoint-every']) === 0) || $epNumber === intval($args['episodes'])) {
      RlWriteCheckpoint($ckptDir, $epNumber, $policy, $frozenPool);
    }
    if (intval($args['log-every']) > 0 && (($epNumber % intval($args['log-every']) === 0) || $epNumber === intval($args['episodes']))) {
      RlPrintProgress($args, $policy, $epNumber, intval($result['steps']), RlEpisodeOutcome($result), $timedOutEpisodes, $completedSteps, $trainStart);
    }
  }

  $totalElapsedMs = intval(round((microtime(true) - $trainStart) * 1000));
  $runConfig = RlBaseRunConfig($args, $baseDir, $replayDir, $timeoutReplayPath, $completedSteps, $timedOutEpisodes, $totalElapsedMs, $episodeSummaries);
  RlWriteJson($baseDir . DIRECTORY_SEPARATOR . 'run_config.json', $runConfig);
  echo json_encode(['success' => true, 'runDir' => $baseDir, 'latestCheckpoint' => $ckptDir . DIRECTORY_SEPARATOR . 'latest.json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function RlQuoteArg($value) {
  return '"' . str_replace('"', '\\"', strval($value)) . '"';
}

function RlWorkerCommand($args, $runId, $policyPath, $resultPath, $episodeNumber, $epSeed, $workerId, $episodeCount, $captureReplay) {
  $parts = [
    RlQuoteArg(PHP_BINARY),
    RlQuoteArg(__FILE__),
    '--worker',
    '--root', RlQuoteArg($args['root']),
    '--deck-file', RlQuoteArg($args['deck-file']),
    '--policy-file', RlQuoteArg($policyPath),
    '--result-file', RlQuoteArg($resultPath),
    '--run-id', RlQuoteArg($runId),
    '--episodes', strval(intval($episodeCount)),
    '--episode-number', strval(intval($episodeNumber)),
    '--episode-seed', strval(intval($epSeed)),
    '--worker-episodes', strval(intval($episodeCount)),
    '--worker-id', strval(intval($workerId)),
    '--max-steps', strval(intval($args['max-steps'])),
    '--max-turns', strval(intval($args['max-turns'])),
    '--max-actions', strval(intval($args['max-actions'])),
    '--learning-rate', strval(floatval($args['learning-rate'])),
    '--temperature', strval(floatval($args['temperature'])),
    '--epsilon', strval(floatval($args['epsilon'])),
    '--timeout-reward', strval(floatval($args['timeout-reward'])),
    '--tactical-terminal-weight', strval(floatval($args['tactical-terminal-weight'])),
    '--aggro-leader-damage-reward', strval(floatval($args['aggro-leader-damage-reward'])),
    '--control-leader-damage-reward', strval(floatval($args['control-leader-damage-reward'])),
    '--control-enemy-threat-reward', strval(floatval($args['control-enemy-threat-reward'])),
    '--control-own-threat-penalty', strval(floatval($args['control-own-threat-penalty'])),
    '--tactical-no-state-change-penalty', strval(floatval($args['tactical-no-state-change-penalty'])),
    '--strategy-mode', RlQuoteArg($args['strategy-mode']),
  ];
  if ($args['memory-only'] === true) $parts[] = '--memory-only';
  if ($args['memory-only'] === false) $parts[] = '--disk-games';
  if ($captureReplay) $parts[] = '--capture-replay';
  return implode(' ', $parts);
}

function RlRunParallel($args) {
  mt_srand(intval($args['seed']));
  $runId = date('Ymd-His');
  $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'runs' . DIRECTORY_SEPARATOR . $runId;
  $ckptDir = $baseDir . DIRECTORY_SEPARATOR . 'checkpoints';
  $replayDir = $baseDir . DIRECTORY_SEPARATOR . 'replays';
  $workerDir = $baseDir . DIRECTORY_SEPARATOR . 'workers';
  RlEnsureDir($baseDir);
  RlEnsureDir($ckptDir);
  RlEnsureDir($replayDir);
  RlEnsureDir($workerDir);

  $policy = trim(strval($args['checkpoint'])) !== ''
    ? RlTabularPolicy::load(strval($args['checkpoint']), $args['max-actions'], $args['temperature'], $args['learning-rate'])
    : new RlTabularPolicy($args['max-actions'], $args['temperature'], $args['learning-rate']);
  RlEnsureStrategyPolicy($policy, $args['strategy-mode']);
  $GLOBALS['rlStrategyMode'] = $policy->strategyMode;
  $GLOBALS['rlStateKeyVersion'] = $policy->stateKeyVersion;
  $frozenPool = [];
  $completedSteps = 0;
  $timedOutEpisodes = 0;
  $timeoutReplayPath = null;
  $trainStart = microtime(true);
  $episodeSummaries = [];
  $nextEpisode = 1;
  $nextCheckpointEpisode = max(1, intval($args['checkpoint-every']));

  while ($nextEpisode <= intval($args['episodes'])) {
    $batchStart = $nextEpisode;
    $batchSize = min(intval($args['workers']) * intval($args['worker-episodes']), intval($args['episodes']) - $nextEpisode + 1);
    $workerCount = intval(ceil($batchSize / max(1, intval($args['worker-episodes']))));
    $policyPath = $workerDir . DIRECTORY_SEPARATOR . 'policy_batch_' . sprintf('%04d', $batchStart) . '.json';
    RlWriteJson($policyPath, $policy->payload());
    $workers = [];

    for ($i = 0; $i < $workerCount; ++$i) {
      $epNumber = $nextEpisode + ($i * intval($args['worker-episodes']));
      $epSeed = intval($args['seed']) + $epNumber - 1;
      $episodeCount = min(intval($args['worker-episodes']), intval($args['episodes']) - $epNumber + 1);
      $lastWorkerEpisode = $epNumber + $episodeCount - 1;
      $resultPath = $workerDir . DIRECTORY_SEPARATOR . 'result_episode_' . sprintf('%04d', $epNumber) . '_' . sprintf('%04d', $lastWorkerEpisode) . '.json';
      $captureReplay = $lastWorkerEpisode === intval($args['episodes']);
      $cmd = RlWorkerCommand($args, $runId, $policyPath, $resultPath, $epNumber, $epSeed, $i + 1, $episodeCount, $captureReplay);
      $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
      ];
      $proc = proc_open($cmd, $descriptors, $pipes, RegressionRepoRoot());
      if (!is_resource($proc)) RlFail('Failed to start worker for episode ' . $epNumber);
      fclose($pipes[0]);
      $workers[] = ['proc' => $proc, 'pipes' => $pipes, 'episode' => $epNumber, 'lastEpisode' => $lastWorkerEpisode, 'resultPath' => $resultPath];
    }

    foreach ($workers as $worker) {
      $stdout = stream_get_contents($worker['pipes'][1]);
      $stderr = stream_get_contents($worker['pipes'][2]);
      fclose($worker['pipes'][1]);
      fclose($worker['pipes'][2]);
      $exitCode = proc_close($worker['proc']);
      if ($exitCode !== 0) {
        RlFail('Worker episode ' . intval($worker['episode']) . ' failed with exit ' . $exitCode . ($stderr !== '' ? (': ' . $stderr) : ($stdout !== '' ? (': ' . $stdout) : '')));
      }
      if (!is_file($worker['resultPath'])) RlFail('Worker episode ' . intval($worker['episode']) . ' did not write a result file.');
      $payload = json_decode(file_get_contents($worker['resultPath']), true);
      if (!is_array($payload) || empty($payload['success'])) RlFail('Worker episode ' . intval($worker['episode']) . ' wrote an invalid result.');
      $policy->applyDelta($payload['delta'] ?? []);
      $summaries = is_array($payload['summaries'] ?? null) ? $payload['summaries'] : [];
      if ($policy->strategyPolicy instanceof RlTabularPolicy) {
        $policy->strategyPolicy->applyDelta($payload['strategyDelta'] ?? []);
      }
      $replays = is_array($payload['replays'] ?? null) ? $payload['replays'] : [];
      $replaysByEpisode = [];
      foreach ($replays as $replayPayload) {
        if (!is_array($replayPayload)) continue;
        $replaysByEpisode[intval($replayPayload['episode'] ?? 0)] = $replayPayload;
      }
      foreach ($summaries as $summary) {
        if (!is_array($summary)) continue;
        if (!empty($summary['timedOut'])) ++$timedOutEpisodes;
        $completedSteps += intval($summary['steps'] ?? 0);
        $episodeSummaries[] = $summary;
        $summaryEpisode = intval($summary['episode'] ?? 0);
        $replayPayload = $replaysByEpisode[$summaryEpisode] ?? null;
        $replay = is_array($replayPayload['replay'] ?? null) ? $replayPayload['replay'] : null;
        if (!empty($summary['timedOut']) && $timeoutReplayPath === null && $replay !== null) {
          $timeoutReplayPath = $replayDir . DIRECTORY_SEPARATOR . 'timeout_episode_' . sprintf('%04d', $summaryEpisode) . '.json';
          RlWriteJson($timeoutReplayPath, $replay);
        }
        if ($summaryEpisode === intval($args['episodes']) && $replay !== null) {
          RlWriteJson($replayDir . DIRECTORY_SEPARATOR . 'episode_' . sprintf('%04d', intval($args['episodes'])) . '.json', $replay);
        }
        if (intval($args['log-every']) > 0 && (($summaryEpisode % intval($args['log-every']) === 0) || $summaryEpisode === intval($args['episodes']))) {
          RlPrintProgress($args, $policy, $summaryEpisode, intval($summary['steps'] ?? 0), RlEpisodeOutcome($summary), $timedOutEpisodes, $completedSteps, $trainStart);
        }
      }
    }

    $nextEpisode += $batchSize;
    $lastCompleted = $nextEpisode - 1;
    if ($lastCompleted >= $nextCheckpointEpisode || $lastCompleted === intval($args['episodes'])) {
      RlWriteCheckpoint($ckptDir, $lastCompleted, $policy, $frozenPool);
      while ($nextCheckpointEpisode <= $lastCompleted) {
        $nextCheckpointEpisode += max(1, intval($args['checkpoint-every']));
      }
    }
  }

  usort($episodeSummaries, fn($a, $b) => intval($a['episode'] ?? 0) <=> intval($b['episode'] ?? 0));
  $totalElapsedMs = intval(round((microtime(true) - $trainStart) * 1000));
  $runConfig = RlBaseRunConfig($args, $baseDir, $replayDir, $timeoutReplayPath, $completedSteps, $timedOutEpisodes, $totalElapsedMs, $episodeSummaries);
  $runConfig['parallel'] = ['workers' => intval($args['workers']), 'workerEpisodes' => intval($args['worker-episodes']), 'mode' => 'batched-frozen-policy-rollout'];
  RlWriteJson($baseDir . DIRECTORY_SEPARATOR . 'run_config.json', $runConfig);
  echo json_encode(['success' => true, 'runDir' => $baseDir, 'latestCheckpoint' => $ckptDir . DIRECTORY_SEPARATOR . 'latest.json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

$args = RlParseArgs($argv);
$GLOBALS['rlStateKeyVersion'] = RlDefaultStateKeyVersion($args['root']);
if (!empty($args['worker'])) {
  RlRunWorker($args);
  exit(0);
}
if (trim(strval($args['deck-file'])) === '') RlFail('--deck-file is required.');
if (!is_file($args['deck-file'])) RlFail('Deck file not found: ' . $args['deck-file']);
if (intval($args['workers']) > 1) {
  RlRunParallel($args);
} else {
  RlRunSequential($args);
}
