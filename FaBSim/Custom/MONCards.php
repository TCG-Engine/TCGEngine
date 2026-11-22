<?php
// Monarch's public-zone rules use seat IDs and persistent card UIDs throughout.
function FaBMONCount(int $p,string $key): int {return FaBARCEffect($p,'MON_'.$key);}
function FaBMONAdd(int $p,string $key,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'MON_'.$key,$n,$data);}
function FaBMONHero(int $p,string $base): bool {
    foreach(FaBChoiceRefs($p,'Hero') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(!HasNoAbilities($o)&&str_starts_with($o->CardID,$base))return true;}return false;
}
function FaBMONArena(int $p,string $base): array {
    return array_values(array_filter(FaBChoiceRefs($p,'Arena',['base'=>$base]),fn($r)=>!HasNoAbilities(FaBIdentityFromMZ($r)['object'])));
}
function FaBMONSoul(int $p): string {return implode('&',FaBChoiceRefs($p,'Soul'));}
function FaBMONBasePower(int $p,object $o): int {if($o->CardID==='blasmophets_boon_blue'&&!HasNoAbilities($o))return FaBIARHasBlasmophet($p)?6:0;$n=$o->CardID==='diabolic_offering_blue'?(FaBMONCount($p,'BANISHED_SIX')?6:0):($o->CardID==='mutated_mass_blue'?FaBMONMass($p):intval(CardPower($o->CardID)));return FaBSUPBase($p,$o,FaBPENBase($p,$o,$n))+FaBHVYPowerOutsideChain($p,$o);}
function FaBMONBloodDebt(int $p): int {
    $n=0;foreach(GetBanish($p) as $o)if(is_object($o)&&empty($o->removed)&&empty($o->FaceDown)&&FaBHasKeyword($o,'Blood Debt'))++$n;return $n;
}
function FaBMONCharge(int $p,string $chosen): int {
    $n=0;foreach(explode('&',$chosen) as $ref){$f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||$f['zone']!=='Hand')continue;
        $light=FaBHasType($f['object'],'Light');FaBMoveUID(intval($f['object']->UniqueID),'Soul',$p);FaBMONAdd($p,'CHARGED');FaBBoltynCharged($p,$f['object']->CardID);if($f['object']->CardID==='banneret_of_swordsmanship_yellow')FaBWTRCreateArena($p,'flurry');FaBDTDCharged($p,$f['object']->CardID);
        $pendingUID=intval(FaBGetState()['pendingPayment']['uid']??0);if($pendingUID)FaBARCSetCard($pendingUID,'dtdCharged',true);if($light)++$n;
    }return $n;
}
function FaBMONBanishRandom(int $p,int $n): bool {
    if (FaBDreadDeferRandomCost($p, $n)) return false;
    return FaBMONBanishRandomImmediate($p, $n);
}
function FaBMONBanishRandomImmediate(int $p,int $n): bool {
    $refs=FaBChoiceRefs($p,'Graveyard');$six=false;
    for($i=0;$i<$n&&count($refs);++$i){$index=EngineRandomInt(0,count($refs)-1);$f=FaBIdentityFromMZ($refs[$index]);array_splice($refs,$index,1);
        if(!$f)continue;$six=$six||FaBMONBasePower($p,$f['object'])>=6;FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);
    }return $six;
}
function FaBMONAfterMove(int $p,object $o,string $from,string $to): void {
    FaBAdvanceCapturedSourceEpochs($o,$from,$to,['monPulpingSourceEpoch','monMemorialGraveEpoch']);
    FaBDTDAfterMove($p,$o,$from,$to);FaBEVOAfterMove($p,$o,$from,$to);FaBHVYAfterMove($p,$o,$from,$to);FaBMSTAfterMove($p,$o,$from,$to);FaBROSAfterMove($p,$o,$from,$to);FaBHNTAfterMove($p,$o,$from,$to);
    if($from==='Arena'&&$to!=='Arena'){
        $s=FaBGetState();$attack=FaBFindUID(intval($s['attackUID']??0));
        if(!empty($s['combatOpen'])&&$attack&&!FaBMONAttackSourceExists($attack['object'])){
            FaBCleanupResolvedLink($s);FaBCloseCombatChain();
        }
    }
    if($to==='Banish'){FaBMONAdd($p,'BANISHED');if(empty($o->FaceDown)&&FaBMONBasePower($p,$o)>=6){FaBMONAdd($p,'BANISHED_SIX');
        foreach(FaBCRUEquipment($p,'hooves_of_the_shadowbeast') as $ref)FaBRunSourceMacro('ResolveAbility',$p,'hooves_of_the_shadowbeast',['mzID'=>$ref]);}}
    if($to==='Soul'){FaBMONAdd($p,'SOUL_ADDED');FaBBoltynSoulAdded($p,$o);}
    if($to==='Banish'&&FaBMONHero($p,'blasmophet_levia_consumed')){$o->FaceDown=1;$o->PlayableFromBanish=0;}
}
function FaBMONAttackSourceExists(object $attack): bool {
    $uid=intval(FaBObjectCounters($attack)['MON_SOURCE_UID']??0);
    if(!$uid)return true;
    $source=FaBFindUID($uid);return $source!==null&&($source['zone']==='Arena'||($source['object']->CardID==='nitro_mechanoid'&&$source['zone']==='Equipment')||($source['object']->CardID==='teklovossen_the_mechropotent'&&$source['zone']==='Hero'));
}
function FaBMONBanishPlayable(int $p,object $o): bool {
    if(!empty($o->FaceDown)||HasNoAbilities($o))return false;$b=FaBWTRBase($o->CardID);
    if(in_array($b,['bounding_demigon','unhallowed_rites'],true))return count(FaBARCPlayed($p,true))>0;
    if($b==='deep_rooted_evil')return FaBMONCount($p,'BANISHED_SIX')>0;
    if($b==='eclipse')return FaBMONCount($p,'DEBT_PLAYED')>=6;
    return in_array($b,['embalm','cull','ghostly_visit','howl_from_beyond','piercing_shadow_vise','rift_bind','rifted_torment','rip_through_reality','seeds_of_agony','seeping_shadows','tome_of_torment','void_wraith','mutated_mass','shadow_of_ursur','invert_existence'],true);
}
function FaBMONCanPlay(int $p,array $f): bool {
    $b=FaBWTRBase($f['object']->CardID);$s=FaBGetState();
    if($f['zone']==='Banish'&&FaBMONCount($p,'NO_BANISH_PLAY')&&$p===intval(GetTurnPlayer()))return false;
    if(FaBMONLocked($p)&& (FaBHasType($f['object'],'Instant')||FaBHasType($f['object'],'Defense Reaction')))return false;
    if(in_array($b,['boneyard_marauder','convulsions_from_the_bellows_of_hell','dread_screamer','endless_maw','hungering_slaughterbeast','unworldly_bellow','writhing_beast_hulk'],true)&&count(FaBChoiceRefs($p,'Graveyard'))<3)return false;
    if($b==='soul_harvest'&&count(FaBChoiceRefs($p,'Graveyard'))<6)return false;
    if($b==='celestial_cataclysm'&&count(FaBChoiceRefs($p,'Soul'))<3)return false;
    if($b==='beacon_of_victory'&&!FaBChoiceRefs($p,'Soul'))return false;
    if($b==='blinding_beam'&&FaBMONCombatTargets()==='')return false;
    if($b==='memorial_ground'&&!FaBChoiceRefs($p,'Graveyard',['attackAction'=>true,'maxCost'=>3-intval(CardPitch($f['object']->CardID))]))return false;
    if($b==='spew_shadow'&&!FaBChoiceRefs($p,'Banish',['attackAction'=>true,'maxCost'=>3-intval(CardPitch($f['object']->CardID))]))return false;
    if($b==='graveling_growl'&&!FaBMONCount($p,'BANISHED_SIX'))return false;
    if($b==='doomsday'&&FaBMONBloodDebt($p)<6)return false;
    if($b==='eclipse'&&FaBMONCount($p,'DEBT_PLAYED')<6)return false;
    return true;
}
function FaBMONLessLife(int $p,string $talent=''): bool {
    foreach(FaBOpponents($p) as $other)if(FaBPENLifeMore($other,$p)&&($talent===''||FaBHasType(GetHero($other)[0],$talent)))return true;return false;
}
/** Invigorating's played event earns a response layer, not an inline close tag. */
function FaBMONQueueInvigoratingPlay(int $p,object $o): void {
    $uid=intval($o->UniqueID??0);
    if(!FaBSeatIsLive($p)||$uid<1||FaBWTRBase((string)($o->CardID??''))!=='invigorating_light'
        ||($o->Kind??'')!=='ATTACK'||intval($o->Controller??0)!==$p||HasNoAbilities($o))return;
    $source=FaBFindUID($uid);
    if($source===null||$source['zone']!=='Stack'||$source['object']!==$o)return;
    $params=(array)($o->Params??[]);
    $pending=FaBFindUID(intval($params['monInvigoratingPlayTrigger']??0));
    if($pending!==null&&$pending['zone']==='Stack'
        &&($pending['object']->Params['rosEvent']??'')==='monInvigoratingLightPlay'
        &&intval($pending['object']->SourceUniqueID??0)===$uid
        &&intval($pending['object']->Controller??0)===$p
        &&(string)$pending['object']->CardID===(string)$o->CardID)return;
    // The printed condition is captured at actual play, not checked again at close.
    FaBROSQueue($p,(string)$o->CardID,$uid,
        ['rosEvent'=>'monInvigoratingLightPlay','monInvigoratingSoulEmpty'=>!FaBChoiceRefs($p,'Soul')]);
    $trigger=FaBStackTop();
    if($trigger===null||($trigger->Kind??'')!=='ABILITY'
        ||($trigger->Params['rosEvent']??'')!=='monInvigoratingLightPlay'
        ||intval($trigger->Controller??0)!==$p||intval($trigger->SourceUniqueID??0)!==$uid
        ||(string)$trigger->CardID!==(string)$o->CardID||intval($trigger->UniqueID??0)<1)return;
    // Bind to the real earned layer; no new nonce or global play counter.
    $params['monInvigoratingPlayTrigger']=intval($trigger->UniqueID);$o->Params=$params;
}

