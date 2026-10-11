<?php
// Petranaki ↔ SWUStats link: the hearted-deck list (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2).
// No network (SWUStatsHttp faked). Runs on the CLI (APCu off) or over HTTP (APCu ON — the 60s deck-list cache is live
// there, so every case clears its key first, and one case checks the cache itself when APCu is available).
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swustats_decks.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';   // GLOBAL scope — the mapper reads $cardUUIDData
require_once './SWUSim/Custom/SetupPanels.php';
require_once './SWUSim/SWUStatsDeckList.php';

const U_DECKS = 990000131;
const NOW_FRESH = 3600;

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

$conn = GetLocalMySQLConnection();
if (!SWUStatsLinkReady($conn)) { echo "SKIP: run Database/migrations/18_swustats_link.sql first.\n"; exit(2); }
$apcu = function_exists('apcu_enabled') && apcu_enabled();
$uncache = function () use ($apcu) { if ($apcu) apcu_delete('swustats_decks_' . U_DECKS); };
$cleanup = function () use ($conn, $uncache) { $conn->query('DELETE FROM swustats_links WHERE usersId = ' . U_DECKS); $uncache(); };
// The production call (cache allowed), with this case's cache key cleared first.
$hearted = function () use ($uncache) { $uncache(); return SWUStatsHeartedDecks(U_DECKS); };
$calls = [];
// $script: list of responses for GetUserDecks calls in order; token.php answers from $tokenResp.
$fake = function (array $script, array $tokenResp = ['status' => 500, 'body' => null, 'raw' => '']) use (&$calls) {
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = function ($m, $url, $body, $h) use (&$calls, &$script, $tokenResp) {
        $calls[] = ['m' => $m, 'url' => $url, 'h' => $h];
        if (strpos($url, '/APIs/OAuth/token.php') !== false) return $tokenResp;
        return array_shift($script) ?? ['status' => 500, 'body' => null, 'raw' => ''];
    };
};
$page = fn(array $decks, bool $more) => ['status' => 200, 'body' => ['decks' => $decks, 'pagination' => ['has_more' => $more]], 'raw' => ''];
$uuidSOR005 = (string)($GLOBALS['cardUUIDData']['SOR_005'] ?? '');

