<?php
// High Ground rooms END TO END through the real endpoints (JoinQueue, LeaveQueue, KickSeat, AddBot, StartRoom,
// PollLobbyUpdates, SWUSim/HighGroundAction.php). The pure rules are high_ground_state_test.php; this pins what only the
// endpoints do. Spec: docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md
//
// Runs in the WEB SAPI (APCu): http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/high_ground_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/HighGround.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }
$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
function hit($url, $params, $jar = null, &$status = null) {
    $ch = curl_init($url);
    $o = [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120];
    if ($jar !== null) { $o[CURLOPT_COOKIEJAR] = $jar; $o[CURLOPT_COOKIEFILE] = $jar; }
    curl_setopt_array($ch, $o);
    $r = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 400)];
}
function rawHit($url, $params, $jar = null): string {
    $ch = curl_init($url);
    $o = [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120];
    if ($jar !== null) { $o[CURLOPT_COOKIEJAR] = $jar; $o[CURLOPT_COOKIEFILE] = $jar; }
    curl_setopt_array($ch, $o);
    $r = curl_exec($ch); curl_close($ch);
    return (string)$r;
}
function getRaw($url): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120]);
    $r = curl_exec($ch); curl_close($ch);
    return (string)$r;
}
function getj($url): array { $j = json_decode(getRaw($url), true); return is_array($j) ? $j : []; }
$DECK = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));

$ROOMS = [];
$JARS = [];
register_shutdown_function(function () {
    global $ROOMS, $JARS;
    foreach ($ROOMS as $r) {
        LobbyMutate($r['lobbyID'], fn($l) => 'delete');
        foreach ([SWURoomPath($r['lobbyID']), SWURoomInvitePath($r['invite'])] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
    foreach ($JARS as $j) @unlink($j);
});
function jar(string $user = ''): string {
    global $B, $JARS;
    $j = tempnam(sys_get_temp_dir(), 'hgf'); $JARS[] = $j;
    if ($user !== '') hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $j);
    return $j;
}
// Create a High Ground room for $format as $hostJar. Returns the raw JoinQueue response.
function createHG(string $hostJar, string $format = 'premier', string $queueType = 'bo1', &$status = null): array {
    global $L, $DECK;
    return hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'highGround' => '1', 'format' => $format,
        'queueType' => $queueType, 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $hostJar, $status);
}
function joinHG(array &$r, string $name, string $jar): array {
    global $L, $DECK;
    $j = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => $DECK,
        'preconstructedDeck' => '', 'game_type' => ''], $jar);
    $r['keys'][$name] = strval($j['authKey'] ?? '');
    $r['ids'][$name] = intval($j['playerID'] ?? 0);
    $r['jars'][$name] = $jar;
    return $j;
}
// Host claudebot1 ("A") plus the named joiners: 'B'..'D' log in as claudebot2..4, anything starting with 'G' is a guest.
function hgRoom(array $joiners = ['B', 'C', 'D'], string $queueType = 'bo1'): array {
    global $ROOMS;
    $ja = jar('claudebot1');
    $h = createHG($ja, 'premier', $queueType);
    $r = ['lobbyID' => strval($h['lobbyID'] ?? ''), 'invite' => strval($h['inviteCode'] ?? ''), 'create' => $h,
          'keys' => ['A' => strval($h['authKey'] ?? '')], 'ids' => ['A' => intval($h['playerID'] ?? 0)], 'jars' => ['A' => $ja]];
    $ROOMS[] = $r;
    $bots = ['B' => 'claudebot2', 'C' => 'claudebot3', 'D' => 'claudebot4'];
    foreach ($joiners as $n) joinHG($r, $n, jar($n[0] === 'G' ? '' : ($bots[$n] ?? '')));
    $ROOMS[count($ROOMS) - 1] = $r;
    return $r;
}
function hg(array $r): ?array {
    $l = apcu_fetch($r['lobbyID']);
    return is_object($l) && !empty($l->highGround) ? $l->highGround : null;
}
function lineNames(array $r, string $field = 'line'): string {
    $hg = hg($r); $byKey = array_flip($r['keys']);
    return implode(',', array_map(fn($k) => $byKey[$k] ?? '?', $hg[$field] ?? []));
}
function act(array $r, string $who, string $action, array $extra = [], &$status = null): array {
    global $B;
    return hit($B . 'SWUSim/HighGroundAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $extra,
        $r['jars'][$who], $status);
}
function poll(array $r, string $who): array {
    global $L;
    return hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => 0, 'authKey' => $r['keys'][$who]],
        $r['jars'][$who]);
}
function leave(array $r, string $who): array {
    global $L;
    return hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => $r['ids'][$who], 'authKey' => $r['keys'][$who]],
        $r['jars'][$who]);
}
function armKicks(array $r): void {   // new seats can't be kicked for 60s (SWUSeatKickableIn) — age them
    LobbyMutate($r['lobbyID'], function ($l) { foreach ($l->players as $p) $p->setJoinedAt(time() - 120); return true; });
}

