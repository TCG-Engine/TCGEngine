<?php
// 1v1 Always Two rooms END TO END through the real endpoints — docs/superpowers/specs/2026-11-20-swusim-always-two-design.md (1v1).
// A private 2-seat room where every seat brings TWO decks: the room says so (decksPerSeat), each deck is validated on its own
// (slot 2 through UpdateLobbyDeck's deckSlot), Start waits for every seat's second deck, and the two decks may be identical
// (owner ruling). Control: a Dualing Suns room still takes one deck.
// Uses the Eternal pool: fixtures/premier_deck.txt plays ASH_248, banned in Premier since 2026-11-09.
//
// Runs in the WEB SAPI (APCu):
//   docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/DevTools/tests/alwaystwo_room_flow_test.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: text/plain');
chdir(__DIR__ . '/../../..');
require_once './APIs/Lobbies/Classes/Player.php';
require_once './APIs/Lobbies/Classes/LobbyStore.php';
require_once './SWUSim/Rooms.php';
require_once './SWUSim/Match.php';
// In-process engine, to read the started game's boards (Task 9 — game creation).
include_once './Core/NetworkingLibraries.php';
include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './Core/GameAuth.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/TurnController.php';
include_once './SWUSim/Custom/GameLogic.php';
include_once './SWUSim/Custom/CombatLogic.php';

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
// Clean up on every exit path (absolute paths: Apache runs shutdown functions from another working directory).
$ROOMS = []; $GAMES = []; $MATCHES = []; $JARS = [];
register_shutdown_function(function () {
    global $ROOMS, $GAMES, $MATCHES, $JARS;
    foreach ($ROOMS as [$lobbyID, $invite]) {
        LobbyMutate($lobbyID, fn($l) => 'delete');
        foreach ([SWURoomPath($lobbyID), SWURoomInvitePath($invite)] as $f) if ($f !== '' && is_file($f)) @unlink($f);
    }
    foreach ($MATCHES as $id) {
        $f = SWUMatchPath($id);
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
$jar1 = tempnam(sys_get_temp_dir(), 'a2r'); $JARS[] = $jar1;
$jar2 = tempnam(sys_get_temp_dir(), 'a2r'); $JARS[] = $jar2;
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jar1);
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot2', 'password' => 'pass'], $jar2);
$deck   = trim(file_get_contents(__DIR__ . '/fixtures/premier_deck.txt'));
$tsDeck = trim(file_get_contents(__DIR__ . '/fixtures/twinsuns_deck.json'));   // the one-deck control room's list
// An Eternal-ILLEGAL list for slot 2: four copies of one card (the copy limit is 3).
$badDeck = preg_replace('/^3 ASH_110$/m', '4 ASH_110', $deck);
// Each seat's SECOND deck leads with a different leader, so the boards can be told apart after Start.
$deckB = preg_replace('/^1 ASH_009$/m', '1 SOR_005', $deck);
$parse = function (string $g) { global $gameName, $playerID; $gameName = $g; $playerID = 1; ParseGamestate('./SWUSim/'); };
$leaderOf = fn(int $b) => strval((GetLeader($b)[0] ?? null)->CardID ?? '');
$hasDecision = function (int $b, string $needle): bool {
    foreach (GetDecisionQueue($b) as $d) if (empty($d->removed) && str_contains(strval($d->Param ?? ''), $needle)) return true;
    return false;
};
$answer = function (string $g, int $board, string $key, string $value) use ($B, $jar1) {
    $u = $B . 'ProcessInput.php?' . http_build_query(['gameName' => $g, 'playerID' => $board, 'authKey' => $key, 'folderPath' => 'SWUSim',
        'mode' => 'DECISION', 'decisionIndex' => 0, 'cardID' => $value]);
    $ch = curl_init($u); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEFILE => $jar1]);
    $r = curl_exec($ch); curl_close($ch); return (string)$r;
};
$FMT = 'at1v1-eternal';

