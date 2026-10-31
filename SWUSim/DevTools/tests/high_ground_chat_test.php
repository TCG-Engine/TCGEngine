<?php
// High Ground chat — ONE conversation for the room and its matches, anyone in the room may chat (owner), guests may not
// (owner: "guests can join but they cannot chat"), and a spectator of an ordinary game still cannot (the anti-goal).
// Spec: docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md ("Chat").
// Runs in the WEB SAPI (APCu): http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/high_ground_chat_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/HighGround.php';
require_once './Core/GameAuth.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }
$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
$JARS = [];
function req(string $url, ?array $post, ?string $jar, array $cookies = []): string {
    $ch = curl_init($url);
    $o = [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120];
    if ($post !== null) { $o[CURLOPT_POST] = 1; $o[CURLOPT_POSTFIELDS] = http_build_query($post); }
    if ($jar !== null) { $o[CURLOPT_COOKIEJAR] = $jar; $o[CURLOPT_COOKIEFILE] = $jar; }
    if (!empty($cookies)) $o[CURLOPT_COOKIE] = implode('; ', array_map(fn($k, $v) => $k . '=' . $v, array_keys($cookies), $cookies));
    curl_setopt_array($ch, $o);
    $r = curl_exec($ch); curl_close($ch);
    return (string)$r;
}
function jreq(string $url, ?array $post, ?string $jar): array { $j = json_decode(req($url, $post, $jar), true); return is_array($j) ? $j : []; }
function jar(string $user = ''): string {
    global $B, $JARS;
    $j = tempnam(sys_get_temp_dir(), 'hgc'); $JARS[] = $j;
    if ($user !== '') req($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $j);
    return $j;
}
$DECK = trim(file_get_contents('./SWUSim/DevTools/tests/fixtures/premier_deck.txt'));
$ROOMS = []; $MATCHES = [];
register_shutdown_function(function () {
    global $ROOMS, $MATCHES, $JARS;
    foreach ($ROOMS as $id) { LobbyMutate($id, fn($l) => 'delete'); if (is_file(SWURoomPath($id))) @unlink(SWURoomPath($id)); }
    foreach ($MATCHES as $m) {   // the match and its games' folders (their pointer files would mislead a reused game number)
        if ($m === '') continue;
        $rec = SWUReadMatch($m);
        foreach ((is_array($rec) ? $rec['games'] : []) as $g) { $gn = preg_replace('/[^A-Za-z0-9_]/', '', strval($g['gameName'] ?? '')); if ($gn !== '') exec('rm -rf ' . escapeshellarg(MatchGamesDir('SWUSim') . '/' . $gn)); }
        exec('rm -rf ' . escapeshellarg(MatchesDir('SWUSim') . '/' . $m));
    }
    foreach ($JARS as $j) @unlink($j);
});
function say(array $q, string $text, ?string $jar, array $cookies = []): string {
    global $B;
    return trim(req($B . 'SubmitChat.php?' . http_build_query($q + ['folderPath' => 'SWUSim', 'chatText' => $text]), null, $jar, $cookies));
}
function roomChat(string $lobbyID, int $playerID, string $key, ?string $jar): array {
    global $B;
    $j = json_decode(req($B . 'GetChat.php?' . http_build_query(['lobbyID' => $lobbyID, 'playerID' => $playerID, 'authKey' => $key,
        'folderPath' => 'SWUSim', 'lastChatID' => 0]), null, $jar), true);
    return is_array($j) ? $j : [];
}
// The board writes id='chatComposer' (single quotes) — match either quote, or a negative can never fail.
function hasComposer(string $html): bool { return (bool)preg_match('/id=[\'"]chatComposer[\'"]/', $html); }
function labelOf(array $msgs, string $text): ?string { foreach ($msgs as $m) if (($m['text'] ?? '') === $text) return strval($m['playerLabel'] ?? ''); return null; }

