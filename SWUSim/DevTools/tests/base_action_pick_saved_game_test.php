<?php
// Named Action abilities — a game SAVED with the old base chooser pending (OPTIONCHOOSE + CUSTOM BASE_ACTION_PICK,
// queued by SWUBaseAction before named abilities) must still resolve: the handler stays for exactly this case.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/base_action_pick_saved_game_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
$g = $build(function ($b) {
    $b->MyBase('LOF_022');
    $b->WithUpgradeOnBaseForPlayer(1, 'HMW_095');
    $b->WithGroundUnitForPlayer(2, 'SOR_095');
});
// The old queue, as SWUBaseAction wrote it (labels: "EpicAction" / the Fortify upgrade's title).
DecisionQueueController::AddDecision(1, 'OPTIONCHOOSE', '@LOF_022&EpicAction&CarboniteChamber', 1, tooltip: 'Which_base_ability_do_you_want_to_use?');
DecisionQueueController::AddDecision(1, 'CUSTOM', 'BASE_ACTION_PICK', 1);
ob_start(); $g->answerDecision(1, 'CarboniteChamber'); ob_end_clean();
$ups = 0;
foreach (GetBase(1) as $b) if (empty($b->removed)) $ups = count(GetUpgradesOnUnit($b));
$check($ups === 0, "the pending BASE_ACTION_PICK still runs the chosen Action (Carbonite Chamber defeated; upgrades left: $ups)");
bot_test_finish();
