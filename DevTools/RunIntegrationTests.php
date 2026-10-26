<?php

require_once __DIR__ . '/../Core/EngineActionRunner.php';

function RunnerParseArgs($argv) {
  $args = [
    'root' => null,
    'test' => null,
    'updateSnapshots' => false,
    'verbose' => false,
    'auditNoops' => false,
    'maxFailures' => null,
    'log' => null,
    'compact' => false,
  ];

  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--root=')) $args['root'] = substr($arg, 7);
    elseif (str_starts_with($arg, '--test=')) $args['test'] = substr($arg, 7);
    elseif ($arg === '--update-snapshots') $args['updateSnapshots'] = true;
    elseif ($arg === '--verbose') $args['verbose'] = true;
    elseif ($arg === '--audit-noops') $args['auditNoops'] = true;
    elseif (str_starts_with($arg, '--max-failures=')) {
      $value = substr($arg, 15);
      $limit = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
      if (!preg_match('/^[1-9][0-9]*$/D', $value) || $limit === false) {
        throw new InvalidArgumentException('--max-failures requires a positive integer within the PHP integer range.');
      }
      $args['maxFailures'] = $limit;
    } elseif (str_starts_with($arg, '--max-failures')) {
      throw new InvalidArgumentException('Use --max-failures=N with a positive integer.');
    } elseif (str_starts_with($arg, '--log=')) {
      $args['log'] = substr($arg, 6);
      if ($args['log'] === '' || str_contains($args['log'], "\0") || str_contains($args['log'], '://')) {
        throw new InvalidArgumentException('--log requires a local file path.');
      }
    } elseif (str_starts_with($arg, '--log')) {
      throw new InvalidArgumentException('Use --log=PATH with a new local file path.');
    } elseif ($arg === '--compact') $args['compact'] = true;
    elseif (str_starts_with($arg, '--compact')) {
      throw new InvalidArgumentException('--compact does not take a value.');
    }
  }

  if ($args['compact'] && $args['log'] === null) {
    throw new InvalidArgumentException('--compact requires --log=PATH so complete diagnostics are retained.');
  }
  return $args;
}

function RunnerWriteLogChunk($chunk) {
  $handle = $GLOBALS['integrationRunnerLogHandle'];
  $length = strlen($chunk);
  for ($offset = 0; $offset < $length;) {
    $written = @fwrite($handle, substr($chunk, $offset));
    if ($written === false || $written === 0) {
      fwrite(STDERR, "[ERROR] Unable to write the complete integration-test log; stopping.\n");
      exit(2);
    }
    $offset += $written;
  }
  if (!@fflush($handle)) {
    fwrite(STDERR, "[ERROR] Unable to flush the integration-test log; stopping.\n");
    exit(2);
  }
}

function RunnerCompactOutput($chunk, $final) {
  $state = &$GLOBALS['integrationRunnerCompactState'];
  $parts = explode("\n", $chunk);
  $output = '';
  foreach ($parts as $index => $part) {
    $available = max(0, 240 - strlen($state['line']));
    $state['line'] .= substr($part, 0, $available);
    if (strlen($part) > $available) $state['truncated'] = true;
    $complete = $index < count($parts) - 1;
    if (!$complete && !$final) continue;
    $line = rtrim($state['line'], "\r");
    if (preg_match('/^\[(FAIL|ERROR|STOP|SUMMARY|LOG)\]/', $line)) {
      $output .= $line . ($state['truncated'] ? ' ... [full diagnostic in log]' : '') . "\n";
    } elseif ($line !== '' && !preg_match('/^\[(PASS|STEP|QUEUE|INFO|NOOP)\]/', $line) && !$state['otherReported']) {
      $output .= "[DIAGNOSTIC] Additional output is retained in the log.\n";
      $state['otherReported'] = true;
    }
    $state['line'] = '';
    $state['truncated'] = false;
  }
  return $output;
}

function RunnerStartOutput($args) {
  if ($args['log'] === null) return;
  // Exclusive creation prevents a diagnostic path from overwriting a fixture or old evidence.
  $handle = @fopen($args['log'], 'xb');
  if ($handle === false) throw new RuntimeException('Cannot create --log file; use a new writable local path in an existing directory.');
  $GLOBALS['integrationRunnerLogHandle'] = $handle;
  $GLOBALS['integrationRunnerCompactState'] = ['line' => '', 'truncated' => false, 'otherReported' => false];
  ob_start(static function($chunk, $phase) use ($args) {
    RunnerWriteLogChunk($chunk);
    return $args['compact'] ? RunnerCompactOutput($chunk, ($phase & PHP_OUTPUT_HANDLER_FINAL) !== 0) : $chunk;
  }, 1);
  echo "[LOG] Full runner output: " . (realpath($args['log']) ?: $args['log']) . "\n";
}