// The High Ground room: A (claudebot1, host), four guests, then B (claudebot2, lobby playerID 6) and C (claudebot3, 7).
$jA = jar('claudebot1');
$h = jreq($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'highGround' => '1', 'format' => 'premier', 'queueType' => 'bo1',
    'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jA);
$lobbyID = strval($h['lobbyID'] ?? ''); $ROOMS[] = $lobbyID;
$who = ['A' => ['id' => intval($h['playerID'] ?? 0), 'key' => strval($h['authKey'] ?? ''), 'jar' => $jA]];
foreach (['G1' => '', 'G2' => '', 'G3' => '', 'G4' => '', 'B' => 'claudebot2', 'C' => 'claudebot3'] as $n => $user) {
    $jj = jar($user);
    $x = jreq($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => strval($h['inviteCode'] ?? ''), 'deckLink' => $DECK,
        'preconstructedDeck' => '', 'game_type' => ''], $jj);
    $who[$n] = ['id' => intval($x['playerID'] ?? 0), 'key' => strval($x['authKey'] ?? ''), 'jar' => $jj];
}
check($who['C']['id'] > 4 && $who['B']['id'] > 4, 'setup: B and C hold lobby playerIDs above 4 (' . $who['B']['id'] . ', ' . $who['C']['id'] . ')');
$name = fn($n) => strval(apcu_fetch($lobbyID)->highGround['names'][$who[$n]['key']] ?? '?');

echo "RoomMember_PlayerIDAbove4_CanChat:\n";
$r = say(['lobbyID' => $lobbyID, 'playerID' => $who['B']['id'], 'authKey' => $who['B']['key']], 'hello from B', $who['B']['jar']);
check($r === 'OK', 'B (lobby playerID ' . $who['B']['id'] . ') sends in the room: ' . $r);
check(labelOf(roomChat($lobbyID, $who['A']['id'], $who['A']['key'], $jA), 'hello from B') === $name('B'), 'it reads as B\'s name, not a seat label');

// Start the hill match: A vs the first guest.
foreach (['A', 'G1'] as $n) req($L . 'SetReady.php', ['lobbyID' => $lobbyID, 'authKey' => $who[$n]['key'], 'ready' => '1'], $who[$n]['jar']);
$s = jreq($B . 'SWUSim/HighGroundAction.php', ['lobbyID' => $lobbyID, 'authKey' => $who['A']['key'], 'action' => 'start'], $jA);
$game = strval($s['gameName'] ?? ''); $MATCHES[] = strval($s['matchId'] ?? '');
check($game !== '', 'setup: the hill match started');
$watchKey = strval(apcu_fetch($lobbyID)->highGround['watchKey'] ?? '');
$hgCookie = 'tcg_hg_' . $lobbyID;

echo "Watcher_OnBoard_CanChat_SameConversation:\n";
$r = say(['gameName' => $game, 'playerID' => 'S', 'authKey' => $watchKey], 'C watching', $who['C']['jar'], [$hgCookie => $who['C']['key']]);
check($r === 'OK', 'C, watching on the board, sends: ' . $r);
check(labelOf(roomChat($lobbyID, $who['B']['id'], $who['B']['key'], $who['B']['jar']), 'C watching') === $name('C'),
    'the room sees it in the same conversation, under C\'s name');
$board = req($B . 'NextTurn.php?' . http_build_query(['gameName' => $game, 'playerID' => 'S', 'authKey' => $watchKey, 'folderPath' => 'SWUSim']),
    null, $who['C']['jar'], [$hgCookie => $who['C']['key']]);
check(strlen($board) > 10000 && hasComposer($board), 'the board gives a logged-in member watcher a message box');

echo "HillPlayer_LabelIsName:\n";
$r = say(['gameName' => $game, 'playerID' => 1, 'authKey' => $who['A']['key']], 'A on the hill', $jA);
check($r === 'OK' && labelOf(roomChat($lobbyID, $who['B']['id'], $who['B']['key'], $who['B']['jar']), 'A on the hill') === $name('A'),
    'A playing sends under A\'s name: ' . $r);

