<?php
// Indirect damage is assigned IN FULL (CR 35.3; SWUDealIndirectDamage sizes the pool "assign as much as
// possible"). SWUValidateDecisionAnswer used to accept "-"/PASS and any under-assignment for every MZSPLITASSIGN,
// so the damaged player could cancel indirect damage. Found by the RL-bot diagnosis 2026-09-14 (Boba: 186 indirect
// prompts answered "-", 0 damage dealt).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/indirect_assignment_validation_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Custom/BotLookahead.php';

// JTL_237 TIE Bomber attacks the base: "Deal 3 indirect damage to the defending player." P2 has a Marine (3 HP)
// and a TIE/ln (1 HP), so the assignment is a real choice (one sink would auto-resolve).
$build(function ($b) {
    $b->MyLeader('SOR_014', false);
    $b->WithSpaceUnitForPlayer(1, 'JTL_237', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    $b->WithSpaceUnitForPlayer(2, 'SOR_225', true);
});
$act(1, 10002, 'mySpaceArena-0!FSM!');
$act(1, 100, 'theirBase-0');
$head = null; foreach (GetDecisionQueue(2) as $d) { if (empty($d->removed)) { $head = $d; break; } }
$check($head !== null && $head->Type === 'MZSPLITASSIGN' && $head->Tooltip === 'Assign_3_indirect_damage', 'fixture: P2 owes the indirect assignment');
$check(SWUValidateDecisionAnswer(2, '-') === false, 'a decline ("-") is refused');
$check(SWUValidateDecisionAnswer(2, 'PASS') === false, 'PASS is refused');
$check(SWUValidateDecisionAnswer(2, '') === false, 'an empty answer is refused');
$check(SWUValidateDecisionAnswer(2, 'myBase-0:2') === false, 'assigning 2 of 3 is refused');
$check(SWUValidateDecisionAnswer(2, 'myBase-0:3') === true, 'all 3 on the base is legal');
$check(SWUValidateDecisionAnswer(2, 'myGroundArena-0:2,myBase-0:1') === true, 'a full split is legal');
$check(SWUValidateDecisionAnswer(2, 'myGroundArena-0:4') === false, 'over the pool is still refused');

bot_test_finish();
