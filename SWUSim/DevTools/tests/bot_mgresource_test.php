<?php
// THE MIDRANGE RESOURCING ARMS — proposals 'mgcost', 'mgkeep' and 'mgsentinel' (all default OFF).
// Owner rulings 2026-09-23 (docs/superpowers/research/2026-09-premier-meta/bot-sweeps/2026-09-23_midrange_rulings.md):
//   2. Sentinels: "keep them against AGGRO. Against control it depends — a 2 power sentinel is almost pointless,
//      but a 3+ power sentinel is good power to start the race against control." Playing: priority against aggro.
//   6. "Resourcing ONE duplicate is fine. Do not resource an efficient on-curve body — a second Koska Reeves
//      (4 cost, 4/4) was the wrong pick." And: "off-aspect cards cost +2 and must be judged at that cost —
//      Chimaera technically costs 9 for Luke (ASH) DV. it would be one of the first to go in an opening hand."
// ⚠ WHY THESE ARE MIDRANGE'S FIRST RULES. The keep value at rank 2 is the aggro wing's FALLBACK, -$cost: "resource
// the most expensive card", nothing else. Every refinement in this engine (the castable-soon horizon, the bomb, the
// wipe keep, the Sentinel keep, the resourcing3 tiers) is gated to rank >= 3. The 2026-09-23 ablation measured the
// consequence: every feature group shipped since part 4 changes ZERO games for a midrange seat.
// The seat is the owner's baseline deck: Luke Skywalker (ASH_005) + Data Vault (JTL_024) = Vigilance/Heroism/Command.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_mgresource_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

foreach (['mgcost', 'mgkeepbody', 'mgkeepdup', 'mgsentinel'] as $p) $check(SWUBotVariantDisabled("try-$p") === ["try:$p"], "proposal $p is registered");
// 'mgkeep' SHIPPED 2026-09-23 as feature group p11 — it is ON by default and '@no-mgkeep' / '@no-p11' turn it off.
$check(in_array('mgkeep', SWUBotFeatureList(), true) && SWUBotFeatureGroups()['p11'] === ['mgkeep'], 'mgkeep is a shipped feature, group p11');
$check(SWUBotVariantDisabled('no-mgkeep') === ['mgkeep'] && SWUBotVariantDisabled('try-mgkeep') === null, '@no-mgkeep switches it off; it is no longer a proposal');
$OFF = ['mgkeep'];   // the pre-p11 resourcer
$AGGRO = 'ASH_009';   // Ahsoka Tano — SWU_BOT_AGGRO_LEADERS
$CTRL  = 'SEC_010';   // Dedra Meero — not an aggro leader

// Luke ASH Data Vault with $hand, 4 resources, against $oppLeader.
$seat = function (array $hand, string $oppLeader) use ($build) {
    $build(function ($b) use ($hand, $oppLeader) {
        $b->MyLeader('ASH_005', false, false, true); $b->MyBase('JTL_024');
        $b->FillResourcesForPlayer(1, 'SOR_095', 4, false);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->TheirLeader($oppLeader, true);
    });
};
// What the resourcer picks, as CardIDs — and, where two copies matter, as hand mzIDs.
// ⚠ EVERY arm in this file is pinned to the PRE-p12 resourcer by adding 'mgbomb' to the disabled set.
// 'mgbomb' SHIPPED 2026-09-24 as feature group p12: midrange now uses the castable-soon + protect-one-bomb
// keep rule instead of the `-$cost` fallback, which moves the pick in all of these fixtures. Every arm here
// (mgcost, mgkeepbody, mgkeepdup, mgsentinel) was MEASURED on top of the fallback, so measuring them against
// the new default would silently change what each test means.
// ⚠ DEBT: those four proposals' measurements are now stale — they need re-screening ON TOP OF p12 before any
// of them ships. See the OTMTCGE memory `midrange-wants-a-lower-kill-weight`.
$picks = function (int $n, array $on, string $style = 'midrange') use ($botCtx) {
    SWUBotSetDisabledFeatures(array_values(array_unique(array_merge(['mgbomb'], $on))));
    $mz = SWUBotChooseResourceCards($botCtx($style), $n);
    SWUBotSetDisabledFeatures([]);
    return $mz;
};
$ids = function (array $mz) { return array_map(fn($m) => strval(GetHand(1)[intval(substr($m, strlen('myHand-')))]->CardID ?? '?'), $mz); };

// ══ mgcost: the cost THIS SEAT pays ═══════════════════════════════════════════════════════════════════════════
// Grand Moff Tarkin is Command/Villainy: Villainy is off-aspect for Luke ASH DV, so his 4 is really a 6, above the
// on-aspect Wedge Antilles at 5. Midrange resources its most expensive card, so the two disagree about which that
// is. (The owner's own example, Chimaera ASH_052 at 7 -> 9, cannot show it: Chimaera is tagged 'removal', so the
// shipped 'keep' feature already lifts it 150 above every filler card, whatever it costs.)
// Both cards are deliberately NON-efficient bodies (Tarkin 2/3, Covert Believers 4/5 at cost 5), so the shipped
// p11 keep is blind to both and this section measures mgcost alone.
$seat(['SOR_084', 'ASH_080'], $AGGRO);
$check($ids($picks(1, [])) === ['ASH_080'], 'fixture: on PRINTED cost the 5-drop is the most expensive card');
$check($ids($picks(1, ['try:mgcost'])) === ['SOR_084'], 'mgcost: the off-aspect card costs +2 and goes first (owner ruling 6)');
$check(SWUBotProposalOn('mgcost') === false && _SWUBotSeatCost(1, 'ASH_052') === 7, 'mgcost is inert by default');
SWUBotSetDisabledFeatures(['try:mgcost']);
$check(_SWUBotSeatCost(1, 'ASH_052') === 9, "mgcost: Chimaera really does cost 9 for this seat — the owner's number");
$check(_SWUBotSeatCost(1, 'SOR_119') === 8 && _SWUBotSeatCost(1, 'ASH_079') === 4, 'mgcost leaves an on-aspect card alone');
SWUBotSetDisabledFeatures([]);

