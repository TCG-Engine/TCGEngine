<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_match_hooks.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Core/Match/Hooks.php';

$checks = [];
$unused = null;
MatchRegisterHooks('FakeSim', [
    'validateDeck' => function ($deck, $fmt) { return count($deck) > 0; },
    'queueTypes'   => ['bo1', 'bo3'],
]);
$checks['required registered callable fires'] = (MatchHook('FakeSim', 'validateDeck', ['x'], 'premier') === true);
$checks['optional absent returns null']       = (MatchHook('FakeSim', 'recordDeckStats', $unused, 1) === null);
$checks['hookExists true for registered']     = (MatchHookExists('FakeSim', 'validateDeck') === true);
$checks['hookExists false for absent']        = (MatchHookExists('FakeSim', 'recordDeckStats') === false);
$checks['config value read']                  = (MatchConfig('FakeSim', 'queueTypes', []) === ['bo1', 'bo3']);
$checks['config default when absent']          = (MatchConfig('FakeSim', 'sideboardSeconds', 180) === 180);

$missingThrew = false;
try { MatchHook('FakeSim', 'setupGame', null); } catch (\Throwable $e) { $missingThrew = true; }
$checks['missing REQUIRED hook throws'] = $missingThrew;

$fail = 0;
foreach ($checks as $k => $v) { echo ($v ? 'PASS ' : 'FAIL ') . $k . "\n"; if (!$v) $fail++; }
echo ($fail === 0 ? "ALL GREEN\n" : "$fail FAILED\n");
