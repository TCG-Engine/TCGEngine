<?php
// SWUSim's chat rules.
//
// ★ OWNER RULING (2026-09-21): no account is needed to PLAY any SWUSim format — guests lose only
// CHAT. So this gate is the whole difference between a guest and an account, and it applies in a
// game, in a lobby and in a sideboard alike.
//
// ⚠ THE REFUSAL STRING IS USER-VISIBLE. SubmitChat.php echoes it straight to the player and the chat
// panel prints it where the composer would be. It is the exact wording SubmitChat.php has always
// used; do not reword it without the owner.
//
// ⚠ This file is NOT SWUSim/Custom/ChatWhisperPolicy.php, which answers a different question (may
// THIS whisper reach THOSE seats). Whispers are game-only and keep their own policy.

if (!function_exists('SimChatSendRefusal')) {

function SimChatSendRefusal(array $viewer, $scopeToken) {
    // ★ OWNER RULING (2026-09-26): SPECTATORS CANNOT CHAT. Checked before the login gate so a
    // logged-in spectator is refused too — watching a game is not a seat at the table.
    //
    // ⚠ The game page renders NO composer and NO notice for a spectator (NextTurn.php), so this
    // string is only ever seen by a hand-crafted POST. It is deliberately terse for that reason;
    // unlike 'Log in to chat.' it must NEVER be surfaced in the UI, because the ruling is that a
    // spectator is told nothing.
    if (!empty($viewer['isSpectator'])) return 'Spectators cannot chat.';
    if (intval($viewer['userId'] ?? 0) <= 0) return 'Log in to chat.';
    return null;
}

// ★ OWNER RULING (2026-10-30): "in a high ground lobby, anyone can chat" — one conversation for the room and its matches,
// and the room's watchers chat from the board too. The spectator ruling above is untouched: this only ever answers for a
// MEMBER of a High Ground room, proven by their own room key (the request's authKey) or, for a watcher on the board, the
// room cookie the room page sets (tcg_hg_<lobbyID>). Everyone gets their fixed room name as the label. The login gate
// still applies (guests read but can't send). docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md
function SimChatViewerOverride(string $scopeToken, array $request, array $cookies): ?array {
    $roomId = '';
    $liveKeys = [];
    if (strpos($scopeToken, 'l:') === 0) {
        $roomId = substr($scopeToken, 2);
    } else {
        // ⚠ Core's match reader, NOT SWUSim/MatchFlow.php: that one loads the engine, and loading it from inside a function
        // leaves the engine's globals local to this call — later top-level includes in the same request then miss them.
        require_once __DIR__ . '/../../Core/Match/MatchFlow.php';
        $m = null;
        if (strpos($scopeToken, 'm:') === 0) {
            $m = MatchRead('SWUSim', substr($scopeToken, 2));
        } else {
            $ref = MatchReadRef('SWUSim', $scopeToken);
            if (is_array($ref)) $m = MatchRead('SWUSim', strval($ref['matchId'] ?? ''));
        }
        if (!is_array($m)) return null;
        $roomId = strval($m['roomId'] ?? '');   // only a High Ground room passes the lobby check below
        foreach ([1, 2] as $s) $liveKeys[$s] = strval($m['players'][strval($s)]['authKey'] ?? '');
    }
    if ($roomId === '' || !preg_match('/^[A-Za-z0-9_.]+$/', $roomId)) return null;
    require_once __DIR__ . '/../../APIs/Lobbies/Classes/Player.php';
    $lobby = function_exists('apcu_fetch') ? apcu_fetch($roomId) : false;
    if (!is_object($lobby) || empty($lobby->highGround['names'])) return null;
    $names = $lobby->highGround['names'];
    $member = '';
    foreach ([strval($request['authKey'] ?? ''), strval($cookies['tcg_hg_' . $roomId] ?? '')] as $k) {
        if ($k !== '' && isset($names[$k])) { $member = $k; break; }
    }
    if ($member === '') return null;
    // A hill player sending from their own board keeps their seat (the board labels seats); everyone else is 'H'.
    $seat = intval($request['playerID'] ?? 0);
    if (($seat === 1 || $seat === 2) && ($liveKeys[$seat] ?? '') === $member) {
        return ['viewerID' => strval($seat), 'viewerSeat' => $seat, 'isSpectator' => false, 'canAct' => true,
                'label' => $names[$member], 'highGround' => true];
    }
    return ['viewerID' => 'H', 'viewerSeat' => 0, 'isSpectator' => false, 'canAct' => false, 'label' => $names[$member], 'highGround' => true];
}

}
