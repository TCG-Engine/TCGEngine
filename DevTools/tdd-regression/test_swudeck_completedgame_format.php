<?php
// RUN VIA CLI (this test makes a loopback HTTP POST to SubmitGameResult.php; serving it over HTTP too
// can deadlock/stall the apache worker pool on docker-for-mac). Invoke:
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_completedgame_format.php
//
// completedgame is large (prod data copy), and WinningHero is unindexed. To stay fast we snapshot
// MAX(GameID) (instant on the PK) before each POST and only inspect rows created after it — every
// lookup/cleanup is then a bounded PK range scan, never a full-table scan.
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../APIKeys/APIKeys.php';

$endpoint = 'http://localhost/TCGEngine/APIs/SubmitGameResult.php'; // loopback inside the web container
$conn = GetLocalMySQLConnection();
$checks = [];

function postJson($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp;
}
function basePayload($apiKey, $winHero, $loseHero, $format = null) {
    $p = [
        'apiKey' => $apiKey, 'winner' => 1, 'firstPlayer' => 1, 'round' => 3, 'winnerHealth' => 10,
        'gameName' => 'zztest_cg', 'winHero' => $winHero, 'loseHero' => $loseHero,
        'winnerDeck' => 'x', 'loserDeck' => 'y',
        'player1' => json_encode(['leader'=>'JTL_T01','base'=>'Green','cardResults'=>[],'turnResults'=>[]]),
        'player2' => json_encode(['leader'=>'JTL_T04','base'=>'Red','cardResults'=>[],'turnResults'=>[]]),
    ];
    if ($format !== null) $p['format'] = $format;
    return $p;
}
function maxGid($conn) {
    return intval($conn->query("SELECT MAX(GameID) m FROM completedgame")->fetch_assoc()['m']);
}
// The just-created row for $sentinel (WinningHero) with GameID beyond $since, or null. PK range scan.
function newRow($conn, $since, $sentinel) {
    $s = $conn->real_escape_string($sentinel);
    $since = intval($since);
    return $conn->query("SELECT GameID, Format FROM completedgame WHERE GameID > $since AND WinningHero = '$s' ORDER BY GameID DESC LIMIT 1")->fetch_assoc();
}
function delGid($conn, $gid) { $conn->query("DELETE FROM completedgame WHERE GameID = " . intval($gid)); }

// Locally APIKeys.php may not define these; the endpoint accepts an empty key in dev. Guard the
// notice and prefer whichever key is configured.
$apiKey = isset($petranakiAPIKey) ? $petranakiAPIKey : (isset($karabastAPIKey) ? $karabastAPIKey : '');

// premier (explicit) => logged, Format=premier
$before = maxGid($conn);
postJson($endpoint, basePayload($apiKey, 'SOR_T01', 'SOR_T02', 'premier'));
$row = newRow($conn, $before, 'SOR_T01');
$checks['premier logged']       = $row !== null;
$checks['premier format value'] = $row && $row['Format'] === 'premier';
if ($row) delGid($conn, $row['GameID']);

// no format => defaults premier
$before = maxGid($conn);
postJson($endpoint, basePayload($apiKey, 'JTL_T01', 'JTL_T02', null));
$row = newRow($conn, $before, 'JTL_T01');
$checks['default logged']       = $row !== null;
$checks['default format value'] = $row && $row['Format'] === 'premier';
if ($row) delGid($conn, $row['GameID']);

// eternal => logged, Format=eternal (new behavior; fails before the code change)
$before = maxGid($conn);
postJson($endpoint, basePayload($apiKey, 'JTL_T03', 'LAW_T01', 'eternal'));
$row = newRow($conn, $before, 'JTL_T03');
$checks['eternal logged']       = $row !== null;
$checks['eternal format value'] = $row && $row['Format'] === 'eternal';
if ($row) delGid($conn, $row['GameID']);

// open => NOT logged
$before = maxGid($conn);
postJson($endpoint, basePayload($apiKey, 'LAW_T02', 'LAW_T03', 'open'));
$checks['open not logged'] = newRow($conn, $before, 'LAW_T02') === null;

// opt-out => NOT logged
$before = maxGid($conn);
$p = basePayload($apiKey, 'LOF_T01', 'LOF_T02', 'premier');
$p['disableMetaStats'] = true;
postJson($endpoint, $p);
$checks['optout not logged'] = newRow($conn, $before, 'LOF_T01') === null;

// private deck => NOT logged (fixture: ownership row with private visibility + a resolving deck link)
$privDeckID = 999900050;
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $privDeckID");
$conn->query("INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus, assetVisibility) VALUES (1, $privDeckID, 999999999, 1, 5000)");
$before = maxGid($conn);
$p = basePayload($apiKey, 'SHD_T01', 'SHD_T02', 'premier');
$p['p1DeckLink'] = "http://localhost/TCGEngine/?gameName=$privDeckID";
postJson($endpoint, $p);
$checks['private not logged'] = newRow($conn, $before, 'SHD_T01') === null;
// teardown fixture + any deck-level rows SaveDeckStats wrote for it (all deckID-indexed)
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $privDeckID");
$conn->query("DELETE FROM deckstats WHERE deckID = $privDeckID");
$conn->query("DELETE FROM carddeckstats WHERE deckID = $privDeckID");
$conn->query("DELETE FROM opponentdeckstats WHERE deckID = $privDeckID");
$conn->query("DELETE FROM opponentnamedbasestats WHERE deckID = $privDeckID");

$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
