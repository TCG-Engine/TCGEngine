<?php

require_once __DIR__ . '/../Core/EngineActionRunner.php';

function RunnerParseArgs($argv) {
  $args = [
    'root' => null,
    'test' => null,
    'updateSnapshots' => false,
    'verbose' => false,
  ];

  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--root=')) $args['root'] = substr($arg, 7);
    elseif (str_starts_with($arg, '--test=')) $args['test'] = substr($arg, 7);
    elseif ($arg === '--update-snapshots') $args['updateSnapshots'] = true;
    elseif ($arg === '--verbose') $args['verbose'] = true;
  }

  return $args;
}

function RunnerLoadMeta($fixtureDir) {
  $path = $fixtureDir . DIRECTORY_SEPARATOR . 'meta.json';
  if (!is_file($path)) return [];
  $data = json_decode(file_get_contents($path), true);
  return is_array($data) ? $data : [];
}

function RunnerUsage() {
  echo "Usage: php DevTools/RunIntegrationTests.php --root=<Root> [--test=<slug>] [--update-snapshots] [--verbose]\n";
}

function RunnerTempGameName($slug) {
  return 'regression_' . $slug . '_' . uniqid();
}

function RunnerPendingDecisionSummary() {
  if (!function_exists('GetDecisionQueue')) return '';
  $pending = [];
  foreach ([1, 2] as $player) {
    foreach ((array)GetDecisionQueue($player) as $decision) {
      if (!is_object($decision) || !empty($decision->removed)) continue;
      $type = strval($decision->Type ?? '');
      $param = strval($decision->Param ?? '');
      $pending[] = "p{$player}:{$type}" . ($param === '' ? '' : "={$param}");
      break;
    }
  }
  return implode(' | ', $pending);
}

function RunnerInitialFixtureDir($rootName, $fixtureDir, $meta) {
  $baseSlug = strval($meta['baseFixture'] ?? '');
  if ($baseSlug === '') return $fixtureDir;
  if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $baseSlug)) {
    throw new RuntimeException("Invalid baseFixture '{$baseSlug}'.");
  }
  $baseDir = RegressionFixtureDir($rootName, $baseSlug);
  if (!is_dir($baseDir) || !is_file($baseDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt')) {
    throw new RuntimeException("Base fixture '{$baseSlug}' has no initial gamestate.");
  }
  return $baseDir;
}

