<?php

function FaBARCAbilitySpecs(string $id): array {
    // timing, resources, destroy, go again, once per turn, steam removed, label
    $rows=[
        'blood_scent'=>[['INSTANT',0,true,false,false,0,'Gain one resource']],
        'mask_of_three_tails'=>[['INSTANT',0,true,false,false,0,'Draw a card']],
        'pouncing_paws'=>[['INSTANT',0,true,false,false,0,'Create Crouching Tiger']],
        'tearing_shuko'=>[['INSTANT',0,true,false,false,0,'Empower next Crouching Tiger']],
        'aether_conduit'=>[['ACTION',2,false,false,true,0,'Deal two arcane damage']],
        'bloodsheath_skeleta'=>[['INSTANT',0,true,false,false,0,'Reduce action costs']],
        'courage_of_bladehold'=>[['ACTION',0,true,true,false,0,'Sword attacks cost less']],
        'crater_fist'=>[['ACTION',3,true,true,false,0,'Crush attacks gain power']],
        'copper'=>[['ACTION',4,true,true,false,0,'Draw a card']],
        'kavdaen_trader_of_skins'=>[['ACTION',3,false,true,true,0,'Trade life for Copper']],
        'perch_grapplers'=>[['ACTION',2,true,true,false,0,'Face-up arrows gain go again']],
        'red_liner'=>[['ACTION',0,false,true,true,0,'Load arrow']],
        'skullhorn'=>[['ACTION',0,true,true,false,0,'Draw then discard']],
        'viziertronic_model_i'=>[['ACTION',0,true,true,false,0,'Draw and reorder when boosting']],
        'plasma_barrel_shot'=>[['ACTION',2,false,true,false,0,'Load gun']],
        'plasma_purifier_red'=>[['ACTION',1,false,true,false,0,'Add steam counter'],['ACTION',0,false,true,true,1,'Empower pistol']],
        'kano'=>[['INSTANT',3,false,false,false,0,'Look at top card']],
        'kano_dracai_of_aether'=>[['INSTANT',3,false,false,false,0,'Look at top card']],
        'azalea'=>[['ACTION',0,false,true,true,0,'Replace arsenal']],
        'azalea_ace_in_the_hole'=>[['ACTION',0,false,true,true,0,'Replace arsenal']],
        'death_dealer'=>[['ACTION',1,false,true,true,0,'Load arrow and draw']],
        'bulls_eye_bracers'=>[['ACTION',0,true,true,false,0,'Load arrow']],
        'skullbone_crosswrap'=>[['ACTION',0,false,true,true,0,'Turn arsenal face up and opt']],
        'crucible_of_aetherweave'=>[['INSTANT',1,false,false,true,0,'Increase next arcane damage']],
        'storm_striders'=>[['INSTANT',1,true,false,false,0,'Play next Wizard action as instant']],
        'robe_of_rapture'=>[['ACTION',0,true,false,false,0,'Gain three resources']],
        'mage_master_boots'=>[['ACTION',1,true,true,false,0,'Next non-attack action gains go again']],
        'grasp_of_the_arknight'=>[['ACTION',2,false,true,true,0,'Create Runechant']],
        'crown_of_dichotomy'=>[['ACTION',1,true,false,false,0,'Return Runeblade cards']],
        'talismanic_lens'=>[['INSTANT',0,true,false,false,0,'Opt two']],
        'bracers_of_belief'=>[['ACTION',0,true,true,false,0,'Reveal and empower next attack']],
        'achilles_accelerator'=>[['INSTANT',0,true,false,false,0,'Gain action point']],
        'teklo_foundry_heart'=>[['ACTION',1,false,true,true,0,'Banish two for resources']],
        'aether_sink_yellow'=>[['ACTION',1,false,true,false,0,'Add steam counter'],['INSTANT',0,false,false,false,1,'Arcane Barrier 2']],
        'cognition_nodes_blue'=>[['ACTION',1,false,true,false,0,'Add steam counter'],['REACTION',0,false,false,true,1,'Return attack on hit']],
        'induction_chamber_red'=>[['ACTION',1,false,true,false,0,'Add steam counter'],['REACTION',0,false,false,true,1,'Pistol gains go again']],
        'teklo_plasma_pistol'=>[['ACTION',1,false,true,false,0,'Load pistol']],
        'convection_amplifier_red'=>[['ACTION',0,false,true,false,1,'Next attack gains dominate']],
        'optekal_monocle_blue'=>[['ACTION',0,false,true,false,1,'Opt one']],
        'dissipation_shield_yellow'=>[['INSTANT',0,true,false,false,0,'Prevent next damage']],
    ];
    $rows=array_merge($rows,FaBMONAbilityRows(),FaBBoltynAbilityRows(),FaBELEAbilityRows(),FaBEVRAbilityRows(),FaBUPRAbilityRows(),FaBDYNAbilityRows(),FaBArakniAbilityRows(),FaBOUTAbilityRows());
    $result=[];foreach($rows[$id]??[] as $index=>$row)$result[]=array_combine(['timing','cost','destroy','goAgain','once','steam','label'],$row)+['index'=>$index,'cardID'=>$id];return $result;
}
function FaBARCAbilityLegal(int $player,array $f,array $spec): bool {
    $o=$f['object'];$id=$o->CardID;$s=FaBGetState();
    if(FaBHasType($o,'Bow')&&FaBELECount($player,'SNAP')){$spec['timing']='INSTANT';if((intval(FaBObjectCounters($o)['ELE_USE_TURN']??0)===intval(GetTurnNumber())?intval(FaBObjectCounters($o)['ELE_USES_'.$spec['index']]??0):0)<=FaBELECount($player,'SNAP'))$spec['once']=false;}
    if(FaBHasType($o,'Bow')&&intval(FaBObjectCounters($o)['EVR_BOW_TURN']??-1)===intval(GetTurnNumber())&&intval(FaBObjectCounters($o)['ELE_USES_'.$spec['index']]??0)<=intval(FaBObjectCounters($o)['EVR_BOW_USES']??0))$spec['once']=false;
    $equipped=$f['zone']==='CombatChain'&&($o->FromZone??'')==='Equipment';
    if((!FaBMONSpecialAbilityZone($f)&&!($id==='the_hand_that_pulls_the_strings'&&$f['zone']==='Arsenal')&&!$equipped&&!($o->CardID==='firebreathing_red'&&$f['zone']==='CombatChain')&&!in_array($f['zone'],['Equipment','Weapons','Hero','Arena'],true))||HasNoAbilities($o))return false;
    if(!FaBELEAbilityLegal($player,$f,$spec)||!FaBEVRAbilityLegal($player,$f,$spec))return false;
    if(!FaBDYNAbilityLegal($player,$f,$spec)||!FaBArakniAbilityLegal($player,$f))return false;
    if(!FaBOUTAbilityLegal($player,$f,$spec))return false;
    if(!FaBUPRAbilityLegal($player,$f,$spec))return false;
    if(!FaBMONAbilityLegal($player,$f))return false;
    if($id==='radiant_touch'&&FaBMONSoul($player)==='')return false;
    if($f['zone']==='Hero'&&!FaBWTRHeroActive($player))return false;
    if($id==='blood_scent'&&!FaBARCEffect($player,'IRA_ATTACKED_TIGER'))return false;
    if($id==='mask_of_three_tails'&&(intval($s['attacker'])!==$player||intval($s['chainHits']??0)<3))return false;
    if($spec['once']&&intval(FaBObjectCounters($o)['ARC_USED_'.$spec['index']]??0)===intval(GetTurnNumber()))return false;
    if($spec['steam']>intval(FaBObjectCounters($o)['STEAM']??0))return false;
    if(in_array($s['window'],['PITCH','DEFEND_DECLARE'],true))return false;
    if($spec['timing']==='ACTION'&&($player!==intval(GetTurnPlayer())||!in_array($s['window'],$id==='emperor_dracai_of_aesir'?['ACTION','RESOLUTION']:['ACTION'],true)||intval(GetActionPoints($player))<1))return false;
    if($spec['timing']==='ACTION'&&FaBARCEffect($player,'ARC_LEDGER')&&FaBARCEffect($player,'ARC_ACTIONS')>=1)return false;
    if($spec['timing']==='REACTION'){
        if($s['window']!=='REACTION'||intval($s['attacker'])!==$player)return false;
        $a=FaBFindUID(intval($s['attackUID']));if($a===null)return false;
        if($id==='induction_chamber_red'&&(!FaBHasType($a['object'],'Mechanologist')||!FaBHasType($a['object'],'Pistol')))return false;
        if($id==='cognition_nodes_blue'&&!FaBWTRIsAttackAction($a['object']))return false;
    }
    if(in_array($id,['achilles_accelerator','teklo_foundry_heart'],true)&&FaBARCEffect($player,'ARC_BOOSTED')===0)return false;
    if(in_array($id,['aether_sink_yellow','induction_chamber_red','cognition_nodes_blue','teklo_plasma_pistol','plasma_barrel_shot','plasma_purifier_red'],true)&&$spec['index']===0&&intval(FaBObjectCounters($o)['STEAM']??0)>0)return false;
    if($id==='skullbone_crosswrap'&&!array_filter(GetArsenal($player),fn($a)=>is_object($a)&&empty($a->removed)&&intval($a->FaceDown??1)===1))return false;
    if($id==='crown_of_dichotomy'&&(FaBARCSelect($player,'Graveyard','Runeblade','AA')===''||FaBARCSelect($player,'Graveyard','Runeblade','NAA')===''))return false;
    if($id==='grasp_of_the_arknight')$spec['cost']+=FaBARCRunechants($player);
    return FaBAvailablePitch($player)>=FaBWTRAbilityCost($player,$spec);
}
function FaBARCAbilityActions(int $player,array $f): array {
    if($f['player']!==$player&&$f['object']->CardID!=='great_library_of_solana')return [];
    if(!FaBSeatIsLive($player)||intval(GetWinner())||intval(GetPriorityPlayer())!==$player||FaBHasPendingDecision()||FaBGetState()['pendingPayment']!==null)return [];
    $out=[];foreach(FaBARCAbilitySpecs($f['object']->CardID) as $spec)if(FaBARCAbilityLegal($player,$f,$spec))$out[$spec['index']]=$spec;return $out;
}
function FaBARCActivate(int $player,array $f,int $index): bool {
    $spec=FaBARCAbilityActions($player,$f)[$index]??null;if($spec===null)return false;
    $o=$f['object'];$s=FaBGetState();
    $emperorTarget=null;if($o->CardID==='emperor_dracai_of_aesir'){$emperorTarget=FaBClaimOrRequestAttackTarget($player,intval($o->UniqueID),'ACTIVATE');if($emperorTarget===null)return true;if($emperorTarget===false)return false;$s=FaBGetState();}
    if(FaBHasType($o,'Bow')&&FaBELECount($player,'SNAP'))$spec['timing']='INSTANT';
    $cost=FaBWTRAbilityCost($player,$spec)+($o->CardID==='grasp_of_the_arknight'?FaBARCRunechants($player):0);
    $stack=AddStack(CardID:$o->CardID,Controller:$player,Kind:'ABILITY',SourceZone:$f['zone'],SourceUniqueID:intval($o->UniqueID),Params:['arcSpec'=>$spec,'returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep'],'attackUID'=>intval($s['attackUID']),'dynEmperorTarget'=>$emperorTarget]);
    $s['pendingPayment']=['player'=>$player,'uid'=>intval($stack->UniqueID),'cost'=>$cost,'fromZone'=>$f['zone'],'kind'=>'ABILITY','isAbility'=>true,'abilityAction'=>$spec['timing']==='ACTION','returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep']];
    $s['window']='PITCH';FaBSetState($s);SetConsecutivePasses(0);
    if((isset(FaBMONAbilityRows()[$o->CardID])||isset(FaBBoltynAbilityRows()[$o->CardID])||isset(FaBELEAbilityRows()[$o->CardID]))&&FaBRunSourceMacro('PrepareCard',$player,$o->CardID,['mzID'=>FaBFindUID(intval($stack->UniqueID))['mzID']]))return true;
    if(FaBOUTPrepareAbility($player,$stack))return true;
    return FaBTryCompletePayment();
}
function FaBARCPayAbility(int $player,object $stack): void {
    $spec=$stack->Params['arcSpec'];if($spec['timing']==='REACTION')FaBOUTReactionPlayed($player,true);$f=FaBFindUID(intval($stack->SourceUniqueID));if($f===null)return;$o=$f['object'];
    if($o->CardID==='micro_processor_blue'){if(intval(FaBObjectCounters($o)['EVR_TURN']??-1)!==intval(GetTurnNumber())){AddActionPoints($player,intval(GetActionPoints($player))+1);FaBSetObjectCounter($o,'EVR_TURN',intval(GetTurnNumber()));}}
    if($spec['timing']==='ACTION')FaBARCRecordAction($player);
    if(FaBHasType($o,'Bow')){if(intval(FaBObjectCounters($o)['ELE_USE_TURN']??0)!==intval(GetTurnNumber())){foreach(array_keys(FaBObjectCounters($o)) as $k)if(str_starts_with($k,'ELE_USES_'))FaBSetObjectCounter($o,$k,0);}FaBSetObjectCounter($o,'ELE_USE_TURN',intval(GetTurnNumber()));FaBSetObjectCounter($o,'ELE_USES_'.$spec['index'],intval(FaBObjectCounters($o)['ELE_USES_'.$spec['index']]??0)+1);}
    if($spec['once'])FaBSetObjectCounter($o,'ARC_USED_'.$spec['index'],intval(GetTurnNumber()));
    FaBARCSetCard(intval($stack->UniqueID),'steam',intval(FaBObjectCounters($o)['STEAM']??0));
    FaBUPRAbilityPaid($player,$o);
    if($o->CardID==='imperial_ledger_red'){FaBMoveUID(intval($o->UniqueID),'Deck',$player);FaBShuffleDeck($player);}
    if($spec['steam'])FaBARCSteam($o,-intval($spec['steam']));
    if($spec['destroy'])FaBMONDestroy(intval($o->UniqueID));
    if($o->CardID==='radiant_touch')FaBMoveUID(intval($o->UniqueID),'Banish',$player);
    if($o->CardID==='skullbone_crosswrap')foreach(GetArsenal($player) as $a)if(is_object($a)&&empty($a->removed)&&intval($a->FaceDown??1)===1){$a->FaceDown=0;break;}
}
function FaBARCResolveAbility(int $player,object $stack): void {
    $spec=$stack->Params['arcSpec'];
    DecisionQueueController::StoreVariable('dynEmperorTarget',$stack->Params['dynEmperorTarget']??null);
    DecisionQueueController::StoreVariable('dynPitchAA',FaBDYNPitched(intval($stack->UniqueID),'AA'));
    DecisionQueueController::StoreVariable('dynPitchNAA',FaBDYNPitched(intval($stack->UniqueID),'NAA'));
    DecisionQueueController::StoreVariable('monAbility_monDraw',FaBARCCard(intval($stack->UniqueID),'monDraw'));
    foreach(['Ice','Lightning'] as $e)DecisionQueueController::StoreVariable('eleAbility'.$e,FaBARCCard(intval($stack->UniqueID),'eleAbility'.$e));
    foreach(['Earth','Ice','Lightning'] as $e)DecisionQueueController::StoreVariable('elePitched'.$e,FaBARCCard(intval($stack->UniqueID),'elePitched'.$e));
    DecisionQueueController::StoreVariable('uprVictim',0);
    DecisionQueueController::StoreVariable('fabAbilityStackUID',intval($stack->UniqueID));
    DecisionQueueController::StoreVariable('arcAbilityIndex',intval($spec['index']));
    DecisionQueueController::StoreVariable('arcAbilitySteam',intval(FaBARCCard(intval($stack->UniqueID),'steam')));
    DecisionQueueController::StoreVariable('arcAttackUID',intval($stack->Params['attackUID']??0));
    $f=FaBFindUID(intval($stack->SourceUniqueID));
    FaBRunSourceMacro('ResolveAbility',$player,$stack->CardID,['mzID'=>$f['mzID']??'']);
    if($spec['goAgain']&&FaBWTRMayGoAgain($player))AddActionPoints($player,intval(GetActionPoints($player))+1);
}
function FaBARCSteam(object $o,int $change): void {
    $n=max(0,intval(FaBObjectCounters($o)['STEAM']??0)+$change);FaBSetObjectCounter($o,'STEAM',$n);
    if($n===0&&in_array($o->CardID,['convection_amplifier_red','optekal_monocle_blue','hyper_driver_red','hyper_driver_yellow','hyper_driver_blue','plasma_mainline_red','teklo_core_blue','absorption_dome_yellow'],true))FaBMoveUID(intval($o->UniqueID),'Graveyard');
}
function FaBARCEnterItem(int $player,object $o): void {
    FaBCRUItemEntered($player,$o);
    $n=['aether_sink_yellow'=>1,'convection_amplifier_red'=>2,'dissipation_shield_yellow'=>4,'hyper_driver_red'=>3,'optekal_monocle_blue'=>5,'teklo_core_blue'=>2][$o->CardID]??0;
    if($n)FaBSetObjectCounter($o,'STEAM',$n);
    FaBDYNEnterItem($player,$o);
}
function FaBARCBoost(int $player,int $uid): void {
    $refs=FaBChoiceRefs($player,'Deck');if(!$refs)return;
    $f=FaBIdentityFromMZ($refs[0]);$mech=FaBHasType($f['object'],'Mechanologist');
    $banishedUID=intval($f['object']->UniqueID);FaBMoveUID($banishedUID,'Banish',$player);
    if($mech)FaBTagUID($uid,'GO_AGAIN');
    FaBWTRAddEffect($player,'ARC_BOOSTED',1);
    FaBCRUBoost($player,$uid);
    FaBEVRBoost($player,$uid);
    $ap=FaBARCEffect($player,'ARC_OCTANE');if($ap)AddActionPoints($player,intval(GetActionPoints($player))+$ap);
    foreach(GetArena($player) as $o)if(is_object($o)&&empty($o->removed)&&FaBWTRBase($o->CardID)==='hyper_driver'&&intval(FaBObjectCounters($o)['ARC_DRIVER_TURN']??0)!==intval(GetTurnNumber())){
        FaBSetObjectCounter($o,'ARC_DRIVER_TURN',intval(GetTurnNumber()));FaBARCSteam($o,-1);AddResources($player,intval(GetResources($player))+1);
    }
    FaBDYNBoost($player,$uid,$banishedUID);
}
