<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_chat_persist_e2e.php
// Saved chat through the REAL endpoints: a private room's conversation survives an APCu wipe, and its ids carry on.
// Web SAPI only (it wipes the same APCu Apache serves from). It writes into the real SWUSim/Chats store — the
// endpoints are separate requests, so a temp dir set here would not reach them — and deletes what it created.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../../Core/NetworkingLibraries.php';

$B = 'http://localhost/TCGEngine/';   // in-container loopback
$FAILS = 0; $CHECKS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS, $CHECKS;
    $CHECKS++;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function req($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 40, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch); curl_close($ch); return (string)$r;
}
$jar1 = tempnam(sys_get_temp_dir(), 'cp');
req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar1, ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass']);

$deck = trim(file_get_contents(__DIR__ . '/../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'));
$host = json_decode(req($B . 'APIs/Lobbies/JoinQueue.php', $jar1,
    ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns', 'deckLink' => $deck]), true);
check(!empty($host['success']), 'created a private room', $host);
if (empty($host['success'])) { echo "\nFAIL: test_chat_persist_e2e — cannot continue\n"; exit; }
$lobbyID = $host['lobbyID']; $k1 = $host['authKey'];
$scope = ['lobbyID' => $lobbyID, 'playerID' => 1, 'authKey' => $k1];
$send = fn($text, $folder = 'SWUSim') => trim(req($B . 'SubmitChat.php?' . http_build_query($scope + ['folderPath' => $folder, 'chatText' => $text]), $jar1));
$read = fn() => json_decode(req($B . 'GetChat.php?' . http_build_query($scope + ['folderPath' => 'SWUSim', 'lastChatID' => 0]), $jar1), true);

ChatPersistUseRoot('SWUSim');
$file = ChatStorePath('l:' . $lobbyID);

echo "── a room's chat is saved by SubmitChat ──\n";
check($send('first') === 'OK' && $send('second') === 'OK', 'two messages sent');
check(is_file($file) && count(ChatSavedHistory('l:' . $lobbyID)) === 2, 'both are saved to the room file', $file);

echo "── it survives an APCu wipe, through GetChat ──\n";
apcu_delete('chat_l:' . $lobbyID); apcu_delete('chat_version_l:' . $lobbyID);
$back = $read();
$texts = array_map(fn($m) => $m['text'], is_array($back) ? $back : []);
check($texts === ['first', 'second'], 'GetChat returns both after the wipe', $back);
check(is_array($back) && !array_key_exists('src', $back[0] ?? []), 'the live rows have no src');

echo "── ids carry on ──\n";
apcu_delete('chat_l:' . $lobbyID); apcu_delete('chat_version_l:' . $lobbyID);
check($send('third') === 'OK', 'a third message after another wipe');
$ids = array_map(fn($r) => intval($r['id']), ChatSavedHistory('l:' . $lobbyID));
check($ids === [1, 2, 3], 'it was saved as id 3', $ids);

echo "── another sim's request writes nothing ──\n";
$before = count(ChatSavedHistory('l:' . $lobbyID));
$r = $send('from another sim', 'GrandArchiveSim');
check($r === 'OK', 'the GrandArchiveSim request reached the append (otherwise this check proves nothing)', $r);
check(count(ChatSavedHistory('l:' . $lobbyID)) === $before, 'and saved nothing');

req($B . 'APIs/Lobbies/LeaveQueue.php', $jar1, ['rootName' => 'SWUSim', 'lobbyID' => $lobbyID, 'playerID' => 1, 'authKey' => $k1]);
apcu_delete('chat_l:' . $lobbyID); apcu_delete('chat_version_l:' . $lobbyID);
@unlink($file); @unlink($file . '.lock');
echo $FAILS === 0 ? "\nPASS: test_chat_persist_e2e — all $CHECKS checks passed\n" : "\nFAIL: test_chat_persist_e2e — $FAILS failed\n";
