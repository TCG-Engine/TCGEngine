<?php
// DecisionQueueController::AllQueuesEmpty() must see EVERY seat's queue, not seats 1..(number alive).
//
// FOUND 2026-10-01 by the Twin Suns self-play harness (DevTools/SWUSimTwinSunsSelfPlay.php, 4 seats, seed ts-14):
// with seats 1 and 2 eliminated, SeatCount() answered "2 live seats" and AllQueuesEmpty() checked seats 1..2 — both
// dead and empty — so EvaluateTransition() never saw seats 3 and 4's pending regroup resource prompts. AutoAdvance
// then ran RGS -> DRAW -> RES -> READY -> RGS forever, stacking another resource prompt on every lap (the hang).
// The same gap hides seat 4 whenever ANY lower seat is gone — Team Suns plays on after eliminations, so with seat 2
// out (live 1,3,4) seat 4's regroup prompts were skipped by the phase engine in live games.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/dq_all_queues_empty_seat_gap_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';

$board = function (string $live, int $active) use ($build) {
    $build(function ($b) use ($live, $active) {
        CommonSetupFarSeat($b, 3, 'grw');
        CommonSetupFarSeat($b, 4, 'brk');
        $b->WithSeatOrder('1234')->WithLiveSeats($live);
        $b->WithActivePlayer($active);
    });
};

$board('1234', 1);
$dq = new DecisionQueueController();
$check($dq->AllQueuesEmpty(), 'control: an idle 4-seat board has no pending decision');

// Team Suns shape: seat 2 eliminated, seats 1/3/4 play on. Seat 4 owes a prompt.
$board('134', 1);
DecisionQueueController::AddDecision(4, 'MZMAYCHOOSE', 'myHand', 1, tooltip: 'Resource_up_to_1_card');
$check(!(new DecisionQueueController())->AllQueuesEmpty(), 'live 1,3,4: seat 4\'s pending prompt is seen');
$check(EvaluateTransition('AUTO') === 'PENDING_DECISION', 'live 1,3,4: the phase engine waits for seat 4 (got ' . EvaluateTransition('AUTO') . ')');

// The harness's shape: seats 1 and 2 gone, seat 3 owes a prompt.
$board('34', 3);
DecisionQueueController::AddDecision(3, 'MZMAYCHOOSE', 'myHand', 1, tooltip: 'Resource_up_to_1_card');
$check(!(new DecisionQueueController())->AllQueuesEmpty(), 'live 3,4: seat 3\'s pending prompt is seen');

// Control for the seat that WAS already covered, so the fix is not just "always false".
$board('134', 1);
DecisionQueueController::AddDecision(1, 'MZMAYCHOOSE', 'myHand', 1, tooltip: 'Resource_up_to_1_card');
$check(!(new DecisionQueueController())->AllQueuesEmpty(), 'control: seat 1\'s prompt was always seen');
$board('134', 1);
$check((new DecisionQueueController())->AllQueuesEmpty(), 'control: with nothing queued, live 1,3,4 reads empty');

bot_test_finish();