// ══ Task 2: create, join, leave, kick, host ══════════════════════════════════════════════════════════════════════

// ── 1. Only a non-rated 1v1 format makes a High Ground room ───────────────────────────────────────────────────────
echo "HG_CreateRefusedForRatedAndTwinSuns:\n";
$j1 = jar('claudebot1');
$bad = createHG($j1, 'twinsuns', 'bo1', $st);
check(empty($bad['success']) && $st === 400, 'Twin Suns is refused (' . $st . ' ' . ($bad['message'] ?? json_encode($bad)) . ')');
$bad = createHG($j1, 'metapremier', 'bo3', $st);
check(empty($bad['success']), 'Meta Premier is refused: ' . ($bad['message'] ?? json_encode($bad)));
$r = hgRoom([]);
check(!empty($r['create']['success']) && ($r['create']['highGround'] ?? null) === true, 'Premier makes a High Ground room');
$l = apcu_fetch($r['lobbyID']);
check(is_object($l) && intval($l->maxPlayers) === 16 && empty($l->roomMatchId), 'sixteen places, no 1v1 room match link');
leave($r, 'A');

// ── 2. Four join, the line is join order ──────────────────────────────────────────────────────────────────────────
echo "HG_FourJoin_LineInJoinOrder:\n";
$r = hgRoom(['B', 'C', 'G1']);
check(lineNames($r) === 'A,B,C,G1', 'line = join order: ' . lineNames($r));
check((hg($r)['names'][$r['keys']['G1']] ?? '') === 'Guest 1', 'the guest got a fixed name');

// ── 3. Bleachers and back ─────────────────────────────────────────────────────────────────────────────────────────
echo "HG_BleachersAndBack:\n";
$a = act($r, 'B', 'toBleachers');
check(!empty($a['success']) && lineNames($r) === 'A,C,G1' && lineNames($r, 'bleachers') === 'B', 'B in the bleachers');
$a = act($r, 'B', 'joinLine');
check(!empty($a['success']) && lineNames($r) === 'A,C,G1,B', 'B back at the back of the line');

// ── 4. The host reorders; nobody else can ─────────────────────────────────────────────────────────────────────────
echo "HG_ReorderByHost:\n";
$order = implode(',', [$r['ids']['B'], $r['ids']['A'], $r['ids']['C'], $r['ids']['G1']]);
$a = act($r, 'C', 'reorder', ['order' => $order], $st);
check(empty($a['success']) && $st === 403 && lineNames($r) === 'A,C,G1,B', 'a non-host reorder is refused (' . $st . ')');
$a = act($r, 'A', 'reorder', ['order' => $order]);
check(!empty($a['success']) && lineNames($r) === 'B,A,C,G1', 'the host reorders: ' . lineNames($r));

// ── 5. A stale reorder is refused and changes nothing ─────────────────────────────────────────────────────────────
echo "HG_ReorderStale409:\n";
$stale = implode(',', [$r['ids']['A'], $r['ids']['B'], $r['ids']['C']]);
$a = act($r, 'A', 'reorder', ['order' => $stale], $st);
check(empty($a['success']) && $st === 409 && lineNames($r) === 'B,A,C,G1', 'a reorder missing someone is a 409 (' . $st . ')');

// ── 6. Bo1 / Bo3 is the host's ────────────────────────────────────────────────────────────────────────────────────
echo "HG_SetBestOfHostOnly:\n";
$a = act($r, 'B', 'setBestOf', ['queueType' => 'bo3'], $st);
check(empty($a['success']) && $st === 403, 'a non-host cannot change the length (' . $st . ')');
$a = act($r, 'A', 'setBestOf', ['queueType' => 'bo3']);
check(!empty($a['success']) && (apcu_fetch($r['lobbyID'])->queueType ?? '') === 'bo3', 'the host sets Bo3');
act($r, 'A', 'setBestOf', ['queueType' => 'bo1']);

// ── 7. A kick removes someone from the line ───────────────────────────────────────────────────────────────────────
echo "HG_KickRemovesFromLine:\n";
armKicks($r);
$k = hit($L . 'KickSeat.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys']['A'], 'targetPlayerID' => $r['ids']['C']], $r['jars']['A']);
check(!empty($k['success']) && lineNames($r) === 'B,A,G1', 'C was kicked out of the line: ' . lineNames($r) . ' ' . json_encode($k));
check(!isset(hg($r)['names'][$r['keys']['C']]), 'and out of the room');

// ── 8. The host leaves: host passes on, the room keeps going ──────────────────────────────────────────────────────
echo "HG_HostLeaves_HostPasses:\n";
$before = hg($r)['watchKey'] ?? '';
leave($r, 'A');
$l = apcu_fetch($r['lobbyID']);
check(is_object($l) && intval($l->hostPlayerID) === $r['ids']['B'] && lineNames($r) === 'B,G1', 'B is host now, the line goes on');
check(($l->highGround['watchKey'] ?? '') !== $before && strlen($l->highGround['watchKey'] ?? '') === 32, 'the watch key rotated when A left');

