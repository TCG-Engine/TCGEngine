<?php
// Limited Bo3: between games the Limited deckbuilder IS the sideboard step (leader and base may change), with a 5-minute
// cap that keeps the last-played deck on timeout; the plain sideboard screen and endpoint send a Limited match away.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.4.
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/DevTools/tests/limited_match_sideboard_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/Limited/LimitedRoom.php';
require_once './SWUSim/Limited/LimitedDeck.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
function hit($url, $params, $jar = null, &$status = null) {
    $ch = curl_init($url);
    $o = [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120];
    if ($jar !== null) { $o[CURLOPT_COOKIEJAR] = $jar; $o[CURLOPT_COOKIEFILE] = $jar; }
    curl_setopt_array($ch, $o);
    $r = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 600)];
}
function headers(string $url): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 1, CURLOPT_NOBODY => 0, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_TIMEOUT => 120]);
    $r = curl_exec($ch); curl_close($ch);
    return substr((string)$r, 0, 2000);
}
$ROOMS = []; $JARS = []; $MADE = [];
register_shutdown_function(function () {
    global $ROOMS, $JARS;
    foreach ($ROOMS as $r) {
        LobbyMutate($r['lobbyID'], fn($l) => 'delete');
        foreach ([SWURoomPath($r['lobbyID']), SWURoomInvitePath($r['invite'])] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
    foreach ($JARS as $j) @unlink($j);
});
function jar(string $user): string {
    global $B, $JARS;
    $j = tempnam(sys_get_temp_dir(), 'lts'); $JARS[] = $j;
    hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $j);
    return $j;
}
$JA = jar('claudebot1'); $JB = jar('claudebot2');
function act(array $r, string $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who]);
}
function build(array $r, string $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedBuilderAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who]);
}
// The between-games builder, as the page calls it: matchId + seat + the seat's key.
function between(string $matchId, int $seat, string $key, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedBuilderAction.php', ['matchId' => $matchId, 'playerID' => $seat, 'authKey' => $key, 'action' => $action] + $params);
}
function pickLegalDeck(array $pool, array $basesInPlay, int $leaderIndex = 0, int $baseIndex = 0): array {
    $leaders = []; $main = [];
    foreach ($pool as $c) {
        $t = CardType($c);
        if ($t === 'Leader') { $leaders[] = $c; continue; }
        if ($t === 'Base') continue;
        if (count($main) < 30) $main[] = $c;
    }
    return ['leader' => $leaders[$leaderIndex] ?? '', 'base' => strval($basesInPlay[$baseIndex] ?? ''), 'main' => $main];
}
// Production's per-game path: the engine's game-over variables, then MatchAfterActionHook (records, sideboards, hooks).
function finishGame(string $game, int $winner): void {
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = fn($method, $url, $body) => ['status' => 200, 'body' => ['success' => true], 'raw' => '{}'];
    $saved = $GLOBALS['gDecisionQueueVariables'] ?? null;
    $GLOBALS['gDecisionQueueVariables'] = json_encode(['GAMEOVER_WINNER' => strval($winner), 'GAMEOVER_WINNERS' => strval($winner)]);
    MatchAfterActionHook('SWUSim', $game);
    $GLOBALS['gDecisionQueueVariables'] = $saved;
    unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
}
$latestGame = fn(array $m) => strval($m['games'][count($m['games']) - 1]['gameName'] ?? '');

