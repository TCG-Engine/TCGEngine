<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_undo_localdev_consent.php
// Undo consent is OFF for a local-dev BROWSER and ON everywhere else (owner, 2026-09-22: approving
// your own undo while driving both seats of a pulled bug report is pure friction).
//
// ⚠ WHY THIS TEST EXISTS AT ALL. The obvious implementation — SWUIsLocalDevRequest()
// (SWUSim/Mod/DevGate.php) — is WRONG: it returns true whenever DEVENV=true, which is the condition
// INSIDE the dev container, where the schema suite runs. Measured by mutation 2026-09-22, swapping
// this predicate for that one takes the undo suite from 21/21 to 14/21
// (Tests/Cases/undo/ConsentGating.md, RequestApprove.md, core/GameLog_Undo.md all assert consent IS
// required) AND turns three rows below red — including the production row.
// The discriminator is therefore the REQUEST HOST, and the rows below are the whole contract.
//
// Web SAPI: the predicate short-circuits on PHP_SAPI === 'cli', so a CLI run proves nothing.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
header('Content-Type: text/plain');

$root = dirname(__DIR__, 2);
chdir($root);

$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}

// Engine stubs — the parse path may reference them outside a real turn.
if (!function_exists('ConvertMzIDToAbsolute'))         { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation'))          { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation'))         { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation'))     { function QueueShieldBreakAnimation($t): void {} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';   // pulls in Custom/GameLogic.php via ServerInclude

check(function_exists('SWUUndoConsentDisabledForLocalDev'), 'the predicate exists');
if (!function_exists('SWUUndoConsentDisabledForLocalDev')) { echo "\n1 FAILED\n"; exit; }

// DEVENV is 'true' in the dev container for ALL of the rows below. If any row's answer tracks it,
// the implementation has drifted back to SWUIsLocalDevRequest().
echo "  (DEVENV is " . var_export(getenv('DEVENV'), true) . " throughout)\n";

$saved = $_SERVER['HTTP_HOST'] ?? null;
foreach ([
    ['localhost:3400', true,  'a local dev browser: consent OFF'],
    ['127.0.0.1:3400', true,  'loopback by IP: consent OFF'],
    ['swustats.net',   false, '★ PRODUCTION: consent ON'],
    ['evil-localhost.example.com', false, 'a host merely CONTAINING localhost: consent ON'],
    ['',               false, 'no host at all: consent ON'],
] as [$host, $expected, $msg]) {
    $_SERVER['HTTP_HOST'] = $host;
    check(SWUUndoConsentDisabledForLocalDev() === $expected, $msg, ['host' => $host]);
}
if ($saved === null) unset($_SERVER['HTTP_HOST']); else $_SERVER['HTTP_HOST'] = $saved;

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
