<?php
require_once __DIR__.'/pen_rules_test.php';
// Mixed PEN engine fixture, not a format-legal deck or bot profile.
$failures=[];
foreach([['bravo','rhinar'],['bravo','rhinar','bravo','rhinar']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  foreach(['basalt_boots','glory_plate','unyielding_grip','myrkhellir_helm'] as $e)AddEquipment($p,CardID:$e,Owner:$p,Controller:$p);
  $pool=['distant_rumbling_red','distant_rumbling_blue','rites_of_earthlore_red','rites_of_earthlore_blue','sense_weakness_blue','seismic_shift_red','future_sight_blue','pound_of_flesh_blue','concoct_disorder_red','rockyard_rodeo_blue','cloud_cover_red','walk_in_my_shoes_yellow','cheating_scoundrel_red','aggressive_pounce_red','reckless_arithmetic_blue'];
  foreach($pool as $cardID)if(!CardName($cardID))throw new RuntimeException('Unknown fixture card: '.$cardID);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }

 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No PEN pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  if(in_array('--trace',$argv,true))echo json_encode(['step'=>$step,'p'=>$p,'window'=>FaBGetState()['window'],'dq'=>GetDecisionQueue($p),'stack'=>array_map(fn($o)=>$o->CardID,GetStack())])."\n";
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  $beforeState=json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]);
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('PEN bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
  if($beforeState===json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]))throw new RuntimeException('PEN bot repeated unchanged action: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'PEN game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
