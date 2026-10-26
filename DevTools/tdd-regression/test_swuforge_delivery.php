<?php
// Petranaki → SWU Forge game delivery: queueing a finished game for each linked seat, posting it, and what each answer
// does (Forge Q&A 25 Oct 2026, §6–9). Test-only account ids and game names; every row written is removed at start and
// in finally. No network: SWUForgeHttp is faked.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swuforge_delivery.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './SWUSim/SWUForgeDelivery.php';

const D_A = 990000231;   // linked, seat 1
const D_B = 990000232;   // not linked, seat 2
const D_C = 990000233;   // linked, used for the answer cases
const NOW = 1800000000;
const G = 'swuforgetest_game';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}
$GLOBALS['SWUFORGE_CONFIG'] = ['baseUrl' => 'https://forge.test', 'clientId' => 'petranaki', 'clientSecret' => 'sec', 'redirectUri' => 'http://localhost:3400/cb'];
$conn = GetLocalMySQLConnection();
if (!SWUForgeLinkReady($conn) || !SWUForgeDeliveryReady($conn)) { echo "SKIP: run Database/migrations/20_swuforge_link.sql first.\n"; exit(2); }
$ids = D_A . ',' . D_B . ',' . D_C;
$cleanup = function () use ($conn, $ids) {
    $conn->query("DELETE FROM swuforge_links WHERE usersId IN ($ids)");
    $conn->query("DELETE FROM swuforge_deliveries WHERE usersId IN ($ids)");
};
$rows = function (int $u) use ($conn) {
    $r = $conn->query('SELECT * FROM swuforge_deliveries WHERE usersId = ' . $u . ' ORDER BY id');
    return $r->fetch_all(MYSQLI_ASSOC);
};
$calls = [];
$answers = [];   // queue of responses for the pgn endpoint
$GLOBALS['SWUFORGE_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$calls, &$answers) {
    $calls[] = ['m' => $m, 'url' => $url, 'body' => $body, 'h' => $h];
    if (str_ends_with($url, '/auth/petranaki/token')) return ['status' => 200, 'body' => ['access_token' => 'acc-new', 'refresh_token' => 'ref-new', 'expires_in' => 3600], 'raw' => ''];
    return array_shift($answers) ?? ['status' => 500, 'body' => null, 'raw' => ''];
};
$pgn = "[Game \"SWU-PGN/1.0\"]\n%%% EVENTS\n";
$match = ['bestOf' => 3, 'players' => [
    '1' => ['userId' => D_A, 'deckLink' => 'https://swuforge.com/decks/abcDEF123', 'originalDeck' => ['name' => 'Vader Aggro']],
    '2' => ['userId' => D_B, 'deckLink' => 'https://swudb.com/deck/xyz', 'originalDeck' => ['name' => 'Krennic Control']],
]];
$meta = ['accounts' => ['1' => D_A, '2' => D_B], 'matchId' => 'm-1234'];

