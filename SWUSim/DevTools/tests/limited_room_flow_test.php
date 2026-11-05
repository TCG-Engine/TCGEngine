<?php
// Limited (Sealed) rooms END TO END through the real endpoints: create and join with no deck, host settings, start (the
// packs are opened and stored), the per-viewer poll, the disk snapshot, and the guards on the endpoints a Limited room
// must refuse. The pure rules are limited_room_state_test.php.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.3, §4.1.
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/DevTools/tests/limited_room_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/Limited/LimitedRoom.php';
require_once './SWUSim/Limited/LimitedSettings.php';

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
$ROOMS = []; $JARS = [];
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
    $j = tempnam(sys_get_temp_dir(), 'ltf'); $JARS[] = $j;
    hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $j);
    return $j;
}
$JA = jar('claudebot1'); $JB = jar('claudebot2'); $JC = jar('claudebot3');

// A Sealed room hosted by claudebot1. Returns [lobbyID, invite, keys[a], ids[a], jars[a]] (+ the raw response).
function createLimited(string $jar, string $mode = 'sealed'): array {
    global $L, $ROOMS;
    $j = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'limited', 'limitedMode' => $mode,
        'queueType' => 'bo1', 'deckLink' => '', 'preconstructedDeck' => '', 'game_type' => ''], $jar);
    $r = ['raw' => $j, 'lobbyID' => strval($j['lobbyID'] ?? ''), 'invite' => strval($j['inviteCode'] ?? ''),
          'keys' => ['a' => strval($j['authKey'] ?? '')], 'ids' => ['a' => intval($j['playerID'] ?? 0)], 'jars' => ['a' => $jar]];
    if ($r['lobbyID'] !== '') $ROOMS[] = $r;
    return $r;
}
function joinLimited(array &$r, string $who, string $jar, string $authKey = ''): array {
    global $L;
    $j = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => '',
        'preconstructedDeck' => '', 'game_type' => '', 'authKey' => $authKey], $jar);
    if (!empty($j['success'])) { $r['keys'][$who] = strval($j['authKey'] ?? ''); $r['ids'][$who] = intval($j['playerID'] ?? 0); $r['jars'][$who] = $jar; }
    return $j;
}
function pollRoom(array $r, string $who): array {
    global $L;
    return hit($L . 'PollLobbyUpdates.php', ['lobbyID' => $r['lobbyID'], 'rootName' => 'SWUSim', 'playerID' => $r['ids'][$who],
        'authKey' => $r['keys'][$who]], $r['jars'][$who]);
}
function act(array $r, string $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who]);
}
function settingsPost(array $r, string $who, array $s): array { return act($r, $who, 'settings', ['settings' => json_encode($s)]); }
// A started two-seat room on $settings.
function startedRoom(array $settings): array {
    global $JA, $JB;
    $r = createLimited($JA);
    joinLimited($r, 'b', $JB);
    settingsPost($r, 'a', $settings);
    act($r, 'a', 'start');
    return $r;
}

echo "CreateWithoutDeck:\n";
$r = createLimited($JA);
$check(!empty($r['raw']['success']) && !empty($r['raw']['limited']) && $r['lobbyID'] !== '', 'a Limited room is created with no deck: ' . json_encode($r['raw']['message'] ?? $r['raw']));
$check(is_file(SWURoomPath($r['lobbyID'])), 'it is saved to disk from the start');
$lob = apcu_fetch($r['lobbyID']);
$check(is_object($lob) && ($lob->limited['phase'] ?? '') === 'setup' && intval($lob->maxPlayers) === 2, 'the room is in setup with 2 places');
$bad = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'limited', 'limitedMode' => 'draft',
    'deckLink' => '', 'preconstructedDeck' => ''], $JA);
$check(empty($bad['success']) && ($bad['message'] ?? '') === 'Choose Sealed.', 'Draft is refused until it exists');

echo "JoinWithoutDeckAndCap:\n";
$jb = joinLimited($r, 'b', $JB);
$check(!empty($jb['success']) && $r['ids']['b'] > 0, 'the second player joins by invite with no deck: ' . json_encode($jb['message'] ?? ''));
$jc = joinLimited($r, 'c', $JC);
$check(empty($jc['success']), 'a third player is refused: ' . strval($jc['message'] ?? ''));

