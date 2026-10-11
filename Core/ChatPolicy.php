<?php
// May this viewer SEND a chat message? One seam, so the server gate and the composer the client
// renders can never disagree — the panel asks the same function.
//
// Default is ALLOW. A sim opts into restrictions with <Sim>/Custom/ChatPolicy.php defining
// SimChatSendRefusal($viewer, $scopeToken): ?string. Returning a string refuses AND supplies the
// exact wording the player sees, so a refusal is never a bare boolean the caller has to name.
//
// ⚠ This exists because the login gate used to be `if ($folderPath === 'SWUSim')` inline in
// SubmitChat.php. That gate has to hold on the Waiting Room and Sideboard too, and FaBSim — which
// shares the Waiting Room page — has different rules.

if (!function_exists('ChatSendRefusal')) {

function ChatSendRefusal($folderPath, array $viewer, $scopeToken) {
    $folderPath = strval($folderPath);
    // Never a path. A sim name is a single path segment of letters and digits, nothing else.
    if ($folderPath === '' || !preg_match('/^[A-Za-z0-9]+$/', $folderPath)) return null;
    $path = __DIR__ . '/../' . $folderPath . '/Custom/ChatPolicy.php';
    if (!is_file($path)) return null;
    include_once $path;
    if (!function_exists('SimChatSendRefusal')) return null;
    $r = SimChatSendRefusal($viewer, strval($scopeToken));
    return (is_string($r) && $r !== '') ? $r : null;
}

}
