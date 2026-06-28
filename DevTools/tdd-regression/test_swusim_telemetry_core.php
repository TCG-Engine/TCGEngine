<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_telemetry_core.php
header('Content-Type: text/plain');
include __DIR__ . '/../../Core/DeterministicRNG.php';
include __DIR__ . '/../../Core/CoreZoneModifiers.php';
include __DIR__ . '/../../SWUSim/ZoneClasses.php';
include __DIR__ . '/../../SWUSim/ZoneAccessors.php';
include __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include __DIR__ . '/../../SWUSim/GamestateParser.php'; // pulls in Telemetry.php

global $gameName, $gTelemetry;
$gameName = 'telcore_' . uniqid();
$dir = __DIR__ . '/../../SWUSim/Games/' . $gameName;
@mkdir($dir, 0777, true);

InitializeGamestate();
$checks = [];
$checks['default empty'] = SWUTelemetryGet()['cards'] === [];

// Bump some counters.
SWUTelemetryBumpCard(1, 'JTL_100', 'played');
SWUTelemetryBumpCard(1, 'JTL_100', 'played');
SWUTelemetryBumpCard(2, 'LOF_100', 'drawn', 3);
SWUTelemetryBumpTurn(1, 'damageDealt', 5);
SWUTelemetryBumpTurn(1, 'restored', 2);

WriteGamestate(__DIR__ . '/../../SWUSim/');
// Simulate APCu eviction so the round-trip goes through the file.
if (function_exists('apcu_delete')) apcu_delete(GetGamestateStorageKey($gameName));
$gTelemetry = null; // wipe in-memory
ParseGamestate(__DIR__ . '/../../SWUSim/');

$t = SWUTelemetryGet();
$checks['played survived'] = ($t['cards']['1']['JTL_100']['played'] ?? 0) === 2;
$checks['drawn survived']  = ($t['cards']['2']['LOF_100']['drawn'] ?? 0) === 3;
$checks['turn dmg survived'] = ($t['cur']['1']['damageDealt'] ?? 0) === 5;
$checks['restored survived'] = ($t['cur']['1']['restored'] ?? 0) === 2;

// Snapshot finalizes a turn record.
SWUTelemetrySnapshotTurn(1);
$t = SWUTelemetryGet();
$checks['turn snapshot'] = count($t['turns']) === 1 && ($t['turns'][0]['damageDealt'] ?? 0) === 5 && ($t['turns'][0]['restored'] ?? 0) === 2;
$checks['cur cleared'] = ($t['cur']['1'] ?? []) === [];

@unlink($dir . '/Gamestate.txt'); @rmdir($dir);
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
