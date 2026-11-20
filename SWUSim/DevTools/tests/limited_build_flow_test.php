<?php
// Limited (Sealed) build → match END TO END: the builder's own-seat state, Submit / Ready with pool-bound validation and
// title-named errors, the one match that follows, its result back in the room, no stats, the end screen's way home, and
// the build-deadline kick vote. Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.3, §3.4,
// §3.6, §3.9. Room setup itself is limited_room_flow_test.php.
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/DevTools/tests/limited_build_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './SWUSim/Limited/LimitedRoom.php';
require_once './SWUSim/Limited/LimitedDeck.php';
require_once './APIs/Lobbies/Classes/LobbySeatIdentity.php';   // LobbyHasStarted

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
function getj($url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode((string)$r, true);
    return is_array($j) ? $j : [];
}
$ROOMS = []; $JARS = []; $GAMES = [];
register_shutdown_function(function () {
    global $ROOMS, $JARS, $GAMES;
    foreach ($ROOMS as $r) {
        LobbyMutate($r['lobbyID'], fn($l) => 'delete');
        foreach ([SWURoomPath($r['lobbyID']), SWURoomInvitePath($r['invite'])] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
    foreach ($GAMES as $g) if (function_exists('RemoveActiveGame')) RemoveActiveGame('SWUSim', $g);
    foreach ($JARS as $j) @unlink($j);
});
function jar(string $user): string {
    global $B, $JARS;
    $j = tempnam(sys_get_temp_dir(), 'ltb'); $JARS[] = $j;
    hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $j);
    return $j;
}
$JA = jar('claudebot1'); $JB = jar('claudebot2');

function room(array $settings): array {
    global $L, $ROOMS, $JA, $JB;
    $j = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'limited', 'limitedMode' => 'sealed',
        'deckLink' => '', 'preconstructedDeck' => ''], $JA);
    $r = ['lobbyID' => strval($j['lobbyID'] ?? ''), 'invite' => strval($j['inviteCode'] ?? ''),
          'keys' => ['a' => strval($j['authKey'] ?? '')], 'ids' => ['a' => intval($j['playerID'] ?? 0)], 'jars' => ['a' => $JA, 'b' => $JB]];
    $ROOMS[] = $r;
    $jb = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => '', 'preconstructedDeck' => ''], $JB);
    $r['keys']['b'] = strval($jb['authKey'] ?? ''); $r['ids']['b'] = intval($jb['playerID'] ?? 0);
    act($r, 'a', 'settings', ['settings' => json_encode($settings)]);
    act($r, 'a', 'start');
    return $r;
}
function act(array $r, string $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who]);
}
function build(array $r, string $who, string $action, array $params = [], ?string $key = null): array {
    global $B;
    return hit($B . 'SWUSim/LimitedBuilderAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $key ?? $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who]);
}
function pollRoom(array $r, string $who): array {
    global $L;
    return hit($L . 'PollLobbyUpdates.php', ['lobbyID' => $r['lobbyID'], 'rootName' => 'SWUSim', 'playerID' => $r['ids'][$who],
        'authKey' => $r['keys'][$who]], $r['jars'][$who])['limited'] ?? [];
}
function lt(array $r): array { $l = apcu_fetch($r['lobbyID']); return is_object($l) ? $l->limited : []; }
// Test-local: the first leader, the first base in play, the first 30 non-leader, non-base cards. Not a deck builder.
function pickLegalDeck(array $pool, array $basesInPlay): array {
    $leader = ''; $main = [];
    foreach ($pool as $c) {
        $t = CardType($c);
        if ($t === 'Leader') { if ($leader === '') $leader = $c; continue; }
        if ($t === 'Base') continue;
        if (count($main) < 30) $main[] = $c;
    }
    return ['leader' => $leader, 'base' => strval($basesInPlay[0] ?? ''), 'main' => $main];
}
function submit(array $r, string $who, array $deck, ?string $key = null): array { return build($r, $who, 'submit', ['deck' => json_encode($deck)], $key); }
$noIds = fn(array $errs) => !preg_match('/\b[A-Z][A-Z0-9]*_\d{3}\b/', implode(' ', $errs));

$single = ['packMode' => 'single', 'singleSet' => 'SOR', 'packCount' => 6, 'bestOf' => 1];

echo "BuilderStateIsOwnSeatOnly:\n";
$r = room($single);
$ia = $r['ids']['a']; $ib = $r['ids']['b'];
$sa = build($r, 'a', 'state'); $sb = build($r, 'b', 'state');
$lt = lt($r);
$check(!empty($sa['success']) && ($sa['context'] ?? '') === 'build' && ($sa['pool'] ?? null) === $lt['pools'][$ia] && ($sb['pool'] ?? null) === $lt['pools'][$ib],
    'each seat gets its own pool: ' . json_encode($sa['message'] ?? ''));
$ids = array_unique(array_merge($sa['pool'] ?? [], $sa['basesInPlay'] ?? []));
$check(count($sa['basesInPlay'] ?? []) > 0 && array_diff($ids, array_keys($sa['cards'] ?? [])) === [], 'card facts cover every pool card and every base in play');
$f = $sa['cards'][($sa['pool'] ?? [''])[0]] ?? [];
$check(isset($f['title'], $f['cost'], $f['aspects'], $f['type'], $f['traits']) && is_array($f['aspects']), 'a card fact has title / cost / aspects[] / type / traits[]');
$onlyB = array_slice(array_values(array_diff(array_unique($lt['pools'][$ib]), $lt['pools'][$ia], $sa['basesInPlay'] ?? [])), 0, 20);
$js = json_encode($sa);
$check(count($onlyB) > 0 && array_filter($onlyB, fn($id) => strpos($js, $id) !== false) === [], 'none of ' . count($onlyB) . ' cards only the other seat opened is in my builder state');
$check(array_key_exists('deck', $sa) && $sa['deck'] === null && empty($sa['submitted']) && ($sa['endsIn'] ?? 0) > 1700, 'no deck yet, the build clock running');
$check(empty(build($r, 'a', 'state', [], 'not-a-key')['success']), 'a wrong key gets nothing');
// The builder's chat is sent as this seat — the page needs its lobby playerID (final review #4).
$check(intval($sa['playerID'] ?? 0) === $ia && intval($sb['playerID'] ?? 0) === $ib, 'the state names the caller\'s own seat (chat sends as it)');

echo "CraftedDeckPostsRefused:\n";
$deckA = pickLegalDeck($lt['pools'][$ia], $sa['basesInPlay']);
$deckB = pickLegalDeck($lt['pools'][$ib], $sb['basesInPlay']);
$notOpened = array_values(array_diff(array_unique($lt['pools'][$ib]), $lt['pools'][$ia]));
$nonLeaderNotOpened = array_values(array_filter($notOpened, fn($c) => !in_array(CardType($c), ['Leader', 'Base'], true)));
$x1 = submit($r, 'a', ['main' => array_merge(array_slice($deckA['main'], 0, 29), [$nonLeaderNotOpened[0]])] + $deckA);
$check(empty($x1['success']) && count($x1['errors'] ?? []) > 0 && $noIds($x1['errors']), 'a card I did not open is refused, named by title: ' . json_encode($x1['errors'] ?? $x1));
$counts = array_count_values(array_map('CardIDOverride', $lt['pools'][$ia]));
$one = ''; foreach ($deckA['main'] as $c) if (($counts[CardIDOverride($c)] ?? 0) === 1) { $one = $c; break; }
$x2 = submit($r, 'a', ['main' => array_merge($deckA['main'], [$one])] + $deckA);
$check($one !== '' && empty($x2['success']) && $noIds($x2['errors'] ?? []), 'one copy more than the pool holds is refused: ' . json_encode($x2['errors'] ?? $x2));
$otherLeader = ''; foreach ($notOpened as $c) if (CardType($c) === 'Leader') { $otherLeader = $c; break; }
$x3 = submit($r, 'a', ['leader' => $otherLeader] + $deckA);
$check($otherLeader === '' || (empty($x3['success']) && $noIds($x3['errors'] ?? [])), 'a leader from the other seat\'s pool is refused' . ($otherLeader === '' ? ' (pools share every leader — skipped)' : ''));
$x4 = submit($r, 'a', $deckB);
$check(empty($x4['success']) && empty(lt($r)['seats'][$ib]['submitted']), 'seat B\'s deck posted with seat A\'s key lands on A, fails A\'s pool, and never touches B');

echo "ReadyNeedsSubmit:\n";
$check(empty(build($r, 'a', 'ready')['success']), 'Ready before Submit is refused');
$ok = submit($r, 'a', $deckA);
$check(!empty($ok['success']) && ($ok['errors'] ?? null) === [] && !empty(lt($r)['seats'][$ia]['submitted']), 'a legal deck from my pool is submitted: ' . json_encode($ok['errors'] ?? $ok));
$rd = build($r, 'a', 'ready');
$check(!empty($rd['success']) && !empty(lt($r)['seats'][$ia]['ready']), 'Ready after Submit');
$check(empty(submit($r, 'a', $deckA)['success']), 'no Submit after Ready');
$st = build($r, 'a', 'state');
$check(($st['deck']['leader'] ?? '') === $deckA['leader'] && !empty($st['ready']), 'the builder state carries my submitted deck back');

echo "ErrorTextUsesTitles:\n";
$t = SWULimitedErrorText('SOR_010 is not in your pool.');
$check(strpos($t, CardTitle('SOR_010')) !== false && strpos($t, 'SOR_010') === false, 'an error names the card by title: ' . $t);

echo "DeckJsonRoundTrips:\n";
$res = SWUResolveDeckInput(SWULimitedDeckJson($deckA, $lt['pools'][$ia]));
$sorted = function (array $a) { sort($a); return $a; };
$check(!empty($res['success']) && $res['leader'] === $deckA['leader'] && $res['base'] === $deckA['base'] && $sorted($res['mainDeck']) === $sorted($deckA['main']),
    'the deck JSON resolves to the same leader, base and main deck');
$check(count($res['sideboard'] ?? []) === count(SWULimitedSideboard($lt['pools'][$ia], $deckA['leader'], $deckA['base'], $deckA['main'])), 'its sideboard is the unused rest of the pool');

echo "BothReadyStartsOneMatch:\n";
$t0 = time();
$before = glob(MatchesDir('SWUSim') . '/*/Match.json') ?: [];
submit($r, 'b', $deckB);
build($r, 'b', 'ready');
build($r, 'a', 'state');
$pv = pollRoom($r, 'a');
$lt = lt($r);
$mid = strval($lt['match']['matchId'] ?? '');
$g1 = strval($pv['live']['gameName'] ?? '');
if ($g1 !== '') $GAMES[] = $g1;
$check(($pv['phase'] ?? '') === 'playing' && $g1 !== '' && $mid !== '', 'both Ready → the room is playing game ' . $g1);
$check(is_dir('./SWUSim/Games/' . $g1), 'the game exists on disk');
$m = SWUReadMatch($mid);
$seatOf = array_flip($lt['match']['seats'] ?? []);
$check(($m['players'][strval($seatOf[$ia] ?? 0)]['originalDeck']['leader'] ?? '') === $deckA['leader']
    && ($m['players'][strval($seatOf[$ib] ?? 0)]['originalDeck']['base'] ?? '') === $deckB['base'], 'the match plays each seat\'s submitted leader and base');
$check(($m['limited']['pools'][strval($seatOf[$ia] ?? 0)] ?? null) === $lt['pools'][$ia] && intval($m['sideboardSeconds'] ?? 0) === 300
    && ($m['sideboardFallback'] ?? '') === 'current' && ($m['roomId'] ?? '') === $r['lobbyID'], 'the match carries the pools, the 5-minute cap and its room');
$check(($m['format'] ?? '') === 'limited', 'the match is format limited');

echo "SecondTickDoesNotCreateASecondMatch:\n";
act($r, 'a', 'tick'); act($r, 'b', 'tick');
SWULimitedTick($r['lobbyID'], time());
$after = glob(MatchesDir('SWUSim') . '/*/Match.json') ?: [];
$check(strval(lt($r)['match']['matchId'] ?? '') === $mid && count($after) - count($before) === 1, 'more ticks: the same match, exactly one new match on disk');

echo "NoStatsFired:\n";
$statsCalls = 0;
$GLOBALS['SWUSTATS_HTTP_FAKE'] = function ($method, $url, $body) use (&$statsCalls) { $statsCalls++; return ['status' => 200, 'body' => ['success' => true], 'raw' => '{}']; };
$savedDQ = $GLOBALS['gDecisionQueueVariables'] ?? null;
$winSeat = intval($seatOf[$ia]);
$GLOBALS['gDecisionQueueVariables'] = json_encode(['GAMEOVER_WINNER' => strval($winSeat), 'GAMEOVER_WINNERS' => strval($winSeat)]);
MatchAfterActionHook('SWUSim', $g1);
$GLOBALS['gDecisionQueueVariables'] = $savedDQ;
unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
$m = SWUReadMatch($mid);
$check(($m['state'] ?? '') === 'complete' && ($m['statsStatus'] ?? '') === 'skipped_limited' && $statsCalls === 0, 'the Bo1 completes and nothing is sent to SWUStats (' . ($m['statsStatus'] ?? 'none') . ', ' . $statsCalls . ' calls)');
// No rematch / convert-to-Bo3 on the SERVER either (final review #3): a rematch would be a Limited match with no pools.
$check(SWUMatchAllowsSeriesChange($m) === false, 'a finished Limited match refuses a rematch or a convert');
$pv = pollRoom($r, 'a');
$check(($pv['phase'] ?? '') === 'done' && intval($pv['result']['winner'] ?? 0) === $ia && ($pv['result']['reason'] ?? '') === 'played',
    'the room shows the event over, won by seat A: ' . json_encode($pv['result'] ?? null));

echo "EndScreenReturnsToRoom:\n";
$egi = getj($B . 'SWUSim/EndGameInfo.php?gameName=' . urlencode($g1) . '&playerID=' . $winSeat . '&authKey=' . urlencode($m['players'][strval($winSeat)]['authKey'] ?? ''));
$check(($egi['origin'] ?? '') === 'limited' && ($egi['roomId'] ?? '') === $r['lobbyID'], 'a seat\'s end screen leads back to the room: ' . json_encode(['o' => $egi['origin'] ?? null, 'r' => $egi['roomId'] ?? null]));
$egs = getj($B . 'SWUSim/EndGameInfo.php?gameName=' . urlencode($g1) . '&playerID=S');
$check(!array_key_exists('roomId', $egs), 'a watcher is never handed the room id');

// ── After the match: Rematch (the other player confirms) and New Pool (owner 2026-11-05) ─────────────────────────────
echo "RematchNeedsTheHost:\n";
$rb = act($r, 'b', 'rematch', ['bestOf' => 1]);
$check(empty($rb['success']) && array_key_exists('rematch', $pr = pollRoom($r, 'a')) && $pr['rematch'] === null, 'only the host asks: ' . ($rb['message'] ?? ''));
$check(!empty(pollRoom($r, 'a')['canRematch']), 'the finished room offers it');

echo "RematchAcceptReplaysTheLastDecks:\n";
// A Bo3-style swap: A's seat finished the match on a deck one card different from the one it submitted (currentDeck).
$swapIn = '';
$used = array_count_values($deckA['main']);
foreach (array_count_values($lt['pools'][$ia]) as $c => $n) {
    if (!in_array(CardType($c), ['Leader', 'Base'], true) && $n > ($used[$c] ?? 0) && $c !== $deckA['main'][0]) { $swapIn = strval($c); break; }
}
$swapped = $deckA; $swapped['main'][0] = $swapIn;
$resolvedSwap = SWUResolveDeckInput(SWULimitedDeckJson($swapped, $lt['pools'][$ia]));
MatchWithLock('SWUSim', $mid, function (&$mm) use ($seatOf, $ia, $resolvedSwap) { $mm['players'][strval($seatOf[$ia])]['currentDeck'] = $resolvedSwap; });
$ask = act($r, 'a', 'rematch', ['bestOf' => 3]);
$pb = pollRoom($r, 'b'); $pa = pollRoom($r, 'a');
$check(!empty($ask['success']) && !empty($pb['rematch']['toMe']) && intval($pb['rematch']['bestOf'] ?? 0) === 3 && ($pb['rematch']['fromName'] ?? '') !== ''
    && !empty($pa['rematch']['fromMe']) && ($pa['phase'] ?? '') === 'done', 'the ask reaches B as a Best of 3, and the room is still finished: ' . json_encode($pb['rematch'] ?? null));
$check(empty(act($r, 'a', 'rematch', ['bestOf' => 1])['success']) && intval(pollRoom($r, 'b')['rematch']['bestOf'] ?? 0) === 3, 'the format is locked while the ask is open');
$check(empty(act($r, 'a', 'rematchAccept')['success']), 'the host cannot accept their own ask');
$acc = act($r, 'b', 'rematchAccept');
$pv = pollRoom($r, 'a');
$lt2 = lt($r);
$mid2 = strval($lt2['match']['matchId'] ?? '');
$g2 = strval($pv['live']['gameName'] ?? '');
if ($g2 !== '') $GAMES[] = $g2;
$check(!empty($acc['success']) && ($pv['phase'] ?? '') === 'playing' && $mid2 !== '' && $mid2 !== $mid && $g2 !== '' && $g2 !== $g1,
    'Accept starts a NEW match at once: ' . json_encode(['phase' => $pv['phase'] ?? null, 'm' => $mid2, 'g' => $g2, 'msg' => $acc['message'] ?? null]));
$m2 = SWUReadMatch($mid2);
$seat2 = array_flip($lt2['match']['seats'] ?? []);
$mainA = $m2['players'][strval($seat2[$ia] ?? 0)]['originalDeck']['mainDeck'] ?? [];
$mainB = $m2['players'][strval($seat2[$ib] ?? 0)]['originalDeck']['mainDeck'] ?? [];
$check($swapIn !== '' && $sorted($mainA) === $sorted($swapped['main']) && $sorted($mainB) === $sorted($deckB['main']),
    'A replays the deck it played LAST (the swap), B its event deck');
$check(intval($lt2['settings']['bestOf'] ?? 0) === 3 && ($m2['queueType'] ?? $m2['bestOf'] ?? '') !== '' && (($m2['queueType'] ?? '') === 'bo3' || intval($m2['bestOf'] ?? 0) === 3),
    'the rematch is the asked Best of 3: ' . json_encode(['q' => $m2['queueType'] ?? null, 'b' => $m2['bestOf'] ?? null]));

// A second finished room: both build, the Bo1 is played, A wins.
$playedRoom = function () use ($single, $sorted) {
    $x = room($single);
    foreach (['a', 'b'] as $who) { $s = build($x, $who, 'state'); submit($x, $who, pickLegalDeck($s['pool'] ?? [], $s['basesInPlay'] ?? [])); build($x, $who, 'ready'); }
    $xl = lt($x);
    $g = strval($xl['match']['gameName'] ?? '');
    $GLOBALS['GAMES'][] = $g;
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = fn($method, $url, $body) => ['status' => 200, 'body' => ['success' => true], 'raw' => '{}'];
    $seat = intval(array_flip($xl['match']['seats'])[$x['ids']['a']]);
    $GLOBALS['gDecisionQueueVariables'] = json_encode(['GAMEOVER_WINNER' => strval($seat), 'GAMEOVER_WINNERS' => strval($seat)]);
    MatchAfterActionHook('SWUSim', $g);
    unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
    pollRoom($x, 'a');
    return $x;
};

echo "RematchDeclineAndCancel:\n";
$y = $playedRoom();
$check((pollRoom($y, 'a')['phase'] ?? '') === 'done', 'the second room finished');
act($y, 'a', 'rematch', ['bestOf' => 1]);
$check(empty(act($y, 'a', 'rematchDecline')['success']), 'the host cannot decline their own ask');
$dc = act($y, 'b', 'rematchDecline');
$ya = pollRoom($y, 'a');
$check(!empty($dc['success']) && !empty($ya['rematch']['declined']) && ($ya['phase'] ?? '') === 'done', 'B declines: A sees it and the room stays finished');
$check(!empty(act($y, 'a', 'rematch', ['bestOf' => 1])['success']) && empty(pollRoom($y, 'b')['rematch']['declined']), 'A may ask again');
$cc = act($y, 'a', 'rematchCancel');
$check(!empty($cc['success']) && array_key_exists('rematch', $pr = pollRoom($y, 'b')) && $pr['rematch'] === null, 'A cancels: the ask is gone for B');

echo "NewPoolBackToSetup:\n";
$check(empty(act($y, 'b', 'newPool')['success']), 'only the host starts a new pool');
$poolBefore = lt($y)['pools'][$y['ids']['a']] ?? [];
$np = act($y, 'a', 'newPool');
$yv = pollRoom($y, 'b');
$check(!empty($np['success']) && ($yv['phase'] ?? '') === 'setup' && intval($yv['settings']['packCount'] ?? 0) === 6 && ($yv['settings']['singleSet'] ?? '') === 'SOR'
    && count($yv['seats'] ?? []) === 2, 'New Pool: setup again, the same settings, both players seated: ' . ($np['message'] ?? ''));
$check(LobbyHasStarted(apcu_fetch($y['lobbyID'])) === false, 'the room is open for settings again (not "started")');
$st = act($y, 'a', 'start');
$yl = lt($y);
$check(!empty($st['success']) && $yl['phase'] === 'building' && ($yl['pools'][$y['ids']['a']] ?? []) !== [] && ($yl['pools'][$y['ids']['a']] ?? []) !== $poolBefore,
    'Start opens FRESH pools for a new build: ' . ($st['message'] ?? ''));

echo "BuildExpiryStartsTheMatch:\n";
// Final review #1: both seats SUBMIT, nobody presses Ready, the clock runs out — the spec readies them with the submitted
// decks and round 1 starts. The seats' Player deckLink was only ever written by Ready.
$x = room($single);
foreach (['a', 'b'] as $who) { $s = build($x, $who, 'state'); submit($x, $who, pickLegalDeck($s['pool'] ?? [], $s['basesInPlay'] ?? [])); }
LobbyMutate($x['lobbyID'], function ($l) { $l->limited['buildDeadline'] = time() - 1; return true; });
act($x, 'a', 'tick');
$xv = pollRoom($x, 'a');
$xg = strval($xv['live']['gameName'] ?? '');
if ($xg !== '') $GAMES[] = $xg;
$check(($xv['phase'] ?? '') === 'playing' && $xg !== '', 'the deadline readies both submitted decks and the match starts (' . ($xv['phase'] ?? '?') . ')');

echo "KickVoteAfterDeadline:\n";
$k = room($single);
$ka = $k['ids']['a']; $kb = $k['ids']['b'];
$ks = build($k, 'a', 'state');
submit($k, 'a', pickLegalDeck($ks['pool'] ?? [], $ks['basesInPlay'] ?? []));
LobbyMutate($k['lobbyID'], function ($l) { $l->limited['buildDeadline'] = time() - 1; return true; });
pollRoom($k, 'b');   // B is connected; the deadline alone makes B kickable
$kv = pollRoom($k, 'a');
$entry = array_values(array_filter($kv['kick'] ?? [], fn($e) => intval($e['target']) === $kb))[0] ?? [];
$check(intval($entry['needed'] ?? 0) === 1 && !empty($entry['canVote']), 'after the deadline A is offered a 1-of-1 vote on B: ' . json_encode($kv['kick'] ?? null));
$vr = act($k, 'a', 'kickVote', ['targetPlayerID' => $kb]);
$kv = pollRoom($k, 'a');
$check(!empty($vr['success']) && ($kv['phase'] ?? '') === 'done' && intval($kv['result']['winner'] ?? 0) === $ka && ($kv['result']['reason'] ?? '') === 'kicked'
    && array_key_exists('match', lt($k)) && lt($k)['match'] === null, 'one vote carries: the event ends, A wins, no match was made');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
