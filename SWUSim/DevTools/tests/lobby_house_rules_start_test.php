<?php
// Twin Suns house rules THROUGH THE REAL START: JoinQueue → UpdateHouseRules → LeaveQueue → SetReady → StartRoom.
// The pure vote rules are lobby_house_rules_test.php; this pins what only the endpoint does:
//   • StartRoom freezes the active set BEFORE it renumbers the seats — with a gap (ids 1,3,4 after seat 2 left) the
//     votes cast by seats 3 and 4 must still count, and land on $lobby->houseRulesActive;
//   • a leave that LIGHTS a rule between the last poll and Start refuses that Start ("House rules changed") and
//     unreadies everyone, instead of starting on rules nobody saw; once they re-ready, Start goes ahead with it.
// docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md
//
// Runs in the WEB SAPI (needs APCu): http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/lobby_house_rules_start_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/Player.php';
require_once __DIR__ . '/../../../APIs/Lobbies/Classes/LobbyStore.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }

$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
function hit($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 300)];
}
$deck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));

// Every room this test makes is deleted when it ends — pass, fail or fatal. They are flipped PUBLIC, so one left
// open captures the next public Twin Suns Join Queue (it did, once: the waiting-room harness landed in it).
$CREATED = [];
register_shutdown_function(function () { global $CREATED; foreach ($CREATED as $id) LobbyMutate($id, fn($l) => 'delete'); });

// A PUBLIC room of four humans. Created private (so the public queue cannot pair it with a stray room) and then
// flipped public in APCu — the vote rules read only $lobby->isPrivate.
function makeRoom() {
    global $L, $deck;
    $seats = [];
    $jar = tempnam(sys_get_temp_dir(), 'hrs');
    hit($GLOBALS['B'] . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jar);
    $h = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'twinsuns',
        'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar);
    if (empty($h['success'])) return null;
    $GLOBALS['CREATED'][] = $h['lobbyID'];
    $seats[1] = ['key' => $h['authKey'], 'jar' => $jar];
    for ($i = 2; $i <= 4; $i++) {
        $j = tempnam(sys_get_temp_dir(), 'hrs');
        $p = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $h['inviteCode'], 'deckLink' => $deck,
            'preconstructedDeck' => '', 'game_type' => ''], $j);
        if (empty($p['success'])) return null;
        $seats[intval($p['playerID'] ?? $i)] = ['key' => $p['authKey'], 'jar' => $j];
    }
    $GLOBALS['CREATED'][] = $h['lobbyID'];
    LobbyMutate($h['lobbyID'], function ($l) { $l->isPrivate = false; return true; });
    return ['id' => $h['lobbyID'], 'seats' => $seats];
}
$vote  = fn($room, $pid, $rule) => hit($L . 'UpdateHouseRules.php', ['lobbyID' => $room['id'], 'authKey' => $room['seats'][$pid]['key'],
    'op' => 'vote', 'rule' => $rule, 'on' => '1'], $room['seats'][$pid]['jar']);
$leave = fn($room, $pid) => hit($L . 'LeaveQueue.php', ['rootName' => 'SWUSim', 'lobbyID' => $room['id'], 'playerID' => $pid,
    'authKey' => $room['seats'][$pid]['key']], $room['seats'][$pid]['jar']);
$readyAll = function ($room) use ($L) {
    foreach ($room['seats'] as $pid => $s) {
        if (!empty($s['gone'])) continue;
        hit($L . 'SetReady.php', ['lobbyID' => $room['id'], 'authKey' => $s['key'], 'ready' => '1'], $s['jar']);
    }
};
$start = fn($room) => hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $room['id'], 'playerID' => 1,
    'authKey' => $room['seats'][1]['key']], $room['seats'][1]['jar']);
$lobbyNow = fn($room) => apcu_fetch($room['id']);

// ── 1. Seat gap: seats 3 and 4 vote, seat 2 leaves, the votes survive Start's renumbering ─────────
echo "seat gap:\n";
$room = makeRoom();
check($room !== null, 'a 4-human room exists');
if ($room === null) { echo "FAIL\n"; exit; }
check(!empty($vote($room, 3, 'planScry')['success']), 'seat 3 votes Plan Scry');
check(!empty($vote($room, 4, 'planScry')['success']), 'seat 4 votes Plan Scry');
check(!empty($vote($room, 1, 'planScry')['success']), 'seat 1 votes Plan Scry (3 of 4: lit)');
$leave($room, 2); $room['seats'][2]['gone'] = true;
$readyAll($room);
$st = $start($room);
check(!empty($st['success']) && !empty($st['gameName']), 'Start succeeds: ' . ($st['message'] ?? json_encode($st)));
$l = $lobbyNow($room);
check(is_object($l) && ($l->houseRulesActive ?? null) === ['planScry' => 2], 'the frozen set is Plan Scry 2 — the gap did not lose the votes');
$ids = is_object($l) ? array_map(fn($p) => $p->getPlayerID(), $l->players) : [];
check($ids === [1, 2, 3], 'seats were renumbered to 1,2,3');
$voteIds = is_object($l) ? array_map('intval', array_keys($l->houseRules['votes']['planScry'] ?? [])) : [];
sort($voteIds);
check($voteIds === [1, 2, 3], 'the three votes moved with their players to the new ids: ' . json_encode($voteIds));

// ── 2. A leave that LIGHTS a rule right before Start refuses that Start ───────────────────────────
echo "leave lights a rule at Start:\n";
$room = makeRoom();
if ($room === null) { echo "FAIL\n"; exit; }
$vote($room, 1, 'extraBaseHp'); $vote($room, 3, 'extraBaseHp');   // 2 of 3 needed among 4 humans: dark
$readyAll($room);
$leave($room, 4); $room['seats'][4]['gone'] = true;               // no poll in between: only StartRoom resolves
$st = $start($room);
check(empty($st['success']) && str_contains(strval($st['message'] ?? ''), 'House rules changed'), 'Start is refused: ' . ($st['message'] ?? json_encode($st)));
$l = $lobbyNow($room);
check(is_object($l) && empty($l->gameName) && ($l->state ?? '') !== 'starting', 'no game was created, the room is still open');
$readies = is_object($l) ? array_map(fn($p) => $p->getReady(), $l->players) : [];
check($readies === [false, false, false], 'everyone was unreadied');
$readyAll($room);
$st = $start($room);
check(!empty($st['success']), 'once they re-ready, Start goes ahead: ' . ($st['message'] ?? ''));
$l = $lobbyNow($room);
check(is_object($l) && ($l->houseRulesActive ?? null) === ['extraBaseHp' => 10], 'with the rule they have now seen');

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
