<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_chat_match_stream.php
// A match created from a lobby must put every one of its games on the LOBBY'S chat stream, so the
// conversation that started in the waiting room continues into game 1, the sideboard and game 2.
//
// ⚠ AND A MATCH THAT PREDATES THIS FEATURE MUST NOT MOVE. A record with no 'chatId' is every match
// alive at deploy; its games must keep resolving to the legacy per-game bucket.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Core/NetworkingLibraries.php';
require_once __DIR__ . '/../../Core/ChatConversation.php';
require_once __DIR__ . '/../../Core/Match/Match.php';
require_once __DIR__ . '/../../Core/Match/MatchFlow.php';

$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}

$players = ['1' => ['originalDeck' => [], 'authKey' => 'k1'], '2' => ['originalDeck' => [], 'authKey' => 'k2']];

echo "── a match created WITH a conversation id ──\n";
$mid = MatchCreate('SWUSim', 'twinsuns', 'bo1', $players, true, 'l:testlobby123');
$m   = MatchRead('SWUSim', $mid);
check(($m['chatId'] ?? null) === 'l:testlobby123', 'MatchCreate stores the chatId', $m['chatId'] ?? null);

MatchWriteRef('SWUSim', '990001', $mid, 1);
MatchWriteRef('SWUSim', '990002', $mid, 2);
check(ChatScopeKey('990001') === 'l:testlobby123', 'game 1 resolves to the lobby stream', ChatScopeKey('990001'));
check(ChatScopeKey('990002') === 'l:testlobby123', 'game 2 resolves to the SAME stream', ChatScopeKey('990002'));
check(GetChatMessagesCacheKey('990002') === GetChatMessagesCacheKey('990001'),
      'both games share one bucket — a Bo3 no longer resets its chat');

echo "── a LEGACY match, with no chatId, does not move ──\n";
$legacyId = MatchCreate('SWUSim', 'premier', 'bo3', $players, false);
$legacy   = MatchRead('SWUSim', $legacyId);
check(!isset($legacy['chatId']), 'no chatId is stored when none is supplied', $legacy['chatId'] ?? '(absent)');
MatchWriteRef('SWUSim', '990003', $legacyId, 1);
check(ChatScopeKey('990003') === '990003', 'its game keeps the legacy per-game stream', ChatScopeKey('990003'));
check(GetChatMessagesCacheKey('990003') === 'chat_990003', 'and the legacy cache key');

echo "── MatchCreateFromLobby stamps the lobby id ──\n";
// The lobby object only needs the fields MatchCreateFromLobby reads.
$lobby = new stdClass();
$lobby->id = 'abc123lobby'; $lobby->format = 'twinsuns'; $lobby->queueType = 'bo1'; $lobby->isPrivate = true;
check(ChatLobbyConversationId($lobby) === 'l:abc123lobby', 'the lobby id becomes l:<id>',
      function_exists('ChatLobbyConversationId') ? ChatLobbyConversationId($lobby) : '(no such function)');

echo "── a REMATCH has no lobby, so it opens its own stream ──\n";
// A rematch is the lobby-less case: its conversation is keyed on the new match itself. Without this
// a rematch Bo3 would fall back to a fresh bucket per game — the reset this whole feature removes.
// Built with sideboard=true so MatchAcceptRematch takes the MatchBeginSideboarding branch and does
// not spawn a real game through the sim's setupGame hook.
$oldId = MatchCreate('SWUSim', 'premier', 'bo1', $players, false);
MatchWithLock('SWUSim', $oldId, function (&$mm) {
    $mm['state'] = 'complete';
    $mm['games'] = [['gameName' => '990004', 'gameNumber' => 1, 'winner' => 1]];
    $mm['rematchRequests'] = ['1' => ['bestOf' => 3, 'sideboard' => true],
                              '2' => ['bestOf' => 3, 'sideboard' => true]];
});
$newId = MatchAcceptRematch('SWUSim', $oldId);
check($newId !== null, 'the rematch was accepted', $newId);
$newM = $newId !== null ? MatchRead('SWUSim', $newId) : [];
check(($newM['chatId'] ?? null) === 'm:' . $newId, 'the rematch opens m:<matchId>', $newM['chatId'] ?? null);
MatchWriteRef('SWUSim', '990005', $newId, 1);
check(ChatScopeKey('990005') === 'm:' . $newId, 'its game resolves to the rematch stream', ChatScopeKey('990005'));
foreach ([$oldId, $newId] as $id) { if ($id !== null) @unlink(MatchPath('SWUSim', $id)); }
foreach (['990004','990005'] as $g) {
    @unlink(MatchRefPath('SWUSim', $g)); @unlink(MatchSideboardPointerPath('SWUSim', $g));
    @rmdir(dirname(MatchRefPath('SWUSim', $g)));
    if (function_exists('apcu_delete')) apcu_delete(ChatSidecarKey($g));
}

foreach (['990001','990002','990003'] as $g) {
    @unlink(MatchRefPath('SWUSim', $g));
    @rmdir(dirname(MatchRefPath('SWUSim', $g)));
    if (function_exists('apcu_delete')) apcu_delete(ChatSidecarKey($g));
}
foreach ([$mid, $legacyId] as $id) { @unlink(MatchPath('SWUSim', $id)); }
echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
