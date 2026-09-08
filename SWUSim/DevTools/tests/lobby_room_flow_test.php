<?php
// End-to-end waiting-room behaviour, driven through the real endpoints.
//
// The bug this guards: a seat whose browser is STILL POLLING was deleted 10s after its client
// stopped presenting an authKey (localStorage key expiry, a throttled hidden tab, or a ~10s network
// stall). Removal from a private room is now always a human act.
//
// Runs in the WEB SAPI — it needs APCu, which the CLI SAPI does not have (apc.enable_cli=0):
//   http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/lobby_room_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
// Needed to read a lobby straight out of APCu at the end: without the class definition, unserialize
// hands back __PHP_Incomplete_Class and any method call on it is fatal.
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/Player.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

$B = 'http://localhost/TCGEngine/';          // in-container loopback
$L = $B . 'APIs/Lobbies/';

function hit($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_FOLLOWLOCATION => false]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 300)];
}

// Only the HOST needs an account (JoinQueue.php — joining by invite code is deliberately exempt),
// so one login covers the whole fixture.
$jarHost = tempnam(sys_get_temp_dir(), 'lrf');
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jarHost);

// A LEGAL Twin Suns deck, on disk. The network is not what is under test, and a remote fetch inside
// the fixture is exactly the latency this change exists to remove from the join path.
$deck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));

$host = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarHost);
check(!empty($host['success']), 'created a twinsuns room: ' . ($host['message'] ?? ''));
if (empty($host['success'])) { echo "FAIL\n"; exit; }
$lobby = $host['lobbyID']; $invite = $host['inviteCode']; $k1 = $host['authKey'];

$jar2 = tempnam(sys_get_temp_dir(), 'lrf');
$jar3 = tempnam(sys_get_temp_dir(), 'lrf');
$p2 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar2);
$p3 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar3);
check(!empty($p2['success']) && !empty($p3['success']), 'two more seats joined by invite');
$k2 = $p2['authKey']; $k3 = $p3['authKey'];

$poll = function ($key, $jar) use ($L, $lobby) {
    return hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $key], $jar);
};
$ids = function ($r) { $o = []; foreach (($r['roster'] ?? []) as $s) $o[] = $s['playerID']; sort($o); return $o; };
$rowOf = function ($r, $pid) { foreach (($r['roster'] ?? []) as $s) if ($s['playerID'] === $pid) return $s; return null; };

$r = $poll($k1, $jarHost);
check($ids($r) === [1, 2, 3], 'all three seats present at the start');

// THE REGRESSION. P1's browser keeps polling but no longer presents its authKey. Under the old
// 10s reaper this deleted the seat; nothing may remove it now.
for ($i = 0; $i < 8; $i++) { $poll('', $jarHost); $poll($k2, $jar2); $poll($k3, $jar3); usleep(1500000); }
$r = $poll($k2, $jar2);
check($ids($r) === [1, 2, 3], 'a keyless poller keeps its seat for 12s (old reaper deleted it at 10s)');
check($r['numPlayers'] === 3, 'numPlayers unchanged');

// The seat is not yet away — 12s is well inside the 90s threshold.
check(($rowOf($r, 1)['away'] ?? null) === false, 'a 12s-silent seat is not away yet');

// A caller holding an authKey the room does not know must be TOLD, not left staring at a Join button.
$stranger = $poll('deadbeefdeadbeefdeadbeefdeadbeef', $jarHost);
check(!empty($stranger['removed']), 'an unrecognised authKey gets removed:true');
$viewer = $poll('', $jarHost);
check(empty($viewer['removed']), 'a viewer who never joined is not "removed", just not seated');

// ── Concurrent writers must not lose each other's work ───────────────────────────────────────────
// Three polls and a SetReady fired together. Before LobbyMutate, whichever request stored last wrote
// back a snapshot taken before the others and silently reverted them.
$ready = hit($L . 'SetReady.php', ['lobbyID' => $lobby, 'authKey' => $k3, 'ready' => '0'], $jar3);
check(!empty($ready['success']), 'SetReady(false) accepted');

