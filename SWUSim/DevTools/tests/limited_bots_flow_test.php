<?php
// Limited BOTS end to end through the real endpoints (Plan 5, spec docs/superpowers/specs/2026-11-04-swusim-limited-sealed-
// draft-design.md §3.7): bot seats build the moment the pod reaches the build, a human-vs-bot match makes the bot a real
// bot seat in the game (its style from the deck-style classifier), a Bo3 bot readies itself between games and is still a
// bot in game 2, and a pod of 1 human + 7 bots runs its draft and all three Swiss rounds (bot-vs-bot tables are coin
// tosses). Infrastructure only — what the bots pick or build is judged by the bot runs, never pinned here.
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s -m 1500 http://localhost/TCGEngine/SWUSim/DevTools/tests/limited_bots_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
include_once './Core/NetworkingLibraries.php';
include_once './SWUSim/MatchFlow.php';                   // MatchRecordGameResult / MatchAfterActionHook — FILE scope
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/Limited/LimitedRoom.php';
require_once './SWUSim/Limited/LimitedSettings.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$B = 'http://localhost/TCGEngine/';
function hit(string $url, array $params = [], bool $get = false, $jar = null): array {
    $ch = curl_init($get ? $url . '?' . http_build_query($params) : $url);
    $o = [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 300];
    if (!$get) $o += [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params)];
    if ($jar !== null) $o += [CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar];
    curl_setopt_array($ch, $o);
    $raw = (string)curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $j = json_decode($raw, true);
    return (is_array($j) ? $j : ['RAW' => substr($raw, 0, 600)]) + ['_code' => $code];
}
$ROOMS = [];
register_shutdown_function(function () {
    global $ROOMS;
    foreach ($ROOMS as $id) {
        $l = apcu_fetch($id);
        $mids = [];
        if (is_object($l) && is_array($l->limited['match'] ?? null)) $mids[] = [$l->limited['match']['matchId'], $l->limited['match']['gameName']];
        foreach (($l->limited['swiss']['pairings'] ?? []) as $round) foreach ($round as $x) if (empty($x['done']) && $x['matchId'] !== '') $mids[] = [$x['matchId'], $x['gameName']];
        foreach ($mids as [$mid, $g]) { MatchAbortNoResult('SWUSim', $mid); RemoveActiveGame('SWUSim', $g); }
        LobbyMutate($id, fn($l) => 'delete');
        if (($f = SWURoomPath($id)) !== '' && is_file($f)) @unlink($f);
    }
});
function fixture(array $q): array {
    global $B, $ROOMS;
    $r = hit($B . 'SWUSim/DevTools/tests/limited_fixture.php', $q, true);
    if (!empty($r['lobbyID'])) $ROOMS[] = $r['lobbyID'];
    return $r;
}
function act(array $r, int $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params);
}
function build(array $r, int $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedBuilderAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params);
}
function draftAct(array $r, int $who, string $action, array $params = []): array {
    global $B;
    return hit($B . 'SWUSim/LimitedDraftAction.php', ['lobbyID' => $r['lobbyID'], 'authKey' => $r['keys'][$who], 'action' => $action] + $params);
}
function lt(string $id): array { $l = apcu_fetch($id); return is_object($l) ? $l->limited : []; }
function playerOf(string $id, int $pid): ?Player { $l = apcu_fetch($id); foreach (($l->players ?? []) as $p) if ($p instanceof Player && intval($p->getPlayerID()) === $pid) return $p; return null; }
function legalDeck(array $b): array {
    $leader = ''; $main = [];
    foreach ($b['pool'] ?? [] as $c) { $t = CardType($c); if ($t === 'Leader') { if ($leader === '') $leader = $c; continue; } if ($t !== 'Base' && count($main) < 30) $main[] = $c; }
    return ['leader' => $leader, 'base' => strval($b['basesInPlay'][0] ?? ''), 'main' => $main];
}
$T0 = time();
// Every Match.json made for this room (scans only match folders touched since the test began).
function roomMatches(string $lobbyID): array {
    global $T0;
    $out = [];
    foreach (glob(MatchesDir('SWUSim') . '/M*', GLOB_ONLYDIR) ?: [] as $d) {
        if (intval(@filemtime($d)) < $T0 - 5) continue;
        $m = json_decode(strval(@file_get_contents($d . '/Match.json')), true);
        if (is_array($m) && strval($m['roomId'] ?? '') === $lobbyID) $out[] = $m;
    }
    return $out;
}
function gamestate(string $g): string { return strval(@file_get_contents('./SWUSim/Games/' . $g . '/Gamestate.txt')); }
// End game $g of match $mid with $winnerSeat the winner, through the real after-action path (Bo3 → sideboarding).
function endGame(string $g, int $winnerSeat): void {
    $GLOBALS['SWUSTATS_HTTP_FAKE'] = fn($method, $url, $body) => ['status' => 200, 'body' => ['success' => true], 'raw' => '{}'];
    $GLOBALS['gDecisionQueueVariables'] = json_encode(['GAMEOVER_WINNER' => strval($winnerSeat), 'GAMEOVER_WINNERS' => strval($winnerSeat)]);
    MatchAfterActionHook('SWUSim', $g);
    unset($GLOBALS['SWUSTATS_HTTP_FAKE']);
}

