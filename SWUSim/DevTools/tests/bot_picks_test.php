<?php
// Phase 1b part 2, Task 8 — picks (feature 'picks'). Diagnosis 2026-09-14: "Choose_upgrade_target" matched no
// scoring branch, so upgrades went on the first unit (26 of 30 Craving Power attaches); searches took the first
// card (8D8, 30 of 30); Boba defeated its own unique copies 17 times in 39 games.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_picks_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$check(SWUBotVariantDisabled('no-picks') === ['picks'], 'the picks feature is switchable');

// Devotion (SOR_070, +1/+1): own Marine (3 power) at index 0, own Wampa (4 power) at index 1, an enemy Marine.
$build(function ($b) {
    $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'SOR_070');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('normal');
$check($ctx['tooltip'] === 'Choose_upgrade_target' && str_starts_with(strval($ctx['following'][0] ?? ''), 'ATTACH_UPGRADE|SOR_070'), 'fixture: the attach prompt for Devotion');
$check($stack('normal')[0] === 'myGroundArena-1', 'the upgrade goes on the strongest friendly attacker (the Wampa)');
$check($stack('normal', 1, 'no-picks')[0] === 'myGroundArena-0', '@no-picks: the first unit, as before');

// Searches rank by play value; more good cards beat fewer.
$build(function ($b) { $b->MyLeader('SOR_014', false); });
$W = SWUBotWeights('normal', 1);
$check(_SWUBotSearchScore(1, 'SOR_046', $W) > _SWUBotSearchScore(1, 'SOR_095', $W), 'search: the 4-cost beats the 2-cost');
$check(_SWUBotSearchScore(1, 'SOR_046,SOR_095', $W) > _SWUBotSearchScore(1, 'SOR_046', $W) && _SWUBotSearchScore(1, '', $W) === 0.0, 'search: two cards beat one; none is 0');

// A second copy of a unique unit I control (the uniqueness rule defeats one) is worth less.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'SEC_186'); $b->WithGroundUnitForPlayer(1, 'SEC_186', true); });
$ctx = $botCtx('normal'); $play = null;
foreach ($ctx['actions'] as $a) if (strval($a['cardID']) === 'myHand-0!FSM!') $play = $a;
$check($play !== null && _SWUBotUniqueClash(1, 'SEC_186') && !_SWUBotUniqueClash(1, 'SOR_164'), 'fixture: a second Garindan clashes; a Wampa never does');
$on = SWUBotScoreAction($ctx, $play, 0);
SWUBotSetDisabledFeatures(['picks']); $off = SWUBotScoreAction($ctx, $play, 0); SWUBotSetDisabledFeatures([]);
$check($on < $off, 'the clashing copy scores lower than before');

bot_test_finish();
