<?php
// High Ground rooms under load: SIXTEEN logged-in accounts through the real endpoints, many requests at once, and a long
// evening of hill matches with people joining, leaving and moving while matches run. high_ground_flow_test.php pins each
// flow on its own with a handful of people; this one checks they still hold together at full size.
//
// The test keeps its OWN model of the room (hill, line, bleachers, and Tonight's W-L-D per account) and compares the real
// room with it after every step, so a step that puts someone in the wrong place fails at that step, not matches later.
//
// Accounts (local DB only): host ninin (a moderator), claudebot1..15 fill the room, claudebot16 is the 17th. Password "pass".
// Runs in the WEB SAPI (APCu): http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/high_ground_stress_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/HighGround.php';
// A finished hill match is a completed private match; nothing in this test may reach SWUStats.
$GLOBALS['SWUSTATS_HTTP_FAKE'] = fn($method, $url, $body) => ['status' => 200, 'body' => ['success' => true], 'raw' => '{}'];

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }
$T0 = microtime(true);
function section(string $name) { global $T0; printf("%s  (+%.1fs)\n", $name, microtime(true) - $T0); }
$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
$DECK = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));

function hit($url, $params, $jar = null, &$status = null) {
    $r = multi([[$url, $params, $jar]], $st);
    $status = $st[0];
    return $r[0];
}
// Every request at once (curl_multi), each with its own cookie jar. Returns the decoded replies in request order.
function multi(array $reqs, &$statuses = null): array {
    $mh = curl_multi_init(); $hs = [];
    foreach ($reqs as $i => [$url, $params, $jar]) {
        $ch = curl_init($url);
        $o = [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120];
        if ($jar !== null) { $o[CURLOPT_COOKIEJAR] = $jar; $o[CURLOPT_COOKIEFILE] = $jar; }
        curl_setopt_array($ch, $o);
        curl_multi_add_handle($mh, $ch); $hs[$i] = $ch;
    }
    do { curl_multi_exec($mh, $running); if ($running) curl_multi_select($mh, 1.0); } while ($running > 0);
    $out = []; $statuses = [];
    foreach ($hs as $i => $ch) {
        $raw = (string)curl_multi_getcontent($ch);
        $statuses[$i] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $j = json_decode($raw, true);
        $out[$i] = is_array($j) ? $j : ['RAW' => substr($raw, 0, 400)];
        $out[$i]['__raw'] = $raw;
        curl_multi_remove_handle($mh, $ch); curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

$ROOMS = []; $JARS = []; $MATCHES = [];
register_shutdown_function(function () {
    global $ROOMS, $JARS, $MATCHES;
    foreach ($MATCHES as $mid) cleanupMatch($mid);
    foreach ($ROOMS as $r) {
        LobbyMutate($r['lobbyID'], fn($l) => 'delete');
        foreach ([SWURoomPath($r['lobbyID']), SWURoomInvitePath($r['invite'])] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
    foreach ($JARS as $j) @unlink($j);
});
// A logged-in session (or a guest's, for $user === ''). Logins run in parallel.
function jars(array $users): array {
    global $B, $JARS;
    $out = []; $reqs = [];
    foreach ($users as $u) {
        $j = tempnam(sys_get_temp_dir(), 'hgs'); $JARS[] = $j; $out[$u] = $j;
        if ($u !== '' && !str_starts_with($u, 'guest')) $reqs[] = [$B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $u, 'password' => 'pass'], $j];
    }
    if ($reqs) multi($reqs);
    return $out;
}
function joinParams(array $r, string $who): array {
    global $DECK;
    return ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''];
}
$UID = [];   // account => userId (Tonight counts per account)
function noteJoin(array &$r, string $who, array $j): void {
    global $UID;
    if (empty($j['success'])) return;
    $r['keys'][$who] = strval($j['authKey'] ?? '');
    $r['ids'][$who] = intval($j['playerID'] ?? 0);
    $uid = intval(hg($r)['userIds'][$r['keys'][$who]] ?? 0);
    if ($uid > 0) $UID[$who] = $uid;
}
function joinHG(array &$r, string $who): array {
    global $L;
    $j = hit($L . 'JoinQueue.php', joinParams($r, $who), $r['jars'][$who]);
    noteJoin($r, $who, $j);
    return $j;
}
function hg(array $r): ?array {
    $l = apcu_fetch($r['lobbyID']);
    return is_object($l) && !empty($l->highGround) ? $l->highGround : null;
}
function names(array $r, string $field): array {
    $hg = hg($r); $byKey = array_flip($r['keys']);
    return array_map(fn($k) => $byKey[$k] ?? '?', $hg[$field] ?? []);
}
function act(array $r, string $who, string $action, array $extra = [], &$status = null): array {
    global $B;
    return hit($B . 'SWUSim/HighGroundAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $extra,
        $r['jars'][$who], $status);
}
function pollParams(array $r, string $who): array {
    return ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => 0, 'authKey' => $r['keys'][$who]];
}
function leave(array &$r, string $who): array {
    global $L;
    $x = hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => $r['ids'][$who], 'authKey' => $r['keys'][$who]],
        $r['jars'][$who]);
    unset($r['keys'][$who], $r['ids'][$who]);
    return $x;
}
function ready(array $r, string $who): array {
    global $L;
    return hit($L . 'SetReady.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'ready' => '1'], $r['jars'][$who]);
}
function hostName(array $r): string {
    $hid = intval(apcu_fetch($r['lobbyID'])->hostPlayerID ?? 0);
    foreach ($r['ids'] as $n => $id) if ($id === $hid) return $n;
    return '';
}
function finishGame(string $matchId, string $game, int $winner): void {   // production's per-game path (as high_ground_flow_test.php)
    if ($winner === 0) MatchRecordGameDraw('SWUSim', $matchId, $game, 5);
    else MatchRecordGameResult('SWUSim', $matchId, $game, $winner, 5, [$winner]);
    MatchHook('SWUSim', 'gameResultRecorded', SWUReadMatch($matchId), $game);
}
function cleanupMatch(string $matchId): void {
    if ($matchId === '') return;
    $m = SWUReadMatch($matchId);
    foreach ((is_array($m) ? $m['games'] : []) as $g) {
        $gn = preg_replace('/[^A-Za-z0-9_]/', '', strval($g['gameName'] ?? ''));
        if ($gn !== '') exec('rm -rf ' . escapeshellarg(MatchGamesDir('SWUSim') . '/' . $gn));
    }
    exec('rm -rf ' . escapeshellarg(MatchesDir('SWUSim') . '/' . $matchId));
}

// ── The model ─────────────────────────────────────────────────────────────────────────────────────────────────────
$M = ['hill' => [], 'line' => [], 'bleachers' => []];
$TALLY = [];   // userId => [w, l, d]
function mWithout(array $list, string $n): array { return array_values(array_filter($list, fn($x) => $x !== $n)); }
function mLeave(string $n): void { global $M; foreach ($M as $f => $l) $M[$f] = mWithout($l, $n); }
function mPair(): array {
    global $M;
    $pair = array_slice($M['hill'], 0, 2);
    foreach ($M['line'] as $n) { if (count($pair) >= 2) break; if (!in_array($n, $pair, true)) $pair[] = $n; }
    return $pair;
}
// Compare the real room with the model; one line per mismatch, named by the step.
function same(array $r, string $step): bool {
    global $M;
    $ok = true;
    foreach (['hill', 'line', 'bleachers'] as $f) {
        $got = names($r, $f);
        if ($got !== $M[$f]) { check(false, "$step: $f is [" . implode(',', $got) . '], model [' . implode(',', $M[$f]) . ']'); $ok = false; }
    }
    // Everyone in the room is in exactly one place (the two playing sit on the hill), and the room's seat count agrees.
    $hg = hg($r); $l = apcu_fetch($r['lobbyID']);
    $placed = array_merge($hg['hill'] ?? [], $hg['line'] ?? [], $hg['bleachers'] ?? []);
    if (count($placed) !== count(array_unique($placed)) || count($placed) !== count($hg['names'] ?? [])
        || count($hg['names'] ?? []) !== count($l->players ?? []) || intval($l->numPlayers ?? -1) !== count($l->players ?? [])) {
        check(false, "$step: placed " . count($placed) . ' (unique ' . count(array_unique($placed)) . '), members ' . count($hg['names'] ?? [])
            . ', seats ' . count($l->players ?? []) . ', numPlayers ' . intval($l->numPlayers ?? -1));
        $ok = false;
    }
    return $ok;
}

$ACCOUNTS = array_merge(['ninin'], array_map(fn($i) => "claudebot$i", range(1, 16)));
$jars = jars($ACCOUNTS);

// ══ 1. Sixteen accounts fill a room, fifteen of them at the same moment ═════════════════════════════════════════════
section('HGS_SixteenJoinAtOnce:');
$h = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'highGround' => '1', 'format' => 'premier', 'queueType' => 'bo1',
    'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jars['ninin']);
$r = ['lobbyID' => strval($h['lobbyID'] ?? ''), 'invite' => strval($h['inviteCode'] ?? ''), 'keys' => ['ninin' => strval($h['authKey'] ?? '')],
      'ids' => ['ninin' => intval($h['playerID'] ?? 0)], 'jars' => $jars];
$ROOMS[] = $r;
check(!empty($h['success']) && ($h['highGround'] ?? null) === true, 'ninin (a moderator) creates the room');
$M['line'] = ['ninin'];
$joiners = array_map(fn($i) => "claudebot$i", range(1, 15));
$t = microtime(true);
$res = multi(array_map(fn($n) => [$L . 'JoinQueue.php', joinParams($r, $n), $jars[$n]], $joiners));
$dt = microtime(true) - $t;
foreach ($joiners as $i => $n) noteJoin($r, $n, $res[$i]);
$okJoins = array_filter($res, fn($x) => !empty($x['success']));
check(count($okJoins) === 15, 'all 15 simultaneous joins succeed (' . count($okJoins) . '/15 in ' . round($dt, 1) . 's)'
    . (count($okJoins) < 15 ? ' — refusals: ' . json_encode(array_values(array_unique(array_map(fn($x) => $x['message'] ?? substr($x['RAW'] ?? '', 0, 80), array_filter($res, fn($x) => empty($x['success']))))))  : ''));
$hgNow = hg($r);
check(count($hgNow['names'] ?? []) === 16 && count(array_unique(array_values($r['ids']))) === 16, 'sixteen members, sixteen distinct playerIDs');
$wantNames = array_merge(['ninin'], $joiners); sort($wantNames);
$gotNames = array_values($hgNow['names'] ?? []); sort($gotNames);
check($gotNames === $wantNames, 'every member is shown under their account name (no "Guest N")');
// Simultaneous joins land in whatever order the server took them; ninin is first. Adopt that order for the model.
$line = names($r, 'line');
check(($line[0] ?? '') === 'ninin' && count($line) === 16 && count(array_unique($line)) === 16, 'the line holds everyone once, the host first');
$M['line'] = $line;
same($r, 'after the joins');
$UID['ninin'] = intval($hgNow['userIds'][$r['keys']['ninin']] ?? 0);
check(count(array_filter($UID)) === 16 && count(array_unique($UID)) === 16, 'every member carries their own account id');

// ══ 2. Full is full; opening the link again is not a second seat ═══════════════════════════════════════════════════
section('HGS_FullAndReopen:');
$x = joinHG($r, 'claudebot16');
check(empty($x['success']) && str_contains(strval($x['message'] ?? ''), 'full'), 'claudebot16 (17th) is refused: ' . ($x['message'] ?? json_encode($x)));
$g = hit($L . 'JoinQueue.php', joinParams($r, 'guest'), jars(['guest1'])['guest1']);
check(empty($g['success']), 'a guest (17th) is refused too: ' . ($g['message'] ?? ''));
// claudebot5 opens the invite again in the SAME browser (it presents its room key, as the page does from localStorage).
$same = hit($L . 'JoinQueue.php', joinParams($r, 'claudebot5') + ['authKey' => $r['keys']['claudebot5']], $jars['claudebot5']);
check(!empty($same['success']) && intval($same['playerID'] ?? 0) === $r['ids']['claudebot5'], 'claudebot5 reopening the link in the same browser keeps their seat ('
    . intval($same['playerID'] ?? 0) . ' vs ' . $r['ids']['claudebot5'] . ') ' . ($same['message'] ?? ''));
same($r, 'after reopening the link in the same browser');
// …and in a second browser: a new session, no room key presented.
$second = jars(['claudebot5'])['claudebot5'];
$re = hit($L . 'JoinQueue.php', joinParams($r, 'claudebot5'), $second);
check(!empty($re['success']) && intval($re['playerID'] ?? 0) === $r['ids']['claudebot5'], 'claudebot5 reopening the link gets their own seat back ('
    . intval($re['playerID'] ?? 0) . ' vs ' . $r['ids']['claudebot5'] . ') ' . ($re['message'] ?? ''));
if (!empty($re['success'])) $r['keys']['claudebot5'] = strval($re['authKey'] ?? $r['keys']['claudebot5']);
same($r, 'after reopening the link');   // not moved, not duplicated
// The same in an ordinary full 1v1 private room (the capacity gate is shared by every private room). Played by two people who
// are NOT in the High Ground room: taking a seat anywhere releases the seats you hold elsewhere (see the next section).
$guestJar = jars(['guest-1v1'])['guest-1v1'];
$p1 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier', 'queueType' => 'bo1',
    'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $guestJar);
$pr = ['lobbyID' => strval($p1['lobbyID'] ?? ''), 'invite' => strval($p1['inviteCode'] ?? ''), 'keys' => [], 'ids' => [], 'jars' => $jars];
$ROOMS[] = $pr;
$p2 = joinHG($pr, 'claudebot16');
$again = hit($L . 'JoinQueue.php', joinParams($pr, 'claudebot16'), jars(['claudebot16'])['claudebot16']);
check(!empty($p2['success']) && !empty($again['success']) && intval($again['playerID'] ?? 0) === intval($p2['playerID'] ?? -1)
    && count(apcu_fetch($pr['lobbyID'])->players ?? []) === 2, 'a full 1v1 room lets its own player back to their seat: ' . ($again['message'] ?? 'ok'));
hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $pr['lobbyID'], 'playerID' => intval($p2['playerID'] ?? 0), 'authKey' => strval($p2['authKey'] ?? '')], $jars['claudebot16']);
hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $pr['lobbyID'], 'playerID' => intval($p1['playerID'] ?? 0), 'authKey' => strval($p1['authKey'] ?? '')], $guestJar);
same($r, 'after the 1v1 room next door');

