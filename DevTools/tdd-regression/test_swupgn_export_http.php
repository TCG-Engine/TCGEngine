<?php
// SWU-PGN export, end to end through the real endpoints: a PvP game (claudebot1 vs an anonymous guest, paired by the
// Open Bo1 queue) played to a concession; each seat downloads ITS OWN view, and nothing else is reachable.
// RUN VIA CLI (it calls Apache inside the container; logs in as claudebot1):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_export_http.php
error_reporting(E_ALL & ~E_DEPRECATED);
chdir('/var/www/html/TCGEngine');
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
$B = 'http://localhost/TCGEngine/';
function sxe_req(string $url, string $jar, ?array $post = null, ?int &$status = null): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = curl_exec($ch); $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE)); curl_close($ch); return (string)$r;
}
$host = tempnam(sys_get_temp_dir(), 'sxh'); $guest = tempnam(sys_get_temp_dir(), 'sxg');
sxe_req($B . 'AccountFiles/AttemptPasswordLogin.php', $host, ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass']);
$deck = trim(implode("\n", array_filter(explode("\n", file_get_contents('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/darth-vader_jtl_yellow.txt')), fn($l) => !str_starts_with($l, '#'))));
$join = fn(string $jar) => json_decode(sxe_req($B . 'APIs/Lobbies/JoinQueue.php', $jar, ['rootName' => 'SWUSim', 'format' => 'open', 'queueType' => 'bo1', 'deckLink' => $deck]), true);
$h = $join($host);
$g = $join($guest);
SwuPgnTestCheck(!empty($g['gameName']), 'the guest pairs with claudebot1 into a game', json_encode($g));
if (empty($g['gameName'])) { SwuPgnTestFinish(); }
$hp = json_decode(sxe_req($B . 'APIs/Lobbies/PollLobbyUpdates.php', $host, ['rootName' => 'SWUSim', 'playerID' => $h['playerID'] ?? 0, 'lobbyID' => $h['lobbyID'] ?? '', 'authKey' => $h['authKey'] ?? '']), true);
$game = (string)$g['gameName'];
$seat = ['host' => intval($hp['playerID'] ?? 0), 'guest' => intval($g['playerID'] ?? 0)];
$key = ['host' => (string)($hp['authKey'] ?? $h['authKey'] ?? ''), 'guest' => (string)($g['authKey'] ?? '')];   // the poll does not repeat the host's key
SwuPgnTestCheck(strval($hp['gameName'] ?? '') === $game && $seat['host'] + $seat['guest'] === 3 && $key['host'] !== '', 'both seats are in the same game', json_encode([$hp, $g]));
SwuPgnTestCheck(is_dir("./SWUSim/Games/$game/Pgn"), 'the PvP game is recording');
$export = function (string $jar, $playerID, string $authKey, ?int &$status = null) use ($B, $game) {
    return json_decode(sxe_req($B . 'SWUSim/SwuPgnExport.php?' . http_build_query(['gameName' => $game, 'playerID' => $playerID, 'authKey' => $authKey]), $jar, null, $status), true);
};

$r = $export($host, $seat['host'], $key['host'], $st);
SwuPgnTestCheck($st === 404 && ($r['success'] ?? null) === false && ($r['message'] ?? '') === 'Replay file unavailable.', 'an unfinished game: 404 "Replay file unavailable."', "$st " . json_encode($r));

// The guest concedes.
$c = json_decode(sxe_req($B . 'ProcessInput.php?' . http_build_query(['gameName' => $game, 'playerID' => $seat['guest'], 'authKey' => $key['guest'],
    'folderPath' => 'SWUSim', 'mode' => 10006, 'responseFormat' => 'json']), $guest), true);
SwuPgnTestCheck(!empty($c['success']), 'the guest concedes', json_encode($c));

foreach (['host', 'guest'] as $who) {
    $r = $export($who === 'host' ? $host : $guest, $seat[$who], $key[$who], $st);
    SwuPgnTestCheck($st === 200 && !empty($r['success']) && is_string($r['pgn'] ?? null), "$who downloads its file", "$st " . substr(json_encode($r), 0, 300));
    if (empty($r['pgn'])) continue;
    $doc = SwuPgnParse($r['pgn']);
    SwuPgnTestEq($doc['headers']['Perspective'] ?? null, 'P' . $seat[$who], "$who gets Perspective P{$seat[$who]}");
    SwuPgnTestEq($doc['headers']['Seed'] ?? null, 'unseeded', "$who's file is unseeded");
    SwuPgnTestCheck(preg_match('/^petranaki_[a-z0-9-]+_vs_[a-z0-9-]+_\d{4}-\d\d-\d\d\.swupgn$/', strval($r['filename'] ?? '')) === 1, "$who's filename", strval($r['filename'] ?? ''));
    SwuPgnTestEq($r['summary']['perspective'] ?? null, 'P' . $seat[$who], "$who's summary names the perspective");
    SwuPgnTestEq($r['summary']['result'] ?? null, 'P' . $seat['host'], "$who's summary: the host won");
}

// Only a seat. (The dev container skips seat-KEY checks on purpose — DEVENV — so "your seat, your key" is pinned with
// real keys and the bypass off in test_swupgn_export.php.)
$r = $export($host, 'S', $key['host'], $st);
SwuPgnTestCheck($st === 403 && empty($r['success']), 'a spectator: 403', "$st " . json_encode($r));
foreach (['all', '0', '../1', '3'] as $bad) {
    $r = $export($host, $bad, $key['host'], $st);
    SwuPgnTestCheck($st === 403 && empty($r['pgn']), "playerID=$bad cannot fetch anything", "$st");
}
$all = file_get_contents("./SWUSim/Games/$game/Pgn/out/all.swupgn");
$mine = $export($host, $seat['host'], $key['host'])['pgn'] ?? '';
SwuPgnTestCheck($mine !== '' && $mine !== $all && !str_contains($mine, SwuPgnParse($all)['headers']['Seed']), 'the download is never the all-seeing file');

@unlink($host); @unlink($guest);
SwuPgnTestFinish();
