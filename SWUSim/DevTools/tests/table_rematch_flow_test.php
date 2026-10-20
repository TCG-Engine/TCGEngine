<?php
// Twin Suns rematch END TO END through the real endpoints: TableRematchVote.php, EndGameInfo.php, AnnounceLeave.php.
// The vote rules themselves are table_rematch_votes_test.php; this pins what only the endpoints do — the fire-once
// claim, the Quick spawn (same keys / decks / house rules / format / bots), the Rematch room build, the per-seat
// result, and a leave completing the vote with nobody clicking.
// Spec: docs/superpowers/specs/2026-10-20-twinsuns-rematch-design.md
//
// Runs in the WEB SAPI (APCu): http://localhost:3400/TCGEngine/SWUSim/DevTools/tests/table_rematch_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './SWUSim/MatchFlow.php';
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/TableRematch.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; if ($cond) { echo "  ok: $msg\n"; } else { echo "  BAD: $msg\n"; $FAILS++; } }
$B = 'http://localhost/TCGEngine/';
function post($url, $params) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 400)];
}
function getj($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 400)];
}

$jd = json_decode(file_get_contents('./SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'), true);
$lines = ['Leader', '1 ' . $jd['leader']['id'], '1 ' . $jd['secondleader']['id'], 'Base', '1 ' . $jd['base']['id'], 'Deck'];
foreach ($jd['deck'] as $c) $lines[] = $c['count'] . ' ' . $c['id'];
$DECK = implode("\n", $lines);

$LOBBIES = [];
register_shutdown_function(function () { global $LOBBIES; foreach ($LOBBIES as $id) LobbyMutate($id, fn($l) => 'delete'); });

// A finished table: seats 1-3 humans (1 and 3 with accounts' names, 2 a guest), seat 4 a bot. Private, Plan Scry 2.
function finishedTable($format = 'twinsuns', $withBot = true) {
    global $DECK;
    $lobby = new stdClass();
    $lobby->isPrivate = true; $lobby->format = $format; $lobby->queueType = 'bo1'; $lobby->hostPlayerID = 1;
    $lobby->houseRulesActive = ['planScry' => 2];
    $lobby->players = [];
    foreach ([1, 2, 3, 4] as $s) {
        $p = new Player($s, $DECK, '', null);
        if ($s === 1) $p->setUsername('claudebot1');
        if ($s === 3) $p->setUsername('claudebot3');
        if ($s === 4 && $withBot) { $p->setBotProfile('precon:x'); $p->setUsername('Arenabot Alpha'); }
        if ($format === 'teamsuns') { $p->setSeat($s); $p->setTeam($s % 2 === 1 ? 'red' : 'blue'); }
        $lobby->players[] = $p;
    }
    $mid = SWUCreateMatchFromLobby($lobby);
    $m = SWUReadMatch($mid);
    $g = $m['games'][0]['gameName'];
    MatchRecordGameResult('SWUSim', $mid, $g, 1, 5, [1]);
    $keys = [];
    foreach (SWUReadMatch($mid)['players'] as $s => $p) $keys[intval($s)] = $p['authKey'];
    return ['matchId' => $mid, 'game' => strval($g), 'keys' => $keys];
}
$vote = fn($t, $seat, $choice) => post($GLOBALS['B'] . 'SWUSim/TableRematchVote.php',
    ['gameName' => $t['game'], 'playerID' => $seat, 'authKey' => $t['keys'][$seat], 'choice' => $choice]);
$info = fn($t, $seat) => getj($GLOBALS['B'] . 'SWUSim/EndGameInfo.php?' . http_build_query(
    ['gameName' => $t['game'], 'playerID' => $seat, 'authKey' => $t['keys'][$seat]]));
$leave = fn($t, $seat) => post($GLOBALS['B'] . 'SWUSim/AnnounceLeave.php',
    ['gameName' => $t['game'], 'playerID' => $seat, 'authKey' => $t['keys'][$seat]]);
$gamestate = fn($g) => @file_get_contents('./SWUSim/Games/' . $g . '/Gamestate.txt') ?: '';

