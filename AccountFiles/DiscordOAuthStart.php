<?php

require_once __DIR__ . '/DiscordOAuth.php';

try {
    $site = DiscordOAuthNormalizeSite($_GET['site'] ?? 'SWUDeck');
    $action = (string)($_GET['action'] ?? 'login');
    if (!in_array($action, ['login', 'signup', 'link'], true)) $action = 'login';
    $redirect = DiscordOAuthSafeReturn($_GET['redirect'] ?? '', $site);
    if ($action === 'link' && !IsUserLoggedIn()) {
        throw new RuntimeException('Log in before linking a Discord account.');
    }
    $state = DiscordOAuthBeginFlow($action, $site, $redirect);
    $url = DiscordOAuthAuthorizeUrl($state);
    session_write_close();
    header('Location: ' . $url);
    exit();
} catch (Throwable $e) {
    $site = DiscordOAuthNormalizeSite($_GET['site'] ?? 'SWUDeck');
    $redirect = DiscordOAuthSafeReturn($_GET['redirect'] ?? '', $site);
    header('Location: ' . DiscordOAuthAppendError($redirect, $e->getMessage()));
    exit();
}

