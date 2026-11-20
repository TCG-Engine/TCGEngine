<?php
// Limited SWISS end to end through the real endpoints: a built Draft pod's round 1 starts by itself, every table gets its
// own child match (up to 4 at once, each game isolated), results come back through the gameResultRecorded hook (or the
// poll, when the hook was missed), the 15-minute break (Ready, the host's Start round, the deadline), three rounds to the
// final standings, Bo3 game scores, and Sealed still one match. Spec: docs/superpowers/specs/2026-11-04-swusim-limited-
// sealed-draft-design.md §3.4, §3.5. Pure rules: limited_swiss_state_test.php. Rooms come from limited_fixture.php.
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s -m 1500 http://localhost/TCGEngine/SWUSim/DevTools/tests/limited_swiss_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/MatchFlow.php';                   // MatchRecordGameResult / MatchHook — FILE scope (engine globals)
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/Limited/LimitedRoom.php';
require_once './SWUSim/Limited/LimitedSettings.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$B = 'http://localhost/TCGEngine/';
$T0 = time();
function hit(string $url, array $params = [], bool $get = false): array {
    $ch = curl_init($get ? $url . '?' . http_build_query($params) : $url);
    $o = [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 300];
    if (!$get) $o += [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params)];
    curl_setopt_array($ch, $o);
    $raw = (string)curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $j = json_decode($raw, true);
    return (is_array($j) ? $j : ['RAW' => substr($raw, 0, 600)]) + ['_code' => $code];
}
$ROOMS = [];
register_shutdown_function(function () {
    global $ROOMS;
    foreach ($ROOMS as $r) {
        $l = apcu_fetch($r['lobbyID']);
        foreach (($l->limited['swiss']['pairings'] ?? []) as $round) foreach ($round as $x) {
            if (empty($x['bye']) && empty($x['done'])) { MatchAbortNoResult('SWUSim', $x['matchId']); RemoveActiveGame('SWUSim', $x['gameName']); }
        }
        LobbyMutate($r['lobbyID'], fn($l) => 'delete');
        foreach ([SWURoomPath($r['lobbyID']), SWURoomInvitePath($r['inviteCode'])] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
});
function fixture(array $q): array {
    global $B, $ROOMS;
    $r = hit($B . 'SWUSim/DevTools/tests/limited_fixture.php', $q, true);
    if (!empty($r['lobbyID'])) $ROOMS[] = $r;
    return $r;
}
function act(array $r, int $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params);
}
function build(array $r, int $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedBuilderAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params);
}
function pollRoom(array $r, int $who): array {
    global $B;
    $j = hit($B . 'APIs/Lobbies/PollLobbyUpdates.php', ['lobbyID' => $r['lobbyID'], 'rootName' => 'SWUSim', 'playerID' => $r['ids'][$who], 'authKey' => $r['keys'][$who]]);
    return $j['limited'] ?? [];
}
function lt(array $r): array { $l = apcu_fetch($r['lobbyID']); return is_object($l) ? $l->limited : []; }
function tables(array $lt, ?int $round = null): array { return $lt['swiss']['pairings'][$round ?? intval($lt['swiss']['round'] ?? 0)] ?? []; }
// Record a table's games (match seat numbers, in order) through the real result path; the hook unless $hook is false.
function play(array $x, array $winners, bool $hook = true): void {
    $g = $x['gameName'];
    foreach ($winners as $i => $seat) { $name = $i === 0 ? $g : $g . chr(98 + $i); MatchRecordGameResult('SWUSim', $x['matchId'], $name, $seat, 1); }
    if ($hook) MatchHook('SWUSim', 'gameResultRecorded', SWUReadMatch($x['matchId']), $name);
}
function playAll(array $r, array $winners = [1]): void { foreach (tables(lt($r)) as $x) if (empty($x['bye'])) play($x, $winners); }
function legalDeck(array $b): array {
    $leader = ''; $main = [];
    foreach ($b['pool'] ?? [] as $c) { $t = CardType($c); if ($t === 'Leader') { if ($leader === '') $leader = $c; continue; } if ($t !== 'Base' && count($main) < 30) $main[] = $c; }
    return ['leader' => $leader, 'base' => strval($b['basesInPlay'][0] ?? ''), 'main' => $main];
}
// Every Match.json this room made (scans only match folders touched since the test began).
function roomMatches(string $lobbyID): array {
    global $T0;
    $out = [];
    foreach (glob(MatchesDir('SWUSim') . '/M*', GLOB_ONLYDIR) ?: [] as $d) {
        if (intval(@filemtime($d)) < $T0 - 5) continue;
        $m = json_decode(strval(@file_get_contents($d . '/Match.json')), true);
        if (is_array($m) && strval($m['roomId'] ?? '') === $lobbyID) $out[] = $m;
    }
    return $out;
}
function pairsMet(array $lt): array {
    $met = [];
    foreach ($lt['swiss']['pairings'] as $round) foreach ($round as $x) if (empty($x['bye'])) { $k = min($x['a'], $x['b']) . '-' . max($x['a'], $x['b']); $met[$k] = ($met[$k] ?? 0) + 1; }
    return $met;
}

