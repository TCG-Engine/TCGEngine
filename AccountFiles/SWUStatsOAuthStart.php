<?php
// Profile "Connect SWUStats" → SWUStats' authorize page (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §1).
require_once __DIR__ . '/SWUStatsOAuth.php';

$redirect = SWUStatsOAuthSafeReturn($_GET['redirect'] ?? '');
try {
    $hop = SWUStatsOAuthCanonicalStartUrl((string)($_SERVER['HTTP_HOST'] ?? ''), $redirect);
    if ($hop !== null) { header('Location: ' . $hop); exit(); }
    CheckSession();
    if (!IsUserLoggedIn()) {
        // On the canonical host the player may not be signed in yet (separate cookie jar): sign in, come back here.
        $back = '/TCGEngine/AccountFiles/SWUStatsOAuthStart.php?' . http_build_query(['redirect' => $redirect]);
        header('Location: /TCGEngine/SharedUI/Sites/SWUSim/LoginPage.php?' . http_build_query(['redirect' => $back]));
        exit();
    }
    $state = SWUStatsOAuthBeginFlow((int)LoggedInUser(), $redirect);
    $url = SWUStatsOAuthAuthorizeUrl($state);
    session_write_close();
    header('Location: ' . $url);
    exit();
} catch (Throwable $e) {
    header('Location: ' . SWUStatsOAuthAppendError($redirect, $e->getMessage()));
    exit();
}
