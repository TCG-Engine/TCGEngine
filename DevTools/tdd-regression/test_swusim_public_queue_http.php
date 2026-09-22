<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_public_queue_http.php
// SWUSim public matchmaking through the real endpoints — docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md.
// Web SAPI only (APCu + sessions). Logs in claudebot1 / claudebot2 (password pass). Every lobby it opens it leaves again.
// ⚠ A stale public Premier Bo1 lobby left by manual play (TTL 10 min) pairs with this test's first join. If "host waits"
//   fails with ready=true, wait out the TTL and re-run — do not change the assertion.
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
    $jar = tempnam(sys_get_temp_dir(), 'pq');
    post($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $jar);
    return $jar;
}
function join_($jar, $format, $queueType, $deck, $extra = []) {
    global $L;
    return post($L . 'JoinQueue.php', array_merge(['rootName' => 'SWUSim', 'deckLink' => $deck, 'format' => $format, 'queueType' => $queueType], $extra), $jar);
}
function leave($jar, $r) {
    global $L;
    if (empty($r['lobbyID'])) return [];
    return post($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'playerID' => $r['playerID'] ?? 0, 'lobbyID' => $r['lobbyID'], 'authKey' => $r['authKey'] ?? ''], $jar);
}
function poll($jar, $r) {
    global $L;
    return post($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'playerID' => $r['playerID'] ?? 0, 'lobbyID' => $r['lobbyID'] ?? '', 'authKey' => $r['authKey'] ?? ''], $jar);
}
function fixture($rel) {
    return trim(implode("\n", array_filter(explode("\n", file_get_contents(__DIR__ . '/../../SWUSim/Tests/BotFixtures/' . $rel)), fn($l) => !str_starts_with($l, '#'))));
}

$LEGAL    = fixture('meta-2026-09/aggro_vader_yellow.txt');   // Premier-legal
$ILLEGAL  = fixture('premier_deck_a.txt');                     // SOR — not Premier-legal; Open-legal
$TWINSUNS = fixture('twinsuns_deck_a.txt');                    // 2 leaders + 80 highlander (CR §12.2)
$bot1 = login('claudebot1');
$bot2 = login('claudebot2');
$anon = tempnam(sys_get_temp_dir(), 'pq');

echo "── Part A: refusals and legality ──\n";
// Owner, 2026-09-20: the Twin Suns family now takes the public queue, REVERSING the refusal this
// block used to assert ("…is refused toward private rooms"). Its queue is a public ROOM: the join
// succeeds, the lobby is created at the format's real seat count, and the client is told it is a room
// so it routes to the waiting-room page instead of sitting on a quick-match spinner.
//
// ⚠ teamsuns-preview was MISSING from the old loop — a pre-existing gap, closed here. All four now.
foreach (['twinsuns', 'twinsuns-preview', 'teamsuns', 'teamsuns-preview'] as $f) {
    $r = join_($bot1, $f, 'bo1', $TWINSUNS);
    check(!empty($r['success']) && !empty($r['lobbyID']), "$f public join is ACCEPTED", $r);
    check(($r['isRoom'] ?? null) === true, "$f public join reports isRoom", $r);
    check(intval($r['maxPlayers'] ?? 0) === 4, "$f public lobby seats 4, not 2", $r);
    check(empty($r['ready']), "$f public lobby does not auto-start on create", $r);
    leave($bot1, $r);
}
// The refusal that REMAINS is deck legality, and it must not be the old "private rooms" message —
// a Premier list has one leader and is nowhere near 80 highlander cards.
$r = join_($bot1, 'twinsuns', 'bo1', $LEGAL);
check(empty($r['success']) && empty($r['lobbyID']), 'a Premier list is still refused for twinsuns', $r);
check(!str_contains((string)($r['message'] ?? ''), 'private rooms'), 'twinsuns refusal is about the DECK, not private rooms', $r);

