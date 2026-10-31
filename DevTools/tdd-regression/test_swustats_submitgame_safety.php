<?php
// RUN VIA CLI (loopback HTTP POSTs to SubmitGameResult.php):
//   docker exec otmtcge-swudeck-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swustats_submitgame_safety.php
//
// Two SubmitGameResult guards:
//  - p1id/p2id reach completedgame as bound integers. A non-numeric pid is ignored (the game is still
//    recorded); it is never spliced into the SQL.
//  - A request refused for a missing winHero/loseHero leaves NOTHING behind — the 400 used to arrive
//    after both players' deck stats were already written.
// Plus guards that the refusal does not change what each player's write sees: a private deck still
// skips the completedgame row (so it is not refused), and $disableMetaStats still accumulates in seat
// order — player 1's privacy reaches player 2's write, player 2's does not reach player 1's.
//
// The local stats DB is a copy of real data: every identifier here is a sentinel (token leaders with
// no rows, deckIDs 9999000xx) and everything the test can create is deleted at the end.
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../APIKeys/APIKeys.php';

$endpoint = 'http://localhost/TCGEngine/APIs/SubmitGameResult.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$apiKey = isset($petranakiAPIKey) ? $petranakiAPIKey : (isset($karabastAPIKey) ? $karabastAPIKey : '');

$L1 = 'SHD_T01'; $L2 = 'SHD_T02';      // player 1 / player 2 leaders, also winHero / loseHero
$D1 = 999900081; $D2 = 999900082;      // player 1 / player 2 deckIDs
$PUBLIC = 2000000; $PRIVATE = 5000;    // ownership.assetVisibility