echo "CreateWithTwoDecks:\n";
$host = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => $FMT, 'queueType' => 'bo1',
    'deckLink' => $deck, 'deckLink2' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar1);
$check(!empty($host['success']) && !empty($host['isRoom']), 'a private 1v1 Always Two room is created: ' . json_encode($host['message'] ?? $host));
if (empty($host['success'])) { echo "1+ FAILED\n"; exit; }
$lobby = strval($host['lobbyID']); $k1 = strval($host['authKey']); $invite = strval($host['inviteCode'] ?? '');
$ROOMS[] = [$lobby, $invite];
$poll = hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $k1], $jar1);
$check(intval($poll['seatModel']['decksPerSeat'] ?? 0) === 2, 'the room asks for two decks per seat: ' . json_encode($poll['seatModel'] ?? null));
$me = ($poll['players'] ?? $poll['roster'] ?? [])[0] ?? [];
$check(!empty($me['deckOk']) && !empty($me['deckOk2']), 'both decks given at creation are legal (identical decks allowed): ' . json_encode(['ok' => $me['deckOk'] ?? null, 'ok2' => $me['deckOk2'] ?? null]));
$check(!empty($me['ready']), 'a seat with both decks legal is ready');

echo "BestOfOneOnly:\n";
// Game 2 of a Bo3 is respawned from the match's one-deck-per-seat record, so a 1v1 Always Two match is Bo1 only.
$check(($poll['seatModel']['queueTypes'] ?? null) === ['bo1'], 'the room offers Best of 1 only: ' . json_encode($poll['seatModel']['queueTypes'] ?? null));
$qt = hit($L . 'UpdateLobbyQueueType.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'authKey' => $k1, 'queueType' => 'bo3'], $jar1);
$check(empty($qt['success']), 'the host cannot switch it to Best of 3: ' . json_encode($qt['message'] ?? $qt));

echo "SecondDeckValidatedAlone:\n";
$check($badDeck !== $deck, 'fixture: the illegal list differs from the legal one');
$u = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $lobby, 'playerID' => 1, 'authKey' => $k1, 'deckLink' => $badDeck, 'deckSlot' => 2], $jar1);
$check(empty($u['deckOk']), 'an illegal list (4 copies) is refused in slot 2: ' . json_encode($u['message'] ?? $u));
$poll = hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => 0, 'authKey' => $k1], $jar1);
$me = ($poll['players'] ?? $poll['roster'] ?? [])[0] ?? [];
$check(!empty($me['deckOk']) && empty($me['deckOk2']) && empty($me['ready']), 'slot 1 stays legal, slot 2 is not, the seat is not ready');
$u = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $lobby, 'playerID' => 1, 'authKey' => $k1, 'deckLink' => $deckB, 'deckSlot' => 2], $jar1);
$check(!empty($u['deckOk']), 'slot 2 is legal again (a second list, led by SOR_005): ' . json_encode($u['message'] ?? $u));

