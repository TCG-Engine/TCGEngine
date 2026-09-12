<?php

function FaBARCAbilitySpecs(string $id): array {
    // timing, resources, destroy, go again, once per turn, steam removed, label
    $rows=[
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
    $result=[];foreach($rows[$id]??[] as $index=>$row)$result[]=array_combine(['timing','cost','destroy','goAgain','once','steam','label'],$row)+['index'=>$index,'cardID'=>$id];return $result;
}
function FaBARCAbilityLegal(int $player,array $f,array $spec): bool {
    $o=$f['object'];$id=$o->CardID;$s=FaBGetState();
    if(!in_array($f['zone'],['Equipment','Weapons','Hero','Arena'],true))return false;
    if($f['zone']==='Hero'&&!FaBWTRHeroActive($player))return false;
    if($spec['once']&&intval(FaBObjectCounters($o)['ARC_USED_'.$spec['index']]??0)===intval(GetTurnNumber()))return false;
    if($spec['steam']>intval(FaBObjectCounters($o)['STEAM']??0))return false;
    if(in_array($s['window'],['PITCH','DEFEND_DECLARE'],true))return false;
    if($spec['timing']==='ACTION'&&($player!==intval(GetTurnPlayer())||$s['window']!=='ACTION'||intval(GetActionPoints($player))<1))return false;
    if($spec['timing']==='ACTION'&&FaBARCEffect($player,'ARC_LEDGER')&&FaBARCEffect($player,'ARC_ACTIONS')>=1)return false;
    if($spec['timing']==='REACTION'){
        if($s['window']!=='REACTION'||intval($s['attacker'])!==$player)return false;
        $a=FaBFindUID(intval($s['attackUID']));if($a===null)return false;
        if($id==='induction_chamber_red'&&(!FaBHasType($a['object'],'Mechanologist')||!FaBHasType($a['object'],'Pistol')))return false;
        if($id==='cognition_nodes_blue'&&!FaBWTRIsAttackAction($a['object']))return false;
    }
    if(in_array($id,['achilles_accelerator','teklo_foundry_heart'],true)&&FaBARCEffect($player,'ARC_BOOSTED')===0)return false;
    if(in_array($id,['aether_sink_yellow','induction_chamber_red','cognition_nodes_blue','teklo_plasma_pistol'],true)&&$spec['index']===0&&intval(FaBObjectCounters($o)['STEAM']??0)>0)return false;
    if($id==='skullbone_crosswrap'&&!array_filter(GetArsenal($player),fn($a)=>is_object($a)&&empty($a->removed)&&intval($a->FaceDown??1)===1))return false;
    if($id==='crown_of_dichotomy'&&(FaBARCSelect($player,'Graveyard','Runeblade','AA')===''||FaBARCSelect($player,'Graveyard','Runeblade','NAA')===''))return false;
    if($id==='grasp_of_the_arknight')$spec['cost']+=FaBARCRunechants($player);
    return FaBAvailablePitch($player)>=FaBWTRAbilityCost($player,$spec);
}
function FaBARCAbilityActions(int $player,array $f): array {
    if(!FaBSeatIsLive($player)||intval(GetWinner())||intval(GetPriorityPlayer())!==$player||FaBHasPendingDecision()||FaBGetState()['pendingPayment']!==null)return [];
    $out=[];foreach(FaBARCAbilitySpecs($f['object']->CardID) as $spec)if(FaBARCAbilityLegal($player,$f,$spec))$out[$spec['index']]=$spec;return $out;
}
function FaBARCActivate(int $player,array $f,int $index): bool {
    $spec=FaBARCAbilityActions($player,$f)[$index]??null;if($spec===null)return false;
    $o=$f['object'];$s=FaBGetState();
    $cost=FaBWTRAbilityCost($player,$spec)+($o->CardID==='grasp_of_the_arknight'?FaBARCRunechants($player):0);
    $stack=AddStack(CardID:$o->CardID,Controller:$player,Kind:'ABILITY',SourceZone:$f['zone'],SourceUniqueID:intval($o->UniqueID),Params:['arcSpec'=>$spec,'returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep'],'attackUID'=>intval($s['attackUID'])]);
    $s['pendingPayment']=['player'=>$player,'uid'=>intval($stack->UniqueID),'cost'=>$cost,'fromZone'=>$f['zone'],'kind'=>'ABILITY','isAbility'=>true,'abilityAction'=>$spec['timing']==='ACTION','returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep']];
    $s['window']='PITCH';FaBSetState($s);SetConsecutivePasses(0);return FaBTryCompletePayment();
}
function FaBARCPayAbility(int $player,object $stack): void {
    $spec=$stack->Params['arcSpec'];$f=FaBFindUID(intval($stack->SourceUniqueID));if($f===null)return;$o=$f['object'];
    if($spec['timing']==='ACTION')FaBARCRecordAction($player);
    if($spec['once'])FaBSetObjectCounter($o,'ARC_USED_'.$spec['index'],intval(GetTurnNumber()));
    FaBARCSetCard(intval($stack->UniqueID),'steam',intval(FaBObjectCounters($o)['STEAM']??0));
    if($spec['steam'])FaBARCSteam($o,-intval($spec['steam']));
    if($spec['destroy'])FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);
    if($o->CardID==='skullbone_crosswrap')foreach(GetArsenal($player) as $a)if(is_object($a)&&empty($a->removed)&&intval($a->FaceDown??1)===1){$a->FaceDown=0;break;}
}
function FaBARCResolveAbility(int $player,object $stack): void {
    $spec=$stack->Params['arcSpec'];
    DecisionQueueController::StoreVariable('arcAbilityIndex',intval($spec['index']));
    DecisionQueueController::StoreVariable('arcAbilitySteam',intval(FaBARCCard(intval($stack->UniqueID),'steam')));
    DecisionQueueController::StoreVariable('arcAttackUID',intval($stack->Params['attackUID']??0));
    $f=FaBFindUID(intval($stack->SourceUniqueID));
    FaBRunSourceMacro('ResolveAbility',$player,$stack->CardID,['mzID'=>$f['mzID']??'']);
    if($spec['goAgain']&&FaBWTRMayGoAgain($player))AddActionPoints($player,intval(GetActionPoints($player))+1);
}
function FaBARCSteam(object $o,int $change): void {
    $n=max(0,intval(FaBObjectCounters($o)['STEAM']??0)+$change);FaBSetObjectCounter($o,'STEAM',$n);
    if($n===0&&in_array($o->CardID,['convection_amplifier_red','optekal_monocle_blue','hyper_driver_red','teklo_core_blue'],true))FaBMoveUID(intval($o->UniqueID),'Graveyard');
}
function FaBARCEnterItem(int $player,object $o): void {
    $n=['aether_sink_yellow'=>1,'convection_amplifier_red'=>2,'dissipation_shield_yellow'=>4,'hyper_driver_red'=>3,'optekal_monocle_blue'=>5,'teklo_core_blue'=>2][$o->CardID]??0;
    if($n)FaBSetObjectCounter($o,'STEAM',$n);
}
function FaBARCBoost(int $player,int $uid): void {
    $refs=FaBChoiceRefs($player,'Deck');if(!$refs)return;
    $f=FaBIdentityFromMZ($refs[0]);$mech=FaBHasType($f['object'],'Mechanologist');
    FaBMoveUID(intval($f['object']->UniqueID),'Banish',$player);
    if($mech)FaBTagUID($uid,'GO_AGAIN');
    FaBWTRAddEffect($player,'ARC_BOOSTED',1);
    $ap=FaBARCEffect($player,'ARC_OCTANE');if($ap)AddActionPoints($player,intval(GetActionPoints($player))+$ap);
    foreach(GetArena($player) as $o)if(is_object($o)&&empty($o->removed)&&$o->CardID==='hyper_driver_red'&&intval(FaBObjectCounters($o)['ARC_DRIVER_TURN']??0)!==intval(GetTurnNumber())){
        FaBSetObjectCounter($o,'ARC_DRIVER_TURN',intval(GetTurnNumber()));FaBARCSteam($o,-1);AddResources($player,intval(GetResources($player))+1);
    }
}