function FaBMONResolveInvigoratingPlay(int $p,object $trigger): void {
    $uid=intval($trigger->SourceUniqueID??0);$layerUID=intval($trigger->UniqueID??0);
    $eligible=$trigger->Params['monInvigoratingSoulEmpty']??null;
    if(!FaBSeatIsLive($p)||$uid<1||$layerUID<1||($trigger->Kind??'')!=='ABILITY'
        ||($trigger->Params['rosEvent']??'')!=='monInvigoratingLightPlay'
        ||intval($trigger->Controller??0)!==$p
        ||FaBWTRBase((string)($trigger->CardID??''))!=='invigorating_light'||!is_bool($eligible))return;
    $source=FaBFindUID($uid);
    if($source===null||$source['zone']!=='Stack')return;
    $o=$source['object'];$params=(array)($o->Params??[]);
    if(($o->Kind??'')!=='ATTACK'||intval($o->Controller??0)!==$p
        ||(string)$o->CardID!==(string)$trigger->CardID
        ||intval($params['monInvigoratingPlayTrigger']??0)!==$layerUID)return;
    // Consume before the tagging callback; stale/repeated resolutions cannot reapply.
    unset($params['monInvigoratingPlayTrigger']);$o->Params=$params;
    if($eligible&&!in_array('MON_CLOSE_SOUL',(array)($o->TurnEffects??[]),true))FaBWTRTag($o,'MON_CLOSE_SOUL');
}

