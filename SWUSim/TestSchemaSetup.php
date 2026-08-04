<?php

// Test Schema Setup endpoint — admin only.
// POST: schema (raw markdown string)
// Returns JSON: { gameName, whenSteps: [{raw, player, cmd, args},...], stepCount }

error_reporting(E_ALL);
header('Content-Type: application/json');

include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../Core/GameAuth.php';   // SimGameWriteAuthKeys — see the seat-auth block below

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

// ── Seat auth for the Test Schema Editor ──────────────────────────────────────
// Seat validation used to FAIL OPEN: SimGameValidateSeatAuth returned true whenever a game had no
// auth key, so a schema game created here was viewable without ever registering one. That default
// inverted when auth keys moved into APCu — SWUSim is now in SimGameRequiresManagedAuth's list, so a
// game with NO auth-keys entry is DENIED and the editor's iframe renders "This seat link is no longer
// valid." (Correct tightening for real games; this dev tool just never wrote keys.)
//
// Register an entry with EMPTY per-seat keys, which is still explicitly allowed
// (SimGameValidateSeatAuth: `if ($expectedKey === '') return true;`). That restores editor access
// without weakening the new model: real games write real keys from their lobby and are unaffected.
//
// ⚠ Must happen HERE, in the web request that creates the game — APCu is per-SAPI, so keys stored
// from a CLI process are invisible to the browser.
if (function_exists('SimGameWriteAuthKeys')) {
    SimGameWriteAuthKeys('SWUSim', $gameName, SimGameDefaultAuthKeys());
}

// ── Return result ─────────────────────────────────────────────────────────────
echo json_encode([
    'gameName'  => $gameName,
    'whenSteps' => $parsed['main'],
    'stepCount' => count($parsed['main']),
    'seatCount' => SeatCountForGame(),
    'liveSeats' => GetLiveSeatsArray(),
]);