function post($url, $d) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($d)]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}
function payload($extra = []) {
    global $apiKey, $L1, $L2;
    return array_merge([
        'apiKey' => $apiKey, 'winner' => 1, 'firstPlayer' => 1, 'round' => 3, 'winnerHealth' => 10,
        'gameName' => 'zztest_safety', 'winHero' => $L1, 'loseHero' => $L2, 'format' => 'premier',
        'player1' => json_encode(['leader' => $L1, 'base' => 'Green', 'cardResults' => [], 'turnResults' => []]),
        'player2' => json_encode(['leader' => $L2, 'base' => 'Red', 'cardResults' => [], 'turnResults' => []]),
    ], $extra);
}
function deckLink($id) { return "http://localhost/TCGEngine/?gameName=$id"; }
function own($conn, $deckID, $visibility) {
    $conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $deckID");
    $conn->query("INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus, assetVisibility) VALUES (1, $deckID, 999999999, 1, $visibility)");
}
function maxGid($conn) { return intval($conn->query("SELECT MAX(GameID) m FROM completedgame")->fetch_assoc()['m']); }
// The row this test just created (GameID beyond $since, sentinel winner), or null. PK range scan.
function newGame($conn, $since) {
    global $L1;
    return $conn->query("SELECT GameID, WinningPID, LosingPID FROM completedgame WHERE GameID > " . intval($since)
        . " AND WinningHero = '$L1' ORDER BY GameID DESC LIMIT 1")->fetch_assoc();
}
function deckRows($conn, $deckID) { return intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID")->fetch_assoc()['c']); }
function metaRows($conn, $leader) { return intval($conn->query("SELECT COUNT(*) c FROM deckmetastats WHERE leaderID = '$leader'")->fetch_assoc()['c']); }
function wipe($conn) {
    global $L1, $L2, $D1, $D2;
    foreach ([$D1, $D2] as $d) {
        foreach (['deckstats', 'carddeckstats', 'opponentdeckstats', 'opponentnamedbasestats'] as $t)
            $conn->query("DELETE FROM $t WHERE deckID = $d");
        $conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $d");
    }
    $conn->query("DELETE FROM deckmetastats WHERE leaderID IN ('$L1', '$L2')");
    $conn->query("DELETE FROM deckmetamatchupstats WHERE leaderID IN ('$L1', '$L2') OR opponentLeaderID IN ('$L1', '$L2')");
    $conn->query("DELETE FROM completedgame WHERE WinningHero IN ('$L1', '$L2') OR LosingHero IN ('$L1', '$L2')");
}

wipe($conn);

// --- player ids -------------------------------------------------------------------------------------
// Numeric pids (one a string, one a JSON number) land in WinningPID / LosingPID, as they always have.
$since = maxGid($conn);
[$code] = post($endpoint, payload(['p1id' => '777001', 'p2id' => 777002]));
$g = newGame($conn, $since);
$checks['numeric pids: 200'] = $code === 200;
$checks['numeric pids: winner pid stored'] = $g && intval($g['WinningPID']) === 777001;
$checks['numeric pids: loser pid stored'] = $g && intval($g['LosingPID']) === 777002;
wipe($conn);

// A pid carrying SQL is not executed: the subquery would otherwise store 424242.
$since = maxGid($conn);
[$code, $body] = post($endpoint, payload(['p1id' => '(SELECT 424242)']));
$g = newGame($conn, $since);
$checks['sql pid: 200 success'] = $code === 200 && strpos((string)$body, '"success":true') !== false;
$checks['sql pid: game recorded'] = $g !== null;
$checks['sql pid: not executed, pid dropped'] = $g && $g['WinningPID'] === null;
wipe($conn);

// A plain non-numeric pid (the documented "player1_id" placeholder) no longer loses the game.
$since = maxGid($conn);
[$code] = post($endpoint, payload(['p1id' => 'player1_id', 'p2id' => '777002']));
$g = newGame($conn, $since);
$checks['text pid: 200'] = $code === 200;
$checks['text pid: game recorded'] = $g !== null;
$checks['text pid: dropped, other pid kept'] = $g && $g['WinningPID'] === null && intval($g['LosingPID']) === 777002;
wipe($conn);

// --- missing hero: refused before anything is written -----------------------------------------------
own($conn, $D1, $PUBLIC); own($conn, $D2, $PUBLIC);
$since = maxGid($conn);
$p = payload(['p1DeckLink' => deckLink($D1), 'p2DeckLink' => deckLink($D2)]);
unset($p['winHero']);
[$code, $body] = post($endpoint, $p);
$checks['no winHero: 400'] = $code === 400 && strpos((string)$body, 'Missing winHero or loseHero') !== false;
$checks['no winHero: p1 deck stats untouched'] = deckRows($conn, $D1) === 0;
$checks['no winHero: p2 deck stats untouched'] = deckRows($conn, $D2) === 0;
$checks['no winHero: no meta rows'] = metaRows($conn, $L1) === 0 && metaRows($conn, $L2) === 0;
$checks['no winHero: no game row'] = newGame($conn, $since) === null;
wipe($conn);

// loseHero: null is refused the same way.
own($conn, $D1, $PUBLIC);
[$code] = post($endpoint, payload(['p1DeckLink' => deckLink($D1), 'loseHero' => null]));
$checks['null loseHero: 400'] = $code === 400;
$checks['null loseHero: deck stats untouched'] = deckRows($conn, $D1) === 0;
wipe($conn);

// Control: the same request WITH its heroes writes both decks (so the "untouched" checks can fail).
own($conn, $D1, $PUBLIC); own($conn, $D2, $PUBLIC);
[$code] = post($endpoint, payload(['p1DeckLink' => deckLink($D1), 'p2DeckLink' => deckLink($D2)]));
$checks['control: 200'] = $code === 200;
$checks['control: both decks written'] = deckRows($conn, $D1) === 1 && deckRows($conn, $D2) === 1;
$checks['control: both meta rows'] = metaRows($conn, $L1) === 1 && metaRows($conn, $L2) === 1;
wipe($conn);

// A private deck records no game row, so a missing hero is not a refusal there: the deck stats are
// written and the caller gets 200, exactly as before.
own($conn, $D1, $PRIVATE);
$p = payload(['p1DeckLink' => deckLink($D1)]);
unset($p['winHero']);
[$code] = post($endpoint, $p);
$checks['private + no winHero: 200'] = $code === 200;
$checks['private + no winHero: deck stats written'] = deckRows($conn, $D1) === 1;
wipe($conn);

// --- meta flag order --------------------------------------------------------------------------------
// Player 2's private deck does not reach player 1's (earlier) write: player 1 still gets meta rows.
own($conn, $D1, $PUBLIC); own($conn, $D2, $PRIVATE);
post($endpoint, payload(['p1DeckLink' => deckLink($D1), 'p2DeckLink' => deckLink($D2)]));
$checks['p2 private: p1 meta written'] = metaRows($conn, $L1) === 1;
$checks['p2 private: p2 meta skipped'] = metaRows($conn, $L2) === 0;
$checks['p2 private: both decks written'] = deckRows($conn, $D1) === 1 && deckRows($conn, $D2) === 1;
wipe($conn);

// Player 1's private deck reaches player 2's write: neither gets meta rows.
own($conn, $D1, $PRIVATE); own($conn, $D2, $PUBLIC);
post($endpoint, payload(['p1DeckLink' => deckLink($D1), 'p2DeckLink' => deckLink($D2)]));
$checks['p1 private: p1 meta skipped'] = metaRows($conn, $L1) === 0;
$checks['p1 private: p2 meta skipped'] = metaRows($conn, $L2) === 0;
$checks['p1 private: both decks written'] = deckRows($conn, $D1) === 1 && deckRows($conn, $D2) === 1;
wipe($conn);

$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
