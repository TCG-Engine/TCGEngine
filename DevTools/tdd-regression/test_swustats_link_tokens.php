<?php
// Petranaki ↔ SWUStats link: storage + token accessor (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §1).
// Test-only account ids; every row written is removed at start and in finally. No network: SWUStatsHttp is faked.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swustats_link_tokens.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './SWUSim/SWUStatsLink.php';

const U_LINK = 990000101;
const U_LOCK = 990000102;
const NOW = 1800000000;

// Child mode (the lock case): started while the parent HOLDS the refresh lock. It must wait, then see the
// tokens the parent stored meanwhile and return them without refreshing a second time.
if (($argv[1] ?? '') === 'child') {
    $calls = 0;
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = function () use (&$calls) { $calls++; return ['status' => 500, 'body' => null, 'raw' => '']; };
    echo json_encode(['r' => SWUStatsAccessToken(U_LOCK, false, NOW), 'calls' => $calls]), "\n";
    exit(0);
}

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

$conn = GetLocalMySQLConnection();
if (!SWUStatsLinkReady($conn)) {
    echo "SKIP: swustats_links is not in this database. Run Database/migrations/18_swustats_link.sql first.\n";
    exit(2);
}
$cleanup = function () use ($conn) { $conn->query('DELETE FROM swustats_links WHERE usersId IN (' . U_LINK . ',' . U_LOCK . ')'); };
$link = function ($u) use ($conn) { return SWUStatsGetLink($conn, $u); };
$calls = [];
$fake = function (array $resp) use (&$calls) {
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$calls, $resp) {
        $calls[] = ['m' => $m, 'url' => $url, 'body' => $body, 'h' => $h];
        return $resp;
    };
};