// ══ mgkeep: an efficient on-curve body stays, the spare duplicate goes ═════════════════════════════════════════
// Koska Reeves (4, 4/4) and Grand Moff Tarkin (4, 2/3) cost the same, so the shipped -$cost rule ties and takes
// the first — Koska, the owner's example of the wrong pick. Only Koska clears power + HP >= 2 x cost.
$seat(['ASH_079', 'SOR_084'], $AGGRO);
$check($ids($picks(1, $OFF)) === ['ASH_079'], 'the pre-p11 resourcer resources the 4/4 body — the owner\'s wrong pick');
$check($ids($picks(1, [])) === ['SOR_084'], 'mgkeep (shipped) keeps the efficient body and resources the 2/3 instead');

// A duplicate EVENT is the card to give up — "resourcing one duplicate is fine" — even though the Walker costs more.
$seat(['SOR_125', 'SOR_125', 'SOR_119'], $AGGRO);
$check($ids($picks(1, $OFF)) === ['SOR_119'], 'the pre-p11 resourcer resources the most expensive card');
$check($picks(1, []) === ['myHand-1'], 'mgkeep resources the SECOND copy, keeping the first');

// THE SPLIT (2026-09-23, after mgkeep won its canary at +8.0 vs Krennic): each clause on its own.
// 'mgkeepbody' keeps the efficient body but says nothing about duplicates; 'mgkeepdup' resources the spare copy
// with NO body exception, so it resources the duplicate Koska that the full arm deliberately keeps.
// Each clause is measured with the SHIPPED feature switched off — "alone" has to mean alone.
$BODY = ['mgkeep', 'try:mgkeepbody']; $DUP = ['mgkeep', 'try:mgkeepdup'];
$seat(['ASH_079', 'SOR_084'], $AGGRO);
$check($ids($picks(1, $BODY)) === ['SOR_084'], 'mgkeepbody alone keeps the efficient body');
$check($ids($picks(1, $DUP)) === ['ASH_079'], 'mgkeepdup alone does not: no duplicate here');
$seat(['SOR_125', 'SOR_125', 'SOR_119'], $AGGRO);
$check($picks(1, $DUP) === ['myHand-1'], 'mgkeepdup alone resources the spare duplicate');
$check($ids($picks(1, $BODY)) === ['SOR_119'], 'mgkeepbody alone leaves the duplicate rule out');
// The owner's own example, and the reason the full arm orders its clauses: two Koskas and a card worth keeping.
$seat(['ASH_079', 'ASH_079', 'SOR_095'], $AGGRO);
$check($picks(1, [])[0] !== 'myHand-1', 'mgkeep keeps BOTH Koskas — the efficient body outranks the duplicate rule');
$check($picks(1, $DUP) === ['myHand-1'], 'mgkeepdup ALONE resources the second Koska — the owner\'s wrong pick');
$check($picks(1, $BODY)[0] !== 'myHand-1', 'mgkeepbody alone also keeps it');

// Scope: the arm is midrange-only and inert by default.
// THE SHIP CHECK, in miniature: the feature is gated to rank 2, so every other seat must be unchanged by it.
$seat(['ASH_079', 'SOR_084'], $AGGRO);
foreach (['hyperaggro', 'softaggro', 'softcontrol', 'hardcontrol'] as $st) {
    $check($picks(1, $OFF, $st) === $picks(1, [], $st), "mgkeep does not touch a $st seat");
}

// ══ mgsentinel, the RESOURCING half ═══════════════════════════════════════════════════════════════════════════
// Lieutenant Childsen (4, 2/2, Sentinel) against Battlefield Marine (2): the shipped rule resources the Sentinel
// because it costs more. Against AGGRO the Sentinel is the card that matters.
// Paired with an EVENT, not a body: p11 keeps efficient bodies, and this section is about the Sentinel rule.
$seat(['SOR_035', 'SOR_125'], $AGGRO);
$check($ids($picks(1, [])) === ['SOR_035'], 'fixture: the shipped resourcer resources the Sentinel');
$check($ids($picks(1, ['try:mgsentinel'])) === ['SOR_125'], 'mgsentinel keeps a Sentinel against an aggro leader');

// Against a control leader a 2-power Sentinel is "almost pointless" and goes as before.
$seat(['SOR_035', 'SOR_125'], $CTRL);
$check($ids($picks(1, ['try:mgsentinel'])) === ['SOR_035'], 'mgsentinel: vs control the 2-power Sentinel is still resourced');

// Captain Typho (4, 4/5, Sentinel) clears the 3-power bar, so he is kept against control too.
$seat(['SEC_098', 'SOR_125'], $CTRL);
$check($ids($picks(1, $OFF)) === ['SEC_098'], 'the pre-p11 resourcer resources Typho');
$check($ids($picks(1, ['try:mgsentinel'])) === ['SOR_125'], 'mgsentinel: vs control a 3+ power Sentinel is kept');
$check($picks(1, ['try:mgsentinel'], 'hardcontrol') === $picks(1, [], 'hardcontrol'), 'mgsentinel does not touch a control seat');
$check($ids($picks(1, $OFF)) === ['SEC_098'], 'mgsentinel is inert by default');

bot_test_finish();