echo "SettingsHostOnly:\n";
$g = settingsPost($r, 'b', ['packMode' => 'chaos', 'chaosSets' => ['SOR', 'HMW']]);
$check(empty($g['success']), 'the guest cannot change the settings');
$h = settingsPost($r, 'a', ['packMode' => 'chaos', 'chaosSets' => ['SOR']]);
$check(!empty($h['success']) && in_array('Chaos Sealed needs at least 2 different sets.', $h['errors'] ?? [], true), 'the host\'s invalid settings are stored with their errors');
$pv = pollRoom($r, 'b')['limited'] ?? [];
$check(in_array('Chaos Sealed needs at least 2 different sets.', $pv['settingsErrors'] ?? [], true) && ($pv['settings']['packMode'] ?? '') === 'chaos',
    'the guest\'s poll shows the same settings and errors');
$check(empty($pv['isHost']) && !empty((pollRoom($r, 'a')['limited'] ?? [])['isHost']), 'only the host is told they host');

echo "SettingsFromRequestCastsAndMerges:\n";
$c = settingsPost($r, 'a', ['packCount' => '8', 'bestOf' => '3', 'packMode' => 'single', 'singleSet' => 'SOR']);
$pv = pollRoom($r, 'a')['limited'] ?? [];
$check(!empty($c['success']) && ($c['errors'] ?? null) === [] && ($pv['settings']['packCount'] ?? null) === 8 && ($pv['settings']['bestOf'] ?? null) === 3,
    'form strings "8" / "3" are cast and accepted: ' . json_encode($c['errors'] ?? $c));
$direct = SWULimitedSettingsFromRequest(['packMode' => 'single', 'mode' => 'draft'], 'sealed');
$check($direct['chaosSets'] === [] && $direct['mode'] === 'sealed' && $direct['packCount'] === 6, 'a partial post is merged over the defaults, and the room keeps its own mode');
$check(SWULimitedSettingsFromRequest(['packCount' => '6x'], 'sealed')['packCount'] === '6x', 'a non-numeric string is left for the validator to refuse');

echo "StartRefusedWhileInvalid:\n";
settingsPost($r, 'a', ['packMode' => 'chaos', 'chaosSets' => ['SOR']]);
$s0 = act($r, 'a', 'start');
$check(empty($s0['success']) && (pollRoom($r, 'a')['limited']['phase'] ?? '') === 'setup', 'start is refused while the settings are invalid');
$check(empty(act($r, 'b', 'start')['success']), 'the guest cannot start');

echo "StartOpensPools:\n";
settingsPost($r, 'a', ['packMode' => 'chaos', 'chaosSets' => ['SOR', 'HMW'], 'packCount' => 6]);
$s1 = act($r, 'a', 'start');
$pa = pollRoom($r, 'a')['limited'] ?? [];
$check(!empty($s1['success']) && ($pa['phase'] ?? '') === 'building', 'a valid start → building: ' . json_encode($s1['message'] ?? ''));
$check(($pa['buildEndsIn'] ?? 0) >= 1795 && ($pa['buildEndsIn'] ?? 0) <= 1800, 'the build clock shows ~30 minutes (' . ($pa['buildEndsIn'] ?? 'none') . ')');
$lob = apcu_fetch($r['lobbyID']);
$lt = $lob->limited ?? [];
$ia = $r['ids']['a']; $ib = $r['ids']['b'];
$check(count($lt['pools'][$ia] ?? []) === 96 && count($lt['pools'][$ib] ?? []) === 96, 'each seat opened 6 packs = 96 cards');
$check(count($lt['lineups'][$ia] ?? []) === 6 && array_diff($lt['lineups'][$ia], ['SOR', 'HMW']) === [] && $lt['setsInPlay'] === ['SOR', 'HMW'],
    'the stored lineup is 6 packs from the ticked sets; both are in play');
$check(($lob->state ?? '') === 'started', 'the lobby counts as started (no deck swaps, no host removal, no auto-release)');
$check(empty(settingsPost($r, 'a', ['packMode' => 'single'])['success']), 'settings are locked after start');

echo "PollViewNeverCarriesPools:\n";
$va = pollRoom($r, 'a')['limited'] ?? []; $vb = pollRoom($r, 'b')['limited'] ?? [];
$ja = json_encode($va); $jbv = json_encode($vb);
$check(!array_key_exists('pools', $va) && !array_key_exists('lineups', $va) && strpos($ja, '"deck"') === false && strpos($jbv, '"pools"') === false,
    'the poll carries no pools, lineups or decks');
