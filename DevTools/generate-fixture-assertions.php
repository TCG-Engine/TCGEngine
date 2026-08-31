<?php
/**
 * Auto-generate baseline assertions for GrandArchiveSim regression fixtures.
 *
 * For each fixture with empty assertions:
 *  1. Loads the initial gamestate
 *  2. Replays all actions
 *  3. Inspects the final state
 *  4. Generates assertions: DQ empty, key zone counts, and per-object
 *     CardID/Status/Damage/Counters/TurnEffects diffs (both players, every
 *     tracked zone) between the initial and final gamestate.
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

// Zones (both players) that hold per-object state worth diffing, mapped to their
// player-explicit accessor function. Keyed by the "Location" string GetZoneObject()
// expects after the p1/p2 prefix (e.g. GetZoneObject('p1Field-2')). Deck is deliberately
// excluded — like the existing zone_count logic below — because deck order/composition
// shifts on every draw/shuffle and diffing it produces noise unrelated to the mechanic
// under test, not a meaningful regression signal.
function FixtureZoneCaptureMap() {
    return [
        'Field' => 'GetField',
        'Hand' => 'GetHand',
        'Graveyard' => 'GetGraveyard',
        'Memory' => 'GetMemory',
        'Banish' => 'GetBanish',
        'Material' => 'GetMaterial',
        'TempZone' => 'GetTempZone',
        'Intent' => 'GetIntent',
        'GlobalEffects' => 'GetGlobalEffects',
        'Mastery' => 'GetMastery',
    ];
}

// Per Schemas/GrandArchiveSim/GameSchema.txt, only some zone object types declare these
// properties (e.g. only Field has Status/Damage; only Field/Banish/Mastery have Counters;
// only Field/Banish/Intent have TurnEffects). property_exists() below naturally restricts
// capture to whatever fields actually exist on a given object's generated class.
function FixtureTrackedProperties() {
    return ['CardID', 'Status', 'Damage', 'Counters', 'TurnEffects'];
}

// Mirrors RegressionEvaluateAssertion()'s card_property_equals comparison exactly
// (Core/RegressionTestFramework.php ~line 960-962): scalars compared as strings,
// everything else (arrays like Counters/TurnEffects) compared via json_encode.
function FixtureValueForCompare($value) {
    if ($value === null) return '';
    return is_scalar($value) ? strval($value) : json_encode($value);
}

// Snapshots CardID/Status/Damage/Counters/TurnEffects for every non-removed object in
// every tracked zone, for both players, keyed by the absolute mzId ("p1Field-2") that
// GetZoneObject() resolves independent of any viewer-relative playerID context.
//
// The id's numeric suffix MUST be the object's actual PHP array position, not its own
// ->mzIndex property. GetZoneObject() (GrandArchiveSim/ZoneAccessors.php) resolves
// "p1Hand-2" via `$zoneArr[2]` — it never reads ->mzIndex at all — and ->mzIndex can be
// stale/wrong for objects added to a zone through certain custom card-effect paths that
// don't go through the generated AddX() helper (e.g. a card returned to hand by some
// abilities keeps a leftover ->mzIndex from a prior zone/position). Keying by array
// position guarantees the generated mzId always matches what evaluation will actually
// look up, regardless of any such upstream ->mzIndex bugs.
function FixtureSnapshotObjectZones() {
    $map = FixtureZoneCaptureMap();
    $props = FixtureTrackedProperties();
    $snapshot = [];
    foreach ([1, 2] as $player) {
        foreach ($map as $location => $getterFn) {
            if (!function_exists($getterFn)) continue;
            $zone = $getterFn($player);
            if (!is_array($zone)) continue;
            foreach ($zone as $index => $obj) {
                if (!is_object($obj)) continue;
                if (method_exists($obj, 'Removed') && $obj->Removed()) continue;
                if (!property_exists($obj, 'CardID')) continue;
                $mzId = 'p' . $player . $location . '-' . $index;
                $fields = [];
                foreach ($props as $prop) {
                    if (property_exists($obj, $prop)) {
                        $fields[$prop] = $obj->$prop;
                    }
                }
                $snapshot[$mzId] = $fields;
            }
        }
    }
    return $snapshot;
}

// Diffs a "before replay" snapshot against an "after replay" snapshot and emits one
// card_property_equals assertion per (mzId, property) pair whose comparable value
// changed. A field present only in the final snapshot (new object, or a property that
// didn't exist on the object before) is diffed against '' — matching what
// RegressionEvaluateAssertion() itself would read for a not-yet-existing mzId/property.
function FixtureDiffObjectSnapshots($initialSnapshot, $finalSnapshot) {
    $assertions = [];
    foreach ($finalSnapshot as $mzId => $fields) {
        $initialFields = $initialSnapshot[$mzId] ?? [];
        foreach ($fields as $prop => $value) {
            $finalCompare = FixtureValueForCompare($value);
            $initialCompare = array_key_exists($prop, $initialFields)
                ? FixtureValueForCompare($initialFields[$prop])
                : '';
            if ($finalCompare !== $initialCompare) {
                $assertions[] = [
                    'mzId' => $mzId,
                    'property' => $prop,
                    'value' => $finalCompare,
                ];
            }
        }
    }
    return $assertions;
}

// The engine's deterministic RNG (Core/DeterministicRNG.php EngineDeterministicHashMaterial())
// hashes a snapshot of every zone returned by GetAllZones(), which is expressed in
// viewer-relative "my*/their*" names resolved through the ambient global $playerID — so
// any code that leaves $playerID touched (even "restored" to a coerced default) between
// here and the replay loop changes what "myHand" et al. mean during a mid-action random
// draw, and silently produces different random results than a clean run (e.g.
// RunIntegrationTests.php, which never touches $playerID before the first action). Save
// and restore the TRUE ambient state — including "was never set" — instead of coercing an
// unset global into 1, so these capture helpers are read-only from the replay's perspective.
function FixtureWithSavedPlayerID($fn) {
    $wasSet = array_key_exists('playerID', $GLOBALS);
    $saved = $GLOBALS['playerID'] ?? null;
    $result = $fn();
    if ($wasSet) {
        $GLOBALS['playerID'] = $saved;
    } else {
        unset($GLOBALS['playerID']);
    }
    return $result;
}

