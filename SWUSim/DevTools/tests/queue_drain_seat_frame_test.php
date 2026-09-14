<?php
// ProcessGoldfishAutomation() — the static-queue drain EngineActionRunner runs after every action — must
// drain each seat's queue IN THAT SEAT'S FRAME. It called ExecuteStaticMethods($s) for every seat while the
// global $playerID stayed on whoever sent the request. ExecuteStaticMethods re-checks an MZCHOOSE at the
// head of the queue with MZCountChoices(), which resolves "my…" zones against $playerID — so another seat's
// "mySpaceArena-0&mySpaceArena-1" was counted on the REQUESTER's side, came to 0 when the requester had no
// space units, and was auto-PASSed; the continuation behind it skipped on the PASS.
//
// FOUND 2026-09-14 in a live Bot Practice game (183167): the browser sends the bot's step as the HUMAN's
// seat, so after the bot answered "Pilot" for its Darth Vader (JTL_006) deploy, the drain threw away its
// "Choose a Vehicle" prompt; the leader stayed undeployed and the bot re-deployed forever ("P2 chose Pilot"
// hundreds of times). Not bot-specific: any seat's own-zone prompt drained during another seat's request.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/queue_drain_seat_frame_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$head = function (int $p) { foreach (GetDecisionQueue($p) as $e) if ($e !== null && empty($e->removed)) return [strval($e->Type), strval($e->Param)]; return null; };

// A) The live case. Seat 2 (active) deploys Darth Vader with two unpiloted Vehicles; seat 1 has no space unit.
$build(function ($b) {
    $b->WithActivePlayer(2); $b->TheirLeader('JTL_006'); $b->FillResourcesForPlayer(2, 'SOR_095', 6);
    $b->WithSpaceUnitForPlayer(2, 'JTL_212', true); $b->WithSpaceUnitForPlayer(2, 'JTL_T01', true);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
});
$act(2, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
$check($head(2) === ['OPTIONCHOOSE', 'Unit&Pilot'], 'fixture: the deploy asks Unit or Pilot; got ' . json_encode($head(2)));
$act(2, 100, 'Pilot');
$check(($head(2)[0] ?? '') === 'MZCHOOSE' && ($head(2)[1] ?? '') === 'mySpaceArena-0&mySpaceArena-1', 'fixture: then asks which Vehicle; got ' . json_encode($head(2)));
// The post-action drain, run from the OTHER seat's request (the human's, in Bot Practice).
$playerID = 1;
ProcessGoldfishAutomation();
$check(($head(2)[0] ?? '') === 'MZCHOOSE', 'the drain keeps seat 2\'s Vehicle prompt when seat 1 sent the request; got ' . json_encode($head(2)));
$act(2, 100, 'mySpaceArena-0');
$vader = GetLeader(2)[0];
$check(!empty($vader->Deployed) && strval($vader->Deployed) !== 'false', 'answering it attaches Vader as a pilot (the deploy completes)');

// B) The mirror: seat 1's own-zone prompt, drained during seat 2's request, where seat 2 has no ground unit.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'SOR_164', true); });
$playerID = 1;
DecisionQueueController::AddDecision(1, 'MZCHOOSE', 'myGroundArena-0&myGroundArena-1', 1, tooltip: 'Choose_a_friendly_unit');
$playerID = 2;
ProcessGoldfishAutomation();
$check(($head(1)[0] ?? '') === 'MZCHOOSE', 'the drain keeps seat 1\'s own-zone prompt during seat 2\'s request; got ' . json_encode($head(1)));
$check($playerID === 2, 'the drain restores the requesting seat\'s frame afterwards');

bot_test_finish();
