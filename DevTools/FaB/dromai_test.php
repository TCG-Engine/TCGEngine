<?php
require_once __DIR__.'/arc_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBBotDeck('dromai');
$check(count($deck['mainDeck'])===40&&!FaBUPFDeckErrors($deck),'Dromai deck not legal.');
$check(FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/dromai_source.json'),true))===$deck,'Dromai deck differs from source.');
$covered=[];foreach(glob(__DIR__.'/*_abilities.json') as $file)foreach(json_decode(file_get_contents($file),true) as $entry)$covered[$entry['cardId']]=true;
foreach(array_merge([$deck['hero']],$deck['weapons'],$deck['equipment'],$deck['mainDeck']) as $id)$check(isset($covered[$id]),'Missing authored card: '.$id);
$resetDromai=function(int $seats)use($arcReset){$arcReset($seats);foreach(FaBSeatOrder() as $p){$z=&GetSoul($p);$z=[];}$p=$seats;$h=&GetHero($p);$h=[];AddHero($p,CardID:'dromai',Owner:$p,Controller:$p);SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(1);$s=FaBGetState();$s['botProfiles']=[$p=>'dromai'];FaBSetState($s);};
foreach([2,4] as $seats){
 $resetDromai($seats);$p=$seats;
 AddWeapons($p,CardID:'storm_of_sandikai',Owner:$p,Controller:$p);
 $red=AddHand($p,CardID:'wounding_blow_red');$blue=AddHand($p,CardID:'sweeping_blow_blue');$rake=AddHand($p,CardID:'rake_the_embers_red');
 $check(FaBDromaiPitchScore($p,$red)>FaBDromaiPitchScore($p,$blue),'Dromai does not prefer red pitch while short of Ash.');
 FaBWTRCreateArena($p,'ash');FaBWTRCreateArena($p,'ash');
 $check(FaBDromaiPitchScore($p,$blue)>FaBDromaiPitchScore($p,$red),'Dromai fails to use blue once stocked with Ash.');
 $dragon=FaBWTRCreateArena($p,'aether_ashwing');$attack=AddHand($p,CardID:'brutal_assault_red');
 $check(FaBDromaiPlayScore($p,$rake,'Hand')>FaBDromaiAbilityScore($p,$dragon),'Dromai attacks before red setup.');
 $s=FaBGetState();$s['cardsPlayedThisTurn'][(string)$p]=['rake_the_embers_red'];FaBSetState($s);
 $check(FaBDromaiAbilityScore($p,$dragon)>FaBDromaiPlayScore($p,$attack,'Hand'),'Dromai strands ready dragons behind a finisher.');
 $silken=AddEquipment($p,CardID:'silken_form',Owner:$p,Controller:$p);$check(FaBDromaiAbilityScore($p,$silken)>0,'Dromai ignores available Silken Form transformation.');
 $d=(object)['Type'=>'MZMAYCHOOSE','Tooltip'=>'Transform_ash','Param'=>FaBUPRAsh($p)];$chosen=FaBBotChoice($p,$d);$check(FaBIdentityFromMZ($chosen)['player']===$p,'Dromai chose another seat Ash.');
 $d=(object)['Type'=>'MZCHOOSE','Tooltip'=>'Choose_attack_target','Param'=>'p'.$p.'Hero-0&p1Hero-0'];$check(FaBBotChoice($p,$d)==='p1Hero-0','Dromai targets itself for damage.');
 $d=(object)['Type'=>'MZCHOOSE','Tooltip'=>'Bottom_card_as_cost','Param'=>FaBFindUID(intval($rake->UniqueID))['mzID'].'&'.FaBFindUID(intval($red->UniqueID))['mzID']];$check(FaBBotChoice($p,$d)===FaBFindUID(intval($red->UniqueID))['mzID'],'Dromai bottoms its Ashwing engine.');
 // Target selection is independent of hidden hand contents.
 $before=FaBBotChoice($p,(object)['Type'=>'MZCHOOSE','Tooltip'=>'Choose_attack_target','Param'=>FaBARCHeroTargets($p)]);AddHand(1,CardID:'command_and_conquer_red');$after=FaBBotChoice($p,(object)['Type'=>'MZCHOOSE','Tooltip'=>'Choose_attack_target','Param'=>FaBARCHeroTargets($p)]);$check($before===$after,'Dromai used hidden opponent information.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "Dromai deck and heuristics passed in duels and UPF.\n";
foreach([['dromai','fai'],['dromai','dromai','dromai','dromai'],['dromai','professor','levia','lexi']] as $profiles){
 $seats=count($profiles);$resetDromai($seats);SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$bots=[];
 foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);$h=&GetHero($p);$h=[];AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);
  foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
  foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id);$z=&GetDeck($p);shuffle($z);DoDrawCard($p,4);$bots[$p]=$profile;
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$dragons=[];$swarmTurns=[];
 for($step=0;$step<8000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();
  if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Dromai bot stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
  if(FaBIsDromaiBot($p)){
   if(count(FaBChoiceRefs($p,'Arena',['type'=>'Dragon']))>1)$swarmTurns[$p.':'.GetTurnNumber()]=true;
   $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&intval($s['attacker'])===$p&&FaBHasType($a['object'],'Dragon'))$dragons[intval($a['object']->UniqueID)]=true;
  }
 }
 $check(intval(GetWinner())>0,'Dromai game exceeded limit.');$check(count($swarmTurns)>0,'Dromai never built a swarm.');$check(count($dragons)>0,'Dromai never attacked with dragons.');
 echo "$seats-player ".implode('/',$profiles).": $step steps; ".count($swarmTurns)." swarm turns, ".count($dragons)." dragon attacks.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