echo "Guest_Refused_LogInToChat:\n";
$r = say(['lobbyID' => $lobbyID, 'playerID' => $who['G2']['id'], 'authKey' => $who['G2']['key']], 'guest in room', $who['G2']['jar']);
check($r === 'Log in to chat.', 'a guest in the room: ' . $r);
$r = say(['gameName' => $game, 'playerID' => 'S', 'authKey' => $watchKey], 'guest watching', $who['G3']['jar'], [$hgCookie => $who['G3']['key']]);
check($r === 'Log in to chat.', 'a guest watching the board: ' . $r);
$gboard = req($B . 'NextTurn.php?' . http_build_query(['gameName' => $game, 'playerID' => 'S', 'authKey' => $watchKey, 'folderPath' => 'SWUSim']),
    null, $who['G3']['jar'], [$hgCookie => $who['G3']['key']]);
check(str_contains($gboard, 'chatGuestNote') && !hasComposer($gboard), 'a guest watcher sees "Log in to chat", no message box');

echo "NonMember_CookieForgedName_Refused:\n";
$jD = jar('claudebot4');
$r = say(['gameName' => $game, 'playerID' => 'S', 'authKey' => $watchKey], 'outsider', $jD, [$hgCookie => bin2hex(random_bytes(16))]);
check($r === 'Spectators cannot chat.', 'a non-member with a made-up room cookie: ' . $r);

echo "Whisper_FromH_Refused:\n";
$r = req($B . 'SubmitChat.php?' . http_build_query(['gameName' => $game, 'playerID' => 'S', 'authKey' => $watchKey, 'folderPath' => 'SWUSim',
    'chatText' => 'psst', 'whisperTo' => '1']), null, $who['C']['jar'], [$hgCookie => $who['C']['key']]);
check(trim($r) === 'Whispers are for seated players.', 'a watcher cannot whisper: ' . trim($r));

echo "NormalPrivateGame_Spectator_StillRefused:\n";
// An ordinary private room (claudebot4 + a guest), started; B watches it with its spectator key AND a forged room cookie.
$n = jreq($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier', 'queueType' => 'bo1',
    'deckLink' => $DECK, 'preconstructedDeck' => '', 'game_type' => ''], $jD);
$nid = strval($n['lobbyID'] ?? ''); $ROOMS[] = $nid;
$jg = jar();
$ng = jreq($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => strval($n['inviteCode'] ?? ''), 'deckLink' => $DECK,
    'preconstructedDeck' => '', 'game_type' => ''], $jg);
req($L . 'SetReady.php', ['lobbyID' => $nid, 'authKey' => $n['authKey'] ?? '', 'ready' => '1'], $jD);
req($L . 'SetReady.php', ['lobbyID' => $nid, 'authKey' => $ng['authKey'] ?? '', 'ready' => '1'], $jg);
$ns = jreq($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $nid, 'playerID' => 1, 'authKey' => $n['authKey'] ?? ''], $jD);
$ngame = strval($ns['gameName'] ?? '');
$ref = $ngame !== '' ? SWUReadMatchRef($ngame) : null; $MATCHES[] = is_array($ref) ? strval($ref['matchId']) : '';
$spec = $ngame !== '' ? strval(SimGameReadAuthKeys('SWUSim', $ngame)['spectator'] ?? '') : '';
check($ngame !== '' && $spec !== '', 'setup: an ordinary private game with a spectator key');
$r = say(['gameName' => $ngame, 'playerID' => 'S', 'authKey' => $spec], 'not allowed', $who['B']['jar'], ['tcg_hg_' . $nid => $who['B']['key']]);
check($r === 'Spectators cannot chat.', 'a logged-in spectator of an ordinary game is still refused: ' . $r);
$nboard = req($B . 'NextTurn.php?' . http_build_query(['gameName' => $ngame, 'playerID' => 'S', 'authKey' => $spec, 'folderPath' => 'SWUSim']),
    null, $who['B']['jar'], ['tcg_hg_' . $nid => $who['B']['key']]);
check(strlen($nboard) > 10000 && !hasComposer($nboard) && !str_contains($nboard, 'chatGuestNote'),
    'and its board renders no message box and no notice');

foreach ($who as $n => $w) req($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID, 'playerID' => $w['id'], 'authKey' => $w['key']], $w['jar']);
echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