$cleanup();
try {
    // ── the mapper ───────────────────────────────────────────────────────────
    check('a SET_NNN id passes through', SWUStatsCardToSimId('ASH_009') === 'ASH_009');
    check('an FFG UID maps to its SET_NNN', $uuidSOR005 !== '' && SWUStatsCardToSimId($uuidSOR005) === 'SOR_005', $uuidSOR005);
    check('an unknown id maps to empty', SWUStatsCardToSimId('NOPE_999x') === '');

    // ── unlinked ─────────────────────────────────────────────────────────────
    $calls = []; $fake([]);
    check('unlinked → status unlinked, no decks', $hearted() === ['status' => 'unlinked', 'decks' => []]);
    check('unlinked → no HTTP', count($calls) === 0);

    // ── two pages, owner filter, mapping, link form ──────────────────────────
    SWUStatsSaveLink($conn, U_DECKS, 5, 'Drixx', 'acc-d', 'ref-d', time() + NOW_FRESH);
    $calls = [];
    $fake([
        $page([
            ['id' => 104, 'name' => 'Owned One', 'is_owner' => true,  'keyIndicator1' => 'ASH_009', 'keyIndicator2' => 'ASH_025'],
            ['id' => 200, 'name' => 'Team Deck', 'is_owner' => false, 'keyIndicator1' => 'SOR_005', 'keyIndicator2' => 'SOR_027'],
        ], true),
        $page([
            ['id' => 1001, 'name' => '', 'is_owner' => true, 'keyIndicator1' => $uuidSOR005, 'keyIndicator2' => ''],
        ], false),
    ]);
    $res = $hearted();
    $byKey = array_column($res['decks'], null, 'key');
    check('status ok', $res['status'] === 'ok', json_encode($res));
    check('only OWNED hearted decks', array_keys($byKey) === ['ss104', 'ss1001'], json_encode(array_keys($byKey)));
    check('leader/base carried', ($byKey['ss104']['leaders'] ?? null) === ['ASH_009'] && ($byKey['ss104']['base'] ?? '') === 'ASH_025');
    check('a UID leader is mapped', ($byKey['ss1001']['leaders'] ?? null) === ['SOR_005']);
    check('an unnamed deck takes its leader label', ($byKey['ss1001']['name'] ?? '') === SWUSetupCardLabel('SOR_005'));
    check('input is the SWUStats deck link', ($byKey['ss104']['input'] ?? '') === SWUStatsDeckLinkBase() . '/TCGEngine/NextTurn.php?gameName=104&folderPath=SWUDeck');
    check('rows have the saved-deck shape', isset($byKey['ss104']['count']) && $byKey['ss104']['count'] === 0);
    // GUEST_BUILD_PICKER shows d.subtitle, else raw ids — a saved deck reads "Leader, Subtitle · Base"; so must this.
    check('subtitle names the cards like a saved deck', ($byKey['ss104']['subtitle'] ?? '') === SWUSetupCardLabel('ASH_009') . ' · ' . SWUSetupCardLabel('ASH_025'),
        $byKey['ss104']['subtitle'] ?? '(none)');
    check('subtitle without a base is the leader alone', ($byKey['ss1001']['subtitle'] ?? '') === SWUSetupCardLabel('SOR_005'));
    $gets = array_values(array_filter($calls, fn($c) => strpos($c['url'], 'GetUserDecks.php') !== false));
    parse_str((string)parse_url($gets[0]['url'] ?? '', PHP_URL_QUERY), $q0);
    parse_str((string)parse_url($gets[1]['url'] ?? '', PHP_URL_QUERY), $q1);
    check('two pages fetched, offsets 0 then 100', count($gets) === 2 && ($q0['offset'] ?? '') === '0' && ($q1['offset'] ?? '') === '100', json_encode([$q0, $q1]));
    check('asks for favorites only', ($q0['favorites'] ?? '') === 'true' && ($q0['limit'] ?? '') === '100');
    check('sends the Bearer token', in_array('Authorization: Bearer acc-d', (array)($gets[0]['h'] ?? []), true));
    if ($apcu) {   // over HTTP: a second call within the TTL is served from the cache; ?refresh=1 bypasses it
        $calls = [];
        $again = SWUStatsHeartedDecks(U_DECKS);
        check('cached: a repeat call makes no request', $again === $res && count($calls) === 0, count($calls) . ' calls');
        $fake([$page([], false)]);
        $forced = SWUStatsHeartedDecks(U_DECKS, true);
        check('refresh bypasses the cache', $forced['decks'] === [] && count($calls) === 1, json_encode($forced));
    }

    // ── a 401 forces ONE refresh and retries the same page ──────────────────
    $calls = [];
    $fake([['status' => 401, 'body' => ['error' => 'invalid_token'], 'raw' => ''], $page([['id' => 7, 'name' => 'After', 'is_owner' => true, 'keyIndicator1' => 'ASH_009', 'keyIndicator2' => 'ASH_025']], false)],
          ['status' => 200, 'body' => ['access_token' => 'acc-new', 'refresh_token' => 'ref-new', 'expires_in' => 3600], 'raw' => '']);
    $res = $hearted();
    $gets = array_values(array_filter($calls, fn($c) => strpos($c['url'], 'GetUserDecks.php') !== false));
    check('401 → refreshed and retried → ok', $res['status'] === 'ok' && ($res['decks'][0]['key'] ?? '') === 'ss7', json_encode($res));
    check('the retry uses the NEW token', in_array('Authorization: Bearer acc-new', (array)($gets[1]['h'] ?? []), true));
    parse_str((string)parse_url($gets[1]['url'] ?? '', PHP_URL_QUERY), $qr);
    check('the retry re-fetches the SAME page', count($gets) === 2 && ($qr['offset'] ?? '') === '0', json_encode($qr));
    $fake([['status' => 401, 'body' => null, 'raw' => ''], ['status' => 401, 'body' => null, 'raw' => '']],
          ['status' => 200, 'body' => ['access_token' => 'acc-n2', 'refresh_token' => 'ref-n2', 'expires_in' => 3600], 'raw' => '']);
    check('a second 401 → relink', $hearted()['status'] === 'relink');

    // ── SWUStats down ────────────────────────────────────────────────────────
    $fake([['status' => 500, 'body' => null, 'raw' => '']]);
    check('5xx → unavailable', $hearted() === ['status' => 'unavailable', 'decks' => []]);
    $fake([['status' => 200, 'body' => ['nope' => 1], 'raw' => '']]);
    check('a malformed body → unavailable', $hearted()['status'] === 'unavailable');
} finally {
    $cleanup();
    unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
}
echo ($FAIL === 0 ? "PASS ($PASS checks)" : "FAIL ($FAIL of " . ($PASS + $FAIL) . ")\n" . implode("\n", $MSGS)) . "\n";
exit($FAIL === 0 ? 0 : 1);