$check(($va['me']['lineup'] ?? null) === $lt['lineups'][$ia] && ($vb['me']['lineup'] ?? null) === $lt['lineups'][$ib], 'each seat sees its own lineup');
$onlyB = array_slice(array_values(array_diff(array_unique($lt['pools'][$ib]), $lt['pools'][$ia])), 0, 20);
$leaked = array_filter($onlyB, fn($id) => strpos($ja, $id) !== false);
$check(count($onlyB) > 0 && $leaked === [], 'none of ' . count($onlyB) . ' cards only the other seat opened appear in my poll');

echo "RehydrateKeepsPoolsAndDeadline:\n";
$before = serialize(apcu_fetch($r['lobbyID'])->limited);
apcu_delete($r['lobbyID']); apcu_delete('invite:' . $r['invite']);
// A build can sit quiet past the usual 15-minute room window (nothing to save while people build): age the snapshot past it.
touch(SWURoomPath($r['lobbyID']), time() - 1000);
clearstatcache();
$check(!is_object(apcu_fetch($r['lobbyID'])), 'APCu lost the room, and its snapshot is 1000 s old');
$rp = pollRoom($r, 'a');
$after = is_object(apcu_fetch($r['lobbyID'])) ? serialize(apcu_fetch($r['lobbyID'])->limited) : '';
$check(($rp['limited']['phase'] ?? '') === 'building' && $after === $before, 'one poll brings it back from disk, pools and deadline byte-identical');

echo "NoOpTickDoesNotRewriteSnapshot:\n";
// Final review #6: the builder ticks the room every 2 s; a tick that changes nothing must not rewrite the disk copy.
$path = SWURoomPath($r['lobbyID']);
touch($path, time() - 500); clearstatcache();
act($r, 'a', 'tick'); act($r, 'b', 'tick');
clearstatcache();
$check(time() - intval(filemtime($path)) >= 400, 'two no-op ticks leave the snapshot untouched (' . (time() - intval(filemtime($path))) . ' s old)');

echo "GuardsOnOtherEndpoints:\n";
$ud = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $r['lobbyID'], 'playerID' => $ia, 'authKey' => $r['keys']['a'], 'deckLink' => 'https://swudb.com/deck/x', 'rootName' => 'SWUSim'], $JA);
$check(empty($ud['success']), 'UpdateLobbyDeck is refused: ' . strval($ud['message'] ?? ''));
$sr = hit($L . 'StartRoom.php', ['lobbyID' => $r['lobbyID'], 'rootName' => 'SWUSim', 'playerID' => $ia, 'authKey' => $r['keys']['a']], $JA);
$check(empty($sr['success']), 'StartRoom is refused: ' . strval($sr['message'] ?? ''));
$ks = hit($L . 'KickSeat.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys']['a'], 'targetPlayerID' => $ib, 'rootName' => 'SWUSim'], $JA);
$check(empty($ks['success']), 'the host cannot remove a seat after start: ' . strval($ks['message'] ?? ''));
$fresh = createLimited($JA);
$ud2 = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $fresh['lobbyID'], 'playerID' => $fresh['ids']['a'], 'authKey' => $fresh['keys']['a'], 'deckLink' => 'https://swudb.com/deck/x', 'rootName' => 'SWUSim'], $JA);
$check(empty($ud2['success']) && strval($ud2['message'] ?? '') === 'Limited decks are built from your pool.', 'UpdateLobbyDeck is refused in setup too: ' . strval($ud2['message'] ?? ''));

echo "RejoinReclaimsSeat:\n";
$poolB = serialize(apcu_fetch($r['lobbyID'])->limited['pools'][$ib]);
$rj = joinLimited($r, 'b', $JB, $r['keys']['b']);
$check(!empty($rj['success']) && intval($rj['playerID'] ?? 0) === $ib, 'a reload rejoins the same seat: ' . json_encode($rj['message'] ?? ''));
$lob = apcu_fetch($r['lobbyID']);
$check(serialize($lob->limited['pools'][$ib]) === $poolB && count($lob->limited['seats']) === 2, 'the pool and the seats are unchanged');

echo "LeaveDuringSetupFreesSeat:\n";
$lv = createLimited($JA);
joinLimited($lv, 'b', $JB);
hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'playerID' => $lv['ids']['b'], 'lobbyID' => $lv['lobbyID'], 'authKey' => $lv['keys']['b']], $JB);
$after = pollRoom($lv, 'a')['limited'] ?? [];
$check(count($after['seats'] ?? []) === 1 && ($after['phase'] ?? '') === 'setup', 'the guest left during setup → one seat, still setup');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
