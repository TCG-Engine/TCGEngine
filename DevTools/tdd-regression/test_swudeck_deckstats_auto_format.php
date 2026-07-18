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

// premier => deckstats row with format=premier
wipe($conn, $deckID);
postJson($endpoint, payload($apiKey, $deckID, 'premier'));
$checks['premier deckstats row'] = fmtRows($conn, $deckID, 'premier') === 1;

// eternal => deckstats row with format=eternal
wipe($conn, $deckID);
postJson($endpoint, payload($apiKey, $deckID, 'eternal'));
$checks['eternal deckstats row'] = fmtRows($conn, $deckID, 'eternal') === 1;
$checks['eternal carddeckstats row'] = intval($conn->query("SELECT COUNT(*) c FROM carddeckstats WHERE deckID = $deckID AND format = 'eternal'")->fetch_assoc()['c']) === 1;

// open => NO deckstats row
wipe($conn, $deckID);
postJson($endpoint, payload($apiKey, $deckID, 'open'));
$checks['open no deckstats row'] = intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID")->fetch_assoc()['c']) === 0;

// teardown
wipe($conn, $deckID);
$conn->query("DELETE FROM ownership WHERE assetType = 1 AND assetIdentifier = $deckID");
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
