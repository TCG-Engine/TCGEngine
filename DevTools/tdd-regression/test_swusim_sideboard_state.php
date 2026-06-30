<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_sideboard_state.php
header('Content-Type: text/plain');
include __DIR__ . '/../../SWUSim/MatchFlow.php';

class _SBPlayer {
    private $seat; private $key; private $link;
    public function __construct($seat,$link){ $this->seat=$seat; $this->link=$link; $this->key='sb'.$seat.uniqid(); }
    public function getGamePlayerID(){return $this->seat;} public function setGamePlayerID($s){$this->seat=$s;}
    public function getAuthKey(){return $this->key;} public function getDeckLink(){return $this->link;}
    public function getPreconstructedDeck(){return '';}
}
$cards=['JTL_100','LOF_100','SEC_100','LAW_100','ASH_100','IBH_010','JTL_101','LOF_101','SEC_101','LAW_101','ASH_101','IBH_011','JTL_102','LOF_102','SEC_102','LAW_102'];
$dl=["Leader","JTL_001","Base","JTL_023","Deck"]; foreach($cards as $c)$dl[]="3 $c"; $dl[]="1 JTL_103"; $dl[]="1 LOF_103";
$deck=implode("\n",$dl);
$lobby=new stdClass(); $lobby->isPrivate=false; $lobby->format='premier'; $lobby->queueType='bo3';
$lobby->players=[new _SBPlayer(1,$deck), new _SBPlayer(2,$deck)];
$matchId=SWUCreateMatchFromLobby($lobby);
$g1=(SWUReadMatch($matchId))['games'][0]['gameName'];

$checks=[];
SWURecordGameResult($matchId,$g1,2);
SWUBeginSideboarding($matchId,1);
$m=SWUReadMatch($matchId);
$checks['state sideboarding'] = ($m['state']??'')==='sideboarding';
$checks['not both ready'] = SWUSideboardBothReady($m)===false;

$resolved = SWUResolveDeckInput($deck); // legal adjusted deck (no changes is allowed)
SWUSubmitSideboardDeck($matchId,1,$resolved);
$m=SWUReadMatch($matchId);
$checks['seat1 ready'] = SWUSideboardSeatReady($m,1)===true;
$checks['still not both'] = SWUSideboardBothReady($m)===false;
$checks['no game2 yet'] = count($m['games'])===1;

SWUSubmitSideboardDeck($matchId,2,$resolved);
$m=SWUReadMatch($matchId);
$checks['both ready'] = SWUSideboardBothReady($m)===true;

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails)?"PASS (".count($checks)." checks)\n":"FAIL: ".implode(', ',$fails)."\n";
