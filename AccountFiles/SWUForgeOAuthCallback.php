<?php
// SWU Forge → Petranaki after the player approves (or denies) the link.
require_once __DIR__ . '/SWUForgeOAuth.php';

$redirect = SWUFORGE_PROFILE_PATH;
try {
    $flow = SWUForgeOAuthConsumeFlow((string)($_GET['state'] ?? ''));
    $redirect = $flow['redirect'];
    if (isset($_GET['error'])) throw new RuntimeException('SWU Forge was not connected.');
    // The flow belongs to the account that started it. A link landing in someone else's session is refused, so nobody
    // can attach their Forge account to a victim's Petranaki account (or the reverse).
    if (!IsUserLoggedIn() || (int)LoggedInUser() !== (int)$flow['usersId']) {
        throw new RuntimeException('Sign in to the Petranaki account that started the SWU Forge connection and try again.');
    }
    SWUForgeOAuthCompleteLink((int)$flow['usersId'], (string)($_GET['code'] ?? ''));
    header('Location: ' . $redirect);
    exit();
} catch (Throwable $e) {
    header('Location: ' . SWUForgeOAuthAppendError($redirect, $e->getMessage()));
    exit();
}
