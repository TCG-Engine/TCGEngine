<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_endgame_info.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _EGP { private $seat;private $key;private $link;
  function __construct($s,$l){$this->seat=$s;$this->link=$l;$this->key='eg'.$s.uniqid();}
  function getGamePlayerID(){return $this->seat;} function setGamePlayerID($x){$this->seat=$x;}
  function getAuthKey(){return $this->key;} function getDeckLink(){return $this->link;} function getPreconstructedDeck(){return '';} }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$dl[]="3 $c"; $dl[]="1 JTL_103"; $dl[]="1 LOF_103"; $deck=implode("\n",$dl);

$p1=new _EGP(1,$deck); $p2=new _EGP(2,$deck);
$lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo1'; $lobby->players=[$p1,$p2];
$matchId=SWUCreateMatchFromLobby($lobby);
$g1=(SWUReadMatch($matchId))['games'][0]['gameName'];
SWURecordGameResult($matchId,$g1,1);

function _info($game,$seat,$key){
  $ch=curl_init('http://localhost/TCGEngine/SWUSim/EndGameInfo.php?gameName='.$game.'&playerID='.$seat.'&authKey='.$key);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15]); $o=curl_exec($ch); curl_close($ch); return json_decode($o,true);
}
$checks=[];
$i1=_info($g1,1,$p1->getAuthKey());
$checks['isMatch'] = !empty($i1['isMatch']);
$checks['seat1 didWin'] = ($i1['didWin'] ?? null) === true;
$checks['bestOf 1'] = ($i1['bestOf'] ?? null) === 1;
$checks['convertible'] = ($i1['convertible'] ?? null) === true;
$checks['seriesOver'] = ($i1['seriesOver'] ?? null) === true;
$checks['has statsHtml'] = is_string($i1['statsHtml'] ?? null);
$i2=_info($g1,2,$p2->getAuthKey());
$checks['seat2 didWin false'] = ($i2['didWin'] ?? null) === false;
$bad=_info($g1,1,'wrong');
$checks['bad auth rejected'] = empty($bad['isMatch']) || !empty($bad['error']);

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails)?"PASS (".count($checks)." checks)\n":"FAIL: ".implode(', ',$fails)." i1=".json_encode($i1)."\n";
