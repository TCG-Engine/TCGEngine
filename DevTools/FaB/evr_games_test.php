<?php
require_once __DIR__.'/evr_rules_test.php';
foreach([2,4] as $seats){
 $arcReset($seats);foreach(FaBSeatOrder() as $seat){$z=&GetSoul($seat);$z=[];}SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$profiles=[];
 foreach(FaBLiveSeats() as $p){
  $hero=['briar','bravo','lexi','rhinar'][$p-1];$h=&GetHero($p);$h=[];AddHero($p,CardID:$hero,Owner:$p,Controller:$p);
  $weapon=match($hero){'briar'=>'rosetta_thorn','bravo'=>'anothos','rhinar'=>'romping_club','lexi'=>'voltaire_strike_twice'};AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  $equipment=match($hero){'briar'=>'spellbound_creepers','bravo'=>'earthlore_bounty','rhinar'=>'skull_crushers','lexi'=>'new_horizon'};AddEquipment($p,CardID:$equipment,Owner:$p,Controller:$p);
  $pool=match($hero){
   'briar'=>['swarming_gloomveil_red','shrill_of_skullform_blue','drowning_dire_red','reek_of_corruption_blue','runeblood_incantation_red','revel_in_runeblood_red','read_the_glide_path_blue','runic_reclamation_red','wax_on_blue','high_striker_red'],
   'bravo'=>['pulverize_red','thunder_quake_blue','seismic_stir_red','macho_grande_blue','steadfast_red','bingo_red','life_of_the_party_blue','smashing_good_time_red','bare_fangs_blue','wild_ride_red'],
   'lexi'=>['battering_bolt_red','fatigue_shot_blue','read_the_glide_path_red','release_the_tension_blue','rain_razors_yellow','tri_shot_blue','timidity_point_red','bingo_red','life_of_the_party_blue','high_striker_red'],
   'rhinar'=>['swing_big_red','bare_fangs_blue','wild_ride_red','bad_beats_blue','rolling_thunder_red','high_roller_blue','bingo_red','life_of_the_party_blue','smashing_good_time_red','high_striker_blue']};
  for($i=0;$i<40;++$i)AddDeck($p,CardID:$pool[$i%count($pool)]);$d=&GetDeck($p);shuffle($d);DoDrawCard($p,4);$profiles[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);$fused=0;
 for($step=0;$step<6000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('EVR match has no pending bot.');
  $acted=false;
  if(!FaBHasPendingDecision()&&FaBELEArsenalSpace($p)&&FaBELESelect($p,'Hand','','Arrow')!=='')foreach(FaBChoiceRefs($p,'Weapons',['type'=>'Bow']) as $r)if(FaBWTRCanActivate($p,$r)){$acted=FaBWTRActivate($p,$r);break;}
  if($acted)GameAfterEngineAction([],[]);else if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('EVR bot stalled '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
  if(FaBEVRCount($p,'AURAS'))++$fused;
 }
 $check(intval(GetWinner())>0,'EVR match exceeded step limit.');$check($fused>0,'EVR match never created an aura.');echo "$seats-player EVR match completed in $step steps ($fused aura observations).\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