echo "BuiltStartsRoundOne:\n";
$r = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'building']);
foreach ([1, 2, 3, 4] as $s) { $b = build($r, $s, 'state'); build($r, $s, 'submit', ['deck' => json_encode(legalDeck($b))]); build($r, $s, 'ready'); }
$lt = lt($r);
$check(($lt['phase'] ?? '') === 'playing' && intval($lt['swiss']['round'] ?? 0) === 1 && count(tables($lt)) === 2, 'the last Ready starts round 1 by itself: two tables: ' . strval($lt['phase'] ?? ''));
$ms = array_map(fn($x) => SWUReadMatch($x['matchId']), tables($lt));
$check(count(array_filter($ms, fn($m) => is_array($m) && $m['roomId'] === $r['lobbyID'] && count($m['limited']['pools'] ?? []) === 2 && intval($m['sideboardSeconds'] ?? 0) === 300)) === 2,
       'each table\'s match has the room id, its two seats\' pools and the 5-minute sideboard cap');
$x0 = tables($lt)[0];
$check(($ms[0]['limited']['pools'][1] ?? null) === $lt['pools'][$x0['a']] && ($ms[0]['limited']['pools'][2] ?? null) === $lt['pools'][$x0['b']], 'match seat 1 is the table\'s first seat (a), seat 2 is b');

echo "FourMatchesAreIsolated:\n";
$e = fixture(['mode' => 'draft', 'players' => 8, 'phase' => 'round1']);
$lt = lt($e);
$check(count(tables($lt)) === 4 && count(array_unique(array_column(tables($lt), 'matchId'))) === 4, 'an 8-seat pod: 4 tables, 4 different matches');
$leader = fn($p) => strval($lt['seats'][$p]['deck']['leader'] ?? '');
$clean = 0;
foreach (tables($lt) as $i => $x) {
    $gs = strval(@file_get_contents('./SWUSim/Games/' . $x['gameName'] . '/Gamestate.txt'));
    $own = [$leader($x['a']), $leader($x['b'])];
    $others = [];
    foreach (tables($lt) as $j => $y) if ($j !== $i) $others = array_merge($others, [$leader($y['a']), $leader($y['b'])]);
    $others = array_diff($others, $own);
    $hasOwn = $gs !== '' && !array_filter($own, fn($c) => strpos($gs, $c) === false);
    $hasOther = array_filter($others, fn($c) => strpos($gs, $c) !== false);
    if ($hasOwn && !$hasOther) $clean++;
    else echo "    table $i: own " . json_encode($own) . ' in file: ' . json_encode($hasOwn) . ', others found: ' . json_encode(array_values($hasOther)) . "\n";
}
$check($clean === 4, "every game holds its own two leaders and no other table's ($clean of 4)");

echo "PollViewShowsMyTable:\n";
$f = fixture(['mode' => 'draft', 'players' => 5, 'phase' => 'round1']);
$lt = lt($f);
$ok = 0; $byeOk = false;
foreach ([1, 2, 3, 4, 5] as $s) {
    $pid = $f['ids'][$s];
    $v = pollRoom($f, $s);
    $mine = LTSwissMyPairing($lt, $pid);
    if (!empty($mine['bye'])) { $byeOk = array_key_exists('live', $v) && $v['live'] === null && !empty($v['swiss']['myBye']); continue; }
    if (($v['live']['gameName'] ?? '') === $mine['gameName'] && intval($v['live']['seat'] ?? 0) === intval(array_search($pid, $mine['seats'], true))) $ok++;
}
$check($ok === 4, "each seat's poll sends it to its own table's game and seat ($ok of 4)");
$check($byeOk, 'the bye seat has no game, and its poll says it has a bye');
$v = pollRoom($f, 1);
$check(count($v['swiss']['tables'] ?? []) === 3 && array_key_exists('standings', $v['swiss'] ?? []) && $v['swiss']['standings'] === null, 'the poll lists the round\'s 3 tables (2 + a bye); no standings before a round is complete');

