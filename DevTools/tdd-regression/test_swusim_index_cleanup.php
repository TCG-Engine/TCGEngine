<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_index_cleanup.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/NetworkingLibraries.php';

$a = 'idxa_' . uniqid(); $b = 'idxb_' . uniqid();
RegisterActiveGame('SWUSim', $a, false);
RegisterActiveGame('SWUSim', $b, false);
$before = ReadActiveGameIndex();
RemoveActiveGame('SWUSim', $a);
$after = ReadActiveGameIndex();

$checks = [];
$checks['both registered'] = isset($before['SWUSim:' . $a], $before['SWUSim:' . $b]);
$checks['a removed'] = !isset($after['SWUSim:' . $a]);
$checks['b remains'] = isset($after['SWUSim:' . $b]);
// cleanup
RemoveActiveGame('SWUSim', $b);

echo (count(array_filter($checks)) === count($checks)) ? "PASS (3 checks)\n"
   : "FAIL: " . implode(', ', array_keys(array_filter($checks, fn($v)=>!$v))) . "\n";
