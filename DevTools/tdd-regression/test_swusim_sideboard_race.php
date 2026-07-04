<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_sideboard_race.php
//
// REGRESSION GUARD for the "sideboard submit → waiting player hangs" bug (both directions),
// plus the concurrent double-spawn race. The player who submits FIRST enters the client poll
// loop (re-POSTing SubmitSideboard); once the opponent submits and the next game spawns, that
// first player's NEXT poll MUST receive nextGameName (else it hangs). This must hold whether
// seat 1 or seat 2 submits first, and concurrent submits must spawn EXACTLY ONE next game.
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _RaceP { private $seat;private $key;private $link;
  function __construct($s,$l){$this->seat=$s;$this->link=$l;$this->key='race'.$s.uniqid();}
  function getGamePlayerID(){return $this->seat;} function setGamePlayerID($x){$this->seat=$x;}
  function getAuthKey(){return $this->key;} function getDeckLink(){return $this->link;} function getPreconstructedDeck(){return '';} }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$dl[]="3 $c"; $dl[]="1 JTL_103"; $dl[]="1 LOF_103"; $deck=implode("\n",$dl);

// Build a fresh Bo3 match already IN sideboarding (game 1 recorded, loser=1 goes first).
function _mkSideboarding($deck){
  $p1=new _RaceP(1,$deck); $p2=new _RaceP(2,$deck);
  $lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo3'; $lobby->players=[$p1,$p2];
  $matchId=SWUCreateMatchFromLobby($lobby);
  $g1=(SWUReadMatch($matchId))['games'][0]['gameName'];
  SWURecordGameResult($matchId,$g1,2); SWUBeginSideboarding($matchId,1);
  return [$matchId,$p1->getAuthKey(),$p2->getAuthKey()];
}
function _sub($matchId,$seat,$key,$deck){
  $ch=curl_init('http://localhost/TCGEngine/SWUSim/SubmitSideboard.php');
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>25,
    CURLOPT_POSTFIELDS=>http_build_query(['matchId'=>$matchId,'playerID'=>$seat,'authKey'=>$key,'deck'=>$deck])]);
  $o=curl_exec($ch); curl_close($ch); return json_decode($o,true);
}
// Fire both seats' submits concurrently (real double-spawn race).
function _subBoth($matchId,$k1,$k2,$deck){
  $mh=curl_multi_init(); $H=[];
  foreach([[1,$k1],[2,$k2]] as $pk){
    $ch=curl_init('http://localhost/TCGEngine/SWUSim/SubmitSideboard.php');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>25,
      CURLOPT_POSTFIELDS=>http_build_query(['matchId'=>$matchId,'playerID'=>$pk[0],'authKey'=>$pk[1],'deck'=>$deck])]);
    curl_multi_add_handle($mh,$ch); $H[$pk[0]]=$ch;
  }
  $run=null; do{ curl_multi_exec($mh,$run); curl_multi_select($mh,1.0); }while($run>0);
  $out=[]; foreach($H as $seat=>$ch){ $out[$seat]=json_decode(curl_multi_getcontent($ch),true); curl_multi_remove_handle($mh,$ch); curl_close($ch);}
  curl_multi_close($mh); return $out;
}

$checks=[];

// ── Ordering A: seat 1 submits FIRST, then seat 2 ─────────────────────────────
[$mA,$k1,$k2]=_mkSideboarding($deck);
$a1=_sub($mA,1,$k1,$deck);
$checks['A seat1-first: no game yet']       = !empty($a1['success']) && empty($a1['nextGameName']);
$a2=_sub($mA,2,$k2,$deck);
$checks['A seat2: spawned next game']        = !empty($a2['nextGameName']);
$a1poll=_sub($mA,1,$k1,$deck);              // the waiting player's next poll
$checks['A seat1 poll: gets nextGameName']   = !empty($a1poll['nextGameName']);
$checks['A seat1 poll: same game as seat2']  = strval($a1poll['nextGameName']??'x')===strval($a2['nextGameName']??'y');

// ── Ordering B: seat 2 submits FIRST, then seat 1 ─────────────────────────────
[$mB,$k1b,$k2b]=_mkSideboarding($deck);
$b2=_sub($mB,2,$k2b,$deck);
$checks['B seat2-first: no game yet']        = !empty($b2['success']) && empty($b2['nextGameName']);
$b1=_sub($mB,1,$k1b,$deck);
$checks['B seat1: spawned next game']        = !empty($b1['nextGameName']);
$b2poll=_sub($mB,2,$k2b,$deck);
$checks['B seat2 poll: gets nextGameName']    = !empty($b2poll['nextGameName']);
$checks['B seat2 poll: same game as seat1']   = strval($b2poll['nextGameName']??'x')===strval($b1['nextGameName']??'y');

// ── Concurrent submits: EXACTLY ONE next game spawns (no double-spawn desync) ──
[$mC,$k1c,$k2c]=_mkSideboarding($deck);
$before=count((SWUReadMatch($mC))['games']);   // = 1 (game 1)
$c=_subBoth($mC,$k1c,$k2c,$deck);
$mCafter=SWUReadMatch($mC);
$after=count($mCafter['games']);
$checks['C concurrent: exactly one game spawned'] = ($after === $before+1);
$checks['C concurrent: match advanced']           = (($mCafter['state']??'')==='in_progress');
// whichever seat didn't get a direct nextGameName recovers on its poll → same single game
$spawned = strval($mCafter['games'][count($mCafter['games'])-1]['gameName']);
$c1poll = !empty($c[1]['nextGameName']) ? $c[1]['nextGameName'] : (_sub($mC,1,$k1c,$deck)['nextGameName']??null);
$c2poll = !empty($c[2]['nextGameName']) ? $c[2]['nextGameName'] : (_sub($mC,2,$k2c,$deck)['nextGameName']??null);
$checks['C concurrent: both converge to the one game'] = (strval($c1poll)===$spawned && strval($c2poll)===$spawned);

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails) ? "PASS (".count($checks)." checks)\n"
  : "FAIL: ".implode(', ',$fails)."\n a2=".json_encode($a2)."\n b1=".json_encode($b1)."\n c=".json_encode($c)."\n";
