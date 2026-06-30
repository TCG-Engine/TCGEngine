<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_rematch.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _RmP { private $seat;private $key;private $link;
  function __construct($s,$l){$this->seat=$s;$this->link=$l;$this->key='rm'.$s.uniqid();}
  function getGamePlayerID(){return $this->seat;} function setGamePlayerID($x){$this->seat=$x;}
  function getAuthKey(){return $this->key;} function getDeckLink(){return $this->link;} function getPreconstructedDeck(){return '';} }

$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$dl[]="3 $c"; $dl[]="1 JTL_103"; $dl[]="1 LOF_103"; $deck=implode("\n",$dl);

function _mkFinishedBo1($deck) {
  $lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo1';
  $lobby->players=[new _RmP(1,$deck), new _RmP(2,$deck)];
  $id=SWUCreateMatchFromLobby($lobby);
  $g=(SWUReadMatch($id))['games'][0]['gameName'];
  SWURecordGameResult($id,$g,1); // Bo1 complete, seat 1 won
  return $id;
}

$checks=[];
// Quick Bo1 rematch: one side → nothing; both → new Bo1, game1 spawned (no sideboard).
$oldId=_mkFinishedBo1($deck);
SWURequestRematch($oldId,1,1,false);
$checks['one side no rematch'] = (SWUAcceptRematch($oldId)===null);
SWURequestRematch($oldId,2,1,false);
$newId=SWUAcceptRematch($oldId);
$checks['new match created'] = is_string($newId) && $newId!==$oldId;
$nm=SWUReadMatch($newId);
$checks['new is bo1'] = intval($nm['bestOf'])===1;
$checks['new has game1'] = !empty($nm['games'][0]['gameName']);
$checks['quick not sideboarding'] = ($nm['state'] ?? '')!=='sideboarding';
$checks['same decks carried'] = ($nm['players']['1']['originalDeck']['leader'] ?? '')==='JTL_001';

// Full Bo3 rematch: both → new Bo3, sideboarding before game 1.
$oldId2=_mkFinishedBo1($deck);
SWURequestRematch($oldId2,1,3,true); SWURequestRematch($oldId2,2,3,true);
$newId2=SWUAcceptRematch($oldId2); $nm2=SWUReadMatch($newId2);
$checks['full bo3 rematch'] = intval($nm2['bestOf'])===3;
$checks['full sideboarding'] = ($nm2['state'] ?? '')==='sideboarding';

// Mismatched bestOf (1 vs 3) → no pairing.
$oldId3=_mkFinishedBo1($deck);
SWURequestRematch($oldId3,1,1,false); SWURequestRematch($oldId3,2,3,false);
$checks['mismatch no rematch'] = (SWUAcceptRematch($oldId3)===null);

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails)?"PASS (".count($checks)." checks)\n":"FAIL: ".implode(', ',$fails)."\n";
