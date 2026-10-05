<?php
// Proposal 'creditbank' — spending a BANKED Credit costs what the line it belongs to is worth.
//
// BUG REPORT #1099 (prod, Arenabot, game 1402804): "bot did not bank Credit. it wasted it on Koska Reeves
// even though no unit of theirs was defeated." Round 2, 3 resources + 1 Credit, hand
// [ASH_079, LAW_044, JTL_121, ASH_052]. The bot played ASH_079 Koska Reeves (cost 4), which consumed the
// Credit to cover the 1-resource shortfall.
//
// ⚠ TWO THINGS THAT ARE NOT THE BUG, checked before building anything:
//   · The bot does NOT over-credit Koska's dead When Played ("if a friendly unit was defeated THIS PHASE"
//     — the friendly defeat was round 1, she was played round 2, so the engine was right to do nothing).
//     She scores 1.2 with or without the condition, because `create-mandalorian-token` has no weight row.
//   · JTL_121 Salvage scoring -0.5 is CORRECT, not a missed alternative: it plays a Vehicle from the
//     discard, and there was none.
// So the bot's real choice was "a 4/4 body" vs "pass", and the only thing wrong with taking it is what the
// Credit was FOR.
//
// THE LINE (owner, 2026-09-29): 6 resources + 2 Credits + Daimyo's Palace's aspect waiver casts LAW_044
// Single Reactor Ignition — 8 printed, +2 Aggression waived — on the 6R turn. The Credit is a COMPONENT OF
// THE WIN CONDITION, so spending it on a marginal body breaks the plan.
//
// ⚠ WHY THE EXISTING HELPER COULD NOT SEE IT. _SWUBotCreditUnlockValue prices every card at
// SWUComputePlayCost — LAW_044 at its UNWAIVED 10 — against CURRENT capacity, so it returns 0 here and
// spending the Credit looks free. Its own comment records that gap. _SWUBotCreditPlanValue prices the bomb
// at its WAIVED cost and asks whether the deck can reach it within the horizon.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_creditbank_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$GLOBALS['SWUBotPinnedDisabled'] = SWU_BOT_PART38_FEATURES;   // isolates this file's feature from curve value (p38, 2026-10-06)

// ⚠ ISOLATED FROM PART 23 'bigcredit' (2026-10-03, shipped default ON): that rule holds a cheap play that needs a banked
// Credit outright, so with it on every "default value" below is -0.5 and this proposal has nothing left to measure.
// The baseline here is therefore "bigcredit OFF", and the proposal arm adds the proposal to it. The last section pins
// what the shipped default now does with the reported #1099 board.
$BASE = ['bigcredit'];
$ON  = ["try:creditbank", 'bigcredit'];
$ON2 = ["try:creditbank", 'bigcredit'];
SWUBotSetDisabledFeatures($BASE);

// ── Registry ────────────────────────────────────────────────────────────────────────────────────────────
$check(in_array('creditbank', SWUBotProposalList(), true), 'creditbank is a PROPOSAL (default off)');
$check(!in_array('creditbank', SWUBotFeatureList(), true), 'creditbank is NOT a shipped feature');
$check(SWUBotVariantDisabled('try-creditbank') === ['try:creditbank'], 'switchable as @try-creditbank');

// The reported board. $waiverSpent mirrors bug #1098, which happened one turn EARLIER in this same game.
$board = function (int $resources, int $credits, bool $waiverSpent,
                   array $hand = ['ASH_079', 'LAW_044', 'JTL_121', 'ASH_052']) use ($build) {
    $build(function ($b) use ($resources, $waiverSpent, $hand) {
        // ⚠ Epic Action UNSPENT: _SWUBotLeaderThreshold returns 0 once it is used, which erases the
        // deploy deadline this whole model is built on. Exhausted is enough to keep the leader's own
        // Action out of the way.
        $b->MyLeader('LAW_008', false, false, false);
        $b->MyBase('LAW_020', 0, $waiverSpent);                   // Daimyo's Palace; 3rd arg = epicActionUsed
        $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 0);
    });
    global $playerID; $playerID = 1;
    if ($credits > 0) SWUCreateCreditToken(1, $credits);
};
$W = SWUBotWeights('softcontrol', 1);

// ── A) The fixture is the reported one ──────────────────────────────────────────────────────────────────
$board(3, 1, true);
$check(SWUResourceCount(1, true) === 3, 'A fixture: 3 ready resources');
$check(count(SWUUsableCreditTokenMzIDs(1)) === 1, 'A fixture: exactly 1 banked Credit');
$koska = null; $bomb = null;
foreach (GetHand(1) as $o) {
    if ($o->CardID === 'ASH_079') $koska = intval(SWUComputePlayCost(1, $o));
    if ($o->CardID === 'LAW_044') $bomb  = intval(SWUComputePlayCost(1, $o));
}
$check($koska === 4, "A fixture: Koska costs 4; got " . json_encode($koska));
$check($bomb === 10, "A fixture: LAW_044 costs 10 UNWAIVED (8 printed + 2 Aggression); got " . json_encode($bomb));
$check($koska > SWUResourceCount(1, true),
    'A fixture: Koska cannot be paid from resources alone — the Credit is the shortfall');