function FixtureGetZoneCounts() {
    return FixtureWithSavedPlayerID(function() {
        global $playerID;
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
        return $counts;
    });
}

function FixtureIsDQEmpty($player) {
    return FixtureWithSavedPlayerID(function() use ($player) {
        global $playerID;
        $playerID = $player;
        $zone = GetDecisionQueue($player);
        if (!is_array($zone)) return true;
        foreach ($zone as $obj) {
            if (is_object($obj) && (!method_exists($obj, 'Removed') || !$obj->Removed())) {
                return false;
            }
        }
        return true;
    });
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

    // Load the just-written initial gamestate into the runtime globals before capturing
    // anything. EngineRunAction() itself calls ParseGamestate() at the top of every action
    // (Core/EngineActionRunner.php ~line 1096), so without this call here the "initial"
    // snapshot below would read whatever globals were left over from the PREVIOUS fixture
    // in this loop (or nothing, on the first iteration) instead of this fixture's true
    // starting state.
    ParseGamestate('./' . $rootName . '/');

    // Capture initial state
    $initialCounts = FixtureGetZoneCounts();
    $initialObjectSnapshot = FixtureSnapshotObjectZones();

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

    // 2. Per-object field diffs across every tracked zone for both players: CardID,
    // Status, Damage, Counters, TurnEffects. This supersedes the old player-1-only,
    // CHAMPION-only, Damage-only special case and also catches non-champion allies,
    // player 2's board, and mechanic state stored in Status/Counters/TurnEffects.
    $finalObjectSnapshot = FixtureSnapshotObjectZones();
    $objectDiffs = FixtureDiffObjectSnapshots($initialObjectSnapshot, $finalObjectSnapshot);
    foreach ($objectDiffs as $diff) {
        $assertions[] = [
            'step' => $finalStep,
            'type' => 'card_property_equals',
            'mzId' => $diff['mzId'],
            'property' => $diff['property'],
            'value' => $diff['value'],
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
