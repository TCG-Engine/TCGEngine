<?php
// Dualing Suns rooms END TO END through the real endpoints: a private 2-seat room, "Fill Seat with Bot" with a Twin Suns
// pre-con, Start, and the game it makes (pool, bot seat, match format). Plus the refusals: a 1-leader deck, a public queue.
// Spec: docs/superpowers/specs/2026-11-12-swusim-dualing-suns-design.md (Product).
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/DevTools/tests/dualingsuns_room_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/Match.php';
include_once './SWUSim/MatchFlow.php';   // MatchSpawnNextGameWithDecks — game 2 of the match

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$B = 'http://localhost/TCGEngine/';
$L = $B . 'APIs/Lobbies/';
function hit($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 120, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    $r = curl_exec($ch); curl_close($ch);
    $j = json_decode($r, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$r, 0, 600)];
}
// Clean up on every exit path: the rooms (APCu + disk snapshot), the game folders and the match records this run created.
// ⚠ Absolute paths: Apache runs shutdown functions with a different working directory, so './SWUSim/…' cleans nothing.
$ROOMS = []; $GAMES = []; $MATCHES = []; $JARS = [];
register_shutdown_function(function () {
    global $ROOMS, $GAMES, $MATCHES, $JARS;
    foreach ($ROOMS as [$lobbyID, $invite]) {
        LobbyMutate($lobbyID, fn($l) => 'delete');
        foreach ([SWURoomPath($lobbyID), SWURoomInvitePath($invite)] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
    foreach ($MATCHES as $id) {
        $f = SWUMatchPath($id);   // <matches>/<id>/Match.json
        if ($f === '' || !is_file($f)) continue;
        array_map('unlink', glob(dirname($f) . '/*') ?: []);
        @rmdir(dirname($f));
    }
    foreach ($GAMES as $g) {
        $id = preg_replace('/[^0-9]/', '', $g);
        $dir = dirname(__DIR__, 2) . '/Games/' . $id;
        if ($id === '' || !is_dir($dir)) continue;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f)
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($dir);
    }
    foreach ($JARS as $j) @unlink($j);
});
$jar = tempnam(sys_get_temp_dir(), 'dsr'); $JARS[] = $jar;
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jar);
$deck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));

echo "CreatePrivateRoom:\n";
$host = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'dualingsuns', 'queueType' => 'bo1',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar);
$check(!empty($host['success']) && !empty($host['isRoom']), 'a private Dualing Suns room is created: ' . json_encode($host['message'] ?? $host));
if (empty($host['success'])) { echo "1+ FAILED\n"; exit; }
$lobby = strval($host['lobbyID']); $k1 = strval($host['authKey']); $ROOMS[] = [$lobby, strval($host['inviteCode'] ?? '')];

echo "DeckValidation:\n";
$one = json_decode($deck, true); unset($one['secondleader']);
$u = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $lobby, 'playerID' => 1, 'authKey' => $k1, 'deckLink' => json_encode($one)], $jar);
$check(empty($u['deckOk']) && stripos(strval($u['message'] ?? ''), '2 leaders') !== false, 'a 1-leader deck is refused: ' . json_encode($u['message'] ?? $u));
$u = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $lobby, 'playerID' => 1, 'authKey' => $k1, 'deckLink' => $deck], $jar);
$check(!empty($u['deckOk']), 'the Twin Suns deck is legal again: ' . json_encode($u['message'] ?? $u));

echo "FillSeatWithBot:\n";
$poll = hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $k1], $jar);
$profiles = array_keys((array)($poll['botProfiles'] ?? []));
$check(in_array('precon:ts1', $profiles, true) && in_array('custom', $profiles, true), 'the room offers the Twin Suns pre-cons: ' . json_encode($profiles));
$r = hit($L . 'AddBot.php', ['lobbyID' => $lobby, 'authKey' => $k1, 'botProfile' => 'precon:ts1', 'botDeck' => ''], $jar);
$check(!empty($r['success']), 'the host fills the seat with a pre-con bot: ' . json_encode($r['message'] ?? $r));

