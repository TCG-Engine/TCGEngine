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

$LEGAL   = fixture('meta-2026-09/aggro_vader_yellow.txt');   // Premier-legal
$ILLEGAL = fixture('premier_deck_a.txt');                     // SOR — not Premier-legal; Open-legal
$bot1 = login('claudebot1');
$bot2 = login('claudebot2');
$anon = tempnam(sys_get_temp_dir(), 'pq');

echo "── Part A: refusals and legality ──\n";
foreach (['twinsuns', 'twinsuns-preview', 'teamsuns'] as $f) {
    $r = join_($bot1, $f, 'bo1', $LEGAL);
    check(empty($r['success']) && str_contains((string)($r['message'] ?? ''), 'private rooms') && empty($r['lobbyID']), "$f public join is refused toward private rooms", $r);
}
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
