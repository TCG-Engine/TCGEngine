<?php
require_once __DIR__.'/sup_rules_test.php';
$failures=[];
foreach([['pleiades_superstar','kayo_underhanded_cheat'],['pleiades','kayo_strong_arm','tuffnut','lyath_goldmane']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  $revered=FaBHasType($id,'Revered');$guardian=FaBHasType($id,'Guardian');
  foreach([$revered?'helm_of_the_adored':'horns_of_the_despised','ironrot_plate','punching_gloves','toby_jugs'] as $e)AddEquipment($p,CardID:$e,Owner:$p,Controller:$p);
  $pool=$revered?['comeback_kid_red','comeback_kid_blue','fight_from_behind_red','fight_from_behind_blue','heroic_pose_red','empowering_ruckus_yellow','tough_smashup_blue','humble_entrance_blue','cheers_blue','dig_in_red']:['mocking_blow_red','low_blow_red','instill_fear_red','villainous_pose_blue','clench_the_upper_hand_blue','goon_beatdown_blue','cruel_ambition_red','booze_blue','take_that_blue','goon_tactics_blue'];
  $pool=array_merge($pool,$guardian?['act_of_glory_blue','tension_in_the_air_blue','full_of_bravado_red','story_beats_blue','short_shrift_yellow','wee_wrecking_ball_yellow']:['buckwild_blue','flex_speed_red','flex_strength_red','high_pitched_howl_blue','rough_up_red','vigorous_smashup_blue'],['bluster_buff_red','look_tuff_red','chest_puff_red','punch_above_your_weight_red']);
  foreach($pool as $cardID)if(!CardName($cardID))throw new RuntimeException('Unknown fixture card: '.$cardID);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }

 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No SUP pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  $beforeState=json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]);
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('SUP bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
  if($beforeState===json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]))throw new RuntimeException('SUP bot repeated unchanged action: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'SUP game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
