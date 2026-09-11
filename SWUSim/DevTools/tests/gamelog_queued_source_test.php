<?php
// ── THE QUEUED-SOURCE STAMP MUST NOT LEAK A SKIPPED CONTINUATION'S SOURCE ────────────────────────────
//
// gamelog-updates #6 (2026-09-11): a UNIVERSAL continuation (DEAL_UNIT_DAMAGE, GIVE_ADVANTAGE, …) names no
// card, so the log source current when it is QUEUED is stamped (GameOnDecisionAdded, a per-seat SWUVar
// FIFO keyed by the exact param) and restored when it RUNS (GameBeforeCustomHandler).
//
// The trap this guards: a continuation that never runs — a declined "you may" skips its CUSTOM on PASS —
// leaves its stamp behind. If a LATER identical continuation from a DIFFERENT card then resolved, a plain
// FIFO would hand it the stale source and credit the wrong card. The list is therefore reconciled against
// the live queue on every add (surplus stamps for a param are dropped, oldest first).
//
// Driven directly (not through a card sequence) because the skip → identical-param → resolve shape is
// fragile to reproduce with real cards; the harness only builds the board. Run in the container:
//     php -d xdebug.mode=off SWUSim/DevTools/tests/gamelog_queued_source_test.php

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));
chdir(realpath(__DIR__ . '/../../..'));
$_GET['mode'] = 'cli';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t): void {} }
foreach (['./AccountFiles/AccountSessionAPI.php', './Core/HTTPLibraries.php', './Core/DeterministicRNG.php',
          './Core/CoreZoneModifiers.php', './Core/GameAuth.php', './SWUSim/ZoneClasses.php', './SWUSim/ZoneAccessors.php',
          './SWUSim/GeneratedCode/GeneratedCardDictionaries.php', './SWUSim/GamestateParser.php',
          './SWUSim/Tests/Framework/Assertions.php', './SWUSim/Tests/Framework/Cards.php',
          './SWUSim/Tests/Framework/CommonSetup.php', './SWUSim/Tests/Framework/GameStateBuilder.php',
          './SWUSim/Tests/Framework/GameTestAdapter.php', './SWUSim/Tests/Framework/SchemaTestRunner.php',
          './SWUSim/Tests/Framework/TestRunner.php'] as $f) include_once $f;

// A plain board, so the engine (SWUVars, decision queues, the hooks) is loaded and live.
ob_start();
SchemaTestRunner::runString("# Q\n## GIVEN\nCommonSetup: rrk/rrk\nP1OnlyActions: true\n## WHEN\n## EXPECT\nP1HANDCOUNT:0\n", 'queued-source');
ob_end_clean();
check(function_exists('GameOnDecisionAdded') && function_exists('GameBeforeCustomHandler'), 'the two hooks are defined');

$param = 'DEAL_UNIT_DAMAGE|1';
$liveCount = function (): int {
    $n = 0;
    foreach (GetDecisionQueue(1) as $d) if (empty($d->removed) && ($d->Type ?? '') === 'CUSTOM') $n++;
    return $n;
};

// 1. Card A queues the continuation (stamp A) — then it is SKIPPED (removed without running).
SetSWUVar('SWU_LOG_SRC', '1,SOR_095');
DecisionQueueController::AddDecision(1, 'CUSTOM', $param, 1);
foreach (GetDecisionQueue(1) as $d) if (($d->Type ?? '') === 'CUSTOM' && ($d->Param ?? '') === $param) $d->removed = true;
check($liveCount() === 0, 'card A\'s continuation was skipped (no longer live)');

// 2. Later, card B queues an IDENTICAL continuation (stamp B). The reconcile must drop A's stale stamp.
SetSWUVar('SWU_LOG_SRC', '1,SEC_080');
DecisionQueueController::AddDecision(1, 'CUSTOM', $param, 1);

// 3. B's continuation runs after the source was cleared — it must get B back, not the leaked A.
SetSWUVar('SWU_LOG_SRC', '');
GameBeforeCustomHandler(1, 'DEAL_UNIT_DAMAGE', $param);
check(GetSWUVar('SWU_LOG_SRC', '') === '1,SEC_080', 'the resolving continuation is credited to card B (got ' . var_export(GetSWUVar('SWU_LOG_SRC', ''), true) . ')');

// 4. And the list is empty afterwards (B's stamp consumed, A's never re-surfaces).
check(GetSWUVar('SWU_LOG_SRCQ_1', '') === '', 'no stamp is left behind');
echo "PASS\n";
