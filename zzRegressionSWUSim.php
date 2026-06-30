<?php

$_pageStart = microtime(true);

include_once './AccountFiles/AccountSessionAPI.php';
include_once './Core/HTTPLibraries.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}

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
