<?php
// Named Action abilities — a game SAVED while Crosshair's old Buff/Deal chooser was open (OPTIONCHOOSE + CUSTOM
// SHD_087#choice|<mz>, queued by the single-provider closure before SHD_087.0 / SHD_087.1 existed) must still resolve.
// The shim pays what the dispatcher now pays (2 resources for Buff, the exhaust for Deal), since the old closure didn't.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/crosshair_choice_saved_game_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
$queueOld = function () {
    DecisionQueueController::AddDecision(1, 'OPTIONCHOOSE', 'Buff&Deal', 1, tooltip: 'Crosshair:_gain_+1/+0_or_deal_power_to_an_enemy?');
    DecisionQueueController::AddDecision(1, 'CUSTOM', 'SHD_087#choice|myGroundArena-0', 1);
};
$board = function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 2); $b->WithGroundUnitForPlayer(1, 'SHD_087'); $b->WithGroundUnitForPlayer(2, 'SOR_046'); };

$g = $build($board); $queueOld();
$err = '';
try { ob_start(); $g->answerDecision(1, 'Buff'); ob_end_clean(); } catch (Throwable $e) { ob_end_clean(); $err = $e->getMessage(); }
$cross = GetGroundArena(1)[0];
$check($err === '', "Buff resolves (no error: '$err')");
$check(intval(ObjectCurrentPower($cross)) === 3, 'Buff: Crosshair gets +1/+0 (power ' . intval(ObjectCurrentPower($cross)) . ')');
$check(SWUResourceCount(1, true) === 0, 'Buff: the 2 resources are paid (' . SWUResourceCount(1, true) . ' ready left)');

$g = $build($board); $queueOld();
$err = '';
try { ob_start(); $g->answerDecision(1, 'Deal'); ob_end_clean(); } catch (Throwable $e) { ob_end_clean(); $err = $e->getMessage(); }
$check($err === '', "Deal resolves (no error: '$err')");
$check(intval(GetGroundArena(1)[0]->Status ?? 1) === 0, 'Deal: Crosshair is exhausted (its cost)');
$check(intval(GetGroundArena(2)[0]->Damage ?? 0) === 2, 'Deal: 2 damage to the enemy unit');
bot_test_finish();
