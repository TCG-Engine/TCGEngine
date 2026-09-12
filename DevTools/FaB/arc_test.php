<?php
require_once __DIR__.'/wtr_test.php';
$failures=[];
$arcReset=function(int $seats=4) use($reset){$reset();if($seats===2){SetSeatOrder('12');SetLiveSeats('12');$s=FaBGetState();$s['gameMode']='BOT';FaBSetState($s);}};
$resolve=function(int $p,string $id){$o=AddGraveyard($p,CardID:$id);FaBRunSourceMacro('ResolveCard',$p,$id,['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);return $o;};
$catalog=json_decode(file_get_contents(__DIR__.'/arc_catalog.json'),true);
$snapshot=json_decode(file_get_contents(__DIR__.'/arc_abilities.json'),true);
$check(count($snapshot)===219&&array_column($catalog,'id')===array_column($snapshot,'cardId'),'ARC snapshot does not cover the complete printing set.');

$arcReset();AddDeck(4,CardID:'zap_blue');AddDeck(4,CardID:'zap_red');
DecisionQueueController::StoreVariable('mzID','p4Equipment-0');
FaBRevealChoices(4,'p4Deck-0');FaBRevealChoices(4,'p4Deck-0&p4Deck-1');
$events=GetMacroGameIndexArray()['RevealCard'][4]??[];
$check(($events['zap_blue']??0)===2&&($events['zap_red']??0)===1,'Reveal events lost repeated cards or the revealing seat.');
$check(!FaBHasPendingDecision()&&DecisionQueueController::GetVariable('mzID')==='p4Equipment-0','Reveal event disturbed the resolving card choice.');
$check(empty(FaBGetState()['reveal']),'Reveal still writes the inline layout banner.');

foreach([2,4] as $seats){
    $arcReset($seats);$target=$seats;
    $expected=$seats===4?'p2Hero-0&p4Hero-0':'p2Hero-0';
    $check(FaBARCHeroTargets(1)===$expected,'Spell targeting ignores adjacency.');
    $s=FaBGetState();$s['combatOpen']=true;$s['defender']=2;FaBSetState($s);
    $check(FaBARCHeroTargets(1)===$expected,'Combat focus incorrectly limits spells.');
    $arcReset($seats);
    AddEquipment($target,CardID:'nullrune_hood',Owner:$target,Controller:$target);
    AddHand($target,CardID:'zap_blue');AddHand(1,CardID:'zap_red');
    $check(DoPlayCard(1,'p1Hand-0'),'Zap cannot be played.');
    $check(GetDecisionQueue(1)[0]->Param===($seats===2?'p1Hero-0&p2Hero-0':$expected),'Zap did not choose target on announcement.');
    $answer(1,'p'.$target.'Hero-0');
    $top=FaBStackTop();$check($top!==null&&FaBGetState()['pendingPayment']===null,'Zap preparation did not finish.');
    DoResolveCard(1,FaBFindUID(intval($top->UniqueID))['mzID']);
    $check(GetDecisionQueue($target)[0]->Type==='MZMODAL','Defender did not get barrier prompt.');
    $answer($target,'1');
    $check(GetDecisionQueue($target)[0]->Param==='p'.$target.'Hand-0','Barrier pitch offered the wrong hand.');
    $answer($target,'p'.$target.'Hand-0');
    $check(intval(GetHealth($target))===18&&intval(GetResources($target))===2,'Barrier pitch/prevention calculation failed.');
    $check(intval(FaBGetState()['arcaneDealt']['1'][(string)$target]??0)===2,'Cross-player await changed spell controller.');
    $check(intval(GetHealth(1))===20,'Spell damaged its controller after prevention choice.');
}
$arcReset();AddEquipment(4,CardID:'arcanite_skullcap',Owner:4,Controller:4);AddHealth(4,10);AddResources(4,4);
$check(FaBARCBarrierPayments(4,1)===[0,3],'Barrier 3 was incorrectly divisible into Barrier 1.');

$arcReset();AddResources(1,5);AddHand(1,CardID:'forked_lightning_red');
DoPlayCard(1,'p1Hand-0');
$check(str_contains(GetDecisionQueue(1)[0]->Param,'p3Hero-0'),'Forked Lightning cannot target nonadjacent hero.');
$answer(1,'p3Hero-0');$answer(1,'p3Hero-0');
AddEquipment(3,CardID:'nullrune_hood',Owner:3,Controller:3);AddResources(3,1);
DoResolveCard(1,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);$answer(3,'1');
$check(intval(GetHealth(3))===17&&!FaBHasPendingDecision(),'Forked Lightning did not combine damage to the same hero.');

$arcReset();AddDeck(4,CardID:'zap_red');AddDeck(4,CardID:'zap_blue');AddDeck(4,CardID:'voltic_bolt_red');
$uids=FaBARCStageTop(4,2);FaBARCFinishOrder(4,$uids,'Top=zap_blue;Bottom=zap_red');
$check(array_map(fn($r)=>FaBIdentityFromMZ($r)['object']->CardID,FaBChoiceRefs(4,'Deck'))===['zap_blue','voltic_bolt_red','zap_red'],'Opt did not reorder top/bottom correctly.');

$arcReset();AddHand(4,CardID:'head_shot_red');
$check(!CanPlayCard(4,'p4Hand-0'),'Arrow can be played from hand.');
FaBARCLoadArsenal(4,'p4Hand-0',true,1);
$a=GetArsenal(4)[0];$check(intval($a->FaceDown)===0&&count($a->TurnEffects)===2,'Arrow face-up entry bonuses were lost.');
$copy=new Arsenal($a->Serialize(),'Arsenal',4,0);
$check(intval($copy->FaceDown)===0&&count($copy->TurnEffects)===2,'Arsenal flags do not survive serialization.');

$arcReset();AddHand(1,CardID:'zero_to_sixty_red');AddDeck(1,CardID:'zipper_hit_red');
DoPlayCard(1,'p1Hand-0');$answer(1,'p2Hero-0');
$check(GetDecisionQueue(1)[0]->Type==='MZMODAL','Boost choice missing.');$answer(1,'0');
$check(FaBARCEffect(1,'ARC_BOOSTED')===1&&count(FaBChoiceRefs(1,'Banish'))===1,'Boost did not banish/count.');
$check(in_array('GO_AGAIN',FaBStackTop()->TurnEffects,true),'Successful boost did not grant go again.');

$arcReset();AddHero(1,CardID:'viserai',Owner:1,Controller:1);FaBARCCreateRunes(1,2);
AddHand(1,CardID:'rune_flash_red');AddResources(1,1);
$check(FaBCardCost(GetHand(1)[0],1)===1,'Runechant cost reduction incorrect.');
DoPlayCard(1,'p1Hand-0');$answer(1,'p2Hero-0');
$check(FaBARCRunechants(1)===0&&FaBStackCount()===3,'Runechants were not placed above the attack as separate triggers.');
$answer(1,'p2Hero-0');$answer(1,'p4Hero-0');
$runeTargets=[];foreach(GetStack() as $rune)if(is_object($rune)&&$rune->CardID==='runechant'&&empty($rune->removed))$runeTargets[]=intval(FaBARCCard(intval($rune->UniqueID),'target'));
sort($runeTargets);$check($runeTargets===[2,4]&&!FaBHasPendingDecision(),'Runechant targets were not retained separately on announcement.');
DoResolveCard(1,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(!FaBHasPendingDecision()&&intval(GetHealth(4))===19,'Runechant asked for its target again at resolution.');

$arcReset();$pistol=AddWeapons(1,CardID:'teklo_plasma_pistol',Owner:1,Controller:1);AddResources(1,3);
$check(FaBWTRActivate(1,'p1Weapons-0'),'Pistol could not load.');DoResolveCard(1,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(intval(FaBObjectCounters($pistol)['STEAM']??0)===1,'Pistol did not gain steam.');
$check(FaBWTRActivate(1,'p1Weapons-0'),'Loaded pistol could not attack.');$answer(1,'p2Hero-0');
$check(intval(FaBObjectCounters($pistol)['STEAM']??0)===0,'Pistol attack did not spend steam.');

$arcReset();$kano=AddHero(4,CardID:'kano',Owner:4,Controller:4);SetPriorityPlayer(4);AddResources(4,3);AddDeck(4,CardID:'zap_red');
$ref=FaBFindUID(intval($kano->UniqueID))['mzID'];$check(FaBWTRActivate(4,$ref),'Kano cannot activate on another turn.');
DoResolveCard(4,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$answer(4,'p4Temp-0');SetPriorityPlayer(4);
$check(CanPlayCard(4,'p4Banish-0'),'Kano banished action cannot be played as instant.');
$ap=intval(GetActionPoints(4));DoPlayCard(4,'p4Banish-0');$answer(4,'p1Hero-0');
$check(FaBStackTop()->Kind==='INSTANT'&&intval(GetActionPoints(4))===$ap,'Kano action consumed AP on another player turn.');
DoResolveCard(4,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(intval(GetHealth(1))===17,'Kano instant did not resolve against its chosen hero.');

$arcReset();AddResources(1,0);AddHand(1,CardID:'spark_of_genius_yellow');AddHand(1,CardID:'zap_blue');AddDeck(1,CardID:'hyper_driver_red');
DoPlayCard(1,'p1Hand-0');$answer(1,'1');
$check(FaBGetState()['pendingPayment']['cost']===2,'Spark of Genius did not charge 2X.');
DoPitchCard(1,'p1Hand-1');DoResolveCard(1,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);$answer(1,'p1Temp-0');
$check(count(FaBChoiceRefs(1,'Arena'))===1&&intval(FaBObjectCounters(GetArena(1)[0])['STEAM']??0)===3,'Spark did not initialize its searched item.');

$arcReset();AddHand(1,CardID:'voltic_bolt_red');AddHand(1,CardID:'eye_of_ophidia_blue');AddDeck(1,CardID:'zap_red');AddDeck(1,CardID:'zap_blue');
DoPlayCard(1,'p1Hand-0');$answer(1,'p2Hero-0');DoPitchCard(1,'p1Hand-1');
$check(GetDecisionQueue(1)[0]->Type==='MZREARRANGE','Eye of Ophidia did not opt while pitching.');
$answer(1,'Top=zap_blue;Bottom=zap_red');
$check(FaBGetState()['pendingPayment']===null&&intval(GetResources(1))===1,'Eye opt left the paid spell stuck in PITCH.');

$arcReset();AddEquipment(1,CardID:'arcanite_skullcap',Owner:1,Controller:1);AddResources(1,3);
$check(FaBARCBarrierPayments(1,3)===[0],'Skullcap has barrier without lower life.');
$arcReset();$sink=AddArena(1,CardID:'aether_sink_yellow',Owner:1,Controller:1);AddResources(1,2);
$check(FaBARCBarrierPayments(1,3)===[0],'Aether Sink has barrier before activation.');
FaBWTRTag($sink,'ARC_BARRIER_2');$check(FaBARCBarrierPayments(1,3)===[0,2],'Aether Sink barrier cannot be activated.');

$arcReset();$resolve(4,'tome_of_aetherwind_red');AddDeck(4,CardID:'zap_red');AddDeck(4,CardID:'zap_blue');
$answer(4,'1');$answer(4,'1');$check(FaBHandCount(4)===2,'Tome cannot select draw twice.');

$arcReset();FaBWTRAddEffect(1,'ARC_FIRST_ATTACK_COST',1);AddHand(1,CardID:'come_to_fight_red');AddHand(1,CardID:'zero_to_sixty_red');
$check(FaBCardCost(GetHand(1)[0],1)===intval(CardCost('come_to_fight_red'))&&FaBCardCost(GetHand(1)[1],1)===1,'Hamstring Shot taxes a nonattack or misses an attack.');
FaBWTRAddEffect(1,'ARC_LEDGER',1);FaBWTRAddEffect(1,'ARC_ACTIONS',1);AddWeapons(1,CardID:'nebula_blade',Owner:1,Controller:1);AddResources(1,10);
$check(!CanPlayCard(1,'p1Hand-1')&&!FaBWTRCanActivate(1,'p1Weapons-0'),'Red in the Ledger allows a second action.');

$arcReset();FaBWTRAddEffect(1,'ARC_PREVENT_ONCE',4);DoDamage(2,'',1,2,'PHYSICAL');DoDamage(2,'',1,2,'PHYSICAL');
$check(intval(GetHealth(1))===18,'One-time prevention carried surplus into another damage event.');

$arcReset();$dash=AddHero(4,CardID:'dash',Owner:4,Controller:4);AddDeck(4,CardID:'hyper_driver_red');
for($i=0;$i<5;++$i)AddDeck(4,CardID:'zero_to_sixty_red');
FaBRunSourceMacro('StartTurn',4,'dash',['mzID'=>FaBFindUID(intval($dash->UniqueID))['mzID']]);$answer(4,'p4Temp-0');
$check(FaBHandCount(4)===4&&count(FaBChoiceRefs(4,'Arena'))===1,'Dash setup failed before opening draw.');

$arcReset();AddHand(4,CardID:'rune_flash_red');AddDeck(4,CardID:'read_the_runes_red');
$resolve(4,'become_the_arknight_blue');$answer(4,'p4Hand-0');$answer(4,'p4Temp-0');
$check(GetHand(4)[1]->CardID==='read_the_runes_red','Become the Arknight searched the wrong action type.');

$invalidDeck=['hero'=>'katsu','mainDeck'=>array_fill(0,40,'zap_red'),'weapons'=>[],'equipment'=>[]];
$check(str_contains(implode(' ',FaBUPFDeckErrors($invalidDeck)),'hero class'),'ARC class validation is missing.');
foreach(['endless_arrow_red','over_loop_red','salvage_shot_red'] as $id){
    $arcReset();$o=AddCombatChain(4,CardID:$id,Owner:4,Controller:4,Role:'ATTACK',ChainLink:1);
    FaBWTRTag($o,'GO_AGAIN');$uid=intval($o->UniqueID);
    $s=FaBGetState();$s['attacker']=4;$s['defender']=1;$s['attackUID']=$uid;FaBSetState($s);
    FaBRunSourceMacro('Hit',4,$id,['mzID'=>FaBFindUID($uid)['mzID'],'amount'=>1]);
    $check(!empty(FaBGetState()['attackGoAgain'])&&FaBFindUID($uid)['zone']!== 'CombatChain','Leaving attack lost go again: '.$id);
}
$arcReset();$o=AddCombatChain(4,CardID:'command_and_conquer_red',Owner:4,Controller:4,Role:'ATTACK',ChainLink:1);
AddArsenal(1,CardID:'zap_red');$s=FaBGetState();$s['attacker']=4;$s['defender']=1;$s['attackTarget']=['type'=>'PERMANENT'];FaBSetState($s);
FaBRunSourceMacro('Hit',4,$o->CardID,['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID'],'amount'=>1]);
$check(count(FaBChoiceRefs(1,'Arsenal'))===1,'Command and Conquer destroyed arsenal without hitting a hero.');
$arcReset();$o=AddEquipment(4,CardID:'bracers_of_belief',Owner:4,Controller:4);
FaBRunSourceMacro('ResolveAbility',4,$o->CardID,['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);
$check(FaBARCEffect(4,'ARC_NEXT_AA')===0,'Bracers granted power without revealing a card.');

// Execute every authored continuation from seat four with populated search zones.
// This catches uncompiled awaits, stale locals and helpers missing on less common variants.
$macroChecks=0;
foreach($snapshot as $entry)foreach($entry['abilities'] as $ability){
    $arcReset();SetTurnPlayer(4);SetPriorityPlayer(4);AddResources(4,30);
    foreach(['zap_blue','head_shot_red','rune_flash_red','read_the_runes_red','induction_chamber_red','sun_kiss_red'] as $id){AddHand(4,CardID:$id);AddDeck(4,CardID:$id);AddGraveyard(4,CardID:$id);}
    AddArsenal(4,CardID:'ridge_rider_shot_red');
    $id=$entry['cardId'];$macro=$ability['macroName'];
    if(in_array($macro,['Hit','AttackDeclared'],true))$o=AddCombatChain(4,CardID:$id,Owner:4,Controller:4,Role:'ATTACK',ChainLink:1);
    elseif($macro==='PrepareCard')$o=AddStack(CardID:$id,Controller:4,Kind:FaBHasType($id,'Attack')?'ATTACK':'ACTION',SourceZone:'Hand');
    elseif($macro==='CardPitched')$o=AddPitch(4,CardID:$id);
    else $o=AddArena(4,CardID:$id,Owner:4,Controller:4);
    $uid=intval($o->UniqueID);$s=FaBGetState();$s['attacker']=4;$s['defender']=1;$s['chainLink']=1;$s['attackUID']=$uid;$s['attackTarget']=['type'=>'HERO','player'=>1,'uid'=>GetHero(1)[0]->UniqueID,'zone'=>'Hero'];
    if($macro==='PrepareCard')$s['pendingPayment']=['player'=>4,'uid'=>$uid,'cost'=>0,'kind'=>'ACTION','fromZone'=>'Hand','returnWindow'=>'ACTION','returnCombatStep'=>'NONE'];
    FaBSetState($s);FaBARCSetCard($uid,'target',1);FaBARCSetCard($uid,'target2',2);
    DecisionQueueController::StoreVariable('fabSourceZone','Arsenal');
    FaBRunSourceMacro($macro,4,$id,['mzID'=>FaBFindUID($uid)['mzID'],'amount'=>2,'attacker'=>4,'defender'=>1]);
    for($step=0;$step<50;++$step){
        $pending=0;foreach([1,2,3,4] as $seat)if(GetDecisionQueue($seat)){$pending=$seat;break;}
        if(!$pending)break;
        $d=GetDecisionQueue($pending)[0];
        if($d->Type==='CUSTOM'){(new DecisionQueueController())->ExecuteStaticMethods($pending);continue;}
        $value=match($d->Type){
            'MZCHOOSE','MZMAYCHOOSE'=>explode('&',$d->Param)[0],
            'MZMODAL'=>implode(',',range(0,max(0,intval(explode('|',$d->Param)[0])-1))),
            'MZREARRANGE'=>$d->Param,
            'NUMBERCHOOSE'=>'0',
            'NAMECARD'=>'Zap',
            default=>throw new RuntimeException('Unhandled '.$d->Type.' for '.$id.' '.$macro)
        };
        $answer($pending,$value);
    }
    $check(!FaBHasPendingDecision(),'Continuation failed to finish: '.$id.' '.$macro);
    ++$macroChecks;
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "ARC mechanics passed; $macroChecks authored continuations exercised from seat four.\n";
