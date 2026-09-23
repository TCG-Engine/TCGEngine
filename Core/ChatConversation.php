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
// ⚠ THE SIDECAR LIVES IN APCu, NOT ON DISK, AND THAT IS CORRECT. An APCu flush loses the redirect
// and a game falls back to its own legacy bucket — but the chat MESSAGES live in APCu too, so a
// flush has already lost them. The sidecar is exactly as durable as the data it addresses, and a
// per-poll filesystem read (GetChat.php is polled continuously) would not be.

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
    return apcu_store(ChatSidecarKey($ownerId), strval($chatId), 86400);
}

function ChatConversationForGame($ownerId) {
    global $APCuEnabled;
    if (!$APCuEnabled || !function_exists('apcu_fetch')) return null;
    $v = apcu_fetch(ChatSidecarKey($ownerId));
    return _ChatIsConversationId($v) ? $v : null;
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
