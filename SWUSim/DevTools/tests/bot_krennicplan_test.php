<?php
// Proposal 'krennicplan' (default OFF) — owner rulings K1-K3 for Krennic (LAW_008) vs Vader Yellow, 2026-09-22
// (bot-sweeps/2026-09-22_krennic_rulings.md). K1 bank Credits for 7+ cards; K2 Hyperspace Disaster as soon as it saves
// the game; K3 attack before sacrificing, and the Expendable Mercenary is sacrificed the round it is played.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_krennicplan_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-krennicplan') === ['try:krennicplan'], 'proposal krennicplan is registered');
$ON = ['try:krennicplan'];
$ids = fn(array $acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$filtered = function (array $on) use ($botCtx) { SWUBotSetDisabledFeatures($on); $ctx = $botCtx('softcontrol'); $r = SWUBotKrennicPlanFilter($ctx); SWUBotSetDisabledFeatures([]); return array_map(fn($a) => strval($a['cardID']), $r); };
// The rule sees the FILTERED actions, as in the stack (BotHeuristic.php runs the filter first).
$rule = function (array $on) use ($botCtx) { SWUBotSetDisabledFeatures($on); $ctx = $botCtx('softcontrol'); $ctx['actions'] = SWUBotKrennicPlanFilter($ctx); $r = SWUBotRuleKrennicPlan($ctx); SWUBotSetDisabledFeatures([]); return $r === null ? null : strval($r['cardID']); };
// Krennic (ready, undeployed) vs Vader with $ships; $res ready resources + $credits Credits (LAW_T01).
$krennic = function (int $res, int $credits, array $hand, array $mine = [], array $ships = ['JTL_081', 'JTL_081', 'SEC_215', 'SEC_213'], string $oppLeader = 'JTL_006') use ($build) {
    $build(function ($b) use ($res, $credits, $hand, $mine, $ships, $oppLeader) {
        $b->MyLeader('LAW_008', true, false, false); $b->MyBase('ASH_019'); $b->FillResourcesForPlayer(1, 'SOR_095', $res);   // Fortress (Vigilance), as the list
        if ($credits > 0) $b->FillResourcesForPlayer(1, 'LAW_T01', $credits);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($mine as [$c, $ready, $arena]) { if ($arena === 'S') $b->WithSpaceUnitForPlayer(1, $c, $ready); else $b->WithGroundUnitForPlayer(1, $c, $ready); }
        $b->TheirLeader($oppLeader, true);
        foreach ($ships as $s) $b->WithSpaceUnitForPlayer(2, $s, true);
    });
};

// ── K1: a Credit is not spent on No Glory (5); Hyperspace Disaster (7) may use it ────────────────────────────────────
$krennic(4, 1, ['JTL_043', 'ASH_116']);                                  // No Glory, Ant Droid
$check(SWUTotalPaymentCapacity(1) === 5 && SWUResourceCount(1, true) === 4, 'fixture: 4 ready resources + 1 Credit (' . SWUTotalPaymentCapacity(1) . ')');
$base = $filtered([]); $f = $filtered($ON);
$check(in_array('myHand-0!FSM!', $base, true), 'fixture: by default No Glory is castable with the Credit');
$check(!in_array('myHand-0!FSM!', $f, true), 'K1: No Glory needing the Credit is filtered out');
$check(in_array('myHand-1!FSM!', $f, true), 'K1: Ant Droid, paid from ready resources, stays');
$check($base === $filtered([]) && count($base) === count($botCtx('softcontrol')['actions']), 'the filter is inert by default');
$krennic(6, 1, ['SEC_078']);                                             // Hyperspace Disaster
$check(in_array('myHand-0!FSM!', $filtered($ON), true), 'K1: Hyperspace Disaster (7) may spend the Credit');

// ── K1 ramp (owner, Q2 follow-up): 4R + 1C, no fodder → Ant Droid first, not the Commando ──────────────────────────
$krennic(4, 1, ['ASH_048', 'ASH_116']);                                  // Imperial Armored Commando (4), Ant Droid (1)
$check($rule($ON) === 'myHand-1!FSM!', 'K1 ramp: with no fodder on board, play the Ant Droid to feed the leader (got ' . var_export($rule($ON), true) . ')');
$check($rule([]) === null, 'the rule is inert by default');

// ── K3: the leader waits while a unit can still attack ───────────────────────────────────────────────────────────
$krennic(2, 0, [], [['ASH_116', true, 'G']]);                            // a READY Ant Droid
$check(in_array('myLeader-0!CustomInput!LeaderAbility', $filtered([]), true), 'fixture: the leader ability is on offer');
$check(!in_array('myLeader-0!CustomInput!LeaderAbility', $filtered($ON), true), 'K3: no sacrifice while the Ant Droid can still attack');
$check($rule($ON) !== 'myLeader-0!CustomInput!LeaderAbility', 'K3: the rule does not sacrifice before the attack either');
$krennic(2, 0, [], [['ASH_116', false, 'G']]);                           // the same Ant Droid, already exhausted
$check(in_array('myLeader-0!CustomInput!LeaderAbility', $filtered($ON), true), 'K3: once it has attacked, the sacrifice is back');
$check($rule($ON) === 'myLeader-0!CustomInput!LeaderAbility', 'K1: short of 7 next round with fodder out → cash it in');

// ── K3: save the leader for the Mercenary; the sacrifice prompt takes it ───────────────────────────────────────────
$krennic(4, 0, ['LAW_159'], [['ASH_116', false, 'G']]);                  // Expendable Mercenary (4) castable
$check(!in_array('myLeader-0!CustomInput!LeaderAbility', $filtered($ON), true), 'K3: the leader waits for the Mercenary');
$check($rule($ON) === 'myHand-0!FSM!' || in_array('myHand-0!FSM!', $filtered($ON), true), 'K3: the Mercenary is playable');
$krennic(0, 0, [], [['ASH_116', false, 'G'], ['LAW_159', false, 'G']]);
$act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
$ctx = $botCtx('softcontrol');
$check($ctx['tooltip'] === 'Defeat_a_friendly_unit_to_create_a_Credit', 'fixture: the sacrifice prompt is pending (' . $ctx['tooltip'] . ')');
$pick = $rule($ON);
$v = $pick !== null ? SWUBotViewForMz(1, $pick) : null;
$check($v !== null && $v['cardID'] === 'LAW_159', 'K3: the sacrifice is the Mercenary (' . var_export($pick, true) . ')');

// ── K2: Hyperspace Disaster now, vs 5 ships ─────────────────────────────────────────────────────────────────────
$FIVE = ['JTL_081', 'JTL_081', 'SEC_215', 'SEC_213', 'JTL_085'];
$krennic(7, 0, ['SEC_078', 'ASH_048'], [], $FIVE);
$check($rule($ON) === 'myHand-0!FSM!', 'K2: Hyperspace Disaster vs 5 ships');
$krennic(7, 0, ['SEC_078', 'ASH_048'], [['JTL_033', true, 'S']], $FIVE);    // my own ship out (Onyx Squadron Brute)
$check($rule($ON) !== 'myHand-0!FSM!', 'K2: not while I have a ship of my own (owner: it depends)');

// ── scope: vs a non-aggro leader the plan is off ────────────────────────────────────────────────────────────────
$krennic(4, 1, ['JTL_043', 'ASH_116'], [], ['JTL_081', 'JTL_081', 'SEC_215', 'SEC_213'], 'SEC_010');   // Dedra
$check($filtered($ON) === $filtered([]) && $rule($ON) === null, 'vs Dedra (not an aggro leader) krennicplan does nothing');

// ── the split arms (2026-09-22, after the −44 canary): each switches on only its own part ───────────────────────────
$partsOf = function (string $prop) { SWUBotSetDisabledFeatures(["try:$prop"]); $p = _SWUBotKrennicPlanParts(); SWUBotSetDisabledFeatures([]); sort($p); return $p; };
$check($partsOf('kpbank') === ['bank'] && $partsOf('kphsd') === ['hsd'] && $partsOf('kporder') === ['order'], 'kpbank / kphsd / kporder each carry one part');
$check($partsOf('kpnoramp') === ['bank', 'hsd', 'order'], 'kpnoramp = everything but the forced ramp');
$krennic(4, 1, ['JTL_043', 'ASH_116']);
$check(!in_array('myHand-0!FSM!', $filtered(['try:kpbank']), true) && in_array('myHand-0!FSM!', $filtered(['try:kporder']), true),
    'K1 banking belongs to kpbank, not to kporder');
$krennic(4, 1, ['ASH_048', 'ASH_116']);
$check($rule(['try:kpnoramp']) === null && $rule(['try:krennicplan']) === 'myHand-1!FSM!', 'kpnoramp never plays a body just to feed the leader');

bot_test_finish();