echo "Start:\n";
$st = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => intval($host['playerID'] ?? 1), 'authKey' => $k1], $jar);
$gameName = strval($st['gameName'] ?? '');
$check(!empty($st['success']) && $gameName !== '', 'Start creates the game: ' . json_encode($st['message'] ?? $st));
if ($gameName !== '') {
    $GAMES[] = $gameName;
    $gs = (string)@file_get_contents("./SWUSim/Games/$gameName/Gamestate.txt");
    $check(str_contains($gs, '"SWUCardPool":"dualingsuns"'), 'the game records the dualingsuns pool');
    $check(str_contains($gs, '"SWUBotProfile_2":'), 'seat 2 is a bot seat');
    $ref = json_decode((string)@file_get_contents("./SWUSim/Games/$gameName/MatchRef.json"), true);
    if (is_array($ref) && !empty($ref['matchId'])) $MATCHES[] = strval($ref['matchId']);
    $m = is_array($ref) ? SWUReadMatch(strval($ref['matchId'] ?? '')) : null;
    $check(is_array($m) && ($m['format'] ?? '') === 'dualingsuns', 'the match record carries the format: ' . json_encode($m['format'] ?? null));
    // Game 2 (a Bo3's next game, a Rematch, Convert to Bo3) is set up from a stand-in lobby that carries no format: the
    // pool must come from the match, or game 2 is plain Premier (final review 2026-11-13, C2).
    if (is_array($m)) {
        $g2 = strval(MatchSpawnNextGameWithDecks('SWUSim', strval($ref['matchId']), 2, $gameName,
            [1 => $m['players']['1']['originalDeck'] ?? [], 2 => $m['players']['2']['originalDeck'] ?? []]));
        if ($g2 !== '') $GAMES[] = $g2;
        $gs2 = (string)@file_get_contents("./SWUSim/Games/$g2/Gamestate.txt");
        $check($g2 !== '' && str_contains($gs2, '"SWUCardPool":"dualingsuns"'), "game 2 ($g2) plays Dualing Suns too");
        $check(str_contains($gs2, '"SWUBotProfile_2":'), 'game 2 keeps the bot seat');
    }
}

// A Best of 3 against a bot: between games the bot never sideboards, so it must be Ready on its own — or the human readies
// and waits forever (final review 2026-11-13, I1). Game 1's result is recorded directly; the sideboard goes through the
// real SubmitSideboard.php, the endpoint the human's sideboard page posts to.
echo "BestOf3AgainstABot:\n";
$h3 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'dualingsuns', 'queueType' => 'bo3',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar);
if (!empty($h3['lobbyID'])) $ROOMS[] = [strval($h3['lobbyID']), strval($h3['inviteCode'] ?? '')];
hit($L . 'AddBot.php', ['lobbyID' => strval($h3['lobbyID'] ?? ''), 'authKey' => strval($h3['authKey'] ?? ''), 'botProfile' => 'precon:ts1', 'botDeck' => ''], $jar);
$st3 = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => strval($h3['lobbyID'] ?? ''), 'playerID' => intval($h3['playerID'] ?? 1),
    'authKey' => strval($h3['authKey'] ?? '')], $jar);
$g31 = strval($st3['gameName'] ?? '');
if ($g31 !== '') $GAMES[] = $g31;
$ref3 = json_decode((string)@file_get_contents("./SWUSim/Games/$g31/MatchRef.json"), true);
$mid3 = strval($ref3['matchId'] ?? '');
if ($mid3 !== '') $MATCHES[] = $mid3;
$m3 = $mid3 !== '' ? SWUReadMatch($mid3) : null;
$check(is_array($m3) && intval($m3['bestOf'] ?? 0) === 3, 'a Best of 3 Dualing Suns match against a bot started: ' . json_encode($st3['message'] ?? $st3));
if (is_array($m3)) {
    MatchRecordGameResult('SWUSim', $mid3, $g31, 2, 3);   // the bot won game 1
    MatchBeginSideboarding('SWUSim', $mid3, 1);
    $humanKey = strval(SWUReadMatch($mid3)['players']['1']['authKey'] ?? '');
    $sb = hit($B . 'SWUSim/SubmitSideboard.php', ['matchId' => $mid3, 'playerID' => 1, 'authKey' => $humanKey, 'action' => 'ready'], $jar);
    $g32 = strval($sb['nextGameName'] ?? '');
    if ($g32 !== '') $GAMES[] = $g32;
    $check($g32 !== '', 'the human readies and game 2 starts at once (the bot was Ready): ' . json_encode($sb));
    $check(str_contains((string)@file_get_contents("./SWUSim/Games/$g32/Gamestate.txt"), '"SWUCardPool":"dualingsuns"'), 'game 2 plays Dualing Suns');
}

echo "NoPublicQueue:\n";
$jar2 = tempnam(sys_get_temp_dir(), 'dsr'); $JARS[] = $jar2;
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot2', 'password' => 'pass'], $jar2);
$pub = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'format' => 'dualingsuns', 'queueType' => 'bo1',
    'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar2);
if (!empty($pub['lobbyID'])) $ROOMS[] = [strval($pub['lobbyID']), ''];
$check(empty($pub['success']), 'a public Dualing Suns queue is refused: ' . json_encode($pub['message'] ?? $pub));

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
