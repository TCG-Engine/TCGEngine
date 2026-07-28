<?php
// TDD guard for deterministic shuffles (SWUSim undo redesign, Task 3).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_deterministic_shuffle.php
// A deterministic (counter-based) shuffle must REPRODUCE from the same seed+counter+state, so that
// restoring those on undo replays the identical order (=> the same mulligan hand). A strong-entropy
// shuffle must NOT reproduce (that is the bug we are fixing at the two call sites).
error_reporting(E_ALL & ~E_DEPRECATED);
chdir('/var/www/html/TCGEngine');
$GLOBALS['__test_rng_seed'] = 'game-secret';
if (!function_exists('GetSWUVar')) { function GetSWUVar($k, $d = '') { return $k === 'RNG_SEED' ? $GLOBALS['__test_rng_seed'] : $d; } }
include_once './Core/DeterministicRNG.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$mk = fn() => range(1, 24);

// Deterministic: reproduces from the same counter.
SetDeterministicRandomCounter(7); $a = $mk(); EngineShuffle($a, false);
SetDeterministicRandomCounter(7); $b = $mk(); EngineShuffle($b, false);
$check($a === $b, 'deterministic shuffle reproduces from the same counter+seed');

// Strong-entropy: does NOT reproduce (independent of the counter).
SetDeterministicRandomCounter(7); $c = $mk(); EngineShuffle($c, true);
SetDeterministicRandomCounter(7); $d = $mk(); EngineShuffle($d, true);
$check($c !== $d, 'strong-entropy shuffle does NOT reproduce (why the mulligan needed changing)');

// Sanity: the deterministic shuffle actually permuted (not a no-op).
$check($a !== $mk(), 'deterministic shuffle actually reorders');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
