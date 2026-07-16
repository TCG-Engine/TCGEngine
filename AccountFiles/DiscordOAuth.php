<?php

require_once __DIR__ . '/AccountSessionAPI.php';
require_once __DIR__ . '/AccountDatabaseAPI.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';

const DISCORD_OAUTH_CLIENT_ID = '1338995198730043432';

function DiscordOAuthAllowedSites(): array {
    return ['SWUDeck', 'SWUSim', 'GudnakSim', 'SoulMastersDB', 'AzukiSim', 'GrandArchiveSim', 'CardEditor'];
}

function DiscordOAuthNormalizeSite($site): string {
    $site = (string)$site;
    return in_array($site, DiscordOAuthAllowedSites(), true) ? $site : 'SWUDeck';
}

function DiscordOAuthDefaultReturn(string $site): string {
    if ($site === 'CardEditor') return '/TCGEngine/CardEditor/UI/';
    return '/TCGEngine/SharedUI/Sites/' . $site . '/MainMenu.php';
}

function DiscordOAuthSafeReturn($redirect, string $site = 'SWUDeck'): string {
    return AccountSafeRedirect($redirect, DiscordOAuthDefaultReturn(DiscordOAuthNormalizeSite($site)));
}

function DiscordOAuthStartUrl(string $action, string $site, string $redirect = ''): string {
    $site = DiscordOAuthNormalizeSite($site);
    $action = in_array($action, ['login', 'signup', 'link'], true) ? $action : 'login';
    return '/TCGEngine/AccountFiles/DiscordOAuthStart.php?' . http_build_query([
        'action' => $action,
        'site' => $site,
        'redirect' => DiscordOAuthSafeReturn($redirect, $site),
    ]);
}

function DiscordOAuthDefaultRedirectUri(): string {
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    $host = preg_replace('/:\d+$/', '', $host);
    if (in_array($host, ['zendo.gg', 'www.zendo.gg'], true)) {
        return 'https://' . $host . '/TCGEngine/APIs/DiscordLogin.php';
    }
    return 'https://www.swustats.net/TCGEngine/APIs/DiscordLogin.php';
}

function DiscordOAuthConfig(): array {
    require __DIR__ . '/../APIKeys/APIKeys.php';
    return [
        'clientId' => trim((string)(getenv('DISCORD_CLIENT_ID') ?: ($discordClientID ?? DISCORD_OAUTH_CLIENT_ID))),
        'clientSecret' => trim((string)(getenv('DISCORD_CLIENT_SECRET') ?: ($discordClientSecret ?? ''))),
        'redirectUri' => trim((string)(getenv('DISCORD_REDIRECT_URI') ?: DiscordOAuthDefaultRedirectUri())),
    ];
}

function DiscordOAuthBeginFlow(string $action, string $site, string $redirect): string {
    CheckSession();
    $state = bin2hex(random_bytes(32));
    $now = time();
    foreach (($_SESSION['discord_oauth_flows'] ?? []) as $key => $flow) {
        if (($flow['expiresAt'] ?? 0) < $now) unset($_SESSION['discord_oauth_flows'][$key]);
    }
    $_SESSION['discord_oauth_flows'][$state] = [
        'action' => $action,
        'site' => $site,
        'redirect' => DiscordOAuthSafeReturn($redirect, $site),
        'expiresAt' => $now + 600,
    ];
    return $state;
}

function DiscordOAuthConsumeFlow(string $state): array {
    CheckSession();
    $flow = $_SESSION['discord_oauth_flows'][$state] ?? null;
    unset($_SESSION['discord_oauth_flows'][$state]);
    if (!is_array($flow) || ($flow['expiresAt'] ?? 0) < time()) {
        throw new RuntimeException('The Discord sign-in request expired or could not be verified. Please try again.');
    }
    return $flow;
}

function DiscordOAuthAuthorizeUrl(string $state): string {
    $config = DiscordOAuthConfig();
    if ($config['clientId'] === '' || $config['clientSecret'] === '' || $config['redirectUri'] === '') {
        throw new RuntimeException('Discord sign-in is not configured on this server.');
    }
    return 'https://discord.com/oauth2/authorize?' . http_build_query([
        'client_id' => $config['clientId'],
        'redirect_uri' => $config['redirectUri'],
        'response_type' => 'code',
        'scope' => 'identify email',
        'state' => $state,
        'prompt' => 'consent',
    ], '', '&', PHP_QUERY_RFC3986);
}

function DiscordOAuthHttpRequest(string $url, array $options): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, $options + [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $body = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $error !== '') throw new RuntimeException('Discord could not be reached. Please try again.');
    $data = json_decode($body, true);
    if ($status < 200 || $status >= 300 || !is_array($data)) {
        throw new RuntimeException('Discord rejected the sign-in request. Please try again.');
    }
    return $data;
}

