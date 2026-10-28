<?php
// Chat conversation scoping — which APCu bucket a chat message belongs to.
//
// Chat used to be keyed by gameName alone, which made it unreachable from any screen without a
// numeric game: the Waiting Room (a hex lobbyID, and no game at all) and the Sideboard (a matchId).
// It also reset a Bo3's conversation at every game. A conversation id fixes both.
//
//   l:<lobbyID>   a lobby's stream — and, once that lobby becomes a match, the WHOLE match's stream
//   m:<matchId>   a match with no originating lobby (rematch, convert-to-Bo3)
//   <gameName>    the legacy per-game stream, used when a game belongs to no match
//
// ⚠ THE LEGACY FORM IS DELIBERATELY UNPREFIXED. ChatScopeKey('4242') returns '4242', so
// GetChatMessagesCacheKey() still produces exactly "chat_4242". That is the entire migration story:
// every game and every match alive at deploy keeps its history, and no backfill runs.
//
// ⚠ THE SIDECAR IS EXACTLY AS DURABLE AS THE CHAT IT ADDRESSES. For a sim with saved chat (Core/ChatStore.php —
// SWUSim, switched on per request by ChatPersistUseRoot) the messages outlive APCu, so the redirect is saved too, at
// <Chats>/pointers/<owner>.txt: lose it and a game would read its empty legacy bucket while its conversation sat on
// disk. APCu stays the fast path — the file is read only on an APCu miss, and a miss with no file is cached as ''
// for 300s, so a matchless game's continuous polling does not stat the disk each time. Every other sim keeps the
// APCu-only sidecar: its messages are APCu-only, and a flush has already lost them.

if (!function_exists('ChatSidecarKey')) {

// A well-formed conversation id. A plain regex, deliberately NOT ChatScopeKey(): validating a stored
// redirect by re-resolving it would recurse forever on a self-referential entry.
function _ChatIsConversationId($v) {
    return is_string($v) && preg_match('/^[lm]:[A-Za-z0-9_-]{1,64}$/', $v) === 1;
}

// APCu key holding "which conversation does this game — or this MATCH — belong to".
function ChatSidecarKey($ownerId) {
    return 'chat_conv_' . preg_replace('/[^A-Za-z0-9_]/', '', strval($ownerId));
}

// Publish the redirect for one game, or for one match. Called once at creation, never on a poll.
// The 24h TTL matches the chat rows' own 3600s-refreshed lifetime with headroom for a long match.
function ChatPublishConversationForGame($ownerId, $chatId) {
    global $APCuEnabled;
    if (!$APCuEnabled || !function_exists('apcu_store')) return false;
    if (!_ChatIsConversationId($chatId)) return false;          // never publish an unusable id
    $path = _ChatSavedPointerPath($ownerId);
    if ($path !== null) {
        if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
        if (@file_put_contents($path, strval($chatId), LOCK_EX) === false) error_log("ChatStore: could not save the chat pointer for '$ownerId'");
    }
    return apcu_store(ChatSidecarKey($ownerId), strval($chatId), 86400);
}

function ChatConversationForGame($ownerId) {
    global $APCuEnabled;
    if (!$APCuEnabled || !function_exists('apcu_fetch')) return null;
    $v = apcu_fetch(ChatSidecarKey($ownerId));
    if ($v === false && ($path = _ChatSavedPointerPath($ownerId)) !== null) {
        $saved = is_file($path) ? trim(strval(@file_get_contents($path))) : '';
        if (_ChatIsConversationId($saved)) { apcu_store(ChatSidecarKey($ownerId), $saved, 86400); return $saved; }
        // Remembered miss — see the header — but only for a game/match that exists: this runs on unauthenticated
        // reads, and a miss cached for any invented id would let a caller fill APCu (Core/ChatStore.php).
        if (function_exists('ChatStoreOwnerExists') && ChatStoreOwnerExists($ownerId)) apcu_store(ChatSidecarKey($ownerId), '', 300);
        return null;
    }
    return _ChatIsConversationId($v) ? $v : null;
}

// The saved pointer's file, or null when this request has no saved chat (Core/ChatStore.php is included by
// NetworkingLibraries.php; a caller that loaded only this file has none).
function _ChatSavedPointerPath($ownerId) {
    if (!function_exists('ChatPersistActive') || !ChatPersistActive()) return null;
    $id = preg_replace('/[^A-Za-z0-9_]/', '', strval($ownerId));
    return $id === '' ? null : ChatStoreDir() . '/pointers/' . $id . '.txt';
}

// The APCu key SUFFIX for a scope token, or null when the token cannot address a bucket.
// Accepts a numeric gameName (legacy / matchless) or an explicit l:/m: conversation id.
//
// ⚠ AN 'm:' TOKEN IS REDIRECTED TOO, AND THAT IS NOT OBVIOUS. A match created from a lobby ADOPTS
// the lobby's conversation ('l:<lobbyID>'), so its own id is NOT where its messages live. Passing
// 'm:<matchId>' straight through sent the Sideboard to an empty bucket while the whole conversation
// sat under the lobby id — caught by test_chat_one_stream_e2e.php, and invisible to every test that
// checked one scope at a time. A match with no redirect (one that never had a lobby, and never had
// its chatId stamped) correctly keeps its own id.
function ChatScopeKey($token) {
    $token = strval($token);
    if ($token === '' || strlen($token) > 128) return null;
    if (ctype_digit($token)) {
        $redirect = ChatConversationForGame($token);
        return $redirect !== null ? $redirect : $token;
    }
    if (preg_match('/^m:([A-Za-z0-9_-]{1,64})$/', $token, $mm)) {
        $redirect = ChatConversationForGame($mm[1]);
        return $redirect !== null ? $redirect : $token;
    }
    if (preg_match('/^l:[A-Za-z0-9_-]{1,64}$/', $token)) return $token;
    return null;
}

}