echo "ResultsMoveToBetween:\n";
$h = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'round1']);
playAll($h);
$lt = lt($h);
$check(($lt['phase'] ?? '') === 'between', 'the round\'s last result (through the hook) → the break: ' . strval($lt['phase'] ?? ''));
$v = pollRoom($h, 2);
$check(count($v['swiss']['standings'] ?? []) === 4 && intval($v['swiss']['betweenEndsIn'] ?? 0) > 890, 'the poll shows the standings (4 rows) and the 15-minute clock');
$b = build($h, 3, 'state');
$check(!empty($b['deck']['leader']) && !empty($b['submitted']) && empty($b['ready']) && intval($b['endsIn'] ?? 0) > 890, 'the builder opens on the seat\'s last deck, submitted, not Ready, with the break\'s clock');

echo "HostStartsRoundTwo:\n";
$no = act($h, 1, 'startRound');
$check($no['_code'] === 409 && empty($no['success']), 'Start round before everyone is Ready → refused: ' . strval($no['message'] ?? ''));
act($h, 1, 'ready'); act($h, 2, 'ready'); build($h, 3, 'ready'); build($h, 4, 'ready');
$check(array_filter(LTLiveSeats(lt($h)), fn($p) => empty(lt($h)['seats'][$p]['ready'])) === [] && (lt($h)['phase'] ?? '') === 'between', 'Ready from the room (2 seats) and from the builder (2 seats); the round waits for the host');
$no = act($h, 2, 'startRound');
$check($no['_code'] === 403, 'a non-host cannot start the round');
$go = act($h, 1, 'startRound');
$lt = lt($h);
$check(!empty($go['success']) && ($lt['phase'] ?? '') === 'playing' && intval($lt['swiss']['round']) === 2 && max(pairsMet($lt)) === 1, 'the host starts round 2; nobody meets a second time: ' . json_encode(pairsMet($lt)));

echo "ConcurrentStartMakesOneRound:\n";
$c = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'between']);
foreach ([1, 2, 3, 4] as $s) act($c, $s, 'ready');
LobbyMutate($c['lobbyID'], function ($l) { $l->limited['swiss']['betweenDeadline'] = time() - 1; return true; });   // the deadline is due too
$mh = curl_multi_init(); $hs = [];
foreach ([[1, 'startRound'], [2, 'tick'], [3, 'tick'], [1, 'startRound']] as [$who, $a]) {
    $ch = curl_init($B . 'SWUSim/LimitedAction.php');
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 300,
        CURLOPT_POSTFIELDS => http_build_query(['lobbyID' => $c['lobbyID'], 'authKey' => $c['keys'][$who], 'action' => $a])]);
    curl_multi_add_handle($mh, $ch); $hs[] = $ch;
}
do { curl_multi_exec($mh, $running); curl_multi_select($mh, 1.0); } while ($running > 0);
foreach ($hs as $ch) { curl_multi_remove_handle($mh, $ch); curl_close($ch); }
$lt = lt($c);
$made = roomMatches($c['lobbyID']);
$check(($lt['phase'] ?? '') === 'playing' && intval($lt['swiss']['round']) === 2 && count($made) === 4,
       'four racing starts make exactly one round 2: 2 + 2 matches for this room (' . count($made) . ')');

echo "DeadlineAutoStarts:\n";
$d = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'between']);
LobbyMutate($d['lobbyID'], function ($l) { $l->limited['swiss']['betweenDeadline'] = time() - 1; return true; });
act($d, 2, 'tick');
$lt = lt($d);
$check(($lt['phase'] ?? '') === 'playing' && intval($lt['swiss']['round']) === 2, 'at the deadline one tick readies everyone and starts round 2 (nobody pressed Ready)');

echo "KickedHostStartsTheRound:\n";   // Final review #2
$k = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'between']);
foreach ([1, 2, 3, 4] as $s) act($k, $s, 'ready');
LobbyMutate($k['lobbyID'], function ($l) {
    $l->limited['swiss']['betweenDeadline'] = time() + LT_BETWEEN_SECONDS - 200;   // the break began 200 s ago
    foreach ($l->players as $p) $p->touch(intval($p->getPlayerID()) === 1 ? time() - 100 : time());   // the host went quiet
    return true;
});
act($k, 2, 'kickVote', ['targetPlayerID' => $k['ids'][1]]);
act($k, 3, 'kickVote', ['targetPlayerID' => $k['ids'][1]]);
$lt = lt($k);
$check(!empty($lt['seats'][$k['ids'][1]]['dropped']) && ($lt['phase'] ?? '') === 'playing' && intval($lt['swiss']['round'] ?? 0) === 2,
       'everyone Ready, the silent host is voted out → round 2 starts at once: ' . strval($lt['phase'] ?? '') . ' round ' . intval($lt['swiss']['round'] ?? 0));

