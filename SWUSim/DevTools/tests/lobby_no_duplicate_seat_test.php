<?php
// ONE HUMAN, ONE SEAT. A lobby must never seat the same person twice.
//
// Three player reports, 2026-09-26, all the same defect:
//   1. "I created a game using a Tarkin/Client deck but left after no one showed. I came back about
//      10 mins later with a different deck but the game launched with me as both P1 and P3 using
//      both decks."
//   2. "twice tonight during twin suns, a guest player signed in so they could chat and it added
//      them twice in the game."
//   3. "1 player was somehow in the game twice."
//
// Root cause: JoinQueue.php has NO identity check on any join path. Every successful join runs
// `$lobby->players[] = new Player(_SWUNextPlayerID($lobby), …)`, and _SWUNextPlayerID is just
// max(playerID)+1 — so re-entering a room you already occupy always appends a second seat.
// Abandoning a tab does not release the seat either (only Leave does, by design), and the lobby
// lives 900s with its TTL renewed by anyone's poll, so "came back 10 minutes later" lands in a room
// that still holds your old seat.
//
// ⚠ THE ENGINE CANNOT CATCH THIS LATER. A Gamestate carries NO player identity at all — no userId,
// no authKey, no username (verified on game 1311327). Identity exists only in the lobby and the
// Match record, so the lobby is the only place this can be prevented.
//
// These assertions are deliberately POLICY-INDEPENDENT: they pin "not seated twice", which holds
// whether the second join is refused or reclaims the existing seat.
//
// Runs in the WEB SAPI — needs APCu, which the CLI SAPI lacks:
//   http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/lobby_no_duplicate_seat_test.php
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
$deck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));

// Seats in a lobby, read straight from APCu.
$seats = function ($lobbyID) {
    $l = apcu_fetch($lobbyID);
    if (!is_object($l)) return [];
    $out = [];
    foreach (($l->players ?? []) as $p) {
        if (!($p instanceof Player)) continue;
        $out[] = ['playerID' => intval($p->getPlayerID()), 'userId' => $p->getUserId(), 'authKey' => $p->getAuthKey()];
    }
    return $out;
};
$countUser = function ($seats, $uid) {
    $n = 0; foreach ($seats as $s) if ($s['userId'] !== null && intval($s['userId']) === intval($uid)) $n++;
    return $n;
};
$leaveAll = function ($lobbyID, $keys, $jar) use ($L) {
    foreach ($keys as $k) hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID, 'playerID' => 0, 'authKey' => $k], $jar);
};
// The logged-in account's own userId, so assertions name a real id rather than guessing.
$whoami = function ($jar) use ($B) {
    $ch = curl_init($B . 'AccountFiles/AccountSettings.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    curl_exec($ch); curl_close($ch);
    return null;   // not needed: the assertions below compare seat userIds to each other
};

echo "── A. the same logged-in player re-enters a room they already hold ──\n";
$jarA = tempnam(sys_get_temp_dir(), 'dup');
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jarA);
$a1 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarA);
check(!empty($a1['success']), 'created a twinsuns room');
$lobbyA = $a1['lobbyID'] ?? ''; $inviteA = $a1['inviteCode'] ?? '';
$before = $seats($lobbyA);
check(count($before) === 1, 'the room starts with exactly one seat (got ' . count($before) . ')');
$uidA = $before ? $before[0]['userId'] : null;

hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $inviteA,
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarA);
$after = $seats($lobbyA);
check($countUser($after, $uidA) === 1,
    'after re-entering by invite, that account holds ONE seat (holds ' . $countUser($after, $uidA) . ')');
check(count($after) === 1, 'the room still has one seat total (has ' . count($after) . ')');
$leaveAll($lobbyA, array_column($after, 'authKey'), $jarA);

echo "\n── B. a joiner presenting an authKey they already hold for this room ──\n";
// This is report 2's shape: the guest's browser keeps tcg:lobbyAuth:<lobbyID> in localStorage
// (24h backstop), so after signing in it still holds the seat's secret. Presenting it must RECOVER
// the seat, never mint a second one — and this is the only key that can work for a guest, whose
// first seat carries userId NULL and so can never be matched by account.
$jarH = tempnam(sys_get_temp_dir(), 'dup');
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot2', 'password' => 'pass'], $jarH);
$b1 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarH);
$lobbyB = $b1['lobbyID'] ?? ''; $inviteB = $b1['inviteCode'] ?? '';
check(!empty($b1['success']), 'host created a second twinsuns room');

