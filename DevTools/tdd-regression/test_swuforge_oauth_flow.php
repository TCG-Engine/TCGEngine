<?php
// Petranaki ↔ SWU Forge link: the connect flow + Profile panel. No network (SWUForgeHttp faked). Test-only account id;
// its link row is removed at start and in finally.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swuforge_oauth_flow.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
chdir(dirname(__DIR__, 2));
require_once './AccountFiles/SWUForgeOAuth.php';
require_once './SharedUI/Render/Profile.php';

const F_FLOW = 990000221;
const NOW = 1800000000;

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}
$throws = function (callable $fn) { try { $fn(); return false; } catch (RuntimeException $e) { return true; } };

$GLOBALS['SWUFORGE_CONFIG'] = ['baseUrl' => 'https://forge.test', 'clientId' => 'petranaki', 'clientSecret' => 'sec',
    'redirectUri' => 'http://localhost:3400/TCGEngine/AccountFiles/SWUForgeOAuthCallback.php'];
$conn = GetLocalMySQLConnection();
if (!SWUForgeLinkReady($conn)) { echo "SKIP: run Database/migrations/20_swuforge_link.sql first.\n"; exit(2); }
$cleanup = function () use ($conn) { $conn->query('DELETE FROM swuforge_links WHERE usersId = ' . F_FLOW); };
$calls = [];
$route = function (array $byPath) use (&$calls) {
    $GLOBALS['SWUFORGE_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$calls, $byPath) {
        $calls[] = ['m' => $m, 'url' => $url, 'body' => $body, 'h' => $h];
        foreach ($byPath as $suffix => $resp) if (strpos($url, $suffix) !== false) return $resp;
        return ['status' => 404, 'body' => null, 'raw' => ''];
    };
};

