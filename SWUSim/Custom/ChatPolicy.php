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

}
