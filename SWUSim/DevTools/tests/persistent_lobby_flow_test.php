<?php
// The persistent 1v1 room END TO END through the real endpoints: JoinQueue, SetReady, StartRoom, PollLobbyUpdates,
// EndGameInfo, EndScreenAction, AnnounceLeave. The pure rules are room_lifecycle_test.php / end_screen_requests_test.php;
// this pins what only the endpoints do — the room surviving its match, APCu being wiped, a quiet seat freeing up.
// Spec: docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md
//
// Runs in the WEB SAPI (APCu): http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/persistent_lobby_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/Rooms.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }
$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
function hit($url, $params, $jar = null) {
    $ch = curl_init($url);
    $o = [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120];
    if ($jar !== null) { $o[CURLOPT_COOKIEJAR] = $jar; $o[CURLOPT_COOKIEFILE] = $jar; }
    curl_setopt_array($ch, $o);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 400)];
}
function getj($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 400)];
}
$DECK = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));

$ROOMS = [];
register_shutdown_function(function () {
    global $ROOMS;
    foreach ($ROOMS as $r) {
        LobbyMutate($r['lobbyID'], fn($l) => 'delete');
        foreach ([SWURoomPath($r['lobbyID']), SWURoomInvitePath($r['invite'])] as $f) if ($f !== '' && is_file($f)) @unlink($f);
        foreach ($r['jars'] as $j) @unlink($j);
    }
});

// A private premier room, both seats ready, started: host claudebot1 (seat 1), a guest by invite (seat 2).
function startedRoom(): array {
    global $L, $B, $DECK, $ROOMS;
    $jh = tempnam(sys_get_temp_dir(), 'plf'); $jg = tempnam(sys_get_temp_dir(), 'plf');
    hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jh);
    $h = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier', 'queueType' => 'bo1',
        'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jh);
    $g = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => strval($h['inviteCode'] ?? ''),
        'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jg);
    $r = ['lobbyID' => strval($h['lobbyID'] ?? ''), 'invite' => strval($h['inviteCode'] ?? ''), 'jars' => [$jh, $jg],
          'k1' => strval($h['authKey'] ?? ''), 'k2' => strval($g['authKey'] ?? ''), 'jh' => $jh, 'jg' => $jg];
    $ROOMS[] = $r;
    hit($L . 'SetReady.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['k1'], 'ready' => '1'], $jh);
    hit($L . 'SetReady.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['k2'], 'ready' => '1'], $jg);
    $s = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => 1, 'authKey' => $r['k1']], $jh);
    $r['game'] = strval($s['gameName'] ?? '');
    $ref = $r['game'] !== '' ? SWUReadMatchRef($r['game']) : null;
    $r['matchId'] = is_array($ref) ? strval($ref['matchId']) : '';
    if ($r['matchId'] === '') { echo "SETUP FAILED: " . json_encode([$h, $g, $s]) . "\n"; }
    return $r;
}
function finishGame(array $r, int $winner = 1): void { MatchRecordGameResult('SWUSim', $r['matchId'], $r['game'], $winner, 5, [$winner]); }
function poll(array $r, string $key, $jar = null): array {
    global $L;
    return hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'playerID' => 0, 'authKey' => $key], $jar);
}
function pollInvite(array $r, string $key = ''): array {
    global $L;
    return hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => '', 'inviteCode' => $r['invite'], 'playerID' => 0, 'authKey' => $key]);
}
function rowFor(array $poll, string $key): ?array {
    $l = apcu_fetch($GLOBALS['__lobbyForRow'] ?? '');
    foreach (($poll['roster'] ?? []) as $i => $row) {
        if (is_object($l) && isset($l->players[$i]) && $l->players[$i]->getAuthKey() === $key) return $row;
    }
    return null;
}
$blockersText = fn(array $p) => implode(' | ', $p['blockers'] ?? []);

// ── 1. Start links the room and its match ─────────────────────────────────────────────────────────────────────────
echo "start:\n";
$r = startedRoom();
$m = SWUReadMatch($r['matchId']);
check(($m['roomId'] ?? '') === $r['lobbyID'], 'the match records the room it came from');
check(is_file(SWURoomPath($r['lobbyID'])), 'the room is saved to disk at start');
$rec = SWURoomLoad($r['lobbyID']);
check(is_array($rec) && $rec['matchId'] === $r['matchId'], 'the snapshot names the running match');

// ── 2. Mid-match the room follows the CURRENT game ────────────────────────────────────────────────────────────────
echo "poll mid-match:\n";
$p = poll($r, $r['k1'], $r['jh']);
check(!empty($p['started']) && strval($p['gameName'] ?? '') === $r['game'], 'while the match runs the room answers started, game 1');
MatchWithLock('SWUSim', $r['matchId'], function (&$mm) { $mm['games'][] = ['gameName' => 'plfake2', 'gameNumber' => 2, 'winner' => null]; });
$p = poll($r, $r['k1'], $r['jh']);
check(strval($p['gameName'] ?? '') === 'plfake2', 'in a later game of the match the room sends you to THAT game, not game 1: ' . ($p['gameName'] ?? json_encode($p)));
MatchWithLock('SWUSim', $r['matchId'], function (&$mm) { array_pop($mm['games']); });
poll($r, $r['k1'], $r['jh']);

// ── 3. The match ends: the room reopens ───────────────────────────────────────────────────────────────────────────
echo "reopen:\n";
finishGame($r);
$GLOBALS['__lobbyForRow'] = $r['lobbyID'];
$p = poll($r, $r['k1'], $r['jh']);
check(empty($p['started']) && ($p['state'] ?? '') === 'open', 'a finished match reopens the room: ' . json_encode(['state' => $p['state'] ?? null, 'started' => $p['started'] ?? null]));
$rows = $p['roster'] ?? [];
check(count($rows) === 2 && !empty($rows[0]['onEndScreen']) && !empty($rows[1]['onEndScreen']), 'both seats show as still on the end-game screen');
check(empty($rows[0]['ready']) && empty($rows[1]['ready']), 'nobody is Ready');
check(str_contains($blockersText($p), 'claudebot1 is still on the end-game screen.') && str_contains($blockersText($p), 'Guest P2 is still on the end-game screen.'),
      'Start is blocked by the end screens: ' . $blockersText($p));

