<?php
// "Connect SWU Forge" for Petranaki — the OAuth authorization-code flow. Mirrors SWUStatsOAuth.php: a random state
// held in the session, bound to the Petranaki account that started the flow. Linking means "send my finished games
// to SWU Forge" (SWUSim/SWUForgeDelivery.php); Forge grants that one thing, with no scopes.
require_once __DIR__ . '/AccountSessionAPI.php';
require_once __DIR__ . '/AccountDatabaseAPI.php';
require_once __DIR__ . '/../SWUSim/SWUForgeLink.php';

const SWUFORGE_PROFILE_PATH = '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php';

function SWUForgeOAuthSafeReturn($redirect): string {
    return AccountSafeRedirect($redirect, SWUFORGE_PROFILE_PATH);
}

// The client registers one redirect URI per host, and a flow keeps its state in the session of the host it started
// on (petranaki.net and www.petranaki.net have separate cookie jars). Returns the Start URL on the redirect URI's
// host, or null when this request is already there.
function SWUForgeOAuthCanonicalStartUrl(string $currentHost, string $redirect): ?string {
    $p = parse_url(SWUForgeConfig()['redirectUri']);
    $want = strtolower(($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : ''));
    if ($want === '' || strtolower($currentHost) === $want) return null;
    return ($p['scheme'] ?? 'https') . '://' . $want . '/TCGEngine/AccountFiles/SWUForgeOAuthStart.php?'
         . http_build_query(['redirect' => $redirect]);
}

function SWUForgeOAuthBeginFlow(int $usersId, string $redirect, int $now = 0): string {
    CheckSession();
    $now = $now ?: time();
    foreach (($_SESSION['swuforge_oauth_flows'] ?? []) as $key => $flow) {
        if (($flow['expiresAt'] ?? 0) < $now) unset($_SESSION['swuforge_oauth_flows'][$key]);
    }
    $state = bin2hex(random_bytes(32));
    $_SESSION['swuforge_oauth_flows'][$state] = [
        'usersId'   => $usersId,
        'redirect'  => SWUForgeOAuthSafeReturn($redirect),
        'expiresAt' => $now + 600,
    ];
    return $state;
}

function SWUForgeOAuthConsumeFlow(string $state, int $now = 0): array {
    CheckSession();
    $now = $now ?: time();
    $flow = $_SESSION['swuforge_oauth_flows'][$state] ?? null;
    unset($_SESSION['swuforge_oauth_flows'][$state]);
    if ($state === '' || !is_array($flow) || ($flow['expiresAt'] ?? 0) < $now) {
        throw new RuntimeException('The SWU Forge connection request expired or could not be verified. Please try again.');
    }
    return $flow;
}

function SWUForgeOAuthAuthorizeUrl(string $state): string {
    $cfg = SWUForgeConfig();
    if (!SWUForgeConfigured() || $cfg['clientId'] === '') {
        throw new RuntimeException('SWU Forge linking is not configured on this server.');
    }
    return $cfg['baseUrl'] . '/auth/petranaki/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id'     => $cfg['clientId'],
        'redirect_uri'  => $cfg['redirectUri'],
        'state'         => $state,
    ], '', '&', PHP_QUERY_RFC3986);
}

// Swap the code for tokens, learn which Forge profile the link saves games to, and store the link.
function SWUForgeOAuthCompleteLink(int $usersId, string $code, int $now = 0): array {
    $now = $now ?: time();
    if ($code === '') throw new RuntimeException('SWU Forge did not return an authorization code. Please try again.');
    $cfg = SWUForgeConfig();
    $tok = SWUForgeHttp('POST', $cfg['baseUrl'] . '/auth/petranaki/token', [
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => $cfg['redirectUri'],
        'client_id'     => $cfg['clientId'],
        'client_secret' => $cfg['clientSecret'],
    ]);
    $t = is_array($tok['body'] ?? null) ? $tok['body'] : [];
    if ($tok['status'] !== 200 || empty($t['access_token']) || empty($t['refresh_token'])) {
        throw new RuntimeException('SWU Forge rejected the connection request. Please try again.');
    }
    $me = SWUForgeHttp('GET', $cfg['baseUrl'] . '/api/petranaki/me', null, ['Authorization: Bearer ' . $t['access_token']]);
    if ($me['status'] !== 200 || empty($me['body']['linked'])) {
        throw new RuntimeException('Could not read your SWU Forge account. Please try again.');
    }
    $name = (string)($me['body']['profile']['name'] ?? '');
    $conn = GetLocalMySQLConnection();
    try {
        if (!SWUForgeLinkReady($conn)) throw new RuntimeException('SWU Forge linking is not available on this server yet.');
        SWUForgeSaveLink($conn, $usersId, $name, (string)$t['access_token'], (string)$t['refresh_token'],
                         $now + (int)($t['expires_in'] ?? 3600), $now);
    } finally {
        $conn->close();
    }
    return ['profileName' => $name];
}

function SWUForgeOAuthAppendError(string $redirect, string $message): string {
    return $redirect . (strpos($redirect, '?') === false ? '?' : '&') . 'swuforge_error=' . rawurlencode($message);
}
