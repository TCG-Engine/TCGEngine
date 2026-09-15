<?php
require_once __DIR__.'/arc_test.php';
$failures=[];
$snapshot=json_decode(file_get_contents(__DIR__.'/mon_abilities.json'),true);

$monReset=function(int $seats=4)use($arcReset){$arcReset($seats);foreach(FaBSeatOrder() as $p){$z=&GetSoul($p);$z=[];}};
$monHero=function(int $p,string $id){$z=&GetHero($p);$z=[];AddHero($p,CardID:$id,Owner:$p,Controller:$p);};
$monPlay=function(int $p,string $ref,int $target=1)use($answer){
    if(!DoPlayCard($p,$ref))throw new RuntimeException('MON play illegal: '.$ref);
    if(GetDecisionQueue($p)&&str_contains(GetDecisionQueue($p)[0]->Tooltip,'attack_target'))$answer($p,'p'.$target.'Hero-0');
};
$monResolve=function(int $p){$o=FaBStackTop();if(!$o)throw new RuntimeException('No stack to resolve');DoResolveCard($p,FaBFindUID(intval($o->UniqueID))['mzID']);};
foreach([2,4] as $seats){
 $p=$seats;$monReset($seats);$monHero($p,'boltyn');SetTurnPlayer($p);SetPriorityPlayer($p);
 AddHand($p,CardID:'bolt_of_courage_red');AddHand($p,CardID:'engulfing_light_yellow');AddDeck($p,CardID:'take_flight_red');
 DoPlayCard($p,'p'.$p.'Hand-0');if($seats===4)$answer($p,'p1Hero-0');
 $check(GetDecisionQueue($p)[0]->Tooltip==='Charge_your_soul','Charge prompt missing.');$answer($p,'p'.$p.'Hand-1');
 $check(count(FaBChoiceRefs($p,'Soul'))===1&&FaBMONCount($p,'CHARGED')===1,'Charge did not enter soul/count.');$monResolve($p);
 $s=FaBGetState();$s['window']='DEFEND_DECLARE';$s['combatStep']='DEFEND';FaBSetState($s);AddHand(1,CardID:'leg_tap_red');FaBDeclareBlock(1,'p1Hand-0');
 $check(FaBAttackPower(FaBGetState())===4,'Boltyn attack bonus missing.');
 $s=FaBGetState();$s['window']='REACTION';$s['combatStep']='REACTION';FaBSetState($s);SetPriorityPlayer($p);
 $check(FaBWTRActivate($p,'p'.$p.'Hero-0'),'Boltyn reaction not available.');$answer($p,'p'.$p.'Soul-0');$monResolve($p);
 $check(count(FaBChoiceRefs($p,'Soul'))===0&&FaBAttackHasGoAgain(FaBGetState(),FaBFindUID(intval(FaBGetState()['attackUID']))['object']),'Boltyn soul cost/go again failed.');

 $monReset($seats);$monHero($p,'chane');SetTurnPlayer($p);SetPriorityPlayer($p);AddDeck($p,CardID:'ghostly_visit_red');
 $check(FaBWTRActivate($p,'p'.$p.'Hero-0'),'Chane activation illegal.');$monResolve($p);
 $check(count(FaBMONArena($p,'soul_shackle'))===1&&intval(GetActionPoints($p))===1,'Chane shackle/go again failed.');StartOfTurnPhase();
 $check(count(FaBChoiceRefs($p,'Banish'))===1&&FaBMONBloodDebt($p)===1,'Soul Shackle did not banish.');AddResources($p,1);
 $check(CanPlayCard($p,'p'.$p.'Banish-0'),'Intrinsic banish play missing.');FaBMONEnd($p);$check(intval(GetHealth($p))===19,'Blood debt not charged to owner.');
 $monHero($p,'levia');FaBMONAdd($p,'BANISHED_SIX');FaBMONEnd($p);$check(intval(GetHealth($p))===19,'Levia did not suppress debt.');

 $monReset($seats);$monHero($p,'prism');SetTurnPlayer($p);SetPriorityPlayer($p);AddSoul($p,CardID:'wartune_herald_yellow');AddResources($p,2);
 $check(FaBWTRActivate($p,'p'.$p.'Hero-0'),'Prism activation illegal.');$answer($p,'p'.$p.'Soul-0');$monResolve($p);
 $check(count(FaBMONArena($p,'spectral_shield'))===1,'Prism did not create shield.');
 $check(DoDamage(1,'',$p,2,'PHYSICAL')===1&&count(FaBMONArena($p,'spectral_shield'))===0,'Ward did not destroy shield/prevent damage.');
 AddWeapons($p,CardID:'luminaris',Owner:$p,Controller:$p);$shield=FaBWTRCreateArena($p,'spectral_shield');AddPitch($p,CardID:'wartune_herald_yellow');AddActionPoints($p,1);SetPriorityPlayer($p);
 $check(FaBWTRActivate($p,FaBFindUID(intval($shield->UniqueID))['mzID']),'Aura could not attack with Luminaris.');if($seats===4)$answer($p,'p1Hero-0');$monResolve($p);
 $s=FaBGetState();$check(FaBAttackPower($s)===1&&FaBAttackHasGoAgain($s,FaBFindUID(intval($s['attackUID']))['object']),'Aura weapon power/go again failed.');
 FaBCloseCombatChain();$check(FaBFindUID(intval($shield->UniqueID))['zone']==='Arena','Aura weapon vanished after attacking.');

 $monReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,3);AddHand($p,CardID:'wartune_herald_red');
 DoPlayCard($p,'p'.$p.'Hand-0');if($seats===4)$answer($p,'p1Hero-0');$monResolve($p);$uid=intval(FaBGetState()['attackUID']);
 $s=FaBGetState();$s['window']='DEFEND_DECLARE';$s['combatStep']='DEFEND';FaBSetState($s);AddHand(1,CardID:'brutal_assault_red');FaBDeclareBlock(1,'p1Hand-0');FaBFinishDefendDeclaration(FaBGetState());
 $check(FaBStackTop()!==null&&FaBGetState()['combatOpen'],'Phantasm did not offer a response window.');$monResolve($p);
 $check(!FaBGetState()['combatOpen']&&FaBFindUID($uid)['zone']==='Graveyard'&&intval(GetActionPoints($p))===0,'Phantasm did not destroy/close without go again.');

 $monReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,2);AddHand($p,CardID:'leg_tap_red');$sentinel=FaBWTRCreateArena(1,'arc_light_sentinel_yellow');
 $legal=FaBLegalAttackTargets($p);$check(count($legal)===1&&intval($legal[0]['uid'])===intval($sentinel->UniqueID),'Sentinel did not force an attack target.');
 DoPlayCard($p,'p'.$p.'Hand-0');if(GetDecisionQueue($p))$answer($p,FaBFindUID(intval($sentinel->UniqueID))['mzID']);$monResolve($p);
 $check(!FaBGetState()['combatOpen']&&FaBFindUID(intval($sentinel->UniqueID))['zone']==='Graveyard'&&intval(GetActionPoints($p))===0,'Spectra did not end attack without go again.');

 $monReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddHand($p,CardID:'zap_red');AddEquipment(1,CardID:'ebon_fold',Owner:1,Controller:1);
 DoPlayCard($p,'p'.$p.'Hand-0');$answer($p,'p1Hero-0');$monResolve($p);
 $check(GetDecisionQueue(1)[0]->Type==='MZMAYCHOOSE','Existing arcane card did not offer Spellvoid.');$answer(1,'p1Equipment-0');
 $check(intval(GetHealth(1))===19&&!FaBChoiceRefs(1,'Equipment'),'Spellvoid failed prevention/destruction.');

 $monReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);$library=FaBWTRCreateArena(1,'great_library_of_solana');
 AddHand($p,CardID:'wartune_herald_yellow');AddHand($p,CardID:'tome_of_divinity_yellow');
 $check(FaBWTRActivate($p,FaBFindUID(intval($library->UniqueID))['mzID']),'Non-controller cannot activate Library.');$answer($p,'p'.$p.'Hand-0&p'.$p.'Hand-1');$monResolve($p);
 $check(FaBFindUID(intval($library->UniqueID))['zone']==='Graveyard'&&FaBHandCount($p)===0&&intval(GetActionPoints($p))===1,'Library discard cost/destruction/go again failed.');
 // Destroying an aura source cancels both a pending and an active attack.
 foreach([false,true] as $resolved){
  $monReset($seats);$monHero($p,'prism');SetTurnPlayer($p);SetPriorityPlayer($p);
  AddWeapons($p,CardID:'luminaris',Owner:$p,Controller:$p);$shield=FaBWTRCreateArena($p,'spectral_shield');
  FaBWTRActivate($p,FaBFindUID(intval($shield->UniqueID))['mzID']);if($seats===4)$answer($p,'p1Hero-0');
  if($resolved)$monResolve($p);FaBMONDestroy(intval($shield->UniqueID));if(!$resolved)$monResolve($p);
  $check(!FaBGetState()['combatOpen']&&!FaBStackTop()&&intval(GetActionPoints($p))===0,'Destroyed aura left an attack or granted go again.');
 }

 // A one-card hand cannot charge away the only way to pay the attack cost.
 $monReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddHand($p,CardID:'take_flight_red');AddHand($p,CardID:'wartune_herald_blue');
 $monPlay($p,'p'.$p.'Hand-0');
 $check(FaBMONAffordableHand($p)===''&&!FaBHasPendingDecision()&&FaBGetState()['window']==='PITCH','Charge offered an unpayable cost.');
 $check(DoPitchCard($p,'p'.$p.'Hand-1'),'Cannot pitch after declining unavailable charge.');

 // Glisten removes pre-existing weapon counters too, at its controller's end.
 $monReset($seats);SetTurnPlayer($p);$w=AddWeapons($p,CardID:'dawnblade',Owner:$p,Controller:$p);FaBSetObjectCounter($w,'POWER',2);
 FaBMONGlisten($p,'p'.$p.'Weapons-0',1);FaBMONEnd(1);
 $check(intval(FaBObjectCounters($w)['POWER'])===3,'Glisten expired on another seat\'s turn.');FaBMONEnd($p);
 $check(intval(FaBObjectCounters($w)['POWER']??0)===0,'Glisten kept old power counters.');

 // Sonata selects matching attacks, damages a chosen opponent, and banishes itself.
 $monReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddHand($p,CardID:'sonata_arcanix_red');
 AddDeck($p,CardID:'ghostly_visit_red');AddDeck($p,CardID:'minnowism_red');AddDeck($p,CardID:'rift_bind_blue');
 $monPlay($p,'p'.$p.'Hand-0');$answer($p,'0');$uid=intval(FaBStackTop()->UniqueID);$monResolve($p);
 $attackRef=FaBChoiceRefs($p,'Temp',['attackAction'=>true])[0];$answer($p,$attackRef);$answer($p,'p1Hero-0');
 $check(FaBHandCount($p)===1&&count(FaBChoiceRefs($p,'Deck'))===2&&intval(GetHealth(1))===19&&FaBFindUID($uid)['zone']==='Banish','Sonata reveal/selection/damage/cleanup failed.');
}
// Gateway must reach all opponents, including the nonadjacent UPF seat.
$monReset(4);SetTurnPlayer(4);SetPriorityPlayer(4);AddResources(4,1);AddHand(4,CardID:'dimenxxional_gateway_blue');AddDeck(4,CardID:'rift_bind_blue');
$monPlay(4,'p4Hand-0');$monResolve(4);$answer(4,GetDecisionQueue(4)[0]->Param);$answer(4,'1');
$check(intval(GetHealth(1))===19&&intval(GetHealth(2))===19&&intval(GetHealth(3))===19&&intval(GetHealth(4))===20,'Gateway did not damage exactly every opponent.');
$check(count(FaBChoiceRefs(4,'Banish'))===1&&!FaBHasPendingDecision(),'Gateway shadow banish failed.');
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "MON targeted duel/UPF checks passed.\n";
$macroChecks=0;
foreach([2,4] as $seatCount)foreach($snapshot as $entry)foreach($entry['abilities'] as $ability){
    $arcReset($seatCount); foreach(FaBSeatOrder() as $seat){$soul=&GetSoul($seat);$soul=[];}$actor=$seatCount;SetTurnPlayer($actor);SetPriorityPlayer($actor);AddResources($actor,30);
    foreach(['herald_of_protection_red','ghostly_visit_red','minnowism_red','bolt_of_courage_red','read_the_runes_red','tome_of_divinity_yellow'] as $id){AddHand($actor,CardID:$id);AddDeck($actor,CardID:$id);AddGraveyard($actor,CardID:$id);AddSoul($actor,CardID:$id);AddBanish($actor,CardID:$id);}
    AddArsenal($actor,CardID:'ridge_rider_shot_red');
    $id=$entry['cardId'];$macro=$ability['macroName'];if(str_ends_with($macro,'Modifier'))continue;
    if(in_array($macro,['Hit','AttackDeclared'],true))$o=AddCombatChain($actor,CardID:$id,Owner:$actor,Controller:$actor,Role:'ATTACK',ChainLink:1);
    elseif($macro==='PrepareCard')$o=AddStack(CardID:$id,Controller:$actor,Kind:FaBHasType($id,'Attack')?'ATTACK':'ACTION',SourceZone:'Hand');
    elseif($macro==='CardPitched')$o=AddPitch($actor,CardID:$id);
    else $o=AddArena($actor,CardID:$id,Owner:$actor,Controller:$actor);
    $uid=intval($o->UniqueID);$s=FaBGetState();$s['attacker']=$actor;$s['defender']=1;$s['chainLink']=1;$s['attackUID']=$uid;$s['attackTarget']=['type'=>'HERO','player'=>1,'uid'=>GetHero(1)[0]->UniqueID,'zone'=>'Hero'];
    if($macro==='PrepareCard')$s['pendingPayment']=['player'=>$actor,'uid'=>$uid,'cost'=>0,'kind'=>'ACTION','fromZone'=>'Hand','returnWindow'=>'ACTION','returnCombatStep'=>'NONE'];
    $s['combatOpen']=true; FaBSetState($s);FaBARCSetCard($uid,'target',1);FaBARCSetCard($uid,'target2',1);
    DecisionQueueController::StoreVariable('fabSourceZone','Arsenal');
    FaBRunSourceMacro($macro,$actor,$id,['mzID'=>FaBFindUID($uid)['mzID'],'amount'=>2,'attacker'=>$actor,'defender'=>1]);
    for($step=0;$step<50;++$step){
        $pending=0;foreach([1,2,3,4] as $seat)if(GetDecisionQueue($seat)){$pending=$seat;break;}
        if(!$pending)break;
        $d=GetDecisionQueue($pending)[0];
        if($d->Type==='CUSTOM'){(new DecisionQueueController())->ExecuteStaticMethods($pending);continue;}
        $value=match($d->Type){
            'MZCHOOSE','MZMAYCHOOSE'=>explode('&',$d->Param)[0],
            'MZMODAL'=>implode(',',range(0,max(0,intval(explode('|',$d->Param)[0])-1))),
            'MZREARRANGE'=>$d->Param,
            'MZMULTICHOOSE'=>implode('&',array_slice(explode('&',explode('|',$d->Param,3)[2]??''),0,intval(explode('|',$d->Param)[0]))),
            'NUMBERCHOOSE'=>strval(max(1,intval(explode('|',$d->Param)[0]))),
            'NAMECARD'=>'Zap',
            default=>throw new RuntimeException('Unhandled '.$d->Type.' for '.$id.' '.$macro)
        };
        $answer($pending,$value);
    }
    $check(!FaBHasPendingDecision(),'Continuation failed to finish: '.$id.' '.$macro);
    ++$macroChecks;
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "MON continuations passed; $macroChecks authored continuations exercised in duels and UPF.\n";



// Complete games exercise repeated turn boundaries, pitch, banish and soul transfers.
foreach([2,4] as $seats){
 $monReset($seats);mt_srand(20260913);$profiles=[];
 foreach(FaBLiveSeats() as $p){
  $hero=['prism','levia','boltyn','chane'][$p-1];$monHero($p,$hero);AddHealth($p,20);
  $weapon=['luminaris','hexagore_the_death_hydra','raydn_duskbane','galaxxi_black'][$p-1];AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  $pool=match($hero){
   'prism'=>['wartune_herald_red','wartune_herald_yellow','wartune_herald_blue','herald_of_protection_red','herald_of_protection_blue','herald_of_tenacity_red','herald_of_ravages_red','herald_of_ravages_blue','spears_of_surreality_red','spears_of_surreality_blue'],
   'levia'=>['smash_with_big_tree_red','smash_with_big_tree_blue','pulping_red','pulping_blue','deadwood_rumbler_red','deadwood_rumbler_blue','boneyard_marauder_red','boneyard_marauder_blue','dread_screamer_red','dread_screamer_blue'],
   'boltyn'=>['bolt_of_courage_red','bolt_of_courage_blue','take_flight_red','take_flight_blue','cross_the_line_red','cross_the_line_blue','battlefield_blitz_red','battlefield_blitz_blue','valiant_thrust_red','valiant_thrust_blue'],
   'chane'=>['seeds_of_agony_red','seeds_of_agony_blue','bounding_demigon_red','bounding_demigon_blue','rift_bind_red','rift_bind_blue','arcanic_crackle_red','arcanic_crackle_blue','ghostly_visit_red','ghostly_visit_blue']};
  for($i=0;$i<40;++$i)AddDeck($p,CardID:$pool[$i%count($pool)]);$d=&GetDeck($p);shuffle($d);DoDrawCard($p,4);$profiles[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);
 for($step=0;$step<6000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();
  if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('MON bot stalled: '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
 }
 $check(intval(GetWinner())>0,'MON match exceeded step limit.');echo "$seats-player MON match completed in $step steps.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
