<?php
// THE MIDRANGE PLAY ARMS — 'mgsentinel' (play half), 'mgremoval' and 'mgmull'. All default OFF.
// Owner rulings 2026-09-23 (bot-sweeps/2026-09-23_midrange_rulings.md):
//   2/4. "Playing: priority against aggro — a Sentinel goes down ahead of a bigger non-Sentinel body." And:
//        "Play a body before attacking ONLY for Sentinels. Otherwise judge the board by POWER, not body count."
//   3.   Removal timing: shrinkfirst's shape with a higher bar — "the target must have COST 5+; usually best to
//        remove a threat they just wasted resources on."
//   5.   Mulligans: the keep test is matchup-dependent. THE BOT HAS NEVER MULLIGANED, and all three traced Luke
//        ASH openings were mulligans ("one probably, two definitely").
// The resourcing halves are in bot_mgresource_test.php.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_mgplay_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

foreach (['mgremoval', 'mgmull'] as $p) $check(SWUBotVariantDisabled("try-$p") === ["try:$p"], "proposal $p is registered");
$AGGRO = 'ASH_009';   // Ahsoka Tano — SWU_BOT_AGGRO_LEADERS
$CTRL  = 'SEC_010';   // Dedra Meero — not an aggro leader

// ══ mgsentinel, the PLAY half ═════════════════════════════════════════════════════════════════════════════════
// Captain Typho (4, 4/5, Sentinel — the owner's "one of the best Sentinels in the game") against Reinforcement
// Walker (8, 6/9), a strictly bigger body, with 8 resources so both are castable this round.
$hand = function (array $cards, string $oppLeader, array $theirs = ['SOR_095', 'SOR_164']) use ($build) {
    $build(function ($b) use ($cards, $oppLeader, $theirs) {
        $b->MyLeader('ASH_005', false, false, true); $b->MyBase('JTL_024');
        $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        foreach ($cards as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(1, 'ASH_079', true);   // Koska 4/4 ready, so an ATTACK is on offer too
        $b->TheirLeader($oppLeader, true);
        foreach ($theirs as $t) $b->WithGroundUnitForPlayer(2, $t, true);   // 3 + 4 = 7 power against my 4
    });
};
$plays = function (string $style, array $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on);
    $ctx = $botCtx($style); $out = [];
    foreach ($ctx['actions'] as $i => $a) { if (SWUBotActionKind($a) === 'play') $out[strval($a['cardID'])] = SWUBotScoreAction($ctx, $a, $i); }
    SWUBotSetDisabledFeatures([]);
    return $out;
};
// What the whole stack actually picks (the rules layer and the style filter included), for a variant string.
$choose = function (string $style, string $variant) use ($gameName) {
    $legal = SWUBotLegalActions($gameName, 1);
    $pick = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return strval($pick['cardID'] ?? '');
};