echo "BotsBuildAtOnce:\n";
$f = fixture(['mode' => 'draft', 'players' => 3, 'bots' => 2, 'phase' => 'building']);
build($f, 1, 'state');
$lt = lt($f['lobbyID']);
$bots = array_values(array_filter(array_keys($lt['seats'] ?? []), fn($p) => !empty($lt['seats'][$p]['bot'])));
$ok = count($bots) === 2;
foreach ($bots as $b) {
    $p = playerOf($f['lobbyID'], $b);
    $ok = $ok && !empty($lt['seats'][$b]['ready']) && count($lt['seats'][$b]['deck']['main'] ?? []) >= 30
        && $p !== null && preg_match('/^limited-(hyperaggro|softaggro|midrange|softcontrol|hardcontrol)$/', $p->getBotProfile());
}
$check($ok, 'one builder poll later both bots have built and are Ready, each with a limited-<style> profile');

echo "SealedAgainstABot:\n";
$s = fixture(['mode' => 'sealed', 'bots' => 1, 'phase' => 'building', 'botBuild' => 1]);
$check(!empty(lt($s['lobbyID'])['seats'][2]['ready']), 'the Sealed bot is Ready as soon as the packs are opened');
$b1 = build($s, 1, 'state'); build($s, 1, 'submit', ['deck' => json_encode(legalDeck($b1))]); build($s, 1, 'ready');
$lt = lt($s['lobbyID']);
$m = is_array($lt['match'] ?? null) ? SWUReadMatch($lt['match']['matchId']) : null;
$prof = strval($m['players']['2']['botProfile'] ?? '');
$check($m !== null && preg_match('/^limited-[a-z]+$/', $prof) && empty($m['players']['1']['botProfile']), 'the match records seat 2 as the bot: ' . $prof);
$gs = $m ? gamestate($lt['match']['gameName']) : '';
$check($gs !== '' && strpos($gs, 'SWUBotProfile_2') !== false && strpos($gs, 'heuristic-' . substr($prof, 8)) !== false, 'the game drives seat 2 with the bot, in its style (heuristic-' . substr($prof, 8) . ')');

