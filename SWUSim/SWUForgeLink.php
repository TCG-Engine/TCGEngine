<?php
// Petranaki ↔ SWU Forge account link: config, the one HTTP seam, link storage, the token accessor and revoke.
// Same shape as SWUStatsLink.php. Petranaki is an ordinary OAuth client of SWU Forge; tokens live in this sim's DB
// (swuforge_links) and never reach the browser. A refresh ROTATES the pair, and Forge holds a used refresh token for
// a 30 s grace window, but every refresh still runs under a per-player MySQL lock so two never race for one link.
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/functions.inc.php';

const SWUFORGE_REFRESH_MARGIN = 300;   // refresh when under 5 minutes remain
const SWUFORGE_STAGING_BASE = 'https://swu-forge-staging-andy.up.railway.app';

function SWUForgeIsDev(): bool { return getenv('DEVENV') === 'true'; }

// Every value is a setting (Forge staging now, production later): an environment variable wins, then APIKeys.php
// ($swuforgeBaseURL, $swuforgeClientID, $swuforgeOAuthKey), then the defaults. Held in $GLOBALS so a test can reset it.
// $keysFile: the APIKeys file (a parameter only so a test can hand in a stand-in).
function SWUForgeConfig(string $keysFile = ''): array {
    if (isset($GLOBALS['SWUFORGE_CONFIG'])) return $GLOBALS['SWUFORGE_CONFIG'];
    $env = fn(string $k) => trim((string)(getenv($k) ?: ''));
    $base = $env('SWUFORGE_BASE_URL'); $clientId = $env('SWUFORGE_CLIENT_ID');
    $secret = $env('SWUFORGE_CLIENT_SECRET'); $redirect = $env('SWUFORGE_REDIRECT_URI');
    $keys = $keysFile !== '' ? $keysFile : __DIR__ . '/../APIKeys/APIKeys.php';
    if (($base === '' || $clientId === '' || $secret === '') && file_exists($keys)) {
        // APIKeys.php declares a const, so a second plain require in a request that already loaded it warns inside the
        // player's action: read the globals when it is loaded, otherwise load it here at function scope (as SWUStats does).
        if (in_array(realpath($keys), get_included_files(), true)) {
            $k = [$GLOBALS['swuforgeBaseURL'] ?? '', $GLOBALS['swuforgeClientID'] ?? '', $GLOBALS['swuforgeOAuthKey'] ?? ''];
        } else {
            $k = (function ($f) { require $f; return [$swuforgeBaseURL ?? '', $swuforgeClientID ?? '', $swuforgeOAuthKey ?? '']; })($keys);
        }
        if ($base === '')     $base     = trim((string)$k[0]);
        if ($clientId === '') $clientId = trim((string)$k[1]);
        if ($secret === '')   $secret   = trim((string)$k[2]);
    }
    if ($base === '') $base = SWUFORGE_STAGING_BASE;
    if ($clientId === '') $clientId = 'petranaki';
    if ($redirect === '') {
        $redirect = SWUForgeIsDev()
            ? 'http://localhost:3400/TCGEngine/AccountFiles/SWUForgeOAuthCallback.php'
            : 'https://petranaki.net/TCGEngine/AccountFiles/SWUForgeOAuthCallback.php';
    }
    return $GLOBALS['SWUFORGE_CONFIG'] = ['baseUrl' => rtrim($base, '/'), 'clientId' => $clientId,
        'clientSecret' => $secret, 'redirectUri' => $redirect];
}

// The off switch: an empty (or missing) client secret turns SWU Forge off on this box — no link, no queue, no send.
function SWUForgeConfigured(): bool { return trim((string)(SWUForgeConfig()['clientSecret'] ?? '')) !== ''; }

// The ONE way this sim talks to SWU Forge. Tests set $GLOBALS['SWUFORGE_HTTP_FAKE'] to a
// fn($method, $url, $body, $headers): array returning this same shape, so no test touches the network.
// $body: array → form-encoded; string → sent as-is (JSON). status 0 = no response at all.
function SWUForgeHttp(string $method, string $url, $body = null, array $headers = [], int $timeout = 30): array {
    $fake = $GLOBALS['SWUFORGE_HTTP_FAKE'] ?? null;
    if (is_callable($fake)) return $fake($method, $url, $body, $headers);
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_HTTPHEADER => $headers];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = is_array($body) ? http_build_query($body) : (string)$body;
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = ($raw === false) ? 0 : (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    return ['status' => $status, 'body' => is_array($decoded) ? $decoded : null,
            'raw' => is_string($raw) ? $raw : '', 'error' => $error];
}

// The client credentials every token / revoke call carries.
function _SWUForgeClient(): array {
    $cfg = SWUForgeConfig();
    return ['client_id' => $cfg['clientId'], 'client_secret' => $cfg['clientSecret']];
}

function SWUForgeLinkReady(mysqli $conn): bool { return DBTableExists($conn, 'swuforge_links'); }