function RunnerLoadMeta($fixtureDir) {
  $path = $fixtureDir . DIRECTORY_SEPARATOR . 'meta.json';
  if (!is_file($path)) return [];
  $data = json_decode(file_get_contents($path), true);
  return is_array($data) ? $data : [];
}

function RunnerUsage() {
  echo "Usage: php DevTools/RunIntegrationTests.php --root=<Root> [--test=<slug>] [--update-snapshots] [--verbose] [--audit-noops] [--max-failures=N] [--log=PATH] [--compact]\n";
  echo "  --max-failures=N stops before the next selected fixture after N failures; default is unlimited.\n";
  echo "  --log=PATH exclusively creates a complete runner-output log; parent directory must exist.\n";
  echo "  --compact requires --log and shows bounded failure headlines and totals; default console output is unchanged.\n";
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
  $gamestate = RegressionNormalizeNewlines(file_get_contents($initialFixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt'));
  foreach (($meta['initialGamestateReplacements'] ?? []) as $replacement) {
    if (!is_array($replacement)) throw new RuntimeException('initialGamestateReplacements entries must be objects.');
    $from = RegressionNormalizeNewlines(strval($replacement['from'] ?? ''));
    $to = RegressionNormalizeNewlines(strval($replacement['to'] ?? ''));
    if ($from === '' || substr_count($gamestate, $from) !== 1) {
      throw new RuntimeException('An initial gamestate replacement must match exactly one location.');
    }
    $gamestate = str_replace($from, $to, $gamestate);
  }
  return $gamestate;
}

function RunnerPrepareTempGame($rootName, $slug, $initialFixtureDir, $meta) {
  $initialGamestate = RunnerInitialGamestateText($initialFixtureDir, $meta);
  $gameName = RunnerTempGameName($slug);
  $gameDir = RegressionRepoRoot() . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName;
  RegressionEnsureDir($gameDir);
  file_put_contents(
    $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt',
    RegressionNormalizeGamestateTextForRoot($rootName, $initialGamestate)
  );
  // Initial assertions must observe this fresh fixture, including on the first test.
  // Loading before evaluation also prevents a prior fixture's globals from leaking in.
  EngineLoadRootRuntime($rootName);
  $GLOBALS['gameName'] = $gameName;
  ParseGamestate('./' . $rootName . '/');
  return [$gameName, $gameDir];
}

// The saved game state (what the next action will load), normalized the way the final-snapshot comparison does.
function RunnerAuditSnapshot($rootName, $gameName) {
  return RegressionNormalizeNewlines(RegressionNormalizeGamestateTextForComparison($rootName, RegressionCurrentGamestateText($rootName, $gameName)));
}

function RunnerReloadPersistedStateForAssertions($rootName, $gameName, $result) {
  // FaBSim serializes compacted zone coordinates without changing live arrays.
  // Match the next request/recorded assertion boundary after a successful write.
  // Rejections and non-writing actions retain their existing loaded diagnostics.
  if ($rootName !== 'FaBSim' || empty($result['success']) || empty($result['writeGamestate'])) return;
  $hasFlash = function_exists('GetFlashMessage') && function_exists('SetFlashMessage');
  $flash = $hasFlash ? GetFlashMessage() : null;
  $GLOBALS['gameName'] = strval($gameName);
  try {
    ParseGamestate('./' . $rootName . '/');
  } finally {
    // Flash can be request-local rather than a serialized gameplay field.
    if ($hasFlash) SetFlashMessage($flash);
  }
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

try {
  $args = RunnerParseArgs($argv);
} catch (Throwable $error) {
  fwrite(STDERR, "[ERROR] " . $error->getMessage() . "\n");
  RunnerUsage();
  exit(1);
}
if ($args['root'] === null) {
  RunnerUsage();
  exit(1);
}
try {
  RunnerStartOutput($args);
} catch (Throwable $error) {
  fwrite(STDERR, "[ERROR] " . $error->getMessage() . "\n");
  exit(1);
}

$gaBehaviorCases = [];
if ($args['root'] === 'GrandArchiveSim') {
  require_once __DIR__ . '/ga-fixtures/behavior-families/suites.php';
  $gaBehaviorCases = gaBehaviorCases();
  $formatCommand = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/create-prd-ability-fixtures.php')
    . ' --check-source-format' . ($args['test'] !== null ? ' ' . escapeshellarg('--fixture=' . $args['test']) : '') . ' 2>&1';
  exec($formatCommand, $formatOutput, $formatStatus);
  if ($formatStatus !== 0) {
    echo "[FAIL] Grand Archive source format\n" . implode("\n", $formatOutput) . "\n";
    exit(1);
  }
}

$fixtures = $args['test'] !== null ? [$args['test']] : RegressionListFixtures($args['root']);
if ($args['test'] === null) $fixtures = array_values(array_unique(array_merge($fixtures, array_keys($gaBehaviorCases))));
$discovered = count($fixtures); // The selected invocation; --test selects exactly one slug.
if (empty($fixtures)) {
  echo "No regression fixtures found for root {$args['root']}.\n";
  echo "[SUMMARY] Total: 0 | Pass: 0 | Fail: 0 | Discovered: 0 | Not run: 0 | Skipped: 0\n";
  exit(0);
}

$failures = 0;
$passes = 0;
$executed = 0;
$stoppedEarly = false;

foreach ($fixtures as $slug) {
  // Check here so every failure path, including either Grand Archive continue, obeys the cap.
  if ($args['maxFailures'] !== null && $failures >= $args['maxFailures']) {
    $stoppedEarly = true;
    break;
  }
  ++$executed;
  if (isset($gaBehaviorCases[$slug])) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/create-prd-ability-fixtures.php')
      . ' --families ' . escapeshellarg('--fixture=' . $slug) . ' 2>&1';
    $output = [];
    exec($command, $output, $status);
    if ($status === 0 && str_contains(implode("\n", $output), '[PASS] ' . $slug . ' (behavior family)')) {
      ++$passes;
      echo "[PASS] $slug (behavior family)\n";
    } else {
      ++$failures;
      echo "[FAIL] $slug (behavior family)\n" . implode("\n", $output) . "\n";
    }
    if ($args['verbose'] && $status === 0) echo implode("\n", $output) . "\n";
    continue;
  }
  $fixtureDir = RegressionFixtureDir($args['root'], $slug);
  if (!is_dir($fixtureDir)) {
    echo "[FAIL] {$slug}: fixture directory not found.\n";
    ++$failures;
    if ($args['test'] !== null) break;
    continue;
  }
  $meta = RunnerLoadMeta($fixtureDir);
  if ($args['root'] === 'GrandArchiveSim' && isset($meta['sourceScenario'])) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/create-prd-ability-fixtures.php')
      . ' --run-source ' . escapeshellarg('--fixture=' . $slug) . ' 2>&1';
    $output = [];
    exec($command, $output, $status);
    if ($status === 0 && str_contains(implode("\n", $output), '[PASS] ' . $slug . ' (source scenario)')) {
      ++$passes;
      echo "[PASS] $slug (source scenario)\n";
    } else {
      ++$failures;
      echo "[FAIL] $slug (source scenario)\n" . implode("\n", $output) . "\n";
    }
    if ($args['verbose'] && $status === 0) echo implode("\n", $output) . "\n";
    continue;
  }

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

  try {
    [$gameName, $gameDir] = RunnerPrepareTempGame($args['root'], $slug, $initialFixtureDir, $meta);
  } catch (Throwable $error) {
    echo "[FAIL] {$slug}: " . $error->getMessage() . "\n";
    ++$failures;
    if ($args['test'] !== null) break;
    continue;
  }
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

  $noopSteps = [];
  $beforeText = null;
  foreach ($actions as $stepIndex => $action) {
    if ($failed) break;
    $stepNumber = $stepIndex + 1;
    // --audit-noops: an accepted action that changes nothing is a silently ignored step (a hand index that moved, an answer to the
    // wrong player's decision...). It makes the rest of a scenario vacuous without failing anything.
    if ($args['auditNoops'] && empty($action['expectFailure'])) $beforeText = RunnerAuditSnapshot($args['root'], $gameName);
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

    if ($args['auditNoops'] && $beforeText !== null && empty($action['expectFailure'])) {
      if (RunnerAuditSnapshot($args['root'], $gameName) === $beforeText) $noopSteps[] = $stepNumber . ':' . ($action['cardID'] ?? '');
      $beforeText = null;
    }

    try {
      RunnerReloadPersistedStateForAssertions($args['root'], $gameName, $result);
    } catch (Throwable $error) {
      $failed = true;
      $failureMessage = "Step {$stepNumber} persisted-state reload failed: " . $error->getMessage();
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

  if ($args['auditNoops'] && !empty($noopSteps)) echo "[NOOP] {$slug}: " . implode(' ', $noopSteps) . "\n";

  if ($failed) {
    echo "[FAIL] {$label}: {$failureMessage}\n";
    ++$failures;
    if ($args['test'] !== null) break;
  } else {
    echo "[PASS] {$label}\n";
    ++$passes;
  }
}

$notRun = $discovered - $executed;
if ($stoppedEarly) {
  echo "[STOP] Reached --max-failures={$args['maxFailures']}; {$notRun} selected fixture(s) were not run.\n";
}
// Total keeps its existing meaning (fixtures run); this runner has no skip path.
echo "[SUMMARY] Total: {$executed} | Pass: {$passes} | Fail: {$failures} | Discovered: {$discovered} | Not run: {$notRun} | Skipped: 0\n";

exit($failures > 0 ? 1 : 0);
