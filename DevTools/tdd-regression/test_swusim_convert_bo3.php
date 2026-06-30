<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_convert_bo3.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _CvP { private $seat;private $key;private $link;
  function __construct($s,$l){$this->seat=$s;$this->link=$l;$this->key='cv'.$s.uniqid();}
  function getGamePlayerID(){return $this->seat;} function setGamePlayerID($x){$this->seat=$x;}
  function getAuthKey(){return $this->key;} function getDeckLink(){return $this->link;} function getPreconstructedDeck(){return '';} }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$dl[]="3 $c"; $dl[]="1 JTL_103"; $dl[]="1 LOF_103"; $deck=implode("\n",$dl);
// Bo1 match.
$lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo1'; $lobby->players=[new _CvP(1,$deck), new _CvP(2,$deck)];
$matchId=SWUCreateMatchFromLobby($lobby);
$g1=(SWUReadMatch($matchId))['games'][0]['gameName'];

$checks=[];
// Game 1 won by player 1 → Bo1 completes.
$m=SWURecordGameResult($matchId,$g1,1);
$checks['bo1 complete'] = (($m['state']??'')==='complete') && intval($m['bestOf'])===1;

// One side requests → still a completed Bo1.
SWURequestConvertToBo3($matchId,1);
$r=SWUAcceptConvertToBo3($matchId);
$m=SWUReadMatch($matchId);
$checks['one side no convert'] = ($r===null) && intval($m['bestOf'])===1;

// EndGameInfo must surface the one-sided request per seat so the menu can show the
// "Waiting on opponent" (initiator) / "Confirm Convert" (other) two-step states.
function _egi($gn,$seat,$key){
  $ch=curl_init('http://localhost/TCGEngine/SWUSim/EndGameInfo.php?'.http_build_query(['gameName'=>$gn,'playerID'=>$seat,'authKey'=>$key]));
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20]);
  $o=curl_exec($ch); curl_close($ch); return json_decode($o,true);
}
$k1=$m['players']['1']['authKey']; $k2=$m['players']['2']['authKey'];
$e1=_egi($g1,1,$k1); $e2=_egi($g1,2,$k2);
$checks['initiator sees own request']   = !empty($e1['convertRequestedByMe']) && empty($e1['convertRequestedByOpp']);
$checks['other sees opp request']       = empty($e2['convertRequestedByMe']) && !empty($e2['convertRequestedByOpp']);
$checks['both still convertible']       = !empty($e1['convertible']) && !empty($e2['convertible']);

// Both sides request → promote to Bo3, re-enter sideboarding, keep game-1 win.
SWURequestConvertToBo3($matchId,2);
$r=SWUAcceptConvertToBo3($matchId);
$m=SWUReadMatch($matchId);
$checks['converted'] = ($r===$matchId);
$checks['now bestOf 3'] = intval($m['bestOf'])===3 && intval($m['winsNeeded'])===2;
$checks['sideboarding'] = ($m['state']??'')==='sideboarding';
$checks['game1 win preserved'] = ($m['wins']['1']??0)===1;

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails)?"PASS (".count($checks)." checks)\n":"FAIL: ".implode(', ',$fails)." m=".json_encode($m)."\n";
