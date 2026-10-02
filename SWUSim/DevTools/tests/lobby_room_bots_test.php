<?php
// Twin Suns "Fill Seat with Bot" — the ROOM side, driven through the real endpoints
// (SWUSim/docs/todo-twinsuns-fill-bot.md, Decisions 2/3/5/6 and step 1).
//
//   • Host-only. A pre-con (profile "precon:<key>") or the host's pasted list ("custom"), validated like a human's.
//   • Decision 5: a PRIVATE room can add a bot at once; a PUBLIC room only 60s after the last HUMAN joined.
//   • Decision 6: bots are placeholders. A human joining a full room takes the seat of the OLDEST bot (FIFO by
//     joinedAt). A room full of humans still refuses.
//   • Start with a bot seat creates a game in which that seat is driven by the bot.
//
// Runs in the WEB SAPI — it needs APCu (apc.enable_cli=0), like lobby_room_flow_test.php:
//   curl -s http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/lobby_room_bots_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/Player.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

$B = 'http://localhost/TCGEngine/';
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
function jar() { return tempnam(sys_get_temp_dir(), 'lrb'); }
// Rewind every seat's joinedAt (Decision 5's clock) — never sleep 60s. $only = [playerID => secondsAgo] for a subset.
function backdate(string $lobbyID, int $secondsAgo, array $only = []): void {
    $l = apcu_fetch($lobbyID);
    foreach (($l->players ?? []) as $p) {
        if (!($p instanceof Player)) continue;
        $pid = intval($p->getPlayerID());
        if ($only && !isset($only[$pid])) continue;
        $p->setJoinedAt(time() - ($only[$pid] ?? $secondsAgo));
    }
    apcu_store($lobbyID, $l);
}
// [playerID => username] — a bot's name ("Arenabot Beta").
function names(string $lobbyID): array {
    $l = apcu_fetch($lobbyID); $o = [];
    foreach (($l->players ?? []) as $p) if ($p instanceof Player && $p->getBotProfile() !== '') $o[intval($p->getPlayerID())] = $p->getUsername();
    ksort($o); return $o;
}
function seats(string $lobbyID): array {   // [playerID => botProfile]
    $l = apcu_fetch($lobbyID); $o = [];
    foreach (($l->players ?? []) as $p) if ($p instanceof Player) $o[intval($p->getPlayerID())] = $p->getBotProfile();
    ksort($o); return $o;
}

$jarHost = jar();
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jarHost);
$deck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));
$join = function ($invite, $jar) use ($L, $deck) {
    return hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar);
};
$addBot = function ($lobbyID, $key, $profile, $botDeck = '') use ($L, $jarHost) {
    return hit($L . 'AddBot.php', ['lobbyID' => $lobbyID, 'authKey' => $key, 'botProfile' => $profile, 'botDeck' => $botDeck], $jarHost);
};

echo "── private room ──\n";
$host = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarHost);
check(!empty($host['success']), 'created a private twinsuns room: ' . ($host['message'] ?? json_encode($host)));
if (empty($host['success'])) { echo "FAIL\n"; exit; }
$lobby = $host['lobbyID']; $invite = $host['inviteCode']; $k1 = $host['authKey'];

$poll = hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $k1], $jarHost);
$profiles = array_keys((array)($poll['botProfiles'] ?? []));
check(in_array('custom', $profiles, true) && in_array('precon:ts1', $profiles, true),
    'the room offers the four pre-cons and a pasted list: ' . json_encode($profiles));

$jar2 = jar();
$p2 = $join($invite, $jar2);
check(!empty($p2['success']), 'a friend joined');
$r = $addBot($lobby, $p2['authKey'], 'precon:ts1');
check(empty($r['success']) && stripos($r['message'] ?? '', 'host') !== false, 'a non-host cannot add a bot: ' . ($r['message'] ?? ''));

$r = $addBot($lobby, $k1, 'precon:ts1');
check(!empty($r['success']), 'private room: the host adds a pre-con bot at once: ' . ($r['message'] ?? ''));
$r = $addBot($lobby, $k1, 'custom', '');
check(empty($r['success']), 'a pasted-list bot with no list is refused: ' . ($r['message'] ?? ''));
$r = $addBot($lobby, $k1, 'custom', '{"not":"a deck"}');
check(empty($r['success']), 'a pasted-list bot with an unreadable list is refused: ' . ($r['message'] ?? ''));
$r = $addBot($lobby, $k1, 'custom', $deck);
check(!empty($r['success']), 'the host adds a bot with a pasted Twin Suns list: ' . ($r['message'] ?? ''));

