<?php
// Phase 1b part 3, Task 3 — targeting v2 (feature 'targeting2'). Diagnosis 2026-09-14 (Aurra): 95 prompts in
// SWUSim/Custom read "Deal_N_to_a_unit", which the damage parser missed (amount 0 → the most valuable unit, not a
// kill); The Tree Remembers ("If it costs 3 or less, defeat it") picked the first candidate; Condemn ("…and loses all
// other abilities") went on the bot's own units 67 of 67 times.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_targeting2_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$check(SWUBotVariantDisabled('no-targeting2') === ['targeting2'], 'targeting2 is switchable');

$check(_SWUBotEffectAmount('Deal_3_to_a_unit', '') === 3, '"Deal_3_to_a_unit" → 3');
SWUBotSetDisabledFeatures(['targeting2']);
$check(_SWUBotEffectAmount('Deal_3_to_a_unit', '') === 0, '@no-targeting2: the old parser misses it');
SWUBotSetDisabledFeatures([]);
$check(function_exists('_SWUBotDefeatIfCostAtMost') && _SWUBotDefeatIfCostAtMost('LAW_132#0') === 3 && _SWUBotDefeatIfCostAtMost('SOR_078#0') === null,
    'The Tree Remembers: "If it costs 3 or less, defeat it"; Vanquish has no cost condition');

// The Tree Remembers on a real prompt: P2's Wampa (cost 4) first, Marine (cost 2) second — only the Marine dies.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LAW_132');
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('control');
$check($ids($ctx['actions']) === ['theirGroundArena-0', 'theirGroundArena-1'], 'fixture: the Tree Remembers target prompt; got ' . json_encode($ids($ctx['actions'])) . ' ' . $ctx['tooltip']);
$check($stack('control')[0] === 'theirGroundArena-1', 'it targets the unit it can defeat (the 2-cost Marine)');
$check($stack('control', 1, 'no-targeting2')[0] === 'theirGroundArena-0', '@no-targeting2: the first candidate, as before');

// Condemn: a harmful upgrade goes on the enemy.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SEC_038');
    $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); });
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('control');
$check($ctx['tooltip'] === 'Choose_upgrade_target' && in_array('theirGroundArena-0', $ids($ctx['actions']), true), 'fixture: Condemn\'s attach prompt offers the enemy Wampa; got ' . $ctx['tooltip'] . ' ' . json_encode($ids($ctx['actions'])));
$check($stack('control')[0] === 'theirGroundArena-0', 'Condemn ("loses all other abilities") goes on the enemy');
$check($stack('control', 1, 'no-targeting2')[0] === 'myGroundArena-0', '@no-targeting2: it went on my own Wampa');

bot_test_finish();
