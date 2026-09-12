<?php
require_once __DIR__.'/arc_test.php';
$failures=[];
$snapshot=json_decode(file_get_contents(__DIR__.'/cru_abilities.json'),true);
$catalog=json_decode(file_get_contents(__DIR__.'/cru_catalog.json'),true);
$check(count($snapshot)===194&&array_column($snapshot,'cardId')===array_column($catalog,'id'),'CRU catalog coverage incomplete.');
$snapshot=array_merge($snapshot,json_decode(file_get_contents(__DIR__.'/cru_support_abilities.json'),true));

$cruResolve=function(int $p,string $id) { $o=AddGraveyard($p,CardID:$id);FaBRunSourceMacro('ResolveCard',$p,$id,['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);return $o; };
$attackFixture=function(int $p,int $target,string $id,int $link=1){
 $o=AddCombatChain($p,CardID:$id,Owner:$p,Controller:$p,Role:'ATTACK',ChainLink:$link);
 $s=FaBGetState();$s['attacker']=$p;$s['defender']=$target;$s['attackUID']=intval($o->UniqueID);$s['chainLink']=$link;$s['combatOpen']=true;$s['window']='REACTION';$s['combatStep']='REACTION';$s['attackTarget']=['type'=>'HERO','player'=>$target,'uid'=>GetHero($target)[0]->UniqueID,'zone'=>'Hero'];$s['attackTargets']=[$s['attackTarget']];FaBSetState($s);return $o;
};
foreach([2,4] as $seats){
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,10);
 AddHand($p,CardID:'snapback_red');FaBWTRAddEffect($p,'ARC_NEXT_ARCANE',1);
 $check(DoPlayCard($p,'p'.$p.'Hand-0'),'CRU spell cannot be announced.');$answer($p,'p1Hero-0');
 AddEquipment(1,CardID:'nullrune_hood',Owner:1,Controller:1);AddHand(1,CardID:'zap_blue');
 DoResolveCard($p,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);$answer(1,'1');$answer(1,'p1Hand-0');
 $check(intval(GetHealth(1))===17&&FaBCRUArcane($p)===3,'CRU spell lost caster or boost after cross-seat pitch.');
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,5);
 FaBCRUAdd($p,'DISCARD_SIX');$o=$attackFixture($p,1,'riled_up_red');FaBCRUAttack($p,$o);
 $check(FaBAttackPower(FaBGetState())===8,'Riled Up discarded-six bonus missing.');FaBCRUAdd($p,'SNAG');
 $check(FaBAttackPower(FaBGetState())===7,'Snag did not suppress the attack own bonus.');FaBWTRTag($o,'WTR_POWER:3');
 $check(FaBAttackPower(FaBGetState())===10,'Snag incorrectly suppressed non-attack action buffs.');
 $arcReset($seats);$p=$seats;AddResources($p,3);$o=$attackFixture($p,1,'brutal_assault_red');
 AddHand($p,CardID:'zap_blue');AddResources($p,0);$trap=AddCombatChain(1,CardID:'rockslide_trap_blue',Owner:1,Controller:1,Role:'DEFENSE_REACTION',ChainLink:1);
 OnDefended(1,FaBFindUID(intval($trap->UniqueID))['mzID'],1);$answer($p,'1');$answer($p,'p'.$p.'Hand-0');
 $check(FaBAttackPower(FaBGetState())===6&&intval(GetResources($p))===2,'Trap did not prompt/pay from actual attacker.');
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);AddHand($p,CardID:'cash_in_yellow');
 for($i=0;$i<4;++$i)FaBWTRCreateArena($p,'copper');AddDeck($p,CardID:'zap_red');AddDeck($p,CardID:'zap_blue');
 $check(CanPlayCard($p,'p'.$p.'Hand-0'),'Cash In alternative cost not recognized.');DoPlayCard($p,'p'.$p.'Hand-0');$answer($p,'1');
 $check(FaBGetState()['pendingPayment']===null&&count(FaBChoiceRefs($p,'Arena',['base'=>'copper']))===0,'Cash In did not consume four Copper.');
 DoResolveCard($p,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);$check(FaBHandCount($p)===2,'Cash In did not draw two.');
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,5);
 $h=&GetHero($p);$h=[];AddHero($p,CardID:'kayo_berserker_runt',Owner:$p,Controller:$p);AddEquipment(1,CardID:'gamblers_gloves',Owner:1,Controller:1);AddHand($p,CardID:'brutal_assault_red');
 DoPlayCard($p,'p'.$p.'Hand-0');if(FaBHasPendingDecision()&&GetDecisionQueue($p))$answer($p,'p1Hero-0');
 $check(!empty(GetDecisionQueue(1)),'Kayo roll did not offer opponent gloves.');$answer(1,'1');
 $check(count(FaBChoiceRefs(1,'Equipment'))===0,'Reroll did not destroy the correct gloves.');
 $uid=intval(FaBStackTop()->UniqueID);$roll=intval(FaBARCCard($uid,'kayo'));DoResolveCard($p,FaBFindUID($uid)['mzID']);
 $check($roll>=1&&$roll<=6&&FaBAttackPower(FaBGetState())===($roll<=4?3:12),'Kayo did not retain final roll on the announced attack.');
}
$arcReset();AddHealth(1,22);AddHealth(2,20);AddHealth(3,20);AddHealth(4,18);FaBCRUKavdaen();
$check(intval(GetHealth(1))===21&&intval(GetHealth(4))===19&&count(FaBChoiceRefs(1,'Arena',['base'=>'copper']))===1,'Kavdaen ignored nonadjacent highest/lowest hero.');
$arcReset();AddWeapons(3,CardID:'reaping_blade',Owner:3,Controller:3);AddHealth(4,21);FaBCRUGainLife(4,3);FaBCRUGainLife(2,1);
$check(intval(GetHealth(4))===21&&intval(GetHealth(2))===21,'Reaping Blade mishandled ties or all-seat presence.');
$arcReset();$h=&GetHero(4);$h=[];AddHero(4,CardID:'data_doll_mkii',Owner:4,Controller:4);$item=AddDeck(4,CardID:'hyper_driver_red');$uid=intval($item->UniqueID);FaBMoveUID($uid,'Banish',4);
$check(FaBFindUID($uid)['zone']==='Arena'&&intval(FaBObjectCounters(FaBFindUID($uid)['object'])['STEAM']??0)===3,'Data Doll did not enter and initialize a banished item.');
$arcReset();AddDeck(4,CardID:'zap_red');AddDeck(4,CardID:'brutal_assault_red');$beast=AddHand(4,CardID:'beast_within_yellow');FaBDiscardChoice(4,'p4Hand-0');
$check(intval(GetHealth(4))===18&&FaBHandCount(4)===1&&GetHand(4)[1]->CardID==='brutal_assault_red','Beast Within did not repeat until a six-power card.');
$arcReset();FaBARCCreateRunes(4,4);FaBWTRCreateArena(4,'runeblood_barrier_yellow');DoDamage(1,'',4,6,'PHYSICAL');
$check(intval(GetHealth(4))===18&&FaBARCRunechants(4)===0,'Runeblood Barrier prevention incorrect.');
$arcReset();FaBWTRCreateArena(4,'zen_state');DoDamage(1,'',4,2,'ARCANE');DoDamage(1,'',4,2,'PHYSICAL');
$check(intval(GetHealth(4))===18,'Zen State failed repeated packets.');
$arcReset();$o=$attackFixture(4,1,'meganetic_shockwave_blue');FaBCRUAdd(4,'CHAIN_BOOST',2);AddEquipment(1,CardID:'ironrot_helm',Owner:1,Controller:1);AddEquipment(1,CardID:'ironrot_legs',Owner:1,Controller:1);
$s=FaBGetState();$s['window']='DEFEND_DECLARE';FaBSetState($s);$check(FaBCRUMustEquip(1),'Meganetic did not require equipment.');
FaBDeclareBlock(1,'p1Equipment-0');$check(FaBCRUMustEquip(1),'Meganetic stopped after one of two required equipment.');FaBDeclareBlock(1,'p1Equipment-1');$check(!FaBCRUMustEquip(1),'Meganetic still requires equipment after two blocks.');
$arcReset();$o=$attackFixture(4,1,'crane_dance_red',3);$s=FaBGetState();$s['previousAttackCardID']='soulbead_strike_red';$s['window']='DEFEND_DECLARE';FaBSetState($s);FaBCRUAttack(4,$o);AddHand(1,CardID:'brutal_assault_red');AddHand(1,CardID:'zap_blue');
$check(!FaBCanBlock(1,'p1Hand-0')&&FaBCanBlock(1,'p1Hand-1'),'Crane Dance defense restriction wrong.');
$arcReset();$o=$attackFixture(4,1,'snatch_red');FaBWTRCreateArena(3,'stamp_authority_blue');AddDeck(4,CardID:'zap_red');OnHit(4,'p4CombatChain-0',1);
$check(FaBHandCount(4)===0,'Stamp Authority failed to suppress existing WTR on-hit.');
$arcReset();AddHero(4,CardID:'shiyana_diamond_gemini',Owner:4,Controller:4);FaBCRUCopyHero(4,'p1Hero-0');
$check(GetHero(4)[1]->CardID===GetHero(1)[0]->CardID&&!empty(FaBObjectCounters(GetHero(4)[1])['CRU_SHIYANA']),'Shiyana did not copy hero identity.');


