<?php
// dump-fixture-state.php — print a readable per-step board dump for an integration fixture.
//
// Loads a fixture's initial_gamestate.txt, replays its actions.json, and prints zone counts + field
// object state (CardID, damage, ready/exhausted, owner/controller) after each step. Used to derive
// explicit assertions.json entries when converting snapshot-only fixtures into targeted assertions.
//
//   php DevTools/dump-fixture-state.php --root=GrandArchiveSim --test=aethercharge-attack
//
// Mirrors RunIntegrationTests.php's replay loop; same engine seam, no database.

require_once __DIR__ . '/../Core/EngineActionRunner.php';

$args = getopt('', ['root::', 'test::']);
$root = (string)($args['root'] ?? 'GrandArchiveSim');
$test = (string)($args['test'] ?? '');
if ($test === '') { fwrite(STDERR, "usage: php DevTools/dump-fixture-state.php --root=GrandArchiveSim --test=<slug>\n"); exit(1); }

$fixtureDir = RegressionFixtureDir($root, $test);
$initialPath = $fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt';
if (!is_file($initialPath)) { fwrite(STDERR, "missing $initialPath\n"); exit(1); }

$gameName = 'dump_' . preg_replace('/[^a-z0-9_-]/i', '', $test) . '_' . uniqid();
$gameDir = RegressionRepoRoot() . DIRECTORY_SEPARATOR . $root . DIRECTORY_SEPARATOR . 'Games' . DIRECTORY_SEPARATOR . $gameName;
RegressionEnsureDir($gameDir);
file_put_contents(
    $gameDir . DIRECTORY_SEPARATOR . 'Gamestate.txt',
    RegressionNormalizeGamestateTextForRoot($root, file_get_contents($initialPath))
);

function dumpLiveCount($zone): int {
    if (!is_array($zone)) return 0;
    $count = 0;
    foreach ($zone as $obj) {
        if (is_object($obj) && method_exists($obj, 'Removed') && $obj->Removed()) continue;
        $count++;
    }
    return $count;
}

function dumpState(string $root, string $gameName, int $step, string $label): void {
    global $playerID;
    $playerID = 1; // viewer perspective for GetMzID()
    echo "\n=== step {$step} {$label} ===\n";
    echo 'currentPlayer=' . (intval($GLOBALS['currentPlayer'] ?? 0)) . ' phase=' . GetCurrentPhase() . ' turnPlayer=' . intval(GetTurnPlayer()) . "\n";
    foreach ([1, 2] as $p) {
        $stats = 'health=' . intval(HealthValue($p))
            . ' memory=' . dumpLiveCount(GetMemory($p))
            . ' material=' . dumpLiveCount(GetMaterial($p))
            . ' hand=' . dumpLiveCount(GetHand($p))
            . ' graveyard=' . dumpLiveCount(GetGraveyard($p))
            . ' deck=' . dumpLiveCount(GetDeck($p))
            . ' mastery=' . dumpLiveCount(GetMastery($p))
            . ' decisionQueue=' . dumpLiveCount(GetDecisionQueue($p));
        echo "P{$p}: {$stats}\n";
        $dq = GetDecisionQueue($p);
        if (is_array($dq)) {
            $entries = [];
            foreach ($dq as $o) {
                if (!is_object($o) || (method_exists($o, 'Removed') && $o->Removed())) continue;
                $entries[] = $o->Type . ':' . $o->Param;
            }
            if ($entries) echo "    DQ: " . implode(' | ', $entries) . "\n";
        }
        $field = GetField($p);
        if (!is_array($field)) continue;
        $idx = 0;
        foreach ($field as $o) {
            if (!is_object($o) || (method_exists($o, 'Removed') && $o->Removed())) continue;
            $mz = method_exists($o, 'GetMzID') ? $o->GetMzID() : "p{$p}Field-{$idx}";
            $name = (function_exists('CardName') && $o->CardID !== '') ? CardName($o->CardID) : '?';
            echo "    [{$idx}] {$mz} = {$o->CardID} ({$name}) dmg=" . intval($o->Damage) . ' status=' . intval($o->Status) . ' owner=' . intval($o->Owner) . ' controller=' . intval($o->Controller) . "\n";
            $idx++;
        }
    }
}

// Load runtime + initial state for step 0.
EngineLoadRootRuntime($root);
global $gameName;
$gameName = $gameName;
ParseGamestate('./' . $root . '/');
dumpState($root, $gameName, 0, '(initial)');

$actions = RegressionLoadActionsForFixture($fixtureDir);
foreach ($actions as $i => $action) {
    $result = EngineRunAction($action, $root, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    if (!$result['success']) {
        echo "\n[FAIL] step " . ($i + 1) . ': ' . ($result['message'] ?: 'engine action failed') . "\n";
        break;
    }
    dumpState($root, $gameName, $i + 1, "(after action)");
}

RegressionDeleteDirRecursive($gameDir);
