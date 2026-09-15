<?php
// Phase 1b part 2, Task 4 — hostile targeting (feature 'targeting'). Diagnosis 2026-09-14: "-N/-N" prompts read as
// beneficial (the word "give"), so Talzin debuffed its own units 229 times vs 16 enemies; damage prompts ignored
// kills (Maul missed an available kill 49 of 72 times); multi-selects hit one unit; tokens were worth 0.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_targeting_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$check(SWUBotVariantDisabled('no-targeting') === ['targeting'], 'the targeting feature is switchable');

// ── The amount a prompt deals.
$check(_SWUBotEffectAmount('Deal_1_damage_to_a_different_unit', '') === 1, '"Deal 1 damage" → 1');
$check(_SWUBotEffectAmount('Give_a_unit_-2/-2_for_this_phase', '') === 2, '"-2/-2" → 2 (the HP half)');
$check(_SWUBotEffectAmount('Choose_a_unit', 'APPLY_PHASE_DEBUFF|3|3|LOF_035') === 3, 'a debuff continuation carries its HP amount');
$check(_SWUBotEffectAmount('Choose_a_unit', 'APPLY_PHASE_DEBUFF|4|0|SOR_216') === 0, '-4/-0 defeats nothing');
$check(_SWUBotEffectAmount('Exhaust_a_unit', 'EXHAUST_UNIT') === 0, 'an exhaust has no amount');
$check(_SWUBotCardTextEffect('LOF_035') === 'hostile' && _SWUBotCardTextEffect('SOR_095') === '', 'card text: "give a unit -3/-3" is hostile; a vanilla unit is not');

// ── A REAL -N/-N prompt: Incapacitate (LAW_131, "Give a unit -2/-2 for this phase."), P1's own Marine at index 0,
// P2's Wampa (4/5) and TIE/ln (2/1).
$build(function ($b) {
    $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCardInHandForPlayer(1, 'LAW_131');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true);
});
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('normal');
$check(in_array('myGroundArena-0', $ids($ctx['actions']), true) && in_array('theirSpaceArena-0', $ids($ctx['actions']), true)
    && (str_contains($ctx['tooltip'], '-2/-2') || str_starts_with(strval($ctx['following'][0] ?? ''), 'APPLY_PHASE_DEBUFF')),
    'fixture: the -2/-2 prompt offers every unit; got ' . $ctx['tooltip'] . ' / ' . ($ctx['following'][0] ?? ''));
$check($stack('normal')[0] === 'theirSpaceArena-0', '-2/-2 defeats the TIE (2/1): not the Wampa, never the own Marine');
$check($stack('normal', 1, 'no-targeting')[0] === 'myGroundArena-0', '@no-targeting: the old pick, the own Marine');

// ── Kill vs chip, multi-select, tokens (the scoring helpers on a built board).
$build(function ($b) {
    $b->MyLeader('SOR_014', false);
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); $b->WithGroundUnitForPlayer(2, 'SEC_T01', true);
});
$W = SWUBotWeights('normal', 1);
$check(_SWUBotTargetScore(1, 'theirSpaceArena-0', true, 1, 'DEAL_TARGET', $W) > _SWUBotTargetScore(1, 'theirGroundArena-0', true, 1, 'DEAL_TARGET', $W),
    'deal 1: defeating the 2/1 beats nicking the 4/5');
$check(_SWUBotTargetsScore(1, 'theirGroundArena-0&theirSpaceArena-0', true, 1, 'DEAL_TARGET', $W) > _SWUBotTargetsScore(1, 'theirSpaceArena-0', true, 1, 'DEAL_TARGET', $W),
    'a multi-select scores the sum of its picks (IG-2000 hits all it can)');
$spy = SWUBotViewForMz(1, 'theirGroundArena-1');
$check($spy !== null && $spy['cardID'] === 'SEC_T01' && SWUBotUnitValue($spy) > 0.0, 'a token is worth its body, not 0');
SWUBotSetDisabledFeatures(['targeting']);
$check(SWUBotUnitValue($spy) === 0.0, '@no-targeting: a token is worth 0, as before');
SWUBotSetDisabledFeatures([]);

bot_test_finish();
