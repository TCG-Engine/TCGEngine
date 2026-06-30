<?php

// Test Schema Expect endpoint — admin only.
// POST: gameName, schema (raw markdown; its EXPECT section is evaluated against
//       the CURRENT persisted game state). Read-only — does not mutate/persist state.
// Returns JSON: { passed: bool, total: int, failures: [string] } | { error: string }

error_reporting(E_ALL);
header('Content-Type: application/json');

include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';

$authError = CheckLoggedInUserMod();
if ($authError !== '') {
    echo json_encode(['error' => $authError]);
    exit;
}

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

// ── Engine includes ───────────────────────────────────────────────────────────
include_once __DIR__ . '/../Core/DeterministicRNG.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/Custom/CombatLogic.php';

// ── Test framework ────────────────────────────────────────────────────────────
include_once __DIR__ . '/Tests/Framework/Assertions.php';
include_once __DIR__ . '/Tests/Framework/Cards.php';
include_once __DIR__ . '/Tests/Framework/CommonSetup.php';
include_once __DIR__ . '/Tests/Framework/GameStateBuilder.php';
include_once __DIR__ . '/Tests/Framework/GameTestAdapter.php';
include_once __DIR__ . '/Tests/Framework/SchemaTestRunner.php';

// ── Input ─────────────────────────────────────────────────────────────────────
$gameName = strval($_POST['gameName'] ?? '');
$schema   = strval($_POST['schema'] ?? '');

if ($gameName === '') { echo json_encode(['error' => 'Missing gameName']); exit; }
if ($schema   === '') { echo json_encode(['error' => 'Missing schema']);   exit; }

// ── Parse EXPECT lines from the schema ─────────────────────────────────────────
$parsed = SchemaTestRunner::parseForUI($schema);
if (!$parsed['ok']) {
    echo json_encode(['error' => $parsed['error'] ?? 'Failed to parse schema']);
    exit;
}
$expectLines = $parsed['expect'] ?? [];

// ── Load current game state ─────────────────────────────────────────────────────
// $gameName is a PHP global read by ParseGamestate; set at file scope.
$playerID = 1;

ob_start();
ParseGamestate(__DIR__ . '/');
ob_end_clean();

// ── Evaluate EXPECT against live state (read-only; nothing is persisted) ────────
try {
    $g = new GameTestAdapter();
    ob_start();
    $failures = SchemaTestRunner::evalExpectLines($g, $expectLines);
    ob_end_clean();
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

echo json_encode([
    'passed'   => empty($failures),
    'total'    => count($expectLines),
    'failures' => array_values($failures),
]);