$cleanup();
try {
    // ── Queueing: one row per LINKED seat, with what Forge wants beside the file ──────────────────────────────────
    SWUForgeSaveLink($conn, D_A, 'A', 'acc-a', 'ref-a', NOW + 3600, NOW);
    check('a finished game is queued for each linked seat', SWUForgeEnqueueGame(G, $meta, $pgn, NOW, $match) === 1);
    $a = $rows(D_A);
    check('the row: seat, the raw match id as lobbyId, both deck names, the Forge deck id, due now', count($a) === 1
        && (int)$a[0]['seat'] === 1 && $a[0]['lobbyId'] === 'm-1234' && $a[0]['deckName'] === 'Vader Aggro'
        && $a[0]['opponentDeckName'] === 'Krennic Control' && $a[0]['deckId'] === 'abcDEF123' && $a[0]['pgn'] === $pgn
        && $a[0]['status'] === 'pending' && (int)$a[0]['nextAttemptAt'] === NOW, json_encode($a));
    check('an unlinked seat gets nothing', $rows(D_B) === []);
    check('a game finished again (a bookmark reopened it) replaces its row, not a second one',
        SWUForgeEnqueueGame(G, $meta, $pgn . "x\n", NOW + 5, $match) === 1 && count($rows(D_A)) === 1 && $rows(D_A)[0]['pgn'] === $pgn . "x\n");
    check('a guest seat (no account) and a game with no accounts queue nothing', SWUForgeEnqueueGame(G . '2', ['accounts' => ['1' => null, '2' => null]], $pgn, NOW, $match) === 0);
    check('a non-Forge deck link has no deck id; a deck with no name sends none', SWUForgeDeckIdFromLink('https://swudb.com/deck/xyz') === ''
        && SWUForgeDeckIdFromLink('swuforge.com/decks/Q-9_z') === 'Q-9_z');
    check('a file over 4 MB is not queued (Forge would answer 413)', SWUForgeEnqueueGame(G . '3', $meta, str_repeat('x', 4 * 1024 * 1024 + 1), NOW, $match) === 0);

    // ── Delivery: the POST ────────────────────────────────────────────────────────────────────────────────────────
    $calls = []; $answers = [['status' => 200, 'body' => ['ok' => true, 'gameId' => 'g1'], 'raw' => '']];
    $n = SWUForgeDeliverDue(NOW + 10);
    $post = $calls[0] ?? [];
    $body = json_decode((string)($post['body'] ?? ''), true);
    check('the worker posts the due row to /api/petranaki/pgn', $n === 1 && ($post['m'] ?? '') === 'POST' && ($post['url'] ?? '') === 'https://forge.test/api/petranaki/pgn', json_encode($calls));
    check('as JSON with the bearer token', in_array('Authorization: Bearer acc-a', $post['h'] ?? [], true) && in_array('Content-Type: application/json', $post['h'] ?? [], true));
    check('body: pgn, seat, lobbyId, deckName, opponentDeckName, deckId', $body === ['pgn' => $pgn . "x\n", 'seat' => 1, 'lobbyId' => 'm-1234',
        'deckName' => 'Vader Aggro', 'opponentDeckName' => 'Krennic Control', 'deckId' => 'abcDEF123'], json_encode($body));
    $a = $rows(D_A)[0];
    check('200 ok → done, and the stored file is dropped', $a['status'] === 'done' && $a['pgn'] === '' && (int)$a['lastHttp'] === 200, json_encode($a));
    $calls = [];
    check('a done row is not sent again', SWUForgeDeliverDue(NOW + 99999) === 0 && $calls === []);

    // ── Every answer ──────────────────────────────────────────────────────────────────────────────────────────────
    SWUForgeSaveLink($conn, D_C, 'C', 'acc-c', 'ref-c', NOW + 3600, NOW);
    $metaC = ['accounts' => ['1' => D_C, '2' => D_B]];
    $case = function (string $name, array $answerList, int $at = NOW) use ($conn, $metaC, $pgn, &$answers, &$calls, $rows) {
        $conn->query('DELETE FROM swuforge_deliveries WHERE usersId = ' . D_C);
        SWUForgeEnqueueGame(G . "_$name", $metaC, $pgn, $at, null);
        $answers = $answerList; $calls = [];
        SWUForgeDeliverDue($at);
        return $rows(D_C)[0] ?? null;
    };
    $r = $case('skip', [['status' => 200, 'body' => ['skipped' => true, 'reason' => 'under 2 rounds'], 'raw' => '']]);
    check('200 skipped → skipped, never retried', $r['status'] === 'skipped' && $r['lastError'] === 'under 2 rounds', json_encode($r));
    foreach ([400, 413, 415] as $code) {
        $r = $case("c$code", [['status' => $code, 'body' => ['error' => "e$code"], 'raw' => '']]);
        check("$code → failed, never retried, the file kept for a look", $r['status'] === 'failed' && (int)$r['lastHttp'] === $code && $r['pgn'] !== '', json_encode($r));
    }
    foreach ([502, 503, 0] as $code) {
        $r = $case("c$code", [['status' => $code, 'body' => null, 'raw' => '']]);
        check("$code → retried in 30 s", $r['status'] === 'pending' && (int)$r['attempts'] === 1 && (int)$r['nextAttemptAt'] === NOW + 30, json_encode($r));
    }
    // Backoff doubles from 30 s; after the last attempt the row fails.
    $conn->query('DELETE FROM swuforge_deliveries WHERE usersId = ' . D_C);
    SWUForgeEnqueueGame(G . '_back', $metaC, $pgn, NOW, null);
    $at = NOW; $waits = [];
    for ($i = 0; $i < SWUFORGE_MAX_ATTEMPTS; $i++) {
        $answers = [['status' => 502, 'body' => null, 'raw' => '']];
        SWUForgeDeliverDue($at);
        $r = $rows(D_C)[0];
        if ($r['status'] !== 'pending') break;
        $waits[] = (int)$r['nextAttemptAt'] - $at; $at = (int)$r['nextAttemptAt'];
    }
    check('backoff doubles from 30 s (capped at 6 h)', array_slice($waits, 0, 4) === [30, 60, 120, 240] && max($waits) <= 6 * 3600, json_encode($waits));
    check('and after the last attempt the row fails', $r['status'] === 'failed' && (int)$r['attempts'] === SWUFORGE_MAX_ATTEMPTS, json_encode($r));

    // 401: refresh, send again; a second 401 means the link is gone.
    $r = $case('auth', [['status' => 401, 'body' => ['error' => 'expired'], 'raw' => ''], ['status' => 200, 'body' => ['ok' => true], 'raw' => '']]);
    check('401 → refresh once and send again', $r['status'] === 'done' && count($calls) === 3 && str_ends_with($calls[1]['url'], '/auth/petranaki/token')
        && in_array('Authorization: Bearer acc-new', $calls[2]['h'], true), json_encode($calls));
    $r = $case('auth2', [['status' => 401, 'body' => [], 'raw' => ''], ['status' => 401, 'body' => [], 'raw' => '']]);
    check('401 again after a refresh → unlinked, and the link cleared', $r['status'] === 'unlinked' && SWUForgeGetLink($conn, D_C) === null, json_encode($r));
    SWUForgeSaveLink($conn, D_C, 'C', 'acc-c', 'ref-c', NOW + 3600, NOW);

    // 403 / 409: the player has to act — the profile says so, and the game waits (hourly) instead of being dropped.
    $r = $case('c403', [['status' => 403, 'body' => ['error' => 'not enabled'], 'raw' => '']]);
    check('403 → held: retried in an hour, the link marked not-enabled', $r['status'] === 'pending' && (int)$r['nextAttemptAt'] === NOW + 3600
        && SWUForgeGetLink($conn, D_C)['problem'] === 'not-enabled', json_encode($r));
    $r = $case('c409', [['status' => 409, 'body' => ['error' => 'no profile'], 'raw' => '']]);
    check('409 → held, the link marked no-profile', $r['status'] === 'pending' && SWUForgeGetLink($conn, D_C)['problem'] === 'no-profile', json_encode($r));
    $r = $case('fine', [['status' => 200, 'body' => ['ok' => true], 'raw' => '']]);
    check('a delivery that goes through clears the problem', $r['status'] === 'done' && SWUForgeGetLink($conn, D_C)['problem'] === '');

    // Unlinked since it was queued: nothing is sent.
    $conn->query('DELETE FROM swuforge_deliveries WHERE usersId = ' . D_C);
    SWUForgeEnqueueGame(G . '_gone', $metaC, $pgn, NOW, null);
    SWUForgeClearLink($conn, D_C);
    $calls = [];
    SWUForgeDeliverDue(NOW);
    check('a row whose link is gone is marked unlinked without a POST', $rows(D_C)[0]['status'] === 'unlinked' && $calls === [], json_encode($calls));

    // No client secret configured (a box Forge has not been set up on): nothing is queued, nothing is sent.
    $conn->query('DELETE FROM swuforge_deliveries WHERE usersId = ' . D_C);
    SWUForgeSaveLink($conn, D_C, 'C', 'acc-c', 'ref-c', NOW + 3600, NOW);
    SWUForgeEnqueueGame(G . '_nosecret_sent', $metaC, $pgn, NOW, null);   // queued while configured…
    foreach (['', null] as $empty) {
        $GLOBALS['SWUFORGE_CONFIG']['clientSecret'] = $empty;
        check('no secret (' . var_export($empty, true) . ') → a finished game is not queued', SWUForgeEnqueueGame(G . '_nosecret', $metaC, $pgn, NOW, null) === 0
            && $conn->query("SELECT COUNT(*) FROM swuforge_deliveries WHERE gameName = '" . G . "_nosecret'")->fetch_row()[0] == 0);
        $calls = [];
        check('no secret (' . var_export($empty, true) . ') → the worker sends nothing, the queued row waits', SWUForgeDeliverDue(NOW) === 0 && $calls === []
            && $rows(D_C)[0]['status'] === 'pending');
    }
    $GLOBALS['SWUFORGE_CONFIG']['clientSecret'] = 'sec';

    // Only due rows go: one queued for later waits.
    SWUForgeSaveLink($conn, D_C, 'C', 'acc-c', 'ref-c', NOW + 3600, NOW);
    $conn->query('DELETE FROM swuforge_deliveries WHERE usersId = ' . D_C);
    SWUForgeEnqueueGame(G . '_later', $metaC, $pgn, NOW + 500, null);
    $calls = [];
    check('a row not yet due is left alone', SWUForgeDeliverDue(NOW) === 0 && $calls === [] && $rows(D_C)[0]['status'] === 'pending');
} finally {
    $cleanup();
    $conn->close();
}

foreach ($MSGS as $m) echo $m, "\n";
echo $FAIL === 0 ? "PASS: test_swuforge_delivery.php — all $PASS checks passed\n" : "FAIL: test_swuforge_delivery.php — $FAIL of " . ($PASS + $FAIL) . " checks failed\n";
exit($FAIL === 0 ? 0 : 1);
