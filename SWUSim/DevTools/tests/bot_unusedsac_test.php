<?php
// Feature 'unusedsac' (p18) — task 3 from the owner's Karabast Krennic games: the human never sacrificed a unit that had
// not attacked yet that round (0%); the bot did 31% of the time. A sacrifice that can WAIT (Krennic's repeatable Action)
// charges a still-ready unit for the attack it throws away, so the bot attacks first and cashes the body in after. A
// one-time trade (Chimaera) only breaks ties on it — a good trade is never declined over it.
// Real play path (board -> legal actions/prompt -> the heuristic stack's own choice); each case is also run with the
// feature off (variant 'no-unusedsac') to show the ruling is what moves the pick.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_unusedsac_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

const KRENNIC = 'LAW_008';
const CHIMAERA = 'ASH_052';
const MERC = 'LAW_159';      // Expendable Mercenary 3/3 — "When Defeated: You may resource this unit": the deck's fodder
const MARINE = 'SOR_095';    // 3/3 vanilla
const CONSULAR = 'SOR_046';  // 3/7 vanilla
const TROOPER = 'SOR_128';   // 3/1 vanilla
$pickedReady = fn(string $mz) => ($v = SWUBotViewForMz(1, $mz)) === null ? $mz : ($v['ready'] ? 'READY ' : 'EXHAUSTED ') . $v['cardID'];

// A: the round's first decision, my only unit a READY Mercenary — attack with it first; Krennic waits.
$mainPick = function (bool $mercReady, string $variant = '') use (&$gameName, $build) {
    $build(function ($b) use ($mercReady) {
        $b->MyLeader(KRENNIC); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
        $b->WithGroundUnitForPlayer(1, MERC, $mercReady);
        $b->WithGroundUnitForPlayer(2, TROOPER, true);
    });
    $legal = SWUBotLegalActions($gameName, 1);
    return SWUBotActionKind(SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant) ?? []);
};
$got = $mainPick(true);
$check(SWUBotUnusedSacPremium(['ready' => true, 'attackPower' => 3]) > 0.0, 'fixture: the premium is live in the action phase');
$check($got !== 'leader-ability', 'A: a READY Mercenary is not sacrificed before it attacks (got ' . $got . ')');
// (On this board the attack outscores the Action even without the feature, so the switch is shown on the Action's own
// value: it carries the Mercenary's unused attack.)
$legal = SWUBotLegalActions($gameName, 1);
$ab = null; foreach ((array)$legal['actions'] as $a) if (SWUBotActionKind($a) === 'leader-ability') $ab = $a;
$W = SWUBotWeights('softcontrol', 1);
$abilityValue = function (string $variant) use (&$legal, &$ab, $W) {
    SWUBotSetDisabledFeatures(SWUBotVariantDisabled($variant) ?? []);
    try { return _SWUBotAbilityValue(['seat' => 1] + $legal, $ab, $W); } finally { SWUBotSetDisabledFeatures([]); }
};
$merc = SWUBotUnits(1)[0];
$on = $abilityValue(''); $offV = $abilityValue('no-unusedsac');
$check($ab !== null && abs(($offV - $on) - SWU_BOT_UNUSED_SAC_PER_POWER * $merc['attackPower']) < 1e-9,
    sprintf('A: Krennic\'s Action is worth %.2f less while the Mercenary is unused (its attack, %d x %.1f): on %.2f, off %.2f',
        SWU_BOT_UNUSED_SAC_PER_POWER * $merc['attackPower'], $merc['attackPower'], SWU_BOT_UNUSED_SAC_PER_POWER, $on, $offV));
// With TWO ready Mercenaries the Action's pick is still pending in the lookahead: the cheapest candidate's unused
// attack is charged all the same.
$build(function ($b) {
    $b->MyLeader(KRENNIC); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
    $b->WithGroundUnitForPlayer(1, MERC, true);
    $b->WithGroundUnitForPlayer(1, MERC, true);
});
$legal = SWUBotLegalActions($gameName, 1);
$ab = null; foreach ((array)$legal['actions'] as $a) if (SWUBotActionKind($a) === 'leader-ability') $ab = $a;
$on = $abilityValue(''); $offV = $abilityValue('no-unusedsac');
$check($ab !== null && abs(($offV - $on) - SWU_BOT_UNUSED_SAC_PER_POWER * 3) < 1e-9,
    sprintf('A: two ready Mercenaries (pick pending) — the Action still carries one unused attack: on %.2f, off %.2f', $on, $offV));