// ── 9. The last person leaves: the room ends ──────────────────────────────────────────────────────────────────────
echo "HG_LastLeaves_RoomEnds:\n";
leave($r, 'B'); leave($r, 'G1');
check(apcu_fetch($r['lobbyID']) === false && !is_file(SWURoomPath($r['lobbyID'])), 'the room is gone, from APCu and disk');

// ── 10. APCu lost mid-evening: everything comes back from the snapshot ────────────────────────────────────────────
echo "HG_RehydrateKeepsState:\n";
$r = hgRoom(['B', 'G1', 'C']);
act($r, 'C', 'toBleachers');
act($r, 'A', 'reorder', ['order' => implode(',', [$r['ids']['G1'], $r['ids']['A'], $r['ids']['B']])]);
poll($r, 'A');   // the poll's change detector has now seen this state…
LobbyMutate($r['lobbyID'], function ($l) { $l->highGround = HGRecordGame($l->highGround, 'gX', [
    1 => ['key' => array_key_first($l->highGround['names']), 'userId' => 0, 'deckLink' => 'dl', 'leader' => 'L', 'base' => 'B', 'deckName' => 'n'],
    2 => ['key' => 'zz', 'userId' => 0, 'deckLink' => 'dl2', 'leader' => 'L', 'base' => 'B', 'deckName' => 'n']], 1); return true; });
poll($r, 'A');   // …so only a Tonight-aware detector saves this change
$want = hg($r);
apcu_delete($r['lobbyID']);
poll($r, 'B');
$got = hg($r);
check($got !== null && $got['line'] === $want['line'] && $got['bleachers'] === $want['bleachers'] && $got['names'] === $want['names']
    && $got['tonight'] === $want['tonight'], 'line, bleachers, names and Tonight are back after the APCu wipe');
check(intval(apcu_fetch($r['lobbyID'])->hostPlayerID ?? 0) === $r['ids']['A'], 'the host is back too');