// ── B) With the waiver INTACT, spending the Credit has a COST ───────────────────────────────────────────
// This is the owner's line alive: 3 resources now, 6 within the short lookahead, LAW_044 waived to 8,
// and 6 + 2 Credits = 8. Spending one breaks it.
// ⚠ ASH_052 Chimaera is out of this hand: at 7 it needs only ONE Credit, so with 2 banked the spend comes
// out of the SURPLUS and is correctly free — which would make this section pass for the wrong reason. With
// only the bomb in range the line needs 2, the bank is exactly 2, and there is no surplus to spend.
$board(3, 2, false, ['ASH_079', 'LAW_044', 'JTL_121']);
$offB = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($ON);
$onB  = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($BASE);
$check($onB < $offB, 'B: playing Koska out of the bank is worth LESS while the line is live; got '
    . json_encode([$offB, $onB]));
$check(abs($offB - 1.2) < 1e-9, 'B: the reported default value is unchanged at 1.2; got ' . json_encode($offB));

// ── B2) With the waiver ALREADY BURNED, there is no line and the Credit is free to spend ────────────────
// ⚠ Bug #1098 burned the waiver one turn earlier, so LAW_044 is a 10 that 6 resources + 2 Credits cannot
// reach. ASH_052 Chimaera is DROPPED from this hand on purpose: at cost 7 it is reachable at 6 + 2 and
// would keep the bank load-bearing for a different card, which is correct behaviour but would make this
// section pass for the wrong reason. With neither in reach there is genuinely no line.
// #1099 is therefore partly DOWNSTREAM of #1098: burning the waiver removes the SRI line the Credits were
// being saved for, so the two want measuring together.
$board(3, 2, true, ['ASH_079', 'LAW_044', 'JTL_121']);
$offB2 = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($ON);
$onB2  = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($BASE);
$check(_SWUBotCreditPlan(1, $W)[0] === 0.0,
    'B2: with the waiver burned the bomb is out of reach, so there is no line to protect');
$check(abs($onB2 - $offB2) < 1e-9,
    'B2: and so spending the Credit is charged NOTHING; got ' . json_encode([$offB2, $onB2]));

// ── C) A card paid entirely from RESOURCES is untouched ─────────────────────────────────────────────────
// ⚠ The negative control. Without it, "the proposal lowers a score" passes for any blanket penalty.
$board(6, 1, true);
$offC = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($ON);
$onC  = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($BASE);
$check(SWUResourceCount(1, true) >= 4, 'C fixture: resources alone now cover Koska');
$check(abs($onC - $offC) < 1e-9,
    'C: no Credit is spent, so nothing is charged; got ' . json_encode([$offC, $onC]));

// ── D) No banked Credits, no cost ────────────────────────────────────────────────────────────────────────
$board(3, 0, true);
SWUBotSetDisabledFeatures($ON);
$onD = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($BASE);
// ⚠ Asserted on the SPEND COST, not the plan: with 0 banked there is still a line the deck could bank
// TOWARD (Credits are makeable until deploy), so the plan is legitimately non-zero. What must hold is that
// a bank of nothing cannot be charged for.
$check(_SWUBotCreditSpendCost(1, 'ASH_079', $W) === 0.0,
    'D: with no Credits banked, spending cannot be charged; got '
    . json_encode(_SWUBotCreditSpendCost(1, 'ASH_079', $W)));

// ── E) THE WAIVER IS WHAT MAKES THE LINE VISIBLE ─────────────────────────────────────────────────────────
// The whole reason this is a separate valuation. With the waiver still available LAW_044 is an 8, which the
// deck reaches at 6R + 2 Credits; priced at its unwaived 10 the plan is invisible and the Credit looks free.
// ⚠ ASH_052 Chimaera is DROPPED here too. At cost 7 it is reachable at 6 + 2 Credits whether or not the
// waiver is intact, so leaving it in keeps the plan alive by itself and the waiver term underneath becomes
// untestable — a mutation sweep caught exactly that. With only the bomb in range, the waiver IS the line.
$board(3, 2, false, ['ASH_079', 'LAW_044', 'JTL_121']);   // waiver UNSPENT — the owner's actual line
[$planLive, $needLive, $reachLive] = _SWUBotCreditPlan(1, $W);
$check($planLive > 0.0 && $reachLive, 'E: with the waiver unspent the banked Credits are protecting a real line; got '
    . json_encode([$planLive, $needLive]));