function FaBMONQueueRiftBindPlay(int $p,object $o): void {
    $uid=intval($o->UniqueID??0);$id=(string)($o->CardID??'');
    if(!FaBSeatIsLive($p)||$uid<1||!in_array($id,['rift_bind_red','rift_bind_yellow','rift_bind_blue'],true)
        ||($o->Kind??'')!=='ATTACK'||($o->SourceZone??'')!=='Banish'||intval($o->Controller??0)!==$p||HasNoAbilities($o))return;
    $source=FaBFindUID($uid);
    if(!$source||$source['zone']!=='Stack'||$source['object']!==$o)return;
    $params=(array)($o->Params??[]);if(array_key_exists('monRiftBindPlayTrigger',$params))return;
    FaBROSQueue($p,$id,$uid,['rosEvent'=>'monRiftBindBanishPlay']);
    $trigger=FaBStackTop();
    if(!$trigger||($trigger->Kind??'')!=='ABILITY'||($trigger->Params['rosEvent']??'')!=='monRiftBindBanishPlay'
        ||intval($trigger->Controller??0)!==$p||intval($trigger->SourceUniqueID??0)!==$uid
        ||(string)$trigger->CardID!==$id||intval($trigger->UniqueID??0)<1)return;
    // Reuse the installed per-play binding pattern; no global play counter or new allocation.
    $params['monRiftBindPlayTrigger']=intval($trigger->UniqueID);$o->Params=$params;
}
function FaBMONResolveRiftBindPlay(int $p,object $trigger): void {
    $uid=intval($trigger->SourceUniqueID??0);$layer=intval($trigger->UniqueID??0);
    if(!FaBSeatIsLive($p)||$uid<1||$layer<1||($trigger->Kind??'')!=='ABILITY'
        ||intval($trigger->Controller??0)!==$p||($trigger->Params['rosTrigger']??false)!==true
        ||($trigger->Params['rosEvent']??'')!=='monRiftBindBanishPlay'
        ||!in_array((string)($trigger->CardID??''),['rift_bind_red','rift_bind_yellow','rift_bind_blue'],true))return;
    $source=FaBFindUID($uid);if(!$source||$source['zone']!=='Stack')return;
    $o=$source['object'];$params=(array)($o->Params??[]);
    if(($o->Kind??'')!=='ATTACK'||!empty($o->removed)||intval($params['monRiftBindPlayTrigger']??0)!==$layer)return;
    // Consume once, retaining the key so a duplicate play callback cannot recapture this layer.
    $params['monRiftBindPlayTrigger']=0;$o->Params=$params;
    $count=count(FaBARCPlayed($p,true));$previous=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$p;
    try{FaBCRUSelfTag($o,'WTR_POWER:'.$count);}finally{$GLOBALS['fabEffectController']=$previous;}
}
function FaBMONQueuePoundPlay(int $p,object $o): void {
    $uid=intval($o->UniqueID??0);$id=(string)($o->CardID??'');
    if(!FaBSeatIsLive($p)||$uid<1||!in_array($id,['pound_for_pound_red','pound_for_pound_yellow','pound_for_pound_blue'],true)
        ||($o->Kind??'')!=='ATTACK'||intval($o->Controller??0)!==$p||HasNoAbilities($o))return;
    $source=FaBFindUID($uid);if(!$source||$source['zone']!=='Stack'||$source['object']!==$o)return;
    $params=(array)($o->Params??[]);if(array_key_exists('monPoundPlayTrigger',$params))return;
    // The WHEN is played; the life condition belongs to the effect generated on resolution.
    FaBROSQueue($p,$id,$uid,['rosEvent'=>'monPoundForPoundPlay']);
    $trigger=FaBStackTop();
    if(!$trigger||($trigger->Kind??'')!=='ABILITY'||($trigger->Params['rosEvent']??'')!=='monPoundForPoundPlay'
        ||intval($trigger->Controller??0)!==$p||intval($trigger->SourceUniqueID??0)!==$uid
        ||(string)$trigger->CardID!==$id||intval($trigger->UniqueID??0)<1)return;
    $params['monPoundPlayTrigger']=intval($trigger->UniqueID);$o->Params=$params;
}
function FaBMONResolvePoundPlay(int $p,object $trigger): void {
    $uid=intval($trigger->SourceUniqueID??0);$layer=intval($trigger->UniqueID??0);
    if(!FaBSeatIsLive($p)||$uid<1||$layer<1||($trigger->Kind??'')!=='ABILITY'
        ||intval($trigger->Controller??0)!==$p||($trigger->Params['rosTrigger']??false)!==true
        ||($trigger->Params['rosEvent']??'')!=='monPoundForPoundPlay'
        ||!in_array((string)($trigger->CardID??''),['pound_for_pound_red','pound_for_pound_yellow','pound_for_pound_blue'],true))return;
    $source=FaBFindUID($uid);if(!$source||$source['zone']!=='Stack')return;
    $o=$source['object'];$params=(array)($o->Params??[]);
    if(($o->Kind??'')!=='ATTACK'||!empty($o->removed)||intval($params['monPoundPlayTrigger']??0)!==$layer)return;
    $params['monPoundPlayTrigger']=0;$o->Params=$params;
    if(!FaBMONLessLife($p))return;
    $previous=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$p;
    try{FaBWTRTag($o,'DOMINATE');}finally{$GLOBALS['fabEffectController']=$previous;}
}
function FaBMONCardPlayed(int $p,object $o,string $from): void {
    $b=FaBWTRBase($o->CardID);$aa=FaBWTRIsAttackAction($o);$left=[];
    if($from==='Banish'){FaBMONAdd($p,'BANISH_PLAYED');FaBARCSetCard(intval($o->UniqueID),'monFromBanish',FaBIAROwnBanish($p,intval($o->UniqueID)));}
    if(FaBHasKeyword($o,'Blood Debt'))FaBMONAdd($p,'DEBT_PLAYED');
    foreach(FaBWTREffects($p) as $e){$t=$e['type']??'';$applies=false;$amount=intval($e['amount']??0);
        if($t==='MON_NEXT_SHADOW_BRUTE')$applies=$aa&&(FaBHasType($o,'Shadow')||FaBHasType($o,'Brute'));
        if($t==='MON_NEXT_SMALL')$applies=$aa&&intval(CardCost($o->CardID))<=intval($e['maxCost']??2);
        if($t==='MON_MINNOW')$applies=$aa&&intval(CardPower($o->CardID))<=3;
        if($t==='MON_PHANTASMIFY')$applies=$aa;
        if($t==='MON_TEAR'){$applies=$aa&&FaBHasType($o,'Brute');$amount=intval(CardPower($o->CardID));}
        if($t==='MON_DREAM')$applies=$aa&&FaBHasType($o,'Illusionist');
        if($t==='MON_CHANE')$applies=FaBHasType($o,'Action')&&(FaBHasType($o,'Shadow')||FaBHasType($o,'Runeblade'));
        if(!$applies){$left[]=$e;continue;}
        if($amount)FaBWTRTag($o,'WTR_POWER:'.$amount);
        if(!empty($e['goAgain'])||$t==='MON_CHANE')FaBWTRTag($o,'GO_AGAIN');
        if(!empty($e['dominate']))FaBWTRTag($o,'DOMINATE');
        if(!empty($e['seeds']))FaBWTRTag($o,'MON_SEEDS');
        if($t==='MON_PHANTASMIFY'){FaBWTRTag($o,'MON_ILLUSIONIST');FaBWTRTag($o,'MON_PHANTASM');}
        if($t==='MON_DREAM')FaBWTRTag($o,'MON_NO_PHANTASM');
    }FaBWTRSetEffects($p,$left);
    if($b==='adrenaline_rush'&&FaBMONLessLife($p))FaBCRUSelfTag($o,'WTR_POWER:3');
    if($b==='pound_for_pound')FaBMONQueuePoundPlay($p,$o);
    if($b==='invigorating_light')FaBMONQueueInvigoratingPlay($p,$o);
    if($b==='frontline_scout'&&$from==='Arsenal')FaBWTRTag($o,'GO_AGAIN');
    if($from==='Banish'){
        if($b==='bounding_demigon')FaBCRUSelfTag($o,'WTR_POWER:1');
        if($b==='rift_bind')FaBMONQueueRiftBindPlay($p,$o);
        $type=$aa?'AA':'NAA';if(FaBHasType($o,'Action')&&count(array_filter(FaBARCPlayed($p,!$aa),fn($id)=>$aa?FaBHasType($id,'Attack'):true))===1)
            foreach(FaBMONArena($p,'dimenxxional_crossroads') as $ref)FaBRunSourceMacro('ResolveAbility',$p,'dimenxxional_crossroads_yellow',['mzID'=>$ref]);
    }
}
function FaBMONWeapon(int $p,string $id): bool {return count(FaBChoiceRefs($p,'Weapons',['base'=>$id]))>0;}
function FaBMONPower(int $p,object $o): int {
    $n=0;$aa=FaBWTRIsAttackAction($o);$s=FaBGetState();$weapon=FaBWTRIsWeapon($o);
    if($aa)foreach(FaBOpponents($p) as $other)$n-=count(FaBMONArena($other,'parable_of_humility'));
    if(FaBMONCount($p,'CHARGED')&&FaBMONHero($p,'boltyn')||FaBMONCount($p,'CHARGED')&&FaBMONHero($p,'ser_boltyn')){
        foreach(FaBChoiceRefs(intval($s['defender']),'CombatChain') as $r){$d=FaBIdentityFromMZ($r)['object'];if(intval($d->ChainLink)===intval($s['chainLink'])&&in_array($d->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBWTRIsAttackAction($d)){++$n;break;}}
    }
    $n+=FaBMONCount($p,'VANGUARD');
    if(FaBHasType(GetHero(intval($s['defender']))[0]??'','Shadow'))$n+=FaBMONCount($p,'RAY');
    if($weapon)$n+=FaBMONCount($p,'GALLANTRY')+FaBMONCount($p,'LUMINA');
    if(in_array('MON_SPEW',(array)$o->TurnEffects,true)&&FaBHasType(GetHero(intval($s['defender']))[0]??'','Light'))$n+=2;

    if($weapon&&FaBHasType($o,'Axe'))$n+=FaBMONCount($p,'SPILL');
    return $n;
}
function FaBMONOwnPower(int $p,object $o): int {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();$n=0;
    if($b==='valiant_thrust'&&FaBMONCount($p,'CHARGED'))$n+=3;
    if($b==='raydn_duskbane'&&FaBMONCount($p,'CHARGED'))$n+=3;
    if($b==='galaxxi_black'&&FaBMONCount($p,'BANISH_PLAYED'))$n+=2;
    if($b==='piercing_shadow_vise'&&FaBCRUArcane($p,true))$n+=2;
    if($b==='tremor_of_iarathael'&&FaBMONCount($p,'BANISHED'))$n+=2;
    if($b==='yinti_yanti'&&FaBChoiceRefs($p,'Arena',['type'=>'Aura']))$n+=1;
    if($b==='stony_woottonhog'&&FaBWTRNonEquipmentBlockCount($s)<2)$n+=1;
    if($b==='surging_militia')$n+=FaBWTRNonEquipmentBlockCount($s);
    return $n;
}
function FaBMONGreaterBlock(array $s): bool {
    $power=FaBAttackPower($s);foreach(FaBChoiceRefs(intval($s['defender']),'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBMONDefendingPower(intval($s['defender']),$o)>=$power)return true;}return false;
}
function FaBMONLocked(int $p): bool {
    $s=FaBGetState();if(!FaBIsDefendingHero($p,$s))return false;$a=FaBFindUID(intval($s['attackUID']));
    return $a!==null&&$a['object']->CardID==='exude_confidence_red'&&!HasNoAbilities($a['object'])&&!FaBMONGreaterBlock($s);
}
function FaBMONGAgain(int $p,object $o,array $s): bool {
    $b=FaBWTRBase($o->CardID);
    if(in_array($b,['battlefield_blitz','take_flight'],true)&&FaBMONCount($p,'CHARGED'))return true;
    if($b==='rip_through_reality'&&FaBCRUArcane($p,true))return true;
    if($b==='out_muscle'&&!FaBMONGreaterBlock($s))return true;
    if($b==='pulping'&&FaBWTRNonEquipmentBlockCount($s)<2)return true;
    if($b==='zealous_belting')foreach(GetPitch($p) as $c)if(is_object($c)&&empty($c->removed)&&intval(CardPower($c->CardID))>intval(CardPower($o->CardID)))return true;
    if(in_array($b,['ursur_the_soul_reaper','soul_reaping'],true)&&count(FaBChoiceRefs(intval($s['defender']),'Soul'))>0)return true;
    if(FaBHasType($o,'Illusionist')){
        if(FaBWTRIsAttackAction($o)&&FaBMONArena($p,'ode_to_wrath'))return true;
        if(FaBMONWeapon($p,'luminaris'))foreach(GetPitch($p) as $c)if(is_object($c)&&empty($c->removed)&&intval(CardPitch($c->CardID))===2)return true;
        if(!empty(FaBObjectCounters($o)['MON_IRIS']))return true;
    }return false;
}
function FaBMONDefendingPower(int $p,object $o): int {
    $n=$o->CardID==='fractal_replication_red'?FaBEVRFractalValue($o,'POWER'):intval(CardPower($o->CardID));if($o->CardID==='mutated_mass_blue')$n=FaBMONMass($p);
    $n=FaBPENBase($p,$o,$n)+FaBPENCombatPower($p,$o);
    foreach((array)($o->TurnEffects??[]) as $tag)if(str_starts_with($tag,'WTR_POWER:'))$n+=intval(substr($tag,10));
    if(FaBWTRIsAttackAction($o)){
        foreach(FaBOpponents($p) as $other)$n-=count(FaBMONArena($other,'parable_of_humility'));
        $a=FaBFindUID(intval(FaBGetState()['attackUID']));if($a){if(FaBWTRBase($a['object']->CardID)==='herald_of_triumph')--$n;foreach((array)(FaBObjectCounters($a['object'])['EVR_COPIED']??[]) as $id)if(FaBWTRBase($id)==='herald_of_triumph')--$n;}
    }if(FaBWTRIsAttackAction($o))foreach(FaBOpponents($p) as $seat)$n-=FaBDTDCount($seat,'TRIUMPH');
    return FaBWTRBase($o->CardID)==='numbskull'?intval(CardPower($o->CardID)):max(0,$n);
}
function FaBMONMass(int $p): int {$costs=[];foreach(FaBChoiceRefs($p,'Pitch') as $r)$costs[]=(string)CardCost(FaBIdentityFromMZ($r)['object']->CardID);return count(array_unique($costs))*2;}
function FaBMONDefense(int $p,object $o): int {
    $b=FaBWTRBase($o->CardID);$n=0;
    if($b==='yinti_yanti'&&FaBChoiceRefs($p,'Arena',['type'=>'Aura']))++$n;
    if($b==='impenetrable_belief')foreach(FaBOpponents($p) as $other)if(FaBMONCount($other,'BANISHED')>=3){$n+=2;break;}
    if($b==='mutated_mass')$n+=FaBMONMass($p);
    return $n;
}
function FaBMONStart(int $p): void {
    FaBBoltynStart($p);
    foreach(FaBChoiceRefs($p,'Arena') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(FaBHasType($o,'Ally'))$o->Damage=0;
        if(HasNoAbilities($o))continue;
        if($o->CardID==='soul_shackle'){$top=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($top);if($f)FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);}
        if($o->CardID==='genesis_yellow')FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$ref]);
    }
    if(intval(GetHealth($p))<=13)foreach(FaBCRUEquipment($p,'carrion_husk') as $r)FaBMoveUID(intval(FaBIdentityFromMZ($r)['object']->UniqueID),'Banish',$p);
}
function FaBMONEnd(int $p, ?int $bloodDebtAtEnd = null): void {
    FaBBoltynEnd();
    if(!FaBDTDBloodDebt($p))FaBARCLoseLife($p,$bloodDebtAtEnd??FaBMONBloodDebt($p),$p);
    foreach(FaBCRUEquipment($p,'valiant_dynamo') as $ref)if(FaBCRUCount($p,'WEAPONS')>=2)FaBRunSourceMacro('ResolveAbility',$p,'valiant_dynamo',['mzID'=>$ref]);
    foreach(FaBLiveSeats() as $owner)if(FaBMONArena($owner,'great_library_of_solana'))foreach(FaBLiveSeats() as $seat){
        $yellow=0;foreach(FaBChoiceRefs($seat,'Pitch') as $r)if(intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===2)++$yellow;
        if($yellow>=2)FaBWTRAddEffect($seat,'INTELLECT',count(FaBMONArena($owner,'great_library_of_solana')));
    }
    foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'Arena',['type'=>'Ally']) as $r)FaBIdentityFromMZ($r)['object']->Damage=0;
    foreach(explode('&',FaBMONWeaponChoices($p)) as $r){$f=FaBIdentityFromMZ($r);if(!$f)continue;$o=$f['object'];$g=FaBMONCount($p,'GLISTEN');if($g){FaBSetObjectCounter($o,'POWER',0);FaBSetObjectCounter($o,'MON_GLISTEN',0);}}
}
function FaBMONDestroy(int $uid): void {
    if(FaBIARBeforeDeath($uid))return;
    $penSource=FaBFindUID($uid);if($penSource&&FaBPENProtectFealty($penSource['object'],intval($GLOBALS['fabEffectController']??0)))return;
    FaBELEDestroyHook($uid);
    $f=FaBFindUID($uid);if(!$f)return;$p=$f['player'];$o=clone $f['object'];
    // Snapshot eligible triggers before the source leaves (Retribution sees itself).
    $triggers=(FaBHasType($o,'Aura')||FaBWTRIsAttackAction($o))?FaBMONArena($p,'merciful_retribution'):[];
    $light=FaBHasType($o,'Light')&&!FaBHasType($o,'Token');$aa=FaBWTRIsAttackAction($o)&&FaBHasType($o,'Illusionist');
    if($f['zone']==='Arena')FaBIARBoundTriggers($uid,true);
    FaBMoveUID($uid,'Graveyard',intval($o->Owner??$p));
    if(!empty(FaBObjectCounters($o)['EVO_HERO_UID']))FaBEliminateSeat($p);FaBEVODestroyed($p,$o,$f['zone']);FaBHVYDestroyed($p,$o);FaBDTDDestroyed($p,$o);FaBEVRDestroyed($p,$o);FaBUPRDestroyed($p,$o);FaBDYNDestroyed($p,$o);FaBROSDestroyed($p,$o,$f['zone']);FaBPENDestroyed($p,$o);FaBOMNDestroyed($p,$o);FaBIARDestroyed($p,$o);if($f['zone']==='Arena')FaBIARZombieDied($p,$o);
    foreach($triggers as $r){$a=FaBIdentityFromMZ($r);$triggerID=$a?$a['object']->CardID:'merciful_retribution_yellow';
        FaBRunSourceMacro('ResolveAbility',$p,$triggerID,['mzID'=>$r,'monDestroyedUID'=>$uid,'monLight'=>$light]);}

    if($aa)foreach(FaBCRUEquipment($p,'phantasmal_footsteps') as $r)FaBRunSourceMacro('ResolveAbility',$p,'phantasmal_footsteps',['mzID'=>$r]);
}
function FaBMONPhantasm(array $s,bool $resolve=true): bool {
    $f=FaBFindUID(intval($s['attackUID']));if(!$f||$f['zone']!=='CombatChain')return false;$o=$f['object'];
    if(HasNoAbilities($o)||in_array('MON_NO_PHANTASM',(array)$o->TurnEffects,true)||(!FaBHasKeyword($o,'Phantasm')&&!in_array('MON_PHANTASM',(array)$o->TurnEffects,true)))return false;
    foreach(FaBChoiceRefs(intval($s['defender']),'CombatChain') as $r){$b=FaBIdentityFromMZ($r)['object'];
        if(intval($b->ChainLink)!==intval($s['chainLink'])||!in_array($b->Role,['DEFENSE','DEFENSE_REACTION'],true)||!FaBWTRIsAttackAction($b)||FaBHasType($b,'Illusionist')||FaBMONDefendingPower(intval($s['defender']),$b)<6)continue;
        if(!$resolve){AddStack(CardID:$o->CardID,Controller:intval($s['attacker']),Kind:'ABILITY',SourceZone:'CombatChain',SourceUniqueID:intval($o->UniqueID),Params:['uprPhantasm'=>intval($o->UniqueID)]);return false;}
        FaBUPRPhantasm(intval($s['attacker']),$o);FaBMONDestroy(intval($o->UniqueID));FaBCleanupResolvedLink($s);FaBCloseCombatChain();return true;
    }return false;
}
function FaBMONSpectra(array $target): bool {
    $f=FaBFindUID(intval($target['uid']??0));if(!$f||!FaBHasKeyword($f['object'],'Spectra'))return false;
    FaBMONDestroy(intval($f['object']->UniqueID));FaBCloseCombatChain();return true;
}
function FaBMONPrevent(int $p,int $amount): int {
    foreach(FaBMONArena($p,'spectral_shield') as $r){if($amount<=0)break;$o=FaBIdentityFromMZ($r)['object'];FaBMONDestroy(intval($o->UniqueID));--$amount;}return $amount;
}
function FaBMONDamage(int $source,int $p,int $amount,string $type,string $sourceMZ): void {
    if($amount<=0)return;
    $a=FaBIdentityFromMZ($sourceMZ);if($a&&$a['object']->CardID==='dread_scythe')FaBWTRAddEffect($p,'MON_NO_HEAL',1,['expiresAfterTurnOf'=>$p],true);
    if($type==='PHYSICAL'){FaBMONAdd($source,'PHYSICAL');FaBMONAdd($p,'PHYSICAL');}
    if($source!==$p&&in_array($p,FaBOpponents($source),true))FaBARCLoseLife($p,count(FaBMONArena($source,'ode_to_wrath')),$source);
}
function FaBMONLifeLost(int $p,int $amount): void {
    if($amount>0)FaBDTDAdd($p,'LOST',$amount);
    if($amount>0&&$p===intval(GetTurnPlayer()))foreach(FaBMONArena($p,'dimenxxional_crossroads') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
}
function FaBMONCloseMove(object $o,int $p): bool {
    if(in_array('MON_CLOSE_SOUL',(array)$o->TurnEffects,true)||$o->CardID==='soul_shield_yellow'){FaBMoveUID(intval($o->UniqueID),'Soul',$p);return true;}
    if($o->CardID==='carrion_husk'&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)){FaBMoveUID(intval($o->UniqueID),'Banish',$p);return true;}
    if((in_array(($o->FromZone??''),['Arena','Hero'],true)||$o->CardID==='nitro_mechanoid')&&($o->Role??'')==='ATTACK'){$o->removed=true;return true;}
    return false;
}
function FaBMONQueuePulpingAttack(int $p,object $o): void {
    $s=FaBGetState();$uid=intval($o->UniqueID??0);$link=intval($o->ChainLink??0);$turn=intval(GetTurnNumber());
    if(!FaBSeatIsLive($p)||$uid<1||$link<1||$turn<1
        ||!in_array((string)($o->CardID??''),['pulping_red','pulping_yellow','pulping_blue'],true)
        ||($o->Role??'')!=='ATTACK'||intval($o->Controller??0)!==$p||HasNoAbilities($o)
        ||empty($s['combatOpen'])||intval($s['attacker']??0)!==$p||intval($s['attackUID']??0)!==$uid
        ||intval($s['chainLink']??0)!==$link)return;
    $source=FaBFindUID($uid);if(!$source||$source['zone']!=='CombatChain'||$source['object']!==$o)return;
    $epoch=FaBArmCapturedSourceEpoch($uid,'monPulpingSourceEpoch');$context=$s['monPulpingAttack']??null;
    if(is_array($context)&&intval($context['uid']??0)===$uid&&intval($context['link']??0)===$link
        &&intval($context['turn']??0)===$turn&&intval($context['epoch']??0)===$epoch)return;
    FaBROSQueue($p,(string)$o->CardID,$uid,['rosEvent'=>'monPulpingAttack','monPulpingLink'=>$link,
        'monPulpingTurn'=>$turn,'monPulpingEpoch'=>$epoch]);
    $trigger=FaBStackTop();
    if(!$trigger||($trigger->Kind??'')!=='ABILITY'||($trigger->Params['rosEvent']??'')!=='monPulpingAttack'
        ||intval($trigger->Controller??0)!==$p||intval($trigger->SourceUniqueID??0)!==$uid
        ||(string)$trigger->CardID!==(string)$o->CardID||intval($trigger->UniqueID??0)<1)return;
    // CombatChain has no serialized Params: use one reset-scoped context and tracked entry epoch.
    $s=FaBGetState();$s['monPulpingAttack']=['uid'=>$uid,'layer'=>intval($trigger->UniqueID),
        'link'=>$link,'turn'=>$turn,'epoch'=>$epoch];FaBSetState($s);
}
function FaBMONPulpingSource(object $trigger): ?array {
    $uid=intval($trigger->SourceUniqueID??0);$source=FaBFindUID($uid);$s=FaBGetState();$context=$s['monPulpingAttack']??null;
    if(!$source||$source['zone']!=='CombatChain'||!empty($source['object']->removed)
        ||($source['object']->Role??'')!=='ATTACK'||!is_array($context))return null;
    return intval($context['uid']??0)===$uid&&intval($context['layer']??0)===intval($trigger->UniqueID??0)
        &&intval($context['link']??0)===intval($trigger->Params['monPulpingLink']??0)
        &&intval($context['turn']??0)===intval($trigger->Params['monPulpingTurn']??0)
        &&intval($context['epoch']??0)===intval($trigger->Params['monPulpingEpoch']??0)
        &&intval(FaBARCCard($uid,'monPulpingSourceEpoch',0))===intval($trigger->Params['monPulpingEpoch']??0)
        &&intval($source['object']->ChainLink??0)===intval($trigger->Params['monPulpingLink']??0)?$source:null;
}
function FaBMONResolvePulpingAttack(int $p,object $trigger): void {
    $params=is_array($trigger->Params??null)?$trigger->Params:[];
    if(!FaBSeatIsLive($p)||($trigger->Kind??'')!=='ABILITY'||intval($trigger->Controller??0)!==$p
        ||intval($trigger->UniqueID??0)<1||intval($trigger->SourceUniqueID??0)<1
        ||!in_array((string)($trigger->CardID??''),['pulping_red','pulping_yellow','pulping_blue'],true)
        ||($params['rosTrigger']??false)!==true||($params['rosEvent']??'')!=='monPulpingAttack'
        ||!is_int($params['monPulpingLink']??null)||$params['monPulpingLink']<1
        ||!is_int($params['monPulpingTurn']??null)||$params['monPulpingTurn']<1
        ||!is_int($params['monPulpingEpoch']??null)||$params['monPulpingEpoch']<1
        ||!empty($params['monPulpingResolved']))return;
    $params['monPulpingResolved']=true;$trigger->Params=$params;
    $source=FaBMONPulpingSource($trigger);$variables=json_decode((string)GetDecisionQueueVariables(),true);
    $hadMZ=is_array($variables)&&array_key_exists('mzID',$variables);$previousMZ=$variables['mzID']??null;
    $previous=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$p;
    DecisionQueueController::StoreVariable('mzID',$source['mzID']??'');
    try{
        // The earned draw/discard does not depend on this attack surviving.
        $six=FaBMONDrawDiscard($p);$source=FaBMONPulpingSource($trigger);
        if($six&&$source)FaBWTRTag($source['object'],'DOMINATE');
    }finally{
        if($hadMZ)DecisionQueueController::StoreVariable('mzID',$previousMZ);else DecisionQueueController::ClearVariable('mzID');
        $GLOBALS['fabEffectController']=$previous;
    }
}
function FaBMONAttack(int $p,object $o): void {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();
    foreach((array)$o->TurnEffects as $tag)if($tag==='MON_SEEDS')FaBRunSourceMacro('ResolveAbility',$p,'seeds_of_agony_red',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);
    if(in_array($b,['hatchet_of_body','hatchet_of_mind'],true)){
        $other=$b==='hatchet_of_body'?'hatchet_of_mind':'hatchet_of_body';if(FaBMONCount($p,'LAST_'.$other)){
            $uid=intval(FaBObjectCounters($o)['WEAPON_UID']??0);$w=FaBFindUID($uid);if($w)FaBWTRTag($w['object'],'WTR_POWER:1');}}
    FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>!str_starts_with($e['type']??'','MON_LAST_'))));FaBMONAdd($p,'LAST_'.$b);
}
function FaBMONHit(int $p,object $o): void {
    if(!FaBFaiHeroHit())return;$s=FaBGetState();$target=intval($s['defender']);
    foreach((array)$o->TurnEffects as $tag){
        if($tag==='MON_SOUL_HIT'){FaBMONToSoul($p,intval($o->UniqueID));}
        if($tag==='MON_BOTTOM_HIT')FaBARCToDeck($p,intval($o->UniqueID),false);
        if($tag==='MON_PUPPET_HIT')FaBRunSourceMacro('Hit',$p,'shadow_puppetry_red',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']??'']);
        if($tag==='MON_DUSK_HIT'){$w=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));if($w)FaBWTRTag($w['object'],'CRU_EXTRA_ATTACK');}
    }
    if(FaBWTRIsWeapon($o))for($i=0;$i<FaBMONCount($p,'LUMINA');++$i){$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)break;FaBRevealChoices($p,$r);if(FaBHasType($f['object'],'Light')){FaBMoveUID(intval($f['object']->UniqueID),'Soul',$p);FaBCRUGainLife($p,1);}else FaBARCToDeck($p,intval($f['object']->UniqueID),false);}
    foreach(FaBLiveSeats() as $owner)if(FaBMONCount($owner,'ECLIPSE_EXISTENCE')&&FaBHasType(GetHero($target)[0],'Light'))FaBRunSourceMacro('Hit',$owner,'eclipse_existence_blue',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']??'','amount'=>1]);
}
/** Current Illuminate WHEN-hit: capture the earned layer before its source moves. */
function FaBMONQueueIlluminateHit(int $p,string $mzID,int $amount): void {
    $f=FaBIdentityFromMZ($mzID);$s=FaBGetState();
    if($amount<=0||!$f||$f['zone']!=='CombatChain')return;
    $source=$f['object'];$id=(string)$source->CardID;$uid=intval($source->UniqueID);
    if(!in_array($id,['illuminate_red','illuminate_yellow','illuminate_blue'],true)
        ||($source->Role??'')!=='ATTACK'||empty($s['combatOpen'])
        ||intval($s['attackUID']??0)!==$uid||intval($s['attacker']??0)!==$p
        ||intval($source->Controller??0)!==$p||!FaBSeatIsLive($p)
        ||HasNoAbilities($source)||FaBMPGSuppressed($source)||FaBSUPSuppressed($source)
        ||FaBCRUHitSuppressed($source))return;
    $owner=intval($source->Owner??0);$turn=intval(GetTurnNumber());$link=intval($source->ChainLink??0);
    if($uid<=0||!in_array($owner,FaBSeatOrder(),true)||$turn<=0||$link<=0
        ||intval($s['chainLink']??0)!==$link)return;
    // No hero-only gate: current text triggers whenever this attack hits.
    // The captured controller owns the layer and its "your soul" destination.
    FaBROSQueue($p,$id,$uid,['rosEvent'=>'monIlluminateHit','monIlluminateOwner'=>$owner,
        'monIlluminateCardID'=>$id,'monIlluminateTurn'=>$turn,'monIlluminateChainLink'=>$link]);
}

