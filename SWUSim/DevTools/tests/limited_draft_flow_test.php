<?php
// Limited DRAFT end to end through the real endpoints: create a Draft room, fill it, start (every pack opened and stored),
// pick through SWUSim/LimitedDraftAction.php, privacy, double picks, the pick clock's auto-pick and the kick vote, a
// dropped drafter auto-picking to the end, the hand-off to the builder, and "built". An 8-seat, 6-pack draft too.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.2, §3.6. Pure rules:
// limited_draft_state_test.php.
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/DevTools/tests/limited_draft_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/Limited/LimitedRoom.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
$SIZES = ['poll' => 0, 'state' => 0];
function hit($url, $params, $jar = null, &$raw = null) {
    $ch = curl_init($url);
    $o = [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120];
    if ($jar !== null) { $o[CURLOPT_COOKIEJAR] = $jar; $o[CURLOPT_COOKIEFILE] = $jar; }
    curl_setopt_array($ch, $o);
    $raw = (string)curl_exec($ch); curl_close($ch);
    $j = json_decode($raw, true);
    return is_array($j) ? $j : ['RAW' => substr($raw, 0, 600)];
}
$ROOMS = []; $JARS = [];
register_shutdown_function(function () {
    global $ROOMS, $JARS;
    foreach ($ROOMS as $r) {
        // Plan 4: a built pod starts round 1, so its matches are discarded with the room.
        $l = apcu_fetch($r['lobbyID']);
        foreach (($l->limited['swiss']['pairings'] ?? []) as $round) foreach ($round as $x) {
            if (!empty($x['bye']) || !empty($x['done'])) continue;
            if (function_exists('MatchAbortNoResult')) MatchAbortNoResult('SWUSim', $x['matchId']);
            if (function_exists('RemoveActiveGame')) RemoveActiveGame('SWUSim', $x['gameName']);
        }
        LobbyMutate($r['lobbyID'], fn($l) => 'delete');
        foreach ([SWURoomPath($r['lobbyID']), SWURoomInvitePath($r['invite'])] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
    foreach ($JARS as $j) @unlink($j);
});
function jar(string $user): string {
    global $B, $JARS;
    $j = tempnam(sys_get_temp_dir(), 'ltd'); $JARS[] = $j;
    hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $j);
    return $j;
}
$J = []; for ($i = 1; $i <= 8; $i++) $J[$i] = jar("claudebot$i");

// A Draft room hosted by claudebot1 with claudebot2..$n seated. Seats are named 1..$n.
function draftRoom(int $n, array $settings = []): array {
    global $L, $ROOMS, $J;
    $j = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'limited', 'limitedMode' => 'draft',
        'deckLink' => '', 'preconstructedDeck' => ''], $J[1]);
    $r = ['raw' => $j, 'lobbyID' => strval($j['lobbyID'] ?? ''), 'invite' => strval($j['inviteCode'] ?? ''),
          'keys' => [1 => strval($j['authKey'] ?? '')], 'ids' => [1 => intval($j['playerID'] ?? 0)], 'jars' => [1 => $J[1]]];
    if ($r['lobbyID'] !== '') $ROOMS[] = $r;
    for ($s = 2; $s <= $n; $s++) {
        $jb = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $r['invite'], 'deckLink' => '', 'preconstructedDeck' => ''], $J[$s]);
        $r['keys'][$s] = strval($jb['authKey'] ?? ''); $r['ids'][$s] = intval($jb['playerID'] ?? 0); $r['jars'][$s] = $J[$s];
    }
    if ($settings) act($r, 1, 'settings', ['settings' => json_encode($settings)]);
    return $r;
}
function act(array $r, int $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who]);
}
function draft(array $r, int $who, string $action, array $params = []): array {
    global $B, $SIZES;
    $j = hit($B . 'SWUSim/LimitedDraftAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who], $raw);
    $SIZES['state'] = max($SIZES['state'], strlen($raw));
    return $j;
}
function build(array $r, int $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedBuilderAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params, $r['jars'][$who]);
}
function pollRoom(array $r, int $who): array {
    global $L, $SIZES;
    $j = hit($L . 'PollLobbyUpdates.php', ['lobbyID' => $r['lobbyID'], 'rootName' => 'SWUSim', 'playerID' => $r['ids'][$who], 'authKey' => $r['keys'][$who]], $r['jars'][$who], $raw);
    $SIZES['poll'] = max($SIZES['poll'], strlen($raw));
    return $j['limited'] ?? [];
}
function lt(array $r): array { $l = apcu_fetch($r['lobbyID']); return is_object($l) ? $l->limited : []; }
function pick1(array $r, int $who): ?array {   // pick the first card of the front pack, if any
    $s = draft($r, $who, 'state');
    if (empty($s['front'])) return null;
    return draft($r, $who, 'pick', ['packId' => $s['front']['packId'], 'cardId' => $s['front']['cards'][0]]);
}
function driveAll(array $r, array $seats): void {   // round-robin until nobody has anything to pick and the draft is over
    for ($guard = 0; $guard < 4000; $guard++) {
        if ((lt($r)['phase'] ?? '') !== 'drafting') return;
        $moved = false;
        foreach ($seats as $s) if (pick1($r, $s) !== null) $moved = true;
        if (!$moved) pollRoom($r, $seats[0]);
    }
}
function pid(array $r, int $s): int { return $r['ids'][$s]; }
function pickLegalDeck(array $pool, array $basesInPlay): array {
    $leader = ''; $main = [];
    foreach ($pool as $c) { $t = CardType($c); if ($t === 'Leader') { if ($leader === '') $leader = $c; continue; } if ($t !== 'Base' && count($main) < 30) $main[] = $c; }
    return ['leader' => $leader, 'base' => strval($basesInPlay[0] ?? ''), 'main' => $main];
}