echo "DroppedSeatIsToldBetweenRounds:\n";   // owner fix (2026-11-05)
$x = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'between', 'dropSeat' => 4]);
$v4 = pollRoom($x, 4); $v2 = pollRoom($x, 2);
$check(!empty($v4['me']['dropped']) && empty($v2['me']['dropped']), 'the dropped seat\'s poll says it was dropped; a live seat\'s does not');
$r4 = act($x, 4, 'ready');
$check(empty($r4['success']) && ($r4['message'] ?? '') === 'You are no longer in this event.', 'a dropped seat\'s Ready is refused with the real reason: ' . strval($r4['message'] ?? ''));

echo "RehydrateMidRoundCatchesResult:\n";
$z = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'round1']);
$x = tables(lt($z))[0];
play($x, [2], false);   // the hook never ran
apcu_delete($z['lobbyID']);
pollRoom($z, 1);
$lt = lt($z);
$t0 = tables($lt)[0] ?? [];
$check(!empty($t0['done']) && $t0['winner'] === $x['b'] && count(tables($lt)) === 2, 'APCu lost the room mid-round: the poll brings it back from disk and picks up the result the hook missed');

echo "ThreeRoundsThenDone:\n";
$t = fixture(['mode' => 'draft', 'players' => 3, 'phase' => 'done']);
$lt = lt($t);
$st = LTSwissStandings($lt);
$byes = array_count_values(array_merge(...array_map(fn($r) => array_column(array_filter($r, fn($x) => !empty($x['bye'])), 'a'), $lt['swiss']['pairings'])));
ksort($byes);
$check(($lt['phase'] ?? '') === 'done' && intval($lt['swiss']['round']) === 3 && $lt['result']['winner'] === $st[0]['playerID'] && $lt['result']['reason'] === 'played',
       'three rounds, then done; the winner is standings rank 1: ' . json_encode($lt['result'] ?? null));
$check(pairsMet($lt) === ['1-2' => 1, '1-3' => 1, '2-3' => 1] || (count(pairsMet($lt)) === 3 && max(pairsMet($lt)) === 1), 'three players: everyone met both others once: ' . json_encode(pairsMet($lt)));
$check(array_values($byes) === [1, 1, 1], 'and each had one bye: ' . json_encode($byes));
$v = pollRoom($t, 1);
$check(($v['phase'] ?? '') === 'done' && count($v['swiss']['standings'] ?? []) === 3, 'the room shows the final standings');

echo "Bo3ScoresAreGames:\n";
$q = fixture(['mode' => 'draft', 'players' => 4, 'phase' => 'round1', 'bestOf' => 3]);
$x = tables(lt($q))[0];
play($x, [1, 2, 1]);
$t0 = tables(lt($q))[0];
$row = array_values(array_filter(LTSwissStandings(lt($q)), fn($s) => $s['playerID'] === $x['a']))[0];
$check(!empty($t0['done']) && $t0['games'] === [$x['a'] => 2, $x['b'] => 1] && $t0['winner'] === $x['a'] && $row['points'] === 3, 'a 2–1 Bo3: games 2 and 1, the winner gets 3 points');
// A seat whose match is over is not sent back to it while the other table plays on.
$seatOf = array_flip($q['ids']);
$y = tables(lt($q))[1];
$vDone = pollRoom($q, $seatOf[$x['a']]); $vLive = pollRoom($q, $seatOf[$y['a']]);
$check(array_key_exists('live', $vDone) && $vDone['live'] === null && ($vLive['live']['gameName'] ?? '') === $y['gameName'],
       'a finished table\'s seats get no game to go back to; the other table\'s still do');

echo "SealedStillOneMatch:\n";
$s = fixture(['phase' => 'playing']);
$v = pollRoom($s, 1);
$check(is_array(lt($s)['match'] ?? null) && !array_key_exists('swiss', $v) && !empty($v['live']['gameName']), 'a Sealed room still plays one match, and its poll has no swiss key');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
