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
// ⚠ BOTH card ids in this payload were placeholders that no longer validate. Since the SET_NNN work
// (2026-08-04) SubmitManualGameResult rejects unknown identifiers outright, so the request 400'd
// before ever reaching SaveDeckStats and all three row assertions failed. The ENDPOINT was right;
// the FIXTURE predated the validation. Both are now real cards, as the sibling deckstats tests use.
//   opposingHero: 'JTL_T012' -> 'SOR_005'  (JTL tokens are T01-T04; a 3-digit token id never existed)
//   cardResults:  'ZZCARD'   -> 'SOR_010'
function manualPayload($deckID, $format) {
    return [
        'deckID'=>$deckID, 'won'=>true, 'rounds'=>3, 'winnerHealth'=>10, 'firstPlayer'=>true, 'format'=>$format,
        'player'=>json_encode(['opposingHero'=>'SOR_005','opposingBaseColor'=>'Red','opposingBaseGroup'=>'Standard',
            'cardResults'=>[['cardID'=>'SOR_010','played'=>1,'resourced'=>1]]]),
    ];
}
function fmtRows($conn, $deckID, $format) {
    $f = $conn->real_escape_string($format);
    return intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID AND format = '$f'")->fetch_assoc()['c']);
}

// eternal manual add => eternal deckstats row
wipe($conn, $deckID);
$resp = postJson($endpoint, manualPayload($deckID, 'eternal'));
// Assert the RESPONSE, not just the rows: every row below is written early in SaveDeckStats, so
// without this the request can die partway through and each row assertion still passes.
$checks['eternal manual request succeeds'] = strpos((string)$resp, 'Fatal error') === false
                                          && strpos((string)$resp, 'Uncaught') === false;
$checks['eternal manual deckstats row'] = fmtRows($conn, $deckID, 'eternal') === 1;
$checks['eternal manual carddeckstats row'] = intval($conn->query("SELECT COUNT(*) c FROM carddeckstats WHERE deckID = $deckID AND format = 'eternal'")->fetch_assoc()['c']) === 1;
// ZZCARD is a non-numeric card id on purpose (cardID is varchar(16), and real traffic carries raw
// SET_NNN ids for cards without an FFG UID). The row is INSERTed before the counters are applied, so
// counting rows cannot see a cardID bound with the wrong type — the UPDATE then matches nothing.
$counters = $conn->query("SELECT timesIncluded, timesPlayed, timesResourced FROM carddeckstats WHERE deckID = $deckID AND format = 'eternal' LIMIT 1")->fetch_assoc();
$checks['eternal manual carddeckstats counted the play'] = $counters !== null
    && intval($counters['timesIncluded']) === 1 && intval($counters['timesPlayed']) === 1
    && intval($counters['timesResourced']) === 1;

// open manual add => NO deckstats row
wipe($conn, $deckID);
$resp = postJson($endpoint, manualPayload($deckID, 'open'));
$checks['open manual request succeeds'] = strpos((string)$resp, 'Fatal error') === false
                                       && strpos((string)$resp, 'Uncaught') === false;
$checks['open manual no row'] = intval($conn->query("SELECT COUNT(*) c FROM deckstats WHERE deckID = $deckID")->fetch_assoc()['c']) === 0;

wipe($conn, $deckID);
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
