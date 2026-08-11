<?php

$_pageStart = microtime(true);

include_once './AccountFiles/AccountSessionAPI.php';
include_once './Core/HTTPLibraries.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}

// Whole-suite run: lift the SAPI execution cap. The CLI runner has max_execution_time=0, but Apache's
// is 30s and the suite is well past that (~45s and growing with every ported section) — so over HTTP it
// died with "Maximum execution time of 30 seconds exceeded" partway through, which reads as a product
// fatal rather than a harness limit. Set AFTER the mod gate on purpose: an unauthenticated request must
// never be able to hold an Apache worker open indefinitely.
@set_time_limit(0);

$filter = isset($_GET['filter']) ? strval($_GET['filter']) : null;
// ?withDetails=1 → list every test with its run time (debugging). Default: minimal
// output (failures + summary only) so plain regression curls stay small.
$withDetails = isset($_GET['withDetails']) && ($_GET['withDetails'] === '1' || strtolower(strval($_GET['withDetails'])) === 'true');

// ── Animation stubs (no-ops in test harness; real impl in Core/EngineActionRunner.php) ──
if (!function_exists('ConvertMzIDToAbsolute')) {
    function ConvertMzIDToAbsolute($mzID, $playerPerspective): string { return ''; }
}
if (!function_exists('QueueDamageAnimation')) {
    function QueueDamageAnimation($targetMzID, $amount): void {}
}
if (!function_exists('QueueRestoreAnimation')) {
    function QueueRestoreAnimation($targetMzID, $amount): void {}
}
if (!function_exists('QueuePreventedDamageAnimation')) {
    function QueuePreventedDamageAnimation($targetMzID): void {}
}
if (!function_exists('QueueShieldBreakAnimation')) {
    function QueueShieldBreakAnimation($targetMzID): void {}
}

// ── Engine includes (same chain as GetNextTurn.php, minus UI/network) ──
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './Core/GameAuth.php';   // SimGameIsPrivateGame — undo consent gate (SWUUndoNeedsConsent)
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';

// ── Test framework ──────────────────────────────────────────────────
include_once './SWUSim/Tests/Framework/Assertions.php';
include_once './SWUSim/Tests/Framework/Cards.php';
include_once './SWUSim/Tests/Framework/CommonSetup.php';
include_once './SWUSim/Tests/Framework/GameStateBuilder.php';
include_once './SWUSim/Tests/Framework/GameTestAdapter.php';
include_once './SWUSim/Tests/Framework/SchemaTestRunner.php';
include_once './SWUSim/Tests/Framework/TestRunner.php';

TestRunner::run($filter, $_pageStart, $withDetails);
