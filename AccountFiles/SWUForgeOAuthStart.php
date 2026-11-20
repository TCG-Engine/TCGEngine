<?php
// Profile "Connect SWU Forge" → SWU Forge's authorize page.
require_once __DIR__ . '/SWUForgeOAuth.php';

$redirect = SWUForgeOAuthSafeReturn($_GET['redirect'] ?? '');
try {
    $hop = SWUForgeOAuthCanonicalStartUrl((string)($_SERVER['HTTP_HOST'] ?? ''), $redirect);
    if ($hop !== null) { header('Location: ' . $hop); exit(); }
    CheckSession();
    if (!IsUserLoggedIn()) {
        // On the canonical host the player may not be signed in yet (separate cookie jar): sign in, come back here.
        $back = '/TCGEngine/AccountFiles/SWUForgeOAuthStart.php?' . http_build_query(['redirect' => $redirect]);
        header('Location: /TCGEngine/SharedUI/Sites/SWUSim/LoginPage.php?' . http_build_query(['redirect' => $back]));
        exit();
    }
    $state = SWUForgeOAuthBeginFlow((int)LoggedInUser(), $redirect);
    $url = SWUForgeOAuthAuthorizeUrl($state);
    session_write_close();
    header('Location: ' . $url);
    exit();
} catch (Throwable $e) {
    header('Location: ' . SWUForgeOAuthAppendError($redirect, $e->getMessage()));
    exit();
}