function SWUForgeGetLink(mysqli $conn, int $usersId): ?array {
    $stmt = $conn->prepare('SELECT * FROM swuforge_links WHERE usersId = ? LIMIT 1');
    $stmt->bind_param('i', $usersId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function SWUForgeSaveLink(mysqli $conn, int $usersId, string $profileName, string $access, string $refresh, int $accessExpires, int $now = 0): void {
    $now = $now ?: time();
    $stmt = $conn->prepare('REPLACE INTO swuforge_links (usersId, profileName, accessToken, refreshToken, accessExpires, linkedAt, problem)
        VALUES (?, ?, ?, ?, ?, ?, \'\')');
    $stmt->bind_param('isssii', $usersId, $profileName, $access, $refresh, $accessExpires, $now);
    $stmt->execute();
    $stmt->close();
}

function SWUForgeSaveTokens(mysqli $conn, int $usersId, string $access, string $refresh, int $accessExpires): void {
    $stmt = $conn->prepare('UPDATE swuforge_links SET accessToken = ?, refreshToken = ?, accessExpires = ? WHERE usersId = ?');
    $stmt->bind_param('ssii', $access, $refresh, $accessExpires, $usersId);
    $stmt->execute();
    $stmt->close();
}

// A delivery's standing problem for the profile to show: '' | not-enabled (403, the closed pilot) | no-profile (409).
function SWUForgeSetProblem(mysqli $conn, int $usersId, string $problem): void {
    $stmt = $conn->prepare('UPDATE swuforge_links SET problem = ? WHERE usersId = ?');
    $stmt->bind_param('si', $problem, $usersId);
    $stmt->execute();
    $stmt->close();
}

function SWUForgeClearLink(mysqli $conn, int $usersId): void {
    $stmt = $conn->prepare('DELETE FROM swuforge_links WHERE usersId = ?');
    $stmt->bind_param('i', $usersId);
    $stmt->execute();
    $stmt->close();
}

function SWUForgeIsLinked(int $usersId): bool {
    if ($usersId <= 0) return false;
    $conn = GetLocalMySQLConnection();
    try { return SWUForgeLinkReady($conn) && SWUForgeGetLink($conn, $usersId) !== null; }
    finally { $conn->close(); }
}

// A usable access token for this Petranaki account. status: ok | unlinked (never linked) | relink (Forge rejected the
// refresh token — the player disconnected there; the link is cleared) | unavailable (Forge unreachable or erroring —
// the link is kept and the next call tries again).
function SWUForgeAccessToken(int $usersId, bool $forceRefresh = false, int $now = 0): array {
    $now = $now ?: time();
    $none = fn(string $status) => ['status' => $status, 'token' => null];
    $fresh = fn(?array $l) => $l && ((int)$l['accessExpires'] - $now) > SWUFORGE_REFRESH_MARGIN;
    $conn = GetLocalMySQLConnection();
    try {
        if (!SWUForgeLinkReady($conn)) return $none('unlinked');
        $link = SWUForgeGetLink($conn, $usersId);
        if (!$link) return $none('unlinked');
        if (!$forceRefresh && $fresh($link)) return ['status' => 'ok', 'token' => (string)$link['accessToken']];

        $lock = 'swuforge_refresh_' . $usersId;
        $stmt = $conn->prepare('SELECT GET_LOCK(?, 10) AS got');
        $stmt->bind_param('s', $lock);
        $stmt->execute();
        $got = (int)($stmt->get_result()->fetch_assoc()['got'] ?? 0);
        $stmt->close();
        if ($got !== 1) return $none('unavailable');
        try {
            // Re-read under the lock: another request may have refreshed while we waited.
            $before = (string)$link['accessToken'];
            $link = SWUForgeGetLink($conn, $usersId);
            if (!$link) return $none('unlinked');
            if ($fresh($link) && (!$forceRefresh || (string)$link['accessToken'] !== $before)) {
                return ['status' => 'ok', 'token' => (string)$link['accessToken']];
            }
            $r = SWUForgeHttp('POST', SWUForgeConfig()['baseUrl'] . '/auth/petranaki/token',
                ['grant_type' => 'refresh_token', 'refresh_token' => (string)$link['refreshToken']] + _SWUForgeClient());
            $b = is_array($r['body'] ?? null) ? $r['body'] : [];
            if ($r['status'] === 200 && !empty($b['access_token']) && !empty($b['refresh_token'])) {
                SWUForgeSaveTokens($conn, $usersId, (string)$b['access_token'], (string)$b['refresh_token'], $now + (int)($b['expires_in'] ?? 3600));
                return ['status' => 'ok', 'token' => (string)$b['access_token']];
            }
            if (($b['error'] ?? '') === 'invalid_grant') {
                SWUForgeClearLink($conn, $usersId);
                return $none('relink');
            }
            error_log('SWU Forge token refresh failed user=' . $usersId . ' http=' . (int)$r['status']
                . ' error=' . (string)($b['error'] ?? ($r['error'] ?? '')));
            return $none('unavailable');
        } finally {
            $rel = $conn->prepare('SELECT RELEASE_LOCK(?)');
            $rel->bind_param('s', $lock);
            $rel->execute();
            $rel->close();
        }
    } finally {
        $conn->close();
    }
}

// Unlink on our side: revoke at Forge (it ends both tokens), then drop the link whatever Forge answered.
function SWUForgeUnlink(int $usersId): void {
    $conn = GetLocalMySQLConnection();
    try {
        if (!SWUForgeLinkReady($conn)) return;
        $link = SWUForgeGetLink($conn, $usersId);
        if (!$link) return;
        try {
            $r = SWUForgeHttp('POST', SWUForgeConfig()['baseUrl'] . '/auth/petranaki/revoke',
                ['token' => (string)$link['refreshToken'], 'token_type_hint' => 'refresh_token'] + _SWUForgeClient(), [], 10);
            if ($r['status'] !== 200) error_log('SWU Forge revoke failed user=' . $usersId . ' http=' . (int)$r['status']);
        } catch (Throwable $e) {
            error_log('SWU Forge revoke failed user=' . $usersId . ': ' . $e->getMessage());
        }
        SWUForgeClearLink($conn, $usersId);
    } finally {
        $conn->close();
    }
}