$s = seats($lobby);
$bots = array_keys(array_filter($s, fn($p) => $p !== ''));
check(count($s) === 4 && count($bots) === 2, 'room is full: 2 humans + 2 bots ' . json_encode($s));
$l = apcu_fetch($lobby);
$botOk = count($bots) === 2;
foreach ($l->players as $p) if ($p->getBotProfile() !== '') $botOk = $botOk && $p->getDeckOk() && $p->getReady() && $p->getDeckLink() !== '' && count($p->getLeaders()) === 2;
check($botOk, 'each bot seat has a legal deck, two leaders, and is ready');
// Names go up the Greek alphabet in the order bots are ADDED (owner, 2026-10-01).
check(array_values(names($lobby)) === ['Arenabot Alpha', 'Arenabot Beta'], 'bots are named in the order added: ' . json_encode(names($lobby)));

// FIFO, made to DISAGREE with seat order: the SECOND bot is backdated to be the older one.
[$botA, $botB] = $bots;
backdate($lobby, 0, [$botA => 10, $botB => 30]);
$jar3 = jar();
$p3 = $join($invite, $jar3);
check(!empty($p3['success']), 'a third friend joins the bot-full room: ' . ($p3['message'] ?? ''));
$s = seats($lobby);
// (The joiner may be handed the freed playerID, so read WHO sits there, not whether the ID exists.)
check(($s[$botB] ?? '') === '' && ($s[$botA] ?? '') !== '', "the OLDEST bot (P$botB) gave up its seat, not P$botA " . json_encode($s));
check(count($s) === 4, 'still four seats');
$jar4 = jar();
$p4 = $join($invite, $jar4);
check(!empty($p4['success']), 'a fourth friend replaces the last bot');
check(count(array_filter(seats($lobby), fn($p) => $p !== '')) === 0, 'no bots left ' . json_encode(seats($lobby)));
$p5 = $join($invite, jar());
check(empty($p5['success']) && stripos($p5['message'] ?? '', 'full') !== false, 'a room full of HUMANS still refuses: ' . ($p5['message'] ?? ''));

echo "── public room ──\n";
// Public matchmaking joins ANY open public twinsuns room, so a room an earlier run of this test left behind would be
// joined instead of a fresh one. Remove this test's own leftovers (a claudebot host; local dev only) first and last.
function drop_test_public_rooms(): int {
    $n = 0;
    foreach ((apcu_cache_info()['cache_list'] ?? []) as $e) {
        $l = apcu_fetch($e['info'] ?? '');
        if (!is_object($l) || ($l->rootName ?? '') !== 'SWUSim' || ($l->format ?? '') !== 'twinsuns' || !empty($l->isPrivate) || !empty($l->gameName)) continue;
        $host = null;
        foreach (($l->players ?? []) as $p) if ($p instanceof Player && intval($p->getPlayerID()) === intval($l->hostPlayerID ?? 0)) $host = $p;
        if ($host !== null && strpos($host->getUsername(), 'claudebot') === 0) { apcu_delete($e['info']); $n++; }
    }
    return $n;
}
echo "  (removed " . drop_test_public_rooms() . " leftover test room(s))\n";
$jarPub = jar();
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot2', 'password' => 'pass'], $jarPub);
$pub = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'format' => 'twinsuns', 'queueType' => 'bo1',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarPub);
check(!empty($pub['success']) && !empty($pub['isRoom'] ?? true), 'claudebot2 opened a public twinsuns room: ' . ($pub['message'] ?? json_encode($pub)));
$plobby = $pub['lobbyID'] ?? ''; $pk = $pub['authKey'] ?? '';
$addPub = function ($profile) use ($L, $jarPub, &$plobby, &$pk) {
    return hit($L . 'AddBot.php', ['lobbyID' => $plobby, 'authKey' => $pk, 'botProfile' => $profile], $jarPub);
};
$pl = apcu_fetch($plobby);
check(is_object($pl) && empty($pl->isPrivate), 'it is a PUBLIC room');
// Public matchmaking joins any open public twinsuns room, including one a previous run left behind (they live
// ~15 min). Only a room this run CREATED has a known join history.
if (!is_object($pl) || intval($pl->numPlayers ?? 0) !== 1) {
    echo "  SKIP: landed in a leftover public room (" . intval($pl->numPlayers ?? 0) . " seats); rerun in ~15 min\n";
    $pl = null;
}
if ($pl !== null) {
$r = $addPub('precon:ts2');
check(empty($r['success']) && preg_match('/\d+\s*s/', $r['message'] ?? ''), 'public room: no bot in the first 60s, and it says how long: ' . ($r['message'] ?? ''));
backdate($plobby, 61);
// The alphabet WRAPS: with the room's counter at its last letter, the next two bots are Omega, then Alpha again.
$pl2 = apcu_fetch($plobby); $pl2->swuBotNameIndex = 23; apcu_store($plobby, $pl2);
$r = $addPub('precon:ts2');
check(!empty($r['success']), 'public room: a bot can be added once 60s have passed since the last human joined: ' . ($r['message'] ?? ''));
$r = $addPub('precon:ts3');
check(!empty($r['success']), "adding a bot does not restart the wait (a second bot goes in at once): " . ($r['message'] ?? ''));
check(array_values(names($plobby)) === ['Arenabot Omega', 'Arenabot Alpha'], 'after Omega the names wrap to Alpha: ' . json_encode(names($plobby)));
// A human joining restarts the wait.
$pj = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => strval($pl->inviteCode ?? ''), 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], jar());
check(!empty($pj['success']), 'a human joins the public room by its link (host + 2 bots + human = full)');
$r = $addPub('precon:ts4');
check(empty($r['success']) && preg_match('/\d+\s*s/', $r['message'] ?? ''), 'that join restarted the 60s wait: ' . ($r['message'] ?? ''));
backdate($plobby, 61);
$r = $addPub('precon:ts4');
check(empty($r['success']) && stripos($r['message'] ?? '', 'full') !== false, 'once it lapses the only refusal left is a full room: ' . ($r['message'] ?? ''));
// PUBLIC MATCHMAKING (not the link) into a room full only because of bots: the searcher lands here and a bot leaves.
$jarQ = jar();
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot4', 'password' => 'pass'], $jarQ);
$q = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'format' => 'twinsuns', 'queueType' => 'bo1',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarQ);
check(!empty($q['success']) && ($q['lobbyID'] ?? '') === $plobby, 'public matchmaking finds the bot-filled room: ' . json_encode([$q['message'] ?? '', $q['lobbyID'] ?? '', $plobby]));
check(count(array_filter(seats($plobby), fn($p) => $p !== '')) === 1 && count(seats($plobby)) === 4, 'and one bot gave up its seat ' . json_encode(seats($plobby)));
}