function FaBMONResolveIlluminateHit(int $p,object $trigger): void {
    $data=(array)($trigger->Params??[]);$id=(string)$trigger->CardID;
    $uid=intval($trigger->SourceUniqueID);$owner=intval($data['monIlluminateOwner']??0);
    if(($trigger->Kind??'')!=='ABILITY'||($data['rosEvent']??'')!=='monIlluminateHit'
        ||!in_array($id,['illuminate_red','illuminate_yellow','illuminate_blue'],true)
        ||($data['monIlluminateCardID']??'')!==$id||$uid<=0
        ||intval($trigger->Controller??0)!==$p||!FaBSeatIsLive($p)
        ||!in_array($owner,FaBSeatOrder(),true))return;
    $f=FaBFindUID($uid);$s=FaBGetState();
    // Re-find the same live attack; never resurrect an already departed source.
    if(!$f||$f['zone']!=='CombatChain'||(string)$f['object']->CardID!==$id
        ||intval($f['object']->Owner??0)!==$owner||($f['object']->Role??'')!=='ATTACK'
        ||empty($s['combatOpen'])||intval($s['attackUID']??0)!==$uid
        ||intval($data['monIlluminateTurn']??0)<=0||intval($data['monIlluminateTurn'])!==intval(GetTurnNumber())
        ||intval($data['monIlluminateChainLink']??0)<=0
        ||intval($data['monIlluminateChainLink'])!==intval($s['chainLink']??0)
        ||intval($data['monIlluminateChainLink'])!==intval($f['object']->ChainLink??0))return;
    // Ability loss/control changes after earning do not counter the independent layer.
    // Existing ToSoul preserves attack go again before moving the actual UID.
    FaBMONToSoul($p,$uid);
}