echo "── Part A2: a public Twin Suns ROOM fills without auto-starting ──\n";
// ⚠ THE ASSERTIONS ABOVE DO NOT COVER THE AUTO-START GUARD. They run on a freshly CREATED lobby,
// which holds one player and so could never be `ready` regardless. The guard only has an opinion when
// the room FILLS — so fill it. Four seats, Team Suns (strictly 4), all on the same public lobby.
$bot3 = login('claudebot3');
$bot4 = login('claudebot4');
$seats = [];
foreach ([[$bot1, 1], [$bot2, 2], [$bot3, 3], [$bot4, 4]] as [$jar, $n]) {
    $r = join_($jar, 'teamsuns', 'bo1', $TWINSUNS);
    $seats[] = [$jar, $r];
    check(!empty($r['success']), "seat $n joined the public Team Suns room", $r);
    if ($n > 1) check(($r['lobbyID'] ?? '') === ($seats[0][1]['lobbyID'] ?? ''), "seat $n landed in the SAME room as seat 1", $r);
}
$last = end($seats)[1];
// The payoff: a FULL room must still not have started. A quick-match lobby would be ready + carry a
// gameName the moment its last seat arrived.
check(empty($last['ready']), 'the full Team Suns room is NOT ready (the host starts it)', $last);
check(empty($last['gameName']), 'the full Team Suns room created no game on its own', $last);
check(intval($last['maxPlayers'] ?? 0) === 4 && ($last['isRoom'] ?? null) === true, 'the 4th seat is told it is a 4-player room', $last);

// ⚠ EVERY SEAT'S DECK, NOT JUST THE CREATOR'S (prod report, 2026-09-22). The public-room JOIN branch
// built the Player and appended it without ever resolving its deck — it was written when a public
// lobby could only be a two-seat quick match, where the deck is resolved at pairing instead. So the
// creator showed `deck ✓` and every OTHER seat showed "NO DECK / deck missing/invalid" forever, with
// "Seat ? has an illegal or unreadable deck" permanently blocking Start. The private-invite join a
// few lines away in the same file has always resolved it; only the public one did not. Assertions
// above this line all ran on the CREATOR, which is why none of them could see it.
$roster = poll($seats[0][0], $seats[0][1])['roster'] ?? [];
check(count($roster) === 4, 'all four seats appear in the roster', $roster);
foreach ($roster as $row) {
    check(!empty($row['deckOk']), 'roster seat ' . ($row['playerID'] ?? '?') . ' has a resolved, legal deck', $row);
    check(!empty($row['identity']['cards']), 'roster seat ' . ($row['playerID'] ?? '?') . ' shows an identity strip', $row);
}
$deckBlockers = array_values(array_filter(poll($seats[0][0], $seats[0][1])['blockers'] ?? [],
                                          fn($b) => str_contains(strtolower((string)$b), 'deck')));
check($deckBlockers === [], 'a room where every seat brought a legal deck has no deck blocker', $deckBlockers);

// And the creator holds the room: only hostPlayerID may start it, so a NON-host start must be refused.
$nonHost = $seats[1];
$sr = post($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $nonHost[1]['lobbyID'],
                                  'playerID' => $nonHost[1]['playerID'] ?? 0, 'authKey' => $nonHost[1]['authKey'] ?? ''], $nonHost[0]);
check(empty($sr['success']) && str_contains(strtolower((string)($sr['message'] ?? '')), 'host'),
      'a non-host cannot start the public room', $sr);
// The creator holds the room: seat 1's start must fail for some OTHER reason (teams unpicked, decks
// unconfirmed — whatever startBlockers says), never "only the host can start".
//
// ⚠ WHAT THIS DOES NOT PROVE, measured by mutation 2026-09-20: deleting `$lobby->hostPlayerID = 1`
// from JoinQueue leaves this GREEN. StartRoom.php:33 and PollLobbyUpdates.php:117 both read
// `hostPlayerID ?? 1`, so an unset field still names seat 1 at both of the places a test can see.
// The readers that default to 0 are SWUMigrateHostIfNeeded / SWUMigrateHostIfAway — and the second
// one is where an unset field really bites: its host lookup finds no seat 0, returns false, and an
// away host's room can NEVER be handed over. That needs 300s of clock and belongs in
// SWUSim/DevTools/tests/lobby_presence_test.php (which injects $now), not here.
$hostSeat = $seats[0];
$hr = post($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $hostSeat[1]['lobbyID'],
                                  'playerID' => $hostSeat[1]['playerID'] ?? 0, 'authKey' => $hostSeat[1]['authKey'] ?? ''], $hostSeat[0]);
