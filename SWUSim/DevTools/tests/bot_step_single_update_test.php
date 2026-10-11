<?php
// A bot step (mode 10017) commits ONE update. Owner report 2026-10-04: "sometimes the animation framework lags and queues an
// animation twice … it looks like the opponent attacked twice" (the damage is applied once). Cause: ProcessBotControllerStep
// runs the bot's move through a NESTED EngineExecuteLoadedAction, which writes the gamestate, the frame-animation cache and the
// update marker (update N). The 10017 wrapper then echoed the nested writeGamestate/updateCache flags and committed AGAIN —
// update N+1 with the same global $frameAnimations (the attack's exhaust/lunge/damage). A browser that polled between the two
// writes played the attack for N and again for N+1. The wrapper also re-ran GameAfterEngineAction / ProcessGoldfishAutomation.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_step_single_update_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
require_once './SWUSim/BotController.php';
// The CLI bootstrap has no cache layer — record every write instead (the engine's post-action path calls these).
$GLOBALS['CACHE_WRITES'] = [];
if (!function_exists('WriteCache')) { function WriteCache($k, $v) { $GLOBALS['CACHE_WRITES'][] = [$k, $v]; } }
if (!function_exists('ReadCache')) { function ReadCache($k) { return ''; } }
if (!function_exists('GamestateUpdated')) { function GamestateUpdated($g) { $GLOBALS['CACHE_WRITES'][] = ['UPDATED', $GLOBALS['updateNumber']]; } }

// Bot Practice: seat 2 is the bot, it is seat 2's turn, with a ready unit — its move is an attack (exhaust + lunge + damage).
$build(function ($b) { $b->WithActivePlayer(2); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
AddGlobalEffects(1, 'SWU_MODE_BOTPRACTICE');
SetSWUBotPlayers([2]);
global $updateNumber, $gameName;
WriteGamestate('./SWUSim/');
$u0 = intval($updateNumber);
$r = EngineExecuteLoadedAction(['playerID' => 1, 'mode' => 10017, 'cardID' => '', 'buttonInput' => '', 'chkInput' => [], 'inputText' => ''],
    'SWUSim', $gameName, []);
$check(!empty($r['botStepApplied']), 'fixture: the bot step applied a move');

$animBatches = array_values(array_filter($GLOBALS['CACHE_WRITES'], fn($w) => str_ends_with(strval($w[0]), '_anim') && $w[1] !== '[]'));
$updates = array_values(array_filter($GLOBALS['CACHE_WRITES'], fn($w) => $w[0] === 'UPDATED'));
$check(count($animBatches) >= 1, 'fixture: the move queued animations');
$check(intval($updateNumber) === $u0 + 1, 'one bot step advances the update by exactly ONE; got ' . $u0 . ' -> ' . intval($updateNumber));
$check(count($updates) === 1, 'the update marker is published once; got ' . count($updates));
$check(count($animBatches) === 1, 'the move\'s animations are published for ONE update, not replayed under the next; got ' . count($animBatches));

bot_test_finish();