// ══ 2b. A member who takes a seat in another room leaves this one — line and all ═══════════════════════════════════
section('HGS_SeatElsewhereLeavesTheLine:');
$mover = end($M['line']);
$o = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier', 'queueType' => 'bo1',
    'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jars[$mover]);
$ROOMS[] = ['lobbyID' => strval($o['lobbyID'] ?? ''), 'invite' => strval($o['inviteCode'] ?? '')];
$hgNow = hg($r);
check(!empty($o['success']) && !isset($hgNow['names'][$r['keys'][$mover]]) && count($hgNow['names'] ?? []) === count(apcu_fetch($r['lobbyID'])->players ?? []),
    "$mover made a 1v1 room: out of the High Ground room's seats AND its line (members " . count($hgNow['names'] ?? []) . ', seats '
    . count(apcu_fetch($r['lobbyID'])->players ?? []) . ')');
$snap = SWURoomLoad($r['lobbyID']);
check($snap !== null && !isset($snap['lobby']->highGround['names'][$r['keys'][$mover]]), 'and the snapshot on disk agrees');
mLeave($mover);
hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => strval($o['lobbyID'] ?? ''), 'playerID' => intval($o['playerID'] ?? 0), 'authKey' => strval($o['authKey'] ?? '')], $jars[$mover]);
unset($r['keys'][$mover], $r['ids'][$mover]);
$x = joinHG($r, $mover);
if (!empty($x['success'])) $M['line'][] = $mover;
check(!empty($x['success']), "$mover comes back: back of the line");
same($r, 'after a member sat in another room and came back');