$hand(['SEC_098', 'SOR_119'], $AGGRO);
$off = $plays('midrange', []);
$check(($off['myHand-1!FSM!'] ?? 0) > ($off['myHand-0!FSM!'] ?? 0), 'fixture: the shipped bot plays the bigger body over the Sentinel');
$on = $plays('midrange', ['try:mgsentinel']);
$check(($on['myHand-0!FSM!'] ?? 0) > ($on['myHand-1!FSM!'] ?? 0), 'mgsentinel: against an aggro leader the Sentinel goes down first');
$check(abs(($on['myHand-1!FSM!'] ?? 0) - ($off['myHand-1!FSM!'] ?? 0)) < 1e-9, 'mgsentinel leaves the other body alone');
// Ruling 4: while BEHIND ON POWER, for a Sentinel — and only for a Sentinel — the body goes down before the
// attacks. That is the layer-2 rule 'mg-sentinel' (BotRules.php), the owner's correction of 'blockerfirst':
// a bonus cannot express it, because the attackFirst guide puts every attack 6.00 clear of its own value.
$check($choose('midrange', '') !== 'myHand-0!FSM!', 'fixture: the shipped bot attacks rather than playing the Sentinel');
$check($choose('midrange', 'try-mgsentinel') === 'myHand-0!FSM!', 'mgsentinel: behind on power, the Sentinel is played before attacking');
$check($choose('midrange', 'try-blockerfirst') !== 'myHand-0!FSM!', "fixture: blockerfirst does not do this — it is not behind on BODIES");
// Ahead on power, the rule abstains and the bot attacks as before (their one 3-power body against my 4).
$hand(['SEC_098', 'SOR_119'], $AGGRO, ['SOR_095']);
$check($choose('midrange', 'try-mgsentinel') === $choose('midrange', ''), 'mgsentinel: ahead on power, the play order is unchanged');
// POWER, not bodies: Koska 4/4 + Neel 1/4 against Battlefield Marine 3/3 + Wampa 4/5. Two bodies each, so
// blockerfirst's test ties and it abstains; 5 power against 7, so the ruling's test says behind. The attack is
// worth 8.40 here (the attackFirst guide), above anything the fallback scores a play, so only the rule can act.
$build(function ($b) use ($AGGRO) {
    $b->MyLeader("ASH_005", false, false, true); $b->MyBase("JTL_024");
    $b->FillResourcesForPlayer(1, "SOR_095", 8);
    $b->WithCardInHandForPlayer(1, "SEC_098");
    $b->WithGroundUnitForPlayer(1, "ASH_079", true);          // Koska 4/4
    $b->WithGroundUnitForPlayer(1, "ASH_248", true);          // Neel 1/4
    $b->TheirLeader($AGGRO, true);
    $b->WithGroundUnitForPlayer(2, "SOR_095", true);          // Battlefield Marine 3/3
    $b->WithGroundUnitForPlayer(2, "SOR_164", true);          // Wampa 4/5
});
$check($choose('midrange', '') !== 'myHand-0!FSM!', 'fixture: level on bodies, the shipped bot attacks');
$check($choose('midrange', 'try-blockerfirst') !== 'myHand-0!FSM!', 'fixture: blockerfirst ties on bodies and abstains');
$check($choose('midrange', 'try-mgsentinel') === 'myHand-0!FSM!', 'mgsentinel: behind on POWER while level on bodies still plays the Sentinel');
// And with no Sentinel in hand the rule abstains: ruling 4 is "only for Sentinels".
$hand(['SOR_119'], $AGGRO);
$check($choose('midrange', 'try-mgsentinel') === $choose('midrange', ''), 'mgsentinel: a non-Sentinel body is NOT played before attacking');
$hand(['SEC_098', 'SOR_119'], $AGGRO);

// Against a control leader the ruling does not apply — the priority is for aggro.
$hand(['SEC_098', 'SOR_119'], $CTRL);
$check($plays('midrange', ['try:mgsentinel']) == $plays('midrange', []), 'mgsentinel: no play priority against a control leader');
// Scope.
$hand(['SEC_098', 'SOR_119'], $AGGRO);
$check($plays('softaggro', ['try:mgsentinel']) == $plays('softaggro', []), 'mgsentinel does not touch an aggro seat');
$check($plays('hardcontrol', ['try:mgsentinel']) == $plays('hardcontrol', []), 'mgsentinel does not touch a control seat');
$check($plays('midrange', []) == $off, 'mgsentinel is inert by default');

