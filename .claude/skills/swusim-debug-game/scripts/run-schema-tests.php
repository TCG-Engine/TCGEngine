<?php
// run-schema-tests.php — SWUSim schema-test runner for the swusim-debug-game skill.
//
// Replicates zzRegressionSWUSim.php's EXACT environment (animation stubs + full engine +
// framework), so it never suffers the two traps that make naïve runs lie:
//   • The web runner (curl zzRegressionSWUSim.php) hits a ~60s gateway timeout on the full
//     suite → HTTP 500 with an empty body (looks like a crash, isn't).
//   • zzRunSWUSimTests.php omits the QueueDamageAnimation/etc. stubs → any combat/damage
//     test fatals → ~1000 phantom failures.
//
// Run it INSIDE the swusim web container (its PHP, not the host's):
//   docker exec -w /var/www/html/TCGEngine swustats-swusim-web-server-1 \
//     php -d xdebug.mode=off .claude/skills/swusim-debug-game/scripts/run-schema-tests.php [args]
//
// Modes:
//   (no args)                    → run the FULL suite (authoritative regression count).
//   <relpath.md> [<relpath.md>…] → run ONLY those schema files (fast RED/GREEN iteration).
//     paths are repo-root-relative, e.g. SWUSim/Tests/Cases/sec/SEC069_….md
//
// Exit code: 0 = all passed, 1 = one or more failed.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Repo root = the nearest ancestor holding SWUSim/ + Core/ (robust to where under
// .claude/skills/ this script lives). Override with REPO_ROOT if needed.
$repo = getenv('REPO_ROOT') ?: (function () {
    $d = __DIR__;
    while ($d !== '/' && $d !== '' && !(is_dir("$d/SWUSim") && is_dir("$d/Core"))) $d = dirname($d);
    return $d;
})();
chdir($repo);

// ── Animation stubs (no-ops in the harness; same as zzRegressionSWUSim.php) ──
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t): void {} }

// ── Engine + framework includes (same chain as zzRegressionSWUSim.php) ──
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/Tests/Framework/Assertions.php';
include_once './SWUSim/Tests/Framework/Cards.php';
include_once './SWUSim/Tests/Framework/CommonSetup.php';
include_once './SWUSim/Tests/Framework/GameStateBuilder.php';
include_once './SWUSim/Tests/Framework/GameTestAdapter.php';
include_once './SWUSim/Tests/Framework/SchemaTestRunner.php';
include_once './SWUSim/Tests/Framework/TestRunner.php';

global $gameName, $playerID;
$gameName = 'test_runner';
$playerID = 1;

$files = array_slice($argv, 1);

if (empty($files)) {
    // Full-suite mode — TestRunner discovers every *.md via SchemaBasedTest.php.
    TestRunner::run(null, microtime(true), false);
    // TestRunner::run() renders HTML + does not set an exit code; the caller greps the summary.
    exit(0);
}

// Targeted mode — run only the named files. Plain text, sets an exit code.
$fail = 0;
foreach ($files as $rel) {
    InitializeGamestate();
    $r = SchemaTestRunner::runFile($rel);
    echo ($r->passed ? 'PASS' : 'FAIL') . ": {$rel}\n";
    if (!$r->passed) { echo '  ' . $r->message . "\n"; $fail++; }
}
echo "\n" . (count($files) - $fail) . '/' . count($files) . " passed\n";
exit($fail > 0 ? 1 : 0);
