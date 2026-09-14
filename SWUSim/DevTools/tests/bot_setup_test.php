<?php
// Phase 1b part 3, Task 6 — damage and -N/-N set up my own finisher (feature 'setup'). Diagnosis 2026-09-14
// (Aurra): her pings and debuffs went to the most valuable enemy, not the one they would leave inside her leader's
// "Action [Exhaust]: Defeat a non-leader unit with 1 or less remaining HP".
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_setup_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$check(SWUBotVariantDisabled('no-setup') === ['setup'], 'setup is switchable');

// Aurra Sing (LAW_004, ready, undeployed) plays Incapacitate (LAW_131, "Give a unit -2/-2 for this phase") into a
// Wampa (4/5) and a Battlefield Marine (3/3): -2/-2 leaves the Marine at 1 — Aurra's Action then defeats it.
$aurra = function (bool $ready) {
    return function ($b) use ($ready) { $b->MyLeader('LAW_004', $ready); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LAW_131');
        $b->WithGroundUnitForPlayer(2, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); };
};
$build($aurra(true));
$check(function_exists('_SWUBotFinisherHP') && _SWUBotFinisherHP(1) === 1, 'Aurra\'s ready leader Action defeats at 1 or less');
$act(1, 10002, 'myHand-0!FSM!');
$got = $ids($botCtx('control')['actions']);
$check(in_array('theirGroundArena-0', $got, true) && in_array('theirGroundArena-1', $got, true), 'fixture: Incapacitate offers both enemy units; got ' . json_encode($got));
$check($stack('control')[0] === 'theirGroundArena-1', 'the Marine (3 → 1, inside the finisher) is the target');
$check($stack('control', 1, 'no-setup')[0] === 'theirGroundArena-0', '@no-setup: the Wampa (more value, no follow-up), as before');

// The finisher is exhausted: no set-up, the Wampa again.
$build($aurra(false));
$check(function_exists('_SWUBotFinisherHP') && _SWUBotFinisherHP(1) === 0, 'an exhausted Aurra has no finisher this round');
$act(1, 10002, 'myHand-0!FSM!');
$check($stack('control')[0] === 'theirGroundArena-0', 'no ready finisher: the Wampa, as before');

bot_test_finish();
