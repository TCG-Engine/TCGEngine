<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_sideboard_carries_forward.php
//
// THE GAME-3 SIDEBOARD MUST START FROM THE GAME-2 LIST, NOT THE MATCH-START LIST.
//
// Reported bug: "Sideboards are not held going into Game 3 Sideboard menu. player has to re-do
// everything they did before Game 2."
//
// Cause: MatchMaybeSpawnAfterSideboard spawned game 2 from the submitted decks and then
// `unset($mm['sideboard'], …)`, so nothing survived; Sideboard.php seeds its editor from
// $m['players'][seat]['originalDeck'], which is by definition the list registered at match START.
// Fix: the spawn now records each seat's submitted list as players[seat]['currentDeck'], and both
// sims' Sideboard.php read `currentDeck ?? originalDeck`.
//
// This test drives the REAL submit endpoint so it covers the whole path, and asserts on the match
// record rather than on scraped HTML — the record is what Sideboard.php reads.
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _CfP { private $seat; private $key; private $link;
  function __construct($s,$l){ $this->seat=$s; $this->link=$l; $this->key='cf'.$s.uniqid(); }
  function getGamePlayerID(){ return $this->seat; } function setGamePlayerID($x){ $this->seat=$x; }
  function getAuthKey(){ return $this->key; } function getDeckLink(){ return $this->link; }
  function getPreconstructedDeck(){ return ''; } }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
function _mk($cards,$tailA,$tailB){
  $dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c) $dl[]="3 $c";
  $dl[]="1 $tailA"; $dl[]="1 $tailB"; return implode("\n",$dl);
}
// Three distinguishable 50-card lists: the registered one, the game-2 one, and a decoy.
$deckOriginal = _mk($cards,'JTL_103','LOF_103');
$deckGame2    = _mk($cards,'SEC_103','LAW_103');

$p1=new _CfP(1,$deckOriginal); $p2=new _CfP(2,$deckOriginal);
$lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo3'; $lobby->players=[$p1,$p2];
$matchId=SWUCreateMatchFromLobby($lobby);
$g1=(SWUReadMatch($matchId))['games'][0]['gameName'];

function _sub($matchId,$seat,$key,$deck){
  $ch=curl_init('http://localhost/TCGEngine/SWUSim/SubmitSideboard.php');
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>20,
    CURLOPT_POSTFIELDS=>http_build_query(['matchId'=>$matchId,'playerID'=>$seat,'authKey'=>$key,'deck'=>$deck])]);
  $o=curl_exec($ch); curl_close($ch); return json_decode($o,true);
}

$checks=[];

// ── Game 1 ends; both seats sideboard into game 2. Seat 1 CHANGES its list, seat 2 keeps its own. ──
SWURecordGameResult($matchId,$g1,2); SWUBeginSideboarding($matchId,1);
$m=SWUReadMatch($matchId);
$checks['no currentDeck before any sideboard'] = !isset($m['players']['1']['currentDeck']);

_sub($matchId,1,$p1->getAuthKey(),$deckGame2);
$r2=_sub($matchId,2,$p2->getAuthKey(),$deckOriginal);
$checks['game2 spawned'] = !empty($r2['nextGameName']);

$m=SWUReadMatch($matchId);
// THE FIX: the submitted list survives the spawn's unset().
$cur1=$m['players']['1']['currentDeck'] ?? null;
$checks['seat1 currentDeck recorded'] = is_array($cur1);
$checks['seat1 currentDeck is the GAME-2 list'] =
  is_array($cur1) && in_array('SEC_103',$cur1['mainDeck'] ?? [],true) && !in_array('JTL_103',$cur1['mainDeck'] ?? [],true);
// originalDeck must NOT be rewritten — other readers depend on it meaning "as registered".
$orig1=$m['players']['1']['originalDeck'] ?? null;
$checks['seat1 originalDeck untouched'] =
  is_array($orig1) && in_array('JTL_103',$orig1['mainDeck'] ?? [],true);
$checks['seat2 currentDeck recorded'] = is_array($m['players']['2']['currentDeck'] ?? null);

// ── Game 2 ends; the GAME-3 sideboard must now seed from seat 1's game-2 list. ──
$g2=$r2['nextGameName'];
SWURecordGameResult($matchId,$g2,1); SWUBeginSideboarding($matchId,2);
$m=SWUReadMatch($matchId);
// This is exactly the read Sideboard.php performs.
$p=$m['players']['1'] ?? [];
$seed=$p['currentDeck'] ?? $p['originalDeck'] ?? null;
$checks['game3 menu seeds from the GAME-2 list'] =
  is_array($seed) && in_array('SEC_103',$seed['mainDeck'] ?? [],true) && !in_array('JTL_103',$seed['mainDeck'] ?? [],true);

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails) ? "PASS (".count($checks)." checks)\n"
                   : "FAIL: ".implode(', ',$fails)."\n";