echo "CreateDraftRoom:\n";
$r = draftRoom(2);
$check(!empty($r['raw']['success']) && intval(apcu_fetch($r['lobbyID'])->maxPlayers ?? 0) === 8 && (lt($r)['mode'] ?? '') === 'draft', 'a Draft room seats 8: ' . json_encode($r['raw']['message'] ?? ''));
$bad = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'limited', 'limitedMode' => 'cube', 'deckLink' => ''], $J[1]);
$check(empty($bad['success']) && ($bad['message'] ?? '') === 'Choose Sealed or Draft.', 'an unknown mode is refused: ' . strval($bad['message'] ?? ''));
$s2 = act($r, 1, 'start');
$check(empty($s2['success']) && ($s2['message'] ?? '') === 'Draft needs at least 3 players.', 'two seats cannot start a Draft: ' . strval($s2['message'] ?? ''));

echo "StartOpensEveryPack:\n";
$r = draftRoom(3);
$st = act($r, 1, 'start');
$lt = lt($r); $d = $lt['draft'] ?? [];
$check(!empty($st['success']) && ($lt['phase'] ?? '') === 'drafting' && ($d['stage'] ?? '') === 'leaders', 'start → drafting, the leader draft: ' . json_encode($st['message'] ?? ''));
$pids = array_values($r['ids']); $order = $d['order'] ?? []; $so = $order; sort($so); $sp = $pids; sort($sp);
$check($so === $sp, 'the seating is a permutation of the seats');
$okQueues = true; $okPacks = true; $baseLeak = false; $allIds = [];
foreach ($pids as $p) {
    if (count($d['queues'][$p] ?? []) !== 1 || count($d['queues'][$p][0]['cards']) !== 3) $okQueues = false;
    if (count($d['unopened'][$p] ?? []) !== 3) $okPacks = false;
    foreach ($d['queues'][$p][0]['cards'] ?? [] as $c) $allIds[] = $c;
    foreach ($d['unopened'][$p] ?? [] as $pk) {
        if (count($pk) !== 14) $okPacks = false;
        foreach ($pk as $c) { $allIds[] = $c; if (CardType($c) === 'Base' && CardRarity($c) === 'Common') $baseLeak = true; }
    }
}
$check($okQueues, 'every seat starts on its own 3 leaders');
$check($okPacks && !$baseLeak, 'each seat has 3 packs of 14 waiting, with no common base in them');
$check(array_diff(array_unique($allIds), array_keys($d['aspects'] ?? [])) === [], 'the blob knows every opened card\'s aspects');

echo "DraftStateIsOwnSeatOnly:\n";
$sa = draft($r, 1, 'state'); $sb = draft($r, 2, 'state');
$mineA = array_merge($sa['front']['cards'] ?? [], $sa['mine']['leaders'] ?? [], $sa['mine']['picks'] ?? []);
$onlyB = array_values(array_diff($sb['front']['cards'] ?? [], $mineA));
$pa = pollRoom($r, 1);
$check(!empty($sa['success']) && count($sa['front']['cards'] ?? []) === 3 && intval($sa['playerID'] ?? 0) === pid($r, 1), 'a seat sees its own front pack: ' . json_encode($sa['message'] ?? ''));
$check(count($onlyB) > 0 && array_filter($onlyB, fn($id) => strpos(json_encode($sa), $id) !== false || strpos(json_encode($pa), $id) !== false) === [],
    'none of the other seat\'s ' . count($onlyB) . ' pack cards is in my state or my poll');
