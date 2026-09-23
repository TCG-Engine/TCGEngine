<?php
// BotDataSnapshot.php must be SELF-SUFFICIENT: it declares its own dependency on BotEvaluator.php
// rather than relying on some other file having loaded it first.
//
// ⚠ History (2026-09-23, plan self-review): the plan predicted a load-order FATAL here — SWUBotUnits()
// lives in BotEvaluator.php, which looked as though only BotHeuristic.php <- BotController.php pulled
// in, a chain a human's request would not load. MEASURED FALSE: SWUSim/Custom/GameLogic.php:27
// includes BotController.php, so the chain is loaded before _SWUOpenAction() can ever run. The
// require_once is kept anyway — the file is included directly by tests and could be included directly
// by a future caller — and this is its guard. It is a STATIC check on purpose: the functional call
// below cannot fail while GameLogic.php keeps loading the chain, so it would be decoration on its own.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/botdata_snapshot_standalone_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Custom/BotDataSnapshot.php';

$src = file_get_contents('./SWUSim/Custom/BotDataSnapshot.php');
$check(preg_match('/require_once\s+__DIR__\s*\.\s*.\/BotEvaluator\.php./', $src) === 1,
    'BotDataSnapshot.php declares its own BotEvaluator dependency');

$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->MyBase('ASH_019');
    $b->FillResourcesForPlayer(1, 'SOR_095', 2);
    $b->WithGroundUnitForPlayer(1, 'LOF_093', false);
    $b->WithCardInHandForPlayer(1, 'ASH_248');
});
$s = SWUBotDataSnapshot(1, 'human', ['kind' => 'play', 'card' => 'ASH_248', 'mz' => 'myHand-0']);
$check(count($s['seats']['1']['ground'] ?? []) === 1, 'the snapshot reads the board');

bot_test_finish();
