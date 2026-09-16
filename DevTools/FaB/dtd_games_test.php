<?php
require_once __DIR__.'/dtd_rules_test.php';
$fixtures=[['vynnset','prism_advent_of_thrones'],['levia','prism_advent_of_thrones','boltyn','vynnset']];
foreach($fixtures as $heroes){
 $n=count($heroes);$dtdReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);mt_srand(20260915);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory'] as $zone){$get='Get'.$zone;$zoneRef=&$get($p);$zoneRef=[];}unset($zoneRef);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);
  $weapon=['vynnset'=>'flail_of_agony','prism_advent_of_thrones'=>'luminaris_celestial_fury','boltyn'=>'beaming_blade','levia'=>'hell_hammer'][$id];AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  $equipment=match($id){'vynnset'=>['dyadic_carapace','grimoire_of_the_haunt'],'prism_advent_of_thrones'=>['empyrean_rapture'],'boltyn'=>['soulbond_resolve','radiant_flow'],'levia'=>['cloak_of_darkness','spoiled_skull']};foreach($equipment as $e)AddEquipment($p,CardID:$e,Owner:$p,Controller:$p);
  if($id==='levia')AddInventory($p,CardID:'levia_redeemed',Owner:$p,Controller:$p);
  $pool=match($id){
   'vynnset'=>['envelop_in_darkness_red','envelop_in_darkness_blue','putrid_stirrings_blue','putrid_stirrings_red','deathly_wail_red','deathly_wail_blue','deathly_delight_red','rift_skitter_blue','vantom_wraith_red','vantom_banshee_blue'],
   'prism_advent_of_thrones'=>['wartune_herald_yellow','herald_of_protection_yellow','herald_of_ravages_blue','herald_of_tenacity_red','herald_of_rebirth_blue','figment_of_protection_yellow','figment_of_erudition_yellow','figment_of_war_yellow','angelic_descent_blue','angelic_wrath_yellow'],
   'boltyn'=>['beaming_bravado_red','beaming_bravado_blue','glaring_impact_red','glaring_impact_blue','light_the_way_red','banneret_of_courage_yellow','banneret_of_resilience_yellow','banneret_of_vigor_yellow','resounding_courage_blue','charge_of_the_light_brigade_red'],
   'levia'=>['ram_raider_red','ram_raider_blue','shaden_swing_blue','shaden_swing_red','tribute_to_demolition_red','tribute_to_demolition_blue','battlefield_breaker_red','battlefield_breaker_blue','wall_breaker_red','slithering_shadowpede_blue']};
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$awakenings=0;$gates=[];
 for($step=0;$step<8000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No DTD pending bot.');$GLOBALS['playerID']=$p;$dq=GetDecisionQueue($p);$d=$dq[0]??null;
  if($d&&$d->Type==='MZMODAL'&&$d->Tooltip==='Search_for_figment'){$answer($p,'1');GameAfterEngineAction([],[]);continue;}
  if($d&&$d->Type==='MZCHOOSE'&&$d->Tooltip==='Banish_from_hand'){ $runes=array_filter(explode('&',$d->Param),fn($r)=>FaBHasKeyword(FaBIdentityFromMZ($r)['object'],'Rune Gate'));if($runes){$answer($p,array_values($runes)[0]);GameAfterEngineAction([],[]);continue;} }
  $h=GetHero($p)[0];if(!$d&&str_starts_with($h->CardID,'prism_')&&FaBWTRCanActivate($p,$ref($h))){FaBWTRActivate($p,$ref($h));++$awakenings;GameAfterEngineAction([],[]);continue;}
  if(!$d){$s=FaBGetState();foreach(FaBChoiceRefs($p,'Banish') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBDTDRuneGate($p,$o)&&CanPlayCard($p,$r))$gates[intval($o->UniqueID)]=true;}}
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('DTD bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'DTD game exceeded action limit.');$check(count($gates)>0,'DTD fixture never made Rune Gate available.');
 echo implode('/',$heroes).": $step actions; $awakenings awaken activations; ".count($gates)." rune-gate candidates.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
