<?php
// Force a Twin Suns game's three counters (and optionally the turn player / counter-taken record) into
// a chosen state. Fixture support for DevTools/ui-harness/swusim-twinsuns-pass-button.mjs, which has to
// see the Pass button under each counter configuration without playing a game into every one of them.
//
//   php SWUSim/DevTools/set-twinsuns-counters.php <gameName> <initiative> <blast> <plan> [turn] [taken]
//
//   initiative : "P{n}_UNCLAIMED" (available to take) | "P{n}_CLAIMED" (already taken this round)
//   blast/plan : "AVAILABLE" | "P{n}"
//   turn       : optional seat to make the active player — the Pass button is only laid out for the
//                active seat, so a fixture that wants to SEE it must set this, or "hidden by the rule"
//                and "not on screen" get conflated.
//   taken      : optional SWU_COUNTER_TAKEN digits, e.g. "1". A seat listed here has a FORCED pass,
//                which is a different case from the pass CHOICE the rule is mostly about.
//
// Dev-only: it writes a gamestate directly, so point it at a scratch clone, never a live game.
error_reporting(E_ALL & ~E_DEPRECATED);
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
InitializeGamestate();

if ($argc < 5) { fwrite(STDERR, "usage: set-twinsuns-counters.php <gameName> <init> <blast> <plan> [turn] [taken]\n"); exit(1); }
[$g, $init, $blast, $plan] = [$argv[1], $argv[2], $argv[3], $argv[4]];
$turn  = intval($argv[5] ?? 0);
$taken = strval($argv[6] ?? '');

$GLOBALS['gameName'] = $g;
ParseGamestate('./SWUSim/');
SetInitiativeCounter($init);
SetBlastCounter($blast);
SetPlanCounter($plan);
SetSWUVar('SWU_COUNTER_TAKEN', $taken);
if ($turn > 0) { global $gTurnPlayer; $gTurnPlayer = $turn; }
// ⚠ THE SAME PATH PREFIX ParseGamestate USED. WriteGamestate() defaults to "./", which resolves to
// ./Games/<id>/ from the web root and silently fails with a file_put_contents warning — the write is
// lost and the fixture quietly keeps the PREVIOUS counter state, which reads as a product bug.
WriteGamestate('./SWUSim/');
echo "$init/$blast/$plan turn=$turn taken='$taken'\n";
