<?php
require_once __DIR__.'/uzuri_test.php';
foreach([['uzuri','fai'],['uzuri','uzuri','uzuri','uzuri'],['arakni','uzuri','dromai','lexi']] as $profiles){
 $n=count($profiles);$outReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);mt_srand(20260915);$bots=[];
 foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);
  foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory'] as $z){$get='Get'.$z;$v=&$get($p);$v=[];}
  AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($d['hero']))?:20);AddResources($p,0);
  foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id,Owner:$p);$uzuriDeckRef=&GetDeck($p);shuffle($uzuriDeckRef);unset($uzuriDeckRef);DoDrawCard($p,4);$bots[$p]=$profile;
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$swaps=[];
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();
  if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Uzuri stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
  $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&FaBIsUzuriBot(intval($s['attacker']))&&($a['object']->FromZone??'')==='Banish')$swaps[intval($a['object']->UniqueID)]=true;
 }
 $check(intval(GetWinner())>0,'Uzuri game exceeded step limit.');$check(count($swaps)>0,'Uzuri never swapped.');
 echo implode('/',$profiles).": $step actions, ".count($swaps)." swaps.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
