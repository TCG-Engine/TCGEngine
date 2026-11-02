<?php
// A lookahead rule's plan survives the request boundary (bug report P-9, Petranaki game 1461: "bot dry played Pre Vizsla").
// Rule 'control-wipe' plays ASH_053 Pre Vizsla because its line defeats the bot's own Death Trooper + Door Technician (two
// Mandalorian tokens and a 2 heal); the answer to the follow-up prompt is that line's PLAN. A live game runs every bot step in its
// own request, so the plan has to be carried to the next step — in memory only, the next request answered with the fallback ('-')
// and the card defeated nothing. Self-play keeps one process, which is why no sweep ever saw it.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_plan_persist_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotController.php';
if (!function_exists('WriteCache')) { function WriteCache(...$a) {} }
if (!function_exists('GamestateUpdated')) { function GamestateUpdated(...$a) {} }
global $gameName;
$dir = './SWUSim/Games/' . $gameName;
@mkdir($dir, 0777, true);

// Game 1461 round 6, the bot's (seat 2) action after Anakin hit its base — the board the report was filed from.
$board = function () {
    $b = new GameStateBuilder(); CommonSetup($b, 'ngw', 'ngw', [], []);
    $b->WithActivePlayer(2); $b->WithGamePhase('MAIN'); $b->WithCurrentRoundBeing(6); $b->WithInitiativePlayerBeing(1);
    $b->MyLeader('HMW_016', true); $b->MyBase('HMW_019', 9);
    $b->TheirLeader('LAW_008', true); $b->TheirBase('ASH_019', 5);
    $b->FillResourcesForPlayer(1, 'SOR_095', 7, false); $b->FillResourcesForPlayer(2, 'SOR_095', 8);
    $b->WithGroundUnitForPlayer(1, 'LOF_070', false);
    $b->WithGroundUnitForPlayer(2, 'SOR_033'); $b->WithGroundUnitForPlayer(2, 'LAW_097');
    foreach (['ASH_053', 'JTL_043', 'HMW_202', 'ASH_052'] as $c) $b->WithCardInHandForPlayer(1, $c);
    foreach (['ASH_053', 'LAW_133', 'ASH_052'] as $c) $b->WithCardInHandForPlayer(2, $c);
    foreach (['ASH_194', 'LAW_096', 'HMW_202'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
    $g = new GameTestAdapter(); $g->loadState($b);
    ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
    SWUMarkBotSeats([2 => 'heuristic-softcontrol']);
    unset($GLOBALS['SWUBotPlan']);
    array_map('unlink', glob($GLOBALS['dir'] . '/BotPlan_*') ?: []);
};
$ground = fn(int $s) => array_values(array_map(fn($o) => strval($o->CardID), array_filter(GetGroundArena($s), fn($o) => $o && empty($o->removed))));
$step = fn() => ProcessBotControllerStep(1, 'SWUSim', $GLOBALS['gameName']);
$vizslaPlayed = fn() => !in_array('ASH_053', array_map(fn($o) => strval($o->CardID), array_filter(GetHand(2), fn($o) => $o && empty($o->removed))), true);

// ── one process (self-play): the plan is in memory ──
$board();
$step();
$check($vizslaPlayed() && !empty($GLOBALS['SWUBotPlan'][2] ?? []), 'fixture: the bot plays Pre Vizsla with a plan for its prompt (rule control-wipe)');
$step();
$g = $ground(2);
$check(!in_array('SOR_033', $g, true) && !in_array('LAW_097', $g, true), 'one process: the plan defeats Death Trooper + Door Technician; got ' . json_encode($g));

// ── a new request per step (live play): the plan is carried over ──
$board();
$step();
$check($vizslaPlayed(), 'live: the bot plays Pre Vizsla');
unset($GLOBALS['SWUBotPlan']);                                                         // the next poll is a new request
$step();
$g = $ground(2);
$check(!in_array('SOR_033', $g, true) && !in_array('LAW_097', $g, true), 'live: the next request follows the plan — Pre Vizsla defeats two units; got ' . json_encode($g));
$check(empty(glob($dir . '/BotPlan_*') ?: []), 'live: a finished plan leaves no file behind');

// ── the board moved between the two requests: the stored plan is not trusted ──
$board();
$step();
unset($GLOBALS['SWUBotPlan']);
GetBase(1)[0]->Damage = intval(GetBase(1)[0]->Damage) + 1;                             // something happened that the plan never saw,
RegressionFlushCurrentGamestate('SWUSim');                                              // saved the way another request's action is
$check(!empty(glob($dir . '/BotPlan_*') ?: []), 'stale: fixture — the plan was stored after the play');
$step();
$g = $ground(2);
$check(in_array('SOR_033', $g, true) && in_array('LAW_097', $g, true), 'stale: a plan stored for another board is dropped (the fallback answers); got ' . json_encode($g));

array_map('unlink', glob($dir . '/*') ?: []); @rmdir($dir);
bot_test_finish();
