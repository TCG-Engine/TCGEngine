<?php
// Petranaki ↔ SWU Forge link: config, storage, the token accessor and revoke. Test-only account ids; every row written
// is removed at start and in finally. No network: SWUForgeHttp is faked.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swuforge_link.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './SWUSim/SWUForgeLink.php';

const F_LINK = 990000201;
const F_LOCK = 990000202;
const NOW = 1800000000;

// Child mode (the lock case): started while the parent HOLDS the refresh lock. It must wait, then see the tokens the
// parent stored meanwhile and return them without refreshing a second time.
if (($argv[1] ?? '') === 'child') {
    $GLOBALS['SWUFORGE_CONFIG'] = ['baseUrl' => 'https://forge.test', 'clientId' => 'petranaki', 'clientSecret' => 'sec', 'redirectUri' => 'http://localhost:3400/cb'];
    $calls = 0;
    $GLOBALS['SWUFORGE_HTTP_FAKE'] = function () use (&$calls) { $calls++; return ['status' => 500, 'body' => null, 'raw' => '']; };
    echo json_encode(['r' => SWUForgeAccessToken(F_LOCK, false, NOW), 'calls' => $calls]), "\n";
    exit(0);
}

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

// ── Config: the Forge base URL, client id and secret are settings (staging now, production later) ────────────────
$keys = tempnam(sys_get_temp_dir(), 'swuforgekeys');
file_put_contents($keys, '<?php $swuforgeOAuthKey = "sec-from-keys"; $swuforgeBaseURL = "https://forge.example/"; $swuforgeClientID = "cid-from-keys";');
unset($GLOBALS['SWUFORGE_CONFIG']);
$cfg = SWUForgeConfig($keys);
check('config reads the secret, base URL and client id from the APIKeys file', $cfg['clientSecret'] === 'sec-from-keys'
    && $cfg['baseUrl'] === 'https://forge.example' && $cfg['clientId'] === 'cid-from-keys', json_encode($cfg));
file_put_contents($keys, '<?php $swuforgeOAuthKey = "sec2";');
unset($GLOBALS['SWUFORGE_CONFIG']);
$cfg = SWUForgeConfig($keys);
check('no Forge address in code: without $swuforgeBaseURL the base URL is empty; client id defaults to "petranaki"', $cfg['baseUrl'] === '' && $cfg['clientId'] === 'petranaki', json_encode($cfg));
$GLOBALS['SWUFORGE_CONFIG'] = ['clientSecret' => 'sec2'] + $cfg;   // set outright: this keys file's path was already loaded once
check('a secret without a Forge address is NOT configured (SWU Forge stays off rather than guessing an environment)', SWUForgeConfigured() === false);
$GLOBALS['SWUFORGE_CONFIG'] = ['baseUrl' => 'https://forge.example', 'clientSecret' => 'sec2'] + $cfg;
check('a secret and an address are configured', SWUForgeConfigured() === true);
unset($GLOBALS['SWUFORGE_CONFIG']);
check('dev redirect URI is the registered localhost callback', $cfg['redirectUri'] === 'http://localhost:3400/TCGEngine/AccountFiles/SWUForgeOAuthCallback.php', $cfg['redirectUri']);
putenv('SWUFORGE_BASE_URL=https://override.example'); unset($GLOBALS['SWUFORGE_CONFIG']);
check('an environment variable wins over the APIKeys file', SWUForgeConfig($keys)['baseUrl'] === 'https://override.example');
putenv('SWUFORGE_BASE_URL'); unlink($keys);
unset($GLOBALS['SWUFORGE_CONFIG']);
$GLOBALS['SWUFORGE_CONFIG'] = ['baseUrl' => 'https://forge.test', 'clientId' => 'petranaki', 'clientSecret' => 'sec', 'redirectUri' => 'http://localhost:3400/cb'];