// ── A Bo3 Limited event, both seats Ready, game 1 on ─────────────────────────────────────────────────────────────────
$j = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'limited', 'limitedMode' => 'sealed', 'deckLink' => '', 'preconstructedDeck' => ''], $JA);
$r = ['lobbyID' => strval($j['lobbyID'] ?? ''), 'invite' => strval($j['inviteCode'] ?? ''), 'keys' => ['a' => strval($j['authKey'] ?? '')], 'jars' => ['a' => $JA, 'b' => $JB]];
$ROOMS[] = $r;
$jb = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => '', 'preconstructedDeck' => ''], $JB);
$r['keys']['b'] = strval($jb['authKey'] ?? '');
act($r, 'a', 'settings', ['settings' => json_encode(['packMode' => 'single', 'singleSet' => 'SOR', 'packCount' => 6, 'bestOf' => 3])]);
act($r, 'a', 'start');
$decks = [];
foreach (['a', 'b'] as $who) {
    $s = build($r, $who, 'state');
    $decks[$who] = pickLegalDeck($s['pool'] ?? [], $s['basesInPlay'] ?? []);
    build($r, $who, 'submit', ['deck' => json_encode($decks[$who])]);
    build($r, $who, 'ready');
}
$lt = apcu_fetch($r['lobbyID'])->limited;
$mid = strval($lt['match']['matchId'] ?? '');
$m = SWUReadMatch($mid);
$check(($lt['phase'] ?? '') === 'playing' && intval($m['bestOf'] ?? 0) === 3, 'setup: a Bo3 Limited match is on (' . $mid . ')');
$k1 = strval($m['players']['1']['authKey'] ?? ''); $k2 = strval($m['players']['2']['authKey'] ?? '');
$pool1 = $m['limited']['pools']['1'] ?? [];
$g1 = $latestGame($m);
finishGame($g1, 1);   // seat 2 loses game 1

echo "PrivateLimitedHasADeadline:\n";
$m = SWUReadMatch($mid);
$left = intval($m['sideboardDeadline'] ?? 0) - time();
$check(($m['state'] ?? '') === 'sideboarding' && $left >= 295 && $left <= 300, 'a private Limited match sideboards against a 5-minute clock (' . $left . ' s)');

echo "PrivateNonLimitedStillHasNone:\n";
$seatRec = fn($k) => ['originalDeck' => ['leader' => 'SOR_005', 'base' => 'SOR_023', 'mainDeck' => [], 'sideboard' => []],
                      'authKey' => $k, 'userId' => null, 'deckIdentity' => '', 'deckLink' => 'dl-' . $k, 'cosmetics' => []];
$pid = MatchCreate('SWUSim', 'premier', 'bo3', ['1' => $seatRec('ka'), '2' => $seatRec('kb')], true);
MatchBeginSideboarding('SWUSim', $pid, 2);
$check(!isset(SWUReadMatch($pid)['sideboardDeadline']), 'a private Premier Bo3 still has no sideboard clock');

echo "SideboardRedirectsToBuilder:\n";
$h = headers($B . 'SWUSim/Sideboard.php?matchId=' . urlencode($mid) . '&playerID=1&authKey=' . urlencode($k1));
$check((bool)preg_match('#^HTTP/\S+ 302#', $h) && (bool)preg_match('#Location: \./LimitedBuilder\.php\?matchId=' . preg_quote($mid, '#') . '&playerID=1&authKey=#i', $h),
    'the sideboard screen sends a Limited seat to the builder: ' . strtok($h, "\n"));

echo "SideboardEndpointsRefuseLimited:\n";
$ss = hit($B . 'SWUSim/SubmitSideboard.php', ['matchId' => $mid, 'playerID' => 1, 'authKey' => $k1, 'action' => 'save', 'deck' => "Leader\nSOR_005\nBase\nSOR_023\nDeck\n30 SOR_093"]);
$check(empty($ss['success']) && strval($ss['message'] ?? '') === 'Use the Limited deckbuilder.', 'save is refused: ' . json_encode($ss['message'] ?? $ss));
$s1 = hit($B . 'SWUSim/SubmitSideboard.php', ['matchId' => $mid, 'playerID' => 1, 'authKey' => $k1, 'deck' => "Leader\nSOR_005\nBase\nSOR_023\nDeck\n30 SOR_093"]);
$check(empty($s1['success']) && !MatchSideboardSeatReady(SWUReadMatch($mid), 1), 'the one-click legacy submit is refused too');
$st = hit($B . 'SWUSim/SubmitSideboard.php', ['matchId' => $mid, 'playerID' => 1, 'authKey' => $k1, 'action' => 'status']);
$check(!empty($st['success']), 'status still answers');