// ══ 3. Sixteen polls at once: each sees itself, nobody sees anyone else's key ══════════════════════════════════════
section('HGS_SixteenPollsAtOnce:');
$members = array_keys($r['keys']);
$polls = multi(array_map(fn($n) => [$L . 'PollLobbyUpdates.php', pollParams($r, $n), $jars[$n]], $members));
$wk = strval(hg($r)['watchKey'] ?? '');
$selfOk = 0; $leaks = [];
foreach ($members as $i => $n) {
    $p = $polls[$i]['highGround'] ?? [];
    if (intval($p['me']['playerID'] ?? 0) === $r['ids'][$n] && ($p['me']['where'] ?? '') === 'line' && ($p['watchKey'] ?? '') === $wk
        && count($p['members'] ?? []) === 16) $selfOk++;
    foreach ($r['keys'] as $other => $k) if ($other !== $n && str_contains($polls[$i]['__raw'], $k)) $leaks[] = "$n saw $other";
}
check($selfOk === 16, "every poll shows its own member, in the line, 16 members, the watch key ($selfOk/16)");
check(empty($leaks), 'no poll carries another member\'s room key' . ($leaks ? ': ' . implode('; ', array_slice($leaks, 0, 5)) : ''));

// ══ 4. Start pressed three times at once makes ONE match ═══════════════════════════════════════════════════════════
section('HGS_StartRaceMakesOneMatch:');
$pair = mPair();
multi(array_map(fn($n) => [$L . 'SetReady.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$n], 'ready' => '1'], $jars[$n]], $pair));
$hostJars = [jars(['ninin'])['ninin'], jars(['ninin'])['ninin'], jars(['ninin'])['ninin']];   // three sessions, so the presses really overlap
$starts = multi(array_map(fn($j) => [$B . 'SWUSim/HighGroundAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys']['ninin'], 'action' => 'start'], $j], array_values($hostJars)));
$won = array_values(array_filter($starts, fn($s) => !empty($s['success'])));
check(count($won) === 1, 'exactly one Start succeeds (' . count($won) . '); the others: '
    . json_encode(array_map(fn($s) => $s['message'] ?? null, array_values(array_filter($starts, fn($s) => empty($s['success']))))));
$mid = strval($won[0]['matchId'] ?? ''); $game = strval($won[0]['gameName'] ?? '');
if ($mid !== '') $MATCHES[] = $mid;
check($mid !== '' && (hg($r)['live']['matchId'] ?? '') === $mid, 'the room\'s live match is that one');
$M['line'] = array_values(array_diff($M['line'], $pair)); $M['hill'] = $pair;
same($r, 'after the start race');

// ══ 5. During a live match: sixteen polls, only the two playing are sent to the board ═══════════════════════════════
section('HGS_LivePollsOnlyPlayersRedirected:');
$members = array_keys($r['keys']);
$polls = multi(array_map(fn($n) => [$L . 'PollLobbyUpdates.php', pollParams($r, $n), $jars[$n]], $members));
$onBoard = [];
foreach ($members as $i => $n) if (!empty($polls[$i]['highGround']['me']['onLiveMatch'])) $onBoard[intval($polls[$i]['highGround']['me']['seat'] ?? 0)] = $n;
ksort($onBoard);
check(array_values($onBoard) === $pair && array_keys($onBoard) === [1, 2], 'only ' . implode(' (seat 1) and ', $pair) . ' (seat 2) are pointed at the board: '
    . json_encode($onBoard));
$liveSeen = count(array_filter($polls, fn($p) => ($p['highGround']['live']['gameName'] ?? '') === $game));
check($liveSeen === 16, "all 16 see the live game ($liveSeen/16)");
finishGame($mid, $game, 1);
$TALLY[$UID[$pair[0]]][0] = ($TALLY[$UID[$pair[0]]][0] ?? 0) + 1;
$TALLY[$UID[$pair[1]]][1] = ($TALLY[$UID[$pair[1]]][1] ?? 0) + 1;
$M['hill'] = [$pair[0]]; $M['line'][] = $pair[1];
same($r, 'after match 1');

// ══ 6. A long evening: 24 hill matches with people coming and going while they run ══════════════════════════════════
section('HGS_LongEvening:');
mt_srand(20261103);
$bench = ['claudebot16'];       // accounts currently outside the room
$bleacherSince = [];            // name => match number they went to the bleachers
$hostLeftAt = 0; $seat2LeftAt = 0; $wipedAt = 0; $abortedAt = 0;
$decided = 1; $draws = 0;
for ($i = 2; $i <= 25; $i++) {
    $step = "match $i";
    $host = hostName($r);
    if ($host === '') { check(false, "$step: the room has no host"); break; }
    $pair = mPair();
    if (count($pair) < 2) { check(false, "$step: fewer than two to play"); break; }
    foreach ($pair as $n) ready($r, $n);
    $s = act($r, $host, 'start');
    if (empty($s['success'])) { check(false, "$step: $host could not start " . implode(' v ', $pair) . ': ' . ($s['message'] ?? json_encode($s))); break; }
    $mid = strval($s['matchId']); $game = strval($s['gameName']); $MATCHES[] = $mid;
    $M['line'] = array_values(array_diff($M['line'], $pair)); $M['hill'] = $pair;
    same($r, "$step start");

    // ── while it runs ──
    // Every 3rd match: the room is full, so a benched account is turned away; then the last in line leaves and they get in.
    if ($i % 3 === 0 && $bench && count($M['line']) >= 2) {
        $in = $bench[0];
        if (count(hg($r)['names']) >= 16) {
            $x = joinHG($r, $in);
            check(empty($x['success']), "$step: $in is refused while the room is full: " . ($x['message'] ?? 'JOINED'));
        }
        $out = end($M['line']);
        leave($r, $out); mLeave($out); $bench[] = $out;
        $x = joinHG($r, $in);
        if (!empty($x['success'])) { array_shift($bench); $M['line'][] = $in; }
        check(!empty($x['success']), "$step: $out left mid-match and $in took the place: " . ($x['message'] ?? 'ok'));
        same($r, "$step churn");
    }
    // Every 4th: someone from the middle of the line goes to the bleachers; two matches later they come back.
    if ($i % 4 === 2 && count($M['line']) >= 3) {
        $n = $M['line'][intdiv(count($M['line']), 2)];
        $a = act($r, $n, 'toBleachers');
        if (!empty($a['success'])) { $M['line'] = mWithout($M['line'], $n); $M['bleachers'][] = $n; $bleacherSince[$n] = $i; }
        check(!empty($a['success']), "$step: $n goes to the bleachers mid-match");
    }
    foreach ($bleacherSince as $n => $since) {
        if ($since + 2 > $i || !isset($r['keys'][$n])) continue;
        $a = act($r, $n, 'joinLine');
        if (!empty($a['success'])) { $M['bleachers'] = mWithout($M['bleachers'], $n); $M['line'][] = $n; unset($bleacherSince[$n]); }
        check(!empty($a['success']), "$step: $n comes back from the bleachers to the back of the line");
    }
    // Someone on the hill tries to go to the bleachers mid-match: refused.
    if ($i === 4) {
        $a = act($r, $pair[0], 'toBleachers', [], $st);
        check(empty($a['success']), "$step: {$pair[0]} cannot leave the hill for the bleachers mid-match ($st)");
    }
    // Once: seat 2 walks out of the room in the middle of their match.
    $seat2Gone = false;
    if ($seat2LeftAt === 0 && $i >= 6) {
        leave($r, $pair[1]); mLeave($pair[1]); $bench[] = $pair[1]; $seat2Gone = true; $seat2LeftAt = $i;
        same($r, "$step seat 2 ({$pair[1]}) left");
    }
    // Once: the host (not playing) leaves mid-match; host passes on and the evening goes on.
    if ($hostLeftAt === 0 && $i >= 9 && !in_array($host, $pair, true) && in_array($host, $M['line'], true)) {
        leave($r, $host); mLeave($host); $bench[] = $host; $hostLeftAt = $i;
        $nh = hostName($r);
        check($nh !== '' && $nh !== $host && isset($r['keys'][$nh]), "$step: host $host left; $nh is host now");
        $other = current(array_filter(array_keys($r['keys']), fn($n) => $n !== $nh));
        $x = act($r, $other, 'setBestOf', ['queueType' => 'bo1'], $st);
        check(empty($x['success']) && $st === 403, "$step: a non-host still cannot use host controls ($st)");
        same($r, "$step host left");
    }
    // Once: APCu loses the room mid-match (16 people inside); the result must still land from the disk snapshot.
    if ($wipedAt === 0 && $i >= 12) { apcu_delete($r['lobbyID']); $wipedAt = $i; }

    // ── the result ──
    $abort = ($abortedAt === 0 && $i >= 15);
    $winner = ($i % 7 === 0) ? 0 : mt_rand(1, 2);
    if ($abort) {
        $nh = hostName($r) ?: $host;
        if (apcu_fetch($r['lobbyID']) === false) hit($L . 'PollLobbyUpdates.php', pollParams($r, $nh), $jars[$nh]);
        $nh = hostName($r);
        $x = act($r, $nh, 'endMatch', ['gonePlayerID' => isset($r['ids'][$pair[1]]) ? $r['ids'][$pair[1]] : 0]);
        check(!empty($x['success']), "$step: host $nh ends the match with no result: " . ($x['message'] ?? 'ok'));
        $M['hill'] = isset($r['keys'][$pair[0]]) ? [$pair[0]] : [];
        if (isset($r['keys'][$pair[1]])) $M['line'][] = $pair[1];
        $abortedAt = $i;
    } else {
        finishGame($mid, $game, $winner);
        if ($winner === 0) {
            foreach ($pair as $n) $TALLY[$UID[$n]][2] = ($TALLY[$UID[$n]][2] ?? 0) + 1;
            $draws++;
            $M['hill'] = array_values(array_filter($pair, fn($n) => isset($r['keys'][$n])));
        } else {
            $w = $pair[$winner - 1]; $lo = $pair[2 - $winner];
            $TALLY[$UID[$w]][0] = ($TALLY[$UID[$w]][0] ?? 0) + 1;
            $TALLY[$UID[$lo]][1] = ($TALLY[$UID[$lo]][1] ?? 0) + 1;
            $decided++;
            $M['hill'] = isset($r['keys'][$w]) ? [$w] : [];
            if (isset($r['keys'][$lo])) $M['line'][] = $lo;
        }
    }
    if ($wipedAt === $i) {
        check(apcu_fetch($r['lobbyID']) !== false && count(hg($r)['names'] ?? []) === count($r['keys']),
            "$step: APCu was wiped mid-match; the room is back with all " . count($r['keys']) . ' members and the result applied');
    }
    same($r, "$step result " . ($abort ? 'aborted' : ($winner === 0 ? 'draw' : "seat $winner")));
    if (!$abort && $winner === 0 && count($M['hill']) === 2) {   // a draw: seat 2 steps down
        $a = act($r, $pair[1], 'stepDown');
        if (!empty($a['success'])) { $M['hill'] = [$pair[0]]; $M['line'][] = $pair[1]; }
        check(!empty($a['success']), "$step: after the draw {$pair[1]} steps down");
        same($r, "$step step down");
    }
    if ($seat2Gone && $bench) {   // the walk-out comes back: a new seat, back of the line, same Tonight row
        $back = $pair[1];
        $x = joinHG($r, $back);
        if (!empty($x['success'])) { $bench = mWithout($bench, $back); $M['line'][] = $back; }
        check(!empty($x['success']), "$step: $back rejoins after walking out: " . ($x['message'] ?? 'ok'));
        same($r, "$step $back rejoined");
    }
    $hgNow = hg($r);
    if ($hgNow === null || $hgNow['live'] !== null) { check(false, "$step: the hill is still live after the result"); break; }
}
check($seat2LeftAt > 0 && $hostLeftAt > 0 && $wipedAt > 0 && $abortedAt > 0,
    "the evening covered a seat-2 walk-out (match $seat2LeftAt), the host leaving (match $hostLeftAt), an APCu wipe (match $wipedAt) and an ended match (match $abortedAt)");

// Tonight: per account, exactly the W-L-D the model counted — across leaving, rejoining (a new seat) and the APCu wipe.
$rows = array_values(hg($r)['tonight'] ?? []);   // the raw rows: HGTonightRows() drops the owner
$got = [];
foreach ($rows as $row) if (preg_match('/^u(\d+)$/', $row['owner'], $mm)) $got[intval($mm[1])] = [$row['w'], $row['l'], $row['d']];
$want = array_map(fn($t) => [$t[0] ?? 0, $t[1] ?? 0, $t[2] ?? 0], $TALLY);
ksort($got); ksort($want);
check($got === $want, 'Tonight matches the model for every account (' . count($want) . ' rows, ' . $decided . ' decided, ' . $draws . ' drawn)'
    . ($got !== $want ? ': got ' . json_encode($got) . ' want ' . json_encode($want) : ''));
check(count($rows) === count($want), 'one row per account (a rejoin keeps the row): ' . count($rows) . ' rows');
$byUid = array_flip($UID);
$unnamed = array_filter($rows, fn($row) => !in_array($row['name'], $ACCOUNTS, true));
check(empty($unnamed), 'every row shows its account name' . ($unnamed ? ': ' . json_encode(array_map(fn($row) => $row['name'] . ' = '
    . ($byUid[intval(substr($row['owner'], 1))] ?? $row['owner']), array_values($unnamed))) . " (seat 2 walked out at match $seat2LeftAt)" : ''));
$sumW = array_sum(array_column($rows, 'w')); $sumL = array_sum(array_column($rows, 'l')); $sumD = array_sum(array_column($rows, 'd'));
check($sumW === $decided && $sumL === $decided && $sumD === 2 * $draws, "Tonight totals: W $sumW = L $sumL = $decided decided, D $sumD = 2 x $draws draws");

// ══ 7. Everyone in the line goes to the bleachers at once, then everyone comes back at once ═════════════════════════
section('HGS_ParallelBleachers:');
$movers = $M['line'];
$res = multi(array_map(fn($n) => [$B . 'SWUSim/HighGroundAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$n], 'action' => 'toBleachers'], $jars[$n]], $movers));
$okN = count(array_filter($res, fn($x) => !empty($x['success'])));
check($okN === count($movers), "all " . count($movers) . " moves to the bleachers succeed ($okN)");
$b = names($r, 'bleachers'); $ln = names($r, 'line');
$sb = $b; sort($sb); $sm = array_merge($M['bleachers'], $movers); sort($sm);
check($ln === [] && $sb === $sm, 'the line is empty and the bleachers hold everyone once (' . count($b) . ')');
$M['line'] = []; $M['bleachers'] = $b;
$back = $movers;
$res = multi(array_map(fn($n) => [$B . 'SWUSim/HighGroundAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$n], 'action' => 'joinLine'], $jars[$n]], $back));
$okN = count(array_filter($res, fn($x) => !empty($x['success'])));
$ln = names($r, 'line'); $sl = $ln; sort($sl); $sbk = $back; sort($sbk);
check($okN === count($back) && $sl === $sbk, 'all come back, each once, in whatever order they arrived (' . count($ln) . ')');
$M['line'] = $ln; $M['bleachers'] = array_values(array_diff($M['bleachers'], $back));
same($r, 'after the bleacher rush');

// ══ 8. Everyone leaves at once: the room is gone, from APCu and from disk ═══════════════════════════════════════════
section('HGS_EveryoneLeavesAtOnce:');
$everyone = array_keys($r['keys']);
multi(array_map(fn($n) => [$L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => $r['ids'][$n], 'authKey' => $r['keys'][$n]], $jars[$n]], $everyone));
$l = apcu_fetch($r['lobbyID']);
$left = is_object($l) ? count($l->players) : 0;
check($l === false && !is_file(SWURoomPath($r['lobbyID'])), count($everyone) . ' left together: the room is gone' . ($l !== false ? " (still $left seated)" : '')
    . (is_file(SWURoomPath($r['lobbyID'])) ? ' (snapshot still on disk)' : ''));
// A late click on the invite must not bring the empty room back from a snapshot one of the leavers wrote after it ended.
$late = jars(['claudebot16'])['claudebot16'];
$x = hit($L . 'JoinQueue.php', joinParams($r, 'claudebot16'), $late);
$ghost = apcu_fetch($r['lobbyID']);
check(empty($x['success']) && $ghost === false, 'a late invite click finds no room' . (!empty($x['success']) ? ' — it JOINED a room holding '
    . (is_object($ghost) ? implode(',', array_map(fn($p) => $p->getUsername(), $ghost->players)) : '?') : ': ' . ($x['message'] ?? '')));

printf("%s in %.1fs\n", $FAILS === 0 ? 'PASS' : "$FAILS FAILED", microtime(true) - $T0);
