<?php
// Phase 1b part 2, Task 7 — enablers (feature 'enablers'). Diagnosis 2026-09-14 (piett_red): a 5-cost ship
// hard-cast before deploying Piett 46 times in 80 games (deploy 1.5 tied the play's 0.3 × 5 and lost the index
// tie-break); Piett's front Action — "Play a Capital Ship unit from your hand. It costs 1 resource less." — scored
// a flat 0.4 and lost even to a 2-drop.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_enablers_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$kind = function (array $acts, string $k) { foreach ($acts as $a) if (SWUBotActionKind($a) === $k) return strval($a['cardID']); return null; };
$check(SWUBotVariantDisabled('no-enablers') === ['enablers'], 'the enablers feature is switchable');

// Deploy first: Piett (undeployed), 5 resources, MC30 Assault Frigate (5, Capital Ship) in hand.
$build(function ($b) { $b->MyLeader('JTL_005'); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCardInHandForPlayer(1, 'JTL_118'); });
$acts = $botCtx('normal')['actions'];
$deploy = $kind($acts, 'deploy');
$check($deploy !== null && in_array('myHand-0!FSM!', $ids($acts), true), 'fixture: MC30 is castable and Piett can deploy');
$check($stack('normal')[0] === $deploy, 'deploy Piett first: MC30 then costs 2 less');
$check($stack('normal', 1, 'no-enablers')[0] === 'myHand-0!FSM!', '@no-enablers: the old tie-break hard-casts MC30');

// A cost-reducing Action: 4 resources — MC30 (5) is not castable, the Marine (2) is; Piett's Action plays MC30 for 4.
$build(function ($b) { $b->MyLeader('JTL_005'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCardInHandForPlayer(1, 'JTL_118'); $b->WithCardInHandForPlayer(1, 'SOR_095'); });
$acts = $botCtx('normal')['actions'];
$ability = $kind($acts, 'leader-ability');
$check($ability !== null && !in_array('myHand-0!FSM!', $ids($acts), true) && in_array('myHand-1!FSM!', $ids($acts), true),
    'fixture: MC30 not castable at 4, the Marine is, and Piett\'s Action is on offer');
$check($stack('normal')[0] === $ability, 'Piett\'s Action (MC30 for 4) beats the Marine');
$check($stack('normal', 1, 'no-enablers')[0] === 'myHand-1!FSM!', '@no-enablers: the flat 0.4 loses to the Marine (0.6)');

bot_test_finish();
