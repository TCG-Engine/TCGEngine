<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_chat_conversation_scope.php
// The conversation-id truth table. Web SAPI only — APCu does not exist in the CLI SAPI.
//
// The load-bearing case is LEGACY COMPATIBILITY: a numeric gameName with no sidecar must produce
// EXACTLY the pre-existing key "chat_<gameName>". That is what makes this change need no migration —
// every game and match alive at deploy keeps its history.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Core/NetworkingLibraries.php';
require_once __DIR__ . '/../../Core/ChatConversation.php';

$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}

echo "── legacy compatibility ──\n";
check(ChatScopeKey('4242') === '4242', 'a bare gameName with no sidecar resolves to itself', ChatScopeKey('4242'));
check(GetChatMessagesCacheKey('4242') === 'chat_4242', 'the legacy message key is byte-identical', GetChatMessagesCacheKey('4242'));
check(GetChatVersionCacheKey('4242')  === 'chat_version_4242', 'the legacy version key is byte-identical', GetChatVersionCacheKey('4242'));

echo "── explicit scopes ──\n";
check(ChatScopeKey('l:6ab2d364f08e2') === 'l:6ab2d364f08e2', 'a lobby token passes through');
check(ChatScopeKey('m:M1801')         === 'm:M1801',         'a match token passes through');
check(GetChatMessagesCacheKey('l:6ab2d364f08e2') === 'chat_l:6ab2d364f08e2', 'a lobby message key');

echo "── the sidecar redirects a game to its match stream ──\n";
ChatPublishConversationForGame('5150', 'l:deadbeefcafe');
check(ChatConversationForGame('5150') === 'l:deadbeefcafe', 'the sidecar reads back', ChatConversationForGame('5150'));
check(ChatScopeKey('5150') === 'l:deadbeefcafe', 'a game with a sidecar resolves to the lobby stream');
check(GetChatMessagesCacheKey('5150') === 'chat_l:deadbeefcafe', 'and so does its cache key');
check(ChatConversationForGame('5151') === null, 'an unrelated game has no sidecar');

echo "── refusals ──\n";
foreach (['', 'x:1', '../etc/passwd', 'l:has space', 'l:' . str_repeat('a', 300), 'chat_4242', 'L:ABC'] as $bad) {
    check(ChatScopeKey($bad) === null, "refuses '" . substr($bad, 0, 24) . "'");
}
check(GetChatMessagesCacheKey('../etc/passwd') === null, 'an unusable token yields a null key, never a path');

// The sidecar is test residue; drop it so a later run of another suite cannot inherit it.
if (function_exists('apcu_delete')) apcu_delete(ChatSidecarKey('5150'));

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
