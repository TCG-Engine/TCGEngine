<?php
// "Connect SWUStats" for Petranaki — the OAuth authorization-code flow
// (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §1). Mirrors DiscordOAuth.php:
// a random state held in the session, here bound to the Petranaki account that started the flow.
require_once __DIR__ . '/AccountSessionAPI.php';
require_once __DIR__ . '/AccountDatabaseAPI.php';
require_once __DIR__ . '/../SWUSim/SWUStatsLink.php';

const SWUSTATS_PROFILE_PATH = '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php';

function SWUStatsOAuthSafeReturn($redirect): string {
    return AccountSafeRedirect($redirect, SWUSTATS_PROFILE_PATH);
}

// The client registers ONE redirect URI, so ONE host. petranaki.net and www.petranaki.net serve the
// same site with separate cookie jars: a flow started on the other host keeps its state in a session
// the callback can never read. Returns the Start URL on the redirect URI's host, or null when this
// request is already there.
function SWUStatsOAuthCanonicalStartUrl(string $currentHost, string $redirect): ?string {
    $p = parse_url(SWUStatsOAuthConfig()['redirectUri']);
    $want = strtolower(($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : ''));
    if ($want === '' || strtolower($currentHost) === $want) return null;
    return ($p['scheme'] ?? 'https') . '://' . $want . '/TCGEngine/AccountFiles/SWUStatsOAuthStart.php?'
         . http_build_query(['redirect' => $redirect]);
}

function SWUStatsOAuthBeginFlow(int $usersId, string $redirect, int $now = 0): string {
    CheckSession();
    $now = $now ?: time();
    foreach (($_SESSION['swustats_oauth_flows'] ?? []) as $key => $flow) {
        if (($flow['expiresAt'] ?? 0) < $now) unset($_SESSION['swustats_oauth_flows'][$key]);
    }
    $state = bin2hex(random_bytes(32));
    $_SESSION['swustats_oauth_flows'][$state] = [
        'usersId'   => $usersId,
        'redirect'  => SWUStatsOAuthSafeReturn($redirect),
        'expiresAt' => $now + 600,
    ];
    return $state;
}

function SWUStatsOAuthConsumeFlow(string $state, int $now = 0): array {
    CheckSession();
    $now = $now ?: time();
    $flow = $_SESSION['swustats_oauth_flows'][$state] ?? null;
    unset($_SESSION['swustats_oauth_flows'][$state]);
    if ($state === '' || !is_array($flow) || ($flow['expiresAt'] ?? 0) < $now) {
        throw new RuntimeException('The SWUStats connection request expired or could not be verified. Please try again.');
    }
    return $flow;
}

function SWUStatsOAuthAuthorizeUrl(string $state): string {
    $cfg = SWUStatsOAuthConfig();
    if ($cfg['clientId'] === '' || $cfg['clientSecret'] === '') {
        throw new RuntimeException('SWUStats linking is not configured on this server.');
    }
    return SWUStatsPublicBase() . '/TCGEngine/APIs/OAuth/authorize.php?' . http_build_query([
        'response_type' => 'code',
        'client_id'     => $cfg['clientId'],
        'redirect_uri'  => $cfg['redirectUri'],
        'scope'         => 'profile decks',
        'state'         => $state,
    ], '', '&', PHP_QUERY_RFC3986);
}

// Swap the code for tokens, learn which SWUStats account it is, and store the link.
function SWUStatsOAuthCompleteLink(int $usersId, string $code, int $now = 0): array {
    $now = $now ?: time();
    if ($code === '') throw new RuntimeException('SWUStats did not return an authorization code. Please try again.');
    $cfg = SWUStatsOAuthConfig();
    $tok = SWUStatsHttp('POST', SWUStatsServerBase() . '/TCGEngine/APIs/OAuth/token.php', [
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => $cfg['redirectUri'],
        'client_id'     => $cfg['clientId'],
        'client_secret' => $cfg['clientSecret'],
    ]);
    $t = is_array($tok['body'] ?? null) ? $tok['body'] : [];
    if ($tok['status'] !== 200 || empty($t['access_token']) || empty($t['refresh_token'])) {
        throw new RuntimeException('SWUStats rejected the connection request. Please try again.');
    }
    $userinfo = SWUStatsServerBase() . '/TCGEngine/APIs/OAuth/userinfo.php';
    $me = SWUStatsHttp('GET', $userinfo, null, ['Authorization: Bearer ' . $t['access_token']]);
    // userinfo.php reads only $_SERVER['HTTP_AUTHORIZATION'], which Apache does not set for a Bearer header,
    // so it answers "Access token required" as if none was sent. Its documented fallback is ?access_token=
    // (one call per link, server to server). A token it REJECTED is never retried in a URL.
    if ($me['status'] === 401 && (($me['body']['error_description'] ?? '') === 'Access token required')) {
        $me = SWUStatsHttp('GET', $userinfo . '?' . http_build_query(['access_token' => $t['access_token']]));
    }
    $ssId = (int)($me['body']['id'] ?? 0);
    if ($me['status'] !== 200 || $ssId <= 0) {
        throw new RuntimeException('Could not read your SWUStats account. Please try again.');
    }
    $name = (string)($me['body']['username'] ?? '');
    $conn = GetLocalMySQLConnection();
    try {
        if (!SWUStatsLinkReady($conn)) throw new RuntimeException('SWUStats linking is not available on this server yet.');
        SWUStatsSaveLink($conn, $usersId, $ssId, $name, (string)$t['access_token'], (string)$t['refresh_token'],
                         $now + (int)($t['expires_in'] ?? 3600), $now);
    } finally {
        $conn->close();
    }
    return ['swustatsUserId' => $ssId, 'swustatsUsername' => $name];
}

function SWUStatsOAuthAppendError(string $redirect, string $message): string {
    return $redirect . (strpos($redirect, '?') === false ? '?' : '&') . 'swustats_error=' . rawurlencode($message);
}
