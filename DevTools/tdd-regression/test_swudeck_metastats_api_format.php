<?php
// RUN VIA CLI:
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_metastats_api_format.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$LEAD='ZZAPILEAD'; $BASE='ZZAPIBASE'; $week=0;
$conn->query("DELETE FROM deckmetastats WHERE leaderID='$LEAD'");
// Seed a premier row (numPlays 5) and an eternal row (numPlays 9) for the same leader/base/week.
$conn->query("INSERT INTO deckmetastats (leaderID,baseID,week,format,numWins,numPlays) VALUES ('$LEAD','$BASE',$week,'premier',3,5)");
$conn->query("INSERT INTO deckmetastats (leaderID,baseID,week,format,numWins,numPlays) VALUES ('$LEAD','$BASE',$week,'eternal',7,9)");
function apiPlays($url,$LEAD){ $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>30]); $r=curl_exec($ch); curl_close($ch);
  $j=json_decode($r,true); if(!is_array($j)) return -1; foreach($j as $row){ if(is_array($row) && ($row['leaderID']??'')===$LEAD) return intval($row['numPlays']); } return 0; }
$base='http://localhost/TCGEngine/Stats/DeckMetaStatsAPI.php';
$checks['default -> premier (5 plays)'] = apiPlays("$base?startWeek=0&endWeek=0",$LEAD) === 5;
$checks['format=eternal (9 plays)']     = apiPlays("$base?startWeek=0&endWeek=0&format=eternal",$LEAD) === 9;
$checks['invalid format -> premier']    = apiPlays("$base?startWeek=0&endWeek=0&format=bogus",$LEAD) === 5;
$conn->query("DELETE FROM deckmetastats WHERE leaderID='$LEAD'");
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
