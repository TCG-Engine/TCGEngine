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

function RunnerPrepareTempGame($rootName, $slug, $fixtureDir) {
  $gameName = RunnerTempGameName($slug);
  $gameDir = RegressionRepoRoot() . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName;
  RegressionEnsureDir($gameDir);
  copy($fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt', $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt');
  return [$gameName, $gameDir];
}

function RunnerCompareFinalSnapshot($fixtureDir, $rootName, $gameName, $updateSnapshots) {
  $expectedPath = $fixtureDir . DIRECTORY_SEPARATOR . 'expected_final_gamestate.txt';
  if (!is_file($expectedPath) && !$updateSnapshots) return [true, ''];

  $actual = RegressionCurrentGamestateText($rootName, $gameName);
  if ($updateSnapshots) {
    file_put_contents($expectedPath, $actual);
    return [true, 'Updated final snapshot.'];
  }

  $expected = file_get_contents($expectedPath);
  if (RegressionNormalizeNewlines($expected) !== RegressionNormalizeNewlines($actual)) {
    return [false, 'Final snapshot mismatch.'];
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

foreach ($fixtures as $slug) {
  $fixtureDir = RegressionFixtureDir($args['root'], $slug);
  if (!is_dir($fixtureDir)) {
    echo "[FAIL] {$slug}: fixture directory not found.\n";
    ++$failures;
    if ($args['test'] !== null) break;
    continue;
  }
  if (!is_file($fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt')) {
    echo "[FAIL] {$slug}: initial_gamestate.txt is missing.\n";
    ++$failures;
    if ($args['test'] !== null) break;
    continue;
  }

  [$gameName, $gameDir] = RunnerPrepareTempGame($args['root'], $slug, $fixtureDir);
  $actions = RegressionLoadActionsForFixture($fixtureDir);
  $assertions = RegressionLoadAssertionsForFixture($fixtureDir);
  $meta = RunnerLoadMeta($fixtureDir);
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

    if (!$result['success']) {
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
      echo "[STEP] {$slug} #{$stepNumber} mode={$action['mode']} player={$action['playerID']}\n";
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
  }
}

exit($failures > 0 ? 1 : 0);
