<?php
// RL Phase 3, Task 1 — the swu-v1 state and move keys (spec Section 3). Pure functions of the loaded game and the
// stack's decision context; the learned table is indexed by them, so the same decision must always give the same key.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/rl_keys_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$move = function (array $ctx, string $cardID) {
    foreach ($ctx['actions'] as $a) if (strval($a['cardID']) === $cardID) return SWURlMoveKey($ctx, $a);
    return 'MISSING:' . $cardID;
};
$has = fn(string $key, string $part) => str_contains($key, $part);

// (a) A free-play state. Seat 1: a Wampa (4/5) on the ground, 3 ready resources, 2 cards in hand, an undeployed
// Sabine it cannot deploy yet. Seat 2: a Marine on the ground, Redemption (a SPACE Sentinel), and the initiative.
$build(function ($b) {
    $b->MyLeader('SOR_014', true); $b->FillResourcesForPlayer(1, 'SOR_095', 3);
    $b->WithCardInHandForPlayer(1, 'SOR_063'); $b->WithCardInHandForPlayer(1, 'SOR_078');
    $b->WithGroundUnitForPlayer(1, 'SOR_164', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithSpaceUnitForPlayer(2, 'SOR_052', true);
    $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed();
});
$ctx = $botCtx('normal');
$s = SWURlStateKey($ctx);
$check(str_starts_with($s, 'swu-v1|free|'), 'free-play state key: ' . $s);
$check($has($s, '|res=3-4|') && $has($s, '|ini=them|') && $has($s, '|hand=2-3|') && $has($s, '|ldr=not-yet'), 'resources, initiative, hand size, leader');
$check($has($s, 'lead:g=ahead') && $has($s, ',s=behind') && $has($s, 'sent:g=0,s=1'), 'lead per arena and enemy Sentinel per arena');
$check(preg_match('/\|me=c(1|2|3|4\+)\|op=c(1|2|3|4\+)\|/', $s) === 1, 'both clocks bucketed');
$check(SWURlStateKey($ctx) === $s, 'the same state gives the same key');

// (b) hand plays: Cloud City Wing Guard (3-cost Sentinel unit, ground) and Vanquish (5-cost removal event). Neither
// is affordable on this board; the key reads the hand card, so the play action is built directly.
$play = fn(int $i) => SWURlMoveKey($ctx, ['cardID' => "myHand-$i!FSM!", 'mode' => 10002]);
$check($play(0) === 'play:unit:3-4:ground:Sentinel', 'unit play key; got ' . $play(0));
$check($play(1) === 'play:event:5-6:removal', 'event play key; got ' . $play(1));
// (c) the verbs.
$check($move($ctx, 'myHealth-0!CustomInput!Pass') === 'pass', 'pass');
$check($move($ctx, 'myGroundArena-0!FSM!') === 'attack:ground', 'attack with a ground unit');

// (d) the attack-target prompt: the base, or the Marine (the Wampa kills it and survives).
$raiseAttack(1, 'myGroundArena-0');
$dctx = $botCtx('normal');
$check(str_starts_with(SWURlStateKey($dctx), 'swu-v1|d:'), 'a decision state key starts with d:; got ' . SWURlStateKey($dctx));
$check($move($dctx, 'theirBase-0') === 'atk:base', 'attack-target: the base; got ' . $move($dctx, 'theirBase-0'));
$check($move($dctx, 'theirGroundArena-0') === 'atk:unit:kill-survive', 'attack-target: the Marine; got ' . $move($dctx, 'theirGroundArena-0'));

// (e) a target prompt (Vanquish): the enemy Wampa → role, power, remaining HP, cost.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 7); $b->WithCardInHandForPlayer(1, 'SOR_078');
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$tctx = $botCtx('control');
$check($move($tctx, 'theirGroundArena-0') === 'target:enemy-unit:4-5:4-5:3-4', 'target key: enemy Wampa; got ' . $move($tctx, 'theirGroundArena-0'));
$check($move($tctx, 'myGroundArena-0') === 'target:own-unit:2-3:2-3:1-2', 'target key: my Marine; got ' . $move($tctx, 'myGroundArena-0'));

// (f) a YESNO (Talzin's Assassin, "Use the Force to give a unit -3/-3?").
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithForceForPlayer(1); $b->WithCardInHandForPlayer(1, 'LOF_035');
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true); });
$act(1, 10002, 'myHand-0!FSM!');
$yctx = $botCtx('normal');
$check($move($yctx, 'YES') === 'yes' && $move($yctx, 'NO') === 'no', 'yes / no');

bot_test_finish();
