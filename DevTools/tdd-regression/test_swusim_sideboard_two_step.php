<?php
// The two-step sideboard (owner, 2026-11-03): SUBMIT a sideboard and READY for the next game are separate, and a seat can
// CANCEL (back to editing, not ready) until both are ready and the next game starts. Ready without a submit plays the deck
// from the last game. SubmitSideboard.php `action` = save | ready | cancel | status; no action = the old one-click
// submit-and-ready (test_swusim_submit_sideboard.php), unchanged.
// Web SAPI (the game's seat keys live in APCu): http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_sideboard_two_step.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _TwoP { private $seat;private $key;private $link;
  function __construct($s,$l){$this->seat=$s;$this->link=$l;$this->key='two'.$s.uniqid();}
  function getGamePlayerID(){return $this->seat;} function setGamePlayerID($x){$this->seat=$x;}
  function getAuthKey(){return $this->key;} function getDeckLink(){return $this->link;} function getPreconstructedDeck(){return '';} }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$main=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$main[]="3 $c";
$deckA=implode("\n",array_merge($main,["1 JTL_103","1 LOF_103","Sideboard","1 JTL_104"]));   // as registered
$deckB=implode("\n",array_merge($main,["1 JTL_104","1 LOF_103","Sideboard","1 JTL_103"]));   // JTL_103 sided out for JTL_104

$MATCHES=[];
function newMatch(){
  global $deckA, $MATCHES;
  $p1=new _TwoP(1,$deckA); $p2=new _TwoP(2,$deckA);
  $lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo3'; $lobby->players=[$p1,$p2];
  $id=SWUCreateMatchFromLobby($lobby); $MATCHES[]=$id;
  $g1=(SWUReadMatch($id))['games'][0]['gameName'];
  SWURecordGameResult($id,$g1,2); SWUBeginSideboarding($id,1);
  return [$id,$p1->getAuthKey(),$p2->getAuthKey()];
}
function sb($matchId,$seat,$key,array $extra){
  $ch=curl_init('http://localhost/TCGEngine/SWUSim/SubmitSideboard.php');
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>60,
    CURLOPT_POSTFIELDS=>http_build_query(['matchId'=>$matchId,'playerID'=>$seat,'authKey'=>$key]+$extra)]);
  $o=curl_exec($ch); curl_close($ch); $j=json_decode((string)$o,true); return is_array($j)?$j:['RAW'=>substr((string)$o,0,300)];
}
function seatSb($matchId,$seat){ return SWUReadMatch($matchId)['sideboard'][strval($seat)] ?? null; }
// The resolved list keeps one entry per copy: is $id in this zone of the saved / played deck?
function has(?array $deck,string $zone,string $id){ return is_array($deck) && in_array($id,$deck[$zone] ?? [],true); }

$checks=[]; $notes=[];
function check($name,$cond,$note=''){ global $checks,$notes; $checks[$name]=(bool)$cond; if(!$cond) $notes[$name]=$note; }

// ── Submit, replace, ready, cancel ──
[$m,$k1,$k2]=newMatch();
check('a wrong key is refused', empty(sb($m,1,'nope',['action'=>'save','deck'=>$deckB])['success']));
check('an illegal list is refused', empty(sb($m,1,$k1,['action'=>'save','deck'=>"Leader\nJTL_001\nDeck\n3 SOR_100"])['success']));
$r=sb($m,1,$k1,['action'=>'save','deck'=>$deckA]);
check('submit saves the list without readying', !empty($r['success']) && ($r['mine'] ?? null)===['submitted'=>true,'ready'=>false] && empty(seatSb($m,1)['ready']), json_encode($r));
$r=sb($m,1,$k1,['action'=>'save','deck'=>$deckB]);
check('a second submit replaces the first', !empty($r['success']) && has(seatSb($m,1)['deck'],'sideboard','JTL_103'), json_encode(seatSb($m,1)));
$r=sb($m,1,$k1,['action'=>'ready']);
check('ready readies the submitted list', !empty($r['success']) && ($r['mine'] ?? null)===['submitted'=>true,'ready'=>true] && has(seatSb($m,1)['deck'],'sideboard','JTL_103') && empty($r['nextGameName']), json_encode($r));
$r=sb($m,1,$k1,['action'=>'save','deck'=>$deckA]);
check('a ready seat cannot change its list without cancelling first', empty($r['success']) && has(seatSb($m,1)['deck'],'sideboard','JTL_103'), json_encode($r));
$st=sb($m,2,$k2,['action'=>'status']);
check('the opponent sees this seat is ready', !empty($st['success']) && ($st['opponentReady'] ?? null)===true && ($st['mine'] ?? null)===['submitted'=>false,'ready'=>false], json_encode($st));
$r=sb($m,1,$k1,['action'=>'cancel']);
check('cancel is back to editing: not ready, nothing submitted', !empty($r['success']) && ($r['mine'] ?? null)===['submitted'=>false,'ready'=>false] && empty(seatSb($m,1)['ready']) && seatSb($m,1)['deck']===null, json_encode($r));
check('and the opponent sees it', (sb($m,2,$k2,['action'=>'status'])['opponentReady'] ?? null)===false);