function FaBMONToSoul(int $p,int $uid): void {
    $f=FaBFindUID($uid);if(!$f)return;$s=FaBGetState();
    if($f['zone']==='CombatChain'&&intval($s['attackUID'])===$uid){$s['attackGoAgain']=FaBAttackHasGoAgain($s,$f['object']);FaBSetState($s);}
    FaBMoveUID($uid,'Soul',$p);
}
function FaBMONSelect(int $p,string $zone,string $kind): string {
    return implode('&',array_filter(FaBChoiceRefs($p,$zone),function($r)use($kind){$o=FaBIdentityFromMZ($r)['object'];return match($kind){
        'DEBT'=>FaBHasKeyword($o,'Blood Debt'),'NAA_DEBT'=>FaBHasKeyword($o,'Blood Debt')&&FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o),
        'SMALL_AA'=>FaBWTRIsAttackAction($o)&&intval(CardPower($o->CardID))<=3,default=>false};}));
}
function FaBMONSetCost(int $uid,int $cost): void {$s=FaBGetState();if(intval($s['pendingPayment']['uid']??0)===$uid){$s['pendingPayment']['cost']=max(0,$cost);FaBSetState($s);}}
function FaBMONSelectedDebt(string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBHasKeyword($f['object'],'Blood Debt'))++$n;}return $n;}
function FaBMONDiscard(int $p,string $refs): void {foreach(explode('&',$refs) as $r)FaBDiscardChoice($p,$r);}
function FaBMONAbilityData(int $p,string $key) {return DecisionQueueController::GetVariable('monAbility_'.$key);}
function FaBMONDrawDiscard(int $p): bool {DoDrawCard($p,1);$ids=FaBDiscardRandom($p,1);$o=FaBFindUID(intval($ids[0]??0));return $o&&intval(CardPower($o['object']->CardID))>=6;}
function FaBMONCombatTargets(): string {
    $refs=[];$s=FaBGetState();foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain',['attackAction'=>true]) as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['ATTACK','DEFENSE','DEFENSE_REACTION'],true))$refs[]=$r;}return implode('&',$refs);
}
function FaBMONAllGraves(): string {$out=[];foreach(FaBLiveSeats() as $p)$out=array_merge($out,FaBChoiceRefs($p,'Graveyard'));return implode('&',$out);}
function FaBMONBanishSelected(string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f){FaBMoveUID(intval($f['object']->UniqueID),'Banish',$f['player']);++$n;}}return $n;}
function FaBMONStageHand(int $viewer,int $owner): array {
    $uids=[];foreach(FaBChoiceRefs($owner,'Hand') as $r){$uid=intval(FaBIdentityFromMZ($r)['object']->UniqueID);$uids[]=$uid;FaBMoveUID($uid,'Temp',$viewer,false);}return $uids;
}
function FaBMONRestoreHand(int $owner,array $uids,int $bottom=0): void {
    foreach($uids as $uid)if(FaBFindUID(intval($uid))){if(intval($uid)===$bottom)FaBARCToDeck($owner,intval($uid),false);else FaBMoveUID(intval($uid),'Hand',$owner,false);}
    if($bottom)DoDrawCard($owner,1);
}
function FaBMONRevealPower(string $chosen): int {$n=0;foreach(explode('&',$chosen) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBWTRIsAttackAction($f['object']))$n+=$f['object']->CardID==='mutated_mass_blue'?FaBMONMass($f['player']):intval(CardPower($f['object']->CardID));}return $n;}
function FaBMONGlisten(int $p,string $ref,int $n): void {FaBMONAdd($p,'GLISTEN',1,['expiresAfterTurnOf'=>$p]);$f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||!FaBWTRIsWeapon($f['object']))return;$o=$f['object'];FaBSetObjectCounter($o,'POWER',intval(FaBObjectCounters($o)['POWER']??0)+$n);FaBSetObjectCounter($o,'MON_GLISTEN',intval(FaBObjectCounters($o)['MON_GLISTEN']??0)+$n);}
function FaBMONSpellvoidRefs(int $p): string {
    $f=FaBIdentityFromMZ((string)DecisionQueueController::GetVariable('mzID'));if($f&&FaBEVRUnpreventable(intval($f['player']),$p))return '';
    $out=[];foreach(['Equipment','Arena','CombatChain'] as $zone)foreach(FaBChoiceRefs($p,$zone) as $r){$o=FaBIdentityFromMZ($r)['object'];if($zone==='CombatChain'&&($o->FromZone??'')!=='Equipment')continue;if(FaBHasKeyword($o,'Spellvoid'))$out[]=$r;}return implode('&',$out);
}
function FaBMONSpellvoid(int $p,string $r): int {
    if(!in_array($r,explode('&',FaBMONSpellvoidRefs($p)),true))return 0;$o=FaBIdentityFromMZ($r)['object'];$n=$o->CardID==='arcanite_fortress'?FaBROSArcanite($p):0;
    foreach(FaBKeywords($o) as $k)if(preg_match('/Spellvoid (\d+)/i',$k,$m))$n=max($n,intval($m[1]));FaBMONDestroy(intval($o->UniqueID));return $n;
}
function FaBMONHigherLight(int $p): bool {foreach(FaBOpponents($p) as $other)if(FaBHasType(GetHero($other)[0],'Light')&&FaBPENLifeMore($p,$other))return true;return false;}
function FaBMONHasBoth(string $refs): bool {$aa=false;$naa=false;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if(!$f)continue;$aa=$aa||FaBWTRIsAttackAction($f['object']);$naa=$naa||(FaBHasType($f['object'],'Action')&&!FaBWTRIsAttackAction($f['object']));}return $aa&&$naa;}
function FaBMONNonAttacks(string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBHasType($f['object'],'Action')&&!FaBWTRIsAttackAction($f['object']))++$n;}return $n;}
function FaBMONExtraWeapons(int $p): void {foreach(FaBChoiceRefs($p,'Weapons') as $r)FaBWTRTag(FaBIdentityFromMZ($r)['object'],'CRU_EXTRA_ATTACK');}
function FaBMONFootstepsBlock(int $uid): void {
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&!FaBHasType($a['object'],'Illusionist')&&FaBAttackPower($s)>=6)FaBTagUID($uid,'DESTROY_ON_CHAIN_CLOSE');
}
function FaBMONPitchValue(int $p,string $id): int {
    return max(0,intval(CardPitch($id)))+(FaBMONCount($p,'SOUL_ADDED')&&FaBHasType($id,'Light')&&FaBCRUEquipment($p,'vestige_of_sol')?1:0);
}
function FaBMONRetributionSoul(int $p,int $uid): void {$f=FaBFindUID($uid);if($f&&$f['zone']==='Graveyard')FaBMoveUID($uid,'Soul',$p);}
function FaBMONShadowCombatTarget(): bool {foreach(explode('&',FaBMONCombatTargets()) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBHasType($f['object'],'Shadow'))return true;}return false;}
function FaBMONWeaponChoices(int $p): string {return implode('&',array_merge(FaBChoiceRefs($p,'Weapons'),FaBChoiceRefs($p,'Arena',['type'=>'Weapon'])));}
function FaBMONDefended(object $block): void {
    if(!FaBWTRIsAttackAction($block))return;$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f)return;$o=$f['object'];
    $tags=(array)$o->TurnEffects;if(in_array('MON_PLOW_USED',$tags,true))return;$n=count(array_filter($tags,fn($t)=>$t==='MON_PLOW'));if(!$n)return;
    $w=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));if($w){FaBWTRTag($w['object'],'WTR_POWER:'.$n);FaBWTRTag($o,'MON_PLOW_USED');}
}
function FaBMONPitchAfterCharge(int $p,array $excluded): int {
    $total=intval(GetResources($p));$vestige=count(FaBCRUEquipment($p,'vestige_of_sol'))>0;
    foreach(FaBChoiceRefs($p,'Hand') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(in_array($ref,$excluded,true)||FaBARCNamedProhibited($o->CardID))continue;
        $total+=max(0,intval(CardPitch($o->CardID)))+($vestige&&FaBHasType($o,'Light')?1:0);
    }return $total;
}
function FaBMONAffordableHand(int $p,string $kind='',bool $charge=false): string {
    $cost=intval(FaBGetState()['pendingPayment']['cost']??0);
    return implode('&',array_filter(FaBChoiceRefs($p,'Hand'),function($r)use($p,$kind,$cost,$charge){$o=FaBIdentityFromMZ($r)['object'];
        $remaining=$charge?FaBMONPitchAfterCharge($p,[$r]):FaBAvailablePitch($p)-FaBMONPitchValue($p,$o->CardID);
        return ($kind!=='DEBT'||FaBHasKeyword($o,'Blood Debt'))&&$remaining>=$cost;
    }));
}
// Reject a multi-card charge that would make the announced resource cost unpayable.
function GameValidateDecisionAnswer($player,$answer): bool {
    if(function_exists('SWUValidateDecisionAnswer')&&!SWUValidateDecisionAnswer($player,$answer))return false;
    $d=GetDecisionQueue(intval($player))[0]??null;$s=FaBGetState();
    if (in_array(($d->Tooltip ?? ''), ['Choose_Arcanic_Crackle_trigger_target','Choose_Vexing_Malice_trigger_target'], true)) return FaBMONArcanicAnswerIsValid(intval($player), (string)$answer);
    if (isset($s['pendingLungingTarget']) || ($d->Tooltip ?? '') === 'Choose_Lunging_Press_target') {
        return FaBCRULungingAnswerIsValid(intval($player), (string)$answer);
    }
    if (FaBIsDiscardRequestDecision(intval($player))) return FaBDiscardRequestAnswerIsValid(intval($player), (string)$answer);
    if ($d && isset($s['pendingPayment']['wtrFlockStage'])
        && ($s['pendingPayment']['wtrFlockStage'] ?? '') === 'REVEAL'
        && intval($s['pendingPayment']['player'] ?? 0) === intval($player)
        && $d->Tooltip === 'Reveal_a_card_for_the_additional_cost') {
        return $d->Type === 'MZCHOOSE'
            && in_array((string)$answer, explode('&', (string)$d->Param), true)
            && FaBWTRFlockRevealAnswerIsValid(intval($player), (string)$answer);
    }
    if($d&&$d->Tooltip==='Choose_two_Earth_cards')return count(FaBUPRUIDs((string)$answer))===2&&FaBROSActionExcept(intval($player),(string)$answer)!=='';
    if(!$d||!$s['pendingPayment']||intval($s['pendingPayment']['player'])!==intval($player))return true;
    if($d->Tooltip==='Choose_three_different_names'){ $refs=explode('&',(string)$answer);$names=[];foreach($refs as $r){$f=FaBIdentityFromMZ($r);if(!$f)return false;$names[]=CardName($f['object']->CardID);}return count($refs)===3&&count(array_unique($names))===3;}
    if(!in_array($d->Tooltip,['Charge_your_soul','Charge_any_number'],true))return true;
    $chosen=array_values(array_filter(array_unique(explode('&',(string)$answer)),fn($r)=>in_array($r,FaBChoiceRefs(intval($player),'Hand'),true)));
    $remaining=$chosen?FaBMONPitchAfterCharge(intval($player),$chosen):FaBAvailablePitch(intval($player));
    return $remaining>=intval($s['pendingPayment']['cost']);
}

