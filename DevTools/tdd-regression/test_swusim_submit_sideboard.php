<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_submit_sideboard.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _SubP { private $seat;private $key;private $link;
  function __construct($s,$l){$this->seat=$s;$this->link=$l;$this->key='sub'.$s.uniqid();}
  function getGamePlayerID(){return $this->seat;} function setGamePlayerID($x){$this->seat=$x;}
  function getAuthKey(){return $this->key;} function getDeckLink(){return $this->link;} function getPreconstructedDeck(){return '';} }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$dl[]="3 $c"; $dl[]="1 JTL_103"; $dl[]="1 LOF_103"; $deck=implode("\n",$dl);

$p1=new _SubP(1,$deck); $p2=new _SubP(2,$deck);
$lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo3'; $lobby->players=[$p1,$p2];
$matchId=SWUCreateMatchFromLobby($lobby);
$g1=(SWUReadMatch($matchId))['games'][0]['gameName'];
SWURecordGameResult($matchId,$g1,2); SWUBeginSideboarding($matchId,1);

function _sub($matchId,$seat,$key,$deck){
  $ch=curl_init('http://localhost/TCGEngine/SWUSim/SubmitSideboard.php');
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>20,
    CURLOPT_POSTFIELDS=>http_build_query(['matchId'=>$matchId,'playerID'=>$seat,'authKey'=>$key,'deck'=>$deck])]);
  $o=curl_exec($ch); curl_close($ch); return json_decode($o,true);
}
$checks=[];
$bad=_sub($matchId,1,'wrongkey',$deck); $checks['auth rejected']=empty($bad['success']);
$illegal=_sub($matchId,1,$p1->getAuthKey(),"Leader\nJTL_001\nDeck\n3 SOR_100"); $checks['illegal rejected']=empty($illegal['success']);
$r1=_sub($matchId,1,$p1->getAuthKey(),$deck); $checks['seat1 ok']=!empty($r1['success']) && empty($r1['nextGameName']);
$r2=_sub($matchId,2,$p2->getAuthKey(),$deck); $checks['both ready']=!empty($r2['bothReady']); $checks['game2 spawned']=!empty($r2['nextGameName']);

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails)?"PASS (".count($checks)." checks)\n":"FAIL: ".implode(', ',$fails)." r1=".json_encode($r1??null)." r2=".json_encode($r2??null)."\n";
