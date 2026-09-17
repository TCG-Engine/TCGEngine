<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_kick_vote_rules.php
//
// Inactivity kick vote (stage 2) — the PURE rules: who may vote, how many are needed, and vote state.
// Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Core/GamePresence.php';

// Engine stubs, switchable per scenario.
$GLOBALS['T_live'] = [1, 2]; $GLOBALS['T_seats'] = 2; $GLOBALS['T_team'] = false;
if (!function_exists('GetLiveSeatsArray')) { function GetLiveSeatsArray() { return $GLOBALS['T_live']; } }
if (!function_exists('SeatCountForGame')) { function SeatCountForGame() { return $GLOBALS['T_seats']; } }
if (!function_exists('SWUIsTeamGame')) { function SWUIsTeamGame() { return $GLOBALS['T_team']; } }
if (!function_exists('SWUTeamOf')) { function SWUTeamOf(int $s) { return $GLOBALS['T_team'] ? ($s % 2) : $s; } }
require_once __DIR__ . '/../../SWUSim/Custom/InactivityClock.php';

$checks = [];
$mode = function (array $live, int $seats, bool $team) {
    $GLOBALS['T_live'] = $live; $GLOBALS['T_seats'] = $seats; $GLOBALS['T_team'] = $team;
};

// ── who may vote ──
$mode([1, 2], 2, false);
$checks['2P: the opponent votes']        = SWUVoterSeatsFor(2) === [1];
$checks['2P: one Yes is enough']         = SWUVotesNeededFor(2) === 1;

$mode([1, 2, 3, 4], 4, false);
$checks['TwinSuns 4: other 3 may vote']  = SWUVoterSeatsFor(2) === [1, 3, 4];
$checks['TwinSuns 4: 2 of 3 needed']     = SWUVotesNeededFor(2) === 2;

$mode([1, 2, 3], 4, false);   // a 4-seat game that lost a seat
$checks['TwinSuns 3: other 2 may vote']  = SWUVoterSeatsFor(2) === [1, 3];
$checks['TwinSuns 3: unanimous (2)']     = SWUVotesNeededFor(2) === 2;

$mode([1, 2, 3, 4], 4, true); // Team Suns: 1+3 red, 2+4 blue
$checks['TeamSuns: only the enemy team'] = SWUVoterSeatsFor(2) === [1, 3];
$checks['TeamSuns: unanimous (2)']       = SWUVotesNeededFor(2) === 2;
$checks['TeamSuns: teammate not asked']  = !in_array(4, SWUVoterSeatsFor(2), true);

// ── is the vote carried ──
$mode([1, 2, 3, 4], 4, false);
$checks['no votes: not carried']     = SWUVoteIsCarried(['yes' => []], 2) === false;
$checks['1 of 3: not carried']       = SWUVoteIsCarried(['yes' => [1 => 10]], 2) === false;
$checks['2 of 3: carried']           = SWUVoteIsCarried(['yes' => [1 => 10, 4 => 11]], 2) === true;
$checks['target cannot vote itself'] = SWUVoteIsCarried(['yes' => [2 => 10, 1 => 10]], 2) === false;
$mode([1, 2, 3], 4, false);
$checks['dead seat Yes is discarded'] = SWUVoteIsCarried(['yes' => [1 => 10, 4 => 10]], 2) === false;
$mode([1, 2], 2, false);
$checks['2P: single Yes carries']    = SWUVoteIsCarried(['yes' => [1 => 10]], 2) === true;

// ── vote state (needs APCu; skipped under CLI, covered over HTTP) ──
if (PresenceApcuReady()) {
    $g = 'votetest' . getmypid();
    apcu_delete(PresenceCacheKey($g));
    $checks['open writes a vote']        = PresenceOpenVote($g, 2, 'stall', 1000, 75) === true;
    $v = PresenceRead($g)['votes'][2];
    $checks['vote carries its reason']   = $v['reason'] === 'stall' && $v['yes'] === [] && $v['waits'] === 0;
    $checks['open is idempotent']        = PresenceOpenVote($g, 2, 'disconnect', 1005, 75) === false;
    $checks['reason not overwritten']    = PresenceRead($g)['votes'][2]['reason'] === 'stall';
    $checks['record a vote']             = PresenceRecordVote($g, 2, 1, 1010) === true;
    $checks['vote recorded']             = array_keys(PresenceRead($g)['votes'][2]['yes']) === [1];
    $checks['re-vote is idempotent']     = PresenceRecordVote($g, 2, 1, 1011) === true
                                           && count(PresenceRead($g)['votes'][2]['yes']) === 1;
    $checks['extend moves until']        = PresenceExtendVote($g, 2, 1020, 20) === true
                                           && PresenceRead($g)['votes'][2]['until'] === 1040;
    $checks['extend counts waits']       = PresenceRead($g)['votes'][2]['waits'] === 1;
    $checks['extend keeps sticky votes'] = array_keys(PresenceRead($g)['votes'][2]['yes']) === [1];
    $checks['clear removes it']          = PresenceClearVote($g, 2) === true && PresenceRead($g)['votes'] === [];
    $checks['vote on a second target']   = PresenceOpenVote($g, 3, 'disconnect', 1100, 150) === true
                                           && PresenceOpenVote($g, 4, 'stall', 1100, 150) === true
                                           && count(PresenceRead($g)['votes']) === 2;
    $checks['voter cannot be the target'] = PresenceRecordVote($g, 3, 3, 1110) === false;
    apcu_delete(PresenceCacheKey($g));
}

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo 'apcu=' . (PresenceApcuReady() ? 'yes' : 'no') . "\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
