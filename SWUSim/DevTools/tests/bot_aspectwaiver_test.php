<?php
// Proposal 'aspectwaiver' — a base Epic Action that WAIVES AN ASPECT PENALTY is worth the best card it unlocks,
// and its prompt picks that card rather than the lowest hand index.
//
// FOUND 2026-09-28, tracing the bot's Krennic Blue Splash vs its own Ahsoka Blue (0/24). The eight LAW common
// bases print "Epic Action: Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or
// Cunning aspect penalties" — LAW_020 Daimyo's Palace is this deck's, and it is what makes LAW_044 Single Reactor
// Ignition castable at its printed 8 instead of 10 (the deck is Vigilance base + Command/Villainy leader, so
// Aggression is off-aspect: +2). Traced over 8 games:
//   · the Epic Action was used once per game — in ROUND 1 in six of eight games, at capacity 2, on a 2-drop;
//   · its hand prompt picked myHand-0 / myHand-4 every time: the LOWEST INDEX, layer=fallback, no rule;
//   · once it fired in round 5 with LAW_044 actually in hand, and still took the cheap card.
//
// TWO ROOT CAUSES, both measured:
//   1. VALUE. `base-epic` scores through _SWUBotAbilityValue, which reads the lookahead for what *I* lose and
//      NEVER for what the opponent loses. Playing a board wipe therefore scored -2.6 (its own 3/7 dying at 4.0,
//      less the 1.0 allowance) while every enemy unit destroyed counted for nothing. A once-per-game unlock that
//      scores -2.6 for its best use is not "spent too early" — it is never chosen for that use at all.
//   2. PICK. The "Play_a_card_(ignore_1_...)" prompt falls through to `0.01 - $index * 1e-6`, the enumeration-order
//      tiebreak. The only thing that picks the best card at a "Play_a_" prompt is proposal 'piettcheat', written
//      for Piett's leader Action and default off.
//
// Valuing it correctly IS the "save it" mechanism: worth ~0 while the hand holds only cheap on-aspect cards,
// worth the bomb once the bomb is affordable at the waived cost.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_aspectwaiver_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$EPIC = 'myBase-0!CustomInput!EpicAction';
$score = function (string $cardID, array $disabled = [], string $style = 'softcontrol') use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx($style);
    $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) { $s = SWUBotScoreAction($ctx, $a, $i); break; }
    SWUBotSetDisabledFeatures([]);
    return $s;
};
$pickAt = function (string $variant = '') use (&$gameName) {
    SWUBotResetCoverage();
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$ON = ['try:aspectwaiver'];

// ── Registry ────────────────────────────────────────────────────────────────────────────────────────────────
$check(in_array('aspectwaiver', SWUBotProposalList(), true), 'aspectwaiver is a PROPOSAL (default off)');
$check(SWUBotVariantDisabled('try-aspectwaiver') === ['try:aspectwaiver'], 'switchable as @try-aspectwaiver');

// The board: Krennic Blue Splash's real leader/base, an exhausted body so no attack competes, and one enemy unit.
$board = function (int $resources, array $hand) use ($build) {
    $build(function ($b) use ($resources, $hand) {
        $b->MyLeader('LAW_008', false, false, true);         // exhausted + Epic spent: the BASE Epic is the play
        $b->MyBase('LAW_020');                              // Daimyo's Palace — the aspect waiver
        $b->FillResourcesForPlayer(1, 'SOR_095', $resources);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(1, 'SOR_046', false, 0);  // exhausted, cannot attack
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 0);
    });
};

// ── A) The fixture's own arithmetic, so the sections below cannot be green for the wrong reason ──────────────
$board(8, ['LAW_044', 'SOR_095']);
$check(SWUComputePlayCost(1, GetHand(1)[0]) === 10, 'A fixture: LAW_044 costs 10 unwaived (8 printed + 2 Aggression)');
$check(_SWUCommonBaseWaivePenalty(1, 'LAW_044') === 2, 'A fixture: Daimyo\'s Palace waives the 2-point Aggression penalty');
$check(SWUTotalPaymentCapacity(1) === 8, 'A fixture: capacity 8 — enough for the WAIVED cost, not the raw one');
$ids = array_map(fn($a) => strval($a['cardID']), $botCtx('softcontrol')['actions']);
$check(in_array($EPIC, $ids, true), 'A fixture: the base Epic Action is on offer; got ' . json_encode($ids));

