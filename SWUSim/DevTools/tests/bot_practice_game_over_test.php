<?php
// Bot Practice: the bot stops when the game has a winner. FOUND 2026-09-14 in a live game (183227, owner report
// "the bot kept trying to play after the game ended"): the log read "P1 wins the game (P2's base was defeated)"
// and then "P2's Ezra Bridger attacked P1's base for 6 damage". The winning attack closed P1's action, the turn
// passed to seat 2, and BotControllerPendingPlayerForClient() — which never looked at the winner — reported the
// bot seat as owing a move, so the browser's mode-10017 poller asked for one and ProcessBotControllerStep played it.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_practice_game_over_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
require_once './SWUSim/BotController.php';
// A bot step that DOES run (the bug) ends in the engine's frame-animation cache write, which the CLI lacks.
if (!function_exists('WriteCache')) { function WriteCache(...$a) {} }
if (!function_exists('GamestateUpdated')) { function GamestateUpdated(...$a) {} }

// A Bot Practice board: seat 2 is the bot, it is seat 2's turn in the action phase, with a ready unit to attack.
$bp = function () use ($build) {
    $build(function ($b) { $b->WithActivePlayer(2); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
    AddGlobalEffects(1, 'SWU_MODE_BOTPRACTICE');
    SetSWUBotPlayers([2]);
};

$bp();
$check(SWUGameMode() === 'botpractice' && BotControllerPendingPlayerForClient() === 2, 'fixture: the bot (seat 2) owes a move while the game is on');

// P1 has won. Nothing is pending for the bot any more, and a poll does nothing.
SWUDeclareGameWinner(1);
$check(SWUGetGameWinner() === 1, 'fixture: the game has a winner');
$check(BotControllerPendingPlayerForClient() === 0, 'after the game ends the bot owes no move (the client stops polling)');
$r = ProcessBotControllerStep(1, 'SWUSim', $gameName);
$check(empty($r['applied']) && ($r['retryable'] ?? true) === false, 'a poll after the game ends applies nothing and is not retried; got ' . json_encode($r));

bot_test_finish();
