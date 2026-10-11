<?php
require_once __DIR__ . '/upf_test.php';
$reset();
$state = FaBGetState();
$state['turnEffects'][1] = [['type'=>'CACHE_TEST', 'amount'=>2]];
FaBSetState($state);
$copy = FaBGetState();
$copy['turnEffects'][1][0]['amount'] = 99;
$check(FaBGetState()['turnEffects'][1][0]['amount'] === 2, 'Mutating a returned array changed authoritative state.');
FaBSetState($copy);
$check(FaBGetState()['turnEffects'][1][0]['amount'] === 99, 'Normal state writes did not invalidate the cache.');

// Loading/undoing serialized state can bypass FaBSetState entirely.
$saved = GetGameState();
SetGameState('{"window":"REACTION","turnEffects":{}}');
$check(FaBGetState()['window'] === 'REACTION' && FaBGetState()['turnEffects'] === [], 'Direct state replacement returned stale data.');
$check(FaBGetState()['combatOpen'] === false, 'Partial state lost its defaults.');
SetGameState($saved);
$check(FaBGetState()['turnEffects'][1][0]['amount'] === 99, 'Restoring a snapshot returned stale data.');
SetGameState('invalid json');
$check(FaBGetState() === FaBStateDefaults(), 'Invalid state did not fall back to defaults.');
$reset();
$check(FaBGetState()['turnEffects'] === [] && FaBGetState()['window'] === 'ACTION', 'Game reset retained cached effects.');
if ($failures) { foreach ($failures as $failure) fwrite(STDERR, "FAIL: $failure\n"); exit(1); }
echo "FaB state cache checks passed.\n";