// ── 4. APCu wiped: the room comes back from disk ──────────────────────────────────────────────────────────────────
echo "wipe:\n";
apcu_delete($r['lobbyID']); apcu_delete('invite:' . $r['invite']);
$p = poll($r, $r['k1'], $r['jh']);
check(!empty($p['isRoom']) && count($p['roster'] ?? []) === 2, 'a poll by room id after a wipe gets the room back: ' . json_encode(array_intersect_key($p, ['isRoom' => 1, 'gone' => 1, 'message' => 1])));
apcu_delete($r['lobbyID']); apcu_delete('invite:' . $r['invite']);
$pi = pollInvite($r, $r['k2']);
check(($pi['lobbyID'] ?? '') === $r['lobbyID'] && !empty($pi['isRoom']), 'the invite link after a wipe resolves to the same room: ' . json_encode(array_intersect_key($pi, ['lobbyID' => 1, 'gone' => 1, 'message' => 1])));

// ── 5. A quiet end screen frees its seat after 120s, not before ──────────────────────────────────────────────────
echo "quiet release:\n";
$setSeen = function (array $r, string $key, int $ago) {
    LobbyMutate($r['lobbyID'], function ($l) use ($key, $ago) { foreach ($l->players as $p) if ($p->getAuthKey() === $key) $p->touch(time() - $ago); return true; });
};
$jx = tempnam(sys_get_temp_dir(), 'plf'); $ROOMS[count($ROOMS) - 1]['jars'][] = $jx;
$setSeen($r, $r['k2'], 110);
$x = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jx);
check(empty($x['success']) && str_contains(strval($x['message'] ?? ''), 'full'), 'a third person cannot take a seat that is 110s quiet: ' . strval($x['message'] ?? json_encode($x)));
// No room poll in between: the JOIN itself must see the seat is free (nobody may be polling the room at all).
$setSeen($r, $r['k2'], 121);
$x = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jx);
check(!empty($x['success']), 'a third person can join through the invite once that seat is 121s quiet: ' . strval($x['message'] ?? ''));
$p = poll($r, $r['k1'], $r['jh']);
$keys = array_map(fn($pl) => $pl->getAuthKey(), apcu_fetch($r['lobbyID'])->players ?? []);
check(count($p['roster'] ?? []) === 2 && !in_array($r['k2'], $keys, true), 'the room is the host and the newcomer; the quiet seat is gone');
// A room poll alone releases a quiet seat too.
$setSeen($r, strval($x['authKey'] ?? ''), 0);
$r3 = startedRoom(); finishGame($r3); poll($r3, $r3['k1'], $r3['jh']);
$setSeen($r3, $r3['k2'], 121);
check(count(poll($r3, $r3['k1'], $r3['jh'])['roster'] ?? []) === 1, 'a room poll releases a 121s-quiet end screen');

// ── 6. A room nobody has touched for 15 minutes after its match ended is gone ─────────────────────────────────────
echo "room ended:\n";
$r2 = startedRoom();
finishGame($r2);
poll($r2, $r2['k1'], $r2['jh']);
apcu_delete($r2['lobbyID']); apcu_delete('invite:' . $r2['invite']);
touch(SWURoomPath($r2['lobbyID']), time() - 901);
// The match ended 901s ago too (written straight to disk: MatchWrite would restamp updatedAt with now).
$mj = SWUReadMatch($r2['matchId']); $mj['updatedAt'] = time() - 901;
file_put_contents(MatchPath('SWUSim', $r2['matchId']), json_encode($mj));
clearstatcache();
$pi = pollInvite($r2, '');
check(!empty($pi['gone']) && ($pi['message'] ?? '') === 'This room has ended.', 'the invite link says the room has ended: ' . json_encode(array_intersect_key($pi, ['gone' => 1, 'message' => 1, 'lobbyID' => 1])));

// ── 7. Quick Rematch keeps where its match came from ──────────────────────────────────────────────────────────────
echo "quick carries origin:\n";
include_once './Core/ChatConversation.php';
$r4 = startedRoom(); finishGame($r4);
SWURequestRematch($r4['matchId'], 1, 1, false); SWURequestRematch($r4['matchId'], 2, 1, false);
$q = SWUAcceptRematch($r4['matchId']);
$qm = $q ? SWUReadMatch($q) : [];
check(($qm['roomId'] ?? '') === $r4['lobbyID'], 'a Quick Rematch from a room still belongs to that room');
check(($qm['chatId'] ?? '') === 'l:' . $r4['lobbyID'], 'and keeps the room chat: ' . strval($qm['chatId'] ?? ''));
$qg = strval($qm['games'][0]['gameName'] ?? '');
check($qg !== '' && ChatScopeKey($qg) === 'l:' . $r4['lobbyID'], 'its game speaks in the room conversation: ' . ($qg !== '' ? ChatScopeKey($qg) : '-'));

