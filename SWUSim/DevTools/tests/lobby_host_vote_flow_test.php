<?php
// End-to-end Kick Host vote — timer arming, vote casting, the 2-of-2 / 2-of-3 threshold, and host
// removal, all through the real endpoints (VoteKickHost.php + PollLobbyUpdates.php's arming).
// Rules-only coverage: SWUSim/DevTools/tests/lobby_host_vote_test.php.
//
// The 120s arm delay is NOT slept for real — this test runs in the web SAPI (same APCu segment as
// the lobby endpoints), so after the first poll arms the timer for real, the cached lobby's
// hostVoteArmedAt is backdated directly. That is setup, not the thing under test: the PRODUCTION
// arming path (SWUHostVoteArm, wired into PollLobbyUpdates.php) runs for real on the poll below, and
// every vote/tally/removal call after the backdate goes through the real HTTP endpoints exactly as
// lobby_room_flow_test.php does for KickSeat.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php SWUSim/DevTools/tests/lobby_host_vote_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/Player.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';

function hit($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_FOLLOWLOCATION => false]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 300)];
}

$jarHost = tempnam(sys_get_temp_dir(), 'hv');
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jarHost);

$deck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));

$host = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarHost);
check(!empty($host['success']), 'created a twinsuns room: ' . ($host['message'] ?? ''));
if (empty($host['success'])) { echo "FAIL\n"; exit; }
$lobby = $host['lobbyID']; $invite = $host['inviteCode']; $k1 = $host['authKey'];

$jar2 = tempnam(sys_get_temp_dir(), 'hv');
$jar3 = tempnam(sys_get_temp_dir(), 'hv');
$p2 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar2);
$p3 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar3);
check(!empty($p2['success']) && !empty($p3['success']), 'two more seats joined by invite');
$k2 = $p2['authKey']; $k3 = $p3['authKey'];

$poll = function ($key, $jar) use ($L, $lobby) {
    return hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $key], $jar);
};
$rowOf = function ($r, $pid) { foreach (($r['roster'] ?? []) as $s) if ($s['playerID'] === $pid) return $s; return null; };
$ids   = function ($r) { $o = []; foreach (($r['roster'] ?? []) as $s) $o[] = $s['playerID']; sort($o); return $o; };

// ── The timer arms on the first poll after all three are seated, but the window is not open yet ──
$r = $poll($k1, $jarHost);
check(($r['hostVote']['open'] ?? null) === false, 'the vote window is closed the moment the room fills');
check(($r['hostVote']['needed'] ?? null) === 2, 'a 3-seat room already reports "needs 2"');

// Backdate the arming clock directly in APCu instead of sleeping 120s in a regression run.
$cached = apcu_fetch($lobby);
check(is_object($cached) && intval($cached->hostVoteArmedAt ?? 0) > 0, 'precondition: the poll armed the timer');
$cached->hostVoteArmedAt -= 130;
apcu_store($lobby, $cached, 900);

$r = $poll($k1, $jarHost);
check(($r['hostVote']['open'] ?? null) === true, 'the window opens once the arm delay has elapsed');
$hostRow = null; foreach ($r['roster'] as $row) if (!empty($row['isHost'])) $hostRow = $row;
check($hostRow !== null, 'the roster names a host');
$hostID = $hostRow['playerID'];

$r2 = $poll($k2, $jar2);
check(($r2['hostVote']['canVote'] ?? null) === true, 'a non-host seat may vote once the window is open');

// ── The host cannot vote to remove themselves ────────────────────────────────────────────────────
$selfVote = hit($L . 'VoteKickHost.php', ['lobbyID' => $lobby, 'authKey' => $k1], $jarHost);
check(empty($selfVote['success']), 'the host cannot vote to remove themselves: ' . ($selfVote['message'] ?? ''));

// ── One Yes in a 3-seat room is not enough (unanimous 2/2 required) ─────────────────────────────
$v2 = hit($L . 'VoteKickHost.php', ['lobbyID' => $lobby, 'authKey' => $k2], $jar2);
check(!empty($v2['success']) && empty($v2['carried']), 'the first Yes vote is recorded but does not carry');

$r = $poll($k2, $jar2);
check(($r['hostVote']['yesCount'] ?? null) === 1, 'the poll reflects the one recorded Yes');
check(($r['hostVote']['youVoted'] ?? null) === true, 'the voter sees their own Yes reflected back');
check($ids($r) === [1, 2, 3], 'the host has NOT been removed yet — one vote of two');

// A duplicate vote from the same seat changes nothing.
$dupe = hit($L . 'VoteKickHost.php', ['lobbyID' => $lobby, 'authKey' => $k2], $jar2);
check(!empty($dupe['success']) && empty($dupe['carried']), 'a duplicate Yes is accepted but still does not carry');

// ── The second Yes carries the vote and removes the host ────────────────────────────────────────
$v3 = hit($L . 'VoteKickHost.php', ['lobbyID' => $lobby, 'authKey' => $k3], $jar3);
check(!empty($v3['success']) && !empty($v3['carried']), 'the second Yes carries the vote: ' . ($v3['message'] ?? ''));

$r = $poll($k2, $jar2);
check(!in_array($hostID, $ids($r), true), 'the former host is gone from the roster');
check($r['numPlayers'] === 2, 'numPlayers reflects the removal');

// The removed host's browser is told, the same way KickSeat tells a kicked seat.
$hostSees = $poll($k1, $jarHost);
check(!empty($hostSees['removed']), 'the removed host is told it was removed');

// ── Seats shift up and the new host is P1's seat, with NO new client code ───────────────────────
// The waiting room draws "Seat N" from the roster's PLAYER-ID SORT ORDER, positionally
// (SharedUI/Render/WaitingRoom.php: `entries = roster.slice().sort(by playerID)`), so removing the
// lowest playerID (the host) makes the next-lowest render at Seat 1 for free. isHost is likewise
// just "this row's playerID === hostPlayerID", and SWUMigrateHostIfNeeded already hands
// hostPlayerID to the lowest REMAINING id. Assert both halves of that chain directly.
$after = apcu_fetch($lobby);
check(is_object($after), 'lobby still cached after the removal');
$remainingIDs = [];
foreach (($after->players ?? []) as $p) $remainingIDs[] = intval($p->getPlayerID());
sort($remainingIDs);
check(count($remainingIDs) === 2, 'two seats remain');
check(intval($after->hostPlayerID ?? 0) === $remainingIDs[0],
      'the LOWEST remaining playerID is the new host — the tile it renders at is Seat 1');

$newHostRow = $rowOf($poll($k2, $jar2), $remainingIDs[0]);
check(($newHostRow['isHost'] ?? null) === true, 'the roster marks the lowest remaining seat as host');

// ── The kicked host may rejoin, like anyone else ─────────────────────────────────────────────────
$back = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarHost);
check(!empty($back['success']), 'the removed host can rejoin: ' . ($back['message'] ?? ''));
$r = $poll($k2, $jar2);
check($r['numPlayers'] === 3, 'the rejoined former host is seated again');
$rejoinedRow = $rowOf($r, $back['playerID']);
check($rejoinedRow !== null && empty($rejoinedRow['isHost']), 'the rejoined former host is an ORDINARY seat, not host again');

echo $FAILS === 0 ? "PASS\n" : "FAIL: $FAILS check(s)\n";
