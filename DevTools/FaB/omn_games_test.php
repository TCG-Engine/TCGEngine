<?php
require_once __DIR__.'/omn_rules_test.php';
// Mixed OMN engine fixture, not a format-legal deck or bot profile.
$failures=[];
foreach([['aurora_emissary_of_lightning','oscilio_forked_continuum'],['aurora_emissary_of_lightning','oscilio_forked_continuum','zyggy','aurora_emissary_of_lightning']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  foreach(['boots_of_astral_sanctuary','starflow_robes','gloves_of_astral_sanctuary','helm_of_astral_sanctuary'] as $e)AddEquipment($p,CardID:$e,Owner:$p,Controller:$p);
  FaBWTRCreateArena($p,'lightning_flow');
  $pool=['shattering_flowtide_red','shattering_flowtide_blue','rush_of_power_red','starlight_road_blue','stellar_glide_red','astral_assault_blue','holo_shield_blue','flash_bolt_red','comet_collision_blue','electryn_joltstep_blue','cosmic_suture_blue','quick_succession_red','path_of_same_ends_red','visionary_of_orbits_red','dashing_flashfoot_yellow'];
  foreach($pool as $cardID)if(!CardName($cardID))throw new RuntimeException('Unknown fixture card: '.$cardID);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }

 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No OMN pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  if(in_array('--trace',$argv,true))echo json_encode(['step'=>$step,'p'=>$p,'window'=>FaBGetState()['window'],'dq'=>GetDecisionQueue($p),'stack'=>array_map(fn($o)=>$o->CardID,GetStack())])."\n";
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  $beforeState=json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]);
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('OMN bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
  if($beforeState===json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]))throw new RuntimeException('OMN bot repeated unchanged action: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'OMN game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