// A public 1v1 match (no room). Built the way the public queue builds one: a lobby, not private, with an id.
function publicMatch(string $queueType = 'bo1', ?int $u1 = null, ?int $u2 = null): array {
    global $DECK;
    $lobby = new stdClass();
    $lobby->id = 'plpub' . bin2hex(random_bytes(4));
    $lobby->isPrivate = false; $lobby->format = 'premier'; $lobby->queueType = $queueType; $lobby->hostPlayerID = 1;
    // Seat 2's deck text differs (a trailing newline) so a test can tell whose deck came back.
    $a = new Player(1, $DECK, '', $u1); $b = new Player(2, $DECK . "\n", '', $u2);
    $lobby->players = [$a, $b];
    $mid = SWUCreateMatchFromLobby($lobby);
    $m = SWUReadMatch($mid);
    $g = strval($m['games'][0]['gameName']);
    MatchRecordGameResult('SWUSim', $mid, $g, 1, 5, [1]);
    return ['matchId' => $mid, 'game' => $g, 'lobbyID' => $lobby->id, 'k1' => $a->getAuthKey(), 'k2' => $b->getAuthKey()];
}
$pub = publicMatch('bo1');
SWURequestRematch($pub['matchId'], 1, 3, false); SWURequestRematch($pub['matchId'], 2, 3, false);
$pq = SWUAcceptRematch($pub['matchId']);
$pqm = $pq ? SWUReadMatch($pq) : [];
check(empty($pqm['roomId']) && intval($pqm['bestOf'] ?? 0) === 3, 'a public Quick Rematch (Bo3) has no room');
check(($pqm['requeue']['queueType'] ?? '') === 'bo1' && ($pqm['requeue']['format'] ?? '') === 'premier', 'and Requeue still means the ORIGINAL queue (bo1), not the rematch length: ' . json_encode($pqm['requeue'] ?? null));
// A Quick Rematch OF that Bo3 Quick Rematch: the queue is still the original bo1 one, not the Bo3 it came from.
MatchWithLock('SWUSim', $pq, function (&$mm) { $mm['state'] = 'complete'; });
SWURequestRematch($pq, 1, 1, false); SWURequestRematch($pq, 2, 1, false);
$pq2 = SWUAcceptRematch($pq);
$pq2m = $pq2 ? SWUReadMatch($pq2) : [];
check(($pq2m['requeue']['queueType'] ?? '') === 'bo1', 'a rematch of a rematch still requeues into the original queue: ' . json_encode($pq2m['requeue'] ?? null));
check(($pqm['chatId'] ?? '') === 'l:' . $pub['lobbyID'], 'and keeps the public match chat (an accepted Rematch later carries it into the room)');

// ── 8. EndGameInfo: origin, requeue, and the end screen counting as present ───────────────────────────────────────
echo "endgameinfo:\n";
$egi = fn(string $game, int $seat, string $key) => getj($B . 'SWUSim/EndGameInfo.php?' . http_build_query(['gameName' => $game, 'playerID' => $seat, 'authKey' => $key]));
$r5 = startedRoom(); finishGame($r5);
$i = $egi($r5['game'], 1, $r5['k1']);
check(($i['origin'] ?? '') === 'room' && !array_key_exists('requeue', $i), 'a room match: origin room, no requeue');
$pub2 = publicMatch('bo1');
$i1 = $egi($pub2['game'], 1, $pub2['k1']); $i2 = $egi($pub2['game'], 2, $pub2['k2']);
check(($i1['origin'] ?? '') === 'public' && ($i1['requeue']['format'] ?? '') === 'premier' && ($i1['requeue']['queueType'] ?? '') === 'bo1', 'a public match: origin public, with the queue to rejoin');
check(($i1['requeue']['deckLink'] ?? '') === $DECK && ($i2['requeue']['deckLink'] ?? '') === $DECK . "\n", 'each seat gets its own deck back');
$spec = getj($B . 'SWUSim/EndGameInfo.php?' . http_build_query(['gameName' => $pub2['game'], 'playerID' => 'S', 'authKey' => '']));
check(!array_key_exists('requeue', $spec), 'a spectator gets no requeue (no deck links)');

poll($r5, $r5['k1'], $r5['jh']);   // reopen
LobbyMutate($r5['lobbyID'], function ($l) use ($r5) { foreach ($l->players as $p) if ($p->getAuthKey() === $r5['k1']) $p->touch(time() - 1000); return true; });
sleep(6);                          // past the heartbeat's 5s throttle (the poll above already stamped this seat)
$egi($r5['game'], 1, $r5['k1']);   // the host is still on the end screen
poll($r5, $r5['k2'], $r5['jg']);   // the guest is back in the room
$hl = apcu_fetch($r5['lobbyID']);
check(is_object($hl) && intval($hl->hostPlayerID) === 1, 'a host still on the end screen keeps host (the end screen counts as present)');

// ══ End-screen requests through SWUSim/EndScreenAction.php ═══════════════════════════════════════════════════════
$act = fn(string $game, int $seat, string $key, string $action, array $extra = []) =>
    hit($B . 'SWUSim/EndScreenAction.php', ['gameName' => $game, 'playerID' => $seat, 'authKey' => $key, 'action' => $action] + $extra);
$es = fn(string $game, int $seat, string $key) => $egi($game, $seat, $key)['endScreen'] ?? null;
// The seat-0 system lines of a game's chat, in order.
$logLines = function (string $game) {
    $out = [];
    foreach (GetChatMessagesSince($game) as $row) if (intval($row['playerID'] ?? -1) === 0) $out[] = strval($row['text'] ?? '');
    return $out;
};