function RunnerInitialGamestateText($initialFixtureDir, $meta) {
  $gamestate = file_get_contents($initialFixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt');
  foreach (($meta['initialGamestateReplacements'] ?? []) as $replacement) {
    if (!is_array($replacement)) throw new RuntimeException('initialGamestateReplacements entries must be objects.');
    $from = strval($replacement['from'] ?? '');
    $to = strval($replacement['to'] ?? '');
    if ($from === '' || substr_count($gamestate, $from) !== 1) {
      throw new RuntimeException('An initial gamestate replacement must match exactly one location.');
    }
    $gamestate = str_replace($from, $to, $gamestate);
  }
  return $gamestate;
}

function RunnerPrepareTempGame($rootName, $slug, $initialFixtureDir, $meta) {
  $gameName = RunnerTempGameName($slug);
  $gameDir = RegressionRepoRoot() . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName;
  RegressionEnsureDir($gameDir);
  $initialGamestate = RunnerInitialGamestateText($initialFixtureDir, $meta);
  file_put_contents(
    $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt',
    RegressionNormalizeGamestateTextForRoot($rootName, $initialGamestate)
  );
  return [$gameName, $gameDir];
}

function RunnerCompareFinalSnapshot($fixtureDir, $rootName, $gameName, $updateSnapshots) {
  $expectedPath = $fixtureDir . DIRECTORY_SEPARATOR . 'expected_final_gamestate.txt';
  if (!is_file($expectedPath) && !$updateSnapshots) return [true, ''];

  $actual = RegressionNormalizeGamestateTextForComparison($rootName, RegressionCurrentGamestateText($rootName, $gameName));
  if ($updateSnapshots) {
    file_put_contents($expectedPath, $actual);
    return [true, 'Updated final snapshot.'];
  }

  $expected = RegressionNormalizeGamestateTextForComparison($rootName, file_get_contents($expectedPath));
  if (RegressionNormalizeNewlines($expected) !== RegressionNormalizeNewlines($actual)) {
    return [false, RegressionFormatSnapshotDiff($expected, $actual)];
  }
  return [true, ''];
}

$args = RunnerParseArgs($argv);
if ($args['root'] === null) {
  RunnerUsage();
  exit(1);
}

$fixtures = $args['test'] !== null ? [$args['test']] : RegressionListFixtures($args['root']);
if (empty($fixtures)) {
  echo "No regression fixtures found for root {$args['root']}.\n";
  exit(0);
}

$failures = 0;
$passes = 0;
$executed = 0;

foreach ($fixtures as $slug) {
  ++$executed;
  $fixtureDir = RegressionFixtureDir($args['root'], $slug);
  if (!is_dir($fixtureDir)) {
    echo "[FAIL] {$slug}: fixture directory not found.\n";
    ++$failures;
    if ($args['test'] !== null) break;
    continue;
  }
  $meta = RunnerLoadMeta($fixtureDir);
  try {
    $initialFixtureDir = RunnerInitialFixtureDir($args['root'], $fixtureDir, $meta);
  } catch (Throwable $error) {
    echo "[FAIL] {$slug}: " . $error->getMessage() . "\n";
    ++$failures;
    if ($args['test'] !== null) break;
    continue;
  }
  if (!is_file($initialFixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt')) {
    echo "[FAIL] {$slug}: initial_gamestate.txt is missing.\n";
    ++$failures;
    if ($args['test'] !== null) break;
    continue;
  }

  [$gameName, $gameDir] = RunnerPrepareTempGame($args['root'], $slug, $initialFixtureDir, $meta);
  $actions = RegressionLoadActionsForFixture($fixtureDir);
  $assertions = RegressionLoadAssertionsForFixture($fixtureDir);
  $label = $meta['name'] ?? $slug;

  $failed = false;
  $failureMessage = '';

  [$initialAssertionsPassed, $initialAssertionMessage] = RegressionEvaluateAssertionsForStep($assertions, 0);
  if (!$initialAssertionsPassed) {
    $failed = true;
    $failureMessage = "Initial assertion failed: {$initialAssertionMessage}";
  }

  foreach ($actions as $stepIndex => $action) {
    if ($failed) break;
    $stepNumber = $stepIndex + 1;
    $result = EngineRunAction($action, $args['root'], $gameName, [
      'updateCache' => false,
      'disableRecording' => true,
    ]);

    $expectsFailure = !empty($action['expectFailure']);
    if ($expectsFailure && $result['success']) {
      $failed = true;
      $failureMessage = "Step {$stepNumber} unexpectedly succeeded; this fixture requires rejection.";
      break;
    }
    if (!$expectsFailure && !$result['success']) {
      $failed = true;
      $failureMessage = "Step {$stepNumber} failed: " . ($result['message'] ?: 'engine action failed');
      break;
    }

    [$assertionsPassed, $assertionMessage] = RegressionEvaluateAssertionsForStep($assertions, $stepNumber);
    if (!$assertionsPassed) {
      $failed = true;
      $failureMessage = "Step {$stepNumber} assertion failed: {$assertionMessage}";
      break;
    }

    if ($args['verbose']) {
      echo "[STEP] {$slug} #{$stepNumber} mode={$action['mode']} player={$action['playerID']}" . ($expectsFailure ? ' expected-rejection' : '') . "\n";
      $pendingSummary = RunnerPendingDecisionSummary();
      if ($pendingSummary !== '') echo "[QUEUE] {$pendingSummary}\n";
    }
  }

  if (!$failed) {
    [$snapshotPassed, $snapshotMessage] = RunnerCompareFinalSnapshot($fixtureDir, $args['root'], $gameName, $args['updateSnapshots']);
    if (!$snapshotPassed) {
      $failed = true;
      $failureMessage = $snapshotMessage;
    } elseif ($args['verbose'] && $snapshotMessage !== '') {
      echo "[INFO] {$slug}: {$snapshotMessage}\n";
    }
  }

  RegressionDeleteDirRecursive($gameDir);

  if ($failed) {
    echo "[FAIL] {$label}: {$failureMessage}\n";
    ++$failures;
    if ($args['test'] !== null) break;
  } else {
    echo "[PASS] {$label}\n";
    ++$passes;
  }
}

echo "[SUMMARY] Total: {$executed} | Pass: {$passes} | Fail: {$failures}\n";

exit($failures > 0 ? 1 : 0);
