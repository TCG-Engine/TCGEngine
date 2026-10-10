<?php
// Petranaki ↔ SWUStats account link: config, the one HTTP seam, link storage, and the token accessor.
// docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §1
//
// Petranaki is an ordinary OAuth client of SWUStats (as Karabast is). Tokens live in this sim's DB
// (swustats_links) and never reach the browser. A SWUStats refresh ROTATES the pair — it deletes the
// old refresh token — so two refreshes racing for one player would leave the loser holding a dead
// token and the link broken. Every refresh therefore runs under a per-player MySQL lock.
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/functions.inc.php';

const SWUSTATS_REFRESH_MARGIN = 300;                         // refresh when under 5 minutes remain
const SWUSTATS_DEV_CLIENT_ID = 'petranaki-dev';
const SWUSTATS_DEV_CLIENT_SECRET = 'petranaki-dev-secret';   // registered only in the LOCAL swudeck DB

function SWUStatsIsDev(): bool { return getenv('DEVENV') === 'true'; }

// Server-to-server base. Inside the dev container "localhost" is the container itself, so the host's
// :3100 SWUDeck mapping is reached through the Docker host gateway (as StatsSubmit already does).
function SWUStatsServerBase(): string {
    return SWUStatsIsDev() ? 'http://host.docker.internal:3100' : 'https://swustats.net';
}

// Browser-facing base (the authorize page, the "heart a deck" link). Dev uses 127.0.0.1, NOT localhost:
// cookies ignore the port, so signing in to SWUStats on localhost:3100 would overwrite Petranaki's
// PHPSESSID on localhost:3400 and sign the player out of Petranaki mid-flow.
function SWUStatsPublicBase(): string {
    return SWUStatsIsDev() ? 'http://127.0.0.1:3100' : 'https://swustats.net';
}

// Base of the deck LINKS Petranaki plays. DeckImport and SubmitGameResult accept the loopback form only under DEVENV.
function SWUStatsDeckLinkBase(): string {
    return SWUStatsIsDev() ? 'http://localhost:3100' : 'https://swustats.net';
}

// $keysFile: the APIKeys file (a parameter only so a test can hand in a stand-in).
function SWUStatsOAuthConfig(string $keysFile = ''): array {
    static $cfg = null;   // once per request
    if ($cfg !== null) return $cfg;
    $clientId = trim((string)(getenv('SWUSTATS_CLIENT_ID') ?: ''));
    $secret   = trim((string)(getenv('SWUSTATS_CLIENT_SECRET') ?: ''));
    $redirect = trim((string)(getenv('SWUSTATS_REDIRECT_URI') ?: ''));
    $keys = $keysFile !== '' ? $keysFile : __DIR__ . '/../APIKeys/APIKeys.php';
    if (($clientId === '' || $secret === '') && file_exists($keys)) {
        // APIKeys.php declares a const, so a second plain require in a request that already loaded it (the game's
        // ProcessInput.php does, at global scope) warns "Constant already defined" — inside the player's action.
        // Read the globals when it is loaded; otherwise load it here (function scope, as DiscordOAuthConfig does).
        if (in_array(realpath($keys), get_included_files(), true)) {
            $id  = $GLOBALS['swustatsClientID'] ?? '';
            $sec = $GLOBALS['swustatsClientSecret'] ?? '';
        } else {
            [$id, $sec] = (function ($f) { require $f; return [$swustatsClientID ?? '', $swustatsClientSecret ?? '']; })($keys);
        }
        if ($clientId === '') $clientId = trim((string)$id);
        if ($secret === '')   $secret   = trim((string)$sec);
    }
    if (SWUStatsIsDev()) {
        if ($clientId === '') $clientId = SWUSTATS_DEV_CLIENT_ID;
        if ($secret === '')   $secret   = SWUSTATS_DEV_CLIENT_SECRET;
    }
    if ($redirect === '') {
        $redirect = SWUStatsIsDev()
            ? 'http://localhost:3400/TCGEngine/AccountFiles/SWUStatsOAuthCallback.php'
            : 'https://petranaki.net/TCGEngine/AccountFiles/SWUStatsOAuthCallback.php';
    }
    return $cfg = ['clientId' => $clientId, 'clientSecret' => $secret, 'redirectUri' => $redirect];
}

// The ONE way this sim talks to SWUStats. Tests set $GLOBALS['SWUSTATS_HTTP_FAKE'] to a
// fn($method, $url, $body, $headers): array returning this same shape, so no test touches the network.
// $body: array → form-encoded; string → sent as-is (JSON). status 0 = no response at all.
function SWUStatsHttp(string $method, string $url, $body = null, array $headers = [], int $timeout = 8): array {
    $fake = $GLOBALS['SWUSTATS_HTTP_FAKE'] ?? null;
    if (is_callable($fake)) return $fake($method, $url, $body, $headers);
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_HTTPHEADER => $headers];
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