/** Finite Arcanic Crackle and Vexing Malice attack-trigger cohorts only. */
function FaBMONArcanicCard(string $id): bool {
    return in_array($id,['arcanic_crackle_red','arcanic_crackle_yellow','arcanic_crackle_blue','vexing_malice_red','vexing_malice_yellow','vexing_malice_blue'],true);
}

function FaBMONArcanicTooltip(string $id): string {
    return str_starts_with($id,'vexing_malice_')?'Choose_Vexing_Malice_trigger_target':'Choose_Arcanic_Crackle_trigger_target';
}

function FaBMONArcanicTrigger(object $o): bool {
    return ($o->Kind??'')==='TRIGGER'&&FaBMONArcanicCard((string)$o->CardID)
        &&($o->SourceZone??'')==='CombatChain'&&intval($o->SourceUniqueID??0)>0
        &&($o->Params['monArcanicTrigger']??false)===true;
}

/** Target declaration is completed before any response to this earned layer. */
function FaBMONQueueArcanicAttack(int $p,object $attack): void {
    $s=FaBGetState();$uid=intval($attack->UniqueID??0);
    if(!FaBMONArcanicCard((string)$attack->CardID)||$uid<1||!FaBSeatIsLive($p)
        ||intval($attack->Controller??0)!==$p||intval($s['attacker']??0)!==$p
        ||intval($s['attackUID']??0)!==$uid||HasNoAbilities($attack))return;
    $refs=[];$options=[];
    foreach(explode('&',FaBARCHeroTargets($p,false)) as $ref){
        $f=FaBIdentityFromMZ($ref);if(!$f||$f['zone']!=='Hero')continue;
        $targetUID=intval($f['object']->UniqueID??0);if($targetUID<1)continue;
        $refs[]=$ref;$options[(string)$targetUID]=intval($f['player']);
    }
    if(!$refs)return;
    $trigger=AddStack(CardID:(string)$attack->CardID,Owner:$p,Controller:$p,Kind:'TRIGGER',
        SourceZone:'CombatChain',SourceUniqueID:$uid,Params:['monArcanicTrigger'=>true,
            'monArcanicPending'=>true,'monArcanicOptions'=>$options]);
    DecisionQueueController::AddDecision($p,'MZCHOOSE',implode('&',$refs),1,FaBMONArcanicTooltip((string)$attack->CardID));
    DecisionQueueController::AddDecision($p,'CUSTOM','FAB_MON_ARCANIC_TARGET|'.intval($trigger->UniqueID),1,'',1);
    $s=FaBGetState();$s['window']='PRIORITY';FaBSetState($s);
    SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);
}