echo "Bo3BotStaysABot:\n";
$t = fixture(['mode' => 'sealed', 'bots' => 1, 'phase' => 'building', 'botBuild' => 1, 'bestOf' => 3]);
$b1 = build($t, 1, 'state'); build($t, 1, 'submit', ['deck' => json_encode(legalDeck($b1))]); build($t, 1, 'ready');
$mt = lt($t['lobbyID'])['match'] ?? null;
if (is_array($mt)) endGame($mt['gameName'], 1);
$m = is_array($mt) ? SWUReadMatch($mt['matchId']) : null;
$check(($m['state'] ?? '') === 'sideboarding', 'game 1 done (host won): the Bo3 is between games: ' . strval($m['state'] ?? ''));
$key1 = strval($m['players']['1']['authKey'] ?? '');
hit($B . 'SWUSim/LimitedBuilderAction.php', ['matchId' => $mt['matchId'] ?? '', 'playerID' => 1, 'authKey' => $key1, 'action' => 'state']);
$m = SWUReadMatch($mt['matchId'] ?? '');
$check(!empty($m['sideboard']['2']['ready']), 'one poll of the host\'s between-games builder: the bot is Ready, keeping its deck');
hit($B . 'SWUSim/LimitedBuilderAction.php', ['matchId' => $mt['matchId'] ?? '', 'playerID' => 1, 'authKey' => $key1, 'action' => 'ready']);
$m = SWUReadMatch($mt['matchId'] ?? '');
$games = is_array($m['games'] ?? null) ? $m['games'] : [];
$g2 = $games ? strval(end($games)['gameName'] ?? '') : '';
$check(count($m['games'] ?? []) === 2 && strpos(gamestate($g2), 'SWUBotProfile_2') !== false, 'game 2 exists and seat 2 is still driven by the bot');

echo "OneHumanSevenBots:\n";
$o = fixture(['mode' => 'draft', 'players' => 8, 'bots' => 7, 'phase' => 'drafting', 'pickSeconds' => 300]);
for ($g = 0; $g < 400 && (lt($o['lobbyID'])['phase'] ?? '') === 'drafting'; $g++) {
    $st = draftAct($o, 1, 'state');
    if (!empty($st['front'])) draftAct($o, 1, 'pick', ['packId' => $st['front']['packId'], 'cardId' => $st['front']['cards'][0]]);
}
$check((lt($o['lobbyID'])['phase'] ?? '') === 'building', 'the human drafts at their own pace and the 7 bots keep up: the draft ends');
$b1 = build($o, 1, 'state'); build($o, 1, 'submit', ['deck' => json_encode(legalDeck($b1))]); build($o, 1, 'ready');
$lt = lt($o['lobbyID']);
$r1 = $lt['swiss']['pairings'][1] ?? [];
$withMatch = array_filter($r1, fn($x) => $x['matchId'] !== '');
$tossed = array_filter($r1, fn($x) => !empty($x['coinToss']));
$check(($lt['phase'] ?? '') === 'playing' && count($r1) === 4 && count($withMatch) === 1 && count($tossed) === 3,
       'round 1: one real match (the human\'s) and three coin-tossed bot tables: ' . json_encode(['phase' => $lt['phase'] ?? '', 'tables' => count($r1)]));
for ($round = 1; $round <= 3; $round++) {
    $lt = lt($o['lobbyID']);
    foreach (($lt['swiss']['pairings'][$round] ?? []) as $x) if ($x['matchId'] !== '' && empty($x['done'])) {
        MatchRecordGameResult('SWUSim', $x['matchId'], $x['gameName'], 1, 1);
        MatchHook('SWUSim', 'gameResultRecorded', SWUReadMatch($x['matchId']), $x['gameName']);
    }
    if ($round < 3) { act($o, 1, 'ready'); act($o, 1, 'startRound'); }
}
$lt = lt($o['lobbyID']);
$check(($lt['phase'] ?? '') === 'done' && count(LTSwissStandings($lt)) === 8, 'three rounds later the event is done, with 8 in the standings: ' . strval($lt['phase'] ?? ''));
$made = count(roomMatches($o['lobbyID']));
$check($made === 3, "only the human's tables became matches — one a round, never a bot-vs-bot one ($made made)");

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
