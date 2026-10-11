<?php
// Regression (2026-10-03, Twin Suns log review): the game log names WHO CHOSE a discard from the seat whose queue is
// running the discarding CUSTOM handler (SWULogHandlerSeat). Core/DecisionQueueController brackets every CUSTOM handler
// with GameBeforeCustomHandler (push) and GameAfterCustomHandler (pop, in a finally). If the pop is lost, a seat that
// answered an earlier pick stays "current", and a later discard nobody chose is credited to it — "P2 discarded X" for a
// random discard P1's card made. Every schema path discards INSIDE some handler, whose own push masks a stale entry
// below it, so no Tests/Cases section can see the pop; this drives real CUSTOM decisions through the controller.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d display_errors=0 SWUSim/DevTools/tests/gamelog_handler_seat_test.php
chdir('/var/www/html/TCGEngine');
require_once 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
InitializeGamestate();
global $customDQHandlers;

$fails = 0;
$check = function (bool $ok, string $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$seen = [];

$customDQHandlers['TEST_SEAT_PROBE'] = function ($player, $parts, $lastDecision) use (&$seen) {
    $seen[] = SWULogHandlerSeat();
};
// Drains ANOTHER seat's queue inline from inside a handler — the nesting a stack must survive.
$customDQHandlers['TEST_SEAT_NEST'] = function ($player, $parts, $lastDecision) use (&$seen) {
    $seen[] = SWULogHandlerSeat();
    DecisionQueueController::AddDecision(3, 'CUSTOM', 'TEST_SEAT_PROBE', 1);
    (new DecisionQueueController())->ExecuteStaticMethods(3);
    $seen[] = SWULogHandlerSeat();
};
$customDQHandlers['TEST_SEAT_THROW'] = function ($player, $parts, $lastDecision) {
    throw new RuntimeException('handler failed');
};

$check(SWULogHandlerSeat() === 0, 'no handler running: no chooser');

$seen = [];
DecisionQueueController::AddDecision(2, 'CUSTOM', 'TEST_SEAT_PROBE', 1);
(new DecisionQueueController())->ExecuteStaticMethods(2);
$check($seen === [2], 'inside a handler on seat 2\'s queue, the chooser is seat 2 (' . json_encode($seen) . ')');
$check(SWULogHandlerSeat() === 0, 'after that handler returns, no chooser is left behind (got ' . SWULogHandlerSeat() . ')');

$seen = [];
DecisionQueueController::AddDecision(2, 'CUSTOM', 'TEST_SEAT_NEST', 1);
(new DecisionQueueController())->ExecuteStaticMethods(2);
$check($seen === [2, 3, 2], 'a nested drain of seat 3 inside seat 2\'s handler reads 3, then 2 again (' . json_encode($seen) . ')');
$check(SWULogHandlerSeat() === 0, 'after the nested drain, no chooser is left behind (got ' . SWULogHandlerSeat() . ')');

DecisionQueueController::AddDecision(4, 'CUSTOM', 'TEST_SEAT_THROW', 1);
try { (new DecisionQueueController())->ExecuteStaticMethods(4); } catch (RuntimeException $e) {}
$check(SWULogHandlerSeat() === 0, 'a handler that throws leaves no chooser behind (got ' . SWULogHandlerSeat() . ')');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
