<?php
// ── A RESTRICTED LOG LINE IS WRITTEN FOR REAL SEATS ONLY (SSOT #8, 2026-09-11) ─────────────────────────────
//
// SWULogPrivate / SWULogSeats are the only way a restricted (seat-scoped) game-log line is written — the
// visibility guard (gamelog_visibility_arg_test.php) fails any hand-built 'P' . $seat elsewhere. What they add
// over the raw AddGameLogEntry: they build the tag themselves, de-duplicate it, and REFUSE to write a line no
// one could see (a seat of 0 — an unset player — used to produce 'P0', written and never displayed).
//     php -d xdebug.mode=off SWUSim/DevTools/tests/gamelog_private_helpers_test.php
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

global $gGameLog;
$entries = function (): array { global $gGameLog; return ($gGameLog ?? '') === '' ? [] : explode('<NL>', $gGameLog); };

// ⚠ Judge by CONTENT, not by entry count: the log starts as a placeholder ('0'), which the FIRST write
// replaces rather than appends to — a count check passed even with the refusal removed (caught by mutation).
$has = function (string $needle) use ($entries): bool {
    foreach ($entries() as $e) if (str_contains($e, $needle)) return true;
    return false;
};
SWULogPrivate(0, 'DRAW', 'You drew nothing');
check(!$has('You drew nothing'), 'SWULogPrivate(0, …) writes nothing (a P0 line would be seen by nobody)');

SWULogPrivate(2, 'DRAW', 'You drew X');
$all = $entries(); $last = end($all);
check($last === 'DRAW|P2|You drew X', 'SWULogPrivate(2, …) writes one line visible to P2 only (got ' . var_export($last, true) . ')');

SWULogSeats([1, 3, 1, 0, -1], 'REVEAL', 'a private look');
$all = $entries(); $last = end($all);
check($last === 'REVEAL|P1,P3|a private look', 'SWULogSeats de-duplicates and drops non-seats (got ' . var_export($last, true) . ')');

SWULogSeats([0, -2], 'REVEAL', 'nobody sees this');
check(!$has('nobody sees this'), 'SWULogSeats with no real seat writes nothing');
echo "PASS\n";