$pod = $pa['draft']['pod'] ?? [];
$check(count($pod) === 3 && !array_key_exists('cards', $pod[0]) && !array_key_exists('picks', $pa['draft']) && !array_key_exists('queues', $pa['draft']),
    'the poll\'s pod carries counts only');

echo "DoublePickRefused:\n";
$f = $sa['front'];
$p1 = draft($r, 1, 'pick', ['packId' => $f['packId'], 'cardId' => $f['cards'][0]]);
$p2 = draft($r, 1, 'pick', ['packId' => $f['packId'], 'cardId' => $f['cards'][1]]);
$check(!empty($p1['success']) && empty($p2['success']) && count(lt($r)['draft']['leaders'][pid($r, 1)]) === 1, 'the same pack picked twice: one pick');

echo "PodLeadersArePublic:\n";   // owner (2026-11-05): everyone's drafted leaders are public on the pick screen; card picks stay private
$leaderA = lt($r)['draft']['leaders'][pid($r, 1)][0];
$sb = draft($r, 2, 'state');
$rowA = array_values(array_filter($sb['pod'] ?? [], fn($p) => intval($p['playerID']) === pid($r, 1)))[0] ?? [];
$check(($rowA['leaders'] ?? null) === [$leaderA] && isset($sb['cards'][$leaderA]['title']), 'B\'s pick screen shows the leader A kept, with its card facts: ' . json_encode($rowA['leaders'] ?? null));
$check(count($sb['pod'] ?? []) === 3 && count(array_filter($sb['pod'], fn($p) => !is_array($p['leaders'] ?? null))) === 0 && is_int($rowA['picks'] ?? null),
       'every pod row carries a leaders list; card picks stay a count');

echo "RehydrateMidDraft:\n";
$before = serialize(lt($r));
apcu_delete($r['lobbyID']); apcu_delete('invite:' . $r['invite']);
touch(SWURoomPath($r['lobbyID']), time() - 1000); clearstatcache();
pollRoom($r, 2);
$after = is_object(apcu_fetch($r['lobbyID'])) ? serialize(lt($r)) : '';
$check($after === $before, 'APCu lost the room mid-draft: one poll brings the draft back byte-identical');

echo "FullDraftThreeSeats:\n";
driveAll($r, [1, 2, 3]);
$lt = lt($r);
$sizes = array_map(fn($p) => count($lt['pools'][$p] ?? []), $pids);
$check(($lt['phase'] ?? '') === 'building' && $sizes === [45, 45, 45], 'the draft ends in the build; each pool = 3 leaders + 42 cards: ' . json_encode($sizes));
$bs = build($r, 1, 'state');
$check(($bs['pool'] ?? null) === $lt['pools'][pid($r, 1)] && count($bs['basesInPlay'] ?? []) > 0, 'the builder opens on the drafted pool, with the common bases in play');
foreach ([1, 2, 3] as $s) { $b = build($r, $s, 'state'); build($r, $s, 'submit', ['deck' => json_encode(pickLegalDeck($b['pool'] ?? [], $b['basesInPlay'] ?? []))]); build($r, $s, 'ready'); }
$pv = pollRoom($r, 1);
// Plan 4: a built pod starts its Swiss round 1 at once — three seats make one table and a bye.
$t1 = lt($r)['swiss']['pairings'][1] ?? [];
$check(($pv['phase'] ?? '') === 'playing' && lt($r)['match'] === null && count($t1) === 2 && count(array_filter($t1, fn($x) => !empty($x['bye']))) === 1,
       'everyone Ready → round 1 is playing with one table and one bye: ' . strval($pv['phase'] ?? ''));

