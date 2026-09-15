<?php
// Feature 'fodder' — CHOOSE THE RIGHT SACRIFICE. Owner, 2026-09-15: "is Krennic prioritizing sac'ing the Expendable
// Mercenary? this way they almost double ramp. gaining an exhausted resource and a Credit." Measured over 48 Krennic
// games: Expendable Mercenary (LAW_159, "When Defeated: You may resource this unit from its owner's discard pile")
// was PLAYED 40 times and SACRIFICED 0 times, while Imperial Door Technician went 39/30 and Ant Droid 25/19.
//
// Two causes, both fixed here:
//  1. SWUBotSacrificeCost allowed a flat 1.5 for any "When Defeated" text, so the choice collapsed to printed cost
//     and the cheapest body always won. A defeat that RESOURCES the unit is ramp — it should be the first pick, not
//     the last. A defeat that heals or draws is worth more than the flat allowance too.
//  2. The pick itself (a hostile effect aimed at my own board) scored -SWUBotUnitValue, never the sacrifice cost,
//     so even a correct cost model would not have reached the prompt.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_fodder_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';

$check(SWUBotVariantDisabled('no-fodder') === ['fodder'] && in_array('fodder', (array)SWUBotVariantDisabled('no-p3'), true),
    'fodder is switchable, alone and in the part-3 group');

// The cost model, unit by unit. Values are "what it costs me to defeat this", so LOWER is better fodder.
$build(function ($b) {
    $b->MyLeader('LAW_008', true, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 8);
    $b->WithGroundUnitForPlayer(1, 'LAW_159', true);   // Expendable Mercenary 4-cost: resources itself when defeated
    $b->WithGroundUnitForPlayer(1, 'LAW_097', true);   // Imperial Door Technician 1-cost: heals 2 when defeated
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);   // Marine 2-cost: no When Defeated at all
    $b->WithGroundUnitForPlayer(1, 'SOR_164', true);   // Wampa 4-cost: no When Defeated
});
$units = [];
foreach (SWUBotUnits(1) as $v) $units[$v['cardID']] = $v;
$check(count($units) === 4, 'fixture: four friendly units');
SWUBotSetDisabledFeatures([]);
$merc = SWUBotSacrificeCost($units['LAW_159']);
$tech = SWUBotSacrificeCost($units['LAW_097']);
$marine = SWUBotSacrificeCost($units['SOR_095']);
$wampa = SWUBotSacrificeCost($units['SOR_164']);
$check($merc < $tech, sprintf('the Mercenary is the cheapest fodder: %.2f vs the Technician %.2f', $merc, $tech));
$check($tech < $marine, sprintf('a When-Defeated body beats a vanilla one: %.2f vs %.2f', $tech, $marine));
$check($marine < $wampa, sprintf('between vanilla bodies the cheaper one goes: %.2f vs %.2f', $marine, $wampa));
SWUBotSetDisabledFeatures(['fodder']);
$check(SWUBotSacrificeCost($units['LAW_159']) > SWUBotSacrificeCost($units['LAW_097']),
    '@no-fodder: the old model, where the 4-cost Mercenary looks worse than a 1-cost body');

// The pick: Krennic's Action defeats a friendly unit for a Credit. It must choose the Mercenary.
// The leader Action first, then the friendly-unit prompt behind it: drive both through the stack's own dispatcher
// ($act, the one the lookahead uses) and read which unit the bot chose to defeat.
$pickedUnit = function (string $variant) use (&$gameName, $build, $act, $botCtx) {
    $build(function ($b) {
        $b->MyLeader('LAW_008', true, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 8);
        $b->WithGroundUnitForPlayer(1, 'LAW_159', true); $b->WithGroundUnitForPlayer(1, 'LAW_097', true);
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    });
    $legal = SWUBotLegalActions($gameName, 1);
    $leaderAction = null;
    foreach ((array)$legal['actions'] as $a) {
        if (str_ends_with(strval($a['cardID'] ?? ''), '!CustomInput!LeaderAbility')) { $leaderAction = $a; break; }
    }
    if ($leaderAction === null) return 'the leader Action was not offered';
    $act(1, intval($leaderAction['mode'] ?? 10001), strval($leaderAction['cardID']));
    $legal2 = SWUBotLegalActions($gameName, 1);
    if (($legal2['kind'] ?? '') !== 'decision') return 'no prompt (kind=' . strval($legal2['kind'] ?? '') . ')';
    $pick = SWUBotHeuristicChoose('control', (array)$legal2['actions'], $legal2, $variant);
    $mz = strval($pick['cardID'] ?? '');
    $v = SWUBotViewForMz(1, $mz);
    return $v === null ? $mz : strval($v['cardID']);
};
$got = $pickedUnit('');
$check($got === 'LAW_159', 'Krennic sacrifices the Expendable Mercenary (ramp), not the cheapest body; got ' . $got);

bot_test_finish();