// Every opposing hero is affected, including the nonadjacent seat. Cross-seat
// prevention choices must not replace the caster saved by the continuation.
$arcReset();SetTurnPlayer(4);SetPriorityPlayer(4);AddResources(4,3);
$s=FaBGetState();$s['cardsPlayedThisTurn']['4']=['zap_red'];FaBSetState($s);AddHand(4,CardID:'chain_lightning_yellow');
DoPlayCard(4,'p4Hand-0');AddEquipment(2,CardID:'nullrune_hood',Owner:2,Controller:2);AddHand(2,CardID:'zap_blue');
DoResolveCard(4,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);$answer(2,'1');$answer(2,'p2Hand-0');
$check(intval(GetHealth(1))===17&&intval(GetHealth(2))===18&&intval(GetHealth(3))===17&&intval(GetHealth(4))===20,'Chain Lightning did not damage all opposing heroes independently.');
$check(FaBCRUArcane(4)===8,'Chain Lightning attribution lost after another seat pitched.');
$arcReset();SetTurnPlayer(4);SetPriorityPlayer(4);AddResources(4,3);AddHand(4,CardID:'chain_lightning_yellow');DoPlayCard(4,'p4Hand-0');DoResolveCard(4,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(intval(GetHealth(1))===20&&FaBARCEffect(4,'ARC_NEXT_WIZARD_INSTANT')===1,'Chain Lightning counted itself as another Wizard action.');
$arcReset();SetTurnPlayer(4);SetPriorityPlayer(4);FaBCRUAdd(4,'CHAIN_BOOST',2);FaBWTRAddEffect(4,'ARC_BOOSTED',2);AddHand(4,CardID:'absorption_dome_yellow');DoPlayCard(4,'p4Hand-0');DoResolveCard(4,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(intval(FaBObjectCounters(GetArena(4)[0])['STEAM']??0)===2,'Played Absorption Dome did not initialize steam.');DoDamage(1,'',4,3,'ARCANE');
$check(intval(GetHealth(4))===19&&count(FaBChoiceRefs(4,'Arena'))===0,'Absorption Dome did not prevent/spend/destroy.');
$arcReset();$o=$attackFixture(4,1,'righteous_cleansing_yellow');foreach(['zap_red','zap_blue','brutal_assault_red','head_shot_red','rune_flash_red','sun_kiss_red'] as $id)AddDeck(1,CardID:$id);
FaBRunSourceMacro('Hit',4,$o->CardID,['mzID'=>'p4CombatChain-0','amount'=>4]);
$check(str_starts_with(GetDecisionQueue(4)[0]->Param,'p4Temp-'),'Righteous Cleansing exposed the look to the deck owner.');
$answer(4,'p4Temp-0');$answer(4,'p4Temp-1');$answer(4,'Top=rune_flash_red,head_shot_red,brutal_assault_red');
$check(array_map(fn($ref)=>FaBIdentityFromMZ($ref)['object']->CardID,FaBChoiceRefs(1,'Deck'))===['rune_flash_red','head_shot_red','brutal_assault_red','sun_kiss_red'],'Righteous Cleansing restored cards to the wrong owner/order.');
$check(count(FaBChoiceRefs(1,'Banish'))===2&&count(FaBChoiceRefs(4,'Temp'))===0,'Righteous Cleansing failed same-name banish or leaked temp cards.');
$arcReset();SetPriorityPlayer(4);$o=$attackFixture(4,1,'brutal_assault_red');$sword=AddWeapons(4,CardID:'cintari_saber',Owner:4,Controller:4);$cruResolve(4,'twinning_blade_yellow');$answer(4,'p4Weapons-0');FaBCRUUseWeapon($sword);
$check(FaBCRUWeaponReady($sword),'Twinning Blade lost extra use on an initially unused sword.');FaBCRUUseWeapon($sword);$check(!FaBCRUWeaponReady($sword),'Twinning Blade granted more than one extra attack.');
$arcReset();SetTurnPlayer(4);SetPriorityPlayer(4);AddResources(4,4);AddEquipment(4,CardID:'metacarpus_node',Owner:4,Controller:4);AddHand(4,CardID:'snapback_red');FaBWTRAddEffect(4,'ARC_NEXT_ARCANE',1);
DoPlayCard(4,'p4Hand-0');$answer(4,'p1Hero-0');$answer(4,'1');DoResolveCard(4,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(intval(GetHealth(1))===15,'Metacarpus Node overwrote the existing arcane modifier.');FaBCRUEnd(1);$check(count(FaBChoiceRefs(4,'Equipment'))===0,'Node survived the current end phase after being used.');
$arcReset();$o=$attackFixture(4,1,'brutal_assault_red');FaBCRUAdd(4,'CHAIN_BOOST',2);FaBWTRAddEffect(4,'ARC_BOOSTED',3);FaBCRUAdd(4,'COURIER',3);FaBCloseCombatChain();
$check(FaBCRUCount(4,'CHAIN_BOOST')===0&&FaBCRUCount(4,'COURIER')===0&&FaBARCEffect(4,'ARC_BOOSTED')===3,'Closing chain confused per-chain and per-turn boost state.');
$arcReset();FaBWTRCreateArena(4,'zen_state');FaBCRUStart(4);$check(count(FaBChoiceRefs(4,'Arena'))===1,'Zen State expired one turn too soon.');FaBCRUStart(4);$check(count(FaBChoiceRefs(4,'Arena'))===0,'Zen State failed to expire after spending its counter.');
$arcReset();$o=$attackFixture(4,1,'wounding_blow_red');$source=AddGraveyard(4,CardID:'pummel_red');DecisionQueueController::StoreVariable('mzID',FaBFindUID(intval($source->UniqueID))['mzID']);FaBTagUID(intval($o->UniqueID),'WTR_POWER:4');FaBCRUAdd(4,'SNAG');
$check(FaBAttackPower(FaBGetState())===4,'Snag did not suppress a legacy attack reaction buff.');

$arcReset();$o=$attackFixture(4,1,'riled_up_red');FaBCRUAdd(4,'DISCARD_SIX');FaBCRUAttack(4,$o);$cruResolve(1,'snag_blue');
$check(FaBAttackPower(FaBGetState())===8,'Snag removed power gained before it resolved.');FaBCRUSelfTag($o,'WTR_POWER:3');
$check(FaBAttackPower(FaBGetState())===8,'Snag allowed a new self power modifier.');
$arcReset();AddHealth(4,2);$b=AddHand(4,CardID:'beast_within_yellow');FaBDiscardChoice(4,'p4Hand-0');
$check(!FaBSeatIsLive(4)&&intval(GetHealth(4))===0,'Beast Within stopped repeating on an empty deck.');
$arcReset();$o=$attackFixture(4,1,'wounding_blow_red');FaBWTRCreateArena(2,'stamp_authority_blue');$hero=&GetHero(4);$hero=[];AddHero(4,CardID:'benji_the_piercing_wind',Owner:4,Controller:4);OnHit(4,'p4CombatChain-0',1);
$check(FaBARCEffect(4,'NEXT_ATTACK')===1,'Stamp Authority wrongly suppressed Benji hero trigger.');
$arcReset();$o=AddArsenal(4,CardID:'remorseless_red',FaceDown:1);FaBARCArsenalFaceUp(4,$o,'Arsenal');
$check(!in_array('CRU_NO_ARSENAL_DR',(array)$o->TurnEffects,true),'Turning Remorseless face up granted its put-into-arsenal effect.');
$arcReset();AddWeapons(1,CardID:'reaping_blade',Owner:1,Controller:1);AddHealth(4,21);FaBCRUCoax('2');
$check(intval(GetHealth(4))===21&&intval(GetHealth(1))===21,'Coax life gain was not simultaneous with Reaping Blade.');

$arcReset();$o=$attackFixture(1,4,'wounding_blow_red');SetPriorityPlayer(4);$skeleta=AddCombatChain(4,CardID:'bloodsheath_skeleta',Owner:4,Controller:4,Role:'DEFENSE',ChainLink:1,FromZone:'Equipment');
$ref=FaBFindUID(intval($skeleta->UniqueID))['mzID'];$check(FaBWTRCanActivate(4,$ref),'Skeleta cannot activate while defending on the chain.');FaBWTRActivate(4,$ref);DoResolveCard(4,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(FaBCRUCount(4,'SKELETA_AA')===1&&FaBCRUCount(4,'SKELETA_NAA')===1,'Skeleta did not grant both separate discounts.');
// Execute every authored continuation from seat four with populated search zones.
// This catches uncompiled awaits, stale locals and helpers missing on less common variants.
$macroChecks=0;
foreach([2,4] as $seatCount)foreach($snapshot as $entry)foreach($entry['abilities'] as $ability){
    $arcReset($seatCount);$actor=$seatCount;SetTurnPlayer($actor);SetPriorityPlayer($actor);AddResources($actor,30);
    foreach(['zap_blue','head_shot_red','rune_flash_red','read_the_runes_red','induction_chamber_red','sun_kiss_red'] as $id){AddHand($actor,CardID:$id);AddDeck($actor,CardID:$id);AddGraveyard($actor,CardID:$id);}
    AddArsenal($actor,CardID:'ridge_rider_shot_red');
    $id=$entry['cardId'];$macro=$ability['macroName'];if(str_ends_with($macro,'Modifier'))continue;
    if(in_array($macro,['Hit','AttackDeclared'],true))$o=AddCombatChain($actor,CardID:$id,Owner:$actor,Controller:$actor,Role:'ATTACK',ChainLink:1);
    elseif($macro==='PrepareCard')$o=AddStack(CardID:$id,Controller:$actor,Kind:FaBHasType($id,'Attack')?'ATTACK':'ACTION',SourceZone:'Hand');
    elseif($macro==='CardPitched')$o=AddPitch($actor,CardID:$id);
    else $o=AddArena($actor,CardID:$id,Owner:$actor,Controller:$actor);
    $uid=intval($o->UniqueID);$s=FaBGetState();$s['attacker']=$actor;$s['defender']=1;$s['chainLink']=1;$s['attackUID']=$uid;$s['attackTarget']=['type'=>'HERO','player'=>1,'uid'=>GetHero(1)[0]->UniqueID,'zone'=>'Hero'];
    if($macro==='PrepareCard')$s['pendingPayment']=['player'=>$actor,'uid'=>$uid,'cost'=>0,'kind'=>'ACTION','fromZone'=>'Hand','returnWindow'=>'ACTION','returnCombatStep'=>'NONE'];
    FaBSetState($s);FaBARCSetCard($uid,'target',1);FaBARCSetCard($uid,'target2',1);
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
            'NUMBERCHOOSE'=>'0',
            'NAMECARD'=>'Zap',
            default=>throw new RuntimeException('Unhandled '.$d->Type.' for '.$id.' '.$macro)
        };
        $answer($pending,$value);
    }
    $check(!FaBHasPendingDecision(),'Continuation failed to finish: '.$id.' '.$macro);
    ++$macroChecks;
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "CRU mechanics passed; $macroChecks authored continuations exercised in duels and UPF.\n";


// Exercise the normal action/priority loop with new CRU heroes, attacks and
// weapons. The existing generic heuristic supplies choices; no CRU-only driver.
foreach([2,4] as $seats){
 $arcReset($seats);mt_srand(20260912);$profiles=[];
 foreach(FaBLiveSeats() as $p){
  $ninja=$p%2===1;$hero=&GetHero($p);$hero=[];$heroID=$ninja?'benji_the_piercing_wind':'kayo_berserker_runt';AddHero($p,CardID:$heroID,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($heroID)));
  foreach($ninja?['zephyr_needle','harmonized_kodachi']:['mandible_claw','mandible_claw'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
  foreach(['ironrot_helm','ironrot_plate','ironrot_gauntlet','ironrot_legs'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
  $pool=$ninja?['soulbead_strike_red','crane_dance_red','torrent_of_tempo_red','rushing_river_red','flying_kick_red','soulbead_strike_blue','crane_dance_blue','torrent_of_tempo_blue','rushing_river_blue','flying_kick_blue']:['riled_up_red','predatory_assault_red','barraging_big_horn_red','swing_fist_think_later_red','massacre_red','riled_up_blue','predatory_assault_blue','barraging_big_horn_blue','swing_fist_think_later_blue','brutal_assault_blue'];
  for($i=0;$i<40;++$i)AddDeck($p,CardID:$pool[$i%count($pool)]);$deck=&GetDeck($p);shuffle($deck);DoDrawCard($p,4);$profiles[$p]='fai';
 }
 $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);
 for($steps=0;$steps<5000&&!intval(GetWinner());++$steps){
  $p=BotControllerPendingPlayerForClient();
  if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('CRU match stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState()]));
 }
 $check(intval(GetWinner())>0,'CRU match exceeded 5000 steps.');
 echo "$seats-player CRU match completed in $steps steps.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