echo "BuilderBetweenGames:\n";
$bs = between($mid, 1, $k1, 'state');
$check(!empty($bs['success']) && ($bs['context'] ?? '') === 'match' && ($bs['pool'] ?? null) === $pool1 && ($bs['endsIn'] ?? 0) > 280
    && ($bs['deck']['leader'] ?? '') !== '', 'the builder opens on my pool, my last deck and the 5-minute clock: ' . json_encode($bs['message'] ?? ''));
$check(empty(between($mid, 1, $k2, 'state')['success']), 'seat 2\'s key does not open seat 1');

echo "OutOfPoolRefusedBetweenGames:\n";
$pool2 = $m['limited']['pools']['2'] ?? [];
$foreign = array_values(array_filter(array_diff($pool2, $pool1), fn($c) => !in_array(CardType($c), ['Leader', 'Base'], true)));
$cur = SWULimitedDeckFromResolved($m['players']['1']['currentDeck'] ?? $m['players']['1']['originalDeck']);
$bad = ['main' => array_merge(array_slice($cur['main'], 0, 29), [$foreign[0] ?? ''])] + $cur;
$x = between($mid, 1, $k1, 'submit', ['deck' => json_encode($bad)]);
$check(empty($x['success']) && count($x['errors'] ?? []) > 0, 'a card from the other pool is refused: ' . json_encode($x['errors'] ?? $x));

echo "SwitchLeaderAndBaseBetweenGames:\n";
$switched = pickLegalDeck($pool1, $bs['basesInPlay'] ?? [], 1, 1);
$check($switched['leader'] !== $cur['leader'] && $switched['base'] !== $cur['base'], 'a different leader and base from the pool / bases in play');
$sv = between($mid, 1, $k1, 'submit', ['deck' => json_encode($switched)]);
$rd1 = between($mid, 1, $k1, 'ready');
$rd2 = between($mid, 2, $k2, 'ready');
$m = SWUReadMatch($mid);
$g2 = $latestGame($m);
$check(!empty($sv['success']) && !empty($rd1['success']) && !empty($rd2['success']) && $g2 !== $g1 && ($m['state'] ?? '') === 'in_progress',
    'both Ready → game 2: ' . json_encode([$sv['errors'] ?? $sv['message'] ?? null, $rd1['message'] ?? null, $rd2['message'] ?? null]));
$check(($m['players']['1']['currentDeck']['leader'] ?? '') === $switched['leader'] && ($m['players']['1']['currentDeck']['base'] ?? '') === $switched['base'],
    'game 2 plays the switched leader and base');
$check(($m['players']['2']['currentDeck']['leader'] ?? '') === $decks['b']['leader'], 'seat 2 kept its deck by readying with no change');

echo "TimeoutKeepsLastPlayedDeck:\n";
finishGame($g2, 2);   // seat 1 loses game 2 → game 3 sideboarding
MatchWithLock('SWUSim', $mid, function (&$mm) { $mm['sideboardDeadline'] = time() - 1; });
between($mid, 2, $k2, 'state');   // any poll ticks the clock
$m = SWUReadMatch($mid);
$g3 = $latestGame($m);
$check(($m['state'] ?? '') === 'in_progress' && $g3 !== $g2 && ($m['players']['1']['currentDeck']['leader'] ?? '') === $switched['leader'],
    'the clock ran out: game 3 starts and seat 1 keeps its game-2 deck, not its first one (' . ($m['players']['1']['currentDeck']['leader'] ?? '?') . ')');
$st3 = between($mid, 1, $k1, 'state');
$check(($st3['gameName'] ?? '') === $g3, 'the builder is told the next game');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
