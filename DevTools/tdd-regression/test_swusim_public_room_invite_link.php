<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_public_room_invite_link.php
// A PUBLIC Twin Suns waiting room can be shared by link — "Copy Link" (owner feature request,
// 2026-09-26: "people are erroneously copying the URL and pasting it in a chat to find players").
//
// Web SAPI only (APCu + sessions). Logs in claudebot1..3 (password pass). Every lobby it opens it
// leaves again — a stray public lobby lives 10 minutes and pairs with the NEXT test run.
//
// WHAT WAS WRONG. inviteCode was minted only in the createPrivate branch, so a public room had none:
// the waiting room's Copy button never rendered, and both invite paths in JoinQueue.php refused a
// public lobby outright (`if (!isset($lobby->isPrivate) || !$lobby->isPrivate) continue;`). Public
// rooms exist ONLY for the Twin Suns family — SWUSim/LobbyAdapter.php's wantsWaitingRoom() is
// `isPrivate || SWUFormatIsRoomFormat()`, i.e. maxPlayers > 2 — which is exactly the surface the
// owner described.
//
// ⚠ THE TRAP THIS TEST IS BUILT AROUND. "Second player joins with the code and lands in the same
// lobby" is VACUOUS for a public room: ordinary matchmaking would put them there anyway, because it
// is the only open public room of that format. So the code is tested by pointing it at a room
// matchmaking would NOT choose — a TEAMSUNS room, joined while asking for TWINSUNS. If the code is
// honoured the joiner lands in the teamsuns lobby (the invite pre-pass adopts the lobby's own format
// on purpose); if it is ignored they get a twinsuns lobby with a different id. The control below
// makes the same request with an EMPTY code and proves the two outcomes really differ.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');

$B = 'http://localhost/TCGEngine/';   // in-container loopback
$L = $B . 'APIs/Lobbies/';
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function post($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 40, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode((string)$r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 300)];
}
function login($user) {
    global $B;
    $jar = tempnam(sys_get_temp_dir(), 'pril');
    post($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $jar);
    return $jar;
}
function join_($jar, $format, $queueType, $deck, $extra = []) {
    global $L;
    return post($L . 'JoinQueue.php', array_merge(
        ['rootName' => 'SWUSim', 'deckLink' => $deck, 'format' => $format, 'queueType' => $queueType], $extra), $jar);
}
function leave($jar, $r) {
    global $L;
    if (empty($r['lobbyID'])) return [];
    return post($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'playerID' => $r['playerID'] ?? 0,
        'lobbyID' => $r['lobbyID'], 'authKey' => $r['authKey'] ?? ''], $jar);
}
function poll($jar, $r) {
    global $L;
    return post($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'playerID' => $r['playerID'] ?? 0,
        'lobbyID' => $r['lobbyID'] ?? '', 'authKey' => $r['authKey'] ?? ''], $jar);
}
function fixture($rel) {
    return trim(implode("\n", array_filter(explode("\n",
        file_get_contents(__DIR__ . '/../../SWUSim/Tests/BotFixtures/' . $rel)), fn($l) => !str_starts_with($l, '#'))));
}

$TWINSUNS = fixture('twinsuns_deck_a.txt');
$PREMIER  = fixture('meta-2026-09/vader_yellow.txt');
$bot1 = login('claudebot1');
$bot2 = login('claudebot2');
$bot3 = login('claudebot3');
$open = [];   // [jar, response] to clean up

// ── 1. A PUBLIC ROOM IS ISSUED A CODE ───────────────────────────────────────────────────────────────
// ⚠ A STALE PUBLIC TEAMSUNS ROOM MAKES THIS TEST JOIN INSTEAD OF CREATE. Public lobbies live 10
// minutes, and matchmaking hands bot1 an existing open room rather than opening a second one — at
// which point bot1 is a JOINER (playerID > 1) and the create response carries no inviteCode, because
// only the creator's response does. That is an environment state, not a regression, so the test
// detects it and asserts the same behaviour through the POLL, which every seat can read. Left
// undetected it is an intermittent red that looks exactly like the feature being broken.
$teamRoom = join_($bot1, 'teamsuns', 'bo1', $TWINSUNS);
$open[] = [$bot1, $teamRoom];
check(!empty($teamRoom['success']), 'a public teamsuns room is created or joined', $teamRoom);
check(!empty($teamRoom['isRoom']), 'and it IS a waiting room (public rooms are the Twin Suns family only)', $teamRoom);

$iCreatedIt = intval($teamRoom['playerID'] ?? 0) === 1;
if ($iCreatedIt) {
    check(!empty($teamRoom['inviteCode']),
          'the create response carries an inviteCode — this is what the Copy Link button needs', $teamRoom);
} else {
    echo "  note: a stale public teamsuns room was already open, so this run JOINED it;\n"
       . "        the create-response assertion is skipped and the poll is used instead.\n";
}
// The code as the PAGE gets it. This is the assertion that matters either way: renderInvite() draws
// the button from the poll payload, not from the create response.
$code = strval((poll($bot1, $teamRoom))['inviteCode'] ?? '');
check($code !== '', 'the poll hands the page an inviteCode for a PUBLIC room', $code);