// ── 11. No bots on the hill ───────────────────────────────────────────────────────────────────────────────────────
echo "HG_NoBots:\n";
$b = hit($L . 'AddBot.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'authKey' => $r['keys']['A'], 'botProfile' => 'default'], $r['jars']['A']);
check(empty($b['success']) && count(apcu_fetch($r['lobbyID'])->players) === 4, 'AddBot is refused (bots exist only in Twin Suns rooms): ' . ($b['message'] ?? json_encode($b)));
foreach (['A', 'B', 'G1', 'C'] as $n) leave($r, $n);

// ── 12. Sixteen people, everyone counted ──────────────────────────────────────────────────────────────────────────
echo "HG_SeventeenthRefused:\n";
$r = hgRoom([]);
for ($i = 1; $i <= 15; $i++) joinHG($r, 'G' . $i, jar());
$x = joinHG($r, 'G16', jar());
check(count(hg($r)['names']) === 16 && empty($x['success']), 'the 17th person is refused (' . ($x['message'] ?? json_encode($x)) . ')');
leave($r, 'A');
for ($i = 1; $i <= 15; $i++) leave($r, 'G' . $i);

// ── 13. A normal private room is untouched ────────────────────────────────────────────────────────────────────────
echo "HG_NormalPrivateRoomUntouched:\n";
$jn = jar('claudebot1');
$n = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier', 'queueType' => 'bo1',
    'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jn);
$nr = ['lobbyID' => strval($n['lobbyID'] ?? ''), 'invite' => strval($n['inviteCode'] ?? ''), 'keys' => ['A' => strval($n['authKey'] ?? '')],
       'ids' => ['A' => 1], 'jars' => ['A' => $jn]];
$ROOMS[] = $nr;
$np = poll($nr, 'A');
check(!empty($n['success']) && !array_key_exists('highGround', $n) && !array_key_exists('highGround', $np), 'no highGround key anywhere');
check(intval(apcu_fetch($nr['lobbyID'])->maxPlayers ?? 0) === 2, 'still a two-seat room');
$s = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'] ?? '', 'playerID' => 1, 'authKey' => 'x']);
leave($nr, 'A');

// ══ Task 3: hill matches ═══════════════════════════════════════════════════════════════════════════════════════════
function ready(array $r, string $who, bool $on = true): array {
    global $L;
    return hit($L . 'SetReady.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'ready' => $on ? '1' : '0'], $r['jars'][$who]);
}
// Ready the next two and start. Returns [matchId, gameName, response].
function startHill(array $r, array $pair): array {
    foreach ($pair as $n) ready($r, $n);
    $s = act($r, 'A', 'start');
    return [strval($s['matchId'] ?? ''), strval($s['gameName'] ?? ''), $s];
}
// Production's per-game path: record the game, then the hook MatchAfterActionHook calls.
function finishGame(string $matchId, string $game, int $winner): void {
    if ($winner === 0) MatchRecordGameDraw('SWUSim', $matchId, $game, 5);
    else MatchRecordGameResult('SWUSim', $matchId, $game, $winner, 5, [$winner]);
    MatchHook('SWUSim', 'gameResultRecorded', SWUReadMatch($matchId), $game);
}
function tonight(array $r): array { return HGTonightRows(hg($r)); }
function rowOf(array $r, string $who): ?array {
    $name = hg($r)['names'][$r['keys'][$who]] ?? '?';
    foreach (tonight($r) as $row) if ($row['name'] === $name) return $row;
    return null;
}
function wld(?array $row): string { return $row === null ? '-' : ($row['w'] . '-' . $row['l'] . '-' . $row['d']); }
// The match AND its games' folders: a game folder keeps its pointers (RoomReturn.json, NextGame.json, Sideboard.json), and
// a later fixture that reuses the game number would follow them off its own board.
function cleanupMatch(string $matchId): void {
    if ($matchId === '') return;
    $m = SWUReadMatch($matchId);
    foreach ((is_array($m) ? $m['games'] : []) as $g) {
        $gn = preg_replace('/[^A-Za-z0-9_]/', '', strval($g['gameName'] ?? ''));
        if ($gn !== '') exec('rm -rf ' . escapeshellarg(MatchGamesDir('SWUSim') . '/' . $gn));
    }
    exec('rm -rf ' . escapeshellarg(MatchesDir('SWUSim') . '/' . $matchId));
}
function isReady(array $r, string $who): bool {
    foreach (apcu_fetch($r['lobbyID'])->players as $p) if ($p->getAuthKey() === $r['keys'][$who]) return (bool)$p->getReady();
    return false;
}

// ── 14. Start waits for both players on the hill ──────────────────────────────────────────────────────────────────
echo "HG_StartNeedsBothReady:\n";
$r = hgRoom(['G1', 'C', 'D']);
$s = act($r, 'A', 'start', [], $st);
check(empty($s['success']) && $st === 409, 'nobody ready: refused (' . $st . ' ' . ($s['message'] ?? '') . ')');
ready($r, 'A');
$s = act($r, 'A', 'start');
check(empty($s['success']) && str_contains(strval($s['message'] ?? ''), 'Guest 1'), 'the refusal names who isn\'t ready: ' . ($s['message'] ?? ''));
$s = act($r, 'C', 'start', [], $st);
check(empty($s['success']) && $st === 403, 'only the host starts (' . $st . ')');

// ── 15. The hill match is a two-seat private match; the room stays open ───────────────────────────────────────────
echo "HG_StartBuildsTwoSeatMatchFromHill:\n";
act($r, 'D', 'toBleachers');
[$mid, $game, $s] = startHill($r, ['A', 'G1']);
$m = $mid !== '' ? SWUReadMatch($mid) : null;
check(is_array($m) && count($m['players']) === 2, 'two seats: ' . json_encode($s));
check(is_array($m) && $m['players']['1']['authKey'] === $r['keys']['A'] && $m['players']['2']['authKey'] === $r['keys']['G1'],
    'seat 1 is the first in line (A), seat 2 the next (the guest)');
check(($m['roomId'] ?? '') === $r['lobbyID'] && !empty($m['highGround']) && ($m['chatId'] ?? '') === 'l:' . $r['lobbyID'],
    'the match knows its room, is a High Ground match, and talks in the room\'s chat');
check(($m['spectatorKey'] ?? '') === (hg($r)['watchKey'] ?? '-') && strlen($m['spectatorKey'] ?? '') === 32, 'it carries the room\'s watch key');
$l = apcu_fetch($r['lobbyID']);
check(empty($l->gameName) && ($l->state ?? 'open') !== 'started' && (hg($r)['live']['matchId'] ?? '') === $mid, 'the room is not "started"; the hill is live');
check(lineNames($r) === 'C' && lineNames($r, 'hill') === 'A,G1', 'C waits, A and the guest are on the hill');

// ── 16. Joining while a match runs: welcome, back of the line ─────────────────────────────────────────────────────
echo "HG_JoinWhileLive_BackOfLine_NotRefused:\n";
$e = joinHG($r, 'G2', jar());
check(!empty($e['success']) && lineNames($r) === 'C,G2', 'a mid-match joiner lands at the back: ' . lineNames($r) . ' ' . ($e['message'] ?? ''));

// ── 17. Nobody kicks a player out of a live game ──────────────────────────────────────────────────────────────────
echo "HG_KickLivePlayerRefused:\n";
armKicks($r);
$k = hit($L . 'KickSeat.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys']['A'], 'targetPlayerID' => $r['ids']['G1']], $r['jars']['A']);
check(empty($k['success']) && str_contains(strval($k['message'] ?? ''), 'End the match'), 'refused: ' . ($k['message'] ?? ''));

