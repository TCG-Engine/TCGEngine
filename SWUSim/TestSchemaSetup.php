<?php

// Test Schema Setup endpoint — admin only.
// POST: schema (raw markdown string)
// Returns JSON: { gameName, whenSteps: [{raw, player, cmd, args},...], stepCount }

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
$content = $_POST['schema'] ?? '';
if ($content === '') {
    echo json_encode(['error' => 'No schema content provided']);
    exit;
}

// ── Parse schema ──────────────────────────────────────────────────────────────
$parsed = SchemaTestRunner::parseForUI($content);
if (!$parsed['ok']) {
    echo json_encode(['error' => $parsed['error'] ?? 'Failed to parse schema']);
    exit;
}

// ── Allocate a game slot ──────────────────────────────────────────────────────
global $gameName, $updateNumber, $playerID;
$gameName   = GetGameCounter(__DIR__ . '/Games');
$playerID   = 1;

// ── Build initial state from GIVEN + pregame ──────────────────────────────────
$builder = SchemaTestRunner::buildInitialStateForUI($parsed['given'], $parsed['pregame']);

ob_start();
InitializeGamestate();
$builder->_applyToGlobals();
AutoAdvanceAndExecute();
SchemaTestRunner::applyPostSetupDirectives($parsed['given']);
(new DecisionQueueController())->AutoResolveSingleChoiceDecisions();
SaveUndoVersion(1, "Start of Game");
ob_end_clean();

// ── Persist to APCu ───────────────────────────────────────────────────────────
++$updateNumber;
WriteGamestate(__DIR__ . '/');
InitializeCache($gameName);
SetCachePiece($gameName, 1, $updateNumber);

// ── Return result ─────────────────────────────────────────────────────────────
echo json_encode([
    'gameName'  => $gameName,
    'whenSteps' => $parsed['main'],
    'stepCount' => count($parsed['main']),
    'seatCount' => SeatCountForGame(),
    'liveSeats' => GetLiveSeatsArray(),
]);