$conn = GetLocalMySQLConnection();
if (!SWUForgeLinkReady($conn)) {
    echo "SKIP: swuforge_links is not in this database. Run Database/migrations/20_swuforge_link.sql first.\n";
    exit(2);
}
$cleanup = function () use ($conn) { $conn->query('DELETE FROM swuforge_links WHERE usersId IN (' . F_LINK . ',' . F_LOCK . ')'); };
$link = fn($u) => SWUForgeGetLink($conn, $u);
$calls = [];
$fake = function (array $resp) use (&$calls) {
    $GLOBALS['SWUFORGE_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$calls, $resp) {
        $calls[] = ['m' => $m, 'url' => $url, 'body' => $body, 'h' => $h];
        return $resp;
    };
};

$cleanup();
try {
    // 1. Not linked: nothing to refresh, nothing sent.
    $calls = []; $fake(['status' => 200, 'body' => [], 'raw' => '']);
    check('unlinked → status unlinked', SWUForgeAccessToken(F_LINK, false, NOW) === ['status' => 'unlinked', 'token' => null]);
    check('unlinked → no HTTP', count($calls) === 0);

    // 2. A token with an hour left is returned as-is.
    SWUForgeSaveLink($conn, F_LINK, 'Drixx Decks', 'acc-1', 'ref-1', NOW + 3600, NOW);
    $calls = [];
    check('fresh token returned as-is', SWUForgeAccessToken(F_LINK, false, NOW) === ['status' => 'ok', 'token' => 'acc-1']);
    check('fresh token → no HTTP', count($calls) === 0);

    // 3. Near expiry: refreshed at the token endpoint, the rotated pair stored.
    SWUForgeSaveTokens($conn, F_LINK, 'acc-1', 'ref-1', NOW + 60);
    $calls = []; $fake(['status' => 200, 'body' => ['access_token' => 'acc-2', 'refresh_token' => 'ref-2', 'expires_in' => 3600], 'raw' => '']);
    $r = SWUForgeAccessToken(F_LINK, false, NOW);
    check('near expiry → refreshed token', $r === ['status' => 'ok', 'token' => 'acc-2'], json_encode($r));
    check('refresh posts to /auth/petranaki/token with the refresh grant', count($calls) === 1 && $calls[0]['m'] === 'POST'
        && $calls[0]['url'] === 'https://forge.test/auth/petranaki/token' && ($calls[0]['body']['grant_type'] ?? '') === 'refresh_token'
        && ($calls[0]['body']['refresh_token'] ?? '') === 'ref-1' && ($calls[0]['body']['client_id'] ?? '') === 'petranaki'
        && ($calls[0]['body']['client_secret'] ?? '') === 'sec', json_encode($calls));
    $l = $link(F_LINK);
    check('the rotated pair is stored', $l['accessToken'] === 'acc-2' && $l['refreshToken'] === 'ref-2' && (int)$l['accessExpires'] === NOW + 3600);

    // 4. Forced (a delivery got 401): refreshed even though the token looks fresh.
    $calls = []; $fake(['status' => 200, 'body' => ['access_token' => 'acc-3', 'refresh_token' => 'ref-3', 'expires_in' => 3600], 'raw' => '']);
    check('forced refresh', SWUForgeAccessToken(F_LINK, true, NOW) === ['status' => 'ok', 'token' => 'acc-3'] && count($calls) === 1);

    // 5. Forge is down: the link is kept, the caller is told to try later.
    SWUForgeSaveTokens($conn, F_LINK, 'acc-3', 'ref-3', NOW + 10);
    $fake(['status' => 0, 'body' => null, 'raw' => '', 'error' => 'timeout']);
    check('Forge unreachable → unavailable, link kept', SWUForgeAccessToken(F_LINK, false, NOW)['status'] === 'unavailable' && $link(F_LINK) !== null);

    // 6. invalid_grant: the player disconnected on Forge's side — treated as unlinked, the link cleared.
    $fake(['status' => 400, 'body' => ['error' => 'invalid_grant'], 'raw' => '']);
    check('invalid_grant → relink, link cleared', SWUForgeAccessToken(F_LINK, false, NOW) === ['status' => 'relink', 'token' => null] && $link(F_LINK) === null);

    // 7. Two refreshes for one player wait on each other: the second sees the first one's tokens.
    SWUForgeSaveLink($conn, F_LOCK, 'p', 'acc-a', 'ref-a', NOW + 10, NOW);
    $lockName = 'swuforge_refresh_' . F_LOCK;
    $lc = GetLocalMySQLConnection();
    $st = $lc->prepare('SELECT GET_LOCK(?, 5)'); $st->bind_param('s', $lockName); $st->execute(); $st->close();
    $child = proc_open([PHP_BINARY, __FILE__, 'child'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    usleep(400000);
    SWUForgeSaveTokens($conn, F_LOCK, 'acc-b', 'ref-b', NOW + 3600);
    $st = $lc->prepare('SELECT RELEASE_LOCK(?)'); $st->bind_param('s', $lockName); $st->execute(); $st->close();
    $out = json_decode(trim(stream_get_contents($pipes[1])), true); proc_close($child); $lc->close();
    check('a refresh waiting on the lock returns the tokens stored meanwhile, without refreshing again',
        ($out['r'] ?? null) === ['status' => 'ok', 'token' => 'acc-b'] && ($out['calls'] ?? -1) === 0, json_encode($out));

    // 8. Revoke (unlink on our side): Forge's revoke endpoint gets the refresh token, then the link is gone.
    SWUForgeSaveLink($conn, F_LINK, 'p', 'acc-x', 'ref-x', NOW + 3600, NOW);
    $calls = []; $fake(['status' => 200, 'body' => [], 'raw' => '']);
    SWUForgeUnlink(F_LINK);
    check('unlink calls /auth/petranaki/revoke with the refresh token and client credentials', count($calls) === 1
        && $calls[0]['url'] === 'https://forge.test/auth/petranaki/revoke' && ($calls[0]['body']['token'] ?? '') === 'ref-x'
        && ($calls[0]['body']['client_secret'] ?? '') === 'sec', json_encode($calls));
    check('unlink clears the link', $link(F_LINK) === null);
    SWUForgeSaveLink($conn, F_LINK, 'p', 'acc-x', 'ref-x', NOW + 3600, NOW);
    $fake(['status' => 0, 'body' => null, 'raw' => '', 'error' => 'down']);
    SWUForgeUnlink(F_LINK);
    check('unlink clears the link even when Forge cannot be reached', $link(F_LINK) === null);

    // 8b. No Forge address configured: a token due a refresh is not refreshed (the link is kept), and an unlink clears
    // the local link without calling anyone.
    $GLOBALS['SWUFORGE_CONFIG']['baseUrl'] = '';
    SWUForgeSaveLink($conn, F_LINK, 'p', 'acc-y', 'ref-y', NOW + 10, NOW);
    $calls = []; $fake(['status' => 200, 'body' => ['access_token' => 'x', 'refresh_token' => 'y', 'expires_in' => 3600], 'raw' => '']);
    check('no Forge address → a stale token is not refreshed, and the link is kept', SWUForgeAccessToken(F_LINK, false, NOW)['status'] === 'unavailable'
        && $calls === [] && $link(F_LINK) !== null, json_encode($calls));
    SWUForgeUnlink(F_LINK);
    check('no Forge address → unlink clears the link without an HTTP call', $calls === [] && $link(F_LINK) === null, json_encode($calls));
    $GLOBALS['SWUFORGE_CONFIG']['baseUrl'] = 'https://forge.test';

    // 9. The standing problem a delivery reports (403 / 409) is shown on the profile and cleared by a success.
    SWUForgeSaveLink($conn, F_LINK, 'p', 'acc-x', 'ref-x', NOW + 3600, NOW);
    SWUForgeSetProblem($conn, F_LINK, 'not-enabled');
    check('a problem is stored on the link', $link(F_LINK)['problem'] === 'not-enabled');
    SWUForgeSetProblem($conn, F_LINK, '');
    check('and cleared', $link(F_LINK)['problem'] === '');
} finally {
    $cleanup();
    $conn->close();
}

foreach ($MSGS as $m) echo $m, "\n";
echo $FAIL === 0 ? "PASS: test_swuforge_link.php — all $PASS checks passed\n" : "FAIL: test_swuforge_link.php — $FAIL of " . ($PASS + $FAIL) . " checks failed\n";
exit($FAIL === 0 ? 0 : 1);
