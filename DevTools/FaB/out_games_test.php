<?php
require_once __DIR__.'/out_rules_test.php';
$fixtures=[['uzuri','riptide'],['arakni_solitary_confinement','katsu','riptide','uzuri']];
foreach($fixtures as $outHeroes){
 $n=count($outHeroes);$outReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);$profiles=[];
 foreach($outHeroes as $i=>$outHero){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory'] as $z){$get='Get'.$z;$v=&$get($p);$v=[];}
  AddHero($p,CardID:$outHero,Owner:$p,Controller:$p);AddHealth($p,20);
  $assassin=in_array($outHero,['uzuri','arakni_solitary_confinement'],true);
  $weapon=$outHero==='riptide'?'barbed_castaway':($assassin?'nerve_scalpel':'harmonized_kodachi');AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  $pool=$assassin?['infect_red','wither_red','sedate_blue','prowl_blue','isolate_red','sneak_attack_red','death_touch_red','codex_of_frailty_yellow','short_and_sharp_blue','infect_blue']:
   ($outHero==='riptide'?['infecting_shot_red','withering_shot_red','sedation_shot_blue','falcon_wing_blue','frailty_trap_red','bloodrot_trap_red','inertia_trap_red','infectious_host_red','cut_down_to_size_blue','codex_of_bloodrot_yellow']:
   ['head_jab_red','surging_strike_blue','descendent_gustwave_red','bonds_of_ancestry_blue','dishonor_blue','be_like_water_red','deadly_duo_blue','spinning_wheel_kick_red','twin_twisters_blue','recoil_red']);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deck=&GetDeck($p);EngineShuffle($deck,true);DoDrawCard($p,4);$profiles[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);
 for($step=0;$step<8000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('OUT fixture has no acting seat.');$d=GetDecisionQueue($p)[0]??null;$acted=false;
  if($d&&$d->Type==='NAMECARD'){$answer($p,'Surging Strike');$acted=true;}
  if(!$d&&!FaBHasPendingDecision()&&FaBOUTHero($p,'riptide'))foreach(FaBChoiceRefs($p,'Weapons',['base'=>'barbed_castaway']) as $r){$f=FaBIdentityFromMZ($r);if(FaBARCAbilityLegal($p,$f,FaBARCAbilitySpecs('barbed_castaway')[0])){$acted=FaBARCActivate($p,$f,0);break;}}
  if($acted)GameAfterEngineAction([],[]);elseif(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('OUT fixture stalled at '.json_encode(['step'=>$step,'p'=>$p,'s'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'OUT fixture exceeded step limit.');echo "$n-player OUT ".implode('/',$outHeroes)." finished in $step actions.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}