$mh = curl_multi_init();
$handles = [];
foreach ([[$k1, $jarHost], [$k2, $jar2], [$k3, $jar3]] as $i => $pair) {
    list($key, $jar) = $pair;
    $ch = curl_init($L . 'PollLobbyUpdates.php');
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_POSTFIELDS => http_build_query(['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $key])]);
    curl_multi_add_handle($mh, $ch); $handles[] = $ch;
}
$chReady = curl_init($L . 'SetReady.php');
curl_setopt_array($chReady, [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30,
    CURLOPT_COOKIEJAR => $jar3, CURLOPT_COOKIEFILE => $jar3,
    CURLOPT_POSTFIELDS => http_build_query(['lobbyID' => $lobby, 'authKey' => $k3, 'ready' => '1'])]);
curl_multi_add_handle($mh, $chReady); $handles[] = $chReady;
$running = null;
do { curl_multi_exec($mh, $running); curl_multi_select($mh, 0.1); } while ($running > 0);
foreach ($handles as $ch) { curl_multi_remove_handle($mh, $ch); curl_close($ch); }
curl_multi_close($mh);

$after = $poll($k2, $jar2);
check(($rowOf($after, 3)['ready'] ?? null) === true, 'a ready flag set during concurrent polls survives');
check($ids($after) === [1, 2, 3], 'no seat was lost to a concurrent write');

// ── A deck change lands, and disturbs nobody else ────────────────────────────────────────────────
$bad = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $k2, 'deckLink' => 'not-a-deck'], $jar2);
check(!empty($bad['success']) && $bad['deckOk'] === false, 'an unreadable deck is reported, not fatal');
$r = $poll($k2, $jar2);
check(($rowOf($r, 2)['deckOk'] ?? null) === false, 'the rejected deck cleared deckOk on that seat');
check(($rowOf($r, 2)['ready']  ?? null) === false, 'a rejected deck also un-readies the seat');
check($ids($r) === [1, 2, 3], 'a deck change removes nobody');

$good = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $k2, 'deckLink' => $deck], $jar2);
check(!empty($good['deckOk']), 'a legal deck is accepted');
$r = $poll($k2, $jar2);
check(($rowOf($r, 2)['ready'] ?? null) === true, 'a legal deck auto-readies the seat');

// ── Seat IDs must stay unique across a leave and a rejoin ────────────────────────────────────────
// JoinQueue allocated $playerID = $lobby->numPlayers while LeaveQueue splices without renumbering,
// so a rejoin after any removal collided with a seat that was still there. The roster then rendered
// two tiles with the same id, and StartRoom's host lookup matched only the first of them.
hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 2, 'authKey' => $k2], $jar2);
$r = $poll($k1, $jarHost);
check($ids($r) === [1, 3], 'the leaver is gone');

$again = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar2);
check(!empty($again['success']), 'the leaver can rejoin: ' . ($again['message'] ?? ''));
$k2 = $again['authKey'];
$r = $poll($k1, $jarHost);
$seen = $ids($r);
check(count($seen) === count(array_unique($seen)), 'no duplicate playerID after leave + rejoin: [' . implode(',', $seen) . ']');
check($seen === [1, 3, 4], 'the rejoiner took a fresh id above the highest in use');

// ── Kick is the only removal path, and it is host-only ───────────────────────────────────────────
$r = $poll($k1, $jarHost);
$hostRow = null; foreach ($r['roster'] as $row) if (!empty($row['isHost'])) $hostRow = $row;
check($hostRow !== null, 'the roster names a host');

