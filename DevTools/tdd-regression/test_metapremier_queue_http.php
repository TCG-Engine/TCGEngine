<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_metapremier_queue_http.php
// Meta Premier through the real endpoints — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §2.3, §4, §5.
// Web SAPI only (APCu + sessions). Logs in claudebot1 / claudebot2 (password pass). Every lobby it opens it leaves again.
// The two accounts' metapremier rows are SNAPSHOTTED first and restored at the end, so local rated history survives.
// ⚠ A stale Meta Premier lobby left by manual play (TTL 10 min) can pair with this test's first join; wait it out and re-run.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
include_once __DIR__ . '/../../SWUSim/MatchFlow.php';
require_once __DIR__ . '/../../SWUSim/MetaPremier.php';
require_once __DIR__ . '/../../Core/GamePresence.php';
require_once __DIR__ . '/../../APIs/Lobbies/Classes/Player.php';   // lobbies in APCu hold Player objects

$B = 'http://localhost/TCGEngine/';   // in-container loopback
$L = $B . 'APIs/Lobbies/';
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}
function post($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode((string)$r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 300)];
}
function login($user) {
    global $B;
    $jar = tempnam(sys_get_temp_dir(), 'mpq');
    post($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => $user, 'password' => 'pass'], $jar);
    return $jar;
}
$OPENED = [];   // every lobby this test opens, so a failing run cannot leave one behind to pair with the next run
function join_($jar, $format, $queueType, $deck, $extra = []) {
    global $L, $OPENED;
    $r = post($L . 'JoinQueue.php', array_merge(['rootName' => 'SWUSim', 'deckLink' => $deck, 'format' => $format, 'queueType' => $queueType], $extra), $jar);
    if (!empty($r['lobbyID'])) $OPENED[] = [$jar, $r];
    return $r;
}
function leave($jar, $r) {
    global $L;
    if (empty($r['lobbyID'])) return [];
    return post($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'playerID' => $r['playerID'] ?? 0, 'lobbyID' => $r['lobbyID'], 'authKey' => $r['authKey'] ?? ''], $jar);
}
function fixture($rel) {
    return trim(implode("\n", array_filter(explode("\n", file_get_contents(__DIR__ . '/../../SWUSim/Tests/BotFixtures/' . $rel)), fn($l) => !str_starts_with($l, '#'))));
}
// One game input for $seat of $gameName, authenticated with that seat's match auth key.
function input($m, $gameName, $seat, $mode, $cardID = '') {
    global $B;
    $q = http_build_query(['gameName' => $gameName, 'playerID' => $seat, 'authKey' => $m['players'][strval($seat)]['authKey'] ?? '',
                           'folderPath' => 'SWUSim', 'mode' => $mode, 'cardID' => $cardID, 'responseFormat' => 'json']);
    $ch = curl_init($B . 'ProcessInput.php?' . $q);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60]);
    $r = curl_exec($ch); curl_close($ch);
    return (string)$r;
}
// The pairing join's lobby reply carries the game; the match is found through the game's match ref.
function poll($jar, $r) {
    global $L;
    return post($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'playerID' => $r['playerID'] ?? 0, 'lobbyID' => $r['lobbyID'] ?? '', 'authKey' => $r['authKey'] ?? ''], $jar);
}
// Push a waiting lobby's queue entry time into the past (APCu is shared with the endpoints in this web SAPI).
function backdate($lobbyID, $seconds) {
    $l = apcu_fetch($lobbyID);
    if (!is_object($l)) return false;
    $l->createdAt = intval($l->createdAt ?? time()) - $seconds;
    return apcu_store($lobbyID, $l, 600);
}
function matchOf($gameName) { $ref = SWUReadMatchRef($gameName); return $ref ? SWUReadMatch($ref['matchId']) : null; }