check(!str_contains(strtolower((string)($hr['message'] ?? '')), 'only the host'),
      'the room CREATOR is its host (seat 1 is not refused as a non-host)', $hr);
foreach (array_reverse($seats) as [$jar, $r]) leave($jar, $r);
$r = join_($bot1, 'premier', 'bo1', $ILLEGAL);
check(empty($r['success']) && ($r['message'] ?? '') !== '' && empty($r['lobbyID']), 'an illegal Premier deck is refused at join', $r);
$r = join_($anon, 'premier', 'bo1', $LEGAL);
check(empty($r['success']) && str_contains((string)($r['message'] ?? ''), 'logged in'), 'an anonymous Premier join hits the login gate', $r);
$r = join_($anon, 'open', 'bo1', $ILLEGAL);
check(!empty($r['success']) && !empty($r['lobbyID']), 'an anonymous Open join is accepted (any list)', $r);
leave($anon, $r);

echo "── Part A: pairing ──\n";
$a = join_($bot1, 'premier', 'bo1', $LEGAL);
check(!empty($a['success']) && empty($a['ready']) && !empty($a['lobbyID']), 'host waits in the Premier Bo1 queue', $a);
$c = join_($bot2, 'premier', 'bo3', $LEGAL);
check(!empty($c['success']) && empty($c['ready']) && empty($c['gameName']), 'a Bo3 join does not pair with the Bo1 host', $c);
leave($bot2, $c);
$b = join_($bot2, 'premier', 'bo1', $LEGAL);
check(!empty($b['success']) && !empty($b['ready']) && !empty($b['gameName']), 'the second Premier Bo1 join pairs and gets a real gameName', $b);
// A matched seat that "leaves" must not reopen its lobby: the next joiner used to be paired into it and handed the old game.
leave($bot2, $b);
$d = join_($bot1, 'premier', 'bo1', $LEGAL);
check(!empty($d['success']) && empty($d['ready']) && empty($d['gameName']) && ($d['lobbyID'] ?? '') !== ($b['lobbyID'] ?? '-'),
      'a matched lobby is never joinable again, even after a seat leaves it', $d);
leave($bot1, $d);

// ── PART B (Task 12) ──
echo "── Part B: a deck that fails when the match is found ──\n";
// The HOST's deck fails at pairing: the joiner takes over the lobby and keeps searching; the host is told why.
$h = join_($bot1, 'premier', 'bo1', $LEGAL, ['testFailAtPairing' => '1']);
check(!empty($h['success']) && empty($h['ready']), 'B1 host (test hook: fails at pairing) waits', $h);
$j = join_($bot2, 'premier', 'bo1', $LEGAL);
check(!empty($j['success']) && empty($j['ready']) && empty($j['gameName']) && ($j['lobbyID'] ?? '') === ($h['lobbyID'] ?? '-'),
      'B1 the joiner keeps searching in the same lobby', $j);
$hp = poll($bot1, $h);
check(!empty($hp['gone']) && str_contains((string)($hp['message'] ?? ''), 'test hook'), 'B1 the host poll answers gone, with the reason', $hp);
$jl = leave($bot2, $j);
check(!empty($jl['success']), 'B1 the joiner still holds a seat (Leave succeeds)', $jl);

// The JOINER's deck fails at pairing: the joiner gets the error; the host keeps its seat.
$h2 = join_($bot1, 'premier', 'bo1', $LEGAL);
check(!empty($h2['success']) && empty($h2['ready']), 'B2 host waits', $h2);
$j2 = join_($bot2, 'premier', 'bo1', $LEGAL, ['testFailAtPairing' => '1']);
check(empty($j2['success']) && str_contains((string)($j2['message'] ?? ''), 'test hook'), 'B2 the joiner is refused, with the reason', $j2);
$hl = leave($bot1, $h2);
check(!empty($hl['success']), 'B2 the host still holds a seat (Leave succeeds)', $hl);

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
