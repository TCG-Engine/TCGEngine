<?php

require_once __DIR__ . '/../Assets/patreon-php-master/src/OAuth.php';
require_once __DIR__ . '/../Assets/patreon-php-master/src/API.php';
require_once __DIR__ . '/../Assets/patreon-php-master/src/PatreonLibraries.php';
require_once __DIR__ . '/../Assets/patreon-php-master/src/PatreonDictionary.php';
require_once __DIR__ . '/../AccountFiles/DiscordOAuth.php';

$redirect = '/TCGEngine/SharedUI/Sites/SWUDeck/LoginPage.php';
$conn = null;

try {
    if (!empty($_GET['error'])) throw new RuntimeException('Discord authorization was cancelled.');
    $state = (string)($_GET['state'] ?? '');
    $code = (string)($_GET['code'] ?? '');
    if ($state === '' || $code === '') throw new RuntimeException('Discord did not return a complete sign-in response.');

    $flow = DiscordOAuthConsumeFlow($state);
    $redirect = DiscordOAuthSafeReturn($flow['redirect'] ?? '', $flow['site'] ?? 'SWUDeck');
    $tokens = DiscordOAuthExchangeCode($code);
    $accessToken = (string)($tokens['access_token'] ?? '');
    if ($accessToken === '') throw new RuntimeException('Discord did not return an access token.');
    $discordUser = DiscordOAuthFetchUser($accessToken);
    $subject = trim((string)($discordUser['id'] ?? ''));
    if ($subject === '') throw new RuntimeException('Discord did not return a user identity.');
    $conn = GetLocalMySQLConnection();
    $existingIdentity = DiscordOAuthFindUserBySubject($conn, $subject);
    $action = $flow['action'] ?? 'login';

    if ($action === 'link') {
        if (!IsUserLoggedIn()) throw new RuntimeException('Log in before linking a Discord account.');
        $userId = (int)LoggedInUser();
        DiscordOAuthLinkIdentity($conn, $userId, $subject);
        $_SESSION['discordID'] = $subject;
        $conn->close();
        $conn = null;
        session_write_close();
        header('Location: ' . $redirect);
        exit();
    }

    if ($existingIdentity) {
        DiscordOAuthLinkIdentity($conn, (int)$existingIdentity['usersId'], $subject);
        $conn->close();
        $conn = null;
        $user = LoadUserDataFromId((int)$existingIdentity['usersId']);
        EstablishUserSessionFromData($user, true);
        header('Location: ' . $redirect);
        exit();
    }

    if (IsUserLoggedIn()) {
        $userId = (int)LoggedInUser();
        DiscordOAuthLinkIdentity($conn, $userId, $subject);
        $_SESSION['discordID'] = $subject;
        $conn->close();
        $conn = null;
        session_write_close();
        header('Location: ' . $redirect);
        exit();
    }

    DiscordOAuthRememberPending($discordUser, $flow);
    $conn->close();
    $conn = null;
    session_write_close();
    header('Location: /TCGEngine/AccountFiles/DiscordOnboarding.php');
    exit();
} catch (Throwable $e) {
    if ($conn instanceof mysqli) $conn->close();
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Location: ' . DiscordOAuthAppendError($redirect, $e->getMessage()));
    exit();
}
