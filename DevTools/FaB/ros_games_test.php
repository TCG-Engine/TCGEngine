<?php
require_once __DIR__.'/ros_rules_test.php';
$failures=[];
foreach([['aurora','florian'],['aurora','florian','oscilio','verdance']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  $weapon=match($id){'aurora'=>'star_fall','florian'=>'rotwood_reaper','oscilio'=>'volzar_the_lightning_rod',default=>'staff_of_verdant_shoots'};
  AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  foreach(['ironrot_helm','ironrot_plate','ironrot_gauntlet','ironrot_legs'] as $eq)AddEquipment($p,CardID:$eq,Owner:$p,Controller:$p);
  $pool=match($id){'aurora'=>['fry_red','fry_blue','second_strike_red','flittering_charge_blue','current_funnel_blue','hocus_pocus_red','arcanic_spike_blue','arcane_seeds_life_red','rune_flash_red','high_voltage_blue'],
   'florian'=>['blossoming_decay_red','cadaverous_tilling_blue','strength_of_four_seasons_red','hocus_pocus_blue','runerager_swarm_red','sigil_of_deadwood_blue','oath_of_the_arknight_red','summers_fall_blue','earth_form_red','autumns_touch_blue'],
   'oscilio'=>['photon_splicing_red','photon_splicing_blue','trailblazing_aether_red','aether_quickening_blue','high_voltage_blue','exploding_aether_red','open_the_flood_gates_red','etchings_of_arcana_blue','sigil_of_aether_blue','comet_storm_shock_red'],
   default=>['photon_splicing_red','photon_splicing_blue','trailblazing_aether_red','exploding_aether_red','high_voltage_blue','pulsing_aether_life_red','blossoming_decay_red','cadaverous_tilling_blue','summers_fall_blue','autumns_touch_blue']};
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No ROS pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('ROS bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'ROS game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
