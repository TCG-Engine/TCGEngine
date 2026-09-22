<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_clock_starts_when_on_clock.php
//
// Player report (Twin Suns, 2026-09-21): "if P3 triggers the inactivity timer, their actions or P4's actions
// don't reset it". Root cause: SWUPresenceEvaluate started a seat's countdown from acted[seat] — that seat's OWN
// last action — so every second spent WAITING for the other players counted against it. At four seats a seat
// waits through three other turns, so it could come on the clock already expired, and the next seat after it
// likewise. A seat's clock must start when its wait began: the later of its own last action and `since`
// (the last clock-resetting action by anyone, i.e. when the game started waiting on the current seats).
// The spec's own rule: "A player waiting on someone else is never on the clock."
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Core/GamePresence.php';
if (!function_exists('SeatCountForGame')) { function SeatCountForGame() { return 4; } }
if (!function_exists('GetLiveSeatsArray')) { function GetLiveSeatsArray() { return [1, 2, 3, 4]; } }
require_once __DIR__ . '/../../SWUSim/Custom/InactivityClock.php';

$checks = [];
$base = PresenceDefault();

// 4 seats, 150s. P4 last acted at 1000. Then P1, P2 and P3 took their turns; P3's action at 1300 handed the turn
// to P4 (since = 1300). At 1310 P4 has been on the clock for 10 seconds.
$facts4p = ['seats' => [1, 2, 3, 4], 'onClock' => [4], 'timeout' => 150, 'active' => true, 'bots' => []];
$p = $base;
$p['acted'] = [1 => 1100, 2 => 1200, 3 => 1300, 4 => 1000];
$p['seen']  = [1 => 1310, 2 => 1310, 3 => 1310, 4 => 1310];
$p['since'] = 1300;
$r = SWUPresenceEvaluate($p, $facts4p, 1310);
$checks['4P: the next seat starts a FULL timeout when its turn begins (deadline 1450)'] = ($r['clock'][4]['deadline'] ?? null) === 1450;
$checks['4P: ...so it is not expired 10s into its turn']                               = ($r['clock'][4]['expired'] ?? null) === false;
$checks['4P: ...and no stall vote opens against it']                                   = !isset($r['needVote'][4]);

// The same at 2 seats, 75s: P2 last acted at 1000, P1 took 60s and acted at 1060. 40s into P2's turn (1100) P2 has
// 35s left, not "expired 25s ago".
$facts2p = ['seats' => [1, 2], 'onClock' => [2], 'timeout' => 75, 'active' => true, 'bots' => []];
$p = $base;
$p['acted'] = [1 => 1060, 2 => 1000];
$p['seen']  = [1 => 1100, 2 => 1100];
$p['since'] = 1060;
$r = SWUPresenceEvaluate($p, $facts2p, 1100);
$checks['2P: the opponent\'s thinking time does not count against you (deadline 1135)'] = ($r['clock'][2]['deadline'] ?? null) === 1135;
$checks['2P: ...not expired']                                                             = ($r['clock'][2]['expired'] ?? null) === false;

// Unchanged: a seat that is STILL deciding keeps its own start. P2 acted at 1000 (a stamp; since = 1000) and is
// still on the clock (e.g. it declared an attack, which does not stamp). Its deadline stays 1075.
$p = $base;
$p['acted'] = [2 => 1000];
$p['seen']  = [1 => 1100, 2 => 1100];
$p['since'] = 1000;
$r = SWUPresenceEvaluate($p, $facts2p, 1100);
$checks['a seat still deciding keeps its start (deadline 1075, expired at 1100)'] = ($r['clock'][2]['deadline'] ?? null) === 1075 && $r['clock'][2]['expired'] === true;

$fails = 0;
foreach ($checks as $name => $okv) { echo ($okv ? 'PASS' : 'FAIL') . ": $name\n"; if (!$okv) $fails++; }
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails ? 1 : 0);
