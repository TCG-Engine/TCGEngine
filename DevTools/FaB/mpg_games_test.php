<?php
require_once __DIR__.'/mpg_rules_test.php';
$failures=[];
foreach([['valda_seismic_impact','bravo_showstopper'],['valda_brightaxe','bravo','victor_goldmane','jarl_vetreidi']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  AddWeapons($p,CardID:'titans_fist',Owner:$p,Controller:$p);
  foreach(['testament_of_valahai','richter_scale','gauntlet_of_boulderhold','craterhoof'] as $e)AddEquipment($p,CardID:$e,Owner:$p,Controller:$p);
  $pool=['aftershock_red','aftershock_blue','clash_of_mountains_blue','clash_of_bravado_yellow','test_of_iron_grip_red','pec_perfect_red','little_big_foot_red','grind_them_down_blue','overswing_blue','rubble_raiser_blue','hostile_encroachment_red','tectonic_instability_blue','promising_terrain_blue','geyser_of_seismic_stirrings_blue','seismic_shelter_blue','daily_grind_blue','call_for_backup_red','draw_a_crowd_blue','crash_and_bash_blue','seismic_eruption_yellow'];
  foreach($pool as $cardID)if(!CardName($cardID))throw new RuntimeException('Unknown fixture card: '.$cardID);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }

 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No MPG pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  $beforeState=json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]);
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('MPG bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
  if($beforeState===json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]))throw new RuntimeException('MPG bot repeated unchanged action: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'MPG game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
