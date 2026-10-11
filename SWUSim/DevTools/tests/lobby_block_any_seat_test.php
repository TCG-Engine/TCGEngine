<?php
// A block must keep the two players out of the same lobby no matter WHICH seat the other one holds.
// Owner ruling, 2026-09-26: refuse the join when ANY seated player has a block with the joiner; the
// refused player falls through to the generic "invalid/expired/full" response and ends up creating a
// room of their own for other people to join.
//
// The bug this pins: both JoinQueue.php call sites tested the joiner against SWULobbyHostUserId()
// alone — players[0], the creator. That is complete at two seats and silently incomplete at three or
// four. Reported scenario: A blocks B, C hosts a Twin Suns room, A joins it, B queues, and because
// B has no block with the HOST the queue seats B right next to A.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/lobby_block_any_seat_test.php
chdir(dirname(__DIR__, 3));
require_once './Database/ConnectionManager.php';
require_once './Database/functions.inc.php';
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/JoinQueue_blocklib.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$A = 990040; $B = 990041; $C = 990042; $D = 990043;
$all = "$A,$B,$C,$D";
$conn = GetLocalMySQLConnection();
$conn->query("DELETE FROM blocklist WHERE blockingPlayer IN ($all) OR blockedPlayer IN ($all)");

// A real lobby, built from real Player objects: getUserId() is the shape production reads, and it
// returns NULL for a guest or a bot seat.
$lobbyOf = function (array $userIds) {
    $lobby = new stdClass();
    $lobby->players = [];
    $seat = 1;
    foreach ($userIds as $u) $lobby->players[] = new Player($seat++, '', '', $u);
    return $lobby;
};

AddBlock($A, $B);   // A blocked B. AreUsersBlocked is symmetric, so direction must not matter.

// ── The reported scenario ───────────────────────────────────────────────────────────────────────
$check(SWUJoinBlockedFromLobby($B, $lobbyOf([$C, $A])) === true,
    "B is refused C's room because A (seat 2, not the host) blocked B");
$check(SWUJoinBlockedFromLobby($A, $lobbyOf([$C, $B])) === true,
    "A is refused C's room because B sits at seat 2 (block applies in both directions)");
$check(SWUJoinBlockedFromLobby($B, $lobbyOf([$C, $D, $A])) === true,
    "a seat-3 blocker refuses the join too (Twin Suns fills to 3-4)");

// ── The old host-only behaviour must survive ────────────────────────────────────────────────────
$check(SWUJoinBlockedFromLobby($B, $lobbyOf([$A])) === true,
    "the HOST's own block still refuses the join");
$check(SWUJoinBlockedFromLobby($B, $lobbyOf([$C, $D])) === false,
    "a room of unrelated players is joinable");
$check(SWUJoinBlockedFromLobby($B, $lobbyOf([])) === false,
    "an empty lobby is joinable");

// ── Seats with no account ───────────────────────────────────────────────────────────────────────
$check(SWUJoinBlockedFromLobby($B, $lobbyOf([null, $A])) === true,
    "a guest/bot host does not hide a blocker sitting behind it");
$check(SWUJoinBlockedFromLobby($B, $lobbyOf([null, null])) === false,
    "a room of guests is joinable");
$check(SWUJoinBlockedFromLobby(0, $lobbyOf([$A])) === false,
    "an anonymous joiner is never blocked");
// ⚠ DELIBERATELY UNPINNABLE, and recorded as such rather than dressed up as a guard: this holds
// because AddBlock refuses a self-block, so no blocklist row can ever match a player against
// themselves. No mutation of AreUsersBlockedAny can turn it red (an explicit self-exclusion was
// tried, measured decorative, and deleted). It is here to state the property, not to prove it.
$check(SWUJoinBlockedFromLobby($A, $lobbyOf([$A])) === false,
    "a player is never blocked from a room they already sit in");

// ── Structural: production must ASK the new question ────────────────────────────────────────────
// The helper being right is worth nothing if JoinQueue.php still passes it a single host id. Both
// join paths (public queue scan + private invite) are checked, and the old host-only call must be
// gone from BOTH — that is the defect, and a behavioural fixture cannot reach this file.
$join = file_get_contents('./APIs/Lobbies/JoinQueue.php');
$check(substr_count($join, 'SWUJoinBlockedFromLobby(') === 2,
    'both JoinQueue.php join paths ask the any-seat question (found ' .
    substr_count($join, 'SWUJoinBlockedFromLobby(') . ')');
$check(strpos($join, 'SWUJoinBlocked($joiningUserId, SWULobbyHostUserId(') === false,
    'no JoinQueue.php join path tests the host alone any more');

$conn->query("DELETE FROM blocklist WHERE blockingPlayer IN ($all) OR blockedPlayer IN ($all)");
mysqli_close($conn);

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
