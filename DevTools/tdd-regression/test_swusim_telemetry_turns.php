<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_telemetry_turns.php
header('Content-Type: text/plain');
include __DIR__ . '/../../Core/DeterministicRNG.php';
include __DIR__ . '/../../Core/CoreZoneModifiers.php';
include __DIR__ . '/../../SWUSim/ZoneClasses.php';
include __DIR__ . '/../../SWUSim/ZoneAccessors.php';
include __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include __DIR__ . '/../../SWUSim/GamestateParser.php';

global $gameName; $gameName = 'telturns_' . uniqid();
InitializeGamestate();
SWUTelemetryInit();
$checks = [];

// Accumulate some running per-turn counters for seat 1, then snapshot.
SWUTelemetryBumpTurn(1, 'cardsUsed', 2);
SWUTelemetryBumpTurn(1, 'resourcesUsed', 3);
SWUTelemetryBumpTurn(1, 'damageDealt', 4);
SWUTelemetrySnapshotTurn(1);

$t = SWUTelemetryGet();
$checks['one snapshot'] = count($t['turns']) === 1;
$checks['snapshot fields'] = ($t['turns'][0]['cardsUsed'] ?? -1) === 2
    && ($t['turns'][0]['resourcesUsed'] ?? -1) === 3
    && ($t['turns'][0]['damageDealt'] ?? -1) === 4
    && array_key_exists('resourcesLeft', $t['turns'][0])
    && array_key_exists('cardsLeft', $t['turns'][0])
    && array_key_exists('restored', $t['turns'][0]); // EXTRA field present
$checks['cur reset'] = ($t['cur']['1'] ?? []) === [];

// A second round accumulates fresh (no carryover).
SWUTelemetryBumpTurn(1, 'cardsUsed', 5);
SWUTelemetrySnapshotTurn(1);
$t = SWUTelemetryGet();
$checks['two snapshots'] = count($t['turns']) === 2;
$checks['no carryover'] = ($t['turns'][1]['cardsUsed'] ?? -1) === 5;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', $fails) . " turns=" . json_encode($t['turns'] ?? null) . "\n";
