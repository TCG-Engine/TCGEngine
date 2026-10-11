    <?php
// WHY A JOIN WAS REFUSED — the message, not just the refusal.
//
// Every row below used to collapse into one of two strings: "That invite is invalid or has expired."
// (PollLobbyUpdates) or "Private game invite is invalid, expired, or already full." (JoinQueue). A
// player who opened a shared room link could not tell a full room from a closed one from a typo, and
// for a PUBLIC Twin Suns room the link did not work at all: PollLobbyUpdates resolved an invite code
// to a lobby only when `!empty($lobby->isPrivate)`, so a public room answered "invalid or expired"
// whether it had a free seat or not. JoinQueue had already dropped that same gate on purpose when the
// public-room Copy Link shipped; this was the site that was missed.
//
// ⚠ THE BLOCK CASE IS DELIBERATELY NOT SPECIFIC. A join refused by SWUJoinBlockedFromLobby is worded
// exactly like a bad code, because naming full/started/expired separately would otherwise turn the
// leftover generic string into a reliable "you are blocked" signal by elimination.
//
// Runs in the WEB SAPI — it needs APCu, which the CLI SAPI does not have:
//   http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/lobby_invite_messages_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
// Without the class definition, unserialize hands back __PHP_Incomplete_Class and any method call on
// a Player read straight out of APCu is fatal.
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/TeamRooms.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }
function says($haystack, $needle, $msg) { check(stripos(strval($haystack), $needle) !== false, $msg . ' — got: ' . json_encode($haystack)); }

$B = 'http://localhost/TCGEngine/';          // in-container loopback
$L = $B . 'APIs/Lobbies/';
$DECK = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));

function hit($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 45,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_FOLLOWLOCATION => false]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 300)];
}

function jar() { return tempnam(sys_get_temp_dir(), 'lim'); }
function account($user) { global $B; $j = jar(); hit($B . 'AccountFiles/AttemptPasswordLogin.php',
    ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $j); return $j; }

// A PUBLIC twinsuns room: plain matchmaking, no createPrivate. The first caller creates it, the rest
// pair into it, which is exactly how a real room fills.
function queue($jar, $extra = []) {
    global $L, $DECK;
    return hit($L . 'JoinQueue.php', array_merge(['rootName' => 'SWUSim', 'format' => 'twinsuns',
        'queueType' => 'bo1', 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $extra), $jar);
}
function pollBy($jar, $lobbyID, $inviteCode, $authKey = '') {
    global $L;
    return hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID,
        'inviteCode' => $inviteCode, 'playerID' => 0, 'authKey' => $authKey], $jar);
}
function bail($jar, $lobbyID, $r) {
    global $L;
    if (empty($r['authKey'])) return;
    hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID,
        'playerID' => $r['playerID'] ?? 0, 'authKey' => $r['authKey']], $jar);
}

$j1 = account('claudebot1'); $j2 = account('claudebot2');
$j3 = account('claudebot3'); $j4 = account('claudebot4');
$outsider = jar();   // no account: a link recipient is not required to have one

// ── 1. A PUBLIC room is reachable by its own Copy Link ─────────────────────────────────────────────
echo "\n1. public room resolves by invite code\n";
$host = queue($j1);
check(!empty($host['success']) && !empty($host['inviteCode']), 'created a public twinsuns room');
$lobby = strval($host['lobbyID'] ?? ''); $code = strval($host['inviteCode'] ?? '');
$seen = pollBy($outsider, '', $code);
check(!empty($seen['success']), 'an outsider polling by ?invite= resolves the room');
check(empty($seen['gone']), 'it is NOT reported gone');
check(intval($seen['numPlayers'] ?? 0) === 1 && intval($seen['maxPlayers'] ?? 0) === 4,
      'the payload carries the seat counts the page needs (1/4)');
check(isset($seen['roster']) && count($seen['roster']) === 1, 'the roster is returned');

// ── 2. A FULL room still resolves, and says it is full ──────────────────────────────────────────────
echo "\n2. full room resolves; the refusal names capacity\n";
$p2 = queue($j2); $p3 = queue($j3); $p4 = queue($j4);
check(!empty($p2['success']) && !empty($p3['success']) && !empty($p4['success']), 'three more seats filled it');
$full = pollBy($outsider, '', $code);
check(!empty($full['success']) && empty($full['gone']), 'a full room is still resolvable, not "expired"');
check(intval($full['numPlayers'] ?? 0) === 4 && intval($full['maxPlayers'] ?? 0) === 4,
      'the page can render "full (4/4)" from the payload');
$refused = queue($outsider, ['privateInviteCode' => $code]);
check(empty($refused['success']), 'joining a full room is refused');
// The COUNTS, not the word "full" — the old catch-all string already contained "already full", so a
// substring check on that word alone passes against the bug this test exists to describe.
says($refused['message'] ?? '', '4/4', 'the refusal names the capacity (4/4)');
check(stripos(strval($refused['message'] ?? ''), 'invalid') === false,
      'it no longer offers "invalid" as one of four possible causes');
