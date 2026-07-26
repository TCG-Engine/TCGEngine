<?php
// Subprocess helper: boot the SWUSim engine exactly like the schema-test runner
// (so the same ability arrays get populated, including the cards/_loader files),
// then print the (array,key) registration snapshot as JSON. Run in a FRESH php
// process before and after a split so the two snapshots reflect the on-disk state.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

$repo = getenv('REPO_ROOT') ?: (function () {
    $d = __DIR__;
    while ($d !== '/' && $d !== '' && !(is_dir("$d/SWUSim") && is_dir("$d/Core"))) $d = dirname($d);
    return $d;
})();
chdir($repo);

// Animation stubs (no-ops in the harness) — same as the schema-test runner.
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }

// Engine include chain (mirrors run-schema-tests.php). GamestateParser pulls in
// Custom/GameLogic.php → the monoliths + cards/_loader.php.
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';

require __DIR__ . '/Verify.php';
echo json_encode(splitter_snapshot_keys());