// ── B) VALUE: the Epic Action is worth the bomb it unlocks, not -2.6 ────────────────────────────────────────
// ⚠ CORRECTION TO MY OWN FIRST READ. I expected the default to score this NEGATIVE, from an earlier probe that
// showed -2.6. It does not: the shipped 'enablers' feature already prices "this action lets me play X" through
// _SWUBotEnabledPlayValue, so a genuine unlock is ALREADY valued at 3.5. The defect is not that a real unlock is
// undervalued — it is that a FAKE one is OVERvalued (section E) and that the card is picked by index (section D).
// This section's job is therefore to prove the proposal does not damage the case that already worked.
$onB  = $score($EPIC, $ON);
$offB = $score($EPIC);
$check($onB !== null && $onB > 0.0, 'B: with a castable wipe behind it the Epic Action scores POSITIVE; got ' . json_encode($onB));
$check($offB !== null && $offB > 0.0, 'B default: already positive — the shipped enablers feature handles a REAL unlock');
$check(abs($onB - $offB) < 1e-9, 'B: the proposal leaves a genuine unlock untouched; got ' . json_encode([$offB, $onB]));

// ── C) "SAVE IT" falls out of valuing it: nothing good behind it → near zero, so other plays win ────────────
// Round-1 shape: 2 resources, only cheap on-aspect cards in hand. This is the traced mistake — six of eight
// games burned the once-per-game waiver here.
$board(2, ['SOR_095', 'JTL_032']);
$onC = $score($EPIC, $ON);
$check($onC !== null && $onC < $onB, 'C: with only cheap on-aspect cards the Epic Action is worth far less than in B; got '
    . json_encode([$onC, $onB]));
$check($score($EPIC) > 0.0, 'C default: the waiver still scores POSITIVE on a 2-drop — this is the round-1 burn');
$check($onC <= 0.0, 'C: the proposal values it at <= 0, because it unlocks nothing; got ' . json_encode($onC));
$check($pickAt('try-aspectwaiver') !== $EPIC, 'C: and the stack does NOT burn the waiver on a 2-drop');

// ── D) PICK: the prompt takes the best card, not the lowest hand index ──────────────────────────────────────
// LAW_044 is deliberately placed AFTER two cheap cards, so index order gives the wrong answer.
$board(8, ['SOR_095', 'JTL_032', 'LAW_044']);
$act(1, 10001, $EPIC);
$ctxD = $botCtx('softcontrol');
$check(str_starts_with($ctxD['tooltip'], 'Play_a_card_(ignore_1_'), "D fixture: the waiver prompt is up; got '{$ctxD['tooltip']}'");
$cands = array_map(fn($a) => strval($a['cardID']), $ctxD['actions']);
$check(count($cands) >= 2, 'D fixture: more than one card is playable at the waived cost; got ' . json_encode($cands));
$check($pickAt('try-aspectwaiver') === 'myHand-2', 'D: the prompt picks LAW_044 at index 2, not the cheap index-0 card');
$check($pickAt() === 'myHand-0', 'D default: it picks the lowest index (the traced behaviour)');

// ── E) A waiver that unlocks nothing is not worth using at all ──────────────────────────────────────────────
// Every card in hand is already on-aspect and affordable, so the waiver changes nothing about what can be played.
$board(8, ['SOR_095']);
$onE = $score($EPIC, $ON);
$check($onE !== null && $onE <= 0.0, 'E: a waiver with nothing off-aspect to unlock is worth <= 0; got ' . json_encode($onE));
$check($score($EPIC) > 0.0, 'E default: it is credited 0.6 for "enabling" a card it never enabled');

bot_test_finish();
