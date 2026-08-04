<?php
// RUN VIA CLI (loopback POST to SubmitGameResult.php):
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_deckstats_auto_format.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../APIKeys/APIKeys.php';

$endpoint = 'http://localhost/TCGEngine/APIs/SubmitGameResult.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$apiKey = isset($petranakiAPIKey) ? $petranakiAPIKey : (isset($karabastAPIKey) ? $karabastAPIKey : '');

// Dedicated throwaway deckID + public ownership fixture so SaveDeckStats runs (deck link resolves).
$deckID = 999900060;
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $deckID");
$conn->query("INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus, assetVisibility) VALUES (1, $deckID, 999999999, 1, 2000000)"); // public (>=1000000)
function wipe($conn, $deckID) {
    foreach (['deckstats','carddeckstats','opponentdeckstats','opponentnamedbasestats'] as $t)
        $conn->query("DELETE FROM `$t` WHERE deckID = $deckID");
}
function postJson($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>json_encode($data)]);
    $r = curl_exec($ch); curl_close($ch); return $r;
}
function payload($apiKey, $deckID, $format) {
    return [
        'apiKey'=>$apiKey, 'winner'=>1, 'firstPlayer'=>1, 'round'=>3, 'winnerHealth'=>10,
        'gameName'=>strval($deckID), 'winHero'=>'ZZH_W', 'loseHero'=>'ZZH_L', 'format'=>$format,
        'p1DeckLink'=>"http://localhost/TCGEngine/?gameName=$deckID",
        'player1'=>json_encode(['leader'=>'ZZLEAD','base'=>'Green','cardResults'=>[['cardId'=>'ZZCARD','played'=>1,'resourced'=>1,'drawn'=>1,'discarded'=>0]],'turnResults'=>[]]),
        'player2'=>json_encode(['leader'=>'ZZLEAD2','base'=>'Red','cardResults'=>[],'turnResults'=>[]]),
    ];
}
function fmtRows($conn, $deckID, $format) {
    $f = $conn->real_escape_string($format);
    return intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID AND format = '$f'")->fetch_assoc()['c']);
}
// The carddeckstats row's counters AFTER the update pass. Counting rows alone is not enough: the row
// is INSERTed before the counters are applied, so a broken UPDATE leaves a row of zeroes behind.
function cardCounters($conn, $deckID, $format) {
    $f = $conn->real_escape_string($format);
    return $conn->query("SELECT timesIncluded, timesPlayed, timesResourced, timesDrawn FROM carddeckstats WHERE deckID = $deckID AND format = '$f' LIMIT 1")->fetch_assoc();
}

// premier => deckstats row with format=premier
wipe($conn, $deckID);
$resp = postJson($endpoint, payload($apiKey, $deckID, 'premier'));
// Assert the RESPONSE, not just the rows. Every row this test checks is written early in
// SaveDeckStats, so without this the request can die partway through — taking the completedgame
// insert with it — and every row assertion still passes.
$checks['premier request succeeds'] = strpos((string)$resp, '"success":true') !== false;
$checks['premier deckstats row'] = fmtRows($conn, $deckID, 'premier') === 1;

// eternal => deckstats row with format=eternal
wipe($conn, $deckID);
$resp = postJson($endpoint, payload($apiKey, $deckID, 'eternal'));
$checks['eternal request succeeds'] = strpos((string)$resp, '"success":true') !== false;
$checks['eternal deckstats row'] = fmtRows($conn, $deckID, 'eternal') === 1;
$checks['eternal carddeckstats row'] = intval($conn->query("SELECT COUNT(*) c FROM carddeckstats WHERE deckID = $deckID AND format = 'eternal'")->fetch_assoc()['c']) === 1;
// ZZCARD is a non-numeric card id on purpose: cardID is varchar(16), and SWUSim falls back to the raw
// SET_NNN CardID for any card without an FFG UID (SWUCardToStatsId), so non-numeric ids are real
// traffic. These counters catch a cardID bound with the wrong type — the UPDATE then matches nothing.
$counters = cardCounters($conn, $deckID, 'eternal');
$checks['eternal carddeckstats counted the play'] = $counters !== null
    && intval($counters['timesIncluded']) === 1 && intval($counters['timesPlayed']) === 1
    && intval($counters['timesResourced']) === 1 && intval($counters['timesDrawn']) === 1;

// open => NO deckstats row
wipe($conn, $deckID);
$resp = postJson($endpoint, payload($apiKey, $deckID, 'open'));
$checks['open request succeeds'] = strpos((string)$resp, '"success":true') !== false;
$checks['open no deckstats row'] = intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID")->fetch_assoc()['c']) === 0;

// teardown — including the META aggregates, which are keyed by leader/base/card and so survive the
// deckID-scoped wipe. Leaving them behind pollutes shared tables and makes other tests' assertions
// collide with this test's fixture.
wipe($conn, $deckID);
$conn->query("DELETE FROM deckmetastats WHERE leaderID = 'ZZLEAD'");
$conn->query("DELETE FROM deckmetamatchupstats WHERE leaderID = 'ZZLEAD'");
$conn->query("DELETE FROM cardmetastats WHERE cardID = 'ZZCARD'");
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $deckID");
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