echo "StartWaitsForEverySecondDeck:\n";
$p2 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'privateInviteCode' => $invite, 'deckLink' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar2);
$check(!empty($p2['success']), 'the second player joins with one deck: ' . json_encode($p2['message'] ?? $p2));
$k2 = strval($p2['authKey'] ?? '');
$st = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => intval($host['playerID'] ?? 1), 'authKey' => $k1], $jar1);
if (!empty($st['gameName'])) $GAMES[] = strval($st['gameName']);   // a wrongly-allowed Start still gets cleaned up
$check(empty($st['success']) && stripos(json_encode($st), 'second deck') !== false, 'Start is blocked until seat 2 brings a second deck: ' . json_encode($st['message'] ?? $st));
$u = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => $lobby, 'playerID' => intval($p2['playerID'] ?? 2), 'authKey' => $k2, 'deckLink' => $deckB, 'deckSlot' => 2], $jar2);
$check(!empty($u['deckOk']), 'seat 2 adds its second deck: ' . json_encode($u['message'] ?? $u));
$st = hit($L . 'StartRoom.php', ['rootName' => 'SWUSim', 'lobbyID' => $lobby, 'playerID' => intval($host['playerID'] ?? 1), 'authKey' => $k1], $jar1);
$gameName = strval($st['gameName'] ?? '');
$check(!empty($st['success']) && $gameName !== '', 'Start creates the game: ' . json_encode($st['message'] ?? $st));
if ($gameName !== '') {
    $GAMES[] = $gameName;
    $ref = json_decode((string)@file_get_contents("./SWUSim/Games/$gameName/MatchRef.json"), true);
    if (is_array($ref) && !empty($ref['matchId'])) $MATCHES[] = strval($ref['matchId']);

    echo "GameHasFourBoards:\n";
    $parse($gameName);
    $check(implode('', GetSeatOrderArray()) === '1234' && implode('', GetLiveSeatsArray()) === '1234', 'four boards, all live');
    $check(SWUIsAlwaysTwo() && SWUAlwaysTwoSharedSeat() && SWUIsTeamGame() && !SWUAlwaysTwoVoteOn(), 'the 1v1 Always Two flags are set');
    $check($leaderOf(1) === 'ASH_009' && $leaderOf(3) === 'SOR_005', "seat 1's decks load to boards 1 and 3: " . $leaderOf(1) . '/' . $leaderOf(3));
    $check($leaderOf(2) === 'ASH_009' && $leaderOf(4) === 'SOR_005', "seat 2's decks load to boards 2 and 4: " . $leaderOf(2) . '/' . $leaderOf(4));
    $keys = SimGameReadAuthKeys('SWUSim', $gameName);
    $check(($keys['p1'] ?? '') !== '' && ($keys['p3'] ?? '') === ($keys['p1'] ?? '') && ($keys['p4'] ?? '') === ($keys['p2'] ?? '')
        && ($keys['p1'] ?? '') !== ($keys['p2'] ?? ''), "each seat's key opens both its boards (p3 = p1, p4 = p2), and only those");

    echo "OpeningChoice:\n";
    $chooser = $hasDecision(1, 'Go_first') ? 1 : ($hasDecision(2, 'Go_first') ? 2 : 0);
    $check($chooser > 0 && !$hasDecision(3, 'Go_first') && !$hasDecision(4, 'Go_first'), "the coin-flip winner is a seat's first board ($chooser)");
    if ($chooser > 0) {
        $key = $chooser === 1 ? $k1 : $k2;
        $resp = $answer($gameName, $chooser, $key, 'Go_first');
        $parse($gameName);
        $check($hasDecision($chooser, 'Board_' . $chooser) && $hasDecision($chooser, 'Board_' . ($chooser + 2)),
            "taking it, the team picks which of its boards holds the initiative (Board_$chooser / Board_" . ($chooser + 2) . '): ' . substr($resp, 0, 200));
        $opts = '';
        foreach (GetDecisionQueue($chooser) as $d) if (empty($d->removed) && str_contains(strval($d->Param ?? ''), 'Board_')) { $opts = strval($d->Param); break; }
        $pick = array_values(array_filter(explode('&', $opts), fn($o) => str_starts_with($o, 'Board_' . ($chooser + 2))))[0] ?? '';
        $answer($gameName, $chooser, $key, $pick);
        $parse($gameName);
        $check(GetInitiativeCounter() === 'P' . ($chooser + 2) . '_UNCLAIMED' && intval(GetFirstPlayer()) === $chooser + 2,
            'board ' . ($chooser + 2) . ' starts with the initiative: ' . GetInitiativeCounter() . ' first=' . GetFirstPlayer());

        echo "SeeingBothBoards:\n";
        // One poll stream per person: seat 1's carries board 3's hidden zones face-up (its own board), seat 2's does not. A visible card
        // carries its JSON ("Location":"Hand","PlayerID":3); a hidden one is a bare CardBack.
        $poll = function (int $seat, string $key) use ($gameName) {
            return (string)shell_exec('curl -s ' . escapeshellarg("http://localhost/TCGEngine/SWUSim/GetNextTurn.php?" . http_build_query(
                ['gameName' => $gameName, 'playerID' => $seat, 'authKey' => $key, 'folderPath' => 'SWUSim', 'lastUpdate' => 0])));
        };
        $v1 = $poll(1, $k1); $v2 = $poll(2, $k2);
        $handOf = fn(string $view, int $b) => preg_match_all('/"Location":"Hand","PlayerID":' . $b . '[,}]/', $view);
        $parse($gameName);
        $n3 = count(GetHand(3));
        $check(count(GetHand(1)) > 0 && count(GetHand(4)) > 0, 'fixture: opening hands are drawn');
        $check($n3 > 0 && $handOf($v1, 3) === $n3, "seat 1 sees board 3's whole hand face-up ($n3 cards; saw " . $handOf($v1, 3) . ')');
        $check($handOf($v1, 1) === count(GetHand(1)), "…and still its own board 1's hand");
        $check($handOf($v2, 3) === 0 && $handOf($v2, 1) === 0, "seat 2 sees neither of seat 1's hands (saw " . $handOf($v2, 3) . '/' . $handOf($v2, 1) . ')');
        $check($handOf($v1, 4) === 0 && $handOf($v1, 2) === 0, "seat 1 sees neither of seat 2's hands");
        $check($handOf($v2, 4) === count(GetHand(4)), "seat 2 sees its board 4's hand");
        // Review Focus 1: board 3's PENDING prompt (its mulligan) reaches seat 1's poll — each board's decision queue rides the same flag.
        $mull = fn(string $view) => substr_count($view, '"mulligan"');
        $check($mull($v1) === 2 && $mull($v2) === 2, "each person's poll carries both of their boards' mulligan prompts (seat 1: " . $mull($v1) . ', seat 2: ' . $mull($v2) . ')');
        // The zone popup (GetPopupContent.php) masks by the same rule.
        $popup = fn(int $seat, string $key, string $type) => (string)shell_exec('curl -s ' . escapeshellarg('http://localhost/TCGEngine/GetPopupContent.php?'
            . http_build_query(['gameName' => $gameName, 'playerID' => $seat, 'authKey' => $key, 'folderPath' => 'SWUSim', 'popupType' => $type])));
        $check($handOf($popup(1, $k1, 'p3Hand'), 3) === $n3, "seat 1's popup of board 3's hand is face-up");
        $check($handOf($popup(2, $k2, 'p3Hand'), 3) === 0, "seat 2's popup of board 3's hand is masked");

        echo "ActingForBoardThree:\n";
        // Seat 1 answers board 3's own prompt (its mulligan) as playerID 3, with seat 1's key — no second login, no reload.
        $check($hasDecision(3, 'mulligan'), 'board 3 has its own mulligan prompt');
        $resp3 = $answer($gameName, 3, $k1, 'NO');
        $parse($gameName);
        $check(!$hasDecision(3, 'mulligan') && $hasDecision(1, 'mulligan'),
            "seat 1's answer as board 3 resolves board 3's prompt and only that one: " . substr($resp3, 0, 160));

        echo "EndOfGame:\n";
        // Seat 1 concedes (input 10006, as the board's own Concede does): its whole team loses. The end screen speaks in SEATS — one
        // person each — and offers no two-player rematch / convert flow (they would respawn a one-deck game).
        $u = $B . 'ProcessInput.php?' . http_build_query(['gameName' => $gameName, 'playerID' => 1, 'authKey' => $k1, 'folderPath' => 'SWUSim', 'mode' => 10006]);
        shell_exec('curl -s ' . escapeshellarg($u));
        $parse($gameName);
        $check(SWUGetGameWinners() === [2, 4], 'the conceding seat\'s team loses: winners ' . json_encode(SWUGetGameWinners()));
        $end = function (int $seat, string $key) use ($B, $gameName) {
            return json_decode((string)shell_exec('curl -s ' . escapeshellarg($B . 'SWUSim/EndGameInfo.php?' . http_build_query(['gameName' => $gameName, 'playerID' => $seat, 'authKey' => $key]))), true);
        };
        $e1 = $end(1, $k1); $e2 = $end(2, $k2);
        $check(is_array($e1) && ($e1['winners'] ?? null) === [2] && empty($e1['didWin']) && !empty($e2['didWin']),
            'the end screen names the winning SEAT (2), not its boards: ' . json_encode(['w' => $e1['winners'] ?? null, 'd1' => $e1['didWin'] ?? null, 'd2' => $e2['didWin'] ?? null]));
        $check(strval($e1['winnerNames']['2'] ?? '') === 'claudebot2', 'the winner reads as its username: ' . json_encode($e1['winnerNames'] ?? null));
        $check(intval($e1['seatCount'] ?? 0) > 2 && empty($e1['convertible']) && empty($e1['origin']),
            'no two-player rematch / convert / requeue flows: ' . json_encode(['seatCount' => $e1['seatCount'] ?? null, 'convertible' => $e1['convertible'] ?? null, 'origin' => $e1['origin'] ?? null]));
    }
}