function FaBMONArcanicPending(int $p,int $uid): ?object {
    $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Stack')return null;$o=$f['object'];
    return FaBMONArcanicTrigger($o)&&intval($o->Controller??0)===$p
        &&($o->Params['monArcanicPending']??false)===true?$o:null;
}

function FaBMONArcanicChoice(int $p,int $uid,string $answer): ?array {
    $o=FaBMONArcanicPending($p,$uid);$f=FaBIdentityFromMZ($answer);
    if(!$o||!$f||$f['zone']!=='Hero'||!FaBSeatIsLive(intval($f['player'])))return null;
    $targetUID=intval($f['object']->UniqueID??0);$options=(array)($o->Params['monArcanicOptions']??[]);
    if($targetUID<1||!array_key_exists((string)$targetUID,$options)
        ||intval($options[(string)$targetUID])!==intval($f['player'])
        ||!in_array($answer,explode('&',FaBARCHeroTargets($p,false)),true))return null;
    return ['uid'=>$targetUID,'seat'=>intval($f['player'])];
}

/** Validation happens before Core consumes the choice; malformed/PASS/stale replies spend nothing. */
function FaBMONArcanicAnswerIsValid(int $p,string $answer): bool {
    $q=GetDecisionQueue($p);$choice=$q[0]??null;$next=$q[1]??null;
    if(!$choice||($choice->Type??'')!=='MZCHOOSE'
        ||!in_array(($choice->Tooltip??''),['Choose_Arcanic_Crackle_trigger_target','Choose_Vexing_Malice_trigger_target'],true)
        ||!$next||($next->Type??'')!=='CUSTOM')return false;
    $parts=explode('|',(string)($next->Param??''));
    $pending=FaBMONArcanicPending($p,intval($parts[1]??0));
    return count($parts)===2&&$parts[0]==='FAB_MON_ARCANIC_TARGET'&&$pending
        &&($choice->Tooltip??'')===FaBMONArcanicTooltip((string)$pending->CardID)
        &&FaBMONArcanicChoice($p,intval($parts[1]),$answer)!==null;
}

function FaBMONArcanicBind(int $p,int $uid,string $answer): bool {
    if(DecisionQueueController::ExecutingSeat()!==$p)return false;
    $target=FaBMONArcanicChoice($p,$uid,$answer);$o=FaBMONArcanicPending($p,$uid);
    if(!$target||!$o)return false;
    $o->Params['monArcanicTargetUID']=$target['uid'];$o->Params['monArcanicTargetSeat']=$target['seat'];
    $o->Params['monArcanicPending']=false;unset($o->Params['monArcanicOptions']);
    SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);
    return true;
}

/** Revalidate the declared identity at resolution, never select a replacement target. */
function FaBMONArcanicTargetSeat(int $p,int $uid): int {
    $f=FaBFindUID($uid);$pending=FaBGetState()['resolvingReaction']??null;
    if(!$f||$f['zone']!=='Stack'||!is_array($pending)
        ||($pending['resolutionClass']??'')!=='MON_ARCANIC_TRIGGER'
        ||intval($pending['uid']??0)!==$uid||intval($pending['controller']??0)!==$p)return 0;
    $o=$f['object'];if(!FaBMONArcanicTrigger($o)||intval($o->Controller??0)!==$p
        ||!empty($o->Params['monArcanicPending']))return 0;
    $target=FaBFindUID(intval($o->Params['monArcanicTargetUID']??0));
    if(!$target||$target['zone']!=='Hero'||!FaBSeatIsLive(intval($target['player']))
        ||intval($target['player'])!==intval($o->Params['monArcanicTargetSeat']??0)
        ||!in_array($target['mzID'],explode('&',FaBARCHeroTargets($p,false)),true))return 0;
    return intval($target['player']);
}

function FaBMONResolveArcanicTrigger(int $p,object $trigger): void {
    if(FaBMONArcanicTargetSeat($p,intval($trigger->UniqueID))>0)FaBResolveRules($p,$trigger,$trigger);
}

/** The await frame owns the virtual layer; damage/prevention use the actual attack's stable UID. */
function FaBMONArcanicDamageSourceUID(int $p,string $mzID): int {
    $f=FaBIdentityFromMZ($mzID);if(!$f||!FaBMONArcanicTargetSeat($p,intval($f['object']->UniqueID??0)))return 0;
    return intval($f['object']->SourceUniqueID??0);
}

function FaBMONArcanicDealArcane(int $p,int $target,int $amount,int $payment=0): int {
    $mzID=(string)DecisionQueueController::GetVariable('mzID');$layer=FaBIdentityFromMZ($mzID);
    if(!$layer||FaBMONArcanicTargetSeat($p,intval($layer['object']->UniqueID??0))!==$target)return 0;
    $source=FaBFindUID(FaBMONArcanicDamageSourceUID($p,$mzID));
    // Missing-source LKI modifiers retain the existing virtual-source fallback; no source resurrection.
    DecisionQueueController::StoreVariable('mzID',$source['mzID']??$mzID);
    try {return FaBARCDealArcane($p,$target,$amount,$payment);}
    finally {DecisionQueueController::StoreVariable('mzID',$mzID);}
}


/** Captain's Call Red: mode is an announcement declaration, before asset payment. */
function FaBMONCaptainPending(int $player, int $uid): ?array {
    $source = FaBFindUID($uid); $pending = FaBGetState()['pendingPayment'] ?? null;
    if ($source === null || $source['zone'] !== 'Stack' || !empty($source['object']->removed)
        || (string)$source['object']->CardID !== 'captains_call_red'
        || (string)($source['object']->Kind ?? '') !== 'ACTION'
        || intval($source['object']->Controller ?? 0) !== $player
        || !FaBSeatIsLive($player) || !is_array($pending)
        || !empty($pending['isAbility']) || !empty($pending['isWeaponAttack']) || !empty($pending['isArenaAttack'])
        || (string)($pending['kind'] ?? '') !== 'ACTION' || !empty($pending['preparedEffectError'])
        || intval($pending['player'] ?? 0) !== $player || intval($pending['uid'] ?? 0) !== $uid) return null;
    return $source;
}

function FaBMONBeginCaptainMode(int $player, int $uid): int {
    $source = FaBMONCaptainPending($player, $uid);
    if ($source === null || HasNoAbilities($source['object'])) return 0;
    $token = intval(FaBARCCard($uid, 'monCaptainAnnouncement', 0)) + 1;
    FaBARCSetCard($uid, 'monCaptainAnnouncement', $token);
    $versions = GetVersions($player); $version = $versions[0] ?? null;
    $source['object']->Params['monCaptainMode'] = [
        'stage'=>'CHOOSING', 'player'=>$player, 'announcement'=>$token,
        'undoHash'=>is_object($version) ? hash('sha256', (string)$version->Version) : ''];
    return $token;
}

function FaBMONRejectCaptainMode(int $player, object $source): void {
    $expected = (string)($source->Params['monCaptainMode']['undoHash'] ?? '');
    $versions = GetVersions($player); $version = $versions[0] ?? null;
    if ($expected !== '' && is_object($version)
        && hash_equals($expected, hash('sha256', (string)$version->Version))) {
        LoadVersion($player, 0);
        return;
    }
    $state = FaBGetState();
    if (intval($state['pendingPayment']['uid'] ?? 0) === intval($source->UniqueID)) {
        $state['pendingPayment']['preparedEffectError'] = "Captain's Call mode declaration is invalid.";
        FaBSetState($state);
    }
}

function FaBMONBindCaptainMode(int $player, int $uid, int $token, string $answer): bool {
    $source = FaBMONCaptainPending($player, $uid);
    if ($source === null) return false;
    $o = $source['object']; $choice = (array)($o->Params['monCaptainMode'] ?? []);
    if (DecisionQueueController::ExecutingSeat() !== $player
        || ($choice['stage'] ?? '') !== 'CHOOSING' || intval($choice['player'] ?? 0) !== $player
        || $token < 1 || intval($choice['announcement'] ?? 0) !== $token
        || intval(FaBARCCard($uid, 'monCaptainAnnouncement', 0)) !== $token) return false;
    if (HasNoAbilities($o) || !in_array($answer, ['0','1'], true)) {
        FaBMONRejectCaptainMode($player, $o);
        return false;
    }
    $o->Params['monCaptainMode'] = ['stage'=>'SEALED', 'player'=>$player,
        'announcement'=>$token, 'mode'=>$answer];
    return true;
}

