<?php
// Local experiment runner: ten-game review batches, never publishes a live model.
define('RL_TRAINER_LIBRARY_ONLY', true);
require_once __DIR__ . '/train_selfplay_php.php';
$options = getopt('', ['config:', 'baseline:', 'output:', 'checkpoint:', 'start:', 'games:', 'seed:', 'evaluate', 'max-steps:', 'representation:', 'imitate', 'archetype-adapters', 'learner-archetype:', 'learner-heuristic:']);
foreach (['config', 'baseline', 'output'] as $required) if (empty($options[$required])) RlFail('Required: --config --baseline --output');
$learnerHeuristic = $options['learner-heuristic'] ?? '';
if (!in_array($learnerHeuristic, ['', 'current', 'frozen'], true)) RlFail('Unknown learner heuristic.');
if ($learnerHeuristic !== '' && !isset($options['evaluate'])) RlFail('Learner heuristic is evaluation-only.');
$start = intval($options['start'] ?? 0);
$games = intval($options['games'] ?? 10);
$seed = intval($options['seed'] ?? (isset($options['evaluate']) ? 1000001 : 10001));
if ($start < 0 || $start % 2 || $games < 2 || $games % 2) RlFail('Start must be nonnegative/even; games must be positive/even.');
if (isset($options['evaluate']) && empty($options['checkpoint'])) RlFail('Evaluation requires --checkpoint.');
if (isset($options['imitate']) && isset($options['evaluate'])) RlFail('Imitation and evaluation are mutually exclusive.');
$out = $options['output'];
if (file_exists($out)) RlFail('Output already exists; use a new batch directory.');
$configPath = realpath($options['config']);
if (!$configPath) RlFail('Config not found.');
$config = json_decode(file_get_contents($configPath), true, flags: JSON_THROW_ON_ERROR);
$decks = $config['decks'] ?? [];
if (count($decks) < 2) RlFail('At least two decks required.');
$deckTexts = []; $deckHashes = [];
foreach ($decks as $deck) {
  $path = str_starts_with($deck['path'], '/') ? $deck['path'] : dirname($configPath) . '/' . $deck['path'];
  $deckTexts[] = RlLoadDeckText('GrandArchiveSim', $path);
  $deckHashes[] = hash('sha256', end($deckTexts));
}
$baseline = realpath($options['baseline']);
if (!$baseline) RlFail('Baseline not found.');
$manifest = json_decode(file_get_contents($baseline . '/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($manifest['files'] as $file => $hash) if (hash_file('sha256', $baseline . '/' . $file) !== $hash) RlFail('Baseline changed: ' . $file);
require_once $baseline . '/BotLegalActions.php';
require_once $baseline . '/BotHeuristic.php';
// Balanced round-robin: every unordered deck pair, both learner seats, both deck roles.
$ring = range(0, count($decks) - 1);
if (count($ring) % 2) $ring[] = null;
$schedule = [];
for ($round = 0; $round < count($ring) - 1; ++$round) {
  for ($i = 0; $i < count($ring) / 2; ++$i) {
    $a = $ring[$i]; $b = $ring[count($ring) - 1 - $i];
    if ($a === null || $b === null) continue;
    foreach ([[$a, $b], [$b, $a]] as [$learner, $other]) foreach ([1, 2] as $seat) $schedule[] = [$learner, $other, $seat];
  }
  $last = array_pop($ring); array_splice($ring, 1, 0, [$last]);
}
$args = RlParseArgs(['trainer']);
$args['ga-training'] = true;
$args['ga-evaluate-heuristic'] = $learnerHeuristic;
$args['ga-imitation'] = isset($options['imitate']);
$args['max-steps'] = intval($options['max-steps'] ?? 2000);
$args['epsilon'] = 0.0; // On-policy softmax exploration; matches the policy-gradient objective.
$args['max-actions'] = 4096;
$representation = $options['representation'] ?? 'linear-v1';
if (!in_array($representation, ['linear-v1', 'compact-v2', 'public-v1'], true)) RlFail('Unknown representation.');
$policy = empty($options['checkpoint'])
  ? new RlTabularPolicy(4096, 1.0, 0.05, 'GrandArchiveSim:' . $representation, 'ga-semantic-v1')
  : RlTabularPolicy::load($options['checkpoint'], 4096, 1.0, 0.05);
if ($policy->strategyMode !== 'none' || $policy->policyRole !== 'full') RlFail('Requires a full GA policy without strategy shaping.');
if (!in_array($policy->stateKeyVersion, ['GrandArchiveSim:linear-v1', 'GrandArchiveSim:public-v1', 'GrandArchiveSim:compact-v2'], true) || $policy->actionKeyVersion !== 'ga-semantic-v1') RlFail('Requires a supported GA state / ga-semantic-v1 checkpoint.');
if (isset($options['representation']) && $policy->stateKeyVersion !== 'GrandArchiveSim:' . $representation) RlFail('Checkpoint representation differs; start fresh to change representation.');
if (isset($options['archetype-adapters'])) {
  if (empty($options['checkpoint'])) RlFail('Adapter training requires a pretrained shared checkpoint.');
  $policy->enableArchetypeAdapters();
}
// Identity comes only from the learner's configured deck, never the opponent or draw order.
$archetypes = [];
foreach ($decks as $deck) {
  $id = trim(strval($deck['archetypeId'] ?? $deck['name'] ?? ''));
  if ($id === '') RlFail('Each deck requires archetypeId or a descriptive archetype name.');
  $archetypes[] = $id;
}
if (isset($options['learner-archetype'])) {
  $requested = strval($options['learner-archetype']);
  $schedule = array_values(array_filter($schedule, fn($match) => $archetypes[$match[0]] === $requested));
  if (!$schedule) RlFail('Unknown learner archetype: ' . $requested);
}
$opponent = new RlTabularPolicy(4096, 1.0, 0.0, 'GrandArchiveSim:public-v1', 'ga-semantic-v1');
RlEnsureDir($out);
$runId = 'ga_' . bin2hex(random_bytes(6));
$report = ['mode' => isset($options['evaluate']) ? 'evaluation' : (isset($options['imitate']) ? 'imitation' : 'training'), 'start' => $start, 'seed' => $seed,
  'maxSteps' => $args['max-steps'], 'learnerHeuristic' => $learnerHeuristic ?: null,
  'config' => $config, 'deckHashes' => $deckHashes, 'baseline' => $manifest,
  'checkpointSource' => $options['checkpoint'] ?? null, 'games' => []];
// Detect edits during a batch; a checkpoint must correspond to one runtime.
$sourceFiles = array_merge(glob(__DIR__ . '/../../GrandArchiveSim/Bot*.php'), glob(__DIR__ . '/../../GrandArchiveSim/Custom/*.php'),
  [__FILE__, __DIR__ . '/train_selfplay_php.php', __DIR__ . '/ga_policy.php', __DIR__ . '/../TestAutomationBridge.php',
   __DIR__ . '/../../GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php']);
$sourceHashes = [];
foreach ($sourceFiles as $file) $sourceHashes[realpath($file)] = hash_file('sha256', $file);
$report['sourceHashes'] = $sourceHashes;
$initialHash = hash('sha256', json_encode($policy->payloadWithStrategy(true)));
for ($i = 0; $i < $games; ++$i) {
  foreach ($sourceHashes as $file => $hash) if (hash_file('sha256', $file) !== $hash) RlFail('Runtime changed during batch: ' . $file);
  $index = $start + $i;
  [$learner, $other, $seat] = $schedule[$index % count($schedule)];
  $args['learner-seat'] = $seat;
  $policy->activeArchetype = $archetypes[$learner];
  $pairSeed = $seed + intdiv($index, 2);
  mt_srand($pairSeed);
  $result = RlRunEpisode($args, $deckTexts[$seat === 1 ? $learner : $other], $deckTexts[$seat === 1 ? $other : $learner],
    $policy, $opponent, $runId, $index + 1, $pairSeed, true, false);
  $complete = !$result['failed'] && !$result['timedOut'] && $result['terminationReason'] === 'terminal';
  if (!isset($options['evaluate']) && $complete) $policy->applyDelta($result['p1Delta']);
  RlWriteJson($out . '/game-' . ($index + 1) . '.json', $result['replay']);
  $result['initialGamestateHash'] = $result['replay']['initialGamestateHash'];
  unset($result['replay'], $result['p1Delta'], $result['p2Delta'], $result['p1StrategyDelta'], $result['p2StrategyDelta']);
  $result['learnerSeat'] = $seat;
  $result['learnerArchetype'] = $archetypes[$learner];
  $result['learnerReward'] = $complete ? ($seat === 1 ? $result['reward'] : -$result['reward']) : null;
  $result['learnerDeck'] = $decks[$learner]['name'];
  $result['opponentDeck'] = $decks[$other]['name'];
  $result['completed'] = $complete;
  $report['games'][] = $result;
  echo 'Game ' . ($index + 1) . ': ' . $result['terminationReason'] . ', winner ' . $result['winner'] . ', steps ' . $result['steps'] . PHP_EOL;
  if (($i + 1) % 10 === 0 || $i + 1 === $games) {
    RlWriteJson($out . '/checkpoint-' . ($index + 1) . '.json', $policy->payload());
    RlWriteJson($out . '/latest.json', $policy->payload());
  }
  $report['initialPolicyHash'] = $initialHash;
  $report['finalPolicyHash'] = hash('sha256', json_encode($policy->payloadWithStrategy(true)));
  $report['completed'] = count(array_filter($report['games'], fn($g) => $g['completed']));
  $report['learnerWins'] = count(array_filter($report['games'], fn($g) => $g['completed'] && $g['winner'] === $g['learnerSeat']));
  RlWriteJson($out . '/report.json', $report);
}
if (isset($options['evaluate']) && $initialHash !== $report['finalPolicyHash']) throw new RuntimeException('Evaluation changed policy.');
echo 'Saved ' . $out . PHP_EOL;