echo "CreateAsksForBo3:\n";
// A Bo3 request (e.g. the PvP setup's hidden match-type value) must not create a Bo3 Always Two room — the room comes out Bo1.
$b3 = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => $FMT, 'queueType' => 'bo3',
    'deckLink' => $deck, 'deckLink2' => $deck, 'preconstructedDeck' => '', 'game_type' => ''], $jar1);
if (!empty($b3['lobbyID'])) $ROOMS[] = [strval($b3['lobbyID']), strval($b3['inviteCode'] ?? '')];
$b3p = hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => strval($b3['lobbyID'] ?? ''), 'playerID' => 0, 'authKey' => strval($b3['authKey'] ?? '')], $jar1);
$check(!empty($b3['success']) && ($b3p['seatModel']['queueType'] ?? '') === 'bo1', 'a 1v1 Always Two room asked for as Bo3 is created Bo1: ' . json_encode($b3p['seatModel']['queueType'] ?? $b3));

echo "ControlOneDeckRooms:\n";
$ds = hit($L . 'JoinQueue.php', ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'dualingsuns', 'queueType' => 'bo1',
    'deckLink' => $tsDeck, 'preconstructedDeck' => '', 'game_type' => ''], $jar1);
if (!empty($ds['lobbyID'])) $ROOMS[] = [strval($ds['lobbyID']), strval($ds['inviteCode'] ?? '')];
$dpoll = hit($L . 'PollLobbyUpdates.php', ['rootName' => 'SWUSim', 'lobbyID' => strval($ds['lobbyID'] ?? ''), 'playerID' => 0, 'authKey' => strval($ds['authKey'] ?? '')], $jar1);
$check(intval($dpoll['seatModel']['decksPerSeat'] ?? 0) === 1, 'a Dualing Suns room takes one deck per seat');
$check(($dpoll['seatModel']['queueTypes'] ?? null) === ['bo1', 'bo3'], 'control: a Dualing Suns room still offers Bo1 and Bo3: ' . json_encode($dpoll['seatModel']['queueTypes'] ?? null));
$u = hit($L . 'UpdateLobbyDeck.php', ['lobbyID' => strval($ds['lobbyID'] ?? ''), 'playerID' => 1, 'authKey' => strval($ds['authKey'] ?? ''), 'deckLink' => $tsDeck, 'deckSlot' => 2], $jar1);
$check(empty($u['success']), 'a second deck is refused in a one-deck room: ' . json_encode($u['message'] ?? $u));

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
