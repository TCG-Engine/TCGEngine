<?php
function FaBMONAbilityRows(): array {return [
    'prism'=>[['INSTANT',2,false,false,true,0,'Create Spectral Shield']],
    'prism_sculptor_of_arc_light'=>[['INSTANT',2,false,false,true,0,'Create Spectral Shield']],
    'boltyn'=>[['REACTION',0,false,false,false,0,'Give an empowered attack go again']],
    'ser_boltyn_breaker_of_dawn'=>[['REACTION',0,false,false,false,0,'Give an empowered attack go again']],
    'chane'=>[['ACTION',0,false,true,true,0,'Create Soul Shackle']],
    'chane_bound_by_shadow'=>[['ACTION',0,false,true,true,0,'Create Soul Shackle']],
    'aether_ironweave'=>[['ACTION',0,true,true,false,0,'Gain two resources']],
    'blood_drop_brocade'=>[['INSTANT',0,true,false,false,0,'Gain one resource']],
    'dream_weavers'=>[['ACTION',0,true,true,false,0,'Remove next attack phantasm']],
    'ebon_fold'=>[['INSTANT',1,true,false,false,0,'Banish from hand']],
    'halo_of_illumination'=>[['INSTANT',1,true,false,false,0,'Put a card into soul']],
    'gallantry_gold'=>[['ACTION',1,true,true,false,0,'Empower weapons']],
    'time_skippers'=>[['ACTION',3,true,false,false,0,'Gain two action points']],
    'great_library_of_solana'=>[['ACTION',0,true,true,false,0,'Discard two yellow cards to destroy']],
    'guardian_of_the_shadowrealm_red'=>[['ACTION',2,false,false,false,0,'Return to hand']],
    'exude_confidence_red'=>[['INSTANT',3,false,false,false,0,'Gain two power']],
    'rally_the_rearguard_red'=>[['INSTANT',0,false,false,true,0,'Discard for three defense']],
    'rally_the_rearguard_yellow'=>[['INSTANT',0,false,false,true,0,'Discard for three defense']],
    'rally_the_rearguard_blue'=>[['INSTANT',0,false,false,true,0,'Discard for three defense']],
];}
function FaBMONSpecialAbilityZone(array $f): bool {
    $o=$f['object'];$b=FaBWTRBase($o->CardID);$s=FaBGetState();
    return ($b==='guardian_of_the_shadowrealm'&&$f['zone']==='Banish'&&empty($o->FaceDown))||
        ($f['zone']==='CombatChain'&&intval($o->ChainLink)===intval($s['chainLink'])&&(($b==='exude_confidence'&&$o->Role==='ATTACK')||($b==='rally_the_rearguard'&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))));
}
function FaBMONAbilityLegal(int $p,array $f): bool {
    $b=FaBWTRBase($f['object']->CardID);$s=FaBGetState();if(FaBMONLocked($p))return false;
    if(in_array($b,['prism','prism_sculptor_of_arc_light','boltyn','ser_boltyn_breaker_of_dawn'],true)&&FaBMONSoul($p)==='')return false;
    if(in_array($b,['boltyn','ser_boltyn_breaker_of_dawn'],true)){$a=FaBFindUID(intval($s['attackUID']));if(!$a||FaBAttackPower($s)<=intval(CardPower($a['object']->CardID)))return false;}
    if($b==='aether_ironweave'&&(!count(FaBARCPlayed($p,true))||!count(array_filter(FaBARCPlayed($p),fn($id)=>FaBHasType($id,'Attack')))))return false;
    if($b==='blood_drop_brocade'&&!FaBMONCount($p,'PHYSICAL'))return false;
    if(in_array($b,['ebon_fold','halo_of_illumination','rally_the_rearguard'],true)){
        $cost=$b==='rally_the_rearguard'?0:1;
        if(!array_filter(FaBChoiceRefs($p,'Hand'),fn($r)=>FaBAvailablePitch($p)-FaBMONPitchValue($p,FaBIdentityFromMZ($r)['object']->CardID)>=$cost))return false;
    }
    if($b==='great_library_of_solana'&&count(FaBChoiceRefs($p,'Hand',['pitch'=>2]))<2)return false;
    if(in_array($b,['exude_confidence','guardian_of_the_shadowrealm','rally_the_rearguard'],true)&&!FaBMONSpecialAbilityZone($f))return false;
    return true;
}
function FaBMONArenaAttackSpec(int $p,array $f): ?array {
    if($f['player']!==$p||$f['zone']!=='Arena'||HasNoAbilities($f['object']))return null;$o=$f['object'];
    if(FaBUPRFrozen($o)||FaBUPRLocked($p))return null;
    if(($mstSpec=FaBMSTCosmo($p,$o))!==null)return $mstSpec;
    if(FaBHasType($o,'Dragon')&&!FaBMONWeapon($p,'storm_of_sandikai'))return null;
    if(in_array('UPR_GHOST',(array)$o->TurnEffects,true))return ['cost'=>3,'power'=>FaBUPRHealth($o),'iris'=>false,'aura'=>false];
    if($o->CardID==='suraya_archangel_of_knowledge')return ['cost'=>2,'power'=>4,'iris'=>false,'aura'=>false];
    if(FaBHasType($o,'Angel'))return ['cost'=>2,'power'=>intval(CardPower($o->CardID)),'iris'=>false,'aura'=>false];
    if($o->CardID==='cintari_sellsword'){if(!FaBHVYCount($p,'WEAPON_ATTACKED'))return null;return ['cost'=>1,'power'=>intval(CardPower($o->CardID)),'iris'=>false,'aura'=>false];}
    if(FaBHasType($o,'Ally'))return ['cost'=>0,'power'=>intval(CardPower($o->CardID)),'iris'=>false,'aura'=>false];
    if(!FaBHasType($o,'Aura')||!FaBHasType($o,'Illusionist'))return null;
    if(FaBMONWeapon($p,'luminaris'))return ['cost'=>0,'power'=>1,'iris'=>false,'aura'=>true];
    if(FaBMONWeapon($p,'reality_refractor'))return ['cost'=>max(0,2-FaBMSTShieldDiscount($p,$o)),'power'=>5,'iris'=>false,'aura'=>true];
    if(FaBMONWeapon($p,'iris_of_reality'))return ['cost'=>max(0,3-FaBMSTShieldDiscount($p,$o)),'power'=>4,'iris'=>true,'aura'=>true];
    return null;
}
function FaBMONArenaCanAttack(int $p,array $f): bool {
    $spec=FaBMONArenaAttackSpec($p,$f);$s=FaBGetState();
    return $spec!==null&&FaBDTDRestrictions($p,$f['object'],true,true)&&$p===intval(GetTurnPlayer())&&FaBCRUWeaponReady($f['object'])&&intval(GetActionPoints($p))>0&&in_array($s['window'],['ACTION','RESOLUTION'],true)&&FaBAvailablePitch($p)>=intval($spec['cost']);
}
function FaBMONArenaAttack(int $p,array $f): bool {
    if(!FaBMONArenaCanAttack($p,$f))return false;$spec=FaBMONArenaAttackSpec($p,$f);$uid=intval($f['object']->UniqueID);
    $target=FaBClaimOrRequestAttackTarget($p,$uid,'ACTIVATE');if($target===null)return true;if($target===false)return false;
    $o=AddStack(CardID:$f['object']->CardID,Controller:$p,Kind:'ATTACK',SourceZone:'Arena',SourceUniqueID:$uid,Params:['attackTarget'=>$target]);
    $o->TurnEffects=(array)$f['object']->TurnEffects;
    FaBSetObjectCounter($o,'MON_ARENA_POWER',$spec['power']);FaBSetObjectCounter($o,'MON_ARENA_ATTACK',1);
    FaBSetObjectCounter($o,'MON_SOURCE_UID',$uid);
    if($spec['aura'])FaBSetObjectCounter($o,'MON_AURA',1);if($spec['iris'])FaBSetObjectCounter($o,'MON_IRIS',1);
    $s=FaBGetState();$s['pendingPayment']=['player'=>$p,'uid'=>intval($o->UniqueID),'weaponUID'=>$uid,'cost'=>$spec['cost'],'fromZone'=>'Arena','kind'=>'ATTACK','isWeaponAttack'=>$spec['aura'],'isArenaAttack'=>true,'returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep']];
    $s['window']='PITCH';FaBSetState($s);SetConsecutivePasses(0);return FaBTryCompletePayment();
}
