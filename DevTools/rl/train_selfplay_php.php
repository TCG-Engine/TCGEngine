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
    'checkpoint-every' => 25,
    'log-every' => 25,
    'memory-only' => null,
    'checkpoint' => '',
  ];

  $items = array_slice($argv, 1);
  for ($i = 0; $i < count($items); ++$i) {
    $arg = $items[$i];
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

  foreach (['episodes', 'seed', 'max-steps', 'max-turns', 'max-actions', 'checkpoint-every', 'log-every'] as $key) {
    $args[$key] = intval($args[$key]);
  }
  foreach (['learning-rate', 'temperature', 'epsilon', 'timeout-reward'] as $key) {
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

function RlStateKeyFromSnapshot($snapshot) {
  $zones = is_array($snapshot['zones'] ?? null) ? $snapshot['zones'] : [];
  $players = is_array($snapshot['players'] ?? null) ? $snapshot['players'] : [];
  $p1 = is_array($players['player1'] ?? null) ? $players['player1'] : [];
  $p2 = is_array($players['player2'] ?? null) ? $players['player2'] : [];
  $c1 = is_array($p1['champion'] ?? null) ? $p1['champion'] : [];
  $c2 = is_array($p2['champion'] ?? null) ? $p2['champion'] : [];
  $scalars = [
    'activePlayer' => intval($snapshot['activePlayer'] ?? 0),
    'turnPlayer' => intval($snapshot['turnPlayer'] ?? 0),
    'turnNumber' => intval($snapshot['turnNumber'] ?? 0),
    'phase' => strval($snapshot['phase'] ?? ''),
    'myHandCount' => intval($zones['myHandCount'] ?? 0),
    'theirHandCount' => intval($zones['theirHandCount'] ?? 0),
    'myDeckCount' => intval($zones['myDeckCount'] ?? 0),
    'theirDeckCount' => intval($zones['theirDeckCount'] ?? 0),
    'myMemoryCount' => intval($zones['myMemoryCount'] ?? 0),
    'theirMemoryCount' => intval($zones['theirMemoryCount'] ?? 0),
    'myMaterialCount' => intval($zones['myMaterialCount'] ?? 0),
    'theirMaterialCount' => intval($zones['theirMaterialCount'] ?? 0),
    'p1ChampionRemainingLife' => intval($c1['remainingLife'] ?? 0),
    'p2ChampionRemainingLife' => intval($c2['remainingLife'] ?? 0),
    'p1ChampionDamage' => intval($c1['damage'] ?? 0),
    'p2ChampionDamage' => intval($c2['damage'] ?? 0),
    'p1DQCount' => intval($p1['decisionQueue']['count'] ?? 0),
    'p2DQCount' => intval($p2['decisionQueue']['count'] ?? 0),
  ];
  ksort($scalars);
  return json_encode($scalars, JSON_UNESCAPED_SLASHES);
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

class RlTabularPolicy {
  public int $maxActions;
  public float $temperature;
  public float $learningRate;
  // Sparse map: stateKey => actionIndex => logit. Missing entries are zero.
  public array $logits = [];

  public function __construct($maxActions, $temperature, $learningRate) {
    $this->maxActions = intval($maxActions);
    $this->temperature = max(0.000001, floatval($temperature));
    $this->learningRate = floatval($learningRate);
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
    foreach ($episodeSteps as $step) {
      if (intval($step['turn_player'] ?? 0) !== intval($player)) continue;
      $stateKey = $step['state_key'];
      $actionIdx = intval($step['action_index']);
      if ($actionIdx >= $this->maxActions) continue;
      $probs = $this->actionProbabilities($stateKey, $step['legal_indices']);
      $this->ensureState($stateKey);
      foreach ($probs as [$idx, $p]) {
        $grad = ($idx === $actionIdx ? 1.0 : 0.0) - $p;
        $key = strval($idx);
        $this->logits[$stateKey][$key] = floatval($this->logits[$stateKey][$key] ?? 0.0) + $this->learningRate * floatval($terminalReward) * $grad;
        if (abs($this->logits[$stateKey][$key]) < 1.0e-12) unset($this->logits[$stateKey][$key]);
      }
      if (empty($this->logits[$stateKey])) unset($this->logits[$stateKey]);
    }
  }

  public function payload() {
    $logits = [];
    foreach ($this->logits as $stateKey => $values) {
      if (!empty($values)) $logits[$stateKey] = (object)$values;
    }
    return [
      'max_actions' => $this->maxActions,
      'temperature' => $this->temperature,
      'learning_rate' => $this->learningRate,
      'logits_format' => 'sparse_index_map',
      'logits' => $logits,
    ];
  }

  public static function fromPayload($payload, $fallbackMaxActions, $fallbackTemperature, $fallbackLearningRate) {
    if (!is_array($payload)) {
      RlFail('Checkpoint payload must be a JSON object.');
    }
    $obj = new RlTabularPolicy(
      intval($payload['max_actions'] ?? $fallbackMaxActions),
      floatval($payload['temperature'] ?? $fallbackTemperature),
      floatval($payload['learning_rate'] ?? $fallbackLearningRate)
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
    $obj = new RlTabularPolicy($this->maxActions, $this->temperature, $this->learningRate);
    $obj->logits = $this->logits;
    return $obj;
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

$args = RlParseArgs($argv);
if (trim(strval($args['deck-file'])) === '') RlFail('--deck-file is required.');
if (!is_file($args['deck-file'])) RlFail('Deck file not found: ' . $args['deck-file']);

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
$frozenPool = [];
$completedSteps = 0;
$timedOutEpisodes = 0;
$timeoutReplayPath = null;
$trainStart = microtime(true);
$episodeSummaries = [];

for ($ep = 0; $ep < intval($args['episodes']); ++$ep) {
  $epSeed = intval($args['seed']) + $ep;
  $gameName = 'rl_train_php_' . $runId . '_' . sprintf('%04d', $ep + 1) . '_' . $epSeed;
  $memoryArg = $args['memory-only'] === null ? 'auto' : ($args['memory-only'] ? '1' : '0');
  $startPayload = BridgeStartSelfplayGame($args['root'], $gameName, $epSeed, $deckText, $deckText, $memoryArg);
  if (empty($startPayload['success'])) RlFail('start-selfplay-game failed: ' . json_encode($startPayload));

  $snapshot = is_array($startPayload['snapshot'] ?? null) ? $startPayload['snapshot'] : BridgeSnapshotLoaded($args['root'], $gameName, 'summary');
  $initialFull = BridgeSnapshotLoaded($args['root'], $gameName, 'full');
  $legal = is_array($startPayload['legalActions'] ?? null) ? $startPayload['legalActions'] : ['actions' => []];
  $lastLegalActions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
  $mask = array_fill(0, count($lastLegalActions), 1);

  $episodeSteps = [];
  $replayActions = [];
  $stepTrace = [];
  $noOpKeys = [];
  $info = ['winner' => 0, 'isTerminal' => false, 'timedOut' => false, 'stepCount' => 0, 'gamestateHash' => strval($snapshot['gamestateHash'] ?? '')];
  $reward = 0.0;
  $done = false;
  $episodeStart = microtime(true);
  $opponent = (!empty($frozenPool) && ($ep % 2 === 1)) ? $frozenPool[mt_rand(0, count($frozenPool) - 1)] : $policy;

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
    $turnPlayer = intval($snapshot['turnPlayer'] ?? 1);
    $actingPolicy = $turnPlayer === 1 ? $policy : $opponent;
    $stateKey = RlStateKeyFromSnapshot($snapshot);
    $boundedMask = array_slice($mask, 0, intval($args['max-actions']));
    $legalIndices = RlCandidateIndices($boundedMask, $lastLegalActions, $noOpKeys, $stateKey);
    if (empty($legalIndices)) {
      $done = true;
      $reward = 0.0;
      $info['timedOut'] = true;
      break;
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
    $episodeSteps[] = ['state_key' => $stateKey, 'action_index' => $actionIndex, 'legal_indices' => $legalIndices, 'turn_player' => $turnPlayer];
    $replayActions[] = ['step' => count($replayActions) + 1, 'turnPlayer' => $turnPlayer, 'actionIndex' => $actionIndex, 'legalCount' => count($legalIndices), 'action' => $cleanAction];

    try {
      $stepPayload = RlStepLoaded($args['root'], $gameName, $cleanAction);
      $applyResult = $stepPayload['applyResult'];
      if (empty($applyResult['success'])) throw new Exception(strval($applyResult['message'] ?? 'engine action failed'));
      $snapshot = $stepPayload['snapshot'];
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
  $policy->updateEpisodeForPlayer($episodeSteps, 1, floatval($reward));
  if ($opponent === $policy) {
    $p2Reward = ($episodeTimedOut && empty($info['isTerminal'])) ? floatval($args['timeout-reward']) : -floatval($reward);
    $policy->updateEpisodeForPlayer($episodeSteps, 2, $p2Reward);
  }
  if ($episodeTimedOut) ++$timedOutEpisodes;
  $episodeWinner = intval($info['winner'] ?? 0);
  $outcome = $episodeTimedOut ? 'timeout' : ($episodeWinner > 0 ? ('winner=P' . $episodeWinner) : 'ended');
  $completedSteps += $steps;
  $episodeSummaries[] = [
    'episode' => $ep + 1,
    'seed' => $epSeed,
    'winner' => $episodeWinner,
    'reward' => floatval($reward),
    'steps' => $steps,
    'timedOut' => $episodeTimedOut,
    'elapsedMs' => $elapsedMs,
  ];

  $replay = [
    'episode' => $ep + 1,
    'seed' => $epSeed,
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

  if ($episodeTimedOut && $timeoutReplayPath === null) {
    $timeoutReplayPath = $replayDir . DIRECTORY_SEPARATOR . 'timeout_episode_' . sprintf('%04d', $ep + 1) . '.json';
    RlWriteJson($timeoutReplayPath, $replay);
  }

  if (($ep + 1) === intval($args['episodes'])) {
    RlWriteJson($replayDir . DIRECTORY_SEPARATOR . 'episode_' . sprintf('%04d', $ep + 1) . '.json', $replay);
  }

  if ((($ep + 1) % intval($args['checkpoint-every']) === 0) || ($ep + 1) === intval($args['episodes'])) {
    $ckptPath = $ckptDir . DIRECTORY_SEPARATOR . 'episode_' . sprintf('%04d', $ep + 1) . '.json';
    RlWriteJson($ckptPath, $policy->payload());
    RlWriteJson($ckptDir . DIRECTORY_SEPARATOR . 'latest.json', $policy->payload());
    $frozenPool[] = $policy->copy();
    if (count($frozenPool) > 5) array_shift($frozenPool);
  }

  if (intval($args['log-every']) > 0 && ((($ep + 1) % intval($args['log-every']) === 0) || ($ep + 1) === intval($args['episodes']))) {
    $elapsedS = max(0.000001, microtime(true) - $trainStart);
    $epsDone = $ep + 1;
    $epsPerS = $epsDone / $elapsedS;
    $stepsPerS = $completedSteps / $elapsedS;
    $epsRemaining = max(0, intval($args['episodes']) - $epsDone);
    $etaS = $epsPerS > 0 ? ($epsRemaining / $epsPerS) : 0;
    $etaText = date('Y-m-d H:i:s', time() + intval(round($etaS)));
    $pct = intval($args['episodes']) > 0 ? (100.0 * $epsDone / intval($args['episodes'])) : 100.0;
    echo sprintf(
      "[progress] ep %d/%d (%.1f%%) | epSteps %d | outcome %s | timeouts %d/%d | mem %.1fMB | states %d | elapsed %.1fs | eps/s %.3f | steps/s %.1f | avgSteps/ep %.1f | ETA %s\n",
      $epsDone,
      intval($args['episodes']),
      $pct,
      $steps,
      $outcome,
      $timedOutEpisodes,
      $epsDone,
      memory_get_usage(true) / 1048576.0,
      $policy->stateCount(),
      $elapsedS,
      $epsPerS,
      $stepsPerS,
      $completedSteps / $epsDone,
      $etaText
    );
  }

  RlCleanupGame($gameName);
  unset($episodeSteps, $replayActions, $stepTrace, $initialFull, $replay, $snapshot, $legal, $lastLegalActions, $mask);
  if (function_exists('gc_collect_cycles')) gc_collect_cycles();
}

$totalElapsedMs = intval(round((microtime(true) - $trainStart) * 1000));

$runConfig = [
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
  'memoryOnly' => $args['memory-only'],
  'trainer' => 'php',
  'checkpointEvery' => intval($args['checkpoint-every']),
  'finalReplay' => $replayDir . DIRECTORY_SEPARATOR . 'episode_' . sprintf('%04d', intval($args['episodes'])) . '.json',
  'firstTimeoutReplay' => $timeoutReplayPath,
  'summary' => [
    'totalSteps' => $completedSteps,
    'timedOutEpisodes' => $timedOutEpisodes,
    'elapsedMs' => $totalElapsedMs,
    'stepsPerSecond' => $totalElapsedMs > 0 ? ($completedSteps / ($totalElapsedMs / 1000.0)) : 0.0,
  ],
  'episodesSummary' => $episodeSummaries,
];
RlWriteJson($baseDir . DIRECTORY_SEPARATOR . 'run_config.json', $runConfig);

echo json_encode([
  'success' => true,
  'runDir' => $baseDir,
  'latestCheckpoint' => $ckptDir . DIRECTORY_SEPARATOR . 'latest.json',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
