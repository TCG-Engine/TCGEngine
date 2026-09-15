<?php
require_once __DIR__.'/ele_rules_test.php';
foreach([2,4] as $seats){
 $eleReset($seats);SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$profiles=[];
 foreach(FaBLiveSeats() as $p){
  $hero=['briar','oldhim','lexi','briar'][$p-1];$h=&GetHero($p);$h=[];AddHero($p,CardID:$hero,Owner:$p,Controller:$p);
  $weapon=match($hero){'briar'=>'rosetta_thorn','oldhim'=>'winters_wail','lexi'=>'voltaire_strike_twice'};AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  $equipment=match($hero){'briar'=>'spellbound_creepers','oldhim'=>'crown_of_seeds','lexi'=>'new_horizon'};AddEquipment($p,CardID:$equipment,Owner:$p,Controller:$p);
  $pool=match($hero){
   'briar'=>['explosive_growth_red','arcanic_shockwave_blue','rites_of_lightning_red','stir_the_wildwood_blue','bramble_spark_red','weave_earth_blue','autumns_touch_blue','heavens_claws_blue','channel_mount_heroic_red','lightning_surge_red'],
   'oldhim'=>['oaken_old_red','glacial_footsteps_blue','snow_under_red','entangle_blue','ice_quake_red','winters_grasp_blue','autumns_touch_blue','turn_timber_red','channel_lake_frigid_blue','frost_fang_red'],
   'lexi'=>['blizzard_bolt_red','dazzling_crescendo_blue','chilling_icevein_red','frazzle_blue','boltn_shot_red','heavens_claws_blue','winters_grasp_blue','electrify_red','weave_lightning_blue','lightning_surge_red']};
  for($i=0;$i<40;++$i)AddDeck($p,CardID:$pool[$i%count($pool)]);$d=&GetDeck($p);shuffle($d);DoDrawCard($p,4);$profiles[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);$fused=0;
 for($step=0;$step<6000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('ELE match has no pending bot.');
  $acted=false;
  if(!FaBHasPendingDecision()&&FaBELEArsenalSpace($p)&&FaBELESelect($p,'Hand','','Arrow')!=='')foreach(FaBChoiceRefs($p,'Weapons',['type'=>'Bow']) as $r)if(FaBWTRCanActivate($p,$r)){$acted=FaBWTRActivate($p,$r);break;}
  if($acted)GameAfterEngineAction([],[]);else if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('ELE bot stalled '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
  if(FaBELECount($p,'FUSED'))++$fused;
 }
 $check(intval(GetWinner())>0,'ELE match exceeded step limit.');$check($fused>0,'ELE match never fused.');echo "$seats-player ELE match completed in $step steps ($fused fusion observations).\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
