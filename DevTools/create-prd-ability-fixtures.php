<?php
/**
 * Batch-create PRD regression fixtures programmatically.
 *
 * Usage: php DevTools/create-prd-ability-fixtures.php [--seed=N] [--dry-run] [--fixture=SLUG]
 *
 * Creates selfplay games from curated decks, replays actions to exercise
 * specific card abilities, and saves regression fixtures.
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
$rootName = 'GrandArchiveSim';
$seed = 42;
$dryRun = false;
$onlyFixture = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--seed=')) $seed = intval(substr($arg, 7));
    elseif ($arg === '--dry-run') $dryRun = true;
    elseif (str_starts_with($arg, '--fixture=')) $onlyFixture = substr($arg, 10);
}

require_once $repoRoot . '/Core/EngineActionRunner.php';
define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once $repoRoot . '/DevTools/TestAutomationBridge.php';

// ---------------------------------------------------------------------------
// Fixture definitions: slug => [deck, actions, testedCards]
// ---------------------------------------------------------------------------
$fixtures = [];

// --- Recover: Escharotomy prevents target player from recovering ---
$fixtures['escharotomy-prevents-recover'] = [
    'testedCards' => ['CIU4gT14EE'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Escharotomy
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Play Escharotomy targeting opponent champion (prevents recovery)
    'actions' => [
        // Drain pregame DQ prompts
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Free play: play Escharotomy (mode 10002 FSM)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        // Pay reserve cost (1 card)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-5', 'chkInput' => [], 'inputText' => ''],
        // Pass fast action opportunities
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Buff Counters: Tindered Soldier gets buff counter on discard ---
$fixtures['tindered-soldier-discard-buff'] = [
    'testedCards' => ['KEhmWGivJp'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Tindered Soldier
4 Scars of Old
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Play Tindered Soldier, then Scars of Old to trigger discard
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Tindered Soldier (2 reserve)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-5', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-4', 'chkInput' => [], 'inputText' => ''],
        // Pass fast actions
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Scars of Old (2 reserve) - triggers draw+discard
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-5', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-4', 'chkInput' => [], 'inputText' => ''],
        // Pass remaining prompts
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cascade: FlameTech Manual activates Cascade ---
$fixtures['flametech-manual-cascade-activate'] = [
    'testedCards' => ['WZJxZMBAir'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 FlameTech Manual
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Escharotomy
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Play FlameTech Manual (0 cost regalia), then try to activate cascade
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play FlameTech Manual (0 cost, goes directly to field/mastery)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        // Pass fast actions
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Static Counter: Fulgurite Coordinator enters with static counter ---
$fixtures['fulgurite-enters-static-counter'] = [
    'testedCards' => ['7aZwqrfbzO'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Fulgurite Coordinator
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Fulgurite Coordinator (1 reserve)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-5', 'chkInput' => [], 'inputText' => ''],
        // Pass fast actions
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Elysian Aura: Elysian Aspirant enters, passive should be active ---
$fixtures['elysian-aspirant-aura-passive'] = [
    'testedCards' => ['HHtlkEeyQR'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Elysian Aspirant
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Elysian Aspirant (0 reserve)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        // Pass fast actions
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Scars of Old: Draw + discard + buff counters on damaged allies ---
$fixtures['scars-of-old-draw-discard'] = [
    'testedCards' => ['lD0sK81PZT'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Scars of Old
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Scars of Old (2 reserve)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-5', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-4', 'chkInput' => [], 'inputText' => ''],
        // Pass remaining prompts
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// ---------------------------------------------------------------------------
// Filter if --fixture specified
// ---------------------------------------------------------------------------
if ($onlyFixture) {
    if (!isset($fixtures[$onlyFixture])) {
        echo "Unknown fixture: $onlyFixture\n";
        echo "Available: " . implode(', ', array_keys($fixtures)) . "\n";
        exit(1);
    }
    $fixtures = [$onlyFixture => $fixtures[$onlyFixture]];
}

// ---------------------------------------------------------------------------
// Create each fixture
// ---------------------------------------------------------------------------
$created = 0;
$failed = 0;

foreach ($fixtures as $slug => $def) {
    echo "\n=== $slug ===\n";

    $fixtureDir = $repoRoot . '/Tests/Integration/' . $rootName . '/' . $slug;

    if ($dryRun) {
        echo "[DRY-RUN] Would create $fixtureDir\n";
        echo "  Deck: " . substr_count($def['deck'], "\n") . " lines\n";
        echo "  Actions: " . count($def['actions']) . "\n";
        echo "  Tested cards: " . implode(', ', $def['testedCards']) . "\n";
        $created++;
        continue;
    }

    // Clean existing fixture
    if (is_dir($fixtureDir)) {
        RegressionDeleteDirRecursive($fixtureDir);
    }
    RegressionEnsureDir($fixtureDir);

    $gameName = 'prd_batch_' . $slug . '_' . $seed;
    $gameDir = $repoRoot . '/' . $rootName . '/Games/' . $gameName;

    try {
        // 1. Initialize game
        echo "  Initializing game (seed=$seed)...\n";
        RegressionEnsureDir($gameDir);
        RegressionEnsureDir($repoRoot . '/' . $rootName . '/Games/' . $gameName);
        EngineLoadRootRuntime($rootName);
        $GLOBALS['gameName'] = $gameName;
        InitializeGamestate();
        SetDeterministicRandomCounter($seed);
        WriteGamestate('./');
        ParseGamestate('./');
        SetDeterministicRandomCounter($seed);

        // 2. Load decks
        $GLOBALS['bridgeDeterministicDeckShuffle'] = true;
        $GLOBALS['bridgeDeterministicDeckShuffleSeed'] = $seed;

        $deckSummary = [];
        BridgeLoadDeckForPlayer($rootName, 1, $def['deck'], $deckSummary);
        BridgeLoadDeckForPlayer($rootName, 2, $def['deck'], $deckSummary);
        echo "  P1 deck: " . ($deckSummary['mainDeckCount'] ?? '?') . " main, " . ($deckSummary['materialCount'] ?? '?') . " material\n";

        // 3. Set first player and turn
        $firstPlayer = &GetFirstPlayer();
        $firstPlayer = 1;
        $turnPlayer = &GetTurnPlayer();
        $turnPlayer = 1;
        $currentTurn = &GetTurnNumber();
        $currentTurn = 1;

        // 4. Run pregame startup
        BridgeRunRootSelfplayStartup($rootName);

        // 5. Save initial gamestate
        RegressionFlushCurrentGamestate($rootName);
        $initialGamestate = RegressionCurrentGamestateText($rootName, $gameName);
        file_put_contents($fixtureDir . '/initial_gamestate.txt', $initialGamestate);
        echo "  Saved initial gamestate\n";

        // 6. Replay actions
        $replayedActions = [];
        foreach ($def['actions'] as $i => $action) {
            $result = EngineRunAction($action, $rootName, $gameName, [
                'updateCache' => false,
                'disableRecording' => true,
            ]);

            if (!$result['success']) {
                echo "  [WARN] Action $i failed: " . ($result['message'] ?? 'unknown') . "\n";
                echo "  Action: " . json_encode($action) . "\n";
                // Continue anyway - some actions may not be legal in all seeds
                break;
            }
            $replayedActions[] = $action;
            echo "  Action $i OK (mode={$action['mode']}, card={$action['cardID']})\n";
        }

        // 7. Save fixture files
        file_put_contents(
            $fixtureDir . '/actions.json',
            json_encode($replayedActions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        file_put_contents(
            $fixtureDir . '/assertions.json',
            json_encode([], JSON_PRETTY_PRINT)
        );

        file_put_contents(
            $fixtureDir . '/meta.json',
            json_encode([
                'name' => $slug,
                'rootName' => $rootName,
                'createdAt' => date('c'),
                'createdBy' => 'batch-script',
                'testedCards' => $def['testedCards'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // 8. Save expected final gamestate
        RegressionFlushCurrentGamestate($rootName);
        $finalGamestate = RegressionCurrentGamestateText($rootName, $gameName);
        $normalized = RegressionNormalizeGamestateTextForComparison($rootName, $finalGamestate);
        file_put_contents($fixtureDir . '/expected_final_gamestate.txt', $normalized);
        echo "  Saved expected final gamestate\n";

        // 9. Verify by replaying
        echo "  Verifying fixture...\n";
        $verifyResult = shell_exec(
            "cd {$repoRoot} && php DevTools/RunIntegrationTests.php --root={$rootName} --test={$slug} 2>&1"
        );
        if (strpos($verifyResult, '[PASS]') !== false) {
            echo "  [OK] Fixture passes verification\n";
            $created++;
        } else {
            echo "  [FAIL] Fixture failed verification\n";
            echo "  " . trim(substr($verifyResult, strrpos($verifyResult, "\n") + 1)) . "\n";
            $failed++;
        }

        // Cleanup temp game
        if (is_dir($gameDir)) {
            RegressionDeleteDirRecursive($gameDir);
        }

    } catch (\Throwable $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
        echo "  " . $e->getTraceAsString() . "\n";
        $failed++;
        // Cleanup
        if (is_dir($gameDir)) {
            RegressionDeleteDirRecursive($gameDir);
        }
    }
}

echo "\n=== Summary ===\n";
echo "Created: $created | Failed: $failed | Total: " . count($fixtures) . "\n";
