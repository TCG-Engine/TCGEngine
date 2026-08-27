<?php
/**
 * Auto-generate baseline assertions for GrandArchiveSim regression fixtures.
 *
 * For each fixture with empty assertions:
 *  1. Loads the initial gamestate
 *  2. Replays all actions
 *  3. Inspects the final state
 *  4. Generates assertions: DQ empty, key zone counts, champion damage
 *
 * Usage: php DevTools/generate-fixture-assertions.php [--root=GrandArchiveSim] [--dry-run] [--slug=xxx]
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
$rootName = 'GrandArchiveSim';
$dryRun = false;
$onlySlug = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--root=')) $rootName = substr($arg, 7);
    elseif ($arg === '--dry-run') $dryRun = true;
    elseif (str_starts_with($arg, '--slug=')) $onlySlug = substr($arg, 7);
}

require_once $repoRoot . '/Core/EngineActionRunner.php';

// Find fixtures
$fixtureDir = $repoRoot . '/Tests/Integration/' . $rootName;
if (!is_dir($fixtureDir)) {
    echo "No fixture directory found for {$rootName}\n";
    exit(1);
}

$entries = array_filter(scandir($fixtureDir), function($e) use ($fixtureDir) {
    return is_dir($fixtureDir . '/' . $e) && $e !== '.' && $e !== '..';
});

// Bootstrap: load root-specific generated files directly (no action to avoid corrupting globals)
$folderPath = $rootName;
EngineLoadRootRuntime($folderPath);

function FixtureLoadActions($fixtureDir) {
    $path = $fixtureDir . '/actions.json';
    if (!is_file($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function FixtureLoadAssertions($fixtureDir) {
    $path = $fixtureDir . '/assertions.json';
    if (!is_file($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function FixtureCountNonEmpty($assertions) {
    $count = 0;
    foreach ($assertions as $a) {
        if (!empty($a['type'])) $count++;
    }
    return $count;
}

function FixtureGetChampionDamage() {
    global $playerID;
    $savedPID = $playerID ?? 1;
    $playerID = 1;
    $field = GetZone('myField');
    $playerID = $savedPID;
    if (!is_array($field)) return null;
    foreach ($field as $obj) {
        if (!is_object($obj)) continue;
        if (method_exists($obj, 'Removed') && $obj->Removed()) continue;
        if (!property_exists($obj, 'CardID')) continue;
        $cardType = function_exists('CardType') ? CardType($obj->CardID) : '';
        if ($cardType === 'CHAMPION') {
            return [
                'mzId' => 'myField-' . ($obj->mzIndex ?? '?'),
                'damage' => $obj->Damage ?? 0,
                'cardID' => $obj->CardID,
            ];
        }
    }
    return null;
}

function FixtureGetZoneCounts() {
    global $playerID;
    $savedPID = $playerID ?? 1;
    $playerID = 1;
    $zones = ['myHand', 'myField', 'myGraveyard', 'myDeck', 'myMemory',
              'theirHand', 'theirField', 'theirGraveyard', 'theirDeck', 'theirMemory'];
    $counts = [];
    foreach ($zones as $zoneName) {
        $zone = GetZone($zoneName);
        $count = 0;
        if (is_array($zone)) {
            foreach ($zone as $obj) {
                if (is_object($obj) && (!method_exists($obj, 'Removed') || !$obj->Removed())) {
                    $count++;
                }
            }
        }
        $counts[$zoneName] = $count;
    }
    $playerID = $savedPID;
    return $counts;
}

function FixtureIsDQEmpty($player) {
    global $playerID;
    $savedPID = $playerID ?? 1;
    $playerID = $player;
    $zone = GetDecisionQueue($player);
    $playerID = $savedPID;
    if (!is_array($zone)) return true;
    foreach ($zone as $obj) {
        if (is_object($obj) && (!method_exists($obj, 'Removed') || !$obj->Removed())) {
            return false;
        }
    }
    return true;
}

$processed = 0;
$skipped = 0;
$generated = 0;

foreach ($entries as $slug) {
    if ($onlySlug && $slug !== $onlySlug) continue;

    $dir = $fixtureDir . '/' . $slug;
    $actions = FixtureLoadActions($dir);
    $existing = FixtureLoadAssertions($dir);

    if (FixtureCountNonEmpty($existing) > 0) {
        $skipped++;
        continue;
    }

    if (empty($actions)) {
        echo "[SKIP] {$slug}: no actions\n";
        $skipped++;
        continue;
    }

    // Set up game
    $gameName = 'autoassert_' . $slug . '_' . uniqid();
    $gameDir = $repoRoot . '/' . $rootName . '/Games/' . $gameName;
    RegressionEnsureDir($gameDir);

    $initialPath = $dir . '/initial_gamestate.txt';
    if (!is_file($initialPath)) {
        echo "[SKIP] {$slug}: no initial_gamestate.txt\n";
        $skipped++;
        continue;
    }

    $initialGamestate = file_get_contents($initialPath);
    file_put_contents(
        $gameDir . '/Gamestate.txt',
        RegressionNormalizeGamestateTextForRoot($rootName, $initialGamestate)
    );

    // Capture initial state
    $initialCounts = FixtureGetZoneCounts();

    // Replay actions
    $failed = false;
    $lastStep = 0;
    foreach ($actions as $stepIndex => $action) {
        $stepNumber = $stepIndex + 1;
        $result = EngineRunAction($action, $rootName, $gameName, [
            'updateCache' => false,
            'disableRecording' => true,
        ]);
        if (!$result['success']) {
            echo "[FAIL] {$slug}: step {$stepNumber} failed: " . ($result['message'] ?? 'unknown') . "\n";
            $failed = true;
            break;
        }
        $lastStep = $stepNumber;
    }

    if ($failed) {
        RegressionDeleteDirRecursive($gameDir);
        $skipped++;
        continue;
    }

    // Generate assertions from final state
    $assertions = [];
    $finalStep = $lastStep;

    // 1. DQ empty for both players (only if actually empty - some fixtures end with pending decisions)
    $p1DQEmpty = FixtureIsDQEmpty(1);
    $p2DQEmpty = FixtureIsDQEmpty(2);
    if ($p1DQEmpty && $p2DQEmpty) {
        $assertions[] = [
            'step' => $finalStep,
            'type' => 'decision_queue_empty',
            'player' => 'all',
        ];
    } elseif ($p1DQEmpty) {
        $assertions[] = [
            'step' => $finalStep,
            'type' => 'decision_queue_empty',
            'player' => 1,
        ];
    } elseif ($p2DQEmpty) {
        $assertions[] = [
            'step' => $finalStep,
            'type' => 'decision_queue_empty',
            'player' => 2,
        ];
    }
    // If neither is empty, skip DQ assertion entirely (fixture ends mid-decision)

    // 2. Champion damage (read from FINAL state, not initial)
    $champion = FixtureGetChampionDamage();
    if ($champion && $champion['damage'] > 0) {
        $assertions[] = [
            'step' => $finalStep,
            'type' => 'card_property_equals',
            'mzId' => $champion['mzId'],
            'property' => 'Damage',
            'value' => strval($champion['damage']),
        ];
    }

    // 3. Key zone count changes (exclude deck draws)
    $finalCounts = FixtureGetZoneCounts();
    foreach ($finalCounts as $zone => $count) {
        $initial = $initialCounts[$zone] ?? 0;
        if ($count !== $initial && !in_array($zone, ['myDeck', 'theirDeck'])) {
            $assertions[] = [
                'step' => $finalStep,
                'type' => 'zone_count',
                'zone' => $zone,
                'viewerPlayerID' => 1,
                'value' => $count,
            ];
        }
    }

    // Write assertions
    $assertPath = $dir . '/assertions.json';
    if ($dryRun) {
        echo "[DRY-RUN] {$slug}: would write " . count($assertions) . " assertions\n";
        foreach ($assertions as $a) {
            $desc = $a['type'];
            if (isset($a['mzId'])) $desc .= " {$a['mzId']}.{$a['property']}={$a['value']}";
            if (isset($a['zone'])) $desc .= " zone={$a['zone']} count={$a['value']}";
            if (isset($a['player'])) $desc .= " player={$a['player']}";
            echo "  - {$desc} (step {$a['step']})\n";
        }
    } else {
        file_put_contents($assertPath, json_encode($assertions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        echo "[OK] {$slug}: wrote " . count($assertions) . " assertions\n";
        $generated++;
    }

    RegressionDeleteDirRecursive($gameDir);
    $processed++;
}

echo "\nDone. Processed: {$processed}, Skipped: {$skipped}, Generated: {$generated}\n";