drop_test_public_rooms();
echo "── start with a bot seat ──\n";
$jarS = jar();
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot3', 'password' => 'pass'], $jarS);
$sh = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jarS);
$slobby = $sh['lobbyID']; $sk = $sh['authKey'];
$sp2 = $join($sh['inviteCode'], jar());
$r = hit($L . 'AddBot.php', ['lobbyID' => $slobby, 'authKey' => $sk, 'botProfile' => 'precon:ts4'], $jarS);
// A removed bot does NOT give its letter back: add Alpha, remove it, and the next bot is Beta.
$alpha = array_key_first(names($slobby));
$k = hit($L . 'KickSeat.php', ['lobbyID' => $slobby, 'authKey' => $sk, 'targetPlayerID' => $alpha], $jarS);
check(!empty($k['success']) && names($slobby) === [], 'the host removes the first bot (Arenabot Alpha): ' . json_encode($k));
$r = hit($L . 'AddBot.php', ['lobbyID' => $slobby, 'authKey' => $sk, 'botProfile' => 'precon:ts4'], $jarS);
check(array_values(names($slobby)) === ['Arenabot Beta'], 'the next bot is Beta, not a reused Alpha: ' . json_encode(names($slobby)));
check(!empty($r['success']), 'start fixture: 2 humans + a pre-con bot');
$st = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $slobby, 'playerID' => intval($sh['playerID'] ?? 1), 'authKey' => $sk], $jarS);
check(!empty($st['success']) && !empty($st['gameName']), 'Start creates the game: ' . ($st['message'] ?? json_encode($st)));
if (!empty($st['gameName'])) {
    $ch = curl_init($B . 'SWUSim/GetNextTurn.php?' . http_build_query(['gameName' => $st['gameName'], 'playerID' => 1, 'authKey' => $sk, 'lastUpdate' => 0]));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEFILE => $jarS]);
    $raw = (string)curl_exec($ch); curl_close($ch);
    $bc = null;
    $at = strpos($raw, '{"enabled"');
    if ($at !== false) { $end = strpos($raw, '<~>', $at); $bc = json_decode($end === false ? substr($raw, $at) : substr($raw, $at, $end - $at), true); }
    check(is_array($bc) && !empty($bc['enabled']) && $bc['players'] === [3], 'the game drives seat 3 with the bot: ' . json_encode($bc));
    // The board names the bot by the name its room gave it (SWUBotSeatDisplayName) — the log and the seat labels read it.
    $ch = curl_init($B . 'NextTurn.php?' . http_build_query(['gameName' => $st['gameName'], 'playerID' => 1, 'folderPath' => 'SWUSim']));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEFILE => $jarS,
                            CURLOPT_HTTPHEADER => ['Cookie: lastAuthKey=' . $sk]]);
    $page = (string)curl_exec($ch); curl_close($ch);
    $names = preg_match('/SWU_SEAT_DISPLAY_NAMES\s*=\s*(\{[^;]*\})/', $page, $m) ? json_decode($m[1], true) : null;
    check(($names['3'] ?? '') === 'Arenabot Beta', 'the game keeps the room\'s name: the seat-3 bot is "Arenabot Beta" on the board: ' . json_encode($names));
}

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
