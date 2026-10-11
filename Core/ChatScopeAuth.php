<?php
// Which conversation is this request for, and may this caller touch it?
//
// Three scopes, three DIFFERENT identity stores, and that is the whole reason this file exists:
//   game   the gamestate's seat authKeys   (SimGameValidateViewerAuth — unchanged)
//   lobby  the APCu lobby record           (a seat's authKey)
//   match  the match record on disk        (a seat's authKey)
// A single "is this authKey valid" helper would have to guess which store to ask.

include_once __DIR__ . '/ChatConversation.php';

if (!function_exists('ChatResolveRequestScope')) {

// Read the scope out of a request. Exactly one of gameName / lobbyID / matchId may be present.
//
// ⚠ AMBIGUITY IS REFUSED, NOT RESOLVED BY PRECEDENCE. A request carrying two scopes is either a bug
// or an attempt to have the auth checked against one conversation and the message written to
// another; picking a winner would make that attempt succeed quietly.
function ChatResolveRequestScope(array $get, $folderPath) {
    $game  = trim(strval($get['gameName'] ?? ''));
    $lobby = trim(strval($get['lobbyID']  ?? ''));
    $match = trim(strval($get['matchId']  ?? ''));
    $given = array_values(array_filter([$game, $lobby, $match], fn($v) => $v !== ''));
    if (count($given) === 0) return ['token' => null, 'kind' => null, 'error' => 'No conversation specified.'];
    if (count($given) > 1)   return ['token' => null, 'kind' => null, 'error' => 'Ambiguous conversation.'];

    if ($game !== '') {
        if (!IsGameNameValid($game)) return ['token' => null, 'kind' => null, 'error' => 'Invalid game name.'];
        return ['token' => $game, 'kind' => 'game', 'error' => null];
    }
    if ($lobby !== '') {
        $t = 'l:' . $lobby;
        if (ChatScopeKey($t) === null) return ['token' => null, 'kind' => null, 'error' => 'Invalid lobby.'];
        return ['token' => $t, 'kind' => 'lobby', 'error' => null];
    }
    $t = 'm:' . $match;
    if (ChatScopeKey($t) === null) return ['token' => null, 'kind' => null, 'error' => 'Invalid match.'];
    return ['token' => $t, 'kind' => 'match', 'error' => null];
}

// True when $authKey belongs to a seat in this conversation.
//
// ⚠ Spectators have NO lobby or match scope. They can watch a game, which is a public artifact; a
// room's and a match's conversations are between the people in them.
function ChatScopeAuthOk($kind, $token, array $viewer, $authKey, $folderPath) {
    if ($authKey === '') return false;

    if ($kind === 'game') {
        return SimGameValidateViewerAuth($folderPath, $token, $viewer, $authKey);
    }
    if (!empty($viewer['isSpectator'])) return false;

    if ($kind === 'lobby') {
        if (!function_exists('apcu_fetch')) return false;
        $lobbyID = substr($token, 2);
        $lobby = apcu_fetch($lobbyID);
        if (!is_object($lobby) || empty($lobby->players)) return false;
        foreach ($lobby->players as $p) {
            if ($p instanceof Player && hash_equals(strval($p->getAuthKey()), strval($authKey))) return true;
        }
        return false;
    }

    if ($kind === 'match') {
        $matchFlow = __DIR__ . '/Match/MatchFlow.php';
        if (!is_file($matchFlow)) return false;
        include_once __DIR__ . '/Match/Match.php';
        include_once $matchFlow;
        $m = MatchRead($folderPath, substr($token, 2));
        if (!is_array($m)) return false;
        foreach (($m['players'] ?? []) as $p) {
            if (hash_equals(strval($p['authKey'] ?? ''), strval($authKey))) return true;
        }
        return false;
    }
    return false;
}

}
