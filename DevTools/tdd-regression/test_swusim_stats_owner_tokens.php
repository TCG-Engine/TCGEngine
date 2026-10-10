<?php
// Owner tokens on SubmitGameResult (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §3).
// No network: SWUStatsHttp is faked; every POST body is captured. Test-only account ids.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_stats_owner_tokens.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
include './SWUSim/StatsSubmit.php';

const U_P1 = 990000141;
const U_P2 = 990000142;
const U_NOLINK = 990000143;

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

$conn = GetLocalMySQLConnection();
if (!SWUStatsLinkReady($conn)) { echo "SKIP: run Database/migrations/18_swustats_link.sql first.\n"; exit(2); }
$cleanup = function () use ($conn) { $conn->query('DELETE FROM swustats_links WHERE usersId IN (' . U_P1 . ',' . U_P2 . ',' . U_NOLINK . ')'); };

$game = ['gameName' => '777', 'gameNumber' => 1, 'winner' => 1,
    'detail' => ['firstPlayer' => 1, 'turns' => 9, 'leader' => ['1' => 'JTL_001', '2' => 'LOF_001'],
        'base' => ['1' => 'JTL_023', '2' => 'LOF_020'], 'baseHpLeft' => ['1' => 12, '2' => 0],
        'telemetry' => ['cards' => [], 'turns' => []]]];
$mk = fn($u1, $u2) => ['format' => 'premier', 'players' => [
    '1' => ['userId' => $u1, 'deckLink' => 'https://swustats.net/TCGEngine/NextTurn.php?gameName=104&folderPath=SWUDeck'],
    '2' => ['userId' => $u2, 'deckLink' => 'https://swudb.com/deck/abc']]];
$posts = [];
$submitScript = function (array $responses) use (&$posts) {
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$posts, &$responses) {
        if (strpos($url, '/APIs/OAuth/token.php') !== false) return ['status' => 0, 'body' => null, 'raw' => ''];
        $posts[] = json_decode((string)$body, true);
        return array_shift($responses) ?? ['status' => 500, 'body' => null, 'raw' => ''];
    };
};
$okResp = ['status' => 200, 'body' => ['success' => true], 'raw' => ''];
$url = 'http://stats.test/TCGEngine/APIs/SubmitGameResult.php';

$cleanup();
try {
    SWUStatsSaveLink($conn, U_P1, 5, 'Drixx', 'tok-p1', 'r1', time() + 3600);
    SWUStatsSaveLink($conn, U_P2, 6, 'Other', 'tok-p2', 'r2', time() + 3600);

    // ── which seats get a token ──────────────────────────────────────────────
    $submitScript([]);
    $p = []; $seats = SWUAttachOwnerTokens($p, $mk(U_P1, null));
    check('a linked seat sends its token', ($p['p1SWUStatsToken'] ?? '') === 'tok-p1' && $seats === ['1'], json_encode([$p, $seats]));
    check('a guest seat sends none', !array_key_exists('p2SWUStatsToken', $p));
    $p = []; $seats = SWUAttachOwnerTokens($p, $mk(U_P1, U_P2));
    check('both linked → both tokens', ($p['p2SWUStatsToken'] ?? '') === 'tok-p2' && $seats === ['1', '2'], json_encode($p));
    $p = []; $seats = SWUAttachOwnerTokens($p, $mk(U_NOLINK, 0));
    check('signed in but not linked → none', $p === [] && $seats === []);
    SWUStatsSaveTokens($conn, U_P2, 'tok-p2', 'r2', time() - 10);   // expired, and the refresh is unreachable
    $p = []; $seats = SWUAttachOwnerTokens($p, $mk(U_P1, U_P2));
    check('a seat whose refresh fails is left out, not sent a stale token', $seats === ['1'] && !isset($p['p2SWUStatsToken']), json_encode($p));

    // Match end runs inside the player's last game action: a failure while getting a token (DB down, a throw from
    // the refresh) must cost only that seat's owner credit, never escape into the action request.
    SWUStatsSaveTokens($conn, U_P1, 'tok-p1', 'r1', time() - 10);   // forces a refresh, which throws below
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = function () { throw new RuntimeException('boom'); };
    $p = []; $threw = false;
    try { $seats = SWUAttachOwnerTokens($p, $mk(U_P1, null)); } catch (Throwable $e) { $threw = true; $seats = null; }
    check('a throw while getting a token does not escape', !$threw && $seats === [] && $p === [], $threw ? 'threw' : json_encode($seats));
    SWUStatsSaveTokens($conn, U_P1, 'tok-p1', 'r1', time() + 3600);

    // ── submission ───────────────────────────────────────────────────────────
    $posts = []; $submitScript([$okResp]);
    check('accepted → ok', SWUSubmitOneGame($mk(U_P1, null), $game, $url, 'k') === 'ok');
    check('…in one POST carrying the token', count($posts) === 1 && ($posts[0]['p1SWUStatsToken'] ?? '') === 'tok-p1' && ($posts[0]['apiKey'] ?? '') === 'k');

    $posts = []; $submitScript([['status' => 401, 'body' => ['success' => false], 'raw' => ''], $okResp]);
    check('token rejected → retried without → ok_without_owner', SWUSubmitOneGame($mk(U_P1, null), $game, $url, 'k') === 'ok_without_owner');
    check('…the retry carries no token and the same game', count($posts) === 2 && isset($posts[0]['p1SWUStatsToken'])
        && !isset($posts[1]['p1SWUStatsToken']) && ($posts[1]['gameName'] ?? '') === '777', json_encode($posts));

    $posts = []; $submitScript([['status' => 401, 'body' => null, 'raw' => ''], ['status' => 401, 'body' => null, 'raw' => '']]);
    check('401 again without tokens → failed', SWUSubmitOneGame($mk(U_P1, null), $game, $url, 'k') === 'failed' && count($posts) === 2);

    $posts = []; $submitScript([['status' => 401, 'body' => null, 'raw' => '']]);
    check('401 with NO tokens attached → failed, no retry', SWUSubmitOneGame($mk(null, null), $game, $url, 'k') === 'failed' && count($posts) === 1);

    $posts = []; $submitScript([['status' => 500, 'body' => null, 'raw' => '']]);
    check('5xx → failed, no retry', SWUSubmitOneGame($mk(U_P1, null), $game, $url, 'k') === 'failed' && count($posts) === 1);

    $posts = []; $submitScript([['status' => 200, 'body' => ['success' => false], 'raw' => '']]);
    check('200 with success:false → failed', SWUSubmitOneGame($mk(U_P1, null), $game, $url, 'k') === 'failed');

    // ── the match status ─────────────────────────────────────────────────────
    check('status: nothing attempted', SWUStatsSubmitStatus(0, 0, 0) === 'skipped_early');
    check('status: a failure wins', SWUStatsSubmitStatus(2, 1, 1) === 'failed');
    check('status: owner stats dropped', SWUStatsSubmitStatus(2, 0, 1) === 'submitted_without_owner');
    check('status: clean', SWUStatsSubmitStatus(2, 0, 0) === 'success');
} finally {
    $cleanup();
    unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
}
echo ($FAIL === 0 ? "PASS ($PASS checks)" : "FAIL ($FAIL of " . ($PASS + $FAIL) . ")\n" . implode("\n", $MSGS)) . "\n";
exit($FAIL === 0 ? 0 : 1);
