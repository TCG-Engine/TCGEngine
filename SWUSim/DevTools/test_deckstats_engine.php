<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../Custom/DeckImport.php';
require_once __DIR__ . '/../MatchFlow.php';
$pass=0;$fail=0; function ok($c,$m){global $pass,$fail; if($c){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

$UW=999001; $UL=999002; $DLW='https://swudb.com/deck/_win_'; $DLL='https://swudb.com/deck/_lose_';
$c=GetLocalMySQLConnection();
foreach([$UW,$UL] as $u){ $c->query("DELETE FROM favoritedeck WHERE usersId=$u"); $c->query("DELETE FROM favoritedeckmatchup WHERE usersId=$u"); }
AddSavedDeck($UW,$DLW,'Winner','ASH_005','JTL_024','premier',null);   // seat 1 saved
AddSavedDeck($UL,$DLL,'Loser','ASH_006','SOR_024','premier',null);    // seat 2 saved (SOR_024 = Green 30HP)

// hand-built match object (seat1 user UW beats seat2 user UL)
$match = ['players'=>[
  '1'=>['userId'=>$UW,'deckIdentity'=>$DLW,'originalDeck'=>['leader'=>'ASH_005','base'=>'JTL_024']],
  '2'=>['userId'=>$UL,'deckIdentity'=>$DLL,'originalDeck'=>['leader'=>'ASH_006','base'=>'SOR_024']],
]];
SWURecordDeckStatsForGame($match, 1);

$w = LoadSavedDecks($UW)[0]; $l = LoadSavedDecks($UL)[0];
ok((int)$w['wins']===1 && (int)$w['losses']===0, "winner deck +1 win");
ok((int)$l['wins']===0 && (int)$l['losses']===1, "loser deck +1 loss");
// winner's matchup vs opp leader ASH_006 + normalized base of SOR_024 (Green 30HP)
$mw = LoadSavedDeckMatchups($UW,$DLW);
ok(count($mw)===1 && $mw[0]['oppLeader']==='ASH_006' && $mw[0]['oppBase']==='Green 30HP'
   && (int)$mw[0]['wins']===1, "winner matchup vs ASH_006 / Green 30HP = 1-0");
// loser's matchup vs ASH_005 + normalized base of JTL_024 (Rare → own cardId)
$ml = LoadSavedDeckMatchups($UL,$DLL);
ok(count($ml)===1 && $ml[0]['oppLeader']==='ASH_005' && $ml[0]['oppBase']==='JTL_024'
   && (int)$ml[0]['losses']===1, "loser matchup vs ASH_005 / JTL_024 = 0-1");

// guest seat: rebuild with seat2 userId null → only seat1 records
foreach([$UW,$UL] as $u){ $c->query("DELETE FROM favoritedeck WHERE usersId=$u"); $c->query("DELETE FROM favoritedeckmatchup WHERE usersId=$u"); }
AddSavedDeck($UW,$DLW,'Winner','ASH_005','JTL_024','premier',null);
$match['players']['2']['userId']=null;
SWURecordDeckStatsForGame($match, 2); // seat2 wins but is a guest; seat1 (saved) takes a loss
$w = LoadSavedDecks($UW)[0];
ok((int)$w['losses']===1, "saved seat records loss even when opponent is a guest");

foreach([$UW,$UL] as $u){ $c->query("DELETE FROM favoritedeck WHERE usersId=$u"); $c->query("DELETE FROM favoritedeckmatchup WHERE usersId=$u"); }
echo "PASS=$pass FAIL=$fail\n";
