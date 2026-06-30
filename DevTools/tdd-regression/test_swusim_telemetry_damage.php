<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_telemetry_damage.php
header('Content-Type: text/plain');
// Stubs the engine normally gets from EngineActionRunner (absent in this lightweight harness).
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mz, $p) { return $mz; } } // passthrough for absolute IDs
if (!function_exists('QueueDamageAnimation'))  { function QueueDamageAnimation($a, $amt) {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($a, $amt) {} }

include __DIR__ . '/../../Core/DeterministicRNG.php';
include __DIR__ . '/../../Core/CoreZoneModifiers.php';
include __DIR__ . '/../../SWUSim/ZoneClasses.php';
include __DIR__ . '/../../SWUSim/ZoneAccessors.php';
include __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include __DIR__ . '/../../SWUSim/GamestateParser.php'; // GameLogic + CombatLogic + Telemetry

global $gameName; $gameName = 'teldmg_' . uniqid();
InitializeGamestate();
SWUTelemetryInit();
$checks = [];

// Universal damage hook: damage to p1 → taken by 1, dealt by 2.
SWUQueueDamageAnim("p1GroundArena-0", 3, 1);
SWUQueueDamageAnim("p2Base-0", 5, 1);
$t = SWUTelemetryGet();
$checks['p1 took 3']  = ($t['cur']['1']['damageTaken'] ?? 0) === 3;
$checks['p2 dealt 3'] = ($t['cur']['2']['damageDealt'] ?? 0) === 3;
$checks['p2 took 5']  = ($t['cur']['2']['damageTaken'] ?? 0) === 5;
$checks['p1 dealt 5'] = ($t['cur']['1']['damageDealt'] ?? 0) === 5;

// Base heal → restored credited to the base owner.
$base = &GetBase(1);
array_push($base, new Base('JTL_023'));
$base[0]->Damage = 4;
OnHealBase(1, 1, 2); // heal 2 from p1's base
$t = SWUTelemetryGet();
$checks['p1 restored 2'] = ($t['cur']['1']['restored'] ?? 0) === 2;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n"
   : "FAIL: " . implode(', ', $fails) . " cur=" . json_encode($t['cur'] ?? null) . "\n";