function SWUStatsLinkReady(mysqli $conn): bool { return DBTableExists($conn, 'swustats_links'); }

function SWUStatsGetLink(mysqli $conn, int $usersId): ?array {
    $stmt = $conn->prepare('SELECT * FROM swustats_links WHERE usersId = ? LIMIT 1');
    $stmt->bind_param('i', $usersId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function SWUStatsSaveLink(mysqli $conn, int $usersId, int $ssUserId, string $ssUsername,
                          string $access, string $refresh, int $accessExpires, int $now = 0): void {
    $now = $now ?: time();
    $stmt = $conn->prepare('REPLACE INTO swustats_links
        (usersId, swustatsUserId, swustatsUsername, accessToken, refreshToken, accessExpires, linkedAt)
        VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iisssii', $usersId, $ssUserId, $ssUsername, $access, $refresh, $accessExpires, $now);
    $stmt->execute();
    $stmt->close();
}

function SWUStatsSaveTokens(mysqli $conn, int $usersId, string $access, string $refresh, int $accessExpires): void {
    $stmt = $conn->prepare('UPDATE swustats_links SET accessToken = ?, refreshToken = ?, accessExpires = ? WHERE usersId = ?');
    $stmt->bind_param('ssii', $access, $refresh, $accessExpires, $usersId);
    $stmt->execute();
    $stmt->close();
}

function SWUStatsClearLink(mysqli $conn, int $usersId): void {
    $stmt = $conn->prepare('DELETE FROM swustats_links WHERE usersId = ?');
    $stmt->bind_param('i', $usersId);
    $stmt->execute();
    $stmt->close();
}

function SWUStatsIsLinked(int $usersId): bool {
    if ($usersId <= 0) return false;
    $conn = GetLocalMySQLConnection();
    try { return SWUStatsLinkReady($conn) && SWUStatsGetLink($conn, $usersId) !== null; }
    finally { $conn->close(); }
}

// A usable access token for this Petranaki account. status: ok | unlinked (never linked) |
// relink (SWUStats rejected the refresh token — the link is cleared) | unavailable (SWUStats
// unreachable or erroring — the link is kept and the next call tries again).
function SWUStatsAccessToken(int $usersId, bool $forceRefresh = false, int $now = 0): array {
    $now = $now ?: time();
    $none = function (string $status) { return ['status' => $status, 'token' => null]; };
    $fresh = function (?array $l) use ($now) { return $l && ((int)$l['accessExpires'] - $now) > SWUSTATS_REFRESH_MARGIN; };
    $conn = GetLocalMySQLConnection();
    try {
        if (!SWUStatsLinkReady($conn)) return $none('unlinked');
        $link = SWUStatsGetLink($conn, $usersId);
        if (!$link) return $none('unlinked');
        if (!$forceRefresh && $fresh($link)) return ['status' => 'ok', 'token' => (string)$link['accessToken']];

        $lock = 'swustats_refresh_' . $usersId;
        $stmt = $conn->prepare('SELECT GET_LOCK(?, 10) AS got');
        $stmt->bind_param('s', $lock);
        $stmt->execute();
        $got = (int)($stmt->get_result()->fetch_assoc()['got'] ?? 0);
        $stmt->close();
        if ($got !== 1) return $none('unavailable');
        try {
            // Re-read under the lock: another request may have refreshed while we waited, and its
            // refresh deleted the token we read above.
            $before = (string)$link['accessToken'];
            $link = SWUStatsGetLink($conn, $usersId);
            if (!$link) return $none('unlinked');
            if ($fresh($link) && (!$forceRefresh || (string)$link['accessToken'] !== $before)) {
                return ['status' => 'ok', 'token' => (string)$link['accessToken']];
            }
            $cfg = SWUStatsOAuthConfig();
            $r = SWUStatsHttp('POST', SWUStatsServerBase() . '/TCGEngine/APIs/OAuth/token.php', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => (string)$link['refreshToken'],
                'client_id'     => $cfg['clientId'],
                'client_secret' => $cfg['clientSecret'],
            ]);
            $b = is_array($r['body'] ?? null) ? $r['body'] : [];
            if ($r['status'] === 200 && !empty($b['access_token']) && !empty($b['refresh_token'])) {
                SWUStatsSaveTokens($conn, $usersId, (string)$b['access_token'], (string)$b['refresh_token'],
                                   $now + (int)($b['expires_in'] ?? 3600));
                return ['status' => 'ok', 'token' => (string)$b['access_token']];
            }
            if (($b['error'] ?? '') === 'invalid_grant') {
                SWUStatsClearLink($conn, $usersId);
                return $none('relink');
            }
            error_log('SWUStats token refresh failed user=' . $usersId . ' http=' . (int)$r['status']
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
