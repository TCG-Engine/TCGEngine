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
    if (intval($viewer['userId'] ?? 0) <= 0) return 'Log in to chat.';
    return null;
}

}