// ── Ready with nothing submitted plays the last game's deck; both ready starts game 2 ──
$r=sb($m,1,$k1,['action'=>'ready']);
check('ready without a submit takes the last game\'s deck', !empty($r['success']) && has(seatSb($m,1)['deck'],'mainDeck','JTL_103') && has(seatSb($m,1)['deck'],'sideboard','JTL_104'), json_encode(seatSb($m,1)));
sb($m,1,$k1,['action'=>'cancel']);
sb($m,1,$k1,['action'=>'save','deck'=>$deckB]);
sb($m,1,$k1,['action'=>'ready']);
$r=sb($m,2,$k2,['action'=>'ready']);
check('the second ready starts the next game', !empty($r['nextGameName']), json_encode($r));
$mm=SWUReadMatch($m);
check('game 2 is played with seat 1\'s submitted list and seat 2\'s last deck',
  has($mm['players']['1']['currentDeck'] ?? null,'sideboard','JTL_103') && has($mm['players']['2']['currentDeck'] ?? null,'mainDeck','JTL_103'), json_encode(['1'=>$mm['players']['1']['currentDeck']['sideboard'] ?? null,'2'=>$mm['players']['2']['currentDeck']['sideboard'] ?? null]));
$r=sb($m,1,$k1,['action'=>'cancel']);
check('once game 2 has started there is nothing to cancel — the seat is sent to it', ($r['nextGameName'] ?? '')!=='' && ($r['nextGameName'] ?? '')===($mm['games'][1]['gameName'] ?? '?'), json_encode($r));

// ── The countdown readies an un-ready seat with what it SUBMITTED ──
[$m,$k1,$k2]=newMatch();
sb($m,1,$k1,['action'=>'save','deck'=>$deckB]);
sb($m,2,$k2,['action'=>'ready']);
MatchWithLock('SWUSim',$m,function(&$x){ $x['sideboardDeadline']=time()-1; });
$st=sb($m,1,$k1,['action'=>'status']);
$mm=SWUReadMatch($m);
check('at the deadline a submitted-but-not-ready seat plays its submitted list', ($st['nextGameName'] ?? '')!=='' && has($mm['players']['1']['currentDeck'] ?? null,'sideboard','JTL_103'), json_encode(['st'=>$st,'sb'=>$mm['players']['1']['currentDeck']['sideboard'] ?? null]));

// Clean up: the matches and their games' folders (a reused game number would follow their pointers).
foreach($MATCHES as $id){
  $x=SWUReadMatch($id);
  foreach((is_array($x)?$x['games']:[]) as $g){ $gn=preg_replace('/[^A-Za-z0-9_]/','',strval($g['gameName'] ?? '')); if($gn!=='') exec('rm -rf '.escapeshellarg(MatchGamesDir('SWUSim').'/'.$gn)); }
  exec('rm -rf '.escapeshellarg(MatchesDir('SWUSim').'/'.$id));
}
$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
foreach($fails as $f) echo "  BAD: $f  ".($notes[$f] ?? '')."\n";
echo empty($fails)?"PASS (".count($checks)." checks)\n":"FAIL: ".count($fails)." of ".count($checks)."\n";
