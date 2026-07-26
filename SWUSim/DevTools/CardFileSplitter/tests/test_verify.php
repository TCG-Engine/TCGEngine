<?php
// Test: (array,key) multiset diff logic.
require __DIR__ . '/../Verify.php';

$before = ['a::1','a::2','b::9'];

// Reorder only → no diff.
assert(splitter_diff_keys($before, ['a::2','a::1','b::9']) === ['missing'=>[],'added'=>[]], 'reorder');

// Dropped a::2 → missing.
$d = splitter_diff_keys($before, ['a::1','b::9']);
assert($d['missing'] === ['a::2'] && $d['added'] === [], 'dropped: '.json_encode($d));

// Duplicated a::2 → surfaces as added (multiset, not set).
$d = splitter_diff_keys($before, ['a::1','a::2','b::9','a::2']);
assert(in_array('a::2', $d['added'], true) && $d['missing'] === [], 'dup: '.json_encode($d));

// New key added.
$d = splitter_diff_keys($before, ['a::1','a::2','b::9','c::3']);
assert($d['added'] === ['c::3'] && $d['missing'] === [], 'newkey: '.json_encode($d));

echo "OK\n";
