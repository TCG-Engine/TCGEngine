<?php
require_once __DIR__.'/sea_rules_test.php';
$failures=[];
foreach([['gravy_bones','marlynn'],['gravy_bones','marlynn','puffin','scurv_stowaway']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  $weapon=match($id){'marlynn'=>'hammerhead_harpoon_cannon','puffin'=>'spitfire','scurv_stowaway'=>'talishar_the_lost_prince',default=>''};
  if($weapon!=='')AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  if($id==='puffin')AddEquipment($p,CardID:'polly_cranka',Owner:$p,Controller:$p);
  if($id==='scurv_stowaway')AddEquipment($p,CardID:'sticky_fingers',Owner:$p,Controller:$p);
  $pool=match($id){
   'gravy_bones'=>['barnacle_yellow','chowder_hearty_cook_yellow','limpit_hop_a_long_yellow','oysten_heart_of_gold_yellow','riggermortis_yellow','swabbie_yellow','angry_bones_red','jittery_bones_red','restless_bones_blue','chart_the_high_seas_blue','give_no_quarter_blue','fools_gold_yellow','fiddlers_green_blue','sea_floor_salvage_blue','strike_gold_red','saltwater_swell_blue'],
   'marlynn'=>['call_in_the_big_guns_red','call_in_the_big_guns_blue','red_fin_harpoon_blue','blue_fin_harpoon_blue','yellow_fin_harpoon_blue','dry_powder_shot_red','swift_shot_red','king_shark_harpoon_red','hook_blue','line_blue','sinker_blue','gold_the_tip_yellow','monkey_powder_red','fools_gold_yellow','strike_gold_red','portside_exchange_blue'],
   'puffin'=>['copper_cog_blue','copper_cog_blue','cloud_skiff_red','sky_skimmer_red','cogwerx_dovetail_red','cogwerx_zeppelin_red','cloud_city_steamboat_blue','golden_tipple_blue','cogwerx_workshop_blue','cog_in_the_machine_red','teeth_of_the_cog_red','pinion_sentry_blue','goldwing_turbine_blue','tighten_the_screws_red','fools_gold_yellow','strike_gold_red'],
   default=>['money_or_your_life_red','money_or_your_life_blue','thievn_varmints_red','nimby_red','nimby_blue','nimblism_red','nimblism_blue','swindlers_grift_yellow','gold_hunter_lightsail_yellow','gold_hunter_longboat_yellow','fools_gold_yellow','sea_legs_yellow','not_so_fast_yellow','mutiny_on_the_swiftwater_blue','tip_the_barkeep_blue','strike_gold_red']};
  foreach($pool as $cardID)if(!CardName($cardID))throw new RuntimeException('Unknown fixture card: '.$cardID);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }
 AddArena(1,CardID:'treasure_island',Owner:1,Controller:1);
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No SEA pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('SEA bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'SEA game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
