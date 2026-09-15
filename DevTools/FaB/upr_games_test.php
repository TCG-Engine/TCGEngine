<?php
require_once __DIR__.'/upr_rules_test.php';
foreach([2,4] as $seats){
 $arcReset($seats);foreach(FaBSeatOrder() as $seat){$z=&GetSoul($seat);$z=[];}SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$profiles=[];
 foreach(FaBLiveSeats() as $p){
  $hero=['dromai','fai','iyslander','dromai'][$p-1];$h=&GetHero($p);$h=[];AddHero($p,CardID:$hero,Owner:$p,Controller:$p);
  $weapon=match($hero){'dromai'=>'storm_of_sandikai','fai'=>'searing_emberblade','iyslander'=>'waning_moon'};AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  AddEquipment($p,CardID:'quelling_robe',Owner:$p,Controller:$p);
  $pool=match($hero){
   'dromai'=>['sweeping_blow_red','invoke_yendurai_red','rake_the_embers_red','billowing_mirage_blue','dustup_red','invoke_cromai_red','invoke_nekria_red','skittering_sands_blue','embermaw_cenipai_red','sand_cover_blue'],
   'fai'=>['ronin_renegade_red','cinderskin_devotion_blue','rise_from_the_ashes_red','mounting_anger_blue','soaring_strike_red','inflame_red','brand_with_cinderclaw_blue','stoke_the_flames_red','lava_burst_red','critical_strike_blue'],
   'iyslander'=>['ice_bolt_red','icebind_blue','aether_hail_red','frosting_blue','polar_cap_red','aether_icevein_blue','cold_snap_red','brain_freeze_blue','dampen_red','arctic_incarceration_blue']};
  for($i=0;$i<40;++$i)AddDeck($p,CardID:$pool[$i%count($pool)]);$d=&GetDeck($p);shuffle($d);DoDrawCard($p,4);$profiles[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);$dragons=0;
 for($step=0;$step<6000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('UPR match has no pending bot.');
  $acted=false;
  if(!FaBHasPendingDecision())foreach(FaBChoiceRefs($p,'Arena',['type'=>'Ally']) as $r)if(FaBWTRCanActivate($p,$r)){$acted=FaBWTRActivate($p,$r);++$dragons;break;}
  if($acted)GameAfterEngineAction([],[]);else if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('UPR bot stalled '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'UPR match exceeded step limit.');$check($dragons>0,'UPR match never attacked with a dragon.');echo "$seats-player UPR match completed in $step steps ($dragons dragon attack actions).\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