function DiscordOAuthExchangeCode(string $code): array {
    $config = DiscordOAuthConfig();
    return DiscordOAuthHttpRequest('https://discord.com/api/v10/oauth2/token', [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $config['clientId'],
            'client_secret' => $config['clientSecret'],
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $config['redirectUri'],
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
}

function DiscordOAuthFetchUser(string $accessToken): array {
    return DiscordOAuthHttpRequest('https://discord.com/api/v10/users/@me', [
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
    ]);
}

function DiscordOAuthFindUserBySubject(mysqli $conn, string $subject): ?array {
    $stmt = $conn->prepare('SELECT * FROM users WHERE discordID = ? LIMIT 1');
    if (!$stmt) throw new RuntimeException('Could not query Discord identities.');
    $stmt->bind_param('s', $subject);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function DiscordOAuthLinkIdentity(mysqli $conn, int $userId, string $subject): void {
    $subjectOwner = DiscordOAuthFindUserBySubject($conn, $subject);
    if ($subjectOwner && (int)$subjectOwner['usersId'] !== $userId) {
        throw new RuntimeException('That Discord account is already linked to another account.');
    }

    $stmt = $conn->prepare('SELECT discordID FROM users WHERE usersId = ? LIMIT 1');
    if (!$stmt) throw new RuntimeException('Could not query the account.');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) throw new RuntimeException('The account could not be found.');
    $existingSubject = trim((string)($user['discordID'] ?? ''));
    if ($existingSubject !== '' && !hash_equals($existingSubject, $subject)) {
        throw new RuntimeException('This account already has a different Discord account linked. Disconnect it first.');
    }

    $stmt = $conn->prepare('UPDATE users SET discordID = ? WHERE usersId = ?');
    if (!$stmt) throw new RuntimeException('Could not link the Discord account.');
    $stmt->bind_param('si', $subject, $userId);
    if (!$stmt->execute()) throw new RuntimeException('Could not link the Discord account.');
    $stmt->close();
}

function DiscordOAuthRememberPending(array $discordUser, array $flow): void {
    CheckSession();
    $_SESSION['discord_oauth_pending'] = [
        'subject' => (string)$discordUser['id'],
        'email' => isset($discordUser['email']) ? trim((string)$discordUser['email']) : '',
        'emailVerified' => !empty($discordUser['verified']),
        'username' => trim((string)($discordUser['global_name'] ?? $discordUser['username'] ?? '')),
        'site' => DiscordOAuthNormalizeSite($flow['site'] ?? 'SWUDeck'),
        'redirect' => DiscordOAuthSafeReturn($flow['redirect'] ?? '', $flow['site'] ?? 'SWUDeck'),
        'csrf' => bin2hex(random_bytes(32)),
        'expiresAt' => time() + 900,
    ];
}

function DiscordOAuthPending(): ?array {
    CheckSession();
    $pending = $_SESSION['discord_oauth_pending'] ?? null;
    if (!is_array($pending) || ($pending['expiresAt'] ?? 0) < time()) {
        unset($_SESSION['discord_oauth_pending']);
        return null;
    }
    return $pending;
}

function DiscordOAuthClearPending(): void {
    CheckSession();
    unset($_SESSION['discord_oauth_pending']);
}

function DiscordOAuthFindUserByEmail(mysqli $conn, string $email): ?array {
    if ($email === '') return null;
    $stmt = $conn->prepare('SELECT * FROM users WHERE LOWER(usersEmail) = LOWER(?) LIMIT 1');
    if (!$stmt) return null;
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function DiscordOAuthUsernameExists(mysqli $conn, string $username): bool {
    $stmt = $conn->prepare('SELECT usersId FROM users WHERE LOWER(usersUid) = LOWER(?) LIMIT 1');
    if (!$stmt) return true;
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $exists;
}

function DiscordOAuthSuggestedUsername(mysqli $conn, string $discordName): string {
    $base = preg_replace('/[^A-Za-z0-9]/', '', $discordName) ?: 'DiscordUser';
    $base = substr($base, 0, 118);
    $candidate = $base;
    for ($suffix = 2; DiscordOAuthUsernameExists($conn, $candidate); $suffix++) $candidate = $base . $suffix;
    return $candidate;
}

function DiscordOAuthCreateUserFromPending(string $username, string $email, bool $rememberMe = true): array {
    $pending = DiscordOAuthPending();
    if (!$pending) throw new RuntimeException('The Discord signup session expired. Please start again.');
    $conn = GetLocalMySQLConnection();
    if (DiscordOAuthUsernameExists($conn, $username)) {
        $conn->close();
        throw new RuntimeException('That username is already taken.');
    }
    if (DiscordOAuthFindUserByEmail($conn, $email)) {
        $conn->close();
        throw new RuntimeException('An account already uses that email. Log in to the existing account to link Discord.');
    }
    $subject = $pending['subject'];
    $stmt = $conn->prepare('INSERT INTO users (usersUid, usersEmail, usersPwd, discordID) VALUES (?, ?, NULL, ?)');
    if (!$stmt) throw new RuntimeException('Could not create the account.');
    $stmt->bind_param('sss', $username, $email, $subject);
    if (!$stmt->execute()) throw new RuntimeException('Could not create the account.');
    $userId = (int)$conn->insert_id;
    $stmt->close();
    $conn->close();

    $user = LoadUserDataFromId($userId);
    DiscordOAuthClearPending();
    EstablishUserSessionFromData($user, $rememberMe);
    return $user;
}

function DiscordOAuthAppendError(string $redirect, string $message): string {
    return $redirect . (strpos($redirect, '?') === false ? '?' : '&') . 'oauth_error=' . rawurlencode($message);
}
