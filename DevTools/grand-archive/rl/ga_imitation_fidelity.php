<?php
// Evaluate a frozen model against recorded, public teacher observations; no engine or RNG.
define('RL_TRAINER_LIBRARY_ONLY', true);
require_once __DIR__ . '/../../rl/train_selfplay_php.php';
$options = getopt('', ['checkpoint:', 'replays:']);
if (empty($options['checkpoint']) || empty($options['replays'])) RlFail('Required: --checkpoint --replays (directory of teacher games)');
$policy = RlTabularPolicy::load($options['checkpoint'], 4096, 1, .05);
$before = $policy->payload();
$choices = 0; $agreement = 0; $probability = 0; $nll = 0;
foreach (glob($options['replays'] . '/game-*.json') as $file) {
  $replay = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
  foreach ($replay['actions'] as $step) {
    if (($step['heuristicPolicy'] ?? '') !== 'ga-teacher' || empty($step['observation']) || count($step['actionKeys'] ?? []) < 2) continue;
    $keys = $step['actionKeys'];
    $probs = $policy->actionProbabilities($step['observation'], array_keys($keys), $keys);
    $best = max(array_column($probs, 1));
    $ties = count(array_filter($probs, fn($v) => abs($v[1] - $best) < 1e-12));
    foreach ($probs as [$idx, $p]) if ($idx === $step['actionIndex']) {
      ++$choices;
      $probability += $p;
      $nll -= log(max(1e-30, $p));
      if (abs($p - $best) < 1e-12) $agreement += 1 / $ties;
    }
  }
}
if ($policy->payload() !== $before) throw new RuntimeException('Fidelity evaluation mutated model');
if (!$choices) RlFail('No teacher observations found.');
echo json_encode(['choices'=>$choices,'topAgreement'=>$agreement/$choices,'meanTeacherProbability'=>$probability/$choices,
  'crossEntropy'=>$nll/$choices,'note'=>'Use independent teacher seeds for held-out fidelity. Synthetic teacher choices may be absent from inference; inspect teacherMissing in collection reports.'], JSON_PRETTY_PRINT) . PHP_EOL;