// ── 9. Request, decline, the 30s rule ────────────────────────────────────────────────────────────────────────────
echo "request + decline:\n";
$r6 = startedRoom(); finishGame($r6);
$a = $act($r6['game'], 1, $r6['k1'], 'request', ['kind' => 'rematch']);
check(!empty($a['success']), 'seat 1 asks for a rematch: ' . strval($a['message'] ?? ''));
$in = $es($r6['game'], 2, $r6['k2'])['incoming'] ?? null;
check(($in['kind'] ?? '') === 'rematch', 'seat 2 sees the request (its pop-up)');
$again = $act($r6['game'], 1, $r6['k1'], 'request', ['kind' => 'rematch']);
check(empty($again['success']) && str_contains(strval($again['message'] ?? ''), 'ask again in'), 'asking again straight away is refused: ' . strval($again['message'] ?? ''));
$d = $act($r6['game'], 2, $r6['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '0']);
check(!empty($d['success']) && ($es($r6['game'], 1, $r6['k1'])['declined']['kind'] ?? '') === 'rematch', 'seat 2 declines; seat 1 is told');
$lines = $logLines($r6['game']);
check(in_array('claudebot1 requested a rematch.', $lines, true) && in_array('Player 2 declined the rematch.', $lines, true), 'the game log records both: ' . json_encode($lines));
check(!empty($act($r6['game'], 1, $r6['k1'], 'request', ['kind' => 'rematch'])['success']), 'after the answer seat 1 may ask again at once');

// ── 10. Rematch accepted in a room: both back in the lobby, not Ready; the next match keeps the room chat ──────────
echo "room rematch accept:\n";
$in = $es($r6['game'], 2, $r6['k2'])['incoming'] ?? null;
$acc = $act($r6['game'], 2, $r6['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
check(($acc['go']['kind'] ?? '') === 'room' && ($acc['go']['lobbyID'] ?? '') === $r6['lobbyID'] && ($acc['go']['authKey'] ?? '') === $r6['k2'], 'the accepter is sent to the room with its own key: ' . json_encode($acc['go'] ?? $acc));
$res1 = $es($r6['game'], 1, $r6['k1'])['result'] ?? null;
check(($res1['kind'] ?? '') === 'room' && ($res1['lobbyID'] ?? '') === $r6['lobbyID'] && ($res1['authKey'] ?? '') === $r6['k1'], 'the requester\'s end screen is told the same, with ITS key');
check(in_array('Player 2 accepted the rematch.', $logLines($r6['game']), true), 'the log records the accept');
$p = poll($r6, $r6['k1'], $r6['jh']);
check(($p['state'] ?? '') === 'open' && empty($p['roster'][0]['onEndScreen']) && empty($p['roster'][1]['onEndScreen']), 'both seats are in the lobby (not on the end screen)');
check(empty($p['roster'][0]['ready']) && empty($p['roster'][1]['ready']), 'and nobody is Ready');
hit($L . 'SetReady.php', ['lobbyID' => $r6['lobbyID'], 'authKey' => $r6['k1'], 'ready' => '1'], $r6['jh']);
hit($L . 'SetReady.php', ['lobbyID' => $r6['lobbyID'], 'authKey' => $r6['k2'], 'ready' => '1'], $r6['jg']);
$s2 = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $r6['lobbyID'], 'playerID' => 1, 'authKey' => $r6['k1']], $r6['jh']);
$ref2 = SWUReadMatchRef(strval($s2['gameName'] ?? ''));
$m2 = $ref2 ? SWUReadMatch($ref2['matchId']) : [];
check(!empty($s2['success']) && ($m2['chatId'] ?? '') === 'l:' . $r6['lobbyID'] && ($m2['roomId'] ?? '') === $r6['lobbyID'], 'the next match starts from the same room, on the same chat: ' . json_encode([$s2['message'] ?? '', $m2['chatId'] ?? '']));

// ── 11. Return to Lobby alone: the other seat stays on its end screen, Start waits ───────────────────────────────
echo "return to lobby solo:\n";
$r7 = startedRoom(); finishGame($r7);
$go = $act($r7['game'], 1, $r7['k1'], 'lobby');
check(($go['go']['lobbyID'] ?? '') === $r7['lobbyID'] && ($go['go']['authKey'] ?? '') === $r7['k1'], 'Return to Lobby sends seat 1 to its room with its key');
check(in_array('claudebot1 returned to the lobby.', $logLines($r7['game']), true), 'the log says so');
$p = poll($r7, $r7['k1'], $r7['jh']);
check(empty($p['roster'][0]['onEndScreen']) && !empty($p['roster'][1]['onEndScreen']), 'in the room, seat 2 is still on the end screen');
hit($L . 'SetReady.php', ['lobbyID' => $r7['lobbyID'], 'authKey' => $r7['k1'], 'ready' => '1'], $r7['jh']);
$st = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $r7['lobbyID'], 'playerID' => 1, 'authKey' => $r7['k1']], $r7['jh']);
check(empty($st['success']) && str_contains(strval($st['message'] ?? ''), 'still on the end-game screen'), 'Start is refused while seat 2 is on the end screen: ' . strval($st['message'] ?? ''));
$v2 = $es($r7['game'], 2, $r7['k2']);
check(($v2['opponentWent'] ?? '') === 'lobby' && ($v2['kinds'] ?? null) === [], 'seat 2 sees seat 1 went to the lobby, and no rematch to ask for');
check(!empty($act($r7['game'], 2, $r7['k2'], 'lobby')['go']), 'seat 2 can follow with Return to Lobby');

