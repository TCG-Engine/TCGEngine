<?php
require_once __DIR__.'/maxx_test.php';
foreach([['maxx','maxx'],['maxx','professor']] as $profiles){
 $n=count($profiles);$evoReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);mt_srand(20260915);$bots=[];
 foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);
  foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory'] as $z){$get='Get'.$z;$v=&$get($p);$v=[];}
  AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($d['hero']))?:20);AddResources($p,0);
  foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id,Owner:$p);$maxxDeckRef=&GetDeck($p);shuffle($maxxDeckRef);unset($maxxDeckRef);DoDrawCard($p,4);$bots[$p]=$profile;
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$banks=[];$cranks=0;
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  if($step%250===0)echo implode('/',$profiles)." step $step turn ".GetTurnNumber()." health ".GetHealth(1).'/'.GetHealth(2)." banks ".count($banks)."\n";
  $p=BotControllerPendingPlayerForClient();
  if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Maxx stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
  $s=FaBGetState();foreach(FaBLiveSeats() as $seat){foreach(FaBChoiceRefs($seat,'Weapons',['base'=>'bank_breaker']) as $r)$banks[intval(FaBIdentityFromMZ($r)['object']->UniqueID)]=true;if(FaBEVOEffect($seat,'CRANKED'))++$cranks;}
 }
 // Construction and two follow-up attacks are required by the prepared-position
 // regression in maxx_test.php; random draws need not assemble that package.
 $check(intval(GetWinner())>0,'Maxx game exceeded step limit.');
 echo implode('/',$profiles).": $step actions, ".count($banks)." Bank Breakers.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
