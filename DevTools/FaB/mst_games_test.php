<?php
require_once __DIR__.'/mst_rules_test.php';
$failures=[];
foreach([['enigma','nuu'],['zen','nuu','enigma','zen']] as $heroes){
 $n=count($heroes);if(in_array('--upf',$argv,true)&&$n!==4)continue;$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  $weapon=match($id){'enigma'=>'cosmo_scroll_of_ancestral_tapestry','nuu'=>'beckoning_mistblade',default=>'tiger_taming_khakkara'};
  AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  foreach(['skycrest_keikoi','skybody_keikoi','skyhold_keikoi','skywalker_keikoi'] as $eq)AddEquipment($p,CardID:$eq,Owner:$p,Controller:$p);
  $base=match($id){'enigma'=>['waxing_specter_red','haunting_specter_red','single_minded_determination_red','manifestation_of_miragai_blue','spectral_manifestations_red','astral_etchings_blue','solitary_companion_blue'],
   'nuu'=>['desires_of_flesh_red','bonds_of_attraction_blue','double_trouble_red','venomous_bite_blue','impulsive_desire_red','art_of_desire_mind_blue','pick_to_pieces_red'],
   default=>['companion_of_the_claw_red','harmony_of_the_hunt_blue','biting_breeze_red','chase_the_tail_blue','tooth_and_claw_red','tiger_form_incantation_blue','wind_chakra_red']};
  $pool=array_merge($base,['homage_to_ancestors_blue','rising_tide_blue','droplet_blue','levels_of_enlightenment_blue','evasive_leap_red']);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);FaBMSTSetup($p);$bots[$p]='professor';
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No MST pending bot.');$GLOBALS['playerID']=$p;
  // Use an aggressive policy after two opening rounds to avoid defensive fatigue mirrors.
  if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&(count(FaBChoiceRefs($p,'Deck'))<4||intval(GetTurnNumber())>=8)){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
  $arenaAttack=false;if(!GetDecisionQueue($p))foreach(FaBChoiceRefs($p,'Arena') as $r){$f=FaBIdentityFromMZ($r);if(FaBMONArenaCanAttack($p,$f)&&FaBMSTWard($p,$f['object'])+FaBMSTAttackCounters($f['object'])>0){$arenaAttack=FaBMONArenaAttack($p,$f);break;}}
  if($arenaAttack){GameAfterEngineAction([],[]);continue;}
  if($step%250===0)echo 'Progress '.$n.' seats: '.$step.' actions, turn '.GetTurnNumber()."\n";
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('MST bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'MST game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