$check($needLive === 2, 'E: and the line needs exactly 2 Credits — 6 resources + 2 = the waived 8; got '
    . json_encode($needLive));
// And the existing helper still cannot see THE BOMB — the gap this exists to close. It is not that the
// helper returns nothing (it finds ASH_052 Chimaera at 7, which 5 capacity + 2 Credits does reach); it is
// that LAW_044 at its UNWAIVED 10 is beyond capacity+credits, so the line the owner actually plays for
// contributes zero to it no matter how many Credits are banked.
$capE = SWUTotalPaymentCapacity(1);
$bombUnwaived = null;
foreach (GetHand(1) as $o) if ($o->CardID === 'LAW_044') $bombUnwaived = intval(SWUComputePlayCost(1, $o));
$check($bombUnwaived > $capE + 2,
    'E: priced UNWAIVED, LAW_044 is out of reach of capacity + the whole bank, so the unlock helper cannot '
    . 'see the line; got ' . json_encode(['bomb' => $bombUnwaived, 'capacity' => $capE, 'credits' => 2]));

// ── G) THE ENGINE CLOSES AT DEPLOY ──────────────────────────────────────────────────────────────────────
// Krennic's Credit Action is on the leader's FRONT side; the deployed side has no Credit ability. The
// owner's own 15 games show it: 29 Credits made before deploy, 1 after — and that one was a leader that had
// been defeated and RETURNED to the leader zone, so the front side was live again. Peak bank never exceeded 2.
// Once deployed, nothing can refill the bank, so a line the remaining Credits cannot reach is DEAD and
// spending them is free.
$boardDeployed = function (int $resources, int $credits) use ($build) {
    $build(function ($b) use ($resources) {
        $b->MyLeader('LAW_008', true, true, true);            // DEPLOYED — the engine is gone
        $b->MyBase('LAW_020', 0, false);                      // waiver still intact
        $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        foreach (['ASH_079', 'LAW_044', 'JTL_121'] as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 0);
    });
    global $playerID; $playerID = 1;
    if ($credits > 0) SWUCreateCreditToken(1, $credits);
};
$boardDeployed(3, 1);
// ⚠ PIN THE PROPERTY THE MODEL LEANS ON. _SWUBotCreditPlan has no separate "engine open" flag — it relies
// on the deploy threshold going to 0 once the leader is deployed, which collapses both the plan horizon and
// the makeable count. If that ever changes, the deadline silently disappears and this catches it.
$check(SWUBotLeaderDeployThreshold(1) === 0,
    'G: a DEPLOYED leader reports a deploy threshold of 0 — the engine-closed signal; got '
    . json_encode(SWUBotLeaderDeployThreshold(1)));
[$planG, $needG, $reachG] = _SWUBotCreditPlan(1, $W);
$check(!$reachG, 'G: deployed, 3 resources and 1 Credit cannot reach the waived 8 and no more can be made — '
    . 'the line is DEAD; got ' . json_encode([$planG, $needG, $reachG]));
SWUBotSetDisabledFeatures($ON2);
$onG = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($BASE);
$check(abs($onG - 1.2) < 1e-9, 'G: so spending the Credit is free; got ' . json_encode($onG));

// ── H) SURPLUS BEYOND THE LINE IS FREE ──────────────────────────────────────────────────────────────────
// The owner never banks past 2, because the line needs 2. A third Credit protects nothing.
$board(3, 4, false, ['ASH_079', 'LAW_044', 'JTL_121']);   // 4 banked, line needs 2
[$planH, $needH, $reachH] = _SWUBotCreditPlan(1, $W);
$check($reachH && $needH === 2, 'H fixture: the line still needs 2; got ' . json_encode([$needH, $reachH]));
SWUBotSetDisabledFeatures($ON2);
$onH = _SWUBotPlayValue(1, 'ASH_079', $W);
SWUBotSetDisabledFeatures($BASE);
$check(abs($onH - 1.2) < 1e-9,
    'H: with 4 banked and 2 needed, spending 1 comes out of the SURPLUS and is free; got ' . json_encode($onH));

// ── Z) The SHIPPED default (Part 23 'bigcredit') on the reported #1099 board ─────────────────────────────────────
// "bot did not bank Credit. it wasted it on Koska Reeves": Koska (cost 4, not a big play) needs the banked Credit, so
// with the default stack she is HELD — what the owner asked for, without this proposal's whole-line model.
SWUBotSetDisabledFeatures([]);
$board(3, 1, true);
$check(SWUBotBanksCredits(1) && SWUBotCreditSpendFor(1, 'ASH_079') > 0, 'Z fixture: a credit-ramp deck, and Koska needs the Credit');
$check(_SWUBotPlayValue(1, 'ASH_079', $W) < 0, 'Z: with the shipped default the #1099 Koska spend is held; got ' . json_encode(_SWUBotPlayValue(1, 'ASH_079', $W)));

bot_test_finish();