// ── 12. Public match: one Rematch → one private room, the requester hosts, the chat carries over ────────────────
echo "public rematch:\n";
$pub3 = publicMatch();
$act($pub3['game'], 1, $pub3['k1'], 'request', ['kind' => 'rematch']);
$in = $es($pub3['game'], 2, $pub3['k2'])['incoming'] ?? null;
$acc = $act($pub3['game'], 2, $pub3['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
$room = apcu_fetch(strval($acc['go']['lobbyID'] ?? ''));
check(is_object($room) && $room->id === $pub3['lobbyID'] && !empty($room->isPrivate), 'accepting builds a private room under the public pairing\'s id (so its chat IS the match chat): ' . strval($acc['go']['lobbyID'] ?? json_encode($acc)));
$res1 = $es($pub3['game'], 1, $pub3['k1'])['result'] ?? null;
$hostKey = '';
if (is_object($room)) foreach ($room->players as $pl) if (intval($pl->getPlayerID()) === intval($room->hostPlayerID)) $hostKey = $pl->getAuthKey();
check($hostKey !== '' && $hostKey === strval($res1['authKey'] ?? ''), 'the requester (seat 1) is the host');
check(is_object($room) && count($room->players) === 2 && !$room->players[0]->getReady() && !$room->players[1]->getReady() && $room->players[0]->getDeckOk(), 'two seats with their decks, nobody Ready');
check(is_file(SWURoomPath($pub3['lobbyID'])), 'the new room is saved to disk');
$ROOMS[] = ['lobbyID' => $pub3['lobbyID'], 'invite' => is_object($room) ? strval($room->inviteCode) : '', 'jars' => []];

echo "public rematch crossing:\n";
$pub4 = publicMatch();
$mh = curl_multi_init(); $hs = [];
foreach ([1 => $pub4['k1'], 2 => $pub4['k2']] as $s => $key) {
    $h = curl_init($B . 'SWUSim/EndScreenAction.php');
    curl_setopt_array($h, [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120,
        CURLOPT_POSTFIELDS => http_build_query(['gameName' => $pub4['game'], 'playerID' => $s, 'authKey' => $key, 'action' => 'request', 'kind' => 'rematch'])]);
    curl_multi_add_handle($mh, $h); $hs[] = $h;
}
do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
foreach ($hs as $h) curl_multi_remove_handle($mh, $h);
$x1 = $es($pub4['game'], 1, $pub4['k1'])['result'] ?? null; $x2 = $es($pub4['game'], 2, $pub4['k2'])['result'] ?? null;
$room4 = apcu_fetch($pub4['lobbyID']);
$seated = is_object($room4) ? array_map(fn($pl) => $pl->getAuthKey(), $room4->players) : [];
check(($x1['lobbyID'] ?? 'a') === ($x2['lobbyID'] ?? 'b') && in_array(strval($x1['authKey'] ?? ''), $seated, true) && in_array(strval($x2['authKey'] ?? ''), $seated, true) && count($seated) === 2,
      'two Rematch clicks at once make ONE room holding both players: ' . json_encode([$x1, $x2, count($seated)]));
$ROOMS[] = ['lobbyID' => $pub4['lobbyID'], 'invite' => is_object($room4) ? strval($room4->inviteCode) : '', 'jars' => []];

// ── 13. Quick Rematch in a room; Convert to Bo3 ──────────────────────────────────────────────────────────────────
echo "quick accept:\n";
$r8 = startedRoom(); finishGame($r8);
$act($r8['game'], 1, $r8['k1'], 'request', ['kind' => 'quick', 'bestOf' => '1']);
$in = $es($r8['game'], 2, $r8['k2'])['incoming'] ?? null;
check(($in['kind'] ?? '') === 'quick' && intval($in['bestOf'] ?? 0) === 1, 'the pop-up says Quick Rematch, Best of 1');
$act($r8['game'], 2, $r8['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
$ng = json_decode(strval(@file_get_contents('./SWUSim/Games/' . $r8['game'] . '/NextGame.json')), true);
$newGame = strval($ng['nextGameName'] ?? '');
$nref = $newGame !== '' ? SWUReadMatchRef($newGame) : null;
$nm = $nref ? SWUReadMatch($nref['matchId']) : [];
check($newGame !== '' && ($nm['roomId'] ?? '') === $r8['lobbyID'], 'both are sent into a new game that still belongs to the room');
$rl = apcu_fetch($r8['lobbyID']);
check(is_object($rl) && ($rl->state ?? '') === 'started' && strval($rl->gameName ?? '') === $newGame && strval($rl->roomMatchId ?? '') === strval($nref['matchId'] ?? ''), 'the room follows the new match (seats held, invite link refuses)');
check(is_object($rl) && SWURoomEndScreenNames($rl) === [], 'and nobody in the room reads as on the end screen while it runs: ' . json_encode(is_object($rl) ? SWURoomEndScreenNames($rl) : null));
check(in_array('claudebot1 requested a quick rematch (Best of 1).', $logLines($r8['game']), true), 'the log names the length');

echo "convert accept:\n";
$pub5 = publicMatch();
$act($pub5['game'], 1, $pub5['k1'], 'request', ['kind' => 'convert']);
$in = $es($pub5['game'], 2, $pub5['k2'])['incoming'] ?? null;
check(($in['kind'] ?? '') === 'convert', 'seat 2 gets the Convert to Best of 3 pop-up');
$act($pub5['game'], 2, $pub5['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
$cm = SWUReadMatch($pub5['matchId']);
check(intval($cm['bestOf'] ?? 0) === 3 && ($cm['state'] ?? '') === 'sideboarding', 'accepted: the match is a Bo3, sideboarding for game 2');
check(empty($egi($pub5['game'], 1, $pub5['k1'])['convertible']), 'and is no longer convertible (the end screen rebuilds for the Bo3)');
check(in_array('Player 1 requested to convert to Best of 3.', $logLines($pub5['game']), true) && in_array('Player 2 accepted the convert to Best of 3.', $logLines($pub5['game']), true), 'the log records both');

// ── 14. Requeue, rated ───────────────────────────────────────────────────────────────────────────────────────────
echo "requeue:\n";
$pub6 = publicMatch();
$rq = $act($pub6['game'], 1, $pub6['k1'], 'requeue');
check(!empty($rq['success']), 'seat 1 requeues');
$v2 = $es($pub6['game'], 2, $pub6['k2']);
check(($v2['opponentWent'] ?? '') === 'requeue' && ($v2['kinds'] ?? null) === [], 'seat 2 sees it, and Rematch is gone');
check(in_array('Player 1 requeued.', $logLines($pub6['game']), true), 'the log says so');
$nl = $act($pub6['game'], 2, $pub6['k2'], 'lobby');
check(empty($nl['success']) && ($nl['message'] ?? '') === 'This match did not come from a room.', 'Return to Lobby is refused after a public match, saying why: ' . strval($nl['message'] ?? ''));

echo "rated:\n";
$pub7 = publicMatch();
MatchWithLock('SWUSim', $pub7['matchId'], function (&$mm) { $mm['format'] = 'metapremier'; });
check(empty($act($pub7['game'], 1, $pub7['k1'], 'request', ['kind' => 'rematch'])['success']), 'a rated match refuses a rematch request');
check(($es($pub7['game'], 1, $pub7['k1'])['kinds'] ?? null) === [], 'and offers nothing to ask for');

// ── 15. Return to Menu frees the room seat; a request to someone who left can't be accepted ────────────────────
echo "menu releases seat:\n";
$leave = fn(string $game, int $seat, string $key) => hit($B . 'SWUSim/AnnounceLeave.php', ['gameName' => $game, 'playerID' => $seat, 'authKey' => $key]);
$r9 = startedRoom(); finishGame($r9);
$act($r9['game'], 1, $r9['k1'], 'lobby');
$leave($r9['game'], 2, $r9['k2']);
$p = poll($r9, $r9['k1'], $r9['jh']);
check(count($p['roster'] ?? []) === 1, 'seat 2 going to the menu frees its seat in the room');
check(($es($r9['game'], 1, $r9['k1'])['opponentWent'] ?? '') === 'menu', 'seat 1\'s end screen (if still open) says seat 2 went to the menu');
$jy = tempnam(sys_get_temp_dir(), 'plf'); $ROOMS[count($ROOMS) - 1]['jars'][] = $jy;
$y = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r9['invite'], 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jy);
check(!empty($y['success']), 'a third person joins through the invite link');
hit($L . 'SetReady.php', ['lobbyID' => $r9['lobbyID'], 'authKey' => $r9['k1'], 'ready' => '1'], $r9['jh']);
hit($L . 'SetReady.php', ['lobbyID' => $r9['lobbyID'], 'authKey' => strval($y['authKey'] ?? ''), 'ready' => '1'], $jy);
$st = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $r9['lobbyID'], 'playerID' => 1, 'authKey' => $r9['k1']], $r9['jh']);
check(!empty($st['success']), 'and the host can start with them: ' . strval($st['message'] ?? ''));

echo "stale accept after menu:\n";
$r10 = startedRoom(); finishGame($r10);
$act($r10['game'], 1, $r10['k1'], 'request', ['kind' => 'rematch']);
$in = $es($r10['game'], 2, $r10['k2'])['incoming'] ?? null;
$leave($r10['game'], 1, $r10['k1']);
$late = $act($r10['game'], 2, $r10['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
check(empty($late['success']) && !isset($late['go']), 'accepting a request from someone who went to the menu fails cleanly: ' . strval($late['message'] ?? ''));
$rl = apcu_fetch($r10['lobbyID']);
check(is_object($rl) && count($rl->players) === 1, 'and the room is not rebuilt around the leaver');

// ── 16. The host picks Bo1 / Bo3 in the room ────────────────────────────────────────────────────────────────────
echo "bo3 switch:\n";
$qt = fn(array $r, string $key, string $v, $jar) => hit($L . 'UpdateLobbyQueueType.php', ['rootName' => 'SWUSim', 'lobbyID' => $r['lobbyID'], 'authKey' => $key, 'queueType' => $v], $jar);
$r11 = startedRoom(); finishGame($r11);
$act($r11['game'], 1, $r11['k1'], 'lobby'); $act($r11['game'], 2, $r11['k2'], 'lobby');
hit($L . 'SetReady.php', ['lobbyID' => $r11['lobbyID'], 'authKey' => $r11['k2'], 'ready' => '1'], $r11['jg']);
$no = $qt($r11, $r11['k2'], 'bo3', $r11['jg']);
check(empty($no['success']), 'the guest cannot change the match length: ' . strval($no['message'] ?? ''));
$yes = $qt($r11, $r11['k1'], 'bo3', $r11['jh']);
check(!empty($yes['success']) && ($yes['queueType'] ?? '') === 'bo3', 'the host switches to Best of 3');
$rs = SWURoomLoad($r11['lobbyID']);
check(is_array($rs) && ($rs['lobby']->queueType ?? '') === 'bo3', 'the room on disk remembers Best of 3 (an APCu wipe keeps it)');
$p = poll($r11, $r11['k2'], $r11['jg']);
check(($p['seatModel']['queueType'] ?? '') === 'bo3', 'everyone in the room sees Best of 3');
check(empty($p['roster'][1]['ready']), 'the change un-readies the guest (they agreed to a Bo1)');
check(empty($qt($r11, $r11['k1'], 'bo7', $r11['jh'])['success']), 'an unknown length is refused');
hit($L . 'SetReady.php', ['lobbyID' => $r11['lobbyID'], 'authKey' => $r11['k1'], 'ready' => '1'], $r11['jh']);
hit($L . 'SetReady.php', ['lobbyID' => $r11['lobbyID'], 'authKey' => $r11['k2'], 'ready' => '1'], $r11['jg']);
$st = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $r11['lobbyID'], 'playerID' => 1, 'authKey' => $r11['k1']], $r11['jh']);
$sref = SWUReadMatchRef(strval($st['gameName'] ?? ''));
check($sref && intval(SWUReadMatch($sref['matchId'])['bestOf'] ?? 0) === 3, 'the next match is a Best of 3');

// ══ Final-review fixes ═══════════════════════════════════════════════════════════════════════════════════════════
// ── 17. A long match: nothing touches the room while it is played, and APCu forgot the lobby long ago ─────────────
echo "long match:\n";
$r12 = startedRoom();
touch(SWURoomPath($r12['lobbyID']), time() - 3600); clearstatcache();
apcu_delete($r12['lobbyID']); apcu_delete('invite:' . $r12['invite']);
finishGame($r12);
$egi($r12['game'], 2, $r12['k2']);   // seat 2's end screen (its heartbeat)
$go = $act($r12['game'], 1, $r12['k1'], 'lobby');
check(($go['go']['lobbyID'] ?? '') === $r12['lobbyID'], 'after an hour-long match Return to Lobby still lands in the room: ' . strval($go['message'] ?? ''));
$p = poll($r12, $r12['k1'], $r12['jh']);
check(count($p['roster'] ?? []) === 2 && !empty($p['roster'][1]['onEndScreen']), 'both seats are still there, seat 2 on its end screen');

// ── 18. Convert to Bo3 inside a room: the room follows the Bo3, nobody's seat is given away at its end ────────────
echo "convert in a room:\n";
$r13 = startedRoom(); finishGame($r13);
$egi($r13['game'], 1, $r13['k1']); $egi($r13['game'], 2, $r13['k2']);   // the room reopens behind the end screens
$act($r13['game'], 1, $r13['k1'], 'request', ['kind' => 'convert']);
$in = $es($r13['game'], 2, $r13['k2'])['incoming'] ?? null;
$act($r13['game'], 2, $r13['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
$rl = apcu_fetch($r13['lobbyID']);
check(is_object($rl) && ($rl->state ?? '') === 'started', 'the converted Bo3 is running: the room is started again (seats held)');
$jz = tempnam(sys_get_temp_dir(), 'plf'); $ROOMS[count($ROOMS) - 1]['jars'][] = $jz;
LobbyMutate($r13['lobbyID'], function ($l) { foreach ($l->players as $pl) $pl->touch(time() - 1000); return true; });
$z = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r13['invite'], 'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jz);
check(empty($z['success']), 'nobody can take a seat through the invite during the Bo3: ' . strval($z['message'] ?? ''));
MatchWithLock('SWUSim', $r13['matchId'], function (&$mm) {   // the Bo3 ends 2-0
    $mm['games'][] = ['gameName' => 'plfakeg2', 'gameNumber' => 2, 'winner' => 1];
    $mm['wins']['1'] = 2; $mm['state'] = 'complete'; $mm['winner'] = 1;
    unset($mm['sideboard'], $mm['sideboardDeadline'], $mm['pendingFirstPlayer']);
});
sleep(6);   // past the heartbeat throttle from the polls above
$egi($r13['game'], 1, $r13['k1']);   // seat 1's end screen after the Bo3
$rl = apcu_fetch($r13['lobbyID']);
$keys = is_object($rl) ? array_map(fn($pl) => $pl->getAuthKey(), $rl->players) : [];
check(in_array($r13['k2'], $keys, true) && ($rl->state ?? '') === 'open', 'when the Bo3 ends the room reopens with BOTH seats (seat 2 is not released as quiet)');

// ── 19. A Quick Rematch from a room keeps ONE conversation, sideboard included ─────────────────────────────────────
echo "quick rematch chat:\n";
$r14 = startedRoom(); finishGame($r14);
$act($r14['game'], 1, $r14['k1'], 'request', ['kind' => 'quick', 'bestOf' => '3']);
$in = $es($r14['game'], 2, $r14['k2'])['incoming'] ?? null;
$act($r14['game'], 2, $r14['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
$qm14 = SWUReadMatch(strval(apcu_fetch($r14['lobbyID'])->roomMatchId ?? ''));
check(is_array($qm14) && ChatConversationForGame(strval($qm14['matchId'])) === 'l:' . $r14['lobbyID'],
      'the new match itself (its sideboard) speaks in the room conversation: ' . strval(is_array($qm14) ? ChatConversationForGame(strval($qm14['matchId'])) : '-'));

// ── 20. The room on disk keeps up with what happens in it; a room everyone left stays gone ─────────────────────────
echo "snapshot keeps up:\n";
$r15 = startedRoom(); finishGame($r15);
$act($r15['game'], 1, $r15['k1'], 'lobby');
hit($L . 'SetReady.php', ['lobbyID' => $r15['lobbyID'], 'authKey' => $r15['k1'], 'ready' => '1'], $r15['jh']);
poll($r15, $r15['k1'], $r15['jh']);
$snap = SWURoomLoad($r15['lobbyID']);
$readyOnDisk = false;
if ($snap) foreach ($snap['lobby']->players as $pl) if ($pl->getAuthKey() === $r15['k1']) $readyOnDisk = $pl->getReady();
check($readyOnDisk, 'a change in the room (Ready) reaches the disk copy, so an APCu wipe does not undo it');
$act($r15['game'], 2, $r15['k2'], 'lobby');
foreach ([[$r15['k1'], $r15['jh']], [$r15['k2'], $r15['jg']]] as [$k, $j]) hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'playerID' => 0, 'lobbyID' => $r15['lobbyID'], 'authKey' => $k], $j);
apcu_delete($r15['lobbyID']); apcu_delete('invite:' . $r15['invite']);
$pi = pollInvite($r15, '');
check(!empty($pi['gone']) && empty($pi['isRoom']), 'a room both players left does not come back from disk: ' . json_encode(array_intersect_key($pi, ['gone' => 1, 'message' => 1, 'isRoom' => 1])));
$pub8 = publicMatch();
$act($pub8['game'], 1, $pub8['k1'], 'request', ['kind' => 'rematch']);
$in = $es($pub8['game'], 2, $pub8['k2'])['incoming'] ?? null;
$acc8 = $act($pub8['game'], 2, $pub8['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
$ROOMS[] = ['lobbyID' => $pub8['lobbyID'], 'invite' => strval(apcu_fetch($pub8['lobbyID'])->inviteCode ?? ''), 'jars' => []];
touch(SWURoomPath($pub8['lobbyID']), time() - 1000); clearstatcache();
hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $pub8['lobbyID'], 'playerID' => 0, 'authKey' => strval($acc8['go']['authKey'] ?? '')]);
clearstatcache();
check(time() - intval(@filemtime(SWURoomPath($pub8['lobbyID']))) < 30, 'a room made from a public match is kept alive by its own polls (before any match starts from it)');

// ── 21. A blocked pair can't send each other requests ────────────────────────────────────────────────────────────
echo "blocked:\n";
require_once './Database/functions.inc.php';
$BA = 990051; $BB = 990052;
$bconn = GetLocalMySQLConnection();
$bconn->query("DELETE FROM blocklist WHERE blockingPlayer IN ($BA,$BB) OR blockedPlayer IN ($BA,$BB)");
AddBlock($BA, $BB);
$pub9 = publicMatch('bo1', $BA, $BB);
$br = $act($pub9['game'], 1, $pub9['k1'], 'request', ['kind' => 'rematch']);
check(empty($br['success']), 'a blocked pair cannot ask each other for a rematch');
check(($es($pub9['game'], 2, $pub9['k2'])['incoming'] ?? null) === null && ($es($pub9['game'], 1, $pub9['k1'])['kinds'] ?? null) === [], 'nothing pops up, and nothing is offered');
check(!in_array('Player 1 requested a rematch.', $logLines($pub9['game']), true), 'and nothing is logged');
$bconn->query("DELETE FROM blocklist WHERE blockingPlayer IN ($BA,$BB) OR blockedPlayer IN ($BA,$BB)");

// ── 22. A rematch that cannot start says so to both players ──────────────────────────────────────────────────────
echo "failed start:\n";
$r16 = startedRoom(); finishGame($r16);
$act($r16['game'], 1, $r16['k1'], 'request', ['kind' => 'rematch']);
$in = $es($r16['game'], 2, $r16['k2'])['incoming'] ?? null;
apcu_delete($r16['lobbyID']); apcu_delete('invite:' . $r16['invite']); @unlink(SWURoomPath($r16['lobbyID']));   // the room is gone
$f = $act($r16['game'], 2, $r16['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
check(empty($f['success']) && !isset($f['go']), 'accepting a rematch whose room is gone fails cleanly');
check(($es($r16['game'], 1, $r16['k1'])['result']['kind'] ?? '') === 'failed', 'and the requester is TOLD (not a silent nothing)');

// ══ Deferred minors (2026-10-30) ═════════════════════════════════════════════════════════════════════════════════
// ── 23. Requeue is for a FINISHED match ──────────────────────────────────────────────────────────────────────────
echo "requeue mid-match:\n";
$pub10 = publicMatch();
MatchWithLock('SWUSim', $pub10['matchId'], function (&$mm) { $mm['state'] = 'in_progress'; });
$rq = $act($pub10['game'], 1, $pub10['k1'], 'requeue');
check(empty($rq['success']) && ($rq['message'] ?? '') === 'The match is not over yet.', 'a requeue while the match is still being played is refused: ' . strval($rq['message'] ?? ''));
check(!in_array('Player 1 requeued.', $logLines($pub10['game']), true), 'and nothing is logged');
check(!isset(SWUReadMatch($pub10['matchId'])['endScreen']['departed']['1']), 'and seat 1 is not marked as gone');

// ── 24. The match length is a PRIVATE room's choice ──────────────────────────────────────────────────────────────
echo "bo switch public lobby:\n";
$ql = new stdClass();
$ql->id = 'plpubq' . bin2hex(random_bytes(4)); $ql->rootName = 'SWUSim'; $ql->format = 'premier'; $ql->queueType = 'bo1';
$ql->isPrivate = false; $ql->maxPlayers = 2; $ql->numPlayers = 1; $ql->hostPlayerID = 1; $ql->state = 'open'; $ql->ready = false;
$qp = new Player(1, $DECK, '', null); $ql->players = [$qp];
apcu_store($ql->id, $ql, LOBBY_TTL_SECONDS);
$ROOMS[] = ['lobbyID' => $ql->id, 'invite' => '', 'jars' => []];
$qres = $qt(['lobbyID' => $ql->id], $qp->getAuthKey(), 'bo3', null);
check(empty($qres['success']) && (apcu_fetch($ql->id)->queueType ?? '') === 'bo1', 'the "host" of a public queue lobby cannot move it to the Best of 3 queue: ' . strval($qres['message'] ?? ''));

// ── 25. The end-screen poll leaves out the stats table it does not draw ───────────────────────────────────────────
echo "light poll:\n";
$pub11 = publicMatch();
$full = $egi($pub11['game'], 1, $pub11['k1']);
$lite = getj($B . 'SWUSim/EndGameInfo.php?' . http_build_query(['gameName' => $pub11['game'], 'playerID' => 1, 'authKey' => $pub11['k1'], 'poll' => '1']));
check(array_key_exists('statsHtml', $full), 'the end screen\'s first load still gets the stats table');
check(!array_key_exists('statsHtml', $lite), 'the poll (poll=1) does not');
check(($lite['endScreen'] ?? null) === ($full['endScreen'] ?? 'x') && ($lite['origin'] ?? '') === 'public' && !empty($lite['seriesOver']) && isset($lite['winnerNames']),
      'and still carries everything the poll draws');

// ── 26. Saving rooms clears out long-dead ones (at most once an hour) ────────────────────────────────────────────────
echo "reap on save:\n";
$dead = new stdClass(); $dead->id = 'plreap' . bin2hex(random_bytes(4)); $dead->inviteCode = bin2hex(random_bytes(12)); $dead->players = [];
SWURoomSave($dead);
touch(SWURoomPath($dead->id), time() - LOBBY_TTL_SECONDS - 86400 - 60); clearstatcache();
$ROOMS[] = ['lobbyID' => $dead->id, 'invite' => $dead->inviteCode, 'jars' => []];
apcu_delete('swu_rooms_reap');   // the hourly throttle
$r17 = startedRoom(); finishGame($r17);
$act($r17['game'], 1, $r17['k1'], 'lobby');   // saves the room
clearstatcache();
check(!is_file(SWURoomPath($dead->id)) && !is_file(SWURoomInvitePath($dead->inviteCode)), 'a room save removed a room that ended over a day ago');
SWURoomSave($dead); touch(SWURoomPath($dead->id), time() - LOBBY_TTL_SECONDS - 86400 - 60); clearstatcache();
$act($r17['game'], 2, $r17['k2'], 'lobby');   // saves again, within the hour
clearstatcache();
check(is_file(SWURoomPath($dead->id)), 'and does not scan again within the hour');

// ── 27. A DRAWN match (CR 6.3) reopens the room, and its Quick Rematch picks first player at random ─────────────────
echo "drawn match:\n";
$quickFrom = function (array $r) use ($act, $es): string {   // Quick Rematch Bo1, accepted; returns the new game's name
    $act($r['game'], 1, $r['k1'], 'request', ['kind' => 'quick', 'bestOf' => '1']);
    $in = $es($r['game'], 2, $r['k2'])['incoming'] ?? null;
    $act($r['game'], 2, $r['k2'], 'answer', ['id' => strval($in['id'] ?? ''), 'accept' => '1']);
    $ng = json_decode(strval(@file_get_contents('./SWUSim/Games/' . $r['game'] . '/NextGame.json')), true);
    return strval($ng['nextGameName'] ?? '');
};
// Who chooses who opens game 1 of the rematch: 'roll' = a random roll's winner, 'loser' = the prior game's loser.
$chooserReason = function (string $game): string {
    $gs = strval(@file_get_contents('./SWUSim/Games/' . $game . '/Gamestate.txt'));
    return preg_match('/InitiativeChoice\|\d\|(roll|loser)/', $gs, $mm) ? $mm[1] : '?';
};
$r18 = startedRoom();
MatchRecordGameDraw('SWUSim', $r18['matchId'], $r18['game'], 5);
$m18 = SWUReadMatch($r18['matchId']);
check(($m18['state'] ?? '') === 'complete' && !empty($m18['matchDraw']), 'a drawn Bo1 completes the room\'s match as a draw');
$GLOBALS['__lobbyForRow'] = $r18['lobbyID'];
$p = poll($r18, $r18['k1'], $r18['jh']);
check(empty($p['started']) && ($p['state'] ?? '') === 'open', 'a drawn match reopens the room: ' . json_encode(['state' => $p['state'] ?? null, 'started' => $p['started'] ?? null]));
$g18 = $quickFrom($r18);
check($g18 !== '' && $chooserReason($g18) === 'roll', 'a Quick Rematch after a draw opens with a RANDOM roll, not a forced seat: ' . $chooserReason($g18));
$r19 = startedRoom(); finishGame($r19, 1);
$g19 = $quickFrom($r19);
check($g19 !== '' && $chooserReason($g19) === 'loser', 'NEGATIVE: after a decided game the loser still chooses: ' . $chooserReason($g19));

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