check(stripos(strval($refused['message'] ?? ''), 'private') === false,
      'it does not call a public room "private"');

// ── 3. A seat opens: the same link now works ───────────────────────────────────────────────────────
echo "\n3. a seat opens and the link works again\n";
bail($j2, $lobby, $p2);
$open = pollBy($outsider, '', $code);
check(intval($open['numPlayers'] ?? 0) === 3, 'the room reports 3/4 after a seat left');
$joined = queue($outsider, ['privateInviteCode' => $code]);
check(!empty($joined['success']), 'the outsider can now take the free seat: ' . ($joined['message'] ?? ''));

// ── 4. A started room hands an unseated visitor to the SPECTATOR view, not a dead end ──────────────
echo "\n4. started room: unseated visitor becomes a spectator\n";
$started = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby,
    'playerID' => $host['playerID'] ?? 1, 'authKey' => $host['authKey'] ?? ''], $j1);
check(!empty($started['success']) && !empty($started['gameName']), 'the host started the room: ' . ($started['message'] ?? ''));
$late = jar();
$afterStart = pollBy($late, '', $code);
check(!empty($afterStart['started']) && !empty($afterStart['gameName']),
      'a visitor with no seat is pointed at the running game');
check(!empty($afterStart['spectator']),
      'and is flagged as a SPECTATOR so the page sends playerID=S, never 0');
$lateJoin = queue($late, ['privateInviteCode' => $code]);
check(empty($lateJoin['success']), 'joining a started room is refused');
says($lateJoin['message'] ?? '', 'already started', 'the refusal says ALREADY STARTED');

// ── 5. A CLOSED room reads as closed, and a bad code reads as a bad code ───────────────────────────
echo "\n5. closed vs never-existed\n";
$solo = queue($j2);
$soloLobby = strval($solo['lobbyID'] ?? ''); $soloCode = strval($solo['inviteCode'] ?? '');
check(!empty($soloCode), 'a second room exists to close');
bail($j2, $soloLobby, $solo);   // last human out: LeaveQueue deletes the lobby
$closed = pollBy($outsider, '', $soloCode);
check(!empty($closed['gone']), 'a closed room is gone');
says($closed['message'] ?? '', 'closed', 'and says it CLOSED, not "invalid"');
$bogus = pollBy($outsider, '', 'deadbeefdeadbeefdeadbeef');
check(!empty($bogus['gone']), 'a code that never existed is gone');
says($bogus['message'] ?? '', "isn't valid", 'and says the LINK is not valid');
check(stripos(strval($bogus['message'] ?? ''), 'closed') === false,
      'a bad code is not reported as a closed room');

// ── 6. The start blocker names a player when Twin Suns has no seat numbers ─────────────────────────
// PURE function, called by StartRoom through SWULobbyAdapter::startBlockers. Twin Suns assigns no
// seat (LobbyUsesFixedSeats is FaBSim/upf only), so getSeat() is null for every seat and the old
// wording printed the literal string "Seat ?".
echo "\n6. seat-less start blocker wording\n";
$fake = new stdClass();
$fake->rootName = 'SWUSim'; $fake->format = 'twinsuns'; $fake->hostPlayerID = 1;
$bad = new Player(1, '', '', null); $bad->setDeckOk(false); $bad->setReady(true);
$ok1 = new Player(2, '', '', null); $ok1->setDeckOk(true); $ok1->setReady(true);
$ok2 = new Player(3, '', '', null); $ok2->setDeckOk(true); $ok2->setReady(true);
$fake->players = [$bad, $ok1, $ok2];
$blockers = SWURoomStartBlockers($fake);
$joined2 = implode(' | ', $blockers);
check(strpos($joined2, 'Seat ?') === false, 'no literal "Seat ?" in a Twin Suns blocker — got: ' . $joined2);
says($joined2, 'a player has an illegal', 'it names A PLAYER instead');
// A seated format (Team Suns) still reports the seat NUMBER, which is the useful case.
$teamLobby = new stdClass();
$teamLobby->rootName = 'SWUSim'; $teamLobby->format = 'teamsuns'; $teamLobby->hostPlayerID = 1;
$seated = new Player(1, '', '', null); $seated->setDeckOk(false); $seated->setReady(true); $seated->setSeat(3);
$teamLobby->players = [$seated];
says(implode(' | ', SWURoomStartBlockers($teamLobby)), 'seat 3', 'a seated format still names the seat number');

// ── teardown ───────────────────────────────────────────────────────────────────────────────────────
echo "\nteardown\n";
foreach ([[$j3, $p3], [$j4, $p4], [$j1, $host], [$outsider, $joined]] as $pair) {
    bail($pair[0], $lobby, $pair[1]);
}
echo "\n" . ($FAILS === 0 ? "ALL GREEN\n" : "$FAILS FAILED\n");