// ── 18. A full evening in miniature ───────────────────────────────────────────────────────────────────────────────
echo "HG_FullEvening:\n";
// A beats the guest — through the PRODUCTION path: the engine's game-over variables, then MatchAfterActionHook (which records
// the game and calls gameResultRecorded). SWUStats is faked, never contacted.
$GLOBALS['SWUSTATS_HTTP_FAKE'] = fn($method, $url, $body) => ['status' => 200, 'body' => ['success' => true], 'raw' => '{}'];
$savedDQ = $GLOBALS['gDecisionQueueVariables'] ?? null;
$GLOBALS['gDecisionQueueVariables'] = json_encode(['GAMEOVER_WINNER' => '1', 'GAMEOVER_WINNERS' => '1']);
MatchAfterActionHook('SWUSim', $game);
$GLOBALS['gDecisionQueueVariables'] = $savedDQ;
unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
check((SWUReadMatch($mid)['state'] ?? '') === 'complete', 'the engine path recorded the match');
check(lineNames($r, 'hill') === 'A' && lineNames($r) === 'C,G2,G1', 'A holds the hill, the guest goes to the back: ' . lineNames($r));
check(wld(rowOf($r, 'A')) === '1-0-0' && wld(rowOf($r, 'G1')) === '0-1-0' && (rowOf($r, 'G1')['name'] ?? '') === 'Guest 1',
    'Tonight: A 1-0-0, Guest 1 0-1-0');
check(HGNextPair(hg($r)) === [$r['keys']['A'], $r['keys']['C']], 'next: A vs C (D in the bleachers is skipped)');
check(!isReady($r, 'A') && !isReady($r, 'G1'), 'the players are no longer Ready after the match');
cleanupMatch($mid);

// ── 19. A Bo3 2–1 counts games, and game 2 can be watched with the same key ───────────────────────────────────────
echo "HG_Bo3_TwoOne_TableByGames:\n";
act($r, 'A', 'setBestOf', ['queueType' => 'bo3']);
[$mid, $g1] = startHill($r, ['A', 'C']);
finishGame($mid, $g1, 2);
$m = SWUReadMatch($mid);
$g2 = MatchSpawnNextGameWithDecks('SWUSim', $mid, 2, $g1, [1 => $m['players']['1']['originalDeck'], 2 => $m['players']['2']['originalDeck']]);
check($g2 !== '' && SimGameReadAuthKeys('SWUSim', $g2)['spectator'] === $m['spectatorKey'], 'game 2 takes the same watch key');
finishGame($mid, $g2, 1);
$m = SWUReadMatch($mid);
$g3 = MatchSpawnNextGameWithDecks('SWUSim', $mid, 2, $g2, [1 => $m['players']['1']['originalDeck'], 2 => $m['players']['2']['originalDeck']]);
finishGame($mid, $g3, 1);
check(wld(rowOf($r, 'A')) === '3-1-0' && wld(rowOf($r, 'C')) === '1-2-0',
    'by games: A 3-1-0 (1-0 + 2-1), C 1-2-0 — ' . wld(rowOf($r, 'A')) . ' / ' . wld(rowOf($r, 'C')));
check(lineNames($r, 'hill') === 'A' && str_ends_with(lineNames($r), 'C'), 'A won the series and holds; C to the back: ' . lineNames($r));
cleanupMatch($mid);

// ── 20. A drawn match: both stay, either may step down ────────────────────────────────────────────────────────────
echo "HG_DrawnMatch_BothStay_StepDown:\n";
act($r, 'A', 'setBestOf', ['queueType' => 'bo1']);
$byKey = array_flip($r['keys']);
$next = array_map(fn($k) => $byKey[$k], HGNextPair(hg($r)));
[$mid, $g] = startHill($r, $next);
finishGame($mid, $g, 0);
check(count(hg($r)['hill']) === 2 && !empty(hg($r)['drawn']), 'both stay on the hill');
$down = $next[1];
$sd = act($r, $down, 'stepDown');
check(!empty($sd['success']) && lineNames($r, 'hill') === $next[0] && str_ends_with(lineNames($r), $down), $down . ' stepped down to the back');
check((rowOf($r, $down)['d'] ?? 0) === 1, 'the drawn game is a D on their row');
cleanupMatch($mid);