// ── 1. Quick Rematch ──────────────────────────────────────────────────────────────────────────
echo "quick:\n";
$t = finishedTable();
$i = $info($t, 1);
check(isset($i['tableRematch']['quick']) && $i['tableRematch']['quick']['needed'] === [1, 2, 3], 'EndGameInfo offers the table vote; voters are the 3 humans: ' . json_encode($i['tableRematch'] ?? $i));
$vote($t, 1, 'quick'); $vote($t, 2, 'quick');
$r = $vote($t, 3, 'quick');
$res = $r['tableRematch']['result'] ?? null;
check(($res['kind'] ?? '') === 'quick' && ($res['gameName'] ?? '') !== '', 'the third vote starts the Quick Rematch: ' . json_encode($r));
$new = strval($res['gameName'] ?? '');
check(is_file('./SWUSim/Games/' . $t['game'] . '/NextGame.json'), 'the old game points at the new one (spectators follow too)');
$ref = SWUReadMatchRef($new);
$nm = $ref ? SWUReadMatch($ref['matchId']) : null;
check(is_array($nm) && $nm['matchId'] !== $t['matchId'], 'a new match');
$sameKeys = is_array($nm);
foreach ([1, 2, 3, 4] as $s) $sameKeys = $sameKeys && ($nm['players'][strval($s)]['authKey'] ?? '') === $t['keys'][$s];
check($sameKeys, 'every seat keeps its key (the browser already holds it)');
check(($nm['houseRules'] ?? null) === ['planScry' => 2] && ($nm['format'] ?? '') === 'twinsuns', 'same house rules and format');
check(($nm['players']['4']['botProfile'] ?? '') === 'precon:x', 'the bot seat is still a bot on the new match');
$gs = $gamestate($new);
check(str_contains($gs, 'SWUBotName_4') && str_contains($gs, 'Arenabot Alpha'), 'the new game drives seat 4 as the same bot');
check(str_contains($gs, '"HR_PLANSCRY":"2"'), 'the new game has the house rules');
check($new !== '' && ($info($t, 2)['tableRematch']['result']['gameName'] ?? '') === $new, 'every seat is told the new game');

echo "team suns quick:\n";
$tt = finishedTable('teamsuns', false);
foreach ([1, 2, 3] as $s) $vote($tt, $s, 'quick');
$r = $vote($tt, 4, 'quick');
$tg = strval($r['tableRematch']['result']['gameName'] ?? '');
check($tg !== '' && str_contains($gamestate($tg), 'SWU_MODE_TEAMS'), 'a Team Suns quick rematch is still a team game');

echo "fire once:\n";
$t = finishedTable();
$vote($t, 1, 'quick');
$before = count(glob('./SWUSim/Matches/M*') ?: []);
$mh = curl_multi_init(); $hs = [];
foreach ([2, 3] as $s) {
    $h = curl_init($B . 'SWUSim/TableRematchVote.php');
    curl_setopt_array($h, [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120,
        CURLOPT_POSTFIELDS => http_build_query(['gameName' => $t['game'], 'playerID' => $s, 'authKey' => $t['keys'][$s], 'choice' => 'quick'])]);
    curl_multi_add_handle($mh, $h); $hs[] = $h;
}
do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
$names = [];
foreach ($hs as $h) { $j = json_decode(curl_multi_getcontent($h), true); $names[] = strval($j['tableRematch']['result']['gameName'] ?? ''); curl_multi_remove_handle($mh, $h); }
$after = count(glob('./SWUSim/Matches/M*') ?: []);
$final = strval($info($t, 1)['tableRematch']['result']['gameName'] ?? '');
check($final !== '' && $after - $before === 1, 'two seats completing the vote at once create exactly ONE new match (' . ($after - $before) . ')');
check(!in_array('', $names, true) ? ($names[0] === $names[1] && $names[0] === $final) : in_array($final, $names, true),
      'both requests agree on the one new game: ' . json_encode($names) . ' final=' . $final);

// ── 2. Rematch room with a leaver ─────────────────────────────────────────────────────────────
echo "room with a leaver:\n";
$t = finishedTable();
$leave($t, 2);
$i = $info($t, 1);
check(($i['tableRematch']['quick']['blocked'] ?? false) === true && ($i['tableRematch']['left'] ?? []) === [2], 'seat 2 left: Quick is blocked');
$vote($t, 1, 'room');
$r = $vote($t, 3, 'room');
$res = $r['tableRematch']['result'] ?? [];
check(($res['kind'] ?? '') === 'room' && ($res['lobbyID'] ?? '') !== '' && ($res['authKey'] ?? '') !== '', 'Rematch opens a room and hands seat 3 its key: ' . json_encode($r));
$lid = strval($res['lobbyID'] ?? '');
$LOBBIES[] = $lid;
$l = apcu_fetch($lid);
check(is_object($l), 'the room exists');
if (is_object($l)) {
    $byName = []; foreach ($l->players as $p) $byName[$p->getUsername()] = $p;
    check(count($l->players) === 3 && isset($byName['claudebot1'], $byName['claudebot3'], $byName['Arenabot Alpha']), 'seated: seats 1, 3 and the bot — not the leaver');
    $names = array_map(fn($p) => $p->getUsername(), $l->players);
    check($names === ['claudebot1', 'claudebot3', 'Arenabot Alpha'], 'in the old seat order: ' . json_encode($names));
    check(!$byName['claudebot1']->getReady() && !$byName['claudebot3']->getReady(), 'humans are NOT ready');
    check($byName['claudebot1']->getDeckOk() && $byName['claudebot3']->getDeckOk(), "humans' last decks are loaded");
    check($byName['Arenabot Alpha']->getReady() && $byName['Arenabot Alpha']->getBotProfile() === 'precon:x', 'the bot is seated, READY, still a bot');
    check(intval($l->hostPlayerID) === intval($byName['claudebot1']->getPlayerID()), 'the old host is host again');
    check(!empty($l->isPrivate) && ($l->format ?? '') === 'twinsuns' && ($l->inviteCode ?? '') !== '', 'private twinsuns room with an invite code');
    check(apcu_fetch('invite:' . $l->inviteCode) === $lid, 'the new invite code resolves to the room');
    require_once './APIs/Lobbies/Classes/HouseRules.php';
    require_once './SWUSim/HouseRuleDefs.php';
    check(LobbyHouseRulesActive($l, SWUHouseRuleDefs()) === ['planScry' => 2], 'Plan Scry 2 is lit in the new room');
    $k1 = strval($info($t, 1)['tableRematch']['result']['authKey'] ?? '');
    check($k1 !== '' && $k1 === $byName['claudebot1']->getAuthKey(), "seat 1 is handed its OWN room seat's key");
    check(($res['authKey'] ?? '') === $byName['claudebot3']->getAuthKey(), "seat 3's key is its own seat's");
    $i2 = $info($t, 2)['tableRematch']['result'] ?? [];
    check(!isset($i2['authKey']) && ($i2['joinInvite'] ?? '') === $l->inviteCode, 'the leaver gets the invite, never a key');
}

