<?php
// SWU-PGN writer identity: the server secret, keyed ids (SWU-PGN/1.0 §17), GameId, and the Engine build id (§5.3).

// The writer's server secret: env SWUPGN_ID_SECRET, else $swupgnIdSecret in APIKeys/APIKeys.php, else a fixed dev value
// under DEVENV, else ''. The env is read on every call; only the APIKeys file read is cached.
// ($GLOBALS['SWUPGN_KEYS_FILE'] names a stand-in APIKeys file for tests.)
function SwuPgnIdSecret(): string {
    $env = trim((string)(getenv('SWUPGN_ID_SECRET') ?: ''));
    if ($env !== '') return $env;
    static $fromFile = [];
    $keys = (string)($GLOBALS['SWUPGN_KEYS_FILE'] ?? (__DIR__ . '/../../../APIKeys/APIKeys.php'));
    if (!array_key_exists($keys, $fromFile)) {
        $val = '';
        if (file_exists($keys)) {
            // APIKeys.php declares a const: never require it twice in one request (see SWUStatsOAuthConfig).
            if (in_array(realpath($keys), get_included_files(), true)) $val = (string)($GLOBALS['swupgnIdSecret'] ?? '');
            else $val = (string)(function ($f) { require $f; return $swupgnIdSecret ?? ''; })($keys);
        }
        $fromFile[$keys] = trim($val);
    }
    if ($fromFile[$keys] !== '') return $fromFile[$keys];
    return getenv('DEVENV') === 'true' ? 'dev-swupgn-secret' : '';
}

// A purpose-separated key derived from the secret ('id' for hashed ids, 'seal' for sealed replays); '' without a secret.
function SwuPgnSubKey(string $purpose): string {
    $secret = SwuPgnIdSecret();
    return $secret === '' ? '' : hash_hmac('sha256', 'swupgn:' . $purpose, $secret, true);
}

// 'sha256:<hex>' for $value. Keyed HMAC when the secret is set (the same value hashes the same across games, §17's
// published scheme); otherwise §17's per-game salt sha256("<gameSalt>:<value>").
function SwuPgnHashId(string $value, string $gameSalt): string {
    $key = SwuPgnSubKey('id');
    return 'sha256:' . ($key !== '' ? hash_hmac('sha256', $value, $key) : hash('sha256', $gameSalt . ':' . $value));
}

// An opaque, GUID-shaped id for one game, stable across re-exports and identical in all three files.
function SwuPgnGameId(string $root, string $gameName): string {
    $h = substr(SwuPgnHashId("game:$root:$gameName", "$root:$gameName"), 7, 32);
    return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4) . '-' . substr($h, 16, 4) . '-' . substr($h, 20, 12);
}

// 'petranaki@<7-char sha>' read from .git (loose ref, packed-refs, or a detached HEAD); 'petranaki@unknown' on any failure.
// No '-dirty' marker (owner ruling 2026-10-19: the build server always has untracked files).
function SwuPgnEngineId(?string $repoRoot = null): string {
    $git = rtrim($repoRoot ?? (__DIR__ . '/../../..'), '/') . '/.git';
    $read = function (string $f) { clearstatcache(true, $f); return is_file($f) ? file_get_contents($f) : false; };
    $head = $read("$git/HEAD");
    $sha = null;
    if (is_string($head)) {
        $head = trim($head);
        if (preg_match('/^ref:\s*(\S+)$/', $head, $m)) {
            $loose = $read("$git/" . $m[1]);
            if (is_string($loose) && preg_match('/^[0-9a-f]{40}/', trim($loose))) $sha = trim($loose);
            else {
                $packed = $read("$git/packed-refs");
                if (is_string($packed) && preg_match('/^([0-9a-f]{40}) ' . preg_quote($m[1], '/') . '$/m', $packed, $p)) $sha = $p[1];
            }
        } elseif (preg_match('/^[0-9a-f]{40}$/', $head)) {
            $sha = $head;
        }
    }
    return 'petranaki@' . ($sha !== null ? substr($sha, 0, 7) : 'unknown');
}
