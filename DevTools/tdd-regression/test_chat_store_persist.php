<?php
// Saved chat (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md): disk is the record, APCu
// is the fast path. Every check here works on temp dirs, so the real SWUSim/Chats and SWUSim/Games are never touched.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 DevTools/tdd-regression/test_chat_store_persist.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
require_once __DIR__ . '/../../Core/NetworkingLibraries.php';

$FAILS = 0; $CHECKS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS, $CHECKS;
    $CHECKS++;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function rrm($d) {
    if (!file_exists($d)) return;
    if (is_file($d) || is_link($d)) { @unlink($d); return; }
    foreach (scandir($d) as $f) if ($f !== '.' && $f !== '..') rrm("$d/$f");
    @rmdir($d);
}
function wipe($key) { apcu_delete('chat_' . $key); apcu_delete('chat_version_' . $key); }
function ids(array $rows) { return array_map(fn($r) => intval($r['id']), $rows); }

if (!function_exists('apcu_enabled') || !apcu_enabled()) { echo "FAIL: test_chat_store_persist — APCu off (run with -d apc.enable_cli=1)\n"; exit(1); }

$tmp = sys_get_temp_dir() . '/chatstore-' . bin2hex(random_bytes(4));
mkdir("$tmp/chats", 0777, true); mkdir("$tmp/games/4242", 0777, true);
putenv("SWUSIM_CHAT_DIR=$tmp/chats");
putenv("SWUSIM_CHAT_GAMES_DIR=$tmp/games");
foreach (['l:testroom1', 'l:testroom2', 'l:testroom3', 'l:testroom4', 'l:garoom', 'l:big', 'l:conc', 'l:nodisk', '4242'] as $k) wipe($k);

ChatPersistUseRoot('SWUSim');
check(ChatPersistActive(), 'SWUSim switches persistence on');

echo "── chat persists to disk ──\n";
foreach (['a', 'b', 'c'] as $t) ChatAppendMessage('l:testroom1', 1, 'alice', $t);
$saved = ChatSavedHistory('l:testroom1');
check(ids($saved) === [1, 2, 3], 'three rows saved in id order', ids($saved));
check(count(array_filter($saved, fn($r) => ($r['src'] ?? '') === 'room:testroom1')) === 3, 'each saved row is tagged with its room (src=room:<lobbyID>)');
check(ChatSourceTag('m:M42') === 'sideboard:M42' && ChatSourceTag('4242') === 'game:4242', 'a sideboard line names its match; a game line names its game', [ChatSourceTag('m:M42'), ChatSourceTag('4242')]);
$live = GetChatMessagesSince('l:testroom1', 0);
check(count($live) === 3 && !array_key_exists('src', $live[0]), 'the live (APCu) rows carry no src', $live[0] ?? null);
check(is_file(ChatStorePath('l:testroom1')) && strpos(ChatStorePath('l:testroom1'), "$tmp/chats/") === 0, 'a room conversation lives in the chat dir');

echo "── survives a full APCu wipe ──\n";
wipe('l:testroom1');
$reloaded = GetChatMessagesSince('l:testroom1', 0);
check(ids($reloaded) === [1, 2, 3], 'history comes back from disk');
check(!array_key_exists('src', $reloaded[0] ?? []), 'reloaded rows carry no src either', $reloaded[0] ?? null);
check(GetChatUpdateVersion('l:testroom1') >= 3, 'the version never goes backwards', GetChatUpdateVersion('l:testroom1'));
wipe('l:testroom1');
check(ChatAppendMessage('l:testroom1', 2, 'bob', 'd') === 4, 'the next append after a wipe gets id 4');
check(GetChatUpdateVersion('l:testroom1') >= 4, 'and the version is at least that id (a wiped counter would restart at 1)', GetChatUpdateVersion('l:testroom1'));
check(ids(ChatSavedHistory('l:testroom1')) === [1, 2, 3, 4], 'disk holds all four');

echo "── survives losing only one of the two keys ──\n";
foreach (['x', 'y'] as $t) ChatAppendMessage('l:testroom2', 1, 'alice', $t);
$v0 = GetChatUpdateVersion('l:testroom2');
apcu_delete('chat_l:testroom2');                               // messages gone, version kept
check(ChatAppendMessage('l:testroom2', 1, 'alice', 'z') === 3, 'messages key lost: the next id continues');
check(GetChatUpdateVersion('l:testroom2') > $v0, 'messages key lost: the version still goes up');
foreach (['x', 'y'] as $t) ChatAppendMessage('l:testroom3', 1, 'alice', $t);
apcu_delete('chat_version_l:testroom3');                       // version gone, messages kept
check(GetChatUpdateVersion('l:testroom3') >= 2, 'version key lost: a read restores it', GetChatUpdateVersion('l:testroom3'));
ChatAppendMessage('l:testroom3', 1, 'alice', 'z');
check(GetChatUpdateVersion('l:testroom3') >= 3, 'version key lost: an append moves it past the last id', GetChatUpdateVersion('l:testroom3'));
check(HasChatUpdate('l:testroom3', 2), 'a client that saw version 2 sees the new message');

