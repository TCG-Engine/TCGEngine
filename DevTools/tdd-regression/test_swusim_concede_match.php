<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_concede_match.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _CMPlayer { private $seat;private $key;private $link;
  function __construct($s,$l){$this->seat=$s;$this->link=$l;$this->key='cm'.$s.uniqid();}
  function getGamePlayerID(){return $this->seat;} function setGamePlayerID($x){$this->seat=$x;}
  function getAuthKey(){return $this->key;} function getDeckLink(){return $this->link;} function getPreconstructedDeck(){return '';} }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$dl[]="3 $c"; $dl[]="1 JTL_103"; $dl[]="1 LOF_103"; $deck=implode("\n",$dl);
$lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo3'; $lobby->players=[new _CMPlayer(1,$deck), new _CMPlayer(2,$deck)];
$matchId=SWUCreateMatchFromLobby($lobby);
$g1=(SWUReadMatch($matchId))['games'][0]['gameName'];

$checks=[];
// Player 2 wins game 1 (now 1-0), then Player 1 concedes the whole match.
SWURecordGameResult($matchId,$g1,2);
$m=SWUConcedeMatch($matchId,1);
$checks['state complete'] = ($m['state'] ?? '')==='complete';
$checks['winner is 2'] = ($m['winner'] ?? 0)===2;
$checks['opp clinched'] = ($m['wins']['2'] ?? 0) === ($m['winsNeeded'] ?? 99);

// Idempotent: conceding again leaves it unchanged.
$wins2 = $m['wins']['2'];
$m2=SWUConcedeMatch($matchId,1);
$checks['idempotent'] = ($m2['wins']['2'] ?? 0) === $wins2 && ($m2['state'] ?? '')==='complete';

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails)?"PASS (".count($checks)." checks)\n":"FAIL: ".implode(', ',$fails)." m=".json_encode($m)."\n";