$byNonHost = hit($L . 'KickSeat.php', ['lobbyID' => $lobby, 'authKey' => $k3, 'targetPlayerID' => $hostRow['playerID']], $jar3);
check(empty($byNonHost['success']), 'a non-host cannot kick');
check($ids($poll($k1, $jarHost)) === $ids($r), 'the failed kick removed nobody');

$self = hit($L . 'KickSeat.php', ['lobbyID' => $lobby, 'authKey' => $k1, 'targetPlayerID' => $hostRow['playerID']], $jarHost);
check(empty($self['success']), 'the host cannot kick themselves');

// Kick the seat jar3 holds, so the assertion below can poll AS the victim with the right key.
$victim = ($poll($k3, $jar3))['playerID'] ?? null;
check($victim !== null && $victim !== $hostRow['playerID'], 'the victim is a real non-host seat');
$kick = hit($L . 'KickSeat.php', ['lobbyID' => $lobby, 'authKey' => $k1, 'targetPlayerID' => $victim], $jarHost);
check(!empty($kick['success']), 'the host kicks a seat: ' . ($kick['message'] ?? ''));
$r2 = $poll($k1, $jarHost);
check(!in_array($victim, $ids($r2), true), 'the kicked seat is gone');
check($r2['numPlayers'] === count($ids($r2)), 'numPlayers follows the kick');

// The kicked browser is TOLD, rather than silently shown a Join button again.
$victimSees = $poll($k3, $jar3);
check(!empty($victimSees['removed']), 'the kicked browser is told it was removed');

$ghost = hit($L . 'KickSeat.php', ['lobbyID' => $lobby, 'authKey' => $k1, 'targetPlayerID' => 999], $jarHost);
check(empty($ghost['success']), 'kicking a seat nobody holds fails cleanly');

// Refill so the Start block below still has the three seats Twin Suns needs.
$back = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar3);
check(!empty($back['success']), 'a kicked player can rejoin: ' . ($back['message'] ?? ''));
$k3 = $back['authKey'];

// ── Start ────────────────────────────────────────────────────────────────────────────────────────
// ⚠ THIS MUST STAY THE LAST BLOCK IN THE FILE. A started room refuses every further mutation
// ("Game already started."), so any assertion a later change adds goes ABOVE this block.
$notHost = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 3, 'authKey' => $k3], $jar3);
check(empty($notHost['success']), 'a non-host cannot start');

$r = $poll($k1, $jarHost);
$hostRow = null; foreach ($r['roster'] as $s) if (!empty($s['isHost'])) $hostRow = $s;
$started = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => $hostRow['playerID'], 'authKey' => $k1], $jarHost);
check(!empty($started['success']), 'the host starts the room: ' . ($started['message'] ?? ''));
check(!empty($started['gameName']), 'a game was created');

$after = $poll($k1, $jarHost);
check(!empty($after['started']), 'the poll reports the room as started');

// ── Game creation runs OUTSIDE the lock, so everything it stamped must be carried back ───────────
// It is not just gameName: SWUSim/CreateGame.php calls setGamePlayerID() on every Player, and
// AzukiSim/MatchHooks.php can set casterMode. A commit that wrote only gameName would leave a cached
// lobby whose seats silently carry gamePlayerID 0. This test runs in the web SAPI, so it can read
// APCu directly — the CLI SAPI has its own empty segment and would see nothing.
$cached = apcu_fetch($lobby);
check(is_object($cached), 'the lobby is still in the cache after the start');
check(($cached->state ?? null) === 'started', 'the cached lobby records state=started');
check(!empty($cached->gameName), 'the cached lobby records the gameName');
$seatIds = [];
foreach (($cached->players ?? []) as $pl) $seatIds[] = $pl->getGamePlayerID();
check(count($seatIds) === 3 && count(array_filter($seatIds, fn($x) => intval($x) > 0)) === 3,
      'every seat carried its gamePlayerID back into the cache: ' . json_encode($seatIds));

echo $FAILS === 0 ? "PASS\n" : "FAIL: $FAILS check(s)\n";
