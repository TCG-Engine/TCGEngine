<?php
// A second copy of a unique unit over a WORN first copy is a refresh: the uniqueness rule defeats the worn one, and the fresh
// one arrives without its damage or its Weakness tokens (feature 'uniquerefresh', p41).
// FOUND 2026-10-07 in a human game (NininTCG Hemlock Red vs a Wicket Green guest, R7): the guest replayed Wicket (ASH_034, 1 cost;
// the copy in play had a Weakness token and 1 damage) and Logray (HMW_045, 2 cost; the copy in play had 4 of 5 damage).
// The bot: 'unique' read a Weakness-ed copy as HEALTHY (its current HP already includes the -1, so remaining >= hp) and held the
// play at -0.5; 'picks' charged a damaged copy's replay as a wasted card (develop x cost + 1) unless 'uniquereplay' (cost <= 3 with a
// When Played) applied — neither card has one. Against Hemlock, whose deck hands out Weakness all game, a 1-cost refresh is cheap.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_uniquerefresh_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
$check(SWUBotVariantDisabled('no-uniquerefresh') === ['uniquerefresh'], 'uniquerefresh is switchable');
$playScore = function (string $style) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === 'myHand-0!FSM!') return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$off = function () { $GLOBALS['SWUBotDisabledFeatures'] = ['uniquerefresh']; };
$on  = function () { $GLOBALS['SWUBotDisabledFeatures'] = []; };

// My copy of $cid in play (with $damage and $weak Weakness tokens), the second copy in hand. The opponent has taken the
// initiative, so Pass (0) is the alternative.
$board = function (string $cid, int $damage, int $weak) {
    return function ($b) use ($cid, $damage, $weak) { $b->MyLeader('HMW_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 4);
        $b->WithCardInHandForPlayer(1, $cid);
        $b->WithGroundUnitForPlayer(1, $cid, false, $damage);
        if ($weak > 0) $b->WithUpgradesOnGroundUnitForPlayer(1, 0, array_fill(0, $weak, GameStateBuilder::Upgrade('HMW_T02', 2)));
        $b->WithGroundUnitForPlayer(2, 'LOF_130', false);
        $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); };
};

// The Weakness-ed Wicket (2/2, undamaged): worn, not healthy.
$build($board('ASH_034', 0, 1));
$wk = SWUBotViewForMz(1, 'myGroundArena-0');
$check($wk['hp'] === 2 && $wk['remaining'] === 2 && $wk['downgrades'] === 1, 'fixture: the Weakness-ed Wicket reads 2/2, one downgrade');
$off(); $sOff = $playScore('normal'); $on(); $sOn = $playScore('normal');
$check($sOff === -0.5, '@no-uniquerefresh: the replay was held as over a "healthy" copy (the reported blind spot); got ' . json_encode($sOff));
// Priced, not held: the clash charge shrinks by the copy's wear — (damage + Weakness) / printed HP, here 1/3. A 1-drop over a copy that
// is still two-thirds intact stays below Pass; the refresh pays off on heavier wear (the Logray below).
$check($sOn !== -0.5 && $sOn > $sOff, 'a Weakness-ed copy is priced by its wear, no longer held as healthy; got ' . json_encode($sOn));

// The log's Wicket: a Weakness token AND 1 damage (1 HP left of 2) — worth more than the Weakness alone.
$build($board('ASH_034', 1, 1));
$on(); $sWorn = $playScore('normal');
$check($sWorn > $sOn, 'more wear, more refresh: Weakness + 1 damage scores above Weakness alone; got ' . round($sWorn, 3) . ' vs ' . round($sOn, 3));

// The log's Logray: 4 of 5 damage, no When Played (so 'uniquereplay' does not apply).
$build($board('HMW_045', 4, 0));
$off(); $sOff = $playScore('normal'); $on(); $sOn = $playScore('normal');
$check($sOff <= 0, '@no-uniquerefresh: the 4-damage Logray replay was a wasted card; got ' . json_encode($sOff));
$check($sOn > 0, 'a fresh Logray over the 1-HP one is worth playing; got ' . json_encode($sOn));

// A healthy copy stays held: nothing to refresh.
$build($board('ASH_034', 0, 0));
$on(); $check($playScore('normal') === -0.5, 'over a healthy copy the second copy is still held');

// End to end: the bot plays the fresh Wicket and the uniqueness rule's choice defeats the WORN copy.
$build($board('ASH_034', 1, 1));
$on();
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('normal');
$check($ctx['tooltip'] === 'Uniqueness_rule_choose_a_copy_to_defeat', 'fixture: the uniqueness rule asks which copy to defeat; got ' . $ctx['tooltip']);
$pick = SWUBotHeuristicChoose('normal', $ctx['actions'], SWUBotLegalActions($gameName, 1), '');
$worn = null;
foreach (['myGroundArena-0', 'myGroundArena-1'] as $mz) { $v = SWUBotViewForMz(1, $mz); if ($v !== null && $v['downgrades'] > 0) $worn = $mz; }
$check($worn !== null && strval($pick['cardID'] ?? '') === $worn, 'the WORN copy is the one defeated; worn=' . json_encode($worn) . ' pick=' . json_encode($pick['cardID'] ?? null));

bot_test_finish();
