<?php
// SWU-PGN reader: fold / stateAt (spec §12) and the keyframe gate (spec §13–14). Pure PHP, no engine.
//   php DevTools/tdd-regression/test_swupgn_fold_integrity.php
error_reporting(E_ALL); ini_set('display_errors', 1);
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$fold = fn(array $events) => SwuPgnFold(swupgnEvents($events));
$card = function (array $state, int $seat, string $id) {
  foreach ($state['players'][$seat]['cards'] as $c) if ($c['id'] === $id) return $c;
  return null;
};
$ids = fn(array $state, int $seat) => array_column($state['players'][$seat]['cards'], 'id');

// ── basics ───────────────────────────────────────────────────────────────────
$basic = [
  ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'],
  ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground', 'cost' => 2],
  ['seq' => 'R1.A.2', 't' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#108', 'def' => 'base', 'defenderType' => 'base'],
  ['seq' => 'R1.A.2a', 't' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 2, 'damageType' => 'combat', 'hp' => 28],
  ['seq' => 'R1.A.2b', 't' => 'EXHAUST', 'card' => 'SOR#108'],
];
swupgnCheck($card($fold(array_slice($basic, 0, 2)), 1, 'SOR#108')['zone'] === 'ground', 'places a played card into its zone');
swupgnCheck($fold($basic)['players'][2]['baseHp'] === 28, 'sets a base to the absolute hp a DAMAGE carries');
swupgnCheck($card($fold($basic), 1, 'SOR#108')['exhausted'] === true, 'marks the attacker exhausted');
swupgnCheck($card(SwuPgnStateAt(swupgnEvents($basic), 'R1.A.1'), 1, 'SOR#108')['exhausted'] === false, 'stateAt stops at the given seq');
swupgnCheck(count(SwuPgnStateAt(swupgnEvents([$basic[1]]), 'R9.Z.9')['players'][1]['cards']) === 1, 'stateAt with an unknown seq folds the whole list (spec §12.3)');
$e = SwuPgnEmptyState();
swupgnCheck($e['round'] === 0 && $e['phase'] === 'setup' && $e['initiative'] === null && $e['players'][1]['baseHp'] === 30 && $e['players'][2]['seat'] === 2, 'emptyState is the spec §11 starting board');

$s = $fold([['seq' => 'R1.A.1', 't' => 'PLAY_EVENT', 'p' => 1, 'card' => 'SOR#142']]);
swupgnCheck($s['players'][1]['cards'] === [] && in_array('SOR#142', $s['players'][1]['discard'], true), 'PLAY_EVENT goes to discard, not into play');
$s = $fold([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground'], ['seq' => 'R1.A.2', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'SOR#200', 'target' => 'SOR#108']]);
swupgnCheck(count($s['players'][1]['cards']) === 1 && $s['players'][1]['cards'][0]['upgrades'] === ['SOR#200'], 'PLAY_UPGRADE attaches to its host unit');
$s = $fold([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 2, 'card' => 'SOR#045', 'zone' => 'ground'], ['seq' => 'R1.A.2', 't' => 'DEFEAT', 'card' => 'SOR#045', 'reason' => 'ability']]);
swupgnCheck($s['players'][2]['cards'] === [] && $s['players'][2]['discard'] === ['SOR#045'], 'DEFEAT moves a card from play to discard');
$s = $fold([['seq' => 'R1.S.1', 't' => 'DRAW', 'p' => 1, 'count' => 2, 'cards' => ['SOR#108', 'SOR#142']]]);
swupgnCheck($s['players'][1]['handSize'] === 0 && $s['players'][1]['hand'] === ['SOR#108', 'SOR#142'], 'DRAW records the hand contents but never the count (MOVE owns it)');

$kf = ['round' => 2, 'phase' => 'action', 'initiative' => 1, 'players' => [
  1 => ['seat' => 1, 'baseHp' => 25, 'baseMaxHp' => 30, 'handSize' => 3, 'hand' => [], 'resourcesReady' => 4, 'resourcesExhausted' => 0, 'credits' => 0, 'hasForce' => false, 'discard' => [], 'cards' => []],
  2 => ['seat' => 2, 'baseHp' => 18, 'baseMaxHp' => 30, 'handSize' => 2, 'hand' => [], 'resourcesReady' => 4, 'resourcesExhausted' => 0, 'credits' => 0, 'hasForce' => true, 'discard' => [], 'cards' => []],
]];
$s = $fold([['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => $kf], ['seq' => 'R2.A.1a', 't' => 'DAMAGE', 'src' => 'X', 'tgt' => 'base@2', 'amt' => 3, 'damageType' => 'combat', 'hp' => 15]]);
swupgnCheck($s['players'][1]['baseHp'] === 25 && $s['players'][2]['baseHp'] === 15, 'snaps to a keyframe and keeps folding');

// ── MOVE is the source of truth ──────────────────────────────────────────────
$s = $fold([
  ['seq' => 'R1.S.1', 't' => 'MOVE', 'p' => 1, 'card' => 'SOR#142', 'from' => 'deck', 'to' => 'hand'],
  ['seq' => 'R1.S.2', 't' => 'MOVE', 'p' => 1, 'card' => 'SOR#108', 'from' => 'deck', 'to' => 'hand'],
  ['seq' => 'R1.S.3', 't' => 'MOVE', 'p' => 1, 'card' => 'SOR#142', 'from' => 'hand', 'to' => 'resource'],
]);
swupgnCheck($s['players'][1]['handSize'] === 1 && $s['players'][1]['resourcesReady'] === 1 && $s['players'][1]['hand'] === ['SOR#108'] && $s['players'][1]['resources'] === ['SOR#142'],
  'MOVE drives the hand and the resource row, count and contents');
$s = $fold([['seq' => 'R1.A.1', 't' => 'MOVE', 'p' => 2, 'card' => 'SOR#095', 'from' => 'outsideTheGame', 'to' => 'ground'], ['seq' => 'R1.A.2', 't' => 'MOVE', 'p' => 2, 'card' => 'SOR#095', 'from' => 'ground', 'to' => 'ground']]);
swupgnCheck($ids($s, 2) === ['SOR#095'], 'a redundant arena MOVE does not duplicate the card');
$s = $fold([['seq' => 'R1.A.1', 't' => 'MOVE', 'p' => 2, 'card' => 'SOR#095', 'from' => 'outsideTheGame', 'to' => 'ground'], ['seq' => 'R1.A.2', 't' => 'MOVE', 'p' => 2, 'card' => 'SOR#095', 'from' => 'ground', 'to' => 'discard']]);
swupgnCheck($ids($s, 2) === [] && $s['players'][2]['discard'] === ['SOR#095'], 'a MOVE out of an arena removes the card and files the pile');
$s = $fold([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground'], ['seq' => 'R1.A.1a', 't' => 'MOVE', 'p' => 1, 'card' => 'SOR#108', 'from' => 'hand', 'to' => 'ground']]);
swupgnCheck($ids($s, 1) === ['SOR#108'], 'PLAY then its hand->ground MOVE does not double-add');
$s = $fold([['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'], ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#095', 'zone' => 'ground', 'cost' => 2]]);
swupgnCheck($ids($s, 1) === ['SOR#095'], 'MOVE then its PLAY does not double-add');
$s = $fold([['seq' => 'R1.A.1', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'zone' => 'ground'], ['seq' => 'R1.A.2', 't' => 'CREATE_TOKEN', 'p' => 2, 'token' => 'TOKEN:X-Wing', 'zone' => 'space']]);
swupgnCheck($card($s, 1, 'SOR#010')['zone'] === 'ground' && $card($s, 2, 'TOKEN:X-Wing')['zone'] === 'space', 'DEPLOY_LEADER and CREATE_TOKEN put cards in play');
$s = $fold([['seq' => 'R1.A.1', 't' => 'CREATE_TOKEN', 'p' => 2, 'token' => 'TOKEN:x', 'zone' => 'outsideTheGame'], ['seq' => 'R1.A.2', 't' => 'CREATE_TOKEN', 'p' => 2, 'token' => 'TOKEN:y', 'zone' => 'ground', 'kind' => 'upgrade']]);
swupgnCheck($s['players'][2]['cards'] === [], 'CREATE_TOKEN places nothing outside an arena, and nothing for an upgrade');
swupgnCheck($fold([['seq' => 'R1.A.1b', 't' => 'OVERWHELM', 'p' => 1, 'tgt' => 'base@2', 'amt' => 4, 'hp' => 16]])['players'][2]['baseHp'] === 16, 'OVERWHELM sets the defending base hp');
$s = $fold([
  ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground'],
  ['seq' => 'R1.A.2a', 't' => 'DAMAGE', 'src' => 'X', 'tgt' => 'SOR#108', 'amt' => 3, 'damageType' => 'combat', 'hp' => 0],
  ['seq' => 'R1.A.2b', 't' => 'HEAL', 'tgt' => 'SOR#108', 'amt' => 1, 'hp' => 0],
  ['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'ground', 'to' => 'space'],
  ['seq' => 'R1.A.4', 't' => 'SHIELD_GAIN', 'card' => 'SOR#108', 'count' => 2],
  ['seq' => 'R1.A.4b', 't' => 'SHIELD_USE', 'card' => 'SOR#108'],
  ['seq' => 'R1.A.5', 't' => 'EXPERIENCE_GAIN', 'card' => 'SOR#108', 'count' => 2],
  ['seq' => 'R1.A.6', 't' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'stun', 'count' => 1],
]);
$c = $card($s, 1, 'SOR#108');
swupgnCheck($c['damage'] === 2 && $c['zone'] === 'space' && $c['shields'] === 1 && $c['experience'] === 2 && $c['statusTokens'] === ['stun' => 1], 'HEAL, seatless MOVE, shields, experience and status tokens');
$s = $fold([
  ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground'],
  ['seq' => 'R1.A.2', 't' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'advantage', 'count' => 1],
  ['seq' => 'R1.A.3', 't' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'advantage', 'count' => -1],
  ['seq' => 'R1.A.4', 't' => 'EXPERIENCE_GAIN', 'card' => 'SOR#108', 'count' => -3],
  ['seq' => 'R1.A.5', 't' => 'SHIELD_USE', 'card' => 'SOR#108', 'count' => 4],
]);
$c = $card($s, 1, 'SOR#108');
swupgnCheck($c['statusTokens'] === [] && $c['experience'] === 0 && $c['shields'] === 0, 'a token count that reaches zero is DELETED; counters clamp at 0 (token contract)');
$s = $fold([['seq' => 'R1.A.1', 't' => 'DAMAGE', 'src' => 'X', 'tgt' => 'base@2', 'amt' => 10, 'damageType' => 'combat', 'hp' => 20], ['seq' => 'R1.A.2', 't' => 'HEAL', 'tgt' => 'base@2', 'amt' => 4, 'hp' => 24]]);
swupgnCheck($s['players'][2]['baseHp'] === 24, 'HEAL on a base sets the reported hp');
$s = $fold([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground'], ['seq' => 'R1.A.2', 't' => 'EXHAUST', 'card' => 'SOR#108'], ['seq' => 'R1.A.3', 't' => 'READY', 'card' => 'SOR#108']]);
swupgnCheck($card($s, 1, 'SOR#108')['exhausted'] === false, 'READY clears EXHAUST');
$base = [['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground']];
swupgnCheck($fold(array_merge($base, [['seq' => 'R1.A.4', 't' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'SOR#108']]))['players'] === $fold($base)['players'], 'a TAKE_CONTROL with no zone leaves the board unchanged');
$s = $fold(array_merge($base, [['seq' => 'R1.A.4', 't' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'SOR#108', 'zone' => 'ground']]));
swupgnCheck($ids($s, 1) === [] && $ids($s, 2) === ['SOR#108'], 'a TAKE_CONTROL in an arena re-seats the card');

// ── resources ────────────────────────────────────────────────────────────────
$three = [
  ['seq' => 'R0.S.1', 't' => 'MOVE', 'card' => 'A', 'from' => 'hand', 'to' => 'resource', 'p' => 1],
  ['seq' => 'R0.S.2', 't' => 'MOVE', 'card' => 'B', 'from' => 'hand', 'to' => 'resource', 'p' => 1],
  ['seq' => 'R0.S.3', 't' => 'MOVE', 'card' => 'C', 'from' => 'hand', 'to' => 'resource', 'p' => 1],
];
$split = fn(array $s, int $seat) => [$s['players'][$seat]['resourcesReady'], $s['players'][$seat]['resourcesExhausted']];
swupgnCheck($split($fold(array_merge($three, [['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2]])), 1) === [1, 2], 'EXHAUST_RESOURCES moves ready to exhausted');
swupgnCheck($split($fold(array_merge($three, [
  ['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2],
  ['seq' => 'R1.G.1', 't' => 'READY_RESOURCES', 'p' => 1, 'amount' => 1],
  ['seq' => 'R1.G.2', 't' => 'READY_RESOURCES', 'p' => 1, 'amount' => 1],
  ['seq' => 'R1.G.3', 't' => 'READY_RESOURCES', 'p' => 1, 'amount' => 1],
])), 1) === [3, 0], 'READY_RESOURCES moves them back, and finds nothing more to ready');
swupgnCheck($split($fold(array_merge($three, [['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 5]])), 1) === [0, 3]
  && $split($fold([['seq' => 'R1.A.0a', 't' => 'READY_RESOURCES', 'p' => 1, 'amount' => 5]]), 1) === [0, 0], 'resource shifts clamp to what the row holds');
swupgnCheck($split($fold(array_merge($three, [
  ['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2],
  ['seq' => 'R1.A.0b', 't' => 'MOVE', 'card' => 'A', 'from' => 'resource', 'to' => 'ground', 'p' => 1, 'kind' => 'unit', 'exhausted' => true],
  ['seq' => 'R1.A.0c', 't' => 'MOVE', 'card' => 'B', 'from' => 'resource', 'to' => 'hand', 'p' => 1],
])), 1) === [0, 1], 'a resource leaving the row exhausted comes out of the exhausted count');
$s = $fold(array_merge($three, [
  ['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 1],
  ['seq' => 'R1.A.1a', 't' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'A', 'zone' => 'resource', 'from' => 1, 'exhausted' => true],
  ['seq' => 'R1.A.1b', 't' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'B', 'zone' => 'resource', 'from' => 1],
]));
swupgnCheck($split($s, 1) === [1, 0] && $split($s, 2) === [1, 1] && $s['players'][1]['resources'] === ['C'] && $s['players'][2]['resources'] === ['A', 'B'],
  'a stolen resource moves its count bucket and its row membership');
swupgnCheck(!array_key_exists('resources', $fold([['seq' => 'R1.A.1', 't' => 'PASS', 'p' => 1]])['players'][1]), 'the resource list stays ABSENT until something supplies one');

// ── credits and the Force ────────────────────────────────────────────────────
$s = $fold([
  ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'TOKEN:credit#8015500527', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 2],
  ['seq' => 'R1.A.1b', 't' => 'MOVE', 'card' => 'TOKEN:credit#8015500527:2', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 2],
  ['seq' => 'R2.A.0a', 't' => 'MOVE', 'card' => 'TOKEN:credit#8015500527', 'from' => 'base', 'to' => 'outsideTheGame', 'p' => 2],
  ['seq' => 'R2.A.0b', 't' => 'DEFEAT', 'card' => 'TOKEN:credit#8015500527', 'reason' => 'ability'],
]);
swupgnCheck($s['players'][2]['credits'] === 1 && $s['players'][2]['cards'] === [], 'Credit token MOVEs drive `credits`; a token in the base is no arena card');
$s = $fold([['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'TOKEN:credit#8015500527', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 2], ['seq' => 'R1.A.2a', 't' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'TOKEN:credit#8015500527', 'zone' => 'base', 'from' => 2]]);
swupgnCheck([$s['players'][1]['credits'], $s['players'][2]['credits']] === [1, 0], 'a Credit changing hands is a TAKE_CONTROL in the base zone');
$on = [['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'TOKEN:the-force#force-id', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 1]];
swupgnCheck($fold($on)['players'][1]['hasForce'] === true
  && $fold(array_merge($on, [['seq' => 'R1.A.2a', 't' => 'MOVE', 'card' => 'TOKEN:the-force#force-id', 'from' => 'base', 'to' => 'outsideTheGame', 'p' => 1]]))['players'][1]['hasForce'] === false, 'the Force token drives `hasForce`');

// ── attachments ──────────────────────────────────────────────────────────────
$host = ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'LOF#164', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'];
$s = $fold([$host, ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'LOF#215', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'LOF#164'], ['seq' => 'R1.A.2', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'zone' => 'ground', 'target' => 'LOF#164', 'cost' => 2]]);
swupgnCheck($s['players'][1]['cards'][0]['upgrades'] === ['LOF#215'], 'an attaching MOVE puts a printed upgrade on its host once');
$s = $fold([$host, ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'TOKEN:weakness#weakness-id', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'LOF#164'], ['seq' => 'R1.A.1b', 't' => 'STATUS_TOKEN', 'card' => 'LOF#164', 'token' => 'weakness', 'count' => 1]]);
swupgnCheck($s['players'][1]['cards'][0]['upgrades'] === [] && $s['players'][1]['cards'][0]['statusTokens'] === ['weakness' => 1], 'a token upgrade is a counter, never in upgrades[]');
$s = $fold([
  $host,
  ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'LOF#215', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'LOF#164'],
  ['seq' => 'R1.A.2a', 't' => 'MOVE', 'card' => 'JTL#058', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'LOF#164'],
  ['seq' => 'R1.A.3a', 't' => 'MOVE', 'card' => 'LOF#215', 'from' => 'ground', 'to' => 'discard', 'p' => 1, 'kind' => 'upgrade'],
  ['seq' => 'R1.A.3b', 't' => 'DEFEAT', 'card' => 'LOF#215', 'reason' => 'frameworkEffect'],
  ['seq' => 'R1.A.4a', 't' => 'MOVE', 'card' => 'JTL#058', 'from' => 'ground', 'to' => 'discard', 'p' => 1, 'kind' => 'unit'],
]);
swupgnCheck($ids($s, 1) === ['LOF#164'] && $s['players'][1]['cards'][0]['upgrades'] === [] && $s['players'][1]['discard'] === ['LOF#215', 'JTL#058'],
  'a defeated upgrade and a departing pilot both come off the host and reach the pile in order');
$s = $fold([$host, ['seq' => 'R1.A.2', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'target' => 'LOF#164'], ['seq' => 'R1.A.3b', 't' => 'DEFEAT', 'card' => 'LOF#215', 'reason' => 'ability']]);
swupgnCheck($s['players'][1]['cards'][0]['upgrades'] === [], 'a DEFEAT alone still detaches');
$s = $fold([['seq' => 'R1.A.1', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'SOR#071', 'target' => 'NOT#TRACKED', 'zone' => 'ground', 'cost' => 3]]);
swupgnCheck($s['players'][1]['cards'] === [], 'PLAY_UPGRADE with an untracked host places nothing');
$s = $fold([
  ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
  ['seq' => 'R1.A.0b', 't' => 'MOVE', 'card' => 'X', 'from' => 'deck', 'to' => 'hand', 'p' => 1],
  ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'SOR#071', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade'],
  ['seq' => 'R1.A.1b', 't' => 'MOVE', 'card' => 'TOKEN:advantage#5844562972', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#095'],
]);
swupgnCheck($ids($s, 1) === ['SOR#095'] && $s['players'][1]['handSize'] === 0, 'an upgrade stays out of the arena but still leaves the hand');
swupgnCheck($ids($fold([['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'TOKEN:battle-droid#3463348370', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 2, 'kind' => 'unit']]), 2) === ['TOKEN:battle-droid#3463348370'], 'a token UNIT enters the arena');

// ── leader, stats, deck count, initiative ────────────────────────────────────
$s = $fold([
  ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
  ['seq' => 'R1.A.0b', 't' => 'STATS', 'card' => 'SOR#095', 'power' => 3, 'hp' => 3, 'keywords' => []],
  ['seq' => 'R1.A.2a', 't' => 'STATS', 'card' => 'SOR#095', 'power' => 5, 'hp' => 3, 'keywords' => ['sentinel', 'raid 2']],
]);
$c = $s['players'][1]['cards'][0];
swupgnCheck($c['power'] === 5 && $c['hp'] === 3 && $c['keywords'] === ['raid 2', 'sentinel'], 'STATS states live power/hp and sorted keywords');
$lead = [
  ['seq' => 'R1.A.1', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'zone' => 'ground', 'epic' => true],
  ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'SOR#010', 'from' => 'base', 'to' => 'ground', 'p' => 1],
  ['seq' => 'R2.A.3a', 't' => 'MOVE', 'card' => 'SOR#010', 'from' => 'ground', 'to' => 'base', 'p' => 1],
  ['seq' => 'R2.A.3b', 't' => 'EXHAUST', 'card' => 'SOR#010'],
];
swupgnCheck($fold(array_slice($lead, 0, 2))['players'][1]['leader'] === ['id' => 'SOR#010', 'deployed' => true, 'exhausted' => false, 'epicActionUsed' => true], 'DEPLOY_LEADER names, deploys and readies the leader, spending its Epic Action');
$home = $fold($lead)['players'][1];
swupgnCheck($home['leader'] === ['id' => 'SOR#010', 'deployed' => false, 'exhausted' => true, 'epicActionUsed' => true] && $home['cards'] === [], 'the leader comes home exhausted on its MOVE back');
swupgnCheck($fold(array_merge($lead, [['seq' => 'R2.G.1', 't' => 'READY', 'card' => 'SOR#010']]))['players'][1]['leader']['exhausted'] === false, 'READY readies the leader');
$seatKf = fn(int $n, array $over = []) => array_merge(['seat' => $n, 'baseHp' => 30, 'baseMaxHp' => 30, 'handSize' => 0, 'hand' => [], 'resourcesReady' => 0, 'resourcesExhausted' => 0, 'credits' => 0, 'hasForce' => false, 'discard' => [], 'cards' => []], $over);
$leaderKf = ['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => ['round' => 1, 'phase' => 'action', 'initiative' => 1, 'players' => [1 => $seatKf(1, ['leader' => ['id' => 'SOR#010', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false]]), 2 => $seatKf(2)]]];
swupgnCheck($fold([$leaderKf, ['seq' => 'R1.A.1', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'ability' => 'x']])['players'][1]['leader']['epicActionUsed'] === false
  && $fold([$leaderKf, ['seq' => 'R1.A.1', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'ability' => 'x', 'epic' => true]])['players'][1]['leader']['epicActionUsed'] === true, 'only an epic ABILITY_ACTIVATE spends the leader\'s Epic Action');
$s = $fold([['seq' => 'R1.A.1', 't' => 'ABILITY_ACTIVATE', 'p' => 2, 'card' => 'base@2', 'kind' => 'epic', 'epic' => true]]);
swupgnCheck(($s['players'][2]['baseEpicActionUsed'] ?? null) === true && !array_key_exists('baseEpicActionUsed', $s['players'][1]), 'a base@N Epic Action marks that seat\'s base, not a leader');
$s = $fold([['seq' => 'R1.A.1a', 't' => 'LEADER_FLIP', 'p' => 1, 'card' => 'TWI#017', 'onStartingSide' => false]]);
swupgnCheck($s['players'][1]['leader']['id'] === 'TWI#017' && $s['players'][1]['leader']['onStartingSide'] === false, 'a LEADER_FLIP seeds the leader when nothing has named it yet');
$deckKf = ['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => ['round' => 1, 'phase' => 'action', 'initiative' => 1, 'initiativeTaken' => false, 'players' => [1 => $seatKf(1, ['deckSize' => 10]), 2 => $seatKf(2)]]];
$s = $fold([
  $deckKf,
  ['seq' => 'R1.A.1', 't' => 'CLAIM_INITIATIVE', 'p' => 2],
  ['seq' => 'R1.G.1', 't' => 'MOVE', 'card' => 'A', 'from' => 'deck', 'to' => 'hand', 'p' => 1],
  ['seq' => 'R1.G.2', 't' => 'MOVE', 'card' => 'B', 'from' => 'hand', 'to' => 'deck', 'p' => 1],
  ['seq' => 'R1.G.3', 't' => 'MOVE', 'card' => 'C', 'from' => 'deck', 'to' => 'hand', 'p' => 1],
  ['seq' => 'R1.G.4', 't' => 'MOVE', 'card' => 'D', 'from' => 'deck', 'to' => 'hand', 'p' => 2],
]);
swupgnCheck($s['players'][1]['deckSize'] === 9 && !array_key_exists('deckSize', $s['players'][2]) && $s['initiative'] === 2 && $s['initiativeTaken'] === true, 'deck count follows MOVE once known; CLAIM_INITIATIVE takes the counter');
swupgnCheck($fold([$deckKf, ['seq' => 'R1.A.1', 't' => 'CLAIM_INITIATIVE', 'p' => 2], ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2]])['initiativeTaken'] === false, 'the initiative status resets each round');
swupgnCheck(($fold([['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'active' => 2]])['active'] ?? null) === 2
  && !array_key_exists('active', $fold([['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'active' => '__proto__']])), 'stores a seat `active`, ignores any other value');

// ── capture ──────────────────────────────────────────────────────────────────
$board = [
  ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
  ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'SOR#095:2', 'from' => 'hand', 'to' => 'ground', 'p' => 2, 'kind' => 'unit'],
];
$captured = array_merge($board, [
  ['seq' => 'R1.A.2a', 't' => 'MOVE', 'card' => 'SOR#095:2', 'from' => 'ground', 'to' => 'capture', 'p' => 2, 'kind' => 'unit'],
  ['seq' => 'R1.A.2b', 't' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#095:2', 'by' => 'SOR#095'],
]);
$s = $fold($captured);
swupgnCheck($s['players'][2]['cards'] === [] && $s['players'][1]['cards'][0]['captured'] === ['SOR#095:2'], 'CAPTURE files the card under its captor');
$ok = true;
foreach ([
  [['seq' => 'R2.A.1a', 't' => 'RESCUE', 'p' => 2, 'card' => 'SOR#095:2'], ['seq' => 'R2.A.1b', 't' => 'MOVE', 'card' => 'SOR#095:2', 'from' => 'capture', 'to' => 'ground', 'p' => 2, 'kind' => 'unit']],
  [['seq' => 'R2.A.1a', 't' => 'MOVE', 'card' => 'SOR#095:2', 'from' => 'capture', 'to' => 'ground', 'p' => 2, 'kind' => 'unit'], ['seq' => 'R2.A.1b', 't' => 'RESCUE', 'p' => 2, 'card' => 'SOR#095:2']],
] as $order) {
  $s = $fold(array_merge($captured, $order));
  if ($s['players'][1]['cards'][0]['captured'] !== [] || $ids($s, 2) !== ['SOR#095:2']) $ok = false;
}
swupgnCheck($ok, 'RESCUE and the MOVE out of capture put it back, in either order');
$s = $fold(array_merge($board, [['seq' => 'R1.A.2b', 't' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#095:2', 'by' => 'base@1']]));
swupgnCheck($s['players'][2]['cards'] === [] && $s['players'][1]['cards'][0]['captured'] === [], 'a CAPTURE by a base still takes the card off the board');

// ── untrusted input ──────────────────────────────────────────────────────────
$s = $fold([['seq' => 'R1.A.1', 't' => 'MOVE', 'p' => 7, 'card' => 'X#1', 'from' => 'deck', 'to' => 'hand']]);
swupgnCheck(array_keys($s['players']) === [1, 2] && $s['players'][1]['handSize'] === 0 && $s['players'][2]['handSize'] === 0, 'ignores counts for a seat that is not 1 or 2');
$s = $fold([['seq' => 'R1.A.1', 't' => 'MOVE', 'p' => '1', 'card' => 'X#1', 'from' => 'deck', 'to' => 'hand']]);
swupgnCheck($s['players'][1]['handSize'] === 0, 'a string "1" is not a seat');
$s = $fold([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground', 'cost' => 2], ['seq' => 'R1.A.2', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'ground', 'to' => 'discard']]);
swupgnCheck($card($s, 1, 'SOR#108')['zone'] === 'discard' && $s['players'][1]['handSize'] === 0, 'a seatless MOVE only updates a tracked card\'s zone');
$s = $fold([['seq' => 'R1.A.1', 't' => 'MOVE', 'card' => 'NOT#TRACKED', 'from' => 'deck', 'to' => 'hand']]);
swupgnCheck($s['players'][1]['cards'] === [] && $s['players'][1]['handSize'] === 0, 'a seatless MOVE for an untracked card does nothing');
$hostile = [
  ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground'],
  ['seq' => 'R1.end', 't' => 'ROUND_END', 'round' => 1, 'keyframe' => ['players' => (object)[]]],
  ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => 1],
  ['seq' => 'R2.A.1', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => ['round' => 2, 'phase' => 'action', 'initiative' => 1, 'players' => [1 => ['cards' => 'x', 'hand' => [], 'discard' => []], 2 => (object)[]]]],
  ['seq' => 'R2.A.1a', 't' => 'DRAW', 'p' => 1, 'count' => 1, 'cards' => 5],
  ['seq' => 'R2.A.1b', 't' => 'DISCARD', 'p' => 1, 'cards' => null],
];
$s = null;
swupgnCheck(swupgnNoThrow(function () use (&$s, $fold, $hostile) { $s = $fold($hostile); }) && $s['round'] === 2 && $ids($s, 1) === ['SOR#108'], 'ignores damaged keyframes and non-array fields, and keeps folding');
$halfCard = ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => ['round' => 2, 'phase' => 'action', 'initiative' => 1, 'players' => [1 => $seatKf(1, ['cards' => [['id' => 'SOR#108', 'zone' => 'ground']]]), 2 => $seatKf(2)]]];
swupgnCheck(swupgnNoThrow(fn() => $fold([$halfCard, ['seq' => 'R2.A.1', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'ground', 'to' => 'discard', 'p' => 1]])) && !SwuPgnIsCompleteKeyframe(swupgnEvents($halfCard)['keyframe']),
  'refuses to snap to a keyframe whose card lacks the list the fold dereferences');
$withKeyframes = [
  ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#001', 'zone' => 'ground'],
  ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => ['round' => 2, 'phase' => 'action', 'initiative' => 2, 'players' => [1 => $seatKf(1), 2 => $seatKf(2)]]],
  ['seq' => 'R2.A.1', 't' => 'PLAY', 'p' => 2, 'card' => 'SOR#002', 'zone' => 'space'],
  ['seq' => 'R2.A.2', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#003', 'zone' => 'ground'],
];
$at = SwuPgnStateAt(swupgnEvents($withKeyframes), 'R2.A.1');
swupgnCheck($at === $fold(array_slice($withKeyframes, 0, 3)) && $ids($at, 2) === ['SOR#002'] && $ids($at, 1) === [], 'stateAt from the nearest keyframe equals a fold from the start');
$many = array_map(fn($i) => "TOKEN:x$i", range(0, 1004));
$s = $fold([['seq' => 'R1.A.1', 't' => 'DRAW', 'p' => 1, 'count' => count($many), 'cards' => $many]]);
swupgnCheck(count($s['players'][1]['hand']) === 1000 && $s['players'][1]['hand'][999] === 'TOKEN:x999' && !in_array('TOKEN:x1000', $s['players'][1]['hand'], true), 'zone lists are capped at 1000 ids');
$arena = [];
for ($i = 0; $i < 1500; $i++) $arena[] = ['seq' => "R1.A.$i", 't' => 'MOVE', 'card' => "SOR#$i", 'from' => 'hand', 'to' => 'ground', 'p' => 1];
swupgnCheck(count($fold($arena)['players'][1]['cards']) === 1000, 'arena growth is capped at 1000 cards');

// ── keyframe gate ────────────────────────────────────────────────────────────
$gate = fn(array $events) => SwuPgnCheckKeyframes(swupgnEvents($events));
$paths = fn(array $r) => array_column($r['mismatches'], 'path');
$roundStart = fn(int $round, array $p1, array $p2) => ['seq' => "R$round.start", 't' => 'ROUND_START', 'round' => $round, 'keyframe' => ['round' => $round, 'phase' => 'setup', 'initiative' => null, 'players' => [1 => $p1, 2 => $p2]]];
$cardKf = fn(string $id, array $over = []) => array_merge(['id' => $id, 'zone' => 'ground', 'damage' => 0, 'exhausted' => false, 'upgrades' => [], 'shields' => 0, 'experience' => 0, 'statusTokens' => (object)[], 'captured' => []], $over);

swupgnCheck($gate([['seq' => 'R1.A.1a', 't' => 'DAMAGE', 'src' => 'X', 'tgt' => 'base@2', 'amt' => 2, 'damageType' => 'combat', 'hp' => 28], $roundStart(2, $seatKf(1), $seatKf(2, ['baseHp' => 28]))])['ok'] === true, 'passes when the fold matches each keyframe');
$r = $gate([$roundStart(1, $seatKf(1), $seatKf(2)), $roundStart(2, $seatKf(1), $seatKf(2, ['baseHp' => 28]))]);
swupgnCheck($r['ok'] === false && $r['mismatches'][0]['seq'] === 'R2.start' && $r['mismatches'][0]['path'] === 'players.2.baseHp', 'fails when a delta before a keyframe is missing');
swupgnCheck($gate([$roundStart(1, $seatKf(1, ['baseHp' => 33, 'baseMaxHp' => 33]), $seatKf(2, ['baseHp' => 28, 'baseMaxHp' => 28]))])['ok'] === true, 'baseHp is exempt at the first keyframe');
swupgnCheck($paths($gate([$roundStart(1, $seatKf(1, ['baseHp' => 33, 'handSize' => 2]), $seatKf(2, ['baseHp' => 28]))])) === ['players.1.handSize'], 'every other field is compared at the first keyframe');
$r = $gate([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#232', 'zone' => 'ground'], $roundStart(2, $seatKf(1, ['cards' => [$cardKf('SOR#232', ['damage' => 2])]]), $seatKf(2))]);
$dmg = array_values(array_filter($r['mismatches'], fn($m) => $m['path'] === 'players.1.cards[SOR#232].damage'));
swupgnCheck($r['ok'] === false && count($dmg) === 1 && $dmg[0]['expected'] === 2 && $dmg[0]['got'] === 0, 'reports a per-card field mismatch with expected and got');
$r = $gate([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'P1#unit', 'zone' => 'ground', 'cost' => 2], $roundStart(2, $seatKf(1), $seatKf(2))]);
swupgnCheck(in_array(['seq' => 'R2.start', 'path' => 'players.1.cards[P1#unit]', 'expected' => 'absent', 'got' => 'present'], $r['mismatches'], true), 'reports a card the fold holds that the keyframe omits');

$mv = fn(string $seq, string $c, string $from, string $to, int $p) => ['seq' => $seq, 't' => 'MOVE', 'card' => $c, 'from' => $from, 'to' => $to, 'p' => $p];
$organic = [$roundStart(1, $seatKf(1), $seatKf(2))];
foreach (['d1', 'd2', 'd3', 'd4', 'd5', 'd6', 'unit'] as $i => $c) $organic[] = $mv("R1.S.$i", "P1#$c", 'deck', 'hand', 1);
foreach (['e1', 'e2', 'e3', 'e4', 'e5', 'e6', 'unit'] as $i => $c) $organic[] = $mv('R1.S.' . ($i + 7), "P2#$c", 'deck', 'hand', 2);
array_push($organic,
  $mv('R1.S.14', 'P1#d1', 'hand', 'resource', 1), $mv('R1.S.15', 'P1#d2', 'hand', 'resource', 1),
  $mv('R1.S.16', 'P2#e1', 'hand', 'resource', 2), $mv('R1.S.17', 'P2#e2', 'hand', 'resource', 2),
  ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'P1#unit', 'zone' => 'ground', 'cost' => 2], $mv('R1.A.1a', 'P1#unit', 'hand', 'ground', 1),
  ['seq' => 'R1.A.2', 't' => 'ATTACK', 'p' => 1, 'atk' => 'P1#unit', 'def' => 'base@2', 'defenderType' => 'base'],
  ['seq' => 'R1.A.2a', 't' => 'DAMAGE', 'src' => 'P1#unit', 'tgt' => 'base@2', 'amt' => 2, 'damageType' => 'combat', 'hp' => 28],
  $roundStart(2, $seatKf(1, ['handSize' => 4, 'resourcesReady' => 2, 'hand' => ['P1#d3', 'P1#d4', 'P1#d5', 'P1#d6'], 'cards' => [$cardKf('P1#unit')]]),
    $seatKf(2, ['handSize' => 5, 'resourcesReady' => 2, 'baseHp' => 28, 'hand' => ['P2#e3', 'P2#e4', 'P2#e5', 'P2#e6', 'P2#unit']])),
  ['seq' => 'R2.A.1', 't' => 'PLAY', 'p' => 2, 'card' => 'P2#unit', 'zone' => 'ground', 'cost' => 2], $mv('R2.A.1a', 'P2#unit', 'hand', 'ground', 2),
  $mv('R2.A.2', 'P1#d3', 'hand', 'resource', 1),
  ['seq' => 'R2.A.3', 't' => 'ATTACK', 'p' => 2, 'atk' => 'P2#unit', 'def' => 'P1#unit', 'defenderType' => 'unit'],
  ['seq' => 'R2.A.3a', 't' => 'DAMAGE', 'src' => 'P2#unit', 'tgt' => 'P1#unit', 'amt' => 3, 'damageType' => 'combat', 'hp' => 0],
  ['seq' => 'R2.A.3b', 't' => 'EXHAUST', 'card' => 'P2#unit'],
  $roundStart(3, $seatKf(1, ['handSize' => 3, 'resourcesReady' => 3, 'hand' => ['P1#d4', 'P1#d5', 'P1#d6'], 'cards' => [$cardKf('P1#unit', ['damage' => 3])]]),
    $seatKf(2, ['handSize' => 4, 'resourcesReady' => 2, 'baseHp' => 28, 'hand' => ['P2#e3', 'P2#e4', 'P2#e5', 'P2#e6'], 'cards' => [$cardKf('P2#unit', ['exhausted' => true])]]))
);
$r = $gate($organic);
swupgnCheck($r['mismatches'] === [] && $r['ok'] === true, 'a clean three-round stream reconstructs counts, hand contents and card state at every keyframe');

$kfEnd = fn(string $seq, array $p1, array $p2) => ['seq' => $seq, 't' => 'ROUND_END', 'round' => 1, 'keyframe' => ['round' => 1, 'phase' => 'regroup', 'initiative' => null, 'players' => [1 => $p1, 2 => $p2]]];
$r = $gate([
  ['seq' => 'R0.S.1', 't' => 'MOVE', 'card' => 'A', 'from' => 'hand', 'to' => 'resource', 'p' => 1],
  ['seq' => 'R0.S.2', 't' => 'MOVE', 'card' => 'B', 'from' => 'hand', 'to' => 'resource', 'p' => 1],
  ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
  ['seq' => 'R1.A.0b', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2],
  ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#095', 'zone' => 'ground', 'cost' => 2],
  ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'LOF#215', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#095'],
  ['seq' => 'R1.A.2', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'target' => 'SOR#095'],
  ['seq' => 'R1.A.3a', 't' => 'MOVE', 'card' => 'TOKEN:credit#8015500527', 'from' => 'outsideTheGame', 'to' => 'base', 'p' => 2],
  $kfEnd('R1.end', $seatKf(1, ['resourcesReady' => 0, 'resourcesExhausted' => 2, 'cards' => [$cardKf('SOR#095', ['upgrades' => ['LOF#215']])]]), $seatKf(2, ['credits' => 1])),
]);
swupgnCheck($r['mismatches'] === [], 'resources, a Credit token and an attachment all reconcile');
$r = $gate([
  $kfEnd('R1.start', $seatKf(1), $seatKf(2)),
  ['seq' => 'R2.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
  ['seq' => 'R2.A.0b', 't' => 'MOVE', 'card' => 'SOR#095:2', 'from' => 'hand', 'to' => 'ground', 'p' => 2, 'kind' => 'unit'],
  $kfEnd('R2.end', $seatKf(1, ['resourcesExhausted' => 1, 'hasForce' => true, 'cards' => [$cardKf('SOR#095', ['upgrades' => ['LOF#215'], 'captured' => ['SOR#095:2']])]]), $seatKf(2, ['credits' => 1, 'cards' => [$cardKf('SOR#095:2')]])),
]);
$p = $paths($r); sort($p);
swupgnCheck($p === ['players.1.cards[SOR#095].captured', 'players.1.cards[SOR#095].upgrades', 'players.1.hasForce', 'players.1.resourcesExhausted', 'players.2.credits'], 'reports each under-recorded field');
$noCaptured = $cardKf('SOR#095', ['upgrades' => ['A', 'B']]); unset($noCaptured['captured']);
swupgnCheck($gate([
  ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
  ['seq' => 'R1.A.1', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'B', 'target' => 'SOR#095'],
  ['seq' => 'R1.A.2', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'A', 'target' => 'SOR#095'],
  $kfEnd('R1.end', $seatKf(1, ['cards' => [$noCaptured]]), $seatKf(2)),
])['mismatches'] === [], 'compares upgrades and captured as sets, a missing list as empty');
$enter = ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'];
$stats = ['seq' => 'R1.A.0b', 't' => 'STATS', 'card' => 'SOR#095', 'power' => 5, 'hp' => 3, 'keywords' => ['raid 2', 'sentinel']];
swupgnCheck($gate([$enter, $stats, $kfEnd('R1.end', $seatKf(1, ['cards' => [$cardKf('SOR#095', ['power' => 5, 'hp' => 3, 'keywords' => ['sentinel', 'raid 2']])]]), $seatKf(2))])['mismatches'] === []
  && $paths($gate([$enter, $kfEnd('R1.end', $seatKf(1, ['cards' => [$cardKf('SOR#095', ['power' => 5, 'hp' => 3])]]), $seatKf(2))])) === ['players.1.cards[SOR#095].power', 'players.1.cards[SOR#095].hp']
  && $gate([$enter, $stats, $kfEnd('R1.end', $seatKf(1, ['cards' => [$cardKf('SOR#095')]]), $seatKf(2))])['mismatches'] === [], 'compares power/hp/keywords only when the keyframe states them');
$leader = fn(array $over = []) => array_merge(['id' => 'SOR#010', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false], $over);
$leaderEvents = [
  ['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => ['round' => 1, 'phase' => 'action', 'initiative' => 1, 'initiativeTaken' => false, 'players' => [1 => $seatKf(1, ['deckSize' => 40, 'leader' => $leader()]), 2 => $seatKf(2, ['deckSize' => 40, 'leader' => $leader(['id' => 'SOR#005'])])]]],
  ['seq' => 'R1.A.1', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'zone' => 'ground', 'epic' => true],
  ['seq' => 'R1.A.1a', 't' => 'MOVE', 'card' => 'SOR#010', 'from' => 'base', 'to' => 'ground', 'p' => 1],
  ['seq' => 'R1.A.2', 't' => 'CLAIM_INITIATIVE', 'p' => 2],
  ['seq' => 'R1.A.2a', 't' => 'EXHAUST', 'card' => 'SOR#005'],
  ['seq' => 'R1.G.1', 't' => 'MOVE', 'card' => 'X', 'from' => 'deck', 'to' => 'hand', 'p' => 1],
  ['seq' => 'R1.end', 't' => 'ROUND_END', 'round' => 1, 'keyframe' => ['round' => 1, 'phase' => 'regroup', 'initiative' => 2, 'initiativeTaken' => true, 'players' => [
    1 => $seatKf(1, ['handSize' => 1, 'hand' => ['X'], 'deckSize' => 39, 'leader' => $leader(['deployed' => true, 'epicActionUsed' => true]), 'cards' => [$cardKf('SOR#010')]]),
    2 => $seatKf(2, ['deckSize' => 40, 'leader' => $leader(['id' => 'SOR#005', 'exhausted' => true])])]]],
  ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => ['round' => 2, 'phase' => 'action', 'initiative' => 2, 'initiativeTaken' => false, 'players' => [
    1 => $seatKf(1, ['handSize' => 1, 'hand' => ['X'], 'deckSize' => 39, 'leader' => $leader(['deployed' => true, 'epicActionUsed' => true]), 'cards' => [$cardKf('SOR#010')]]),
    2 => $seatKf(2, ['deckSize' => 40, 'leader' => $leader(['id' => 'SOR#005', 'exhausted' => true])])]]],
];
swupgnCheck($gate($leaderEvents)['mismatches'] === [], 'gates the leader, the deck count and the initiative status — clean stream');
$wrong = swupgnEvents($leaderEvents);
$wrong[6]['keyframe']['initiativeTaken'] = false;
$wrong[6]['keyframe']['players'][1]['deckSize'] = 38;
$wrong[6]['keyframe']['players'][1]['leader']['epicActionUsed'] = false;
$wrong[6]['keyframe']['players'][2]['leader']['exhausted'] = false;
$p = array_column(array_filter(SwuPgnCheckKeyframes($wrong)['mismatches'], fn($m) => $m['seq'] === 'R1.end'), 'path'); sort($p);
swupgnCheck($p === ['initiativeTaken', 'players.1.deckSize', 'players.1.leader.epicActionUsed', 'players.2.leader.exhausted'], '...and reports each one when wrong');
$r = $gate([
  ['seq' => 'R1.A.1', 't' => 'MOVE', 'card' => 'A', 'from' => 'hand', 'to' => 'discard', 'p' => 1],
  ['seq' => 'R1.A.2', 't' => 'MOVE', 'card' => 'B', 'from' => 'hand', 'to' => 'discard', 'p' => 1],
  $kfEnd('R1.end', $seatKf(1, ['discard' => ['B', 'A']]), $seatKf(2)),
]);
swupgnCheck($paths($r) === ['players.1.discard'], 'the discard pile is compared IN ORDER: the same cards out of order is a mismatch');
$r = $gate([$enter, $kfEnd('R1.end', $seatKf(1, ['cards' => [$cardKf('SOR#095', ['power' => 5])]]), $seatKf(2))]);
swupgnCheck(count($r['mismatches']) === 1 && $r['mismatches'][0]['expected'] === 5 && !array_key_exists('got', $r['mismatches'][0]),
  'a side the fold never recorded is ABSENT from the mismatch, not null');
$noId = $leader(); unset($noId['id']);
$r = $gate([
  ['seq' => 'R1.A.1', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'zone' => 'ground'],
  $kfEnd('R1.end', $seatKf(1, ['leader' => array_merge($noId, ['deployed' => true]), 'cards' => [$cardKf('SOR#010')]]), $seatKf(2)),
]);
swupgnCheck(count($r['mismatches']) === 1 && $r['mismatches'][0]['path'] === 'players.1.leader.id' && !array_key_exists('expected', $r['mismatches'][0]) && $r['mismatches'][0]['got'] === 'SOR#010',
  'a field the keyframe never stated is ABSENT from the mismatch, not null');
$r = $gate([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground'], ['seq' => 'R1.end', 't' => 'ROUND_END', 'round' => 1, 'keyframe' => ['players' => [1 => $seatKf(1)]]]]);
swupgnCheck($paths($r) === ['keyframe'], 'a damaged keyframe is reported once and never snapped to');

swupgnFinish();
