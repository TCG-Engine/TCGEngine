<?php
// Twin Suns self-play SMOKE: three fixed games through DevTools/SWUSimTwinSunsSelfPlay.php must play to a winner.
// Each pins a bug the harness found on 2026-10-01 (SWUSim/docs/todo-twinsuns-fill-bot.md, step 2):
//   • 3 seats, game 1 (seed ts-1): seat 3's mulligan prompt never reached the bot — the decision bridge looked only
//     at seats 1-2 (DevTools/TestAutomationBridge.php BridgeDecisionSeats).
//   • 4 seats, game 14 (seed ts-14): seats 1 and 2 eliminated, then the end-of-phase scoring at regroup looped
//     RGS->DRAW->RES->READY->RGS forever — AllQueuesEmpty() checked seats 1..(live count) and never saw seats 3/4's
//     prompts (Core/DecisionQueueController.php SeatIDs).
//   • 3 seats, game 19 (seed ts-19): P1 eliminated ITSELF mid-action (TWI_146 Steela's base damage at 1 HP) and the turn
//     stayed on the dead seat (_SWUMoveTurnOffEliminatedSeat; core/TwinSuns_EliminatedOnOwnAction_TurnMovesOn.md).
//     The bot's lookahead is time-budgeted, so this game takes that line in most runs, not every run.
// Each game is a child process with a hard time limit, so a regression fails here instead of hanging the suite.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/twinsuns_selfplay_smoke_test.php
$fails = 0;
foreach ([[3, 1], [4, 14], [3, 19]] as [$seats, $game]) {
    $cmd = sprintf('cd /var/www/html/TCGEngine && timeout 300 php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G DevTools/SWUSimTwinSunsSelfPlay.php --child=%d --seats=%d --seed=ts 2>&1', $game, $seats);
    $out = (string)shell_exec($cmd);
    $r = null;
    foreach (explode("\n", $out) as $l) if (strpos($l, '[TSGAME] ') === 0) $r = json_decode(substr($l, 9), true);
    $ok = is_array($r) && !empty($r['finished']) && !empty($r['winners']);
    echo ($ok ? 'PASS' : 'FAIL') . ": {$seats} seats, game {$game} plays to a winner — "
        . (is_array($r) ? json_encode(['winners' => $r['winners'], 'rounds' => $r['rounds'], 'steps' => $r['steps'], 'error' => $r['error']]) : 'no result (timed out or crashed)') . "\n";
    if (!$ok) $fails++;
}
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
