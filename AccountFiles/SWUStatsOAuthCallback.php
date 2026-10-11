<?php
// SWUStats → Petranaki after the player approves (or denies) the link
// (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §1).
require_once __DIR__ . '/SWUStatsOAuth.php';

$redirect = SWUSTATS_PROFILE_PATH;
try {
    $flow = SWUStatsOAuthConsumeFlow((string)($_GET['state'] ?? ''));
    $redirect = $flow['redirect'];
    if (isset($_GET['error'])) throw new RuntimeException('SWUStats was not connected.');
    // The flow belongs to the account that started it. A link landing in someone else's session is refused,
    // so nobody can attach their SWUStats account to a victim's Petranaki account (or the reverse).
    if (!IsUserLoggedIn() || (int)LoggedInUser() !== (int)$flow['usersId']) {
        throw new RuntimeException('Sign in to the Petranaki account that started the SWUStats connection and try again.');
    }
    SWUStatsOAuthCompleteLink((int)$flow['usersId'], (string)($_GET['code'] ?? ''));
    header('Location: ' . $redirect);
    exit();
} catch (Throwable $e) {
    header('Location: ' . SWUStatsOAuthAppendError($redirect, $e->getMessage()));
    exit();
}
