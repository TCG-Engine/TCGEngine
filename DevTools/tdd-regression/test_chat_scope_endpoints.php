<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_chat_scope_endpoints.php
// SubmitChat/GetChat over the two NEW scopes, through the real endpoints.
//
// ⚠ AUTH IS THE POINT OF THIS TEST. A lobby's authKey is the only thing standing between a stranger
// and a private room's conversation, and each scope authenticates against a different store.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../APIs/Lobbies/Classes/Player.php';

$B = 'http://localhost/TCGEngine/';   // in-container loopback
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function req($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 40,
                            CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch); curl_close($ch); return (string)$r;
}
$jar1 = tempnam(sys_get_temp_dir(), 'cs'); $jar2 = tempnam(sys_get_temp_dir(), 'cs');
req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar1, ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass']);
req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar2, ['submit' => '1', 'userID' => 'claudebot2', 'password' => 'pass']);
$anon = tempnam(sys_get_temp_dir(), 'cs');

// A real private Twin Suns room, so the lobby scope has a real authKey to check against.
$deck = trim(file_get_contents(__DIR__ . '/../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'));
$host = json_decode(req($B . 'APIs/Lobbies/JoinQueue.php', $jar1,
    ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns', 'deckLink' => $deck]), true);
check(!empty($host['success']), 'created a private twinsuns room', $host);
if (empty($host['success'])) { echo "\nCANNOT CONTINUE\n"; exit; }
$lobbyID = $host['lobbyID']; $k1 = $host['authKey'];

echo "── the LOBBY scope ──\n";
$send = function ($jar, $key, $text) use ($B, $lobbyID) {
    return req($B . 'SubmitChat.php?' . http_build_query(
        ['lobbyID' => $lobbyID, 'playerID' => 1, 'authKey' => $key, 'folderPath' => 'SWUSim', 'chatText' => $text]), $jar);
};
check(trim($send($jar1, $k1, 'hello from the room')) === 'OK', 'a seated, logged-in player may send', trim($send($jar1, $k1, 'probe')));
check(trim($send($jar1, 'wrongkey', 'nope')) !== 'OK', 'a wrong authKey is refused');
check(trim($send($anon, $k1, 'guest')) === 'Log in to chat.', 'a guest is refused with the policy wording', trim($send($anon, $k1, 'guest')));

$read = json_decode(req($B . 'GetChat.php?' . http_build_query(
    ['lobbyID' => $lobbyID, 'lastChatID' => 0, 'playerID' => 1, 'authKey' => $k1, 'folderPath' => 'SWUSim']), $jar1), true);
$texts = array_map(fn($m) => $m['text'], is_array($read) ? $read : []);
check(in_array('hello from the room', $texts, true), 'GetChat returns the lobby stream', $texts);

check(json_decode(req($B . 'GetChat.php?' . http_build_query(
    ['lobbyID' => $lobbyID, 'lastChatID' => 0, 'playerID' => 1, 'authKey' => 'wrongkey', 'folderPath' => 'SWUSim']), $jar1), true) === [],
    'a lobby READ with a wrong authKey returns nothing — a room is not public');

echo "── whispers are GAME-ONLY ──\n";
$w = req($B . 'SubmitChat.php?' . http_build_query(
    ['lobbyID' => $lobbyID, 'playerID' => 1, 'authKey' => $k1, 'folderPath' => 'SWUSim',
     'chatText' => 'psst', 'whisperTo' => '2']), $jar1);
// ⚠ ASSERT THE EXACT STRING, not merely "refused, and the word whisper appears". Measured by
// mutation 2026-09-22: deleting the scope gate outright leaves the request refused anyway — it falls
// through to ChatWhisperAllowed(), which cannot resolve 'l:<id>' as a game and answers "Whisper not
// allowed." A loose check passed with the guard REMOVED, i.e. it was pinning nothing.
check(trim($w) === 'Whispers are only available in a game.',
      'a whisper outside a game is refused BY THE SCOPE GATE', trim($w));

echo "── refusals ──\n";
check(trim(req($B . 'SubmitChat.php?' . http_build_query(
    ['lobbyID' => '../etc', 'playerID' => 1, 'authKey' => $k1, 'folderPath' => 'SWUSim', 'chatText' => 'x']), $jar1)) !== 'OK',
    'a path-shaped lobbyID is refused');
check(trim(req($B . 'SubmitChat.php?' . http_build_query(
    ['playerID' => 1, 'authKey' => $k1, 'folderPath' => 'SWUSim', 'chatText' => 'x']), $jar1)) !== 'OK',
    'no scope at all is refused');
check(trim(req($B . 'SubmitChat.php?' . http_build_query(
    ['lobbyID' => $lobbyID, 'gameName' => '4242', 'playerID' => 1, 'authKey' => $k1, 'folderPath' => 'SWUSim', 'chatText' => 'x']), $jar1)) !== 'OK',
    'two scopes at once is refused as ambiguous');

req($B . 'APIs/Lobbies/LeaveQueue.php', $jar1, ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID, 'playerID' => 1, 'authKey' => $k1]);
echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