echo "a leave completes the vote:\n";
$t = finishedTable();
$vote($t, 1, 'room'); $vote($t, 3, 'room');
check(empty($info($t, 1)['tableRematch']['result']), 'seat 2 still at the table, silent: nothing yet');
$leave($t, 2);
$res = $info($t, 3)['tableRematch']['result'] ?? [];
check(($res['kind'] ?? '') === 'room', 'seat 2 leaves: the next poll opens the room with nobody clicking');
if (!empty($res['lobbyID'])) $LOBBIES[] = $res['lobbyID'];

echo "removed for inactivity:\n";
$t = finishedTable();
SWUTableRematchMarkLeftForGame($t['game'], 3, 'kicked');   // what SWUApplyKick calls
check(($info($t, 1)['tableRematch']['quick']['blocked'] ?? false) === true, 'a kicked seat blocks Quick');

// ── Final-review fixes (2026-10-20) ───────────────────────────────────────────────────────────
echo "review: a spectator never learns a private room's invite:\n";
$t = finishedTable();
$leave($t, 2); $vote($t, 1, 'room'); $r = $vote($t, 3, 'room');
if (!empty($r['tableRematch']['result']['lobbyID'])) $LOBBIES[] = $r['tableRematch']['result']['lobbyID'];
$spec = getj($B . 'SWUSim/EndGameInfo.php?' . http_build_query(['gameName' => $t['game'], 'playerID' => 'S', 'authKey' => '']));
$sr = $spec['tableRematch']['result'] ?? [];
check(empty($sr['joinInvite']) && empty($sr['authKey']) && empty($sr['lobbyID']), 'a spectator (no auth) gets no invite, key or room id: ' . json_encode($sr));

echo "review: losing the heartbeat store does not fire a room early:\n";
$t = finishedTable();
$vote($t, 1, 'room');
MatchWithLock('SWUSim', $t['matchId'], function (&$m) { $m['tableRematch']['since'] = time() - 600; });   // the vote opened 10 min ago
apcu_delete('swu_tr_seen_' . $t['matchId']);                                                          // server restart / expunge
$i = $info($t, 1);
check(empty($i['tableRematch']['result']) && ($i['tableRematch']['left'] ?? null) === [], 'after a wipe nobody is quiet yet, so nothing fires: ' . json_encode($i['tableRematch'] ?? null));

echo "review: a stale claim's late result cannot overwrite the real one:\n";
$t = finishedTable();
foreach ([1, 2, 3] as $s) $vote($t, $s, 'quick');
$real = SWUReadMatch($t['matchId'])['tableRematch'];
$claimAt = intval($real['claim']['at'] ?? 0);
$stored = _SWUTableRematchStoreResult($t['matchId'], $claimAt - 100, ['kind' => 'quick', 'gameName' => 'IMPOSTOR']);
$after = SWUReadMatch($t['matchId'])['tableRematch'];
check($stored === false && ($after['result']['gameName'] ?? '') === ($real['result']['gameName'] ?? 'x'), 'an older claimer cannot store its result over the winner\'s');

echo "review: an idle end-screen poll writes nothing:\n";
$t = finishedTable();
$info($t, 1);
$u1 = SWUReadMatch($t['matchId'])['updatedAt'] ?? 0;
sleep(2);
$info($t, 1); $info($t, 2);
$u2 = SWUReadMatch($t['matchId'])['updatedAt'] ?? 0;
check($u1 === $u2, "polls with nothing due leave the match record untouched ($u1 vs $u2)");

echo $FAILS === 0 ? "PASS\n" : "$FAILS FAILED\n";
