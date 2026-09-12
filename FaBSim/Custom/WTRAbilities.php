<?php

/** Activation metadata; the common payment and stack paths own timing. */
function FaBWTRAbilitySpec(string $id): ?array {
    $specs=[
        'fai'=>['INSTANT',3,false,false,true],
        'stubby_hammerers'=>['ACTION',0,true,true,false],
        'scabskin_leathers'=>['ACTION',0,false,false,true],
        'bravo'=>['ACTION',2,false,true,false], 'bravo_showstopper'=>['ACTION',2,false,true,false],
        'tectonic_plating'=>['ACTION',1,false,true,true],
        'helm_of_isens_peak'=>['ACTION',1,true,false,false],
        'braveforge_bracers'=>['ACTION',1,false,true,true],
        'heartened_cross_strap'=>['ACTION',0,true,true,false],
        'goliath_gauntlet'=>['ACTION',0,true,true,false],
        'potion_of_strength_blue'=>['ACTION',0,true,true,false],
        'timesnap_potion_blue'=>['ACTION',0,true,false,false],
        'crazy_brew_blue'=>['ACTION',0,true,false,false],
        'barkbone_strapping'=>['INSTANT',0,true,false,false],
        'energy_potion_blue'=>['INSTANT',0,true,false,false],
        'fyendals_spring_tunic'=>['INSTANT',0,false,false,false],
        'hope_merchants_hood'=>['INSTANT',0,true,false,false],
        'breaking_scales'=>['REACTION',0,true,false,false],
        'snapdragon_scalers'=>['REACTION',0,true,false,false],
    ];
    if(!isset($specs[$id]))return null;
    return array_combine(['timing','cost','destroy','goAgain','once'],$specs[$id])+['cardID'=>$id];
}

function FaBWTRAbilityCost(int $player, array $spec): int {
    $cost=$spec['cost'];
    if(($spec['cardID']??'')==='fai')$cost-=FaBFaiChainCount($player);
    if($spec['timing']==='ACTION')foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='FIRST_ACTION_COST')$cost+=intval($effect['amount']);
    return max(0,$cost);
}

function FaBWTRAbilityLegal(int $player, array $found, array $spec): bool {
    $obj=$found['object'];$state=FaBGetState();
    $inEquipment=$found['zone']==='Equipment'||($found['zone']==='CombatChain'&&($obj->FromZone??'')==='Equipment');
    if(!$inEquipment&&!in_array($found['zone'],['Hero','Arena'],true))return false;
    if($found['zone']==='Hero'&&!FaBWTRHeroActive($player))return false;
    if($spec['once']&&intval(FaBObjectCounters($obj)['ACTIVATED_TURN']??0)===intval(GetTurnNumber()))return false;
    if($spec['timing']==='ACTION'&&($player!==intval(GetTurnPlayer())||$state['window']!=='ACTION'||intval(GetActionPoints($player))<1))return false;
    if($spec['timing']==='ACTION'&&FaBARCEffect($player,'ARC_LEDGER')&&FaBARCEffect($player,'ARC_ACTIONS')>=1)return false;
    if($spec['timing']==='REACTION'){
        if($state['window']!=='REACTION'||intval($state['attacker'])!==$player)return false;
        $attack=FaBFindUID(intval($state['attackUID']));
        if($attack===null||!FaBWTRIsAttackAction($attack['object']))return false;
        if($obj->CardID==='breaking_scales'&&!FaBHasKeyword($attack['object'],'Combo'))return false;
        if($obj->CardID==='snapdragon_scalers'&&intval(CardCost($attack['object']->CardID))>1)return false;
    }
    if(in_array($state['window'],['PITCH','DEFEND_DECLARE'],true))return false;
    if($obj->CardID==='fyendals_spring_tunic'&&intval(FaBObjectCounters($obj)['ENERGY']??0)<3)return false;
    if($obj->CardID==='braveforge_bracers'){
        $hit=false;foreach(GetWeapons($player)as$weapon)if(is_object($weapon)&&!empty($state['weaponHits'][(string)$weapon->UniqueID]))$hit=true;
        if(!$hit)return false;
    }
    return FaBAvailablePitch($player)>=FaBWTRAbilityCost($player,$spec);
}

