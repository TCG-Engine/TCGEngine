<?php
// Feature 'disclosereserve' (p36) — while MY Condemn (SEC_038) sits on an enemy unit, its "On Attack: the defending player may
// disclose Vigilance Villainy. If they do, this unit gets -6/-0" needs a hand that can still disclose. Reprint_Cad (Hemlock Red)
// vs Ninin (Ahsoka Yellow), human game 2026-10-04: Condemn on Ahsoka; disclosed Ninth Sister + Sando, then Ravage, then Marrok —
// then PLAYED Marrok and could no longer blank her. Owner 2026-10-04: hold it.
// The card whose loss would break the disclose is (a) priced, when played, at the damage the -6/-0 stops (W['base'] per point,
// capped at 6, per Condemned enemy), and (b) kept off the resource pick (top keep tier 9, +100 on the plain path).
// Fixtures (dictionary-checked): ASH_030 Marrok (3, Vigilance/Aggression/Villainy — the hand's only Vigilance) · LAW_172 Storm
//   Raider (Aggression/Villainy) · HMW_159 General Grievous (7, Aggression/Villainy) · HMW_071 Ravage (Vigilance/Villainy) ·
//   SOR_095 Battlefield Marine 3/3 · SEC_038 Condemn · ASH_009 Ahsoka Tano (an aggro leader) · HMW_003 · HMW_027 · LAW_097
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_disclosereserve_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-disclosereserve') === ['disclosereserve'], 'disclosereserve is switchable');
$check(in_array('disclosereserve', SWUBotFeatureGroups()['p36'] ?? [], true), 'disclosereserve is in group p36');
// Seat 1 Hemlock with $hand and 3 ready resources; seat 2 Ahsoka (undeployed) and a Battlefield Marine, Condemned by me if $condemn.
$board = function (array $hand, bool $condemn = true) use ($build) {
    $build(function ($b) use ($hand, $condemn) {
        $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027');
        $b->TheirLeader('ASH_009', false);
        $b->FillResourcesForPlayer(1, 'LAW_097', 3);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095');
        if ($condemn) $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SEC_038', 1)]);
    });
};
$scorePlay = function (int $idx, bool $on) use ($botCtx, &$gameName) {
    $l = SWUBotLegalActions($gameName, 1); $ctx = $botCtx('softcontrol');
    $play = null; foreach ((array)$l['actions'] as $a) if (str_starts_with(strval($a['cardID']), "myHand-$idx!")) $play = $a;
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['disclosereserve'];
    $s = $play === null ? null : SWUBotScoreAction($ctx, $play, 0);
    $GLOBALS['SWUBotDisabledFeatures'] = [];
    return $s;
};
// A) Marrok is the hand's only Vigilance: playing it ends the disclose — priced at the Marine's 3 blanked power.
$board(['ASH_030', 'LAW_172', 'HMW_159']);
$W = SWUBotWeights('softcontrol', 1);
$check(_SWUBotBreaksDiscloseReserve(1, 0) && !_SWUBotBreaksDiscloseReserve(1, 1), 'A fixture: only Marrok breaks the reserve');
[$on, $off] = [$scorePlay(0, true), $scorePlay(0, false)];
$check($on !== null && abs(($off - $on) - $W['base'] * 3) < 1e-6, "A: Marrok's play costs W[base] x 3; got $off -> $on");
$check(abs($scorePlay(1, true) - $scorePlay(1, false)) < 1e-9, 'A: Storm Raider (not needed for the disclose) is unchanged');

// B) A second discloser in hand (Ravage): Marrok is free to go — unchanged.
$board(['ASH_030', 'LAW_172', 'HMW_071']);
$check(abs($scorePlay(0, true) - $scorePlay(0, false)) < 1e-9, 'B: Ravage still covers the disclose — Marrok unchanged');

// C) No Condemn of mine in play: nothing to hold for.
$board(['ASH_030', 'LAW_172', 'HMW_159'], false);
$check(abs($scorePlay(0, true) - $scorePlay(0, false)) < 1e-9, 'C: no Condemn — unchanged');

// E) A Condemn seat 2 controls (on its own unit) is no defence of mine: nothing to hold for.
$build(function ($b) {
    $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027'); $b->TheirLeader('ASH_009', false);
    $b->FillResourcesForPlayer(1, 'LAW_097', 3);
    foreach (['ASH_030', 'LAW_172', 'HMW_159'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->WithGroundUnitForPlayer(2, 'SOR_095');
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SEC_038', 2)]);
});
$check(abs($scorePlay(0, true) - $scorePlay(0, false)) < 1e-9, "E: the opponent's own Condemn — Marrok unchanged");

// F) A hand that cannot disclose at all (no Vigilance) has no reserve to keep: Storm Raider's play is unchanged.
$board(['LAW_172', 'HMW_159']);
$check(abs($scorePlay(0, true) - $scorePlay(0, false)) < 1e-9, 'F: nothing can disclose now — unchanged');
$check(!_SWUBotBreaksDiscloseReserve(1, 0) && !_SWUBotBreaksDiscloseReserve(1, 1), 'F: no card "breaks" a reserve that does not exist (the resourcer keeps nothing for it)');

// D) The resource pick never takes the last discloser.
// D1 — against an aggro leader (Ahsoka) the control tiers run: Marrok goes to the keep-everything tier 9.
$board(['ASH_030', 'LAW_172', 'HMW_159']);
$tier = function (bool $on) use ($botCtx) {
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['disclosereserve'];
    $t = _SWUBotResourcing2Tiers($botCtx('softcontrol'), 1, true)[0] ?? null; $GLOBALS['SWUBotDisabledFeatures'] = []; return $t;
};
$check($tier(true) === [9, 0.0], 'D1: Marrok is in the keep tier 9; got ' . json_encode($tier(true)));
$check($tier(false) !== [9, 0.0], 'D1 fixture: today it is not; got ' . json_encode($tier(false)));
// D2 — against a non-aggro leader (a Hemlock mirror) the plain keep path runs. Marrok's printed Sentinel already earns a
// control keep, so the discloser here is Imperial Door Technician (LAW_097, 1, Vigilance/Villainy): the cheapest castable card,
// resourced today; fixed, the Battlefield Marine (Command/Heroism) goes instead. Grievous (Aggression/Villainy) has no Vigilance.
$build(function ($b) {
    $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027'); $b->TheirLeader('HMW_003', false);
    $b->FillResourcesForPlayer(1, 'LAW_097', 3);
    foreach (['LAW_097', 'SOR_095', 'HMW_159'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->WithGroundUnitForPlayer(2, 'SOR_095');
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SEC_038', 1)]);
});
$pick = function (bool $on) use ($botCtx) {
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['disclosereserve'];
    $r = SWUBotChooseResourceCards($botCtx('softcontrol'), 1); $GLOBALS['SWUBotDisabledFeatures'] = []; return $r;
};
$check($pick(false) === ['myHand-0'], 'D2 fixture: today the resourcer gives up the Door Technician; got ' . json_encode($pick(false)));
$check($pick(true) !== ['myHand-0'], 'D2: the Door Technician stays in hand; got ' . json_encode($pick(true)));

bot_test_finish();