// ── 21. End match: no result anywhere, the room reopens ───────────────────────────────────────────────────────────
echo "HG_EndMatch_NoTableNoStats_RoomReopens:\n";
$next = array_map(fn($k) => $byKey[$k], HGNextPair(hg($r)));
$before = tonight($r);
[$mid, $g] = startHill($r, $next);
$gone = $next[1];
$x = act($r, 'C', 'endMatch', ['gonePlayerID' => $r['ids'][$gone]], $st);
check(empty($x['success']) && $st === 403, 'only the host ends a match (' . $st . ')');
$x = act($r, 'A', 'endMatch', ['gonePlayerID' => $r['ids'][$gone]]);
$m = SWUReadMatch($mid);
check(!empty($x['success']) && ($m['state'] ?? '') === 'abandoned' && $m['games'][0]['winner'] === null, 'the match is abandoned with no winner');
check(tonight($r) === $before && strval($m['statsStatus'] ?? '') === '', 'Tonight unchanged, nothing sent anywhere');
check(hg($r)['live'] === null && lineNames($r, 'hill') === $next[0] && str_ends_with(lineNames($r), $gone), $next[0] . ' keeps the hill, ' . $gone . ' goes to the back');
$ptr = MatchGamesDir('SWUSim') . '/' . $g . '/RoomReturn.json';
check(is_file($ptr) && (json_decode((string)@file_get_contents($ptr), true)['lobbyID'] ?? '') === $r['lobbyID'], 'the board is told to go back to the room');
$gnt = getRaw($B . 'SWUSim/GetNextTurn.php?gameName=' . urlencode($g) . '&playerID=S&folderPath=SWUSim&lastUpdate=0');
// The reply names NO room: it comes before any auth, and game names are sequential — a room id here would lead anyone to the
// room's invite code. The board already knows its room (the room page remembered it).
check(trim($gnt) === '1237ROOM', 'GetNextTurn sends every viewer back, naming no room: ' . substr($gnt, 0, 40));
cleanupMatch($mid);

// ── 22. The host plays a whole match and is still host ────────────────────────────────────────────────────────────
echo "HG_HostPlaysBo3_StillHost:\n";
$next = array_map(fn($k) => $byKey[$k], HGNextPair(hg($r)));
[$mid, $g] = startHill($r, $next);
$hostKey = $r['keys'][$byKey[$next[0]] ?? 'A'];
LobbyMutate($r['lobbyID'], function ($l) use ($r) { foreach ($l->players as $p) if ($p->getAuthKey() === $r['keys']['A']) $p->touch(time() - 200); return true; });
poll($r, 'D');
check(in_array('A', $next, true) ? intval(apcu_fetch($r['lobbyID'])->hostPlayerID) === $r['ids']['A'] : true,
    'A is on the hill (' . implode(',', $next) . ') and still host while playing a fresh game');

// ── 23. A result delivered twice is counted once ──────────────────────────────────────────────────────────────────
echo "HG_ResultAppliedOnce:\n";
$beforeGames = array_sum(array_map(fn($x) => $x['w'] + $x['l'] + $x['d'], tonight($r)));
finishGame($mid, $g, 1);
MatchHook('SWUSim', 'gameResultRecorded', SWUReadMatch($mid), $g);
poll($r, 'D'); poll($r, 'C');
$afterGames = array_sum(array_map(fn($x) => $x['w'] + $x['l'] + $x['d'], tonight($r)));
check($afterGames - $beforeGames === 2, 'one game = one W and one L, however often it arrives (' . ($afterGames - $beforeGames) . ')');
check(substr_count(',' . lineNames($r) . ',', ',' . $next[1] . ',') === 1, 'the loser is queued once: ' . lineNames($r));

// ── 23b. The end screen of a hill match is "Back to the room" — for players and watchers ──────────────────────────
echo "HG_EndScreenIsBackToRoom:\n";
$m = SWUReadMatch($mid);
$egi = getj($B . 'SWUSim/EndGameInfo.php?gameName=' . urlencode($g) . '&playerID=1&authKey=' . urlencode($m['players']['1']['authKey']));
check(($egi['origin'] ?? '') === 'highground' && ($egi['roomId'] ?? '') === $r['lobbyID'], 'the player is sent back to the room: ' . json_encode(['o' => $egi['origin'] ?? null]));
check(!array_key_exists('endScreen', $egi) && empty($egi['convertible']), 'no rematch / convert requests');
$egs = getj($B . 'SWUSim/EndGameInfo.php?gameName=' . urlencode($g) . '&playerID=S');
check(($egs['origin'] ?? '') === 'highground' && !array_key_exists('roomId', $egs), 'a watcher is sent back to the room too — but an unauthenticated read gets no room id');
check(SWUMatchAllowsSeriesChange($m) === false, 'the server refuses Rematch / Quick Rematch / Convert for a hill match');
cleanupMatch($mid);

