<?php
// Phase 1b part 2, Task 2 — indirect and split damage (feature 'splits').
// Diagnosis 2026-09-14 (boba_lakecountry): with 2+ targets the only indirect candidate was "-" and the engine
// accepted it, so indirect damage never landed; "Choose a player" picked You 118/118; split answers were unscored.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_splits_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$check(SWUBotVariantDisabled('no-splits') === ['splits'], 'the splits feature is switchable');

// ── A REAL indirect prompt: the TIE Bomber (0/4, "On Attack: Deal 3 indirect damage to the defending player")
// attacks; P2 (Marine 3/3 on the ground, TIE/ln 2/1 in space) assigns 3.
$build(function ($b) {
    $b->MyLeader('SOR_014', false);
    $b->WithSpaceUnitForPlayer(1, 'JTL_237', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    $b->WithSpaceUnitForPlayer(2, 'SOR_225', true);
});
$act(1, 10002, 'mySpaceArena-0!FSM!');
$check($botCtx('aggro')['tooltip'] === 'Choose_an_attack_target', 'fixture: the Bomber chooses its target');
$act(1, 100, 'theirBase-0');
$ctx2 = $botCtx('aggro', 2);
$check(SWUBotPendingDecisionSeat() === 2 && $ctx2['tooltip'] === 'Assign_3_indirect_damage', 'fixture: P2 owes the assignment; got ' . $ctx2['tooltip']);
$cands = $ids($ctx2['actions']);
$check(!in_array('-', $cands, true) && !in_array('PASS', $cands, true), 'no decline among the candidates');
$sums = array_map(fn($c) => array_sum(array_map(fn($p) => intval(explode(':', $p)[1]), explode(',', $c))), $cands);
$check(count($cands) >= 3 && array_unique($sums) === [3], 'every candidate assigns exactly 3; got ' . json_encode($cands));
$check(in_array('myGroundArena-0:2,myBase-0:1', $cands, true), 'the soak split (the Marine survives on 1 HP) is offered');
$check(count(array_filter($cands, fn($c) => !SWUValidateDecisionAnswer(2, $c))) === 0, 'the engine accepts every candidate');
$check($stack('aggro', 2)[0] === 'myGroundArena-0:2,myBase-0:1', 'P2 soaks 2 on the Marine and 1 on its base — no unit lost');
$check($stack('aggro', 2, 'no-splits')[0] !== 'myGroundArena-0:2,myBase-0:1', '@no-splits: the old scorer (which cannot read a split) does not find the soak');

// ── The split scorer on the ENEMY board (a Devastator-style assignment), per style.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$Wa = SWUBotWeights('aggro', 1); $Wc = SWUBotWeights('control', 1);
$killMarine = 'theirGroundArena-0:3,theirBase-0:1'; $killTie = 'theirSpaceArena-0:1,theirBase-0:3';
$check(_SWUBotSplitScore(1, $killTie, $Wa, true) > _SWUBotSplitScore(1, $killMarine, $Wa, true), 'Aggro: the base-heavy split');
$check(_SWUBotSplitScore(1, $killMarine, $Wc, true) > _SWUBotSplitScore(1, $killTie, $Wc, true), 'Control: defeat the bigger unit');
// A Shield stops divided damage (one instance) but not indirect damage (unpreventable).
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]); });
$check(_SWUBotSplitScore(1, 'theirGroundArena-0:3', $Wc, false) === 0.0, 'divided damage into a Shield: worth nothing');
$check(_SWUBotSplitScore(1, 'theirGroundArena-0:3', $Wc, true) > 0.0, 'indirect damage ignores the Shield: the kill counts');

// ── "Choose a player" for indirect damage: Fett's Firespray's When Played.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'JTL_240'); });
$act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('aggro')['tooltip'] === 'Choose_a_player_to_deal_indirect_damage', 'fixture: Firespray asks for a player; got ' . $botCtx('aggro')['tooltip']);
$check($stack('aggro')[0] === 'Opponent', 'the indirect damage goes to the opponent');
$check($stack('aggro', 1, 'no-splits')[0] === 'You', '@no-splits: "You", the old first-legal pick');

bot_test_finish();
