<?php
// RUN VIA CLI:
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_deckstats_manual_format.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';

$endpoint = 'http://localhost/TCGEngine/APIs/SubmitManualGameResult.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$deckID = 999900061;
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
function manualPayload($deckID, $format) {
    return [
        'deckID'=>$deckID, 'won'=>true, 'rounds'=>3, 'winnerHealth'=>10, 'firstPlayer'=>true, 'format'=>$format,
        'player'=>json_encode(['opposingHero'=>'ZZLEAD2','opposingBaseColor'=>'Red','opposingBaseGroup'=>'Standard',
            'cardResults'=>[['cardID'=>'ZZCARD','played'=>1,'resourced'=>1]]]),
    ];
}
function fmtRows($conn, $deckID, $format) {
    $f = $conn->real_escape_string($format);
    return intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID AND format = '$f'")->fetch_assoc()['c']);
}

// eternal manual add => eternal deckstats row
wipe($conn, $deckID);
postJson($endpoint, manualPayload($deckID, 'eternal'));
$checks['eternal manual deckstats row'] = fmtRows($conn, $deckID, 'eternal') === 1;
$checks['eternal manual carddeckstats row'] = intval($conn->query("SELECT COUNT(*) c FROM carddeckstats WHERE deckID = $deckID AND format = 'eternal'")->fetch_assoc()['c']) === 1;

// open manual add => NO deckstats row
wipe($conn, $deckID);
postJson($endpoint, manualPayload($deckID, 'open'));
$checks['open manual no row'] = intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID")->fetch_assoc()['c']) === 0;

wipe($conn, $deckID);
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