$cleanup();
CheckSession();
try {
    // ── state ────────────────────────────────────────────────────────────────
    $state = SWUForgeOAuthBeginFlow(F_FLOW, '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php', NOW);
    check('state is 64 hex chars', (bool)preg_match('/^[0-9a-f]{64}$/', $state), $state);
    $flow = SWUForgeOAuthConsumeFlow($state, NOW + 5);
    check('flow carries the account that started it and the safe return', (int)$flow['usersId'] === F_FLOW
        && $flow['redirect'] === '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php');
    check('a state is single-use', $throws(fn() => SWUForgeOAuthConsumeFlow($state, NOW + 6)));
    $old = SWUForgeOAuthBeginFlow(F_FLOW, '', NOW);
    check('an expired state is refused', $throws(fn() => SWUForgeOAuthConsumeFlow($old, NOW + 601)));
    check('an unknown state is refused', $throws(fn() => SWUForgeOAuthConsumeFlow(str_repeat('a', 64), NOW)));
    check('an off-site return is replaced by the Profile', SWUForgeOAuthSafeReturn('https://evil.example/x') === '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php');
    check('a SWUStats flow state is not a SWU Forge one', $throws(function () use ($conn) {
        $_SESSION['swustats_oauth_flows']['b' . str_repeat('0', 63)] = ['usersId' => F_FLOW, 'redirect' => '/', 'expiresAt' => NOW + 600];
        SWUForgeOAuthConsumeFlow('b' . str_repeat('0', 63), NOW);
    }));

    // ── authorize URL ────────────────────────────────────────────────────────
    $u = SWUForgeOAuthAuthorizeUrl('abc');
    parse_str((string)parse_url($u, PHP_URL_QUERY), $q);
    check('authorize goes to Forge\'s /auth/petranaki/authorize', strpos($u, 'https://forge.test/auth/petranaki/authorize?') === 0, $u);
    check('authorize asks for a code, with our client, redirect and state (no scopes)', ($q['response_type'] ?? '') === 'code'
        && ($q['client_id'] ?? '') === 'petranaki' && ($q['redirect_uri'] ?? '') === 'http://localhost:3400/TCGEngine/AccountFiles/SWUForgeOAuthCallback.php'
        && ($q['state'] ?? '') === 'abc' && !isset($q['scope']), json_encode($q));
    $GLOBALS['SWUFORGE_CONFIG']['clientSecret'] = '';
    check('no client secret configured → refused before leaving the site', $throws(fn() => SWUForgeOAuthAuthorizeUrl('abc')));
    $GLOBALS['SWUFORGE_CONFIG']['clientSecret'] = 'sec';
    $GLOBALS['SWUFORGE_CONFIG']['baseUrl'] = '';
    check('no Forge address configured → refused before leaving the site', $throws(fn() => SWUForgeOAuthAuthorizeUrl('abc')));
    $GLOBALS['SWUFORGE_CONFIG']['baseUrl'] = 'https://forge.test';

    // ── canonical host (one redirect URI → one host) ─────────────────────────
    check('on the redirect URI host → no hop', SWUForgeOAuthCanonicalStartUrl('localhost:3400', '/x') === null);
    $hop = SWUForgeOAuthCanonicalStartUrl('127.0.0.1:3400', '/TCGEngine/SharedUI/Sites/SWUSim/Profile.php');
    check('on another host → hop to the Start URL on the redirect URI host',
        $hop === 'http://localhost:3400/TCGEngine/AccountFiles/SWUForgeOAuthStart.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FSWUSim%2FProfile.php', (string)$hop);

    // ── code exchange ────────────────────────────────────────────────────────
    $calls = [];
    $route([
        '/auth/petranaki/token' => ['status' => 200, 'body' => ['access_token' => 'acc-x', 'refresh_token' => 'ref-x', 'expires_in' => 3600], 'raw' => ''],
        '/api/petranaki/me'     => ['status' => 200, 'body' => ['linked' => true, 'profile' => ['name' => 'Mario Decks']], 'raw' => ''],
    ]);
    $who = SWUForgeOAuthCompleteLink(F_FLOW, 'the-code', NOW);
    $t = $calls[0] ?? ['body' => []]; $me = $calls[1] ?? ['h' => []];
    check('exchange POSTs the code with our redirect and client credentials', ($t['m'] ?? '') === 'POST'
        && ($t['url'] ?? '') === 'https://forge.test/auth/petranaki/token' && ($t['body']['grant_type'] ?? '') === 'authorization_code'
        && ($t['body']['code'] ?? '') === 'the-code' && ($t['body']['redirect_uri'] ?? '') === 'http://localhost:3400/TCGEngine/AccountFiles/SWUForgeOAuthCallback.php'
        && ($t['body']['client_id'] ?? '') === 'petranaki' && ($t['body']['client_secret'] ?? '') === 'sec', json_encode($t));
    check('who-am-I is asked with the new Bearer token', ($me['url'] ?? '') === 'https://forge.test/api/petranaki/me'
        && in_array('Authorization: Bearer acc-x', (array)$me['h'], true), json_encode($me));
    check('returns the Forge profile name', $who === ['profileName' => 'Mario Decks'], json_encode($who));
    $row = SWUForgeGetLink($conn, F_FLOW);
    check('the link is stored', $row && $row['profileName'] === 'Mario Decks' && $row['accessToken'] === 'acc-x'
        && $row['refreshToken'] === 'ref-x' && (int)$row['accessExpires'] === NOW + 3600, json_encode($row));

    $cleanup();
    $route(['/auth/petranaki/token' => ['status' => 400, 'body' => ['error' => 'invalid_grant'], 'raw' => '']]);
    check('a rejected code throws and stores nothing', $throws(fn() => SWUForgeOAuthCompleteLink(F_FLOW, 'bad', NOW)) && SWUForgeGetLink($conn, F_FLOW) === null);
    $route([
        '/auth/petranaki/token' => ['status' => 200, 'body' => ['access_token' => 'a', 'refresh_token' => 'r'], 'raw' => ''],
        '/api/petranaki/me'     => ['status' => 401, 'body' => ['error' => 'nope'], 'raw' => ''],
    ]);
    check('a who-am-I that fails throws and stores nothing', $throws(fn() => SWUForgeOAuthCompleteLink(F_FLOW, 'c', NOW)) && SWUForgeGetLink($conn, F_FLOW) === null);
    check('an empty code throws without calling Forge', $throws(function () use (&$calls) { $calls = []; SWUForgeOAuthCompleteLink(F_FLOW, '', NOW); }) && count($calls) === 0);

    // ── Profile panel ────────────────────────────────────────────────────────
    $def = ['identity' => ['rootName' => 'SWUSim'], 'profile' => ['swuforgeLink' => true]];
    $_SESSION['userid'] = F_FLOW;
    $html = _DisplaySWUForgeLink($def);
    check('unlinked → Connect button to the Start URL', strpos($html, 'Connect SWU Forge') !== false
        && strpos($html, '/TCGEngine/AccountFiles/SWUForgeOAuthStart.php?redirect=') !== false, $html);
    check('unlinked → SWU Forge\'s own wording on what linking does', strpos($html, 'will be sent to SWU Forge and saved to your SWU Forge account') !== false, $html);
    SWUForgeSaveLink($conn, F_FLOW, 'Mario <Decks>', 'a', 'r', NOW + 3600, NOW);
    $html = _DisplaySWUForgeLink($def);
    check('linked → "Linked as" the Forge profile name, escaped', strpos($html, 'Linked as <b>Mario &lt;Decks&gt;</b>') !== false, $html);
    check('linked → Unlink via DisconnectOAuth type=swuforge', strpos($html, 'Unlink SWU Forge') !== false
        && strpos($html, 'DisconnectOAuth.php?type=swuforge') !== false, $html);
    SWUForgeSetProblem($conn, F_FLOW, 'not-enabled');
    check('a 403 problem tells the player their account is not enabled yet', strpos(_DisplaySWUForgeLink($def), 'not enabled') !== false);
    SWUForgeSetProblem($conn, F_FLOW, 'no-profile');
    check('a 409 problem tells the player to create a profile', strpos(_DisplaySWUForgeLink($def), 'Create a profile') !== false);
    $_GET['swuforge_error'] = '<b>nope</b>';
    $html = _DisplaySWUForgeLink($def);
    check('a flow error is shown, escaped', strpos($html, '&lt;b&gt;nope&lt;/b&gt;') !== false && strpos($html, '<b>nope</b>') === false, $html);
    unset($_GET['swuforge_error']);
    check('the Welcome panel includes the SWU Forge section when the site opts in', strpos(_ProfileWelcome($def, ['username' => 'x']), 'swuforge-link') !== false);
    check('…and not when it does not', strpos(_ProfileWelcome(['identity' => ['rootName' => 'SWUSim'], 'profile' => []], ['username' => 'x']), 'swuforge-link') === false);
} finally {
    $cleanup();
    $conn->close();
}

foreach ($MSGS as $m) echo $m, "\n";
echo $FAIL === 0 ? "PASS: test_swuforge_oauth_flow.php — all $PASS checks passed\n" : "FAIL: test_swuforge_oauth_flow.php — $FAIL of " . ($PASS + $FAIL) . " checks failed\n";
exit($FAIL === 0 ? 0 : 1);
