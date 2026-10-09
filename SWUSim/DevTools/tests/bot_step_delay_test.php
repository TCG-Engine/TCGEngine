<?php
// The client paces bot steps by the payload's stepDelayMs (Core/jsInclude.js MaybeRunBotControllerStep). Owner request
// 2026-10-09: three Twin Suns room bots resolved in one blink after the human acted — room bots wait 1800ms per step,
// 1v1 Arenabot 250ms, a game with no bots sends 0.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_step_delay_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
require_once './SWUSim/BotController.php';
require_once './Core/BotController.php';

// No bots: nothing to pace.
$build(function ($b) { $b->WithActivePlayer(1); });
$state = BuildBotControllerClientState('SWUSim', $GLOBALS['gameName']);
$check(intval($state['stepDelayMs'] ?? -1) === 0, 'a game with no bot seats sends stepDelayMs 0; got ' . json_encode($state['stepDelayMs'] ?? null));

// 1v1 Arenabot (Bot Practice).
$build(function ($b) { $b->WithActivePlayer(2); });
AddGlobalEffects(1, 'SWU_MODE_BOTPRACTICE');
SetSWUBotPlayers([2]);
$state = BuildBotControllerClientState('SWUSim', $GLOBALS['gameName']);
$check(!empty($state['enabled']), 'fixture: Bot Practice enables the controller');
$check(intval($state['stepDelayMs'] ?? -1) === 250, 'Arenabot paces each bot step by 250ms; got ' . json_encode($state['stepDelayMs'] ?? null));

// A room game with bot seats (Twin Suns "Fill Seat with Bot").
$build(function ($b) { $b->WithActivePlayer(2); });
SWUMarkBotSeats([2 => 'heuristic-normal']);
$state = BuildBotControllerClientState('SWUSim', $GLOBALS['gameName']);
$check(!empty($state['enabled']), 'fixture: room bot seats enable the controller');
$check(SWUGameMode() === '', 'fixture: a room bot game is not a game mode');
$check(intval($state['stepDelayMs'] ?? -1) === 1800, 'room bots pace each bot step by 1800ms; got ' . json_encode($state['stepDelayMs'] ?? null));

bot_test_finish();
