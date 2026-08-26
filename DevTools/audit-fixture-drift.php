<?php
// audit-fixture-drift.php — print a readable expected-vs-actual board diff for one fixture.
//
// Replays the fixture to get the ACTUAL final state, and parses expected_final_gamestate.txt to get
// the EXPECTED final state, then prints both as structured zone summaries so drift can be classified
// (set-normalization identity drift vs. behavior regression) without eyeballing raw gamestate text.
//
//   php DevTools/audit-fixture-drift.php --test=cleave-retaliate

require_once __DIR__ . '/../Core/EngineActionRunner.php';

$args = getopt('', ['root::', 'test::']);
$root = (string)($args['root'] ?? 'GrandArchiveSim');
$test = (string)($args['test'] ?? '');
if ($test === '') { fwrite(STDERR, "usage: php DevTools/audit-fixture-drift.php --test=<slug>\n"); exit(1); }

$fixtureDir = RegressionFixtureDir($root, $test);
$initialPath = $fixtureDir . DIRECTORY_SEPARATOR . 'initial_gamestate.txt';
$expectedPath = $fixtureDir . DIRECTORY_SEPARATOR . 'expected_final_gamestate.txt';
if (!is_file($initialPath)) { fwrite(STDERR, "missing $initialPath\n"); exit(1); }

function dumpLiveCount($zone): int {
    if (!is_array($zone)) return 0;
    $count = 0;
    foreach ($zone as $obj) {
        if (is_object($obj) && method_exists($obj, 'Removed') && $obj->Removed()) continue;
        $count++;
    }
    return $count;
}

function summarizeState(): string {
    global $playerID;
    $playerID = 1;
    $out = '';
    $out .= 'currentPlayer=' . intval($GLOBALS['currentPlayer'] ?? 0) . ' phase=' . GetCurrentPhase() . ' turnPlayer=' . intval(GetTurnPlayer()) . "\n";
    foreach ([1, 2] as $p) {
        $out .= "P{$p}: health=" . intval(HealthValue($p))
            . ' deck=' . dumpLiveCount(GetDeck($p))
            . ' hand=' . dumpLiveCount(GetHand($p))
            . ' field=' . dumpLiveCount(GetField($p))
            . ' graveyard=' . dumpLiveCount(GetGraveyard($p))
            . ' memory=' . dumpLiveCount(GetMemory($p))
            . ' material=' . dumpLiveCount(GetMaterial($p))
            . ' temp=' . dumpLiveCount(GetTempZone($p))
            . ' mastery=' . dumpLiveCount(GetMastery($p))
            . ' dq=' . dumpLiveCount(GetDecisionQueue($p)) . "\n";
        $field = GetField($p);
        if (is_array($field)) {
            $idx = 0;
            foreach ($field as $o) {
                if (!is_object($o) || (method_exists($o, 'Removed') && $o->Removed())) continue;
                $mz = method_exists($o, 'GetMzID') ? $o->GetMzID() : "p{$p}Field-{$idx}";
                $name = (function_exists('CardName') && $o->CardID !== '') ? CardName($o->CardID) : '?';
                $out .= "    {$mz} = {$o->CardID} ({$name}) dmg=" . intval($o->Damage) . ' status=' . intval($o->Status) . "\n";
                $idx++;
            }
        }
    }
    return rtrim($out);
}

EngineLoadRootRuntime($root);

// ── ACTUAL: replay from initial state ────────────────────────────────────────
$gameActual = 'audit_act_' . preg_replace('/[^a-z0-9_-]/i', '', $test) . '_' . uniqid();
$dirActual = RegressionRepoRoot() . "/$root/Games/$gameActual";
RegressionEnsureDir($dirActual);
file_put_contents($dirActual . '/Gamestate.txt', RegressionNormalizeGamestateTextForRoot($root, file_get_contents($initialPath)));

global $gameName;
$gameName = $gameActual;
ParseGamestate("./$root/");
foreach (RegressionLoadActionsForFixture($fixtureDir) as $action) {
    $result = EngineRunAction($action, $root, $gameActual, ['updateCache' => false, 'disableRecording' => true]);
    if (!$result['success']) { fwrite(STDERR, "replay action failed: " . ($result['message'] ?? '') . "\n"); break; }
}
$actualSummary = summarizeState();
RegressionDeleteDirRecursive($dirActual);

// ── EXPECTED: parse the recorded snapshot ────────────────────────────────────
$gameExpected = 'audit_exp_' . preg_replace('/[^a-z0-9_-]/i', '', $test) . '_' . uniqid();
$dirExpected = RegressionRepoRoot() . "/$root/Games/$gameExpected";
RegressionEnsureDir($dirExpected);
$expectedText = is_file($expectedPath) ? file_get_contents($expectedPath) : '';
if ($expectedText === '') { fwrite(STDERR, "missing $expectedPath\n"); exit(1); }
file_put_contents($dirExpected . '/Gamestate.txt', RegressionNormalizeGamestateTextForRoot($root, $expectedText));
$gameName = $gameExpected;
ParseGamestate("./$root/");
$expectedSummary = summarizeState();
RegressionDeleteDirRecursive($dirExpected);

echo "========== EXPECTED ==========\n$expectedSummary\n";
echo "==========  ACTUAL  ==========\n$actualSummary\n";
