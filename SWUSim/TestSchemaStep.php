<?php

// Test Schema Step endpoint — admin only.
// POST: gameName, step (raw WHEN line e.g. "- P1>PlayHand:0")
// Returns JSON: { success: true } | { error: string }

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
$rawStep  = strval($_POST['step'] ?? '');

if ($gameName === '') { echo json_encode(['error' => 'Missing gameName']); exit; }
if ($rawStep   === '') { echo json_encode(['error' => 'Missing step']);    exit; }

// ── Parse step ────────────────────────────────────────────────────────────────
$action = SchemaTestRunner::parseSingleAction($rawStep);
if ($action === null) {
    echo json_encode(['error' => 'Could not parse step: ' . $rawStep]);
    exit;
}

// ── Load game state ───────────────────────────────────────────────────────────
// $gameName is a PHP global read by ParseGamestate/WriteGamestate; set it here
// at file scope so all included-function globals pick it up.
$playerID = $action['player'];

ob_start();
ParseGamestate(__DIR__ . '/');
ob_end_clean();

// ── Execute step ──────────────────────────────────────────────────────────────
try {
    $g = new GameTestAdapter();
    ob_start();
    SchemaTestRunner::executeSingleAction($g, $action);
    ob_end_clean();
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// ── Collect any remaining pending decisions (for UI display) ─────────────────
// Do NOT auto-resolve single-choice decisions here — schemas use explicit
// AnswerDecision steps for every interactive choice, matching CLI test behavior.
$autoResolved = 0;
$pendingDecisions = [];
for ($p = 1; $p <= 2; $p++) {
    $queue = GetDecisionQueue($p);
    if (!empty($queue)) {
        $pendingDecisions[] = [
            'player' => $p,
            'type'   => $queue[0]->Type,
            'param'  => $queue[0]->Param,
            'tooltip'=> $queue[0]->Tooltip ?? '',
        ];
    }
}

// ── Persist updated state ─────────────────────────────────────────────────────
++$updateNumber;
WriteGamestate(__DIR__ . '/');
SetCachePiece($gameName, 1, $updateNumber);
GamestateUpdated($gameName);

echo json_encode([
    'success'       => true,
    'autoResolved'  => $autoResolved,
    'pending'       => $pendingDecisions,
]);