// ── 23c. Ended while sideboarding: the sideboard screen goes back to the room ─────────────────────────────────────
echo "HG_SideboardEndedReturnsToRoom:\n";
act($r, 'A', 'setBestOf', ['queueType' => 'bo3']);
$next = array_map(fn($k) => $byKey[$k], HGNextPair(hg($r)));
[$mid, $g] = startHill($r, $next);
finishGame($mid, $g, 1);
MatchBeginSideboarding('SWUSim', $mid, 2);
$m = SWUReadMatch($mid);
$k1 = $m['players']['1']['authKey'];
$st0 = hit($B . 'SWUSim/SubmitSideboard.php', ['matchId' => $mid, 'playerID' => 1, 'authKey' => $k1, 'statusOnly' => '1']);
check(!empty($st0['success']) && !array_key_exists('roomLobbyID', $st0), 'while sideboarding, the status check says nothing');
act($r, 'A', 'endMatch', ['gonePlayerID' => $r['ids'][$next[1]]]);
$st1 = hit($B . 'SWUSim/SubmitSideboard.php', ['matchId' => $mid, 'playerID' => 1, 'authKey' => $k1, 'statusOnly' => '1']);
check(($st1['roomLobbyID'] ?? '') === $r['lobbyID'], 'once ended, the sideboard screen is told the room: ' . json_encode($st1));
$ch = curl_init($B . 'SWUSim/Sideboard.php?matchId=' . urlencode($mid) . '&playerID=1');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 1, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_TIMEOUT => 60]);
$hdr = (string)curl_exec($ch); curl_close($ch);
check((bool)preg_match('#Location: \.\./SharedUI/WaitingRoom\.php\?lobby=' . preg_quote($r['lobbyID'], '#') . '#i', $hdr), 'opening the sideboard screen redirects to the room');
check(hg($r)['live'] === null, 'the room is open for the next match');
act($r, 'A', 'setBestOf', ['queueType' => 'bo1']);
cleanupMatch($mid);

// ── 23d. A whole-match concede counts its open game and ends the hill match ───────────────────────────────────────
echo "HG_ConcedeCountsTheGame:\n";
act($r, 'A', 'setBestOf', ['queueType' => 'bo3']);
$next = array_map(fn($k) => $byKey[$k], HGNextPair(hg($r)));
[$mid, $g] = startHill($r, $next);
$before = wld(rowOf($r, $next[0]));
SWUConcedeMatch($mid, 2);   // seat 2 forfeits the Bo3 (the in-game "Forfeit Best of 3" / a block)
[$bw, $bl, $bd] = $before === '-' ? [0, 0, 0] : array_map('intval', explode('-', $before));
check(wld(rowOf($r, $next[0])) === ($bw + 1) . '-' . $bl . '-' . $bd, 'the conceded game is a W for seat 1: ' . $before . ' → ' . wld(rowOf($r, $next[0])));
check(hg($r)['live'] === null && lineNames($r, 'hill') === $next[0] && str_ends_with(lineNames($r), $next[1]), 'seat 1 holds, seat 2 to the back');
act($r, 'A', 'setBestOf', ['queueType' => 'bo1']);
cleanupMatch($mid);
foreach (array_keys($r['keys']) as $n) leave($r, $n);

// ══ Task 4: the room poll and watching ════════════════════════════════════════════════════════════════════════════
function rawPoll(array $r, string $who, string $keyOverride = null): string {
    global $L;
    return rawHit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => 0,
        'authKey' => $keyOverride ?? $r['keys'][$who]], $r['jars'][$who] ?? null);
}
$r = hgRoom(['G1', 'C', 'D']);
act($r, 'D', 'toBleachers');
[$mid, $g] = startHill($r, ['A', 'G1']);

// ── 24. Nobody's room key ever reaches anyone else ────────────────────────────────────────────────────────────────
echo "HG_PollNeverLeaksOtherKeys:\n";
$body = rawPoll($r, 'C');
$leaks = array_filter(['A', 'G1', 'D'], fn($n) => str_contains($body, $r['keys'][$n]));
check(str_contains($body, '"highGround"') && empty($leaks), 'C\'s poll carries no one else\'s key (leaked: ' . implode(',', $leaks) . ')');
$p = json_decode($body, true)['highGround'] ?? [];
check(($p['me']['playerID'] ?? 0) === $r['ids']['C'] && ($p['me']['where'] ?? '') === 'line', 'C is told where they are');
check(($p['line'] ?? null) === [$r['ids']['C']] && ($p['bleachers'] ?? null) === [$r['ids']['D']] && ($p['hill'] ?? null) === [$r['ids']['A'], $r['ids']['G1']],
    'line, bleachers and hill are playerIDs');
$names = array_column($p['members'] ?? [], 'name', 'playerID');
check(($names[$r['ids']['G1']] ?? '') === 'Guest 1' && count($names) === 4, 'every member is listed with their fixed name');
check(($p['live']['gameName'] ?? '') === $g && ($p['live']['players'] ?? null) === [$r['ids']['A'], $r['ids']['G1']]
    && intval($p['live']['gameNumber'] ?? 0) === 1, 'the live match: its game, its two players, game 1');

// ── 25. Only the two playing are pointed at the board ─────────────────────────────────────────────────────────────
echo "HG_PollRedirectsOnlyHillPlayers:\n";
$pa = json_decode(rawPoll($r, 'A'), true); $pc = json_decode(rawPoll($r, 'C'), true);
check(!empty($pa['highGround']['me']['onLiveMatch']) && intval($pa['highGround']['me']['seat'] ?? 0) === 1, 'A is told they are playing, as seat 1');
check(empty($pc['highGround']['me']['onLiveMatch']), 'C is not');
check(!array_key_exists('started', $pa) && !array_key_exists('started', $pc), 'nobody gets the plain room\'s automatic redirect');

