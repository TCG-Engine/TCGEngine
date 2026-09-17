<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_clock_evaluator.php
//
// Inactivity clock stage 1 — the PURE evaluator. No APCu, no gamestate: $presence + $facts + $now in,
// verdict out. Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Core/GamePresence.php';
// The policy file's OTHER functions need the engine's accessors; stub the two used at include time so
// the evaluator can be tested standalone.
if (!function_exists('SeatCountForGame')) { function SeatCountForGame() { return 2; } }
if (!function_exists('GetLiveSeatsArray')) { function GetLiveSeatsArray() { return [1, 2]; } }
require_once __DIR__ . '/../../SWUSim/Custom/InactivityClock.php';

$checks = [];
$base = PresenceDefault();
$facts2p = ['seats' => [1, 2], 'onClock' => [2], 'timeout' => 75, 'active' => true, 'bots' => []];
$facts4p = ['seats' => [1, 2, 3, 4], 'onClock' => [2], 'timeout' => 150, 'active' => true, 'bots' => []];

// ── the clock ──
$p = $base; $p['acted'] = [2 => 1000]; $p['seen'] = [1 => 1050, 2 => 1050]; $p['since'] = 1000;
$r = SWUPresenceEvaluate($p, $facts2p, 1050);
$checks['2P deadline = acted + 75']        = $r['clock'][2]['deadline'] === 1075;
$checks['2P remaining counts down']        = $r['clock'][2]['remaining'] === 25;
$checks['not expired before the deadline'] = $r['clock'][2]['expired'] === false;
$checks['no vote needed yet']              = $r['needVote'] === [];
$checks['seat not on the clock is absent'] = !isset($r['clock'][1]);

$r = SWUPresenceEvaluate($p, $facts2p, 1076);
$checks['expired past the deadline']       = $r['clock'][2]['expired'] === true;
$checks['remaining floors at 0']           = $r['clock'][2]['remaining'] === 0;
$checks['expiry asks for a stall vote']    = $r['needVote'] === [2 => 'stall'];

$p4 = $base; $p4['acted'] = [2 => 1000]; $p4['seen'] = [1 => 1100, 2 => 1100, 3 => 1100, 4 => 1100]; $p4['since'] = 1000;
$r = SWUPresenceEvaluate($p4, $facts4p, 1100);
$checks['multi timeout is 150']            = $r['clock'][2]['deadline'] === 1150;
$checks['4P not expired at 100s']          = $r['clock'][2]['expired'] === false;

// A seat that has never acted starts its clock from 'since' (the last board movement).
$p = $base; $p['acted'] = []; $p['seen'] = [1 => 1000, 2 => 1000]; $p['since'] = 1000;
$checks['no acted[] falls back to since'] = SWUPresenceEvaluate($p, $facts2p, 1050)['clock'][2]['deadline'] === 1075;

// An open vote's extension postpones expiry (the Wait button, stage 2).
$p = $base; $p['acted'] = [2 => 1000]; $p['seen'] = [1 => 1080, 2 => 1080]; $p['since'] = 1000;
$p['votes'] = [2 => ['reason' => 'stall', 'yes' => [], 'until' => 1120, 'waits' => 1]];
$r = SWUPresenceEvaluate($p, $facts2p, 1090);
$checks['wait extension moves the deadline'] = $r['clock'][2]['deadline'] === 1120;
$checks['not expired inside the extension']  = $r['clock'][2]['expired'] === false;
$checks['open vote is not re-requested']     = $r['needVote'] === [];

// ── disconnect ──
$p = $base; $p['acted'] = [2 => 1000]; $p['seen'] = [1 => 1100, 2 => 1060]; $p['since'] = 1000;
$checks['seen 40s ago = gone']        = SWUPresenceEvaluate($p, $facts2p, 1100)['gone'] === [2];
$checks['gone asks for a disconnect vote'] = SWUPresenceEvaluate($p, $facts2p, 1100)['needVote'] === [2 => 'disconnect'];
$p['seen'] = [1 => 1100, 2 => 1075];
$checks['seen 25s ago is still present'] = SWUPresenceEvaluate($p, $facts2p, 1100)['gone'] === [];
$checks['present + expired = stall vote'] = SWUPresenceEvaluate($p, $facts2p, 1100)['needVote'] === [2 => 'stall'];

// Disconnect applies to ANY seat, not just the one on the clock (a quitter waiting their turn).
$p = $base; $p['acted'] = [2 => 1090]; $p['seen'] = [1 => 1100, 2 => 1100, 3 => 1000, 4 => 1100]; $p['since'] = 1090;
$r = SWUPresenceEvaluate($p, $facts4p, 1100);
$checks['off-clock seat can be gone'] = $r['gone'] === [3] && $r['needVote'] === [3 => 'disconnect'];

// Bots are never gone and never on the clock.
$factsBot = ['seats' => [1, 2], 'onClock' => [2], 'timeout' => 75, 'active' => true, 'bots' => [2]];
// ⚠ Seat 1 must be seen AT $now, or seat 1 itself reads as disconnected and the bot assertions below
// fail for the wrong reason (this test caught exactly that on its first run).
$p = $base; $p['acted'] = [2 => 1000]; $p['seen'] = [1 => 1200]; $p['since'] = 1000;
$r = SWUPresenceEvaluate($p, $factsBot, 1200);
$checks['bot never gone']        = $r['gone'] === [];
$checks['bot never on the clock'] = !isset($r['clock'][2]);
$checks['bot never voted on']    = $r['needVote'] === [];

// Inactive clock (goldfish/hotseat/botpractice/replay/game over) reports nothing at all.
$factsOff = ['seats' => [1, 2], 'onClock' => [], 'timeout' => 75, 'active' => false, 'bots' => []];
$p = $base; $p['acted'] = [2 => 1]; $p['seen'] = [1 => 1, 2 => 1]; $p['since'] = 1;
$r = SWUPresenceEvaluate($p, $factsOff, 99999);
$checks['inactive: no clock'] = $r['clock'] === [];
$checks['inactive: no gone']  = $r['gone'] === [];
$checks['inactive: no votes'] = $r['needVote'] === [];

// A brand-new game with no presence data must not instantly expire anyone.
$r = SWUPresenceEvaluate(PresenceDefault(), $facts2p, 1000);
$checks['empty store never expires'] = $r['needVote'] === [] && $r['gone'] === [];

// A seat that has never polled is not "gone" (it may not have opened the page yet — the lobby's rule).
$p = $base; $p['acted'] = [1 => 1000]; $p['seen'] = [1 => 1100]; $p['since'] = 1000;
$checks['never-polled seat is not gone'] = SWUPresenceEvaluate($p, $facts2p, 1100)['gone'] === [];

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
