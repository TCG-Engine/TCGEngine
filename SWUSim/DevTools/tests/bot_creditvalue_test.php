<?php
// Feature 'creditvalue' (group p16) — a Credit token is worth what it BRINGS INTO REACH.
//
// FOUND 2026-09-28 by tracing the bot's Krennic Blue Splash against its own Ahsoka Blue: 0 wins in 24 games,
// three styles, both seats. The owner's read was that the deck's best line in that matchup is 6 resources +
// 2 Credits to cast LAW_044 Single Reactor Ignition ("Defeat all units") off Daimyo's Palace's aspect waiver.
// Traced over 8 games / 222 Krennic decisions:
//   · LAW_044 was IN HAND for 82 decisions, was an offered candidate 0 times, and was played 0 times;
//   · max payment capacity ALL GAME was 6 (R1 2, R2 4, R3 5, R4 6, R5 6) against a cost of 8;
//   · Krennic's ramp Action ("[Exhaust, defeat a friendly unit]: Create a Credit token") was offered 78 times
//     and taken 12 — and the 12 are the games where LAW_159 Expendable Mercenary was the fodder, which
//     SWUBotSacrificeCost already prices at -1.0 because it resources itself back.
//
// ROOT CAUSE: nothing in the bot's value path reads Credits. _SWUBotBoardSignature tracks units, bases, hand
// SIZE, resource COUNT, deck and discard — never Credits — and there is no ramp term anywhere. So the Action
// scored W['ability'] - max(0, sacrificeCost - 1.0) with the Credit worth ZERO: pure loss whenever the cheapest
// friendly body costs more than a point of sacrifice value. A pure Credit-generator with no prompt is worse
// still — an unchanged signature reads as "changed nothing" and returns -0.5.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_creditvalue_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$score = function (string $cardID, string $style = 'softcontrol', array $disabled = []) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx($style);
    $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) { $s = SWUBotScoreAction($ctx, $a, $i); break; }
    SWUBotSetDisabledFeatures([]);
    return $s;
};
$stack = function (string $style = 'softcontrol', string $variant = '') use (&$gameName) {
    SWUBotResetCoverage();
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$ACTION = 'myLeader-0!CustomInput!LeaderAbility';

// ── Registry ────────────────────────────────────────────────────────────────────────────────────────────────
// ⚠ A PROPOSAL, DEFAULT OFF — it FAILS the owner's bar ("raise the weak, never lower the strong"). Measured over
// 24 games vs ahsoka-tano_ash_blue: 0/24 either way, and base damage dealt FELL 7.9 → 6.4. It does raise Credit-engine use
// (12/78 → 16/60), but it cannot reach the line it exists for, because LAW_044 costs 10 unwaived against a max
// capacity of 6 — the missing half is Daimyo's Palace's aspect waiver, not valuation. Measure them together.
$check(in_array('creditvalue', SWUBotProposalList(), true), 'creditvalue is a PROPOSAL (default off)');
$check(!in_array('creditvalue', SWUBotFeatureList(), true), 'creditvalue is NOT a shipped feature');
$check(SWUBotVariantDisabled('try-creditvalue') === ['try:creditvalue'], 'creditvalue is switchable as @try-creditvalue');
$check(isset(SWUBotWeights('softcontrol', 1)['creditRamp']), 'the archetype table carries a creditRamp weight');
$W = SWUBotWeights('softcontrol', 1); $Wa = SWUBotWeights('hyperaggro', 1);
$check($W['creditRamp'] > $Wa['creditRamp'], 'ramp is worth more to control than to hyper aggro; got '
    . json_encode([$Wa['creditRamp'], $W['creditRamp']]));

// ── A) THE REPORTED SHAPE — the Credit unlocks the bomb, and the fodder is a real body ──────────────────────
// LAW_008 Director Krennic, "[Exhaust, defeat a friendly unit]: Create a Credit token". Base LAW_020 Daimyo's
// Palace. 7 resources and LAW_044 Single Reactor Ignition (cost 8) in hand: ONE Credit closes the gap, so the
// Action is worth the bomb it unlocks even though the only fodder is a 3/7 body.
// ⚠ SOR_046 Consular Security Force (4 cost, 3/7) has NO "When Defeated", so SWUBotSacrificeCost returns its
// full unit value — this is deliberately the case the old scorer refused.
$krennic = function (int $resources, array $hand, string $fodder = 'SOR_046', bool $fodderReady = true) use ($build) {
    $build(function ($b) use ($resources, $hand, $fodder, $fodderReady) {
        $b->MyLeader('LAW_008', true, false, true);          // ready, undeployed, Epic Action already spent
        $b->MyBase('LAW_020');
        $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(1, $fodder, $fodderReady, 0);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 0);
    });
};

