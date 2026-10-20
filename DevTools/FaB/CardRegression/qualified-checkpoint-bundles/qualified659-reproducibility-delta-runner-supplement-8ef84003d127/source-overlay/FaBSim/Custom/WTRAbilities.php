<?php

/** Activation metadata; the common payment and stack paths own timing. */
function FaBWTRAbilitySpec(string $id): ?array {
    $specs=[
        'fai'=>['INSTANT',3,false,false,true], 'fai_rising_rebellion'=>['INSTANT',3,false,false,true],
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
    if (FaBHasType($spec['cardID']??'', 'Draconic')) $spec['cost']=max(0,intval($spec['cost'])-FaBARCEffect($player,'OMN_DRACO_COST'));
    if (($spec['cardID']??'')==='aphrodias') $spec['cost']=max(0,intval($spec['cost'])-FaBARCEffect($player,'OMN_STARFIELD'));
    if(FaBMSTChiCost($spec['cardID']??''))return FaBMSTChiCost($spec['cardID'])+max(0,FaBELETax($player)-FaBEVRCount($player,'IGNITION'));
    $cost=$spec['cost']+FaBHNTAbilityCost($player,$spec)-(($spec['cardID']??'')==='volzar_the_lightning_rod'&&FaBROSSigils($player)!==''?1:0)+FaBHVYAbilityCost($player,$spec)+FaBEVOAbilityCost($player,$spec)+FaBDTDAbilityCost($player,$spec)+FaBELETax($player)-FaBEVRCount($player,'IGNITION');
    if(FaBHasType($spec['cardID']??'','Staff'))$cost-=FaBUPRCount($player,'STAFF_DISCOUNT');
    if(in_array($spec['cardID']??'',['fai','fai_rising_rebellion'],true))$cost-=FaBFaiChainCount($player);
    if($spec['timing']==='ACTION')foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='FIRST_ACTION_COST')$cost+=intval($effect['amount']);
    return max(0,$cost);
}

function FaBWTRAbilityLegal(int $player, array $found, array $spec): bool {
    $obj=$found['object'];$state=FaBGetState();
    if(!FaBDTDRestrictions($player,$obj,$spec['timing']==='ACTION',false))return false;
    if(FaBUPRLocked($player)||FaBUPRFrozen($obj))return false;
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
    if(!FaBDTDRestrictions($player,$obj,$spec['timing']==='ACTION',false))return false;
    if(FaBUPRLocked($player)||FaBUPRFrozen($obj))return false;
    $stack=AddStack(CardID:$obj->CardID,Controller:$player,Kind:'ABILITY',SourceZone:$found['zone'],SourceUniqueID:intval($obj->UniqueID),
        Params:['returnWindow'=>$state['window'],'returnCombatStep'=>$state['combatStep'],'attackUID'=>intval($state['attackUID'])]);
    $state['pendingPayment']=['player'=>$player,'uid'=>intval($stack->UniqueID),'cost'=>FaBWTRAbilityCost($player,$spec),'fromZone'=>$found['zone'],
        'kind'=>'ABILITY','isAbility'=>true,'abilityAction'=>$spec['timing']==='ACTION','returnWindow'=>$state['window'],'returnCombatStep'=>$state['combatStep']];
    $state['window']='PITCH';FaBSetState($state);SetConsecutivePasses(0);
    return FaBTryCompletePayment();
}

