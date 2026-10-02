<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_core_game_write_lock.php
//
// Per-game WRITE LOCK in ProcessInput.php (Core/GameWriteLock.php) and the bot step's STALE TOKEN.
//
// Why: ProcessInput is the only live writer of a game, and it is a plain parse -> execute -> write with
// no lock. Two requests on one game that overlap both read the same state and the LAST write wins, so
// the other action is silently lost. Live bot seats make that common: every human browser POSTs the
// bot's step (mode 10017), and a step that spends time choosing can overwrite a human's move committed
// in between. See SWUSim/docs/todo-twinsuns-fill-bot.md, "Deep research D".
//
// What this pins, through the REAL endpoint:
//   1. While the lock is held, a ProcessInput action WAITS — the gamestate is untouched — and it applies
//      once the lock is released.
//   2. A lock held past the wait budget makes the request give up with a "busy" reply and no write.
//   3. Mode 10017 with a stale lastUpdate is refused as stale (no move); with the current one it is not.
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';
require_once __DIR__ . '/../../Core/GameWriteLock.php';

$ROOT = dirname(__DIR__, 2);

function wl_state_file(string $gn): string
{
    global $ROOT;
    return "$ROOT/SWUSim/Games/$gn/Gamestate.txt";
}
// The persisted file's 2nd line is $updateNumber (WriteGamestate writes currentPlayer, then it).
function wl_update_number(string $gn): int
{
    clearstatcache();
    $lines = @file(wl_state_file($gn));
    return is_array($lines) ? intval($lines[1] ?? -1) : -1;
}
function wl_action_url(string $gn, array $extra = []): string
{
    return SWUCHAT_BASE . 'ProcessInput.php?' . http_build_query($extra + [
        'gameName' => $gn, 'playerID' => '1', 'authKey' => 'testschema', 'folderPath' => 'SWUSim',
        'mode' => 10001, 'cardID' => 'myHealth-0!CustomInput!',
    ]);
}
// Starts a request WITHOUT waiting for it, so the test can hold the lock while it is in flight.
function wl_start(string $url): array
{
    $mh = curl_multi_init();
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60]);
    curl_multi_add_handle($mh, $ch);
    $t = microtime(true);
    do { curl_multi_exec($mh, $running); usleep(10000); } while (microtime(true) - $t < 0.3);
    return [$mh, $ch];
}
function wl_finish(array $h): string
{
    [$mh, $ch] = $h;
    do { $s = curl_multi_exec($mh, $running); if ($running) curl_multi_select($mh, 0.1); } while ($running && $s === CURLM_OK);
    $body = (string)curl_multi_getcontent($ch);
    curl_multi_remove_handle($mh, $ch); curl_multi_close($mh);
    return $body;
}

$checks = [];
$gn = swuchat_make_game(SWUCHAT_SCHEMA_PREMIER);
$checks['fixture created'] = $gn !== '' && is_file(wl_state_file($gn));

// ── control: an unlocked action writes (proves the probe action moves $updateNumber at all) ──
$u0 = wl_update_number($gn);
swuchat_http(substr(wl_action_url($gn), strlen(SWUCHAT_BASE)));
$u1 = wl_update_number($gn);
$checks['control: the action bumps updateNumber'] = $u1 === $u0 + 1;

// ── 1. held lock -> the request waits, then applies ──
$lock = SimGameAcquireWriteLock('SWUSim', $gn, 0);
$checks['test took the lock'] = is_resource($lock);
$inflight = wl_start(wl_action_url($gn));
usleep(1200000);
$checks['while locked: no write'] = wl_update_number($gn) === $u1;
SimGameReleaseWriteLock($lock);
wl_finish($inflight);
$checks['after release: the action applied'] = wl_update_number($gn) === $u1 + 1;

// ── 2. lock held past the wait budget -> "busy", no write ──
$u2 = wl_update_number($gn);
$lock = SimGameAcquireWriteLock('SWUSim', $gn, 0);
$t = microtime(true);
$busy = trim(swuchat_http(substr(wl_action_url($gn, ['responseFormat' => 'json', 'lockWaitMs' => 300]), strlen(SWUCHAT_BASE))));
$elapsed = microtime(true) - $t;
SimGameReleaseWriteLock($lock);
$bj = json_decode($busy, true);
$checks['busy: refused']             = is_array($bj) && ($bj['success'] ?? null) === false;
$checks['busy: says it is busy']     = is_array($bj) && stripos(strval($bj['message'] ?? ''), 'busy') !== false;
$checks['busy: retryable for a bot'] = is_array($bj) && ($bj['botStepRetryable'] ?? null) === true;
$checks['busy: no write']            = wl_update_number($gn) === $u2;
$checks['busy: gave up promptly']    = $elapsed < 5.0;

// ── 3. bot step stale token ──
$u3 = wl_update_number($gn);
$stale = json_decode(swuchat_http('ProcessInput.php?' . http_build_query([
    'gameName' => $gn, 'playerID' => '1', 'authKey' => 'testschema', 'folderPath' => 'SWUSim',
    'mode' => 10017, 'responseFormat' => 'json', 'lastUpdate' => $u3 - 1,
])), true);
$checks['stale step: flagged stale']  = is_array($stale) && ($stale['botStepStale'] ?? null) === true;
$checks['stale step: not applied']    = is_array($stale) && ($stale['botStepApplied'] ?? null) === false;
$checks['stale step: no write']       = wl_update_number($gn) === $u3;
$fresh = json_decode(swuchat_http('ProcessInput.php?' . http_build_query([
    'gameName' => $gn, 'playerID' => '1', 'authKey' => 'testschema', 'folderPath' => 'SWUSim',
    'mode' => 10017, 'responseFormat' => 'json', 'lastUpdate' => $u3,
])), true);
$checks['current step: not stale']    = is_array($fresh) && empty($fresh['botStepStale']);
$noToken = json_decode(swuchat_http('ProcessInput.php?' . http_build_query([
    'gameName' => $gn, 'playerID' => '1', 'authKey' => 'testschema', 'folderPath' => 'SWUSim',
    'mode' => 10017, 'responseFormat' => 'json',
])), true);
$checks['no token (old client): not stale'] = is_array($noToken) && empty($noToken['botStepStale']);

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$ok) $fail++; }
echo $fail === 0 ? "\nALL PASS (" . count($checks) . ")\n" : "\n$fail FAILED of " . count($checks) . "\n";
exit($fail === 0 ? 0 : 1);
