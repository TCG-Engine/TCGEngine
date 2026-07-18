<?php
// RUN VIA CLI:
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_metastats_write_format.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../APIKeys/APIKeys.php';
$endpoint = 'http://localhost/TCGEngine/APIs/SubmitGameResult.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$apiKey = isset($petranakiAPIKey) ? $petranakiAPIKey : (isset($karabastAPIKey) ? $karabastAPIKey : '');

// Sentinel leader/base so we can find + clean the meta rows without touching real data.
$LEAD = 'ZZMLEAD'; $BASE = 'ZZMBASE';
$deckID = 999900070;
$conn->query("DELETE FROM ownership WHERE assetType=1 AND assetIdentifier=$deckID");
$conn->query("INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus, assetVisibility) VALUES (1,$deckID,999999999,1,2000000)");
function wipeMeta($conn,$LEAD){ $l=$conn->real_escape_string($LEAD);
  $conn->query("DELETE FROM deckmetastats WHERE leaderID='$l'");
  $conn->query("DELETE FROM deckmetamatchupstats WHERE leaderID='$l'"); }
function post($url,$d){ $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($d)]); $r=curl_exec($ch); curl_close($ch); return $r; }
function payload($apiKey,$deckID,$LEAD,$BASE,$format){ return [
  'apiKey'=>$apiKey,'winner'=>1,'firstPlayer'=>1,'round'=>3,'winnerHealth'=>10,'gameName'=>strval($deckID),
  'winHero'=>$LEAD,'loseHero'=>'ZZOPP','format'=>$format,
  'p1DeckLink'=>"http://localhost/TCGEngine/?gameName=$deckID",
  'player1'=>json_encode(['leader'=>$LEAD,'base'=>$BASE,'cardResults'=>[],'turnResults'=>[]]),
  'player2'=>json_encode(['leader'=>'ZZOPP','base'=>'Red','cardResults'=>[],'turnResults'=>[]]),
]; }
function metaRows($conn,$LEAD,$format){ $l=$conn->real_escape_string($LEAD); $f=$conn->real_escape_string($format);
  return intval($conn->query("SELECT COUNT(*) c FROM deckmetastats WHERE leaderID='$l' AND format='$f'")->fetch_assoc()['c']); }

wipeMeta($conn,$LEAD);
post($endpoint,payload($apiKey,$deckID,$LEAD,$BASE,'premier'));
$checks['premier meta row'] = metaRows($conn,$LEAD,'premier') === 1;
wipeMeta($conn,$LEAD);
post($endpoint,payload($apiKey,$deckID,$LEAD,$BASE,'eternal'));
$checks['eternal meta row'] = metaRows($conn,$LEAD,'eternal') === 1;
wipeMeta($conn,$LEAD);
post($endpoint,payload($apiKey,$deckID,$LEAD,$BASE,'twinsuns'));
$checks['twinsuns meta row'] = metaRows($conn,$LEAD,'twinsuns') === 1;
wipeMeta($conn,$LEAD);
post($endpoint,payload($apiKey,$deckID,$LEAD,$BASE,'open'));
$checks['open no meta row'] = intval($conn->query("SELECT COUNT(*) c FROM deckmetastats WHERE leaderID='".$conn->real_escape_string($LEAD)."'")->fetch_assoc()['c']) === 0;

wipeMeta($conn,$LEAD);
$conn->query("DELETE FROM deckstats WHERE deckID=$deckID");
$conn->query("DELETE FROM carddeckstats WHERE deckID=$deckID");
$conn->query("DELETE FROM opponentdeckstats WHERE deckID=$deckID");
$conn->query("DELETE FROM opponentnamedbasestats WHERE deckID=$deckID");
$conn->query("DELETE FROM ownership WHERE assetType=1 AND assetIdentifier=$deckID");
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