// Outside the action phase there is no attack left to throw away.
$merc = SWUBotUnits(1)[0];
$check(SWUBotUnusedSacPremium($merc) > 0.0, 'fixture: a ready Mercenary in the action phase carries the premium');
$phase = GetCurrentPhase(); SetCurrentPhase('REGROUP');
$check(SWUBotUnusedSacPremium($merc) === 0.0, 'A: in the regroup phase a ready unit carries no premium');
SetCurrentPhase($phase);
$got = $mainPick(false);
$check($got === 'leader-ability', 'A: once it has attacked (exhausted), Krennic cashes it in (got ' . $got . ')');

// B: Krennic's pick — a ready Marine and an exhausted one: the one that already attacked goes.
$krennicPick = function (string $variant = '') use (&$gameName, $build, $act, $pickedReady) {
    $build(function ($b) {
        $b->MyLeader(KRENNIC); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
        $b->WithGroundUnitForPlayer(1, MARINE, true);
        $b->WithGroundUnitForPlayer(1, MARINE, false);
        $b->WithGroundUnitForPlayer(2, TROOPER, true);
    });
    $ab = null;
    foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (SWUBotActionKind($a) === 'leader-ability') { $ab = $a; break; }
    if ($ab === null) return 'no Krennic Action on offer';
    $act(1, intval($ab['mode'] ?? 10002), strval($ab['cardID']));
    $legal = SWUBotLegalActions($gameName, 1);
    if (($legal['kind'] ?? '') !== 'decision') return 'no prompt';
    return $pickedReady(strval(SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant)['cardID'] ?? 'PASS'));
};
$got = $krennicPick();
$check($got === 'EXHAUSTED ' . MARINE, 'B: Krennic sacrifices the Marine that already attacked (got ' . $got . ')');
$got = $krennicPick('no-unusedsac');
$check($got === 'READY ' . MARINE, 'B control, feature OFF: the tie goes to the first (ready) Marine (got ' . $got . ')');

// C: Chimaera — a one-time trade. A READY Marine for their Consular is still taken (declining loses the kill)...
$chimaeraPick = function (array $mine, string $variant = '', string $enemy = CONSULAR) use (&$gameName, $build, $act, $pickedReady) {
    $build(function ($b) use ($mine, $enemy) {
        $b->MyLeader(KRENNIC, true, false, true); $b->MyBase('JTL_020'); $b->TheirBase('SOR_020');
        $b->FillResourcesForPlayer(1, MARINE, 10);
        $b->WithCardInHandForPlayer(1, CHIMAERA);
        foreach ($mine as [$c, $ready]) $b->WithGroundUnitForPlayer(1, $c, $ready);
        $b->WithGroundUnitForPlayer(2, $enemy, true);
    });
    $play = null;
    foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (str_starts_with(strval($a['cardID'] ?? ''), 'myHand-0')) { $play = $a; break; }
    if ($play === null) return 'Chimaera was not playable';
    $act(1, intval($play['mode'] ?? 10002), strval($play['cardID']));
    $legal = SWUBotLegalActions($gameName, 1);
    if (stripos(strval($legal['decisionTooltip'] ?? ''), 'friendly') === false) return 'unexpected prompt: ' . strval($legal['decisionTooltip'] ?? '');
    $c = strval(SWUBotHeuristicChoose('midrange', (array)$legal['actions'], $legal, $variant)['cardID'] ?? 'PASS');
    return $c === 'PASS' ? 'PASS' : $pickedReady($c);
};
$got = $chimaeraPick([[MARINE, true]]);
$check($got === 'READY ' . MARINE, 'C: a ready Marine for their Consular — the trade is still taken (got ' . $got . ')');
// A tight trade — my ready 2-cost Marine for their 3-cost Liberated Slaves (worth +1): charging the Marine's unused attack
// (1.5) would decline it, so on a one-time trade it must only break ties.
$got = $chimaeraPick([[MARINE, true]], '', 'SHD_200');
$check($got === 'READY ' . MARINE, 'C: a ready Marine for their 3-cost Liberated Slaves — a +1 trade, still taken (got ' . $got . ')');
// ...and between two equal Marines, the one that already attacked goes.
$got = $chimaeraPick([[MARINE, true], [MARINE, false]]);
$check($got === 'EXHAUSTED ' . MARINE, 'C: of two Marines, Chimaera takes the one that already attacked (got ' . $got . ')');
$got = $chimaeraPick([[MARINE, true], [MARINE, false]], 'no-unusedsac');
$check($got === 'READY ' . MARINE, 'C control, feature OFF: the tie goes to the first (ready) Marine (got ' . $got . ')');

bot_test_finish();