echo "── an id written to APCu alone never collides with a saved one ──\n";
foreach (['x', 'y', 'z'] as $t) ChatAppendMessage('l:testroom4', 1, 'alice', $t);
wipe('l:testroom4');
ChatPersistUseRoot('');                                         // a request that never switched persistence on
ChatAppendMessage('l:testroom4', 1, 'alice', 'apcu only');      // → APCu restarts at id 1
ChatPersistUseRoot('SWUSim');
check(ChatAppendMessage('l:testroom4', 1, 'alice', 'saved') === 4, 'the next saved id continues from disk, not from APCu');

echo "── keeps full history beyond 100 ──\n";
for ($i = 1; $i <= 150; $i++) ChatAppendMessage('l:big', 1, 'alice', "m$i");
check(count(ChatSavedHistory('l:big')) === 150, 'disk keeps all 150');
wipe('l:big');
$back = GetChatMessagesSince('l:big', 0);
check(count($back) === 100 && intval($back[0]['id']) === 51 && intval(end($back)['id']) === 150, 'APCu is refilled with the last 100 (51..150)', [count($back), $back[0]['id'] ?? null]);

echo "── whisper redaction survives a reload ──\n";
ChatAppendMessage('4242', 1, 'alice', 'secret plan', [2]);
check(strpos(ChatStorePath('4242'), "$tmp/games/4242/Chat.jsonl") === 0, 'a game conversation lives in its game folder', ChatStorePath('4242'));
wipe('4242');
$spec = GetChatMessagesSince('4242', 0, ['viewerID' => 'S', 'viewerSeat' => 0, 'isSpectator' => true]);
check(count($spec) === 1 && $spec[0]['text'] === '' && !empty($spec[0]['redacted']), 'a non-party sees the whisper redacted after a reload', $spec);
$party = GetChatMessagesSince('4242', 0, ['viewerID' => '2', 'viewerSeat' => 2, 'isSpectator' => false]);
check(($party[0]['text'] ?? '') === 'secret plan', 'the recipient still reads it');
$disk = ChatSavedHistory('4242');
check(($disk[0]['text'] ?? '') === 'secret plan' && ($disk[0]['src'] ?? '') === 'game:4242' && ($disk[0]['to'] ?? null) === [2], 'disk keeps the real text, recipients and src', $disk);

echo "── an unwritable store falls back to APCu ──\n";
file_put_contents("$tmp/notadir", 'x');
putenv("SWUSIM_CHAT_DIR=$tmp/notadir");
$id = ChatAppendMessage('l:nodisk', 1, 'alice', 'still works');
check($id > 0, 'the append still returns an id', $id);
check(count(GetChatMessagesSince('l:nodisk', 0)) === 1, 'and the APCu read returns it');
putenv("SWUSIM_CHAT_DIR=$tmp/chats");

echo "── a read for a game that does not exist writes nothing to APCu ──\n";
apcu_delete('chat_version_88880001'); apcu_delete('chat_88880001');
GetChatUpdateVersion('88880001'); GetChatMessagesSince('88880001', 0);
check(apcu_fetch('chat_version_88880001') === false && apcu_fetch('chat_88880001') === false, 'an unauthenticated poll of a made-up game number caches nothing (no APCu fill)');
mkdir("$tmp/games/88880002", 0777, true);
apcu_delete('chat_version_88880002');
GetChatUpdateVersion('88880002');
check(apcu_fetch('chat_version_88880002') === 0, 'a real game with no chat caches its empty version, so its polls stay off the disk');
apcu_delete('chat_version_88880002');

echo "── other sims are untouched ──\n";
ChatPersistUseRoot('GrandArchiveSim');
check(!ChatPersistActive(), 'another root switches persistence off');
ChatAppendMessage('l:garoom', 1, 'gauser', 'hello');
check(!is_file("$tmp/chats/l_garoom.jsonl") && count(glob("$tmp/chats/*garoom*")) === 0, 'no file is written for another sim');
check(count(GetChatMessagesSince('l:garoom', 0)) === 1, 'its chat still works through APCu');
ChatPersistUseRoot('SWUSim');

echo "── concurrent appends ──\n";
$pids = [];
for ($c = 0; $c < 2; $c++) {
    $pid = pcntl_fork();
    if ($pid === 0) { for ($i = 0; $i < 20; $i++) ChatAppendMessage('l:conc', $c + 1, "p$c", "c$c-$i"); exit(0); }
    $pids[] = $pid;
}
foreach ($pids as $p) pcntl_waitpid($p, $st);
$cids = ids(ChatSavedHistory('l:conc'));
$sorted = $cids; sort($sorted);
check(count($cids) === 40 && count(array_unique($cids)) === 40 && $sorted === range(1, 40), 'two writers × 20 → ids 1..40, all unique', $cids);

foreach (['l:testroom1', 'l:testroom2', 'l:testroom3', 'l:testroom4', 'l:garoom', 'l:big', 'l:conc', 'l:nodisk', '4242'] as $k) wipe($k);
rrm($tmp);
echo $FAILS === 0 ? "PASS: test_chat_store_persist — all $CHECKS checks passed\n" : "FAIL: test_chat_store_persist — $FAILS failed\n";
exit($FAILS === 0 ? 0 : 1);