// ── 26. Only members get the watch key ────────────────────────────────────────────────────────────────────────────
echo "HG_WatchKeyOnlyForMembers:\n";
$wk = strval(hg($r)['watchKey']);
check(($pc['highGround']['watchKey'] ?? '') === $wk, 'a member gets the watch key');
$outsider = rawPoll($r, 'C', bin2hex(random_bytes(16)));
check(!str_contains($outsider, $wk), 'someone presenting an unknown key does not');
$noKey = rawPoll($r, 'C', '');
check(!str_contains($noKey, $wk), 'nor does a visitor with no key');

// ── 27. The key rotates when someone leaves ───────────────────────────────────────────────────────────────────────
echo "HG_WatchKeyRotatesOnLeave:\n";
leave($r, 'D');
$after = strval(hg($r)['watchKey'] ?? '');
check($after !== '' && $after !== $wk, 'D left: the next match will need a new key (the live one keeps its own — see R2)');
cleanupMatch($mid);
foreach (['A', 'G1', 'C'] as $n) leave($r, $n);

// ══ Final review fixes ════════════════════════════════════════════════════════════════════════════════════════════
$r = hgRoom(['B', 'C', 'G1']);
act($r, 'G1', 'toBleachers');

// ── R4. The host keeps host however long they sit in the bleachers or watch (it passes only when they LEAVE) ──────
echo "HG_HostAwayInBleachers_StillHost:\n";
act($r, 'A', 'toBleachers');
LobbyMutate($r['lobbyID'], function ($l) use ($r) { foreach ($l->players as $p) if ($p->getAuthKey() === $r['keys']['A']) $p->touch(time() - 600); return true; });
poll($r, 'B');
check(intval(apcu_fetch($r['lobbyID'])->hostPlayerID) === $r['ids']['A'], 'A, quiet for 10 minutes in the bleachers, is still host');
act($r, 'A', 'joinLine');

// ── R2. Someone leaves mid-match: the room keeps handing out the key the LIVE match accepts ───────────────────────
echo "HG_LeaveDuringMatch_WatchKeyStillOpensLiveMatch:\n";
[$mid, $g] = startHill($r, array_map(fn($k) => array_flip($r['keys'])[$k], HGNextPair(hg($r))));
$liveKey = strval(SWUReadMatch($mid)['spectatorKey'] ?? '');
leave($r, 'G1');
$pc = poll($r, 'C');
check(($pc['highGround']['watchKey'] ?? '') === $liveKey && $liveKey !== '', 'after a leave, Watch live still gets the live match\'s key');
check(strval(hg($r)['watchKey']) !== $liveKey, 'the room\'s key for the NEXT match has rotated');

// ── R1. Nobody on the room page for 15+ minutes while the match runs: the room survives, the result lands ─────────
echo "HG_ExpiredApcuDuringLiveMatch_Survives:\n";
apcu_delete($r['lobbyID']);                                   // the APCu entry expired (LOBBY_TTL_SECONDS)
touch(SWURoomPath($r['lobbyID']), time() - 1800);             // the snapshot was last written half an hour ago
finishGame($mid, $g, 1);                                      // the game ends: the hook must still find the room
$h2 = hg($r);
check($h2 !== null && $h2['live'] === null && count($h2['recorded']) >= 1, 'the room came back from disk and the result was counted');
check(empty(apcu_fetch($r['lobbyID'])->gameName), 'and it did not turn into a "started" room');
cleanupMatch($mid);

// ── R10. End match when the match record is gone: the room still reopens ─────────────────────────────────────────
echo "HG_EndMatch_MissingMatchRecord_Reopens:\n";
[$mid, $g] = startHill($r, array_map(fn($k) => array_flip($r['keys'])[$k], HGNextPair(hg($r))));
$gone = array_flip($r['keys'])[hg($r)['live']['keys'][2]];
cleanupMatch($mid);                                           // the match record vanished
$x = act($r, 'A', 'endMatch', ['gonePlayerID' => $r['ids'][$gone]]);
check(!empty($x['success']) && hg($r)['live'] === null, 'End match still frees the hill: ' . json_encode($x));

// ── R5. The host can send a hill-holder or a bleacher to the back (or kick them) between matches ──────────────────
echo "HG_SendToBack_HostOnly:\n";
$holder = array_flip($r['keys'])[hg($r)['hill'][0] ?? ''] ?? '';
$x = act($r, 'C', 'sendToBack', ['targetPlayerID' => $r['ids'][$holder] ?? 0], $st);
check(empty($x['success']) && $st === 403, 'only the host (' . $st . ')');
$x = act($r, 'A', 'sendToBack', ['targetPlayerID' => $r['ids'][$holder] ?? 0]);
check(!empty($x['success']) && hg($r)['hill'] === [] && str_ends_with(lineNames($r), $holder), $holder . ' (holding the hill) went to the back: ' . lineNames($r));
foreach (['A', 'B', 'C'] as $n) leave($r, $n);

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