$conn = GetLocalMySQLConnection();
if (!SWUMetaPremierTablesReady($conn)) { echo "SKIP: run Database/migrations/17_glicko_ratings.sql first.\n"; exit; }
$uid = function ($name) use ($conn) {
    $st = $conn->prepare("SELECT usersId FROM users WHERE usersUid = ?"); $st->bind_param('s', $name); $st->execute();
    $r = $st->get_result()->fetch_assoc(); $st->close(); return $r ? intval($r['usersId']) : 0;
};
$U1 = $uid('claudebot1'); $U2 = $uid('claudebot2');
if ($U1 <= 0 || $U2 <= 0) { echo "SKIP: claudebot1/claudebot2 accounts are missing.\n"; exit; }
$ids = "$U1,$U2";
$snapshot = [];
foreach (['glicko_ratings', 'glicko_penalties'] as $t) $snapshot[$t] = $conn->query("SELECT * FROM $t WHERE userId IN ($ids)")->fetch_all(MYSQLI_ASSOC);
$restore = function () use ($conn, $ids, $snapshot) {
    foreach ($snapshot as $t => $rows) {
        $conn->query("DELETE FROM $t WHERE userId IN ($ids)");
        foreach ($rows as $row) {
            $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($row)));
            $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'", array_values($row)));
            $conn->query("INSERT INTO $t ($cols) VALUES ($vals)");
        }
    }
};
$createdMatches = [];
$games = fn($u) => intval((SWUMetaPremierGetRating($conn, $u, 'bo3') ?? ['games' => 0])['games']);

$LEGAL = fixture('ash-meta-2026-09/darth-vader_jtl_yellow.txt');   // Premier-legal
$bot1 = login('claudebot1');
$bot2 = login('claudebot2');
$anon = tempnam(sys_get_temp_dir(), 'mpq');
foreach (['glicko_ratings', 'glicko_penalties'] as $t) $conn->query("DELETE FROM $t WHERE userId IN ($ids)");
// Start clean: a Meta Premier lobby the two test accounts left waiting (an earlier failed run, manual play) would pair
// with this run's joins. They are test accounts, so their waiting rated lobbies are removed outright.
foreach ((apcu_cache_info()['cache_list'] ?? []) as $e) {
    $l = isset($e['info']) ? apcu_fetch($e['info']) : false;
    if (!is_object($l) || ($l->format ?? '') !== 'metapremier' || !empty($l->gameName)) continue;
    foreach (($l->players ?? []) as $p) if ($p instanceof Player && in_array(intval($p->getUserId()), [$U1, $U2], true)) { apcu_delete($e['info']); break; }
}