$krennic(9, ['LAW_044']);
$ids = array_map(fn($a) => strval($a['cardID']), $botCtx('softcontrol')['actions']);
$check(in_array($ACTION, $ids, true), 'A fixture: Krennic\'s ramp Action is on offer; got ' . json_encode($ids));
// ⚠ COST 10, NOT ITS PRINTED 8. The deck is Vigilance (base LAW_020) + Command/Villainy (leader LAW_008), so
// LAW_044's Aggression is off-aspect: +2. The owner's 6R+2C line needs Daimyo's Palace's once-per-game waiver to
// bring it back to 8, and SWUComputePlayCost cannot see that waiver — so the fixture uses the honest 10.
$check(SWUComputePlayCost(1, GetHand(1)[0]) === 10, 'A fixture: Single Reactor Ignition costs 10 unwaived (8 printed + 2 Aggression)');
$check(SWUTotalPaymentCapacity(1) === 9, 'A fixture: capacity is 9 — exactly one Credit short of 10');
$GLOBALS['SWUBotPinnedDisabled'] = SWU_BOT_PART38_FEATURES;   // owner 2026-10-06: on this board SRI is a bad play by curve value; this checks the MECHANISM
$sA = $score($ACTION, 'softcontrol', ['try:creditvalue']);
unset($GLOBALS['SWUBotPinnedDisabled']);
$check($sA !== null && $sA > 0.0, "A: ramping is worth doing when the Credit unlocks the bomb; got " . json_encode($sA));
// The reported mistake, reproducible with the feature off — so A cannot pass vacuously.
$off = $score($ACTION);
$check($off !== null && $off < 0.0, "A @no-creditvalue: the Action scores NEGATIVE (the traced refusal); got " . json_encode($off));

// ── B) Nothing to unlock: a Credit that reaches no card in hand stays cheap, and the body is not thrown away ─
// Same board, but the hand is a 2-drop that is already affordable. One more Credit buys nothing this turn.
$krennic(9, ['SOR_095']);
$sB = $score($ACTION, 'softcontrol', ['try:creditvalue']);
$check($sB !== null && $sB < $sA, 'B: with nothing to unlock the Action is worth LESS than in A; got ' . json_encode([$sB, $sA]));
$check($sB !== null && $sB < 0.0, 'B: and it still loses a real 3/7 body for a Credit it cannot spend; got ' . json_encode($sB));

// ── C) The gap is too big for one Credit — do not sacrifice on a promise it cannot keep ─────────────────────
// 4 resources and the cost-8 bomb: one Credit leaves it 3 short, so the unlock is not real yet.
$krennic(4, ['LAW_044']);
$sC = $score($ACTION, 'softcontrol', ['try:creditvalue']);
$check($sC !== null && $sC < 0.0, 'C: one Credit that still leaves the bomb unaffordable does not pay for a body; got ' . json_encode($sC));

// ── D) The deck's INTENDED fodder is unchanged — this must not regress the 12 takes that already worked ─────
// LAW_159 Expendable Mercenary resources itself back, so SWUBotSacrificeCost prices it at -1.0 and the Action
// was already correct here. It must still be taken, with or without the feature — once it has ATTACKED (exhausted).
// Feature 'unusedsac' (p18, owner 2026-10-01): a still-READY Mercenary attacks first, then is cashed in.
$krennic(9, ['SOR_095'], 'LAW_159', false);
$check($score($ACTION, 'softcontrol', ['try:creditvalue']) > 0.0, 'D: sacrificing Expendable Mercenary is still worth it (nothing to unlock needed)');
$check($score($ACTION) > 0.0, 'D default (proposal off): and that was already true before it');
$krennic(9, ['SOR_095'], 'LAW_159', true);
$check($score($ACTION) < $score($ACTION, 'softcontrol', ['unusedsac']), 'D unusedsac: a READY Mercenary is worth less to sacrifice than after it has attacked');

// ── E) The whole point: the stack actually takes the ramp Action ─────────────────────────────────────────────
// ⚠ THE FODDER IS EXHAUSTED HERE, deliberately. With a READY 3/7 the stack attacks instead (scored 2.6 against
// ramping at 1.93) — and that is a defensible play, not a bug, so asserting "always ramp" would be asserting
// something false. Exhausting it removes the attack and leaves the ramp against PASS / initiative, which is the
// choice this feature is actually about: a turn with nothing better to do than set up the bomb.
$build(function ($b) {
    $b->MyLeader('LAW_008', true, false, true);
    $b->MyBase('LAW_020');
    $b->FillResourcesForPlayer(1, 'SOR_095', 9);
    $b->WithCardInHandForPlayer(1, 'LAW_044');
    $b->WithGroundUnitForPlayer(1, 'SOR_046', false, 0);      // EXHAUSTED — cannot attack
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 0);
});
$check($stack('softcontrol', 'try-creditvalue') === $ACTION, 'E: with no attack available the stack RAMPS toward the bomb');
$check($stack('softcontrol') !== $ACTION, 'E default (proposal off): it does not — the traced behaviour');

bot_test_finish();
