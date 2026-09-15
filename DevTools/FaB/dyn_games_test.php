<?php
require_once __DIR__.'/dyn_rules_test.php';
$fixtures=[
 ['arakni','kano'],
 ['arakni','ira_crimson_haze','dash','kano'],
 ['yoji_royal_protector','viserai','dorinthea','prism'],
];
foreach($fixtures as $dynHeroes){
 $seats=count($dynHeroes);$dynReset($seats);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);$profiles=[];
 foreach($dynHeroes as $index=>$dynHero){
  $p=$index+1;
  foreach(['Hero','Equipment','Weapons','Hand','Deck','Arena','Soul'] as $z){$get='Get'.$z;$v=&$get($p);$v=[];}
  AddHero($p,CardID:$dynHero,Owner:$p,Controller:$p);
  [$weapon,$pool]=match($dynHero){
   'arakni'=>['spiders_bite',['annihilate_the_armed_red','fleece_the_frail_blue','rob_the_rich_red','nix_the_nimble_blue','leave_no_witnesses_red','surgical_extraction_blue','cut_to_the_chase_red','shred_blue','eradicate_yellow','plunder_the_poor_red']],
   'ira_crimson_haze'=>['harmonized_kodachi',['predatory_streak_red','pouncing_qi_blue','qi_unleashed_red','flex_claws_blue','roar_of_the_tiger_yellow','tiger_swipe_red','blessing_of_qi_blue','mindstate_of_tiger_blue','qi_unleashed_blue','pouncing_qi_red']],
   'dash'=>['hanabi_blaster',['crankshaft_red','crankshaft_blue','jump_start_red','jump_start_blue','scramble_pulse_red','scramble_pulse_blue','urgent_delivery_red','urgent_delivery_blue','bios_update_red','hyper_driver_blue']],
   'kano'=>['surgent_aethertide',['aether_quickening_red','aether_quickening_blue','prognosticate_red','sap_blue','mind_warp_yellow','swell_tidings_red','tempest_aurora_red','tempest_aurora_blue','blessing_of_aether_red','blessing_of_aether_blue']],
   'yoji_royal_protector'=>['anothos',['shield_wall_red','shield_bash_blue','blessing_of_patience_red','blessing_of_patience_blue','buckle_blue','never_yield_blue','withstand_red','macho_grande_red','thunder_quake_blue','pulverize_red']],
   'viserai'=>['annals_of_sutcliffe',['deathly_duet_red','deathly_duet_blue','aether_slash_red','aether_slash_blue','cryptic_crossing_yellow','runic_reaping_red','runic_reaping_blue','sky_fire_lanterns_red','blessing_of_occult_blue','looming_doom_blue']],
   'dorinthea'=>['jubeel_spellbane',['ironsong_pride_red','precision_press_red','precision_press_blue','puncture_red','puncture_blue','visit_the_imperial_forge_red','visit_the_imperial_forge_blue','blessing_of_steel_red','blessing_of_steel_blue','felling_swing_blue']],
   'prism'=>['luminaris',['spectral_prowler_red','spectral_prowler_blue','spectral_rider_red','spectral_rider_blue','spectral_procession_red','blessing_of_spirits_red','blessing_of_spirits_blue','water_glow_lanterns_yellow','tome_of_aeo_blue','invoke_suraya_yellow']],
  };
  AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);AddEquipment($p,CardID:$dynHero==='yoji_royal_protector'?'seasoned_saviour':'spell_fray_cloak',Owner:$p,Controller:$p);FaBDYNSetup($p);
  for($i=0;$i<40;++$i)AddDeck($p,CardID:$pool[$i%count($pool)]);
  $deck=&GetDeck($p);EngineShuffle($deck,true);DoDrawCard($p,4);$profiles[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);$actions=0;
 for($step=0;$step<7000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('DYN fixture has no acting seat.');$acted=false;$d=GetDecisionQueue($p)[0]??null;
  if($d&&$d->Type==='NAMECARD'){$answer($p,'Ravenous Rabble');$acted=true;}
  if(!$d&&FaBHasPendingDecision()===false){
   foreach(FaBChoiceRefs($p,'Arena') as $r)if(FaBWTRCanActivate($p,$r)){FaBWTRActivate($p,$r);$acted=true;break;}
  }
  if($acted)GameAfterEngineAction([],[]);elseif(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('DYN fixture stalled: '.json_encode(['step'=>$step,'p'=>$p,'s'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
  ++$actions;
 }
 $check(intval(GetWinner())>0,'DYN fixture exceeded step limit: '.implode('/',$dynHeroes));echo "$seats-player DYN ".implode('/',$dynHeroes)." finished in $actions actions.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