try {
    echo "── gates ──\n";
    $r = join_($anon, 'metapremier', 'bo3', $LEGAL);
    check(empty($r['success']) && ($r['code'] ?? '') === 'login_required' && empty($r['lobbyID']), 'a guest is refused', $r);
    // Bo1 is a rated ladder too (owner, 2026-10-05).
    $r = join_($bot1, 'metapremier', 'bo1', $LEGAL);
    check(!empty($r['success']) && !empty($r['lobbyID']), 'a logged-in Bo1 join is accepted', $r);
    leave($bot1, $r);
    $r = join_($bot1, 'metapremier', 'bo3', $LEGAL, ['createPrivate' => '1']);
    check(empty($r['success']) && ($r['code'] ?? '') === 'queue_only' && empty($r['lobbyID']), 'a private Meta Premier room is refused', $r);
    $conn->query("INSERT INTO glicko_penalties (userId, abandonStrikes, cooldownUntil) VALUES ($U1, 1, UNIX_TIMESTAMP() + 300)");
    $r = join_($bot1, 'metapremier', 'bo3', $LEGAL);
    check(empty($r['success']) && ($r['code'] ?? '') === 'cooldown' && intval($r['secondsLeft'] ?? 0) > 0, 'a player in cooldown is refused', $r);
    $conn->query("DELETE FROM glicko_penalties WHERE userId IN ($ids)");
    $r = join_($bot1, 'premier', 'bo1', $LEGAL);
    check(!empty($r['success']), 'plain Premier is untouched by the gates', $r);
    leave($bot1, $r);

    echo "── rating window ──\n";
    $conn->query("INSERT INTO glicko_ratings (userId, format, queueType, season, rating, rd, volatility, games, updatedAt) VALUES ($U2,'metapremier','bo3',1,1900,80,0.06,20,UNIX_TIMESTAMP())");
    $a = join_($bot1, 'metapremier', 'bo3', $LEGAL);
    check(!empty($a['success']) && empty($a['ready']), 'claudebot1 (1500) waits', $a);
    $b = join_($bot2, 'metapremier', 'bo3', $LEGAL);
    check(!empty($b['success']) && empty($b['ready']) && ($b['lobbyID'] ?? '') !== ($a['lobbyID'] ?? '-'), 'claudebot2 (1900) is outside the window: its own lobby, no pairing', $b);
    leave($bot2, $b); leave($bot1, $a);
    $conn->query("DELETE FROM glicko_ratings WHERE userId IN ($ids)");

    // Early concede (owner, 2026-10-05): conceding before Round 2's action phase is punished like an abandon. Every game
    // this test can reach is still in its mulligans, so both sections below are early concedes: a strike, no rating.
    $resultOf = fn($mm) => $conn->query("SELECT outcome, rated, winnerUserId FROM glicko_results WHERE matchId='" . $conn->real_escape_string($mm['matchId'])
                              . "' AND matchCreatedAt=" . intval($mm['createdAt']))->fetch_assoc();

    echo "── forfeiting the whole match during mulligans: an early concede (strike, no rating) ──\n";
    $a = join_($bot1, 'metapremier', 'bo3', $LEGAL);
    $b = join_($bot2, 'metapremier', 'bo3', $LEGAL);
    check(!empty($b['ready']) && !empty($b['gameName']), 'two unrated players pair in the window', $b);
    $m = !empty($b['gameName']) ? matchOf($b['gameName']) : null;
    check(is_array($m) && ($m['format'] ?? '') === 'metapremier', 'the match is a metapremier match', $m['format'] ?? null);
    if (is_array($m)) {
        $createdMatches[] = $m;
        input($m, $b['gameName'], 2, 10007);               // claudebot2 forfeits the whole Bo3 before keeping a hand
        $m2 = SWUReadMatch($m['matchId']);
        check(($m2['state'] ?? '') === 'complete' && intval($m2['concededBy'] ?? 0) === 2, 'the forfeit completed the match', $m2['state'] ?? null);
        $d = $m2['games'][0]['detail'] ?? [];
        check(($d['pregameDone'] ?? null) === false && intval($d['turns'] ?? 0) === 1, 'the live capture says Round 1, pregame NOT done', $d);
        check($games($U1) === 0 && $games($U2) === 0, 'neither player was rated', [$games($U1), $games($U2)]);
        check(SWUMetaPremierCooldownLeft($conn, $U2) > 0 && SWUMetaPremierCooldownLeft($conn, $U1) === 0, 'the forfeiting player gets a cooldown strike', SWUMetaPremierCooldownLeft($conn, $U2));
        $row = $resultOf($m);
        check(($row['outcome'] ?? '') === 'early' && intval($row['rated'] ?? 1) === 0, 'logged as an unrated early concede', $row);
    }
    $conn->query("DELETE FROM glicko_penalties WHERE userId IN ($ids)");   // the strike would lock claudebot2 out of the next section

    echo "── an early GAME concede ends the whole series (the after-action path) ──\n";
    $a = join_($bot1, 'metapremier', 'bo3', $LEGAL);
    $b = join_($bot2, 'metapremier', 'bo3', $LEGAL);
    $m = !empty($b['gameName']) ? matchOf($b['gameName']) : null;
    check(is_array($m), 'paired again', $b);
    if (is_array($m)) {
        $createdMatches[] = $m;
        input($m, $b['gameName'], 2, 10006);               // claudebot2 concedes GAME 1 in Round 1
        $m3 = SWUReadMatch($m['matchId']);
        check(($m3['state'] ?? '') === 'complete' && intval($m3['winner'] ?? 0) === 1 && empty($m3['sideboard']),
            'the series ended for claudebot1 — no sideboarding', [$m3['state'] ?? null, $m3['wins'] ?? null]);
        check(SWUMetaPremierCooldownLeft($conn, $U2) > 0, 'claudebot2 gets a cooldown strike', SWUMetaPremierCooldownLeft($conn, $U2));
        $row = $resultOf($m);
        check(($row['outcome'] ?? '') === 'early' && intval($row['winnerUserId'] ?? 0) === $U1, 'logged as an early concede won by claudebot1', $row);
        // The end-game overlay's data: a rated match offers no rematch / convert (spec §4.5).
        $eg = json_decode((string)file_get_contents($B . 'SWUSim/EndGameInfo.php?' . http_build_query(['gameName' => $b['gameName'],
            'playerID' => 1, 'authKey' => $m3['players']['1']['authKey'] ?? ''])), true);
        check(($eg['rated'] ?? null) === true && ($eg['convertible'] ?? null) === false, 'EndGameInfo marks the match rated, not convertible', $eg);
    }
    $conn->query("DELETE FROM glicko_penalties WHERE userId IN ($ids)");

    echo "── Bo1 and Bo3 are separate queues ──\n";
    $a = join_($bot1, 'metapremier', 'bo1', $LEGAL);
    $b = join_($bot2, 'metapremier', 'bo3', $LEGAL);
    check(empty($a['ready']) && empty($b['ready']) && ($a['lobbyID'] ?? '') !== ($b['lobbyID'] ?? '-'), 'a Bo1 and a Bo3 searcher are never paired', [$a, $b]);
    leave($bot2, $b);
    $b = join_($bot2, 'metapremier', 'bo1', $LEGAL);
    $m = !empty($b['gameName']) ? matchOf($b['gameName']) : null;
    check(is_array($m) && ($m['queueType'] ?? '') === 'bo1' && intval($m['bestOf'] ?? 0) === 1, 'two Bo1 searchers pair into a Bo1 match', $m['queueType'] ?? $b);
    if (is_array($m)) { $createdMatches[] = $m; input($m, $b['gameName'], 2, 10007); }   // end it; this section is about pairing
    $conn->query("DELETE FROM glicko_penalties WHERE userId IN ($ids)");

    echo "── two players out of each other's range still meet once their windows open (final review #1) ──\n";
    $conn->query("INSERT INTO glicko_ratings (userId, format, queueType, season, rating, rd, volatility, games, updatedAt) VALUES
                  ($U1,'metapremier','bo3',1,1670,80,0.06,20,UNIX_TIMESTAMP()), ($U2,'metapremier','bo3',1,1330,80,0.06,20,UNIX_TIMESTAMP())
                  ON DUPLICATE KEY UPDATE rating=VALUES(rating), rd=VALUES(rd)");
    $a = join_($bot1, 'metapremier', 'bo3', $LEGAL);
    $b = join_($bot2, 'metapremier', 'bo3', $LEGAL);
    check(empty($a['ready']) && empty($b['ready']) && ($a['lobbyID'] ?? '') !== ($b['lobbyID'] ?? '-'), '1670 and 1330 each wait in their own lobby', [$a, $b]);
    backdate($a['lobbyID'] ?? '', 130); backdate($b['lobbyID'] ?? '', 110);
    $pa = poll($bot1, $a);
    check(($pa['requeue'] ?? null) === true, 'once a window covers the gap, the waiting player is told to re-queue', $pa);
    $a2 = join_($bot1, 'metapremier', 'bo3', $LEGAL);
    check(!empty($a2['ready']) && !empty($a2['gameName']) && ($a2['lobbyID'] ?? '') === ($b['lobbyID'] ?? '-'), "... and the re-queue pairs into the other player's lobby", $a2);
    $mR = !empty($a2['gameName']) ? matchOf($a2['gameName']) : null;
    if (is_array($mR)) { $createdMatches[] = $mR; input($mR, $a2['gameName'], 1, 10007); }   // end it; this section is about pairing
    $conn->query("DELETE FROM glicko_ratings WHERE userId IN ($ids)");
    $conn->query("DELETE FROM glicko_penalties WHERE userId IN ($ids)");

    echo "── an abandon in game 1 ends the whole rated series (final review #3) ──\n";
    $a = join_($bot1, 'metapremier', 'bo3', $LEGAL);
    $b = join_($bot2, 'metapremier', 'bo3', $LEGAL);
    $m = !empty($b['gameName']) ? matchOf($b['gameName']) : null;
    check(is_array($m), 'paired for the abandon case', $b);
    if (is_array($m)) {
        $createdMatches[] = $m;
        PresenceSetClockEnabledInDev($b['gameName'], true);
        PresenceOpenVote($b['gameName'], 2, 'test', time());
        input($m, $b['gameName'], 1, 10021, '2');          // claudebot1 carries the removal vote against seat 2
        $mK = SWUReadMatch($m['matchId']);
        check(($mK['games'][0]['detail']['endReason'] ?? '') === 'abandon', 'game 1 recorded as an abandon', $mK['games'][0] ?? null);
        check(($mK['state'] ?? '') === 'complete' && intval($mK['winner'] ?? 0) === 1, 'the whole series ended for claudebot1 — no sideboarding', [$mK['state'] ?? null, $mK['wins'] ?? null]);
        // Still in mulligans when removed: no rating change, but a strike (spec §4.3).
        check($games($U2) === 0 && SWUMetaPremierCooldownLeft($conn, $U2) > 0, 'a pregame abandon: no rating, but a cooldown strike', [$games($U2), SWUMetaPremierCooldownLeft($conn, $U2)]);
    }
} finally {
    foreach ($OPENED as [$jar, $r]) leave($jar, $r);
    foreach ($createdMatches as $cm) {
        $conn->query("DELETE FROM glicko_results WHERE matchId='" . $conn->real_escape_string($cm['matchId']) . "' AND matchCreatedAt=" . intval($cm['createdAt']));
    }
    $restore();
}
echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
