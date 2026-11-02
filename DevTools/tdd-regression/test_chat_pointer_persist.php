<?php
// Saved chat, part 2: the "which conversation does this game belong to" pointer (Core/ChatConversation.php) is saved
// beside the chat, so a game still finds its room/match conversation after APCu loses the 24h sidecar.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 DevTools/tdd-regression/test_chat_pointer_persist.php
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
    if (is_file($d)) { @unlink($d); return; }
    foreach (scandir($d) as $f) if ($f !== '.' && $f !== '..') rrm("$d/$f");
    @rmdir($d);
}
if (!function_exists('apcu_enabled') || !apcu_enabled()) { echo "FAIL: test_chat_pointer_persist — APCu off\n"; exit(1); }

$tmp = sys_get_temp_dir() . '/chatptr-' . bin2hex(random_bytes(4));
mkdir("$tmp/chats", 0777, true); mkdir("$tmp/games/5151", 0777, true);
putenv("SWUSIM_CHAT_DIR=$tmp/chats");
putenv("SWUSIM_CHAT_GAMES_DIR=$tmp/games");
foreach (['5150', '5151', '5152', '5153'] as $g) apcu_delete(ChatSidecarKey($g));

ChatPersistUseRoot('SWUSim');
echo "── the pointer survives an APCu wipe ──\n";
ChatPublishConversationForGame('5150', 'l:deadbeefcafe');
check(is_file("$tmp/chats/pointers/5150.txt"), 'publishing writes the pointer file');
apcu_delete(ChatSidecarKey('5150'));
check(ChatScopeKey('5150') === 'l:deadbeefcafe', 'the game still resolves to its room after the wipe', ChatScopeKey('5150'));
check(apcu_fetch(ChatSidecarKey('5150')) === 'l:deadbeefcafe', 'and APCu is refilled');

echo "── a missing pointer is negatively cached ──\n";
check(ChatConversationForGame('5151') === null, 'a game with no pointer has no conversation');
check(apcu_fetch(ChatSidecarKey('5151')) === '', 'the miss is cached as an empty string', apcu_fetch(ChatSidecarKey('5151')));
check(ChatScopeKey('5151') === '5151', 'and the game keeps its own legacy stream');

apcu_delete(ChatSidecarKey('77770001'));
check(ChatConversationForGame('77770001') === null && apcu_fetch(ChatSidecarKey('77770001')) === false, 'a made-up game number caches no miss (an unauthenticated read must not fill APCu)');
apcu_delete(ChatSidecarKey('M77770001'));
check(ChatConversationForGame('M77770001') === null && apcu_fetch(ChatSidecarKey('M77770001')) === false, 'nor does a made-up match id');

echo "── a bad stored pointer is ignored ──\n";
@mkdir("$tmp/chats/pointers", 0777, true);
file_put_contents("$tmp/chats/pointers/5153.txt", '../x');
check(ChatConversationForGame('5153') === null, 'a stored value that is not a conversation id is refused');

echo "── another sim writes no pointer file ──\n";
ChatPersistUseRoot('GrandArchiveSim');
ChatPublishConversationForGame('5152', 'l:gaaaaaaaa');
check(!is_file("$tmp/chats/pointers/5152.txt"), 'no file for another sim');
check(ChatConversationForGame('5152') === 'l:gaaaaaaaa', 'its APCu pointer still works');
apcu_delete(ChatSidecarKey('5152'));
check(ChatConversationForGame('5152') === null && apcu_fetch(ChatSidecarKey('5152')) === false, 'and after a wipe it behaves exactly as before (no disk, no sentinel)');

foreach (['5150', '5151', '5152', '5153'] as $g) apcu_delete(ChatSidecarKey($g));
rrm($tmp);
echo $FAILS === 0 ? "PASS: test_chat_pointer_persist — all $CHECKS checks passed\n" : "FAIL: test_chat_pointer_persist — $FAILS failed\n";
exit($FAILS === 0 ? 0 : 1);
