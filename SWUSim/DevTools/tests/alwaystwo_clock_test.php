<?php
// 1v1 Always Two — the inactivity clock is per SEAT (person), not per board. docs/superpowers/specs/2026-11-20-swusim-always-two-design.md.
// One person plays boards 1+3 (seat 1), the other 2+4 (seat 2), and nobody ever polls as board 3 or 4. So the clock's facts list the
// two SEATS, and a decision owed by board 3 runs SEAT 1's clock. Unmapped, boards 3/4 would read as silent seats and could be voted
// out — and a kick concedes the game for the team. Control: Team Suns keeps one seat per board.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/alwaystwo_clock_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './Core/GamePresence.php';
global $gameName;
PresenceSetClockEnabledInDev($gameName, true);   // dev keeps the clock off unless asked

$four = function (bool $shared) use ($build) {
    return $build(function ($b) use ($shared) {
        $b->WithSeatOrder('1234'); $b->WithLiveSeats('1234');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_TEAMS');
        if ($shared) { $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_ALWAYSTWO'); $b->WithGlobalEffectForPlayer(1, 'SWU_ALWAYSTWO_SHARED_SEAT'); }
    });
};

echo "SeatBoardMap:\n";
$four(true);
$check(SWUBoardsOfSeat(1) === [1, 3] && SWUBoardsOfSeat(2) === [2, 4], 'seat 1 plays boards 1+3, seat 2 boards 2+4');
$check(SWUSeatOfBoard(3) === 1 && SWUSeatOfBoard(4) === 2 && SWUSeatOfBoard(1) === 1, 'board 3 belongs to seat 1, board 4 to seat 2');
$check(SWUViewerControlsBoard(1, 3) && !SWUViewerControlsBoard(1, 4) && !SWUViewerControlsBoard(2, 3), 'a viewer controls exactly its own boards');

echo "ClockIsPerSeat:\n";
$four(true);
DecisionQueueController::AddDecision(3, 'YESNO', '-', 1, tooltip: 'Test_prompt');
$check(SWUSeatsOnTheClock() === [1], 'a decision owed by board 3 runs seat 1\'s clock; got ' . json_encode(SWUSeatsOnTheClock()));
$facts = SWUClockFacts();
$check(($facts['seats'] ?? null) === [1, 2], 'the clock knows two seats, not four boards; got ' . json_encode($facts['seats'] ?? null));
$check(SWUVoterSeatsFor(1, $facts) === [2], 'only the other person votes on seat 1; got ' . json_encode(SWUVoterSeatsFor(1, $facts)));
$four(true);
SetTurnPlayer(4);
$check(SWUSeatsOnTheClock() === [2], 'board 4 holding the turn runs seat 2\'s clock; got ' . json_encode(SWUSeatsOnTheClock()));

echo "ControlTeamSuns:\n";
$four(false);
DecisionQueueController::AddDecision(3, 'YESNO', '-', 1, tooltip: 'Test_prompt');
$check(SWUSeatsOnTheClock() === [3], 'Team Suns: seat 3 owes its own decision; got ' . json_encode(SWUSeatsOnTheClock()));
$check((SWUClockFacts()['seats'] ?? null) === [1, 2, 3, 4], 'Team Suns: four seats');
$check(SWUBoardsOfSeat(3) === [3] && SWUSeatOfBoard(3) === 3, 'Team Suns: one board per seat');

PresenceSetClockEnabledInDev($gameName, false);
$f = $GLOBALS['BOT_TEST_FAILS'];
echo $f === 0 ? "ALL PASS\n" : "$f FAILED\n";
exit($f === 0 ? 0 : 1);