$jarG = tempnam(sys_get_temp_dir(), 'dup');    // a GUEST — no login on this jar
$g1 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $inviteB,
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarG);
$guestKey = $g1['authKey'] ?? '';
check(!empty($g1['success']) && $guestKey !== '', 'a guest took a seat');
check(count($seats($lobbyB)) === 2, 'two seats after the guest joined');

// The guest signs in so they can chat, then the page re-joins presenting the seat key it still has.
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot3', 'password' => 'pass'], $jarG);
hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $inviteB, 'authKey' => $guestKey,
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarG);
$afterB = $seats($lobbyB);
check(count($afterB) === 2,
    'signing in and re-joining keeps the room at two seats (has ' . count($afterB) . ')');
$leaveAll($lobbyB, array_column($afterB, 'authKey'), $jarG);
$leaveAll($lobbyB, array_column($afterB, 'authKey'), $jarH);

echo "\n── C. the public queue must not pair you with yourself ──\n";
// Report 1's actual route: create, wander off without pressing Leave, come back and queue again.
// The scan must not hand you a lobby you are already sitting in.
$jarC = tempnam(sys_get_temp_dir(), 'dup');
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot4', 'password' => 'pass'], $jarC);
$c1 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'format' => 'twinsuns', 'queueType' => 'bo1',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarC);
$lobbyC = $c1['lobbyID'] ?? '';
check(!empty($c1['success']), 'queued into a public twinsuns room: ' . ($c1['message'] ?? ''));
$c2 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'format' => 'twinsuns', 'queueType' => 'bo1',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarC);
$lobbyC2 = $c2['lobbyID'] ?? '';
$seatsC = $seats($lobbyC);
$uidC = $seatsC ? ($seatsC[0]['userId'] ?? null) : null;
check($countUser($seatsC, $uidC) <= 1,
    'queueing twice never puts one account in one room twice (holds ' . $countUser($seatsC, $uidC) . ')');
$leaveAll($lobbyC,  array_column($seatsC, 'authKey'), $jarC);
if ($lobbyC2 !== '' && $lobbyC2 !== $lobbyC) $leaveAll($lobbyC2, array_column($seats($lobbyC2), 'authKey'), $jarC);

echo "\n── D. one account holds a seat in ONE room (owner ruling 2026-09-26) ──\n";
// Report 1's ghost seat: create a room, wander off WITHOUT pressing Leave, then go and play
// somewhere else. The abandoned seat must not keep holding a slot in a room other people can see
// and join for the rest of the 900s TTL.
$jarD = tempnam(sys_get_temp_dir(), 'dup');
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jarD);
$d1 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarD);
$roomOld = $d1['lobbyID'] ?? '';
check(!empty($d1['success']) && count($seats($roomOld)) === 1, 'created a room and took its only seat');
$uidD = $seats($roomOld)[0]['userId'] ?? null;

// Somebody else opens a room, and our player goes there instead — no Leave in between.
$jarE = tempnam(sys_get_temp_dir(), 'dup');
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot2', 'password' => 'pass'], $jarE);
$e1 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarE);
$roomNew = $e1['lobbyID'] ?? ''; $inviteNew = $e1['inviteCode'] ?? '';
hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $inviteNew,
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarD);

check($countUser($seats($roomNew), $uidD) === 1, 'they took a seat in the new room');
check($countUser($seats($roomOld), $uidD) === 0,
    'their seat in the ABANDONED room was released (still holds ' . $countUser($seats($roomOld), $uidD) . ')');
$leaveAll($roomNew, array_column($seats($roomNew), 'authKey'), $jarE);
$leaveAll($roomOld, array_column($seats($roomOld), 'authKey'), $jarD);

echo "\n── E. the CLIENT presents the seat key it holds ──\n";
// The server's authKey branch is unreachable if the waiting room never sends one, and scenario B
// above passes it by hand — so on its own B proves the server contract and NOT the shipped flow.
// This is the half a request-level fixture cannot see.
$wr = file_get_contents(__DIR__ . '/../../../SharedUI/Render/WaitingRoom.php');
$joinFn = strstr($wr, 'function doJoin()');
$joinFn = $joinFn === false ? '' : substr($joinFn, 0, 1400);
check(strpos($joinFn, 'loadKey(lobbyID)') !== false,
    'doJoin reads the stored seat key for this lobby');
check(strpos($joinFn, "'&authKey='") !== false || strpos($joinFn, '"&authKey="') !== false,
    'doJoin sends that key to JoinQueue');

echo "\n" . ($FAILS === 0 ? "ALL PASS\n" : "$FAILS FAILED\n");
exit($FAILS === 0 ? 0 : 1);
