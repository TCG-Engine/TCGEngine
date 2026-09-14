<?php
// Phase 1b part 3, Task 4 — a card's "Choose one" is judged by what each option does (feature 'modes'). Diagnosis
// 2026-09-14 (Talzin): Shatterpoint's option prompt fell through to first-legal — "DefeatWeak" 339 of 352 times, and
// in 95 of them, with the Force available, "ForceDefeat" had the better target.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_modes_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$check(SWUBotVariantDisabled('no-modes') === ['modes'], 'modes is switchable');

// Shatterpoint with the Force against a Wampa (5 HP): only "ForceDefeat" defeats anything.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithForceForPlayer(1);
    $b->WithCardInHandForPlayer(1, 'LOF_079'); $b->WithGroundUnitForPlayer(2, 'SOR_164', true); });
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('normal');
$check($ctx['type'] === 'OPTIONCHOOSE' && $ids($ctx['actions']) === ['DefeatWeak', 'ForceDefeat'], 'fixture: Shatterpoint\'s two modes; got ' . $ctx['type'] . ' ' . json_encode($ids($ctx['actions'])));
$check($stack('normal')[0] === 'ForceDefeat', 'the mode that defeats the Wampa is chosen');
$check($stack('normal', 1, 'no-modes')[0] === 'DefeatWeak', '@no-modes: the first option, as before');

// Against a Marine (3 HP) both modes defeat it: the tie keeps the first ("DefeatWeak" — the Force is kept).
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithForceForPlayer(1);
    $b->WithCardInHandForPlayer(1, 'LOF_079'); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$check($stack('normal')[0] === 'DefeatWeak', 'equal outcomes keep the first option (no Force spent)');

bot_test_finish();
