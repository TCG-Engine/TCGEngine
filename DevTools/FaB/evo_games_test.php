<?php
require_once __DIR__.'/evo_rules_test.php';
$failures=[];
foreach([['teklovossen','maxx_nitro'],['dash_database','maxx_nitro','teklovossen','professor_teklovossen']] as $heroes){
 $n=count($heroes);$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260916);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  AddWeapons($p,CardID:match($id){'dash_database'=>'symbiosis_shot','maxx_nitro'=>'banksy','professor_teklovossen'=>'teklo_blaster',default=>'teklo_leveler'},Owner:$p,Controller:$p);
  foreach(['head','chest','arms','legs'] as $slot)AddEquipment($p,CardID:'cogwerx_base_'.$slot,Owner:$p,Controller:$p);FaBDYNSetup($p);
  $pool=['zero_to_fifty_red','zero_to_fifty_blue','full_tilt_red','full_tilt_blue','evo_sentry_base_head_red','evo_sentry_base_chest_red','evo_sentry_base_arms_red','evo_sentry_base_legs_red','mechanical_strength_red','mechanical_strength_blue','liquid_cooled_mayhem_blue','terminator_tank_red','scrap_prospector_blue','scrap_hopper_red','boom_grenade_red','penetration_script_yellow','mini_forcefield_red','twin_drive_red','gas_guzzler_blue','big_bertha_blue'];
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$cranks=0;$evoPlays=0;$dashPlays=0;
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No EVO pending bot.');$GLOBALS['playerID']=$p;$d=GetDecisionQueue($p)[0]??null;
  if($d&&$d->Type==='MZMODAL'&&$d->Tooltip==='Remove_steam_to_crank'){$answer($p,'0');++$cranks;GameAfterEngineAction([],[]);continue;}
  if(!$d){$taken=false;$s=FaBGetState();foreach(FaBChoiceRefs($p,'Deck') as $r){if(CanPlayCard($p,$r)){DoPlayCard($p,$r);++$dashPlays;$taken=true;break;}}if($taken){GameAfterEngineAction([],[]);continue;}
   $h=GetHero($p)[0];if(FaBEVOMaxx($p)&&FaBWTRCanActivate($p,$ref($h))){FaBWTRActivate($p,$ref($h));GameAfterEngineAction([],[]);continue;}
   foreach(['Hand','Banish'] as $z)foreach(FaBChoiceRefs($p,$z,['type'=>'Evo']) as $r)if(CanPlayCard($p,$r)&&FaBEvoBase($p,FaBIdentityFromMZ($r)['object'])){DoPlayCard($p,$r);++$evoPlays;$taken=true;break 2;}if($taken){GameAfterEngineAction([],[]);continue;}
  }
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('EVO bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'EVO game exceeded action limit.');$check($evoPlays>0&&$cranks>0,'EVO fixture missed transformations or crank.');if($n===4)$check($dashPlays>0,'EVO fixture missed Dash top play.');echo implode('/',$heroes).": $step actions; $evoPlays Evo plays; $cranks cranks; $dashPlays Dash plays.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