// ══ mgremoval: the removal bar is the target's COST ═══════════════════════════════════════════════════════════
// Vanquish (5, "Defeat a unit") in hand, and one READY enemy unit. The shipped 'shrinkfirst' is control-wing only
// and bars on POWER (3+); mgremoval is midrange-only and bars on the cost they paid (5+).
$removalBoard = function (string $enemy) use ($build) {
    $build(function ($b) use ($enemy) {
        $b->MyLeader('ASH_005', false, false, true); $b->MyBase('JTL_024');
        $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        $b->WithCardInHandForPlayer(1, 'SOR_078');          // Vanquish — 5, defeat a non-leader unit
        $b->WithGroundUnitForPlayer(1, 'ASH_079', true);    // Koska Reeves 4/4, so an ATTACK is also on offer
        $b->TheirLeader('SEC_010', true);
        $b->WithGroundUnitForPlayer(2, $enemy, true);
    });
};
// A ready 5-drop: SOR_100 Wedge Antilles (5, 5/5). Over the cost bar, so the removal is played before attacking.
$removalBoard('SOR_100');
$check($choose('midrange', '') !== 'myHand-0!FSM!', 'fixture: the shipped bot does not lead with the removal');
$check($choose('midrange', 'try-mgremoval') === 'myHand-0!FSM!', 'mgremoval: a ready 5-drop is answered before attacking');
// The same board with a ready 2-drop that hits just as hard is NOT worth the card (Battlefield Marine, 2, 3/3).
$removalBoard('SOR_095');
$check($choose('midrange', 'try-mgremoval') !== 'myHand-0!FSM!', 'mgremoval: a 2-cost body is under the bar, card held');
// Scope: the arm does not reach the other styles (control has its own shipped rule).
$removalBoard('SOR_100');
$check($choose('softaggro', 'try-mgremoval') === $choose('softaggro', ''), 'mgremoval does not touch an aggro seat');
$check($choose('midrange', '') !== 'myHand-0!FSM!', 'mgremoval is inert by default');

// ══ mgmull: the matchup keep test ════════════════════════════════════════════════════════════════════════════
// Six cards, as dealt (CR 1.8). Costs are printed — there are no resources yet.
$opening = function (array $cards, string $oppLeader) use ($build) {
    $build(function ($b) use ($cards, $oppLeader) {
        $b->MyLeader('ASH_005', false, false, true); $b->MyBase('JTL_024');
        foreach ($cards as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader($oppLeader, true);
    });
};
$mull = function (array $on) {
    SWUBotSetDisabledFeatures($on);
    $r = _SWUBotShouldMulligan(1, 'midrange');
    SWUBotSetDisabledFeatures([]);
    return $r;
};
$ON = ['try:mgmull'];
// vs AGGRO — a curve AND a body that blocks. Typho (4, 4/5, Sentinel) is the blocker; two cards cost <= 3.
$opening(['SEC_098', 'SOR_095', 'SOR_125', 'SOR_119', 'SOR_119', 'SOR_119'], $AGGRO);
$check($mull([]) === null, 'fixture: the shipped bot has no opinion — it always keeps');
$check($mull($ON) === false, 'mgmull: vs aggro, a curve plus a blocker is a keep');
// The same curve with no body that survives round 2 (Neel is 1/4 — it does survive; Death Star Stormtrooper 3/1 does not).
$opening(['SOR_128', 'SOR_125', 'SOR_125', 'SOR_119', 'SOR_119', 'SOR_119'], $AGGRO);
$check($mull($ON) === true, 'mgmull: vs aggro, a curve with nothing that blocks is a mulligan');
// No curve at all, blocker or not.
$opening(['SEC_098', 'SOR_119', 'SOR_119', 'SOR_119', 'SOR_119', 'SOR_119'], $AGGRO);
$check($mull($ON) === true, 'mgmull: vs aggro, one cheap card is not a curve');
// vs CONTROL — set the two priciest aside; the remaining four must have a play by round 3 (cost <= 4).
$opening(['SOR_119', 'SOR_119', 'SEC_098', 'SOR_095', 'SOR_125', 'SOR_100'], $CTRL);
$check($mull($ON) === false, 'mgmull: vs control, a play in the four kept cards is a keep');
$opening(['SOR_119', 'SOR_119', 'SOR_119', 'SOR_119', 'SOR_100', 'SOR_100'], $CTRL);
$check($mull($ON) === true, 'mgmull: vs control, nothing castable by round 3 is a mulligan');
// Scope: styles and the default.
$check(_SWUBotShouldMulligan(1, 'midrange') === null, 'mgmull is inert by default');
SWUBotSetDisabledFeatures($ON);
$check(_SWUBotShouldMulligan(1, 'hardcontrol') === null, 'mgmull does not touch a control seat');
$check(_SWUBotShouldMulligan(1, 'hyperaggro') === null, 'mgmull does not touch an aggro seat');
SWUBotSetDisabledFeatures([]);

bot_test_finish();
