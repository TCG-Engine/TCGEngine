<?php
// TDD guard for the per-game secret RNG seed (SWUSim undo redesign, Task 2).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_rng_seed_determinism.php
// Asserts: (1) the deterministic stream is REPRODUCIBLE (same seed+counter+state -> same value),
//          (2) the seed actually INFLUENCES the stream (different seed -> different value),
//          (3) an EMPTY seed reproduces the pre-change bytes exactly (backward compat for other roots).
error_reporting(E_ALL & ~E_DEPRECATED);
chdir('/var/www/html/TCGEngine');

// Controllable seed via a GetSWUVar stub (that is how EngineDeterministicBytes reads the seed).
$GLOBALS['__test_rng_seed'] = '';
if (!function_exists('GetSWUVar')) {
  function GetSWUVar($key, $default = '') { return $key === 'RNG_SEED' ? ($GLOBALS['__test_rng_seed'] ?? '') : $default; }
}
include_once './Core/DeterministicRNG.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// A fixed minimal state so EngineSnapshotState is stable across the calls below.
$GLOBALS['gTestZone'] = 'fixed-state';

// (1) reproducible with a set seed
$GLOBALS['__test_rng_seed'] = 'seedA';
SetDeterministicRandomCounter(5);
$vA1 = EngineRandomInt(0, 1000000);
SetDeterministicRandomCounter(5);
$vA2 = EngineRandomInt(0, 1000000);
$check($vA1 === $vA2, "reproducible: same seed+counter+state -> same value ($vA1)");

// (2) seed influences the stream
$GLOBALS['__test_rng_seed'] = 'seedB';
SetDeterministicRandomCounter(5);
$vB = EngineRandomInt(0, 1000000);
$check($vA1 !== $vB, "seed matters: seedA=$vA1 vs seedB=$vB differ");

// (3) empty seed == pre-change bytes (byte-identical to the old hash input)
$GLOBALS['__test_rng_seed'] = '';
SetDeterministicRandomCounter(5);
$material = EngineDeterministicHashMaterial();
$expectedOldByte0 = hash('sha256', $material . '|' . 5, true);   // exactly the pre-seed formula
$actual = EngineDeterministicBytes(32);
$check($actual === $expectedOldByte0, "empty seed reproduces the pre-change bytes (backward compat)");

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