function FaBWTRAnnounceAbility(int $player, array $found, array $spec): bool {
    $obj=$found['object'];$state=FaBGetState();
    $stack=AddStack(CardID:$obj->CardID,Controller:$player,Kind:'ABILITY',SourceZone:$found['zone'],SourceUniqueID:intval($obj->UniqueID),
        Params:['returnWindow'=>$state['window'],'returnCombatStep'=>$state['combatStep'],'attackUID'=>intval($state['attackUID'])]);
    $state['pendingPayment']=['player'=>$player,'uid'=>intval($stack->UniqueID),'cost'=>FaBWTRAbilityCost($player,$spec),'fromZone'=>$found['zone'],
        'kind'=>'ABILITY','isAbility'=>true,'abilityAction'=>$spec['timing']==='ACTION','returnWindow'=>$state['window'],'returnCombatStep'=>$state['combatStep']];
    $state['window']='PITCH';FaBSetState($state);SetConsecutivePasses(0);
    return FaBTryCompletePayment();
}

function FaBWTRPayAbilityCosts(int $player, object $stack): void {
    if(isset($stack->Params['arcSpec'])){FaBARCPayAbility($player,$stack);return;}
    $spec=FaBWTRAbilitySpec($stack->CardID);$found=FaBFindUID(intval($stack->SourceUniqueID));
    if($spec===null||$found===null)return;
    if($spec['timing']==='ACTION')FaBARCRecordAction($player);
    $obj=$found['object'];
    if($spec['once'])FaBSetObjectCounter($obj,'ACTIVATED_TURN',intval(GetTurnNumber()));
    if($stack->CardID==='fyendals_spring_tunic')FaBSetObjectCounter($obj,'ENERGY',intval(FaBObjectCounters($obj)['ENERGY']??0)-3);
    if($spec['destroy'])FaBMoveUID(intval($obj->UniqueID),'Graveyard',$player);
    if($spec['timing']==='ACTION')FaBWTRSetEffects($player,array_values(array_filter(FaBWTREffects($player),fn($e)=>($e['type']??'')!=='FIRST_ACTION_COST')));
}

function FaBWTRResolveAbility(int $player, object $stack): void {
    if(isset($stack->Params['arcSpec'])){FaBARCResolveAbility($player,$stack);return;}
    $id=$stack->CardID;$spec=FaBWTRAbilitySpec($id);
    if($spec===null)return;
    $source=FaBFindUID(intval($stack->SourceUniqueID));
    $ran=FaBRunSourceMacro('ResolveAbility',$player,$id,['mzID'=>$source['mzID']??'']);
    if(!$ran)switch($id){
        case 'scabskin_leathers': AddActionPoints($player,intval(GetActionPoints($player))+intdiv(EngineRandomInt(1,6),2)); break;
        case 'barkbone_strapping': AddResources($player,intval(GetResources($player))+intdiv(EngineRandomInt(1,6),2)); break;
        case 'energy_potion_blue': AddResources($player,intval(GetResources($player))+2); break;
        case 'fyendals_spring_tunic': AddResources($player,intval(GetResources($player))+1); break;
        case 'timesnap_potion_blue': AddActionPoints($player,intval(GetActionPoints($player))+2); break;
        case 'potion_of_strength_blue': FaBWTRAddEffect($player,'NEXT_ATTACK',2); break;
        case 'bravo': case 'bravo_showstopper': FaBWTRAddEffect($player,'BRAVO_DOMINATE'); break;
        case 'tectonic_plating': FaBWTRCreateArena($player,'seismic_surge'); break;
        case 'helm_of_isens_peak': FaBWTRAddEffect($player,'INTELLECT',1); break;
        case 'braveforge_bracers': FaBWTRAddEffect($player,'NEXT_WEAPON',1); break;
        case 'heartened_cross_strap': FaBWTRAddEffect($player,'NEXT_COST',2); break;
        case 'goliath_gauntlet': FaBWTRAddEffect($player,'NEXT_AA_HIGH',2); break;
        case 'breaking_scales': FaBTagUID(intval($stack->Params['attackUID']),'WTR_POWER:1'); break;
        case 'snapdragon_scalers': FaBTagUID(intval($stack->Params['attackUID']),'GO_AGAIN'); break;
        case 'crazy_brew_blue':
            $roll=EngineRandomInt(1,6);
            if($roll<=2){AddHealth($player,max(0,intval(GetHealth($player))-2));if(intval(GetHealth($player))===0)FaBEliminateSeat($player);}
            elseif($roll<=4)FaBCRUGainLife($player,2);
            else{AddResources($player,intval(GetResources($player))+2);AddActionPoints($player,intval(GetActionPoints($player))+2);FaBWTRAddEffect($player,'NEXT_ATTACK',2);}
            if($roll<=4&&FaBWTRMayGoAgain($player))AddActionPoints($player,intval(GetActionPoints($player))+1);
            break;
    }
    if($spec['goAgain']&&FaBWTRMayGoAgain($player))AddActionPoints($player,intval(GetActionPoints($player))+1);
}