$cleanup();
try {
    // 1. Not linked: nothing to refresh, nothing sent.
    $calls = []; $fake(['status' => 200, 'body' => [], 'raw' => '']);
    $r = SWUStatsAccessToken(U_LINK, false, NOW);
    check('unlinked → status unlinked', $r === ['status' => 'unlinked', 'token' => null], json_encode($r));
    check('unlinked → no HTTP', count($calls) === 0);

    // 2. A token with an hour left is returned as-is.
    SWUStatsSaveLink($conn, U_LINK, 5, 'Drixx', 'acc-1', 'ref-1', NOW + 3600, NOW);
    $calls = [];
    $r = SWUStatsAccessToken(U_LINK, false, NOW);
    check('fresh token returned as-is', $r === ['status' => 'ok', 'token' => 'acc-1'], json_encode($r));
    check('fresh token → no HTTP', count($calls) === 0);

    // 3. Under the 5-minute margin → refresh, and the ROTATED pair is stored.
    SWUStatsSaveTokens($conn, U_LINK, 'acc-1', 'ref-1', NOW + 100);
    $calls = []; $fake(['status' => 200, 'body' => ['access_token' => 'acc-2', 'refresh_token' => 'ref-2', 'expires_in' => 3600], 'raw' => '']);
    $r = SWUStatsAccessToken(U_LINK, false, NOW);
    $c = $calls[0] ?? ['m' => '', 'url' => '', 'body' => []];
    check('refresh returns the new token', $r === ['status' => 'ok', 'token' => 'acc-2'], json_encode($r));
    check('refresh POSTs token.php once', count($calls) === 1 && $c['m'] === 'POST'
        && substr($c['url'], -31) === '/TCGEngine/APIs/OAuth/token.php', json_encode($calls));
    check('refresh sends the grant, the OLD refresh token and client creds',
        ($c['body']['grant_type'] ?? '') === 'refresh_token' && ($c['body']['refresh_token'] ?? '') === 'ref-1'
        && ($c['body']['client_id'] ?? '') !== '' && array_key_exists('client_secret', (array)$c['body']), json_encode($c['body']));
    $row = $link(U_LINK);
    check('refresh persists the rotated pair', $row && $row['accessToken'] === 'acc-2' && $row['refreshToken'] === 'ref-2'
        && (int)$row['accessExpires'] === NOW + 3600, json_encode($row));
    check('refresh keeps the linked identity', $row && (int)$row['swustatsUserId'] === 5 && $row['swustatsUsername'] === 'Drixx');

    // 4. SWUStats unreachable / erroring / rejecting OUR client → unavailable, link untouched.
    SWUStatsSaveTokens($conn, U_LINK, 'acc-2', 'ref-2', NOW - 10);
    $fake(['status' => 0, 'body' => null, 'raw' => '']);
    check('no response → unavailable', SWUStatsAccessToken(U_LINK, false, NOW) === ['status' => 'unavailable', 'token' => null]);
    check('no response keeps the link', ($link(U_LINK)['refreshToken'] ?? '') === 'ref-2');
    $fake(['status' => 500, 'body' => ['error' => 'server_error'], 'raw' => '']);
    check('5xx → unavailable, link kept', SWUStatsAccessToken(U_LINK, false, NOW)['status'] === 'unavailable' && $link(U_LINK) !== null);
    $fake(['status' => 401, 'body' => ['error' => 'invalid_client'], 'raw' => '']);
    check('invalid_client (our config) → unavailable, link kept',
        SWUStatsAccessToken(U_LINK, false, NOW)['status'] === 'unavailable' && $link(U_LINK) !== null);

    // 5. invalid_grant: the refresh token is dead → the link is cleared, the player must reconnect.
    $fake(['status' => 400, 'body' => ['error' => 'invalid_grant'], 'raw' => '']);
    check('invalid_grant → relink', SWUStatsAccessToken(U_LINK, false, NOW) === ['status' => 'relink', 'token' => null]);
    check('invalid_grant deletes the link', $link(U_LINK) === null);

    // 6. forceRefresh refreshes even a fresh token (used after SWUStats 401s a token we thought valid).
    SWUStatsSaveLink($conn, U_LINK, 5, 'Drixx', 'acc-3', 'ref-3', NOW + 3600, NOW);
    $calls = []; $fake(['status' => 200, 'body' => ['access_token' => 'acc-4', 'refresh_token' => 'ref-4', 'expires_in' => 3600], 'raw' => '']);
    $r = SWUStatsAccessToken(U_LINK, true, NOW);
    check('forceRefresh refreshes a fresh token', $r['token'] === 'acc-4' && count($calls) === 1, json_encode($r));

    // 7. Waiting on the lock while another request refreshes → use ITS tokens, do not refresh again.
    SWUStatsSaveLink($conn, U_LOCK, 5, 'Drixx', 'old-acc', 'old-ref', NOW - 10, NOW);
    $holder = GetLocalMySQLConnection();
    $lk = 'swustats_refresh_' . U_LOCK;
    $holder->query("SELECT GET_LOCK('$lk', 5)");
    $proc = proc_open([PHP_BINARY, __FILE__, 'child'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, getcwd(), null);
    sleep(1);   // let the child reach GET_LOCK and block on it
    SWUStatsSaveTokens($holder, U_LOCK, 'fresh-acc', 'fresh-ref', NOW + 3600);
    $holder->query("SELECT RELEASE_LOCK('$lk')");
    $out = json_decode(trim((string)stream_get_contents($pipes[1])), true);
    $err = (string)stream_get_contents($pipes[2]);
    proc_close($proc);
    $holder->close();
    check('lock waiter returns the token the holder stored', ($out['r']['token'] ?? '') === 'fresh-acc', json_encode($out) . ' ' . $err);
    check('lock waiter does not refresh again', ($out['calls'] ?? -1) === 0, json_encode($out));
} finally {
    $cleanup();
    unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
}
echo ($FAIL === 0 ? "PASS ($PASS checks)" : "FAIL ($FAIL of " . ($PASS + $FAIL) . ")\n" . implode("\n", $MSGS)) . "\n";
exit($FAIL === 0 ? 0 : 1);