// ── 2. AND THE POLL REPORTS IT, PLUS whether the room is public ─────────────────────────────────────
// The page renders "Copy Link" for a public room and "Invite: <code> [Copy Invite Link]" for a private
// one, so it needs to tell them apart. isPrivate is ADDITIVE to the poll payload — every existing
// field is untouched.
$p = poll($bot1, $teamRoom);
check(array_key_exists('isPrivate', $p), 'the poll reports isPrivate so the page can pick its label',
      array_keys(is_array($p) ? $p : []));
check(($p['isPrivate'] ?? null) === false, 'and says this room is PUBLIC', $p['isPrivate'] ?? null);

// ── 3. THE CODE DECIDES THE ROOM — the non-vacuous half (see the header) ───────────────────────────
// bot2 asks for TWINSUNS but presents the TEAMSUNS room's code. Honouring the code means landing in
// the teamsuns lobby; ignoring it means a twinsuns lobby with a different id.
$viaCode = join_($bot2, 'twinsuns', 'bo1', $TWINSUNS, ['privateInviteCode' => $code]);
$open[] = [$bot2, $viaCode];
check(!empty($viaCode['success']), 'a second player can join a public room by its code', $viaCode);
check(strval($viaCode['lobbyID'] ?? '') === strval($teamRoom['lobbyID'] ?? 'x'),
      'and lands in THAT room, not in the queue their format would have matched',
      [$viaCode['lobbyID'] ?? null, $teamRoom['lobbyID'] ?? null]);

// THE CONTROL. Same request, no code — it must NOT reach the teamsuns room, or the assertion above
// proves nothing about the code.
$noCode = join_($bot3, 'twinsuns', 'bo1', $TWINSUNS);
$open[] = [$bot3, $noCode];
check(strval($noCode['lobbyID'] ?? '') !== strval($teamRoom['lobbyID'] ?? 'x'),
      'WITHOUT the code the same request goes elsewhere — the code is what did the work',
      [$noCode['lobbyID'] ?? null, $teamRoom['lobbyID'] ?? null]);

foreach ($open as [$jar, $r]) leave($jar, $r);
$open = [];

// ── 4. ONLY ROOM FORMATS GET A LINK ────────────────────────────────────────────────────────────────
// A public Premier queue has no waiting room at all (maxPlayers 2), so there is no page to share and
// no code to mint. Without this, "mint a code for every public lobby" would pass everything above.
$premier = join_($bot1, 'premier', 'bo1', $PREMIER);
$open[] = [$bot1, $premier];
check(!empty($premier['success']), 'a public premier queue still works', $premier);
check(empty($premier['isRoom']), 'premier is NOT a room', $premier);
check(empty($premier['inviteCode']), 'and a non-room public queue is issued NO code', $premier);
foreach ($open as [$jar, $r]) leave($jar, $r);
$open = [];

// ── 5. PRIVATE ROOMS ARE UNCHANGED ─────────────────────────────────────────────────────────────────
$priv = post($L . 'JoinQueue.php',
    ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns', 'deckLink' => $TWINSUNS], $bot1);
$open[] = [$bot1, $priv];
check(!empty($priv['inviteCode']), 'a private room still gets its code', $priv);
$privJoin = join_($bot2, 'twinsuns', 'bo1', $TWINSUNS, ['privateInviteCode' => $priv['inviteCode'] ?? '']);
$open[] = [$bot2, $privJoin];
check(strval($privJoin['lobbyID'] ?? '') === strval($priv['lobbyID'] ?? 'x'),
      'and joining a private room by code still lands in it', [$privJoin['lobbyID'] ?? null, $priv['lobbyID'] ?? null]);
$pp = poll($bot1, $priv);
check(($pp['isPrivate'] ?? null) === true, 'the poll marks a private room private', $pp['isPrivate'] ?? null);
foreach ($open as [$jar, $r]) leave($jar, $r);
$open = [];

// ── 6. A BOGUS CODE STILL FAILS ────────────────────────────────────────────────────────────────────
$bogus = join_($bot3, 'twinsuns', 'bo1', $TWINSUNS, ['privateInviteCode' => 'deadbeefdeadbeefdeadbeef']);
$open[] = [$bot3, $bogus];
check(empty($bogus['success']), 'an unknown invite code is still refused', $bogus);
foreach ($open as [$jar, $r]) leave($jar, $r);

echo $FAILS === 0 ? "\nALL PASS\n" : "\n{$FAILS} FAILED\n";