function FaBWTRPayAbilityCosts(int $player, object $stack): void {
    FaBEVRAdd($player,'ACTIVATED');FaBEVRClear($player,'IGNITION');
    FaBELEActivated($player,$stack);
    $reactionSpec=$stack->Params['arcSpec']??FaBWTRAbilitySpec($stack->CardID);FaBArakniReactionEvent($player,($reactionSpec['timing']??'')==='REACTION');
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

/** Put a hit-triggered draw on the existing ability stack before granting priority. */
function FaBWTRQueueDrawOnHit(int $player, object $attack): void {
    if (!FaBSeatIsLive($player) || HasNoAbilities($attack)) return;
    $state = FaBGetState();
    AddStack(CardID:$attack->CardID, Owner:$player, Controller:$player, Kind:'ABILITY',
        SourceZone:'CombatChain', SourceUniqueID:intval($attack->UniqueID),
        Params:['wtrDrawTrigger'=>true, 'returnWindow'=>(string)$state['window'],
            'returnCombatStep'=>(string)$state['combatStep']]);
    $state = FaBGetState(); $state['window'] = 'PRIORITY'; FaBSetState($state);
}

/** Queue the play trigger; its conditional grant is evaluated on resolution. */
function FaBWTRQueueLifeOnPlay(int $player, object $source, string $tag): void {
    if (!FaBSeatIsLive($player) || HasNoAbilities($source)) return;
    if (!in_array($tag, ['GO_AGAIN', 'WTR_WOUNDED_BULL'], true)) return;
    $state = FaBGetState();
    AddStack(CardID:$source->CardID, Owner:$player, Controller:$player, Kind:'ABILITY',
        SourceZone:'Stack', SourceUniqueID:intval($source->UniqueID),
        Params:['wtrLifeTriggerTag'=>$tag, 'returnWindow'=>(string)$state['window'],
            'returnCombatStep'=>(string)$state['combatStep']]);
    $state = FaBGetState(); $state['window'] = 'PRIORITY'; FaBSetState($state);
}

/** Queue Quicken's play/activation trigger before granting go again. */
function FaBWTRQueueQuicken(int $player, object $token, object $attack): void {
    if (!FaBSeatIsLive($player) || HasNoAbilities($token)) return;
    $state = FaBGetState();
    AddStack(CardID:'quicken', Owner:$player, Controller:$player, Kind:'ABILITY',
        SourceZone:'Arena', SourceUniqueID:intval($token->UniqueID),
        Params:['wtrQuickenAttackUID'=>intval($attack->UniqueID),
            'returnWindow'=>(string)$state['window'], 'returnCombatStep'=>(string)$state['combatStep']]);
    $state = FaBGetState(); $state['window'] = 'PRIORITY'; FaBSetState($state);
}

/** Flock creates its token when the attack trigger resolves. */
function FaBWTRQueueFlockToken(int $player, object $attack): void {
    if (!FaBSeatIsLive($player) || HasNoAbilities($attack)) return;
    $state = FaBGetState();
    AddStack(CardID:$attack->CardID, Owner:$player, Controller:$player, Kind:'ABILITY',
        SourceZone:'CombatChain', SourceUniqueID:intval($attack->UniqueID),
        Params:['wtrFlockTokenTrigger'=>true, 'returnWindow'=>(string)$state['window'],
            'returnCombatStep'=>(string)$state['combatStep']]);
    $state = FaBGetState(); $state['window'] = 'PRIORITY'; FaBSetState($state);
}

/** Granted hit abilities use the same priority/ability stack as printed hit abilities. */
function FaBWTRQueueGrantedHit(int $player, object $attack, string $effect, int $victim = 0): void {
    // Plain base-ability loss does not remove these effects-granted abilities.
    // The hit dispatcher retains its existing explicit hit-prohibition checks.
    if (!FaBSeatIsLive($player) || !in_array($effect, ['DISCARD', 'GO_AGAIN'], true)) return;
    if ($effect === 'DISCARD' && ($victim < 1 || $victim > 4)) return;
    $state = FaBGetState();
    AddStack(CardID:$attack->CardID, Owner:$player, Controller:$player, Kind:'ABILITY',
        SourceZone:'CombatChain', SourceUniqueID:intval($attack->UniqueID),
        Params:['wtrGrantedHitEffect'=>$effect, 'wtrHitVictim'=>$victim,
            'wtrHitChainLink'=>intval($attack->ChainLink ?? 0),
            'returnWindow'=>(string)$state['window'], 'returnCombatStep'=>(string)$state['combatStep']]);
    $state = FaBGetState(); $state['window'] = 'PRIORITY'; FaBSetState($state);
}

function FaBWTRResolveAbility(int $player, object $stack): void {
    if (isset($stack->Params['wtrGrantedHitEffect'])) {
        // A triggered layer survives loss or suppression of the source ability.
        if ($stack->Params['wtrGrantedHitEffect'] === 'DISCARD') {
            FaBRequestDiscard(intval($stack->Params['wtrHitVictim'] ?? 0));
        } elseif ($stack->Params['wtrGrantedHitEffect'] === 'GO_AGAIN') {
            $attack = FaBFindUID(intval($stack->SourceUniqueID));
            // Preserve source identity even if this physical UID is replayed later.
            if ($attack !== null && $attack['zone'] === 'CombatChain'
                && ($attack['object']->Role ?? '') === 'ATTACK'
                && intval($attack['object']->ChainLink ?? 0) === intval($stack->Params['wtrHitChainLink'] ?? -1)) {
                FaBWTRTag($attack['object'], 'GO_AGAIN');
            }
        }
        return;
    }
    if (isset($stack->Params['wtrQuickenAttackUID'])) {
        // Each queued trigger remains independent of its source's later abilities.
        $token = FaBFindUID(intval($stack->SourceUniqueID));
        if ($token !== null && $token['zone'] === 'Arena' && $token['object']->CardID === 'quicken') {
            FaBMONDestroy(intval($token['object']->UniqueID));
        }
        $attack = FaBFindUID(intval($stack->Params['wtrQuickenAttackUID']));
        if ($attack !== null && $attack['zone'] === 'Stack') FaBWTRTag($attack['object'], 'GO_AGAIN');
        return;
    }
    if (!empty($stack->Params['wtrFlockTokenTrigger'])) { FaBWTRCreateArena($player, 'quicken'); return; }

    if (isset($stack->Params['wtrLifeTriggerTag'])) {
        $tag = (string)$stack->Params['wtrLifeTriggerTag'];
        $source = FaBFindUID(intval($stack->SourceUniqueID));
        if ($source !== null && $source['zone'] === 'Stack'
            && in_array($tag, ['GO_AGAIN', 'WTR_WOUNDED_BULL'], true)) {
            foreach (FaBOpponents($player) as $opponent) {
                if (FaBPENLifeMore($opponent, $player)) { FaBWTRTag($source['object'], $tag); break; }
            }
        }
        return;
    }
    // A triggered layer resolves independently of its source or current suppression.
    if (!empty($stack->Params['wtrDrawTrigger'])) { DoDrawCard($player, 1); return; }
    if(isset($stack->Params['arcSpec'])){FaBARCResolveAbility($player,$stack);return;}
    $id=$stack->CardID;$spec=FaBWTRAbilitySpec($id);
    if($spec===null)return;
    $source=FaBFindUID(intval($stack->SourceUniqueID));
    $ran=FaBRunSourceMacro('ResolveAbility',$player,$id,['mzID'=>$source['mzID']??'']);
    if(!$ran)switch($id){
        case 'scabskin_leathers': AddActionPoints($player,intval(GetActionPoints($player))+intdiv(FaBEVRRoll($player),2)); break;
        case 'barkbone_strapping': AddResources($player,intval(GetResources($player))+intdiv(FaBEVRRoll($player),2)); break;
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
            $roll=FaBEVRRoll($player);
            if($roll<=2){AddHealth($player,max(0,intval(GetHealth($player))-2));if(intval(GetHealth($player))===0)FaBEliminateSeat($player);}
            elseif($roll<=4)FaBCRUGainLife($player,2);
            else{AddResources($player,intval(GetResources($player))+2);AddActionPoints($player,intval(GetActionPoints($player))+2);FaBWTRAddEffect($player,'NEXT_ATTACK',2);}
            if($roll<=4&&FaBWTRMayGoAgain($player))AddActionPoints($player,intval(GetActionPoints($player))+1);
            break;
    }
    if($spec['goAgain']&&FaBWTRMayGoAgain($player)){AddActionPoints($player,intval(GetActionPoints($player))+1);FaBROSGo($player,intval($stack->SourceUniqueID));}
}
