<?php
// Petranaki ↔ SWUStats link: the connect flow + Profile panel (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §1).
// No network (SWUStatsHttp faked). Test-only account id; its link row is removed at start and in finally.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swustats_oauth_flow.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
chdir(dirname(__DIR__, 2));
require_once './AccountFiles/SWUStatsOAuth.php';
require_once './SharedUI/Render/Profile.php';

const U_FLOW = 990000121;
const NOW = 1800000000;

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}
$throws = function (callable $fn) { try { $fn(); return false; } catch (RuntimeException $e) { return true; } };

$conn = GetLocalMySQLConnection();
if (!SWUStatsLinkReady($conn)) { echo "SKIP: run Database/migrations/18_swustats_link.sql first.\n"; exit(2); }
$cleanup = function () use ($conn) { $conn->query('DELETE FROM swustats_links WHERE usersId = ' . U_FLOW); };
$calls = [];
$route = function (array $byPath) use (&$calls) {
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$calls, $byPath) {
        $calls[] = ['m' => $m, 'url' => $url, 'body' => $body, 'h' => $h];
        foreach ($byPath as $suffix => $resp) if (strpos($url, $suffix) !== false) return $resp;
        return ['status' => 404, 'body' => null, 'raw' => ''];
    };
};

$cleanup();
CheckSession();
try {
    // ── state ────────────────────────────────────────────────────────────────
    $state = SWUStatsOAuthBeginFlow(U_FLOW, '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php', NOW);
    check('state is 64 hex chars', (bool)preg_match('/^[0-9a-f]{64}$/', $state), $state);
    $flow = SWUStatsOAuthConsumeFlow($state, NOW + 5);
    check('flow carries the account that started it', (int)$flow['usersId'] === U_FLOW);
    check('flow carries the safe return', $flow['redirect'] === '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php');
    check('a state is single-use', $throws(fn() => SWUStatsOAuthConsumeFlow($state, NOW + 6)));
    $old = SWUStatsOAuthBeginFlow(U_FLOW, '', NOW);
    check('an expired state is refused', $throws(fn() => SWUStatsOAuthConsumeFlow($old, NOW + 601)));
    check('an unknown state is refused', $throws(fn() => SWUStatsOAuthConsumeFlow(str_repeat('a', 64), NOW)));
    check('an off-site return is replaced by the Profile', SWUStatsOAuthSafeReturn('https://evil.example/x') === '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php');

    // ── authorize URL ────────────────────────────────────────────────────────
    $u = SWUStatsOAuthAuthorizeUrl('abc');
    parse_str((string)parse_url($u, PHP_URL_QUERY), $q);
    $cfg = SWUStatsOAuthConfig();
    check('authorize goes to the PUBLIC base (127.0.0.1 in dev, never localhost)',
        strpos($u, SWUStatsPublicBase() . '/TCGEngine/APIs/OAuth/authorize.php?') === 0
        && (getenv('DEVENV') !== 'true' || strpos($u, 'http://127.0.0.1:3100/') === 0), $u);
    check('authorize asks for code + profile decks + our redirect + state',
        ($q['response_type'] ?? '') === 'code' && ($q['scope'] ?? '') === 'profile decks'
        && ($q['redirect_uri'] ?? '') === $cfg['redirectUri'] && ($q['state'] ?? '') === 'abc'
        && ($q['client_id'] ?? '') === $cfg['clientId'], json_encode($q));

    // ── canonical host ───────────────────────────────────────────────────────
    $rp = parse_url($cfg['redirectUri']);
    $canon = $rp['host'] . (isset($rp['port']) ? ':' . $rp['port'] : '');
    check('on the redirect URI host → no hop', SWUStatsOAuthCanonicalStartUrl($canon, '/x') === null);
    check('host compare ignores case', SWUStatsOAuthCanonicalStartUrl(strtoupper($canon), '/x') === null);
    $hop = SWUStatsOAuthCanonicalStartUrl('www.' . $canon, '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php');
    check('on another host → hop to the Start URL on the redirect URI host',
        $hop === $rp['scheme'] . '://' . $canon . '/TCGEngine/AccountFiles/SWUStatsOAuthStart.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FSWUSim%2FProfile.php', (string)$hop);

    // ── code exchange ────────────────────────────────────────────────────────
    $calls = [];
    $route([
        '/APIs/OAuth/token.php'    => ['status' => 200, 'body' => ['access_token' => 'acc-x', 'refresh_token' => 'ref-x', 'expires_in' => 3600], 'raw' => ''],
        '/APIs/OAuth/userinfo.php' => ['status' => 200, 'body' => ['id' => '5', 'username' => 'Drixx'], 'raw' => ''],
    ]);
    $who = SWUStatsOAuthCompleteLink(U_FLOW, 'the-code', NOW);
    $t = $calls[0] ?? ['body' => []]; $me = $calls[1] ?? ['h' => []];
    check('exchange POSTs the code with our redirect + creds', ($t['m'] ?? '') === 'POST'
        && ($t['body']['grant_type'] ?? '') === 'authorization_code' && ($t['body']['code'] ?? '') === 'the-code'
        && ($t['body']['redirect_uri'] ?? '') === $cfg['redirectUri'] && ($t['body']['client_id'] ?? '') === $cfg['clientId'], json_encode($t));
    check('exchange goes server-to-server', strpos($t['url'] ?? '', SWUStatsServerBase()) === 0, $t['url'] ?? '');
    check('userinfo is asked with the new Bearer token', in_array('Authorization: Bearer acc-x', (array)$me['h'], true), json_encode($me));
    check('returns who was linked', $who === ['swustatsUserId' => 5, 'swustatsUsername' => 'Drixx'], json_encode($who));
    $row = SWUStatsGetLink($conn, U_FLOW);
    check('the link is stored', $row && (int)$row['swustatsUserId'] === 5 && $row['accessToken'] === 'acc-x'
        && $row['refreshToken'] === 'ref-x' && (int)$row['accessExpires'] === NOW + 3600, json_encode($row));

    // Apache does not hand a Bearer header to userinfo.php (it reads only HTTP_AUTHORIZATION) → "Access token required".
    // userinfo.php documents ?access_token= as its fallback; the link must still complete through it.
    $cleanup();
    $calls = [];
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$calls) {
        $calls[] = ['m' => $m, 'url' => $url, 'h' => $h];
        if (strpos($url, '/APIs/OAuth/token.php') !== false)
            return ['status' => 200, 'body' => ['access_token' => 'acc-q', 'refresh_token' => 'ref-q', 'expires_in' => 3600], 'raw' => ''];
        if (strpos($url, 'access_token=acc-q') !== false) return ['status' => 200, 'body' => ['id' => '5', 'username' => 'Drixx'], 'raw' => ''];
        return ['status' => 401, 'body' => ['error' => 'invalid_token', 'error_description' => 'Access token required'], 'raw' => ''];
    };
    $who = SWUStatsOAuthCompleteLink(U_FLOW, 'code-q', NOW);
    check('a stripped Bearer header falls back to ?access_token=', $who['swustatsUserId'] === 5 && count($calls) === 3
        && strpos($calls[2]['url'], '/APIs/OAuth/userinfo.php?access_token=acc-q') !== false, json_encode($calls));
    check('…and the link is stored', (SWUStatsGetLink($conn, U_FLOW)['accessToken'] ?? '') === 'acc-q');
    $cleanup();
    $calls = [];
    $route([
        '/APIs/OAuth/token.php'    => ['status' => 200, 'body' => ['access_token' => 'a2', 'refresh_token' => 'r2'], 'raw' => ''],
        '/APIs/OAuth/userinfo.php' => ['status' => 401, 'body' => ['error' => 'invalid_token', 'error_description' => 'The access token is invalid or has expired'], 'raw' => ''],
    ]);
    check('a REJECTED token is not retried in the URL', $throws(fn() => SWUStatsOAuthCompleteLink(U_FLOW, 'c2', NOW)) && count($calls) === 2, json_encode($calls));

    $cleanup();
    $route(['/APIs/OAuth/token.php' => ['status' => 400, 'body' => ['error' => 'invalid_grant'], 'raw' => '']]);
    check('a rejected code throws', $throws(fn() => SWUStatsOAuthCompleteLink(U_FLOW, 'bad', NOW)));
    check('a rejected code stores nothing', SWUStatsGetLink($conn, U_FLOW) === null);
    $route([
        '/APIs/OAuth/token.php'    => ['status' => 200, 'body' => ['access_token' => 'a', 'refresh_token' => 'r'], 'raw' => ''],
        '/APIs/OAuth/userinfo.php' => ['status' => 200, 'body' => ['username' => 'NoId'], 'raw' => ''],
    ]);
    check('userinfo without an id throws', $throws(fn() => SWUStatsOAuthCompleteLink(U_FLOW, 'c', NOW)));
    check('userinfo without an id stores nothing', SWUStatsGetLink($conn, U_FLOW) === null);
    check('an empty code throws without calling SWUStats', $throws(function () use (&$calls) { $calls = []; SWUStatsOAuthCompleteLink(U_FLOW, '', NOW); }) && count($calls) === 0);

    // ── Profile panel ────────────────────────────────────────────────────────
    $def = ['identity' => ['rootName' => 'SWUSim'], 'profile' => ['swustatsLink' => true]];
    $_SESSION['userid'] = U_FLOW;
    $html = _DisplaySWUStatsLink($def);
    check('unlinked → Connect button to the Start URL', strpos($html, 'Connect SWUStats') !== false
        && strpos($html, '/TCGEngine/AccountFiles/SWUStatsOAuthStart.php?redirect=') !== false, $html);
    SWUStatsSaveLink($conn, U_FLOW, 5, 'Drixx', 'a', 'r', NOW + 3600, NOW);
    $html = _DisplaySWUStatsLink($def);
    check('linked → Connected as the SWUStats name', strpos($html, 'Connected as <b>Drixx</b>') !== false, $html);
    check('linked → Disconnect via DisconnectOAuth type=swustats', strpos($html, 'Disconnect SWUStats') !== false
        && strpos($html, 'DisconnectOAuth.php?type=swustats') !== false, $html);
    $_GET['swustats_error'] = '<b>nope</b>';
    $html = _DisplaySWUStatsLink($def);
    check('a flow error is shown, escaped', strpos($html, '&lt;b&gt;nope&lt;/b&gt;') !== false && strpos($html, '<b>nope</b>') === false, $html);
    unset($_GET['swustats_error']);
    $welcome = _ProfileWelcome($def, ['username' => 'claudebot1']);
    check('the Welcome panel includes the SWUStats section when the site opts in', strpos($welcome, 'swustats-link') !== false);
    $welcomeOff = _ProfileWelcome(['identity' => ['rootName' => 'SWUDeck'], 'profile' => []], ['username' => 'x']);
    check('other sites do not get it', strpos($welcomeOff, 'swustats-link') === false);
} finally {
    $cleanup();
    unset($GLOBALS['SWUSTATS_HTTP_FAKE'], $_SESSION['userid']);
}
echo ($FAIL === 0 ? "PASS ($PASS checks)" : "FAIL ($FAIL of " . ($PASS + $FAIL) . ")\n" . implode("\n", $MSGS)) . "\n";
exit($FAIL === 0 ? 0 : 1);
