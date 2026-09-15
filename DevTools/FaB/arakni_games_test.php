<?php
require_once __DIR__.'/arakni_test.php';
foreach([['arakni','fai'],['arakni','arakni','arakni','arakni'],['arakni','professor','dromai','lexi']] as $profiles){
 if(in_array('--mixed',$argv??[],true)&&$profiles!==['arakni','professor','dromai','lexi'])continue;
 $seats=count($profiles);$dynReset($seats);SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$bots=[];
 foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);AddHealth($p,max(1,intval(CardHealth($d['hero']))?:20));AddResources($p,0);$h=&GetHero($p);$h=[];AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);
  foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id);$z=&GetDeck($p);shuffle($z);DoDrawCard($p,4);$bots[$p]=$profile;
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$contracts=[];$silverTurns=[];
 for($step=0;$step<8000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();
  if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Arakni bot stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
  if(FaBIsArakniBot($p)){
   if(count(FaBMONArena($p,'silver'))>0)$silverTurns[$p.':'.GetTurnNumber()]=true;
   $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&intval($s['attacker'])===$p&&FaBDYNContract($a['object']->CardID)!=='')$contracts[intval($a['object']->UniqueID)]=true;
  }
 }
 $check(intval(GetWinner())>0,'Arakni game exceeded limit.');$check(count($silverTurns)>0||count(array_filter($profiles,fn($profile)=>$profile==='arakni'))===1&&count($profiles)===4,'Arakni never earned Silver in the contract fixtures.');$check(count($contracts)>0,'Arakni never attacked with contracts.');
 echo "$seats-player ".implode('/',$profiles).": $step steps; ".count($silverTurns)." Silver turns, ".count($contracts)." contract attacks.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
