<?php
require_once __DIR__.'/hnt_rules_test.php';
$failures=[];
foreach([['cindra','fang'],['arakni_web_of_deceit','cindra','fang','arakni_5lp3d_7hru_7h3_cr4x']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  $weapon=match($id){'cindra'=>'kunai_of_retribution','fang'=>'obsidian_fire_vein',default=>'mark_of_the_huntsman'};
  AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  $equipment=$id==='cindra'?['leap_frog_vocal_sac','blood_splattered_vest','danger_digits','leap_frog_leggings']:($id==='fang'?['red_alert_visor','coat_of_allegiance','blade_beckoner_gauntlets','starting_point']:['mask_of_deceit','blood_splattered_vest','danger_digits','starting_point']);
  foreach($equipment as $eq)AddEquipment($p,CardID:$eq,Owner:$p,Controller:$p);
  $pool=match($id){'cindra'=>['blood_drop_red','blood_line_red','burning_blade_dance_red','demonstrate_devotion_red','fire_tenet_strike_first_red','fire_tenet_strike_first_yellow','fire_tenet_strike_first_blue','hot_on_their_heels_red','pick_up_the_point_red','pick_up_the_point_yellow','pick_up_the_point_blue','tag_the_target_red','tag_the_target_yellow','tag_the_target_blue','throw_yourself_at_them_red','throw_yourself_at_them_yellow','throw_yourself_at_them_blue','trap_and_release_red','trap_and_release_yellow','trap_and_release_blue'],
'fang'=>['affirm_loyalty_red','blistering_blade_red','endear_devotion_red','for_the_realm_red','incision_red','incision_yellow','incision_blue','scar_tissue_red','scar_tissue_yellow','scar_tissue_blue','sworn_vengeance_red','sworn_vengeance_yellow','sworn_vengeance_blue','to_the_point_red','to_the_point_yellow','to_the_point_blue'],
default=>['bite_red','bite_yellow','bite_blue','cut_deep_red','cut_deep_yellow','cut_deep_blue','mark_of_the_black_widow_red','mark_of_the_black_widow_yellow','mark_of_the_black_widow_blue','mark_the_prey_red','mark_the_prey_yellow','mark_the_prey_blue','plunge_the_prospect_red','plunge_the_prospect_yellow','plunge_the_prospect_blue','reapers_call_red','reapers_call_yellow','reapers_call_blue','scuttle_the_canal_red','scuttle_the_canal_yellow']};
  foreach($pool as $cardID)if(!CardName($cardID))throw new RuntimeException('Unknown fixture card: '.$cardID);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No HNT pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('HNT bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'HNT game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
