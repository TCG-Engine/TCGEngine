<?php
// Phase 1b part 3, Task 1 — the dud gate (feature 'dudgate'). Four diagnoses on the run-5 stack (2026-09-14) found
// Control (and Talzin) casting removal and wipes with nothing to hit or onto their own units: the play scorer adds
// the tag weight regardless (Aurra: Crushing Blow did nothing in 96 of 214 casts and hit her own unit 14 more times).
// An effect event is now played in the lookahead first.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_dudgate_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$playScore = function (string $style, string $cardID) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$check(SWUBotVariantDisabled('no-dudgate') === ['dudgate'] && in_array('dudgate', (array)SWUBotVariantDisabled('no-p3'), true), 'dudgate is switchable, alone and in the part-3 group');
$check(defined('SWU_BOT_PART3_FEATURES') && SWUBotVariantDisabled('no-p3') === SWU_BOT_PART3_FEATURES && isset($GLOBALS['SWUBotChoosers']['heuristic-control@no-p3']),
    '@no-p3 turns off exactly the part-3 features and is registered');

// A — Vanquish (SOR_078, "Defeat a non-leader unit", 7 for seat 1) with no enemy unit: its only target is my own
// Marine. Held; the Marine attacks the base instead.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 7); $b->WithCardInHandForPlayer(1, 'SOR_078'); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$check(in_array('myHand-0!FSM!', $ids($botCtx('normal')['actions']), true) && in_array('myGroundArena-0!FSM!', $ids($botCtx('normal')['actions']), true), 'fixture: Vanquish and the attack are on offer');
$check($stack('normal')[0] === 'myGroundArena-0!FSM!', 'a removal event with no enemy target is held (it would defeat my own Marine)');
$check($stack('normal', 1, 'no-dudgate')[0] === 'myHand-0!FSM!', '@no-dudgate: the old pick — Vanquish into my own Marine');

// B — the value floor: removal must take at least half its printed cost in enemy value (Vanquish: 5 → 2.5).
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 7); $b->WithCardInHandForPlayer(1, 'SOR_078'); $b->WithGroundUnitForPlayer(2, 'SEC_T01', true); });
$check($playScore('normal', 'myHand-0!FSM!') === -0.5, 'Vanquish on a Spy token (value 1 < 2.5) is a dud');
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 7); $b->WithCardInHandForPlayer(1, 'SOR_078'); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); });
$check($playScore('normal', 'myHand-0!FSM!') > 0.0, 'Vanquish on a Wampa is worth playing');

// C — pressure lifts the floor: 5 left on my base against a Marine (their clock on me: 2) → Vanquish on the Marine.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->MyBase('SOR_024', 25); $b->FillResourcesForPlayer(1, 'SOR_095', 7); $b->WithCardInHandForPlayer(1, 'SOR_078'); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$check(SWUBotClock(2, 1) === 2 && $playScore('normal', 'myHand-0!FSM!') > 0.0, 'under pressure (their clock 2) a small removal is played');

// D — a wipe must take at least as much value as it costs me. Single Reactor Ignition (12 for seat 1).
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 12); $b->WithCardInHandForPlayer(1, 'LAW_044');
    $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$check($playScore('control', 'myHand-0!FSM!') === -0.5, 'a wipe that kills my two Wampas for their Marine is a dud');
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 12); $b->WithCardInHandForPlayer(1, 'LAW_044');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); });
$check($playScore('control', 'myHand-0!FSM!') > 0.0, 'a wipe that kills their two Wampas for my Marine is worth playing');

bot_test_finish();