echo "TimeoutAutoPickAndKick:\n";
$t = draftRoom(3);
act($t, 1, 'start');
$c = pid($t, 3);
LobbyMutate($t['lobbyID'], function ($l) use ($c) { $l->limited['draft']['frontSince'][$c] -= intval($l->limited['draft']['pickSeconds']); return true; });
foreach ([1, 2] as $s) pollRoom($t, $s);   // keeps A and B connected
$pv = pollRoom($t, 1);
$lt = lt($t);
$check(count($lt['draft']['leaders'][$c]) === 1 && intval($lt['draft']['autoPicks'][$c]) === 1, 'C\'s pack timed out: one auto-pick, counted');
$k = array_values(array_filter($pv['kick'] ?? [], fn($e) => intval($e['target']) === $c))[0] ?? [];
$check(intval($k['needed'] ?? 0) === 2 && !empty($k['canVote']), 'A is offered the vote on C: 2 of 2 needed (a true majority): ' . json_encode($pv['kick'] ?? null));
// Everyone drafting sits on the pick screen, so its state offers the same vote (not only the room page).
$ds = draft($t, 2, 'state');
$dk = array_values(array_filter($ds['kick'] ?? [], fn($e) => intval($e['target']) === $c))[0] ?? [];
$check(intval($dk['needed'] ?? 0) === 2 && !empty($dk['canVote']), 'the pick screen offers B the same vote: ' . json_encode($ds['kick'] ?? null));
act($t, 1, 'kickVote', ['targetPlayerID' => $c]);
$check(empty(lt($t)['seats'][$c]['dropped']), 'one vote of two does not carry');
act($t, 2, 'kickVote', ['targetPlayerID' => $c]);
$check(!empty(lt($t)['seats'][$c]['dropped']) && (lt($t)['phase'] ?? '') === 'drafting', 'the second vote drops C, and the draft goes on');
// Final review #5: the kicked drafter is told.
$cs = draft($t, 3, 'state');
$check(!empty($cs['dropped']), 'C\'s own pick screen is told it was removed: ' . json_encode(['dropped' => $cs['dropped'] ?? null]));
// Final review #2: a state poll that changes nothing must not rewrite the room's disk copy.
$path = SWURoomPath($t['lobbyID']);
touch($path, time() - 500); clearstatcache();
draft($t, 3, 'state'); draft($t, 3, 'state');
clearstatcache();
$check(time() - intval(filemtime($path)) >= 400, 'no-op draft polls leave the snapshot untouched (' . (time() - intval(filemtime($path))) . ' s old)');
driveAll($t, [1, 2]);
$lt = lt($t);
$check(($lt['phase'] ?? '') === 'building' && count($lt['pools'][$c] ?? []) === 45 && count(LTLiveSeats($lt)) === 2,
    'C kept auto-picking: its pool is complete, the other two build');

echo "IconsLineupDraft:\n";   // owner 2026-11-05: Host's Choice may mix in IC27 Icons packs (9 cards, a leader 1 in 4)
$ic = draftRoom(3, ['packMode' => 'hostchoice', 'packCount' => 3, 'lineup' => ['HMW', 'IC27', 'IC27']]);
$st = act($ic, 1, 'start', ['testPickSeconds' => 5]);
$check(!empty($st['success']), 'a Draft of HMW, IC27, IC27 starts: ' . json_encode($st['message'] ?? ''));
$seen = null;
for ($guard = 0; $guard < 400 && (lt($ic)['phase'] ?? '') === 'drafting' && $seen === null; $guard++) {
    foreach ([1, 2, 3] as $s) {
        $sx = draft($ic, $s, 'state');
        if (($sx['stage'] ?? '') === 'cards' && intval($sx['round'] ?? 0) === 2 && !empty($sx['front']) && intval($sx['pickNo'] ?? 0) === 1) { $seen = $sx; break; }
        if (!empty($sx['front'])) draft($ic, $s, 'pick', ['packId' => $sx['front']['packId'], 'cardId' => $sx['front']['cards'][0]]);
    }
}
$check($seen !== null && ($seen['packSet'] ?? '') === 'IC27' && in_array(intval($seen['packSize'] ?? 0), [8, 9], true) && count($seen['front']['cards']) === intval($seen['packSize']),
       'pack 2 is an Icons pack: "IC27", Pick 1 of its own 8 or 9 cards: ' . json_encode(['set' => $seen['packSet'] ?? null, 'size' => $seen['packSize'] ?? null]));
driveAll($ic, [1, 2, 3]);
$lt = lt($ic);
$check(($lt['phase'] ?? '') === 'building' && array_sum(array_map(fn($p) => count($lt['pools'][$p] ?? []), array_values($ic['ids']))) > 0, 'the Icons draft finishes and everyone builds');

echo "EightSeatsSixPacks:\n";
$e = draftRoom(8, ['packCount' => 6]);
$es = act($e, 1, 'start', ['testPickSeconds' => 5]);
$check(!empty($es['success']) && intval(lt($e)['draft']['pickSeconds'] ?? 0) === 5, 'local dev: the pick clock can be shortened for a test');
driveAll($e, range(1, 8));
$lt = lt($e);
$sizes = array_map(fn($s) => count($lt['pools'][pid($e, $s)] ?? []), range(1, 8));
$check(($lt['phase'] ?? '') === 'building' && $sizes === array_fill(0, 8, 90), '8 seats × 6 packs: every pool = 6 leaders + 84 cards: ' . json_encode($sizes));
$check($SIZES['poll'] < 60000 && $SIZES['state'] < 120000, 'the largest poll ' . $SIZES['poll'] . ' B and draft state ' . $SIZES['state'] . ' B stay in budget (60 / 120 KB)');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