/** Native admission until the hosted source includes the matching canonical PrepareCard. */
function FaBMONPrepareCaptainBridge(int $player, int $uid): void {
    $token = FaBMONBeginCaptainMode($player, $uid);
    if ($token < 1) return;
    DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|Power&Go_again', 1, 'Choose_Captains_Call_mode');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'FAB_MON_CAPTAIN_MODE|'.$uid.'|'.$token, 1, '', 1);
}

function FaBMONResolveCaptainMode(int $player, string $mzID): void {
    $source = FaBIdentityFromMZ($mzID);
    if ($source === null || $source['zone'] !== 'Stack' || !empty($source['object']->removed)) return;
    $o = $source['object']; $choice = (array)($o->Params['monCaptainMode'] ?? []);
    if ((string)$o->CardID !== 'captains_call_red' || (string)($o->Kind ?? '') !== 'ACTION'
        || intval($o->Controller ?? 0) !== $player || !FaBSeatIsLive($player) || HasNoAbilities($o)
        || ($choice['stage'] ?? '') !== 'SEALED' || intval($choice['player'] ?? 0) !== $player
        || intval($choice['announcement'] ?? 0) < 1
        || intval($choice['announcement']) !== intval(FaBARCCard(intval($o->UniqueID), 'monCaptainAnnouncement', 0))
        || !in_array($choice['mode'] ?? null, ['0','1'], true)) return;
    $mode = $choice['mode'];
    $o->Params['monCaptainMode'] = ['stage'=>'CONSUMED'];
    FaBMONAdd($player, 'NEXT_SMALL', $mode === '0' ? 2 : 0, ['maxCost'=>2, 'goAgain'=>$mode === '1']);
}

/** Memorial Ground Blue: declare an own-Graveyard cost0 AA before assets/priority. */
function FaBMONMemorialPending(int $player,int $uid): ?array {
 $source=FaBFindUID($uid);$pending=FaBGetState()['pendingPayment']??null;
 if($source===null||$source['zone']!=='Stack'||!FaBSeatIsLive($player)||!is_array($pending))return null;
 $o=$source['object'];
 if($o->CardID!=='memorial_ground_blue'||($o->Kind??'')!=='INSTANT'||intval($o->Controller??0)!==$player
  ||intval($pending['uid']??0)!==$uid||intval($pending['player']??0)!==$player
  ||($pending['kind']??'')!=='INSTANT'||!empty($pending['isAbility'])||!empty($pending['isWeaponAttack'])
  ||!empty($pending['isArenaAttack'])||!empty($pending['preparedEffectError']))return null;
 return $source;
}
function FaBMONMemorialChoosing(int $player,int $uid,int $token): ?array {
 $source=FaBMONMemorialPending($player,$uid);if($source===null||$token<1)return null;
 $choice=$source['object']->Params['monMemorialTarget']??null;
 if(!is_array($choice)||($choice['stage']??'')!=='CHOOSING'||intval($choice['player']??0)!==$player
  ||intval($choice['announcement']??0)!==$token||intval(FaBARCCard($uid,'monMemorialAnnouncement',0))!==$token)return null;
 return $source;
}
/** Only the current owned declaration may roll itself back. Stale callbacks never reach this helper. */
function FaBMONRejectMemorialTarget(int $player,int $uid,int $token): void {
 $source=FaBMONMemorialChoosing($player,$uid,$token);if($source===null)return;
 $expected=(string)($source['object']->Params['monMemorialTarget']['undoHash']??'');
 $versions=GetVersions($player);$version=$versions[0]??null;
 if($expected!==''&&is_object($version)&&hash_equals($expected,hash('sha256',(string)$version->Version))){LoadVersion($player,0);return;}
 $state=FaBGetState();$state['pendingPayment']['preparedEffectError']='Memorial Ground target declaration is invalid.';FaBSetState($state);
}
function FaBMONBeginMemorialTarget(int $player,int $uid): int {
 $source=FaBMONMemorialPending($player,$uid);if($source===null||HasNoAbilities($source['object']))return 0;
 $token=intval(FaBARCCard($uid,'monMemorialAnnouncement',0))+1;FaBARCSetCard($uid,'monMemorialAnnouncement',$token);
 $offered=[];
 foreach(array_filter(explode('&',FaBARCSelect($player,'Graveyard','','AA',0))) as $ref){
  $target=FaBIdentityFromMZ($ref);if($target===null||$target['player']!==$player||$target['zone']!=='Graveyard')continue;
  $targetUID=intval($target['object']->UniqueID??0);if($targetUID<1)continue;
  $offered[(string)$targetUID]=FaBArmCapturedSourceEpoch($targetUID,'monMemorialGraveEpoch');
 }
 $versions=GetVersions($player);$version=$versions[0]??null;
 $source['object']->Params['monMemorialTarget']=['stage'=>'CHOOSING','player'=>$player,'announcement'=>$token,
  'offered'=>$offered,'undoHash'=>is_object($version)?hash('sha256',(string)$version->Version):''];
 if(!$offered){FaBMONRejectMemorialTarget($player,$uid,$token);return 0;}
 return $token;
}
function FaBMONMemorialTargetChoices(int $player,int $uid,int $token): string {
 $source=FaBMONMemorialChoosing($player,$uid,$token);if($source===null)return '';
 $offered=(array)$source['object']->Params['monMemorialTarget']['offered'];$out=[];
 foreach(array_filter(explode('&',FaBARCSelect($player,'Graveyard','','AA',0))) as $ref){
  $target=FaBIdentityFromMZ($ref);if($target===null)continue;$targetUID=intval($target['object']->UniqueID??0);
  if(isset($offered[(string)$targetUID])&&intval($offered[(string)$targetUID])===intval(FaBARCCard($targetUID,'monMemorialGraveEpoch',0)))$out[]=$ref;
 }
 if(!$out)FaBMONRejectMemorialTarget($player,$uid,$token);
 return implode('&',$out);
}
function FaBMONBindMemorialTarget(int $player,int $uid,int $token,string $answer): bool {
 if(DecisionQueueController::ExecutingSeat()!==$player)return false;
 $source=FaBMONMemorialChoosing($player,$uid,$token);if($source===null)return false;
 $o=$source['object'];$choice=$o->Params['monMemorialTarget'];$target=FaBIdentityFromMZ($answer);
 $targetUID=$target===null?0:intval($target['object']->UniqueID??0);$epoch=intval($choice['offered'][(string)$targetUID]??0);
 if(HasNoAbilities($o)||$target===null||$target['zone']!=='Graveyard'||$target['player']!==$player
  ||$targetUID<1||$epoch<1||$epoch!==intval(FaBARCCard($targetUID,'monMemorialGraveEpoch',0))
  ||!in_array($answer,explode('&',FaBARCSelect($player,'Graveyard','','AA',0)),true)){
  FaBMONRejectMemorialTarget($player,$uid,$token);return false;
 }
 $o->Params['monMemorialTarget']=['stage'=>'SEALED','player'=>$player,'announcement'=>$token,
  'targetUID'=>$targetUID,'targetEpoch'=>$epoch,'targetPlayer'=>$player];
 return true;
}
/** Local admission while the hosted row still has no PrepareCard macro. */
function FaBMONPrepareMemorialBridge(int $player,int $uid): void {
 $token=FaBMONBeginMemorialTarget($player,$uid);if($token<1)return;
 $refs=FaBMONMemorialTargetChoices($player,$uid,$token);if($refs==='')return;
 DecisionQueueController::AddDecision($player,'MZCHOOSE',$refs,1,'Choose_a_card');
 DecisionQueueController::AddDecision($player,'CUSTOM','FAB_MON_MEMORIAL_TARGET|'.$uid.'|'.$token,1,'',1);
}
function FaBMONResolveMemorialTarget(int $player,string $mzID): void {
 $source=FaBIdentityFromMZ($mzID);if($source===null||$source['zone']!=='Stack')return;
 $o=$source['object'];$uid=intval($o->UniqueID??0);$choice=$o->Params['monMemorialTarget']??null;
 if($o->CardID!=='memorial_ground_blue'||($o->Kind??'')!=='INSTANT'||intval($o->Controller??0)!==$player
  ||!FaBSeatIsLive($player)||HasNoAbilities($o)||!is_array($choice)||($choice['stage']??'')!=='SEALED'
  ||intval($choice['player']??0)!==$player||intval($choice['announcement']??0)<1
  ||intval($choice['announcement'])!==intval(FaBARCCard($uid,'monMemorialAnnouncement',0)))return;
 // Consume the earned resolution even if its formal target became illegal. Never retarget/revive it.
 $o->Params['monMemorialTarget']=['stage'=>'CONSUMED'];
 $targetUID=intval($choice['targetUID']??0);$target=FaBFindUID($targetUID);
 if($target===null||$target['zone']!=='Graveyard'||$target['player']!==$player
  ||intval($choice['targetPlayer']??0)!==$player||intval($choice['targetEpoch']??0)<1
  ||intval($choice['targetEpoch'])!==intval(FaBARCCard($targetUID,'monMemorialGraveEpoch',0))
  ||!in_array($target['mzID'],explode('&',FaBARCSelect($player,'Graveyard','','AA',0)),true))return;
 FaBRevealChoices($player,$target['mzID']);FaBARCToDeck($player,$targetUID,true);
}
